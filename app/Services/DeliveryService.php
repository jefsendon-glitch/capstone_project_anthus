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
     * @param  array{product_id:int, quantity:int, customer_address:string, preferred_delivery_date:?string, notes:?string}  $data
     */
    public function placeOrder(array $data, User $customer): DeliveryOrder
    {
        return DB::transaction(function () use ($data, $customer) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);

            if (! $product->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => 'This product is no longer available for delivery orders.',
                ]);
            }

            if ($data['quantity'] > $product->stock_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Only {$product->stock_quantity} {$product->stock_unit_label} of this product are currently available.",
                ]);
            }

            $order = DeliveryOrder::create([
            'order_code' => $this->sales->generateCode('DEL'),
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_address' => $data['customer_address'],
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => $data['quantity'],
            'unit_price' => $product->unit_price,
            'total_amount' => $product->unit_price * $data['quantity'],
            'status' => 'pending',
            'preferred_delivery_date' => $data['preferred_delivery_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'placed_by' => $customer->id,
        ]);

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

            $product = Product::lockForUpdate()->findOrFail($order->product_id);

            if ($paymentMethod === 'loan') {
                $this->creditAccounts->ensureEligible($order->customer);
            }

            if ($order->quantity > $product->stock_quantity) {
                throw ValidationException::withMessages([
                    'payment_method' => "Not enough stock for {$product->name}. Only {$product->stock_quantity} {$product->stock_unit_label} left.",
                ]);
            }

            $transaction = SalesTransaction::create([
                'transaction_code' => $this->sales->generateCode('TXN'),
                'transaction_type' => 'delivery',
                'customer_id' => $order->customer_id,
                'customer_name' => $order->customer_name,
                'product_id' => $product->id,
                'product_name' => $order->product_name,
                'quantity' => $order->quantity,
                'unit_price' => $order->unit_price,
                'total_amount' => $order->total_amount,
                'payment_method' => $paymentMethod,
                'credit_due_date' => $paymentMethod === 'loan' ? now()->addDays(CreditAccountService::CREDIT_TERM_DAYS)->toDateString() : null,
                'credit_status' => $paymentMethod === 'loan' ? 'outstanding' : 'not_applicable',
                'processed_by' => $updatedBy->id,
            ]);

            $product->decrement('stock_quantity', $order->quantity);

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

            return $transaction;
        });
    }
}
