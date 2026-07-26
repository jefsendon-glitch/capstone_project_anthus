<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReceivePurchaseOrderRequest;
use App\Models\GallonStock;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PurchaseOrderReceiptController extends Controller
{
    public function store(ReceivePurchaseOrderRequest $request, PurchaseOrder $purchase_order): RedirectResponse
    {
        if (! in_array($purchase_order->status, ['ordered', 'partially_received'])) {
            return back()->with('error', 'This purchase order cannot receive stock in its current status.');
        }

        DB::transaction(function () use ($request, $purchase_order) {
            foreach ($request->validated('items') as $line) {
                $item = PurchaseOrderItem::where('purchase_order_id', $purchase_order->id)->findOrFail($line['purchase_order_item_id']);

                $quantity = min((float) $line['quantity_received'], $item->remaining_quantity);

                if ($quantity <= 0) {
                    continue;
                }

                $itemable = $item->itemable;
                $before = (float) ($itemable->stock_quantity ?? $itemable->quantity);

                if ($itemable instanceof Product) {
                    $itemable->increment('stock_quantity', $quantity);
                } else {
                    $itemable->increment('quantity', $quantity);
                }

                $after = (float) ($itemable->fresh()->stock_quantity ?? $itemable->fresh()->quantity);

                StockMovement::record($itemable, 'purchase_receive', $quantity, $before, $after, $request->user()->id, null, (float) $item->unit_cost);

                if ($itemable instanceof Product && $itemable->category === 'container') {
                    $gallonStock = GallonStock::firstOrCreate(
                        ['product_id' => $itemable->id, 'status' => 'company_owned'],
                        ['quantity' => 0],
                    );
                    $gallonBefore = $gallonStock->quantity;
                    $gallonStock->increment('quantity', $quantity);

                    StockMovement::record($gallonStock, 'purchase_receive', $quantity, $gallonBefore, $gallonStock->fresh()->quantity, $request->user()->id);
                }

                $item->increment('quantity_received', $quantity);
            }

            $purchase_order->recalculateStatus();
        });

        return redirect()->route('admin.purchase-orders.show', $purchase_order)->with('success', 'Stock received successfully.');
    }
}
