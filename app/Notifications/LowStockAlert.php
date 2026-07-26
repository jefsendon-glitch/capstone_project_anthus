<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification
{
    public function __construct(
        private readonly string $itemType,
        private readonly int $itemId,
        private readonly string $name,
        private readonly string $remaining,
        private readonly string $url,
    ) {
    }

    public static function forProduct(\App\Models\Product $product): self
    {
        return new self(
            'product',
            $product->id,
            "{$product->name} ({$product->size})",
            "{$product->stock_quantity} {$product->stock_unit_label}",
            route('admin.products.edit', $product),
        );
    }

    public static function forConsumable(\App\Models\Consumable $consumable): self
    {
        return new self(
            'consumable',
            $consumable->id,
            $consumable->name,
            rtrim(rtrim((string) $consumable->quantity, '0'), '.').' '.$consumable->unit,
            route('admin.consumables.edit', $consumable),
        );
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Low stock alert',
            'message' => "{$this->name} is running low — only {$this->remaining} left.",
            'url' => $this->url,
            'item_type' => $this->itemType,
            'item_id' => $this->itemId,
        ];
    }
}
