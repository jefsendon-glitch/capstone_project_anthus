<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\NewDeliveryOrderPlaced;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryService
{
    public function __construct(private readonly SalesService $sales, private readonly CreditAccountService $creditAccounts, private readonly ProductOrderLines $orderLines)
    {
    }

    /**
     * @param  array{items: array<int, array{product_id:int, quantity:int}>, customer_address:string, payment_method?:string, preferred_delivery_date:?string, notes:?string}  $data
     */
    public function placeOrder(array $data, User $customer): DeliveryOrder
    {
        return DB::transaction(function () use ($data, $customer) {
            // HTTP order submissions must choose a method. This default protects
            // service callers created before payment selection was introduced.
            $paymentMethod = $data['payment_method'] ?? 'cash';

            if ($paymentMethod === 'loan') {
                $this->creditAccounts->ensureEligible($customer);
            }

            $orderItems = $this->orderLines->prepare($data['items'] ?? (isset($data['product_id']) ? [[
                'product_id' => $data['product_id'], 'quantity' => $data['quantity'], 'container_type' => $data['container_type'] ?? null,
            ]] : []));

            $firstItem = $orderItems->first();

            $order = DeliveryOrder::create([
            'order_code' => $this->sales->generateCode('DEL'),
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_address' => $data['customer_address'],
            'product_id' => $firstItem['product']->id,
            'product_name' => $orderItems->count() === 1 ? $firstItem['product']->name : 'Multiple products',
            'container_type' => $orderItems->count() === 1 ? $firstItem['container_type'] : null,
            'quantity' => $orderItems->sum('quantity'),
            'unit_price' => $firstItem['product']->unit_price,
            'total_amount' => $orderItems->sum('total_amount'),
            'payment_method' => $paymentMethod,
            'status' => 'pending',
            'preferred_delivery_date' => $data['preferred_delivery_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'placed_by' => $customer->id,
        ]);

            $order->items()->createMany($orderItems->map(fn ($item) => [
                'product_id' => $item['product']->id,
                'product_name' => $item['product']->name,
                'container_type' => $item['container_type'],
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
        abort_unless($cancelledBy->isAdmin() || $cancelledBy->isStaff(), 403);

        return DB::transaction(function () use ($order, $cancelledBy) {
            $order = DeliveryOrder::lockForUpdate()->findOrFail($order->id);

            if (! in_array($order->status, ['pending', 'confirmed'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending or confirmed delivery orders can be cancelled.',
                ]);
            }

            $order->update([
                'status' => 'cancelled',
                'updated_by' => $cancelledBy->id,
            ]);
            activity('delivery_orders')->causedBy($cancelledBy)->performedOn($order)->event('cancelled')->log('Cancelled a delivery order');

            return $order;
        });
    }

    public function updateStatus(DeliveryOrder $order, string $status, User $updatedBy): DeliveryOrder
    {
        return DB::transaction(function () use ($order, $status, $updatedBy) {
            $order = DeliveryOrder::lockForUpdate()->findOrFail($order->id);
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
            activity('delivery_orders')->causedBy($updatedBy)->performedOn($order)->event('updated')->log('Changed delivery status to '.str_replace('_', ' ', $status));

            return $order;
        });
    }

    public function fulfill(DeliveryOrder $order, ?string $legacyPaymentMethod, User $updatedBy): SalesTransaction
    {
        return DB::transaction(function () use ($order, $legacyPaymentMethod, $updatedBy) {
            $order = DeliveryOrder::lockForUpdate()->with('customer')->findOrFail($order->id);
            $order->setRelation('customer', User::lockForUpdate()->findOrFail($order->customer_id));

            // New orders always use the customer's choice. The fallback is only
            // for orders that existed before a method was selected at checkout.
            $paymentMethod = $order->payment_method ?? $legacyPaymentMethod;
            if (! in_array($paymentMethod, ['cash', 'loan'], true)) {
                throw ValidationException::withMessages([
                    'payment_method' => 'A payment method is required before completing this delivery.',
                ]);
            }

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
                $items = collect([(object) ['product_id' => $order->product_id, 'product_name' => $order->product_name, 'quantity' => $order->quantity, 'unit_price' => $order->unit_price, 'total_amount' => $order->total_amount, 'container_type' => $order->container_type]]);
            }

            $products = [];
            $transaction = null;
            foreach ($items->groupBy('product_id')->sortKeys() as $id => $group) {
                $product = Product::lockForUpdate()->findOrFail($id);
                if ($group->sum('quantity') > $product->stock_quantity) {
                    throw ValidationException::withMessages(['payment_method' => 'Not enough shared water stock for '.$product->name.'.']);
                }
                $products[$id] = $product;
            }

            foreach ($items as $item) {
                $product = $products[$item->product_id];
                $transaction = SalesTransaction::create([
                'transaction_code' => $this->sales->generateCode('TXN'),
                'transaction_type' => 'delivery',
                'customer_id' => $order->customer_id,
                'delivery_order_id' => $order->id,
                'customer_name' => $order->customer_name,
                'product_id' => $product->id,
                'product_name' => $item->product_name,
                'container_type' => $item->container_type ?? null,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_amount' => $item->total_amount,
                'payment_method' => $paymentMethod,
                'credit_due_date' => $paymentMethod === 'loan' ? now()->addDays(CreditAccountService::CREDIT_TERM_DAYS)->toDateString() : null,
                'credit_status' => $paymentMethod === 'loan' ? 'outstanding' : 'not_applicable',
                'processed_by' => $updatedBy->id,
                ]);
                $before = (float) $product->stock_quantity;
                $product->decrement('stock_quantity', $item->quantity);
                StockMovement::record($product, 'sale_deduction', -$item->quantity, $before,
                    (float) $product->stock_quantity, $updatedBy->id, 'Delivery '.$order->order_code,
                    null, $order->customer_id, $transaction->transaction_code);
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
