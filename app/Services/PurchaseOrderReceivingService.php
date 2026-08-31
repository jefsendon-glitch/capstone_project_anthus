<?php

namespace App\Services;

use App\Models\GallonStock;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseOrderReceivingService
{
    /** @param array<int, array{purchase_order_item_id:int, quantity_received:float|int}> $lines */
    public function receive(PurchaseOrder $purchaseOrder, array $lines, User $receivedBy): void
    {
        DB::transaction(function () use ($purchaseOrder, $lines, $receivedBy) {
            $purchaseOrder->refresh();

            foreach ($lines as $line) {
                $item = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)
                    ->findOrFail($line['purchase_order_item_id']);
                $quantity = min((float) $line['quantity_received'], $item->remaining_quantity);

                if ($quantity <= 0 || ! $item->itemable) {
                    continue;
                }

                $itemable = $item->itemable;
                $before = (float) ($itemable->stock_quantity ?? $itemable->quantity);
                $itemable instanceof Product
                    ? $itemable->increment('stock_quantity', $quantity)
                    : $itemable->increment('quantity', $quantity);
                $after = (float) ($itemable->fresh()->stock_quantity ?? $itemable->fresh()->quantity);

                StockMovement::record($itemable, 'purchase_receive', $quantity, $before, $after, $receivedBy->id, null, (float) $item->unit_cost);

                if ($itemable instanceof Product && $itemable->category === 'container') {
                    $gallonStock = GallonStock::firstOrCreate(
                        ['product_id' => $itemable->id, 'status' => 'company_owned'],
                        ['quantity' => 0],
                    );
                    $gallonBefore = $gallonStock->quantity;
                    $gallonStock->increment('quantity', $quantity);
                    StockMovement::record($gallonStock, 'purchase_receive', $quantity, $gallonBefore, $gallonStock->fresh()->quantity, $receivedBy->id);
                }

                $item->increment('quantity_received', $quantity);
            }

            $purchaseOrder->load('items');
            $purchaseOrder->recalculateStatus();
        });
    }
}
