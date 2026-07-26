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

class ProductStockController extends Controller
{
    public function add(AddProductStockRequest $request, Product $product): RedirectResponse
    {
        $quantity = (float) $request->validated('quantity');

        DB::transaction(function () use ($product, $quantity, $request) {
            $before = (float) $product->stock_quantity;
            $product->increment('stock_quantity', $quantity);

            StockMovement::record($product, 'restock', $quantity, $before, (float) $product->fresh()->stock_quantity, $request->user()->id);
        });

        return back()->with('success', 'Stock added successfully.');
    }

    public function update(UpdateProductStockRequest $request, Product $product): RedirectResponse
    {
        $newQuantity = (float) $request->validated('quantity');

        DB::transaction(function () use ($product, $newQuantity, $request) {
            $before = (float) $product->stock_quantity;
            $delta = $newQuantity - $before;
            $product->update(['stock_quantity' => $newQuantity]);

            StockMovement::record(
                $product,
                $delta >= 0 ? 'adjustment_increase' : 'adjustment_decrease',
                $delta,
                $before,
                $newQuantity,
                $request->user()->id,
            );
        });

        return back()->with('success', 'Stock updated successfully.');
    }

    public function adjust(AdjustProductStockRequest $request, Product $product): RedirectResponse
    {
        $delta = (float) $request->validated('delta');

        DB::transaction(function () use ($product, $delta, $request) {
            $before = (float) $product->stock_quantity;
            $after = max(0, $before + $delta);
            $product->update(['stock_quantity' => $after]);

            StockMovement::record(
                $product,
                $delta >= 0 ? 'adjustment_increase' : 'adjustment_decrease',
                $after - $before,
                $before,
                $after,
                $request->user()->id,
                $request->validated('notes'),
            );
        });

        return back()->with('success', 'Stock adjusted successfully.');
    }
}
