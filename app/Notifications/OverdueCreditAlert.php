<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OverdueCreditAlert extends Notification
{
    use Queueable;

    public function __construct(private readonly User $customer, private readonly bool $forStaff = false)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $balance = '₱'.number_format((float) $this->customer->credit_balance, 2);

        return [
            'type' => 'overdue_credit',
            'message' => $this->forStaff
                ? "{$this->customer->name}'s credit account was suspended after an overdue balance of {$balance}."
                : "Your credit balance of {$balance} is overdue. Your credit account is suspended; please settle your balance and contact the station for review.",
            'url' => $this->forStaff ? route('payments.index') : route('customer.dashboard'),
        ];
    }
}
