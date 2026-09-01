<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddProductStockRequest;
use App\Http\Requests\AdjustProductStockRequest;
use App\Http\Requests\UpdateProductStockRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\WaterProductionLog;
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
        $newQuantity = (float) $request->validated('quantity');

        DB::transaction(function () use ($product, $newQuantity, $request) {
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
        $delta = (float) $request->validated('delta');
        $this->ensureWholeWaterQuantity($product, max(0, $delta));

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
        if ($product->category !== 'container') {
            WaterProductionLog::create([
                'product_id' => $product->id,
                'gallons_produced' => (int) $quantity,
                'produced_by' => $userId,
                'production_date' => today(),
                'notes' => $notes ?? 'Automatically recorded from a water inventory increase.',
            ]);
        }

        StockMovement::record($product, $product->category === 'container' ? 'restock' : 'production', $quantity, $before, $after, $userId, $notes);
    }

    private function ensureWholeWaterQuantity(Product $product, float $quantity): void
    {
        if ($product->category !== 'container' && $quantity > 0 && floor($quantity) !== $quantity) {
            throw ValidationException::withMessages(['quantity' => 'Water production must be recorded in whole gallons.']);
        }
    }
}
