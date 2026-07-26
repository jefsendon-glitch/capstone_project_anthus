<?php

namespace App\Observers;

use App\Models\GallonStock;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Notifications\DatabaseNotification;

class ProductObserver
{
    public function created(Product $product): void
    {
        if ($product->category === 'container') {
            foreach (GallonStock::STATUSES as $status) {
                GallonStock::firstOrCreate(['product_id' => $product->id, 'status' => $status], ['quantity' => 0]);
            }
        }
    }

    public function updated(Product $product): void
    {
        if (! $product->wasChanged('stock_quantity') || ! $product->is_low_stock) {
            return;
        }

        $alreadyNotified = DatabaseNotification::query()
            ->where('type', LowStockAlert::class)
            ->whereNull('read_at')
            ->where('data->item_type', 'product')
            ->where('data->item_id', $product->id)
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        $notification = LowStockAlert::forProduct($product);

        User::role(['admin', 'staff'])->get()->each->notify($notification);
    }
}
