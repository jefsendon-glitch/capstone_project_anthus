<?php

namespace App\Observers;

use App\Models\Consumable;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Notifications\DatabaseNotification;

class ConsumableObserver
{
    public function updated(Consumable $consumable): void
    {
        if (! $consumable->wasChanged('quantity') || ! $consumable->is_low_stock) {
            return;
        }

        $alreadyNotified = DatabaseNotification::query()
            ->where('type', LowStockAlert::class)
            ->whereNull('read_at')
            ->where('data->item_type', 'consumable')
            ->where('data->item_id', $consumable->id)
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        $notification = LowStockAlert::forConsumable($consumable);

        User::role(['admin', 'staff'])->get()->each->notify($notification);
    }
}
