<?php

namespace App\Services;

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
    public function receive(PurchaseOrder $purchaseOrder, array $lines, User $receivedBy, ?string $receiptToken = null): void
    {
        DB::transaction(function () use ($purchaseOrder, $lines, $receivedBy, $receiptToken) {
            $purchaseOrder = PurchaseOrder::whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();
            $reference = $receiptToken ? 'PR-'.substr(hash('sha256', $purchaseOrder->id.':'.$receiptToken), 0, 32) : null;
            if ($reference && (DB::table('stock_receipt_references')->where('reference_code', $reference)->exists()
                || StockMovement::where('reference_code', $reference)->exists())) {
                throw ValidationException::withMessages(['items' => 'This delivery was already recorded. Refresh before recording another delivery.']);
            }
            if (! in_array($purchaseOrder->status, ['ordered', 'partially_received'], true)) {
                throw ValidationException::withMessages(['items' => 'This order is no longer awaiting stock.']);
            }

            $receivedStock = false;
            foreach ($lines as $line) {
                $item = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)
                    ->lockForUpdate()->findOrFail($line['purchase_order_item_id']);
                $quantity = (float) $line['quantity_received'];
                if ($quantity > $item->remaining_quantity) {
                    throw ValidationException::withMessages(['items' => 'Delivered quantity exceeds the remaining order quantity. Refresh and try again.']);
                }

                if ($quantity <= 0 || ! $item->itemable || $item->itemable->trashed()) {
                    if ($quantity <= 0) {
                        continue;
                    }
                    throw ValidationException::withMessages(['items' => 'Restore the archived item before receiving stock.']);
                }

                $itemable = $item->itemable()->lockForUpdate()->firstOrFail();
                $before = (float) ($itemable->stock_quantity ?? $itemable->quantity);
                if (($itemable instanceof Product && ($quantity !== floor($quantity) || $before + $quantity > 2147483647))
                    || (! $itemable instanceof Product && $before + $quantity > 99999999.99)) {
                    throw ValidationException::withMessages(['items' => 'Delivered quantity exceeds the inventory capacity or uses fractional container pieces.']);
                }
                $itemable instanceof Product
                    ? $itemable->increment('stock_quantity', $quantity)
                    : $itemable->increment('quantity', $quantity);
                if (! $itemable instanceof Product) {
                    $itemable->update(['unit_cost' => $item->unit_cost]);
                }
                $after = (float) ($itemable->fresh()->stock_quantity ?? $itemable->fresh()->quantity);

                StockMovement::record($itemable, 'purchase_receive', $quantity, $before, $after, $receivedBy->id, 'Received against '.$purchaseOrder->po_number, (float) $item->unit_cost, null, $reference);

                if ($itemable instanceof Product && $itemable->category === 'container') {
                    if (mb_strlen($itemable->size) > 100) {
                        throw ValidationException::withMessages(['items' => 'Container size must be at most 100 characters to match station inventory.']);
                    }
                    $matches = DB::table('station_gallons')->where('name', $itemable->name)->where('size', $itemable->size)
                        ->lockForUpdate()->get();
                    if ($matches->count() > 1) {
                        throw ValidationException::withMessages(['items' => 'Multiple station containers match '.$itemable->name.'. Give these entries distinct names before receiving.']);
                    }
                    $gallon = $matches->first();
                    if ($gallon && $gallon->empty + $quantity > 4294967295) {
                        throw ValidationException::withMessages(['items' => 'The station container inventory has reached its capacity.']);
                    }
                    $gallonId = $gallon?->id ?? DB::table('station_gallons')->insertGetId([
                        'name' => $itemable->name, 'size' => $itemable->size, 'empty' => 0,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    DB::table('station_gallons')->where('id', $gallonId)->increment('empty', (int) $quantity, ['updated_at' => now()]);
                    DB::table('station_gallon_movements')->insert([
                        'station_gallon_id' => $gallonId, 'action' => 'add', 'to_status' => 'empty',
                        'quantity' => (int) $quantity, 'recorded_by' => $receivedBy->id,
                        'notes' => 'Received against '.$purchaseOrder->po_number,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }

                $item->increment('quantity_received', $quantity);
                $receivedStock = true;
            }

            if ($reference && $receivedStock) {
                DB::table('stock_receipt_references')->insert(['reference_code' => $reference, 'created_at' => now()]);
            }

            $purchaseOrder->load('items');
            $purchaseOrder->recalculateStatus();
        });
    }
}
