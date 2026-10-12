<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWaterProductionLogRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\WaterProductionLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WaterProductionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', WaterProductionLog::class);

        $filters = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'source' => ['nullable', 'in:product,gallon'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            $request->validate(['date_to' => ['after_or_equal:date_from']]);
        }
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $query = WaterProductionLog::with(['product', 'producedBy'])
            ->when($dateFrom, fn ($query) => $query->whereDate('production_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('production_date', '<=', $dateTo))
            ->when(($filters['source'] ?? '') === 'product', fn ($query) => $query->whereNull('container_name'))
            ->when(($filters['source'] ?? '') === 'gallon', fn ($query) => $query->whereNotNull('container_name'))
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = '%'.$filters['search'].'%';
                $query->where(function ($query) use ($search) {
                    $query->where('container_name', 'like', $search)->orWhere('container_size', 'like', $search)
                        ->orWhereHas('product', fn ($product) => $product->where('name', 'like', $search)->orWhere('size', 'like', $search));
                });
            });
        $summary = [
            'batches' => (clone $query)->count(),
            'productUnits' => (clone $query)->whereNull('container_name')->sum('gallons_produced'),
            'filledContainers' => (clone $query)->whereNotNull('container_name')->sum('gallons_produced'),
        ];
        $logs = $query->latest('production_date')->latest('id')->paginate(15)->withQueryString();

        return view('admin.water-production.index', compact('logs', 'dateFrom', 'dateTo', 'filters', 'summary'));
    }

    public function create(): View
    {
        $this->authorize('create', WaterProductionLog::class);

        $products = Product::where('category', '!=', 'container')->orderBy('name')->get();

        return view('admin.water-production.create', compact('products'));
    }

    public function reset(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $request->validate([
            'reset_confirmation' => ['required', 'in:RESET'],
        ]);

        DB::transaction(function () {
            WaterProductionLog::query()->delete();
        });

        return redirect()->route('admin.water-production.index')
            ->with('success', 'Water production logs reset successfully. Product stock and stock movement history preserved.');
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

        // Container fills are historical activity; reversing them needs a gallon movement.
        abort_if($water_production->container_name !== null, 422, 'Manage filled containers through Gallon Inventory.');

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
