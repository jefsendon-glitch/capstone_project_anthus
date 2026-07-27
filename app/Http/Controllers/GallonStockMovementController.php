<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddGallonStockRequest;
use App\Http\Requests\TransferGallonStockRequest;
use App\Models\GallonStock;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GallonStockMovementController extends Controller
{
    public function addStock(AddGallonStockRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $product, $request) {
            $row = GallonStock::firstOrCreate(
                ['product_id' => $product->id, 'status' => $validated['status']],
                ['quantity' => 0],
            );

            $before = $row->quantity;
            $row->increment('quantity', $validated['quantity']);

            StockMovement::record(
                $row,
                $this->movementTypeFor($validated['status']),
                $validated['quantity'],
                $before,
                $row->fresh()->quantity,
                $request->user()->id,
                $validated['notes'] ?? null,
            );
        });

        return back()->with('success', 'Gallon stock added successfully.');
    }

    public function transfer(TransferGallonStockRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $product, $request) {
            $fromRow = GallonStock::firstOrCreate(
                ['product_id' => $product->id, 'status' => $validated['from_status']],
                ['quantity' => 0],
            );
            $fromRow = GallonStock::lockForUpdate()->find($fromRow->id);

            if ($fromRow->quantity < $validated['quantity']) {
                throw ValidationException::withMessages(['quantity' => "Not enough {$fromRow->status_label} gallons to transfer."]);
            }

            $toRow = GallonStock::firstOrCreate(
                ['product_id' => $product->id, 'status' => $validated['to_status']],
                ['quantity' => 0],
            );
            $toRow = GallonStock::lockForUpdate()->find($toRow->id);

            $fromBefore = $fromRow->quantity;
            $fromRow->decrement('quantity', $validated['quantity']);
            StockMovement::record(
                $fromRow,
                'adjustment_decrease',
                -$validated['quantity'],
                $fromBefore,
                $fromRow->fresh()->quantity,
                $request->user()->id,
                $validated['notes'] ?? null,
            );

            $toBefore = $toRow->quantity;
            $toRow->increment('quantity', $validated['quantity']);
            StockMovement::record(
                $toRow,
                $this->movementTypeFor($validated['to_status'], forTransfer: true),
                $validated['quantity'],
                $toBefore,
                $toRow->fresh()->quantity,
                $request->user()->id,
                $validated['notes'] ?? null,
            );
        });

        return back()->with('success', 'Gallons transferred successfully.');
    }

    private function movementTypeFor(string $status, bool $forTransfer = false): string
    {
        return match ($status) {
            'damaged' => 'damage',
            'missing' => 'missing',
            'returned' => 'returned',
            default => $forTransfer ? 'adjustment_increase' : 'restock',
        };
    }
}
