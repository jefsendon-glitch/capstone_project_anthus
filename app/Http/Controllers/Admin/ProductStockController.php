<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddProductStockRequest;
use App\Http\Requests\AdjustProductStockRequest;
use App\Http\Requests\UpdateProductStockRequest;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductStockController extends Controller
{
    public function add(AddProductStockRequest $request, Product $product): RedirectResponse
    {
        $quantity = (float) $request->validated('quantity');
        $this->ensureWholeWaterQuantity($product, $quantity);

        DB::transaction(function () use ($product, $quantity, $request) {
            $before = (float) $product->stock_quantity;
            $product->increment('stock_quantity', $quantity);

            $this->recordIncrease($product, $quantity, $before, (float) $product->fresh()->stock_quantity, $request->user()->id);
        });

        return back()->with('success', 'Stock added successfully.');
    }

    public function update(UpdateProductStockRequest $request, Product $product): RedirectResponse
    {
        if ($product->requires_container_choice) {
            DB::transaction(function () use ($request, $product) {
                $product = Product::lockForUpdate()->findOrFail($product->id);
                $before = (int) $product->stock_quantity;
                $roundBefore = (int) $product->round_stock;
                $slimBefore = (int) $product->slim_stock;
                $round = (int) $request->validated('round_stock');
                $slim = (int) $request->validated('slim_stock');
                $after = $round + $slim;
                $product->update(['round_stock' => $round, 'slim_stock' => $slim, 'stock_quantity' => $after]);
                if ($before !== $after || $roundBefore !== $round || $slimBefore !== $slim) {
                    StockMovement::record($product, $after < $before ? 'adjustment_decrease' : 'adjustment_increase',
                        $after - $before, $before, $after, $request->user()->id,
                        'Stock quantity edited. Round: '.$roundBefore.' → '.$round.'; Slim: '.$slimBefore.' → '.$slim.'.');
                }
            });

            return back()->with('success', 'Round and Slim stock quantities updated. Total stock recalculated.');
        }
        $newQuantity = (float) $request->validated('quantity');

        DB::transaction(function () use ($product, $newQuantity, $request) {
            $product = Product::lockForUpdate()->findOrFail($product->id);
            $before = (float) $product->stock_quantity;
            $delta = $newQuantity - $before;
            $this->ensureWholeWaterQuantity($product, max(0, $delta));
            $product->update(['stock_quantity' => $newQuantity]);

            if ($delta > 0) {
                $this->recordIncrease($product, $delta, $before, $newQuantity, $request->user()->id);
            } elseif ($delta < 0) {
                StockMovement::record($product, 'adjustment_decrease', $delta, $before, $newQuantity, $request->user()->id);
            }
        });

        return back()->with('success', 'Stock updated successfully.');
    }

    public function adjust(AdjustProductStockRequest $request, Product $product): RedirectResponse
    {
        $quantity = (float) $request->validated('quantity');
        $delta = $request->validated('adjustment_type') === 'decrease' ? -$quantity : $quantity;
        $this->ensureWholeWaterQuantity($product, $quantity);

        DB::transaction(function () use ($product, $delta, $request) {
            $before = (float) $product->stock_quantity;
            $after = max(0, $before + $delta);
            $product->update(['stock_quantity' => $after]);

            $actualDelta = $after - $before;

            if ($actualDelta > 0) {
                $this->recordIncrease($product, $actualDelta, $before, $after, $request->user()->id, $request->validated('notes'));
            } elseif ($actualDelta < 0) {
                StockMovement::record($product, 'adjustment_decrease', $actualDelta, $before, $after, $request->user()->id, $request->validated('notes'));
            }
        });

        return back()->with('success', 'Stock adjusted successfully.');
    }

    private function recordIncrease(Product $product, float $quantity, float $before, float $after, int $userId, ?string $notes = null): void
    {
        StockMovement::record($product, 'restock', $quantity, $before, $after, $userId, $notes);
    }

    private function ensureWholeWaterQuantity(Product $product, float $quantity): void
    {
        if ($product->requires_container_choice) {
            throw ValidationException::withMessages(['quantity' => 'Use Fill Gallons to add water stock, or Edit Product to correct Round and Slim quantities.']);
        }
        if ($product->category !== 'container' && $quantity > 0 && floor($quantity) !== $quantity) {
            throw ValidationException::withMessages(['quantity' => 'Water production must be recorded in whole gallons.']);
        }
    }
}
