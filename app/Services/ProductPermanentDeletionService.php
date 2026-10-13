<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\GallonStock;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesTransaction;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProductPermanentDeletionService
{
    public function delete(Product $product, User $admin): ?string
    {
        return DB::transaction(function () use ($product, $admin) {
            $product = Product::onlyTrashed()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $sales = SalesTransaction::where('product_id', $product->id)->orderBy('id')->lockForUpdate()->get();
            foreach ($sales->whereIn('payment_method', ['loan', 'credit'])->groupBy('customer_id')->sortKeys() as $customerId => $credits) {
                if (! $customerId) continue;
                $customer = User::withTrashed()->whereKey($customerId)->lockForUpdate()->first();
                $unpaid = $credits->sum(fn ($sale) => max(0, round((float) $sale->total_amount - (float) $sale->credit_paid_amount, 2)));
                if ($customer && $unpaid > 0) {
                    $customer->forceFill(['credit_balance' => max(0, round((float) $customer->credit_balance - $unpaid, 2))])->save();
                }
            }
            DB::table('activity_log')->where('subject_type', (new SalesTransaction)->getMorphClass())->whereIn('subject_id', $sales->pluck('id'))->delete();
            SalesTransaction::where('product_id', $product->id)->delete();

            // Keep other products in mixed delivery orders; update their header totals.
            $orders = DeliveryOrder::where('product_id', $product->id)
                ->orWhereHas('items', fn ($query) => $query->where('product_id', $product->id))
                ->orderBy('id')->lockForUpdate()->get();
            foreach ($orders as $order) {
                $order->items()->where('product_id', $product->id)->delete();
                $remaining = $order->items()->orderBy('id')->get();
                if ($remaining->isEmpty()) {
                    $order->delete();
                } else {
                    $first = $remaining->first();
                    $order->update([
                        'product_id' => $first->product_id, 'product_name' => $first->product_name,
                        'unit_price' => $first->unit_price, 'quantity' => $remaining->sum('quantity'),
                        'total_amount' => $remaining->sum('total_amount'),
                    ]);
                }
            }

            $orderIds = PurchaseOrderItem::where('itemable_type', $product->getMorphClass())
                ->where('itemable_id', $product->id)->pluck('purchase_order_id');
            $purchaseOrders = PurchaseOrder::whereIn('id', $orderIds)->orderBy('id')->lockForUpdate()->get();
            PurchaseOrderItem::where('itemable_type', $product->getMorphClass())->where('itemable_id', $product->id)->delete();
            foreach ($purchaseOrders as $order) {
                $items = $order->items()->get();
                if ($items->isEmpty()) {
                    $order->delete();
                } elseif (in_array($order->status, ['ordered', 'partially_received', 'received'], true)) {
                    $allReceived = $items->every(fn ($item) => $item->remaining_quantity <= 0);
                    $order->update(['status' => $allReceived ? 'received' : ($items->sum('quantity_received') > 0 ? 'partially_received' : 'ordered')]);
                }
            }

            $gallonIds = GallonStock::where('product_id', $product->id)->pluck('id');
            StockMovement::where('movable_type', (new GallonStock)->getMorphClass())->whereIn('movable_id', $gallonIds)->delete();
            $product->stockMovements()->delete();
            DB::table('water_production_logs')->where('product_id', $product->id)->delete();
            DB::table('gallon_customer_balances')->where('product_id', $product->id)->delete();
            $product->gallonStocks()->delete();
            DB::table('notifications')->where('data->item_type', 'product')->where('data->item_id', $product->id)->delete();
            DB::table('activity_log')->where('subject_type', $product->getMorphClass())->where('subject_id', $product->id)->delete();
            $imagePath = $product->image_path;
            $product->forceDelete();
            activity('products')->causedBy($admin)->event('permanent_delete')
                ->withProperties(['product_id' => $product->id, 'product_name' => $product->name, 'sales_deleted' => $sales->count()])
                ->log('Permanently deleted archived product and related records');

            return $imagePath;
        }, 3);
    }
}
