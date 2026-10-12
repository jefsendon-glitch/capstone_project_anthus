<?php

namespace App\Services;

use App\Models\GallonStock;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderReceivingService
{
    /** @param array<int, array{purchase_order_item_id:int, quantity_received:float|int}> $lines */
    public function receive(PurchaseOrder $purchaseOrder, array $lines, User $receivedBy): void
    {
        DB::transaction(function () use ($purchaseOrder, $lines, $receivedBy) {
            $purchaseOrder = PurchaseOrder::whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();
            if (! in_array($purchaseOrder->status, ['ordered', 'partially_received'], true)) {
                throw ValidationException::withMessages(['items' => 'This order is no longer awaiting stock.']);
            }

            foreach ($lines as $line) {
                $item = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)
                    ->lockForUpdate()->findOrFail($line['purchase_order_item_id']);
                $quantity = (float) $line['quantity_received'];
                if ($quantity > $item->remaining_quantity) {
                    throw ValidationException::withMessages(['items' => 'Delivered quantity exceeds the remaining order quantity. Refresh and try again.']);
                }

                if ($quantity <= 0 || ! $item->itemable) {
                    continue;
                }

                $itemable = $item->itemable()->lockForUpdate()->firstOrFail();
                $before = (float) ($itemable->stock_quantity ?? $itemable->quantity);
                $itemable instanceof Product
                    ? $itemable->increment('stock_quantity', $quantity)
                    : $itemable->increment('quantity', $quantity);
                $after = (float) ($itemable->fresh()->stock_quantity ?? $itemable->fresh()->quantity);

                StockMovement::record($itemable, 'purchase_receive', $quantity, $before, $after, $receivedBy->id, 'Received against '.$purchaseOrder->po_number, (float) $item->unit_cost);

                if ($itemable instanceof Product && $itemable->category === 'container') {
                    $gallonStock = GallonStock::firstOrCreate(
                        ['product_id' => $itemable->id, 'status' => 'empty'],
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
