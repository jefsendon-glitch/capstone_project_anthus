<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\User;
use App\Notifications\NewDeliveryOrderPlaced;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryService
{
    public function __construct(private readonly SalesService $sales, private readonly CreditAccountService $creditAccounts)
    {
    }

    /**
     * @param  array{items: array<int, array{product_id:int, quantity:int}>, customer_address:string, preferred_delivery_date:?string, notes:?string}  $data
     */
    public function placeOrder(array $data, User $customer): DeliveryOrder
    {
        return DB::transaction(function () use ($data, $customer) {
            $items = collect($data['items'] ?? (isset($data['product_id']) ? [[
                'product_id' => $data['product_id'], 'quantity' => $data['quantity'],
            ]] : []))->groupBy('product_id')->map(fn ($lines) => (int) $lines->sum('quantity'));
            $orderItems = collect();

            foreach ($items as $productId => $quantity) {
                $product = Product::lockForUpdate()->findOrFail($productId);
                if (! $product->is_active) {
                    throw ValidationException::withMessages(['items' => "{$product->name} is no longer available for delivery orders."]);
                }
                if ($quantity > $product->stock_quantity) {
                    throw ValidationException::withMessages(['items' => "Only {$product->stock_quantity} {$product->stock_unit_label} of {$product->name} are currently available."]);
                }
                $orderItems->push(['product' => $product, 'quantity' => $quantity, 'total_amount' => $product->unit_price * $quantity]);
            }

            $firstItem = $orderItems->first();

            $order = DeliveryOrder::create([
            'order_code' => $this->sales->generateCode('DEL'),
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_address' => $data['customer_address'],
            'product_id' => $firstItem['product']->id,
            'product_name' => $orderItems->count() === 1 ? $firstItem['product']->name : 'Multiple products',
            'quantity' => $orderItems->sum('quantity'),
            'unit_price' => $firstItem['product']->unit_price,
            'total_amount' => $orderItems->sum('total_amount'),
            'status' => 'pending',
            'preferred_delivery_date' => $data['preferred_delivery_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'placed_by' => $customer->id,
        ]);

            $order->items()->createMany($orderItems->map(fn ($item) => [
                'product_id' => $item['product']->id,
                'product_name' => $item['product']->name,
                'quantity' => $item['quantity'],
                'unit_price' => $item['product']->unit_price,
                'total_amount' => $item['total_amount'],
            ])->all());

            activity('delivery_orders')->causedBy($customer)->performedOn($order)->event('created')->log('Placed a delivery order');

            User::role(['admin', 'staff'])->get()->each->notify(new NewDeliveryOrderPlaced($order));

            return $order;
        });
    }

    public function cancel(DeliveryOrder $order, User $cancelledBy): DeliveryOrder
    {
        if (! in_array($order->status, ['pending', 'confirmed'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only pending or confirmed delivery orders can be cancelled.',
            ]);
        }

        $order->update([
            'status' => 'cancelled',
            'updated_by' => $cancelledBy->id,
        ]);

        return $order;
    }

    public function updateStatus(DeliveryOrder $order, string $status, User $updatedBy): DeliveryOrder
    {
        $allowedTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['out_for_delivery', 'cancelled'],
            'out_for_delivery' => [],
        ];

        if ($order->status === $status) {
            return $order;
        }

        if (! in_array($status, $allowedTransitions[$order->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => 'This delivery status change is not allowed.',
            ]);
        }

        $order->update(['status' => $status, 'updated_by' => $updatedBy->id]);

        return $order;
    }

    public function fulfill(DeliveryOrder $order, string $paymentMethod, User $updatedBy): SalesTransaction
    {
        return DB::transaction(function () use ($order, $paymentMethod, $updatedBy) {
            $order = DeliveryOrder::lockForUpdate()->with('customer')->findOrFail($order->id);

            if (! in_array($order->status, ['confirmed', 'out_for_delivery'], true)) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Confirm the order before marking it as delivered.',
                ]);
            }

            if ($paymentMethod === 'loan') {
                $this->creditAccounts->ensureEligible($order->customer);
            }
            $items = $order->items()->lockForUpdate()->get();
            if ($items->isEmpty()) {
                $items = collect([(object) ['product_id' => $order->product_id, 'product_name' => $order->product_name, 'quantity' => $order->quantity, 'unit_price' => $order->unit_price, 'total_amount' => $order->total_amount]]);
            }

            $products = [];
            $transaction = null;
            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item->product_id);
                if ($item->quantity > $product->stock_quantity) {
                    throw ValidationException::withMessages(['payment_method' => "Not enough stock for {$product->name}. Only {$product->stock_quantity} {$product->stock_unit_label} left."]);
                }
                $products[$item->product_id] = $product;
            }

            foreach ($items as $item) {
                $product = $products[$item->product_id];
                $transaction = SalesTransaction::create([
                'transaction_code' => $this->sales->generateCode('TXN'),
                'transaction_type' => 'delivery',
                'customer_id' => $order->customer_id,
                'customer_name' => $order->customer_name,
                'product_id' => $product->id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_amount' => $item->total_amount,
                'payment_method' => $paymentMethod,
                'credit_due_date' => $paymentMethod === 'loan' ? now()->addDays(CreditAccountService::CREDIT_TERM_DAYS)->toDateString() : null,
                'credit_status' => $paymentMethod === 'loan' ? 'outstanding' : 'not_applicable',
                'processed_by' => $updatedBy->id,
                ]);
                $product->decrement('stock_quantity', $item->quantity);
            }

            if ($paymentMethod === 'loan') {
                $order->customer->increment('credit_balance', $order->total_amount);
            }

            $order->update([
                'status' => 'delivered',
                'payment_method' => $paymentMethod,
                'delivered_at' => now(),
                'updated_by' => $updatedBy->id,
            ]);

            $order->customer?->notify(new OrderStatusUpdated($order));
            activity('delivery_orders')->causedBy($updatedBy)->performedOn($order)->event('fulfilled')->log('Completed a delivery order');

            return $transaction;
        });
    }
}
