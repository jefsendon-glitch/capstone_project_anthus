<?php

namespace App\Notifications;

use App\Models\DeliveryOrder;
use Illuminate\Notifications\Notification;

class NewDeliveryOrderPlaced extends Notification
{
    public function __construct(private readonly DeliveryOrder $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New delivery order',
            'message' => "{$this->order->customer_name} placed order {$this->order->order_code}.",
            'url' => route('deliveries.show', $this->order),
        ];
    }
}
