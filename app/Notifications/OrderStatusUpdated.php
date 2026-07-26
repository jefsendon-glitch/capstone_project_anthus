<?php

namespace App\Notifications;

use App\Models\DeliveryOrder;
use Illuminate\Notifications\Notification;

class OrderStatusUpdated extends Notification
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
        $label = ucfirst(str_replace('_', ' ', $this->order->status));

        return [
            'title' => 'Order update',
            'message' => "Your order {$this->order->order_code} is now: {$label}.",
            'url' => route('customer.orders.show', $this->order),
        ];
    }
}
