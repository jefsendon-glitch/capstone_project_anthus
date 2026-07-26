<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesService
{
    /**
     * @param  array{items: array<int, array{product_id:int, quantity:int}>, payment_method:string, transaction_type:string, customer_id:?int, customer_name:?string, notes:?string}  $data
     * @return Collection<int, SalesTransaction>
     */
    public function recordSale(array $data, User $processedBy): Collection
    {
        return DB::transaction(function () use ($data, $processedBy) {
            $customer = ! empty($data['customer_id']) ? User::findOrFail($data['customer_id']) : null;

            $lines = collect();
            $grandTotal = 0;

            // Merge duplicate product_id lines first so the stock check below sees the
            // true combined quantity being requested, not each line checked in isolation.
            $quantitiesByProduct = collect($data['items'])
                ->groupBy('product_id')
                ->map(fn (Collection $items) => $items->sum('quantity'));

            foreach ($quantitiesByProduct as $productId => $quantity) {
                $product = Product::lockForUpdate()->findOrFail($productId);

                if ($quantity > $product->stock_quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Not enough stock for {$product->name} ({$product->size}). Only {$product->stock_quantity} {$product->stock_unit_label} left.",
                    ]);
                }

                $lineTotal = $product->unit_price * $quantity;
                $grandTotal += $lineTotal;

                $lines->push([
                    'product' => $product,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ]);
            }

            $tenderedAmount = null;
            $changeAmount = null;

            if ($data['payment_method'] === 'cash') {
                $tenderedAmount = round((float) $data['tendered_amount'], 2);

                if ($tenderedAmount < $grandTotal) {
                    throw ValidationException::withMessages([
                        'tendered_amount' => 'The amount received must cover the sale total of ₱'.number_format($grandTotal, 2).'.',
                    ]);
                }

                $changeAmount = round($tenderedAmount - $grandTotal, 2);
            }

            $transactions = collect();

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $line['product'];

                $transactions->push(SalesTransaction::create([
                    'transaction_code' => $this->generateCode('TXN'),
                    'transaction_type' => $data['transaction_type'],
                    'customer_id' => $customer?->id,
                    'customer_name' => $customer?->name ?? $data['customer_name'] ?? null,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $line['quantity'],
                    'unit_price' => $product->unit_price,
                    'total_amount' => $line['line_total'],
                    'tendered_amount' => $tenderedAmount,
                    'change_amount' => $changeAmount,
                    'payment_method' => $data['payment_method'],
                    'processed_by' => $processedBy->id,
                    'notes' => $data['notes'] ?? null,
                ]));

                $product->decrement('stock_quantity', $line['quantity']);
            }

            if ($data['payment_method'] === 'loan' && $customer) {
                $customer->increment('credit_balance', $grandTotal);
            }

            return $transactions;
        });
    }

    public function generateCode(string $prefix): string
    {
        $table = $prefix === 'TXN' ? 'sales_transactions' : 'delivery_orders';
        $column = $prefix === 'TXN' ? 'transaction_code' : 'order_code';
        $today = now()->format('Ymd');

        $sequence = DB::table($table)->where($column, 'like', "{$prefix}-{$today}-%")->count() + 1;

        return sprintf('%s-%s-%03d', $prefix, $today, $sequence);
    }
}
