<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SystemTransactionResetService
{
    // Children precede their parents. Keep foreign-key checks enabled throughout.
    public const TRANSACTION_TABLES = [
        'purchase_order_emails', 'purchase_order_items', 'expenses', 'purchase_orders',
        'sales_transactions', 'delivery_order_items', 'delivery_orders', 'customer_payments',
        'water_production_logs', 'stock_movements', 'stock_receipt_references',
        'station_gallon_movements', 'station_gallon_loans', 'gallon_customer_balances',
        'supply_usages', 'financial_reports', 'maintenance_logs', 'notifications', 'activity_log',
    ];

    public function reset(User $admin): string
    {
        abort_unless($admin->isAdmin(), 403);

        return DB::transaction(function () use ($admin) {
            $snapshot = ['created_at' => now()->toIso8601String(), 'reset_by' => $admin->id, 'tables' => []];
            // These locks also serialize resets with stock fills, sales and account updates.
            $balances = [
                'users' => ['id', 'credit_balance', 'credit_status', 'credit_suspended_at', 'credit_suspension_reason'],
                'products' => ['id', 'stock_quantity', 'round_stock', 'slim_stock'],
                'consumables' => ['id', 'quantity'],
                'station_gallons' => ['*'],
                'gallon_stocks' => ['*'],
            ];
            foreach ($balances as $table => $columns) {
                $snapshot['balances'][$table] = DB::table($table)->orderBy('id')->lockForUpdate()->get($columns);
            }
            foreach (self::TRANSACTION_TABLES as $table) {
                $snapshot['tables'][$table] = DB::table($table)->orderBy($table === 'stock_receipt_references' ? 'reference_code' : 'id')->lockForUpdate()->get();
            }

            $backup = 'system-reset-backups/reset-'.now()->format('Ymd-His').'-'.Str::uuid().'.json';
            try {
                $saved = Storage::disk('local')->put($backup, json_encode($snapshot, JSON_THROW_ON_ERROR));
            } catch (\Throwable $exception) {
                report($exception);
                $saved = false;
            }
            if (! $saved) {
                throw ValidationException::withMessages(['reset_confirmation' => 'The backup could not be saved. No records were reset.']);
            }

            $counts = [];
            foreach (self::TRANSACTION_TABLES as $table) {
                $counts[$table] = DB::table($table)->delete();
            }
            DB::table('products')->update(['stock_quantity' => 0, 'round_stock' => 0, 'slim_stock' => 0]);
            DB::table('consumables')->update(['quantity' => 0]);
            DB::table('gallon_stocks')->update(['quantity' => 0]);
            DB::table('station_gallons')->update(array_fill_keys(['empty', 'filled', 'borrowed', 'returned', 'damaged', 'missing'], 0));
            DB::table('users')->update(['credit_balance' => 0, 'credit_status' => 'eligible',
                'credit_suspended_at' => null, 'credit_suspension_reason' => null]);

            activity('system_reset')->causedBy($admin)->event('reset')
                ->withProperties(['backup' => $backup, 'deleted_records' => $counts])
                ->log('Reset all system transactions, inventory balances, and customer credit balances. Accounts and catalog retained.');

            return $backup;
        }, 3);
    }
}
