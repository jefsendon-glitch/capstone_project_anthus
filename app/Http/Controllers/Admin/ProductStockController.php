<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddProductStockRequest;
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
