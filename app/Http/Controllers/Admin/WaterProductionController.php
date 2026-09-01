<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWaterProductionLogRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\WaterProductionLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WaterProductionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', WaterProductionLog::class);

        $dateFrom = request('date_from');
        $dateTo = request('date_to');
        $logs = WaterProductionLog::with(['product', 'producedBy'])
            ->when($dateFrom, fn ($query) => $query->whereDate('production_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('production_date', '<=', $dateTo))
            ->latest('production_date')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.water-production.index', compact('logs', 'dateFrom', 'dateTo'));
    }

    public function create(): View
    {
        $this->authorize('create', WaterProductionLog::class);

        $products = Product::where('category', '!=', 'container')->orderBy('name')->get();

        return view('admin.water-production.create', compact('products'));
    }

    public function store(StoreWaterProductionLogRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request) {
            $product = Product::findOrFail($validated['product_id']);
            $before = $product->stock_quantity;
            $product->increment('stock_quantity', $validated['gallons_produced']);

            $log = WaterProductionLog::create([
                ...$validated,
                'produced_by' => $request->user()->id,
            ]);

            StockMovement::record(
                $product,
                'production',
                $validated['gallons_produced'],
                $before,
                $product->fresh()->stock_quantity,
                $request->user()->id,
                $validated['notes'] ?? null,
            );

            return $log;
        });

        return redirect()->route('admin.water-production.index')->with('success', 'Production batch logged successfully.');
    }

    public function destroy(WaterProductionLog $water_production): RedirectResponse
    {
        $this->authorize('delete', $water_production);

        DB::transaction(function () use ($water_production) {
            $product = $water_production->product;
            $before = $product->stock_quantity;
            $product->decrement('stock_quantity', min($water_production->gallons_produced, $product->stock_quantity));

            StockMovement::record(
                $product,
                'adjustment_decrease',
                $product->fresh()->stock_quantity - $before,
                $before,
                $product->fresh()->stock_quantity,
                request()->user()->id,
                'Reversal of production log #'.$water_production->id,
            );

            $water_production->delete();
        });

        return redirect()->route('admin.water-production.index')->with('success', 'Production log removed and stock reversed.');
    }
}
