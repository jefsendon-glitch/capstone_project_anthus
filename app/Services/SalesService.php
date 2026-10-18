<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesService
{
    public function __construct(private readonly CreditAccountService $creditAccounts, private readonly ProductOrderLines $orderLines)
    {
    }

    /**
     * @param  array{items: array<int, array{product_id:int, quantity:int}>, payment_method:string, transaction_type:string, customer_id:?int, customer_name:?string, notes:?string}  $data
     * @return Collection<int, SalesTransaction>
     */
    public function recordSale(array $data, User $processedBy): Collection
    {
        return DB::transaction(function () use ($data, $processedBy) {
            $customer = ! empty($data['customer_id']) ? User::lockForUpdate()->findOrFail($data['customer_id']) : null;

            if ($data['payment_method'] === 'loan') {
                $this->creditAccounts->ensureEligible($customer);
            }

            $lines = $this->orderLines->prepare($data['items']);
            $grandTotal = round((float) $lines->sum('line_total'), 2);
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
                    'container_type' => $line['container_type'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $product->unit_price,
                    'total_amount' => $line['line_total'],
                    'tendered_amount' => $tenderedAmount,
                    'change_amount' => $changeAmount,
                    'payment_method' => $data['payment_method'],
                    'credit_due_date' => $data['payment_method'] === 'loan' ? now()->addDays(CreditAccountService::CREDIT_TERM_DAYS)->toDateString() : null,
                    'credit_status' => $data['payment_method'] === 'loan' ? 'outstanding' : 'not_applicable',
                    'processed_by' => $processedBy->id,
                    'notes' => $data['notes'] ?? null,
                ]));

                $before = (float) $product->stock_quantity;
                $product->deductStock($line['quantity'], $line['container_type']);
                StockMovement::record($product, 'sale_deduction', -$line['quantity'], $before,
                    (float) $product->stock_quantity, $processedBy->id, ($line['container_type'] ? ucfirst($line['container_type']).' Gallon. ' : '').($data['notes'] ?? 'POS sale'), null,
                    $customer?->id, $transactions->last()->transaction_code);
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

        $codes = DB::table($table)->where($column, 'like', "{$prefix}-{$today}-%")->pluck($column);
        if ($prefix === 'TXN') {
            $codes = $codes->merge(StockMovement::where('reference_code', 'like', "{$prefix}-{$today}-%")->pluck('reference_code'));
        }
        $sequence = (int) $codes->map(fn ($code) => (int) substr($code, strrpos($code, '-') + 1))->max() + 1;

        return sprintf('%s-%s-%03d', $prefix, $today, $sequence);
    }
}
