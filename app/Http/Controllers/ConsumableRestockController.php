<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsumableRestockRequest;
use App\Models\Consumable;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ConsumableRestockController extends Controller
{
    public function store(StoreConsumableRestockRequest $request, Consumable $consumable): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $consumable, $request) {
            $before = (float) $consumable->quantity;
            $consumable->increment('quantity', $validated['quantity_added']);

            StockMovement::record(
                $consumable,
                'restock',
                $validated['quantity_added'],
                $before,
                (float) $consumable->fresh()->quantity,
                $request->user()->id,
                $validated['notes'] ?? null,
                $validated['cost'] ?? null,
            );
        });

        return back()->with('success', 'Consumable restocked successfully.');
    }
}
