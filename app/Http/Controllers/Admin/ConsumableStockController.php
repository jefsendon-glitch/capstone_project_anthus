<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustConsumableStockRequest;
use App\Http\Requests\UpdateConsumableStockRequest;
use App\Models\Consumable;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ConsumableStockController extends Controller
{
    public function update(UpdateConsumableStockRequest $request, Consumable $consumable): RedirectResponse
    {
        $newQuantity = (float) $request->validated('quantity');

        DB::transaction(function () use ($consumable, $newQuantity, $request) {
            $before = (float) $consumable->quantity;
            $delta = $newQuantity - $before;
            $consumable->update(['quantity' => $newQuantity]);

            StockMovement::record(
                $consumable,
                $delta >= 0 ? 'adjustment_increase' : 'adjustment_decrease',
                $delta,
                $before,
                $newQuantity,
                $request->user()->id,
            );
        });

        return back()->with('success', 'Stock updated successfully.');
    }

    public function adjust(AdjustConsumableStockRequest $request, Consumable $consumable): RedirectResponse
    {
        $delta = (float) $request->validated('delta');

        DB::transaction(function () use ($consumable, $delta, $request) {
            $before = (float) $consumable->quantity;
            $after = max(0, $before + $delta);
            $consumable->update(['quantity' => $after]);

            StockMovement::record(
                $consumable,
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
