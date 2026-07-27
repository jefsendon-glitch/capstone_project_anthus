<?php

namespace App\Services;

use App\Models\CustomerPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerLedgerService
{
    public function __construct(private readonly CreditAccountService $creditAccounts)
    {
    }

    public function recordPayment(User $customer, float $amount, User $staff, ?string $notes = null): CustomerPayment
    {
        return DB::transaction(function () use ($customer, $amount, $staff, $notes) {
            $customer = User::lockForUpdate()->findOrFail($customer->id);

            if ($amount > (float) $customer->credit_balance) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment cannot be greater than the outstanding balance of ₱'.number_format((float) $customer->credit_balance, 2).'.',
                ]);
            }

            $payment = CustomerPayment::create([
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'amount' => $amount,
                'payment_date' => now()->toDateString(),
                'recorded_by' => $staff->id,
                'staff_name' => $staff->name,
                'notes' => $notes,
            ]);

            $customer->decrement('credit_balance', $amount);
            $this->creditAccounts->allocatePayment($customer, $amount);

            return $payment;
        });
    }
}
