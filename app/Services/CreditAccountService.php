<?php

namespace App\Services;

use App\Models\SalesTransaction;
use App\Models\User;
use App\Notifications\OverdueCreditAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreditAccountService
{
    public const CREDIT_TERM_DAYS = 14;

    public function ensureEligible(User $customer): void
    {
        if (! $customer->isCustomer()) {
            throw ValidationException::withMessages(['customer_id' => 'Credit can only be assigned to a registered customer.']);
        }

        if ($customer->credit_status !== 'eligible') {
            throw ValidationException::withMessages([
                'payment_method' => 'This customer\'s credit account is suspended because of an overdue payment. Cash payment is required. Contact management after the balance is settled for a credit review.',
            ]);
        }
    }

    public function suspendOverdueAccounts(): int
    {
        $overdueCustomers = User::role('customer')
            ->where('credit_status', 'eligible')
            ->whereHas('salesTransactions', fn ($query) => $query
                ->where('payment_method', 'loan')
                ->where('credit_status', 'outstanding')
                ->whereNotNull('credit_due_date')
                ->where('created_at', '<=', now()->subDays(self::CREDIT_TERM_DAYS)))
            ->get();

        foreach ($overdueCustomers as $customer) {
            DB::transaction(function () use ($customer): void {
                $customer = User::lockForUpdate()->findOrFail($customer->id);

                $hasOverdueCredit = $customer->salesTransactions()
                    ->where('payment_method', 'loan')
                    ->where('credit_status', 'outstanding')
                    ->whereNotNull('credit_due_date')
                    ->where('created_at', '<=', now()->subDays(self::CREDIT_TERM_DAYS))
                    ->exists();

                if (! $hasOverdueCredit || $customer->credit_status !== 'eligible') {
                    return;
                }

                $customer->update([
                    'credit_status' => 'suspended',
                    'credit_suspended_at' => now(),
                    'credit_suspension_reason' => 'Outstanding credit was not paid within the 14-day payment term.',
                ]);

                $customer->notify(new OverdueCreditAlert($customer));
                User::role(['admin', 'staff'])->get()->each->notify(new OverdueCreditAlert($customer, true));
            });
        }

        return $overdueCustomers->count();
    }

    public function allocatePayment(User $customer, float $amount): void
    {
        $remaining = $amount;

        $credits = SalesTransaction::query()
            ->where('customer_id', $customer->id)
            ->where('payment_method', 'loan')
            ->where('credit_status', 'outstanding')
            ->orderBy('credit_due_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($credits as $credit) {
            if ($remaining <= 0) {
                break;
            }

            $unpaid = round((float) $credit->total_amount - (float) $credit->credit_paid_amount, 2);
            $applied = min($remaining, $unpaid);
            $paid = round((float) $credit->credit_paid_amount + $applied, 2);

            $credit->update([
                'credit_paid_amount' => $paid,
                'credit_status' => $paid >= (float) $credit->total_amount ? 'paid' : 'outstanding',
            ]);

            $remaining = round($remaining - $applied, 2);
        }
    }
}
