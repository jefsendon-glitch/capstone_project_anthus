<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuickUpdateConsumableRequest;
use App\Http\Requests\StoreConsumableRequest;
use App\Http\Requests\UpdateConsumableRequest;
use App\Models\Consumable;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ConsumableController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Consumable::class);

        $archived = request()->boolean('archived');
        $query = $archived ? Consumable::onlyTrashed() : Consumable::query();
        $consumables = $query->orderBy('category')->orderBy('name')->paginate(15)->withQueryString();

        $activeConsumables = Consumable::query();
        $summary = [
            'totalSupplies' => (clone $activeConsumables)->count(),
            'totalStock' => (clone $activeConsumables)->sum('quantity'),
            'lowStock' => (clone $activeConsumables)->where('quantity', '>', 0)->whereColumn('quantity', '<=', 'low_stock_threshold')->count(),
            'outOfStock' => (clone $activeConsumables)->where('quantity', '<=', 0)->count(),
        ];

        return view('admin.consumables.index', compact('consumables', 'archived', 'summary'));
    }

    public function create(): View
    {
        $this->authorize('create', Consumable::class);

        return view('admin.consumables.create');
    }

    public function report(): View
    {
        $this->authorize('viewAny', Consumable::class);

        $categories = array_values(array_filter((array) request('categories', []), 'is_string'));
        $status = request('status', 'all');
        $status = in_array($status, ['all', 'in_stock', 'low_stock', 'out_of_stock'], true) ? $status : 'all';
        $query = Consumable::query()
            ->when($categories, fn ($query) => $query->whereIn('category', $categories))
            ->withStockStatus($status);

        $consumables = $query->orderBy('category')->orderBy('name')->get();
        $availableCategories = Consumable::query()->select('category')->distinct()->orderBy('category')->pluck('category');

        return view('admin.consumables.report', compact('consumables', 'availableCategories', 'categories', 'status'));
    }

    public function store(StoreConsumableRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $consumable = Consumable::create($request->validated());
            $quantity = (float) $consumable->quantity;

            if ($quantity > 0) {
                StockMovement::record($consumable, 'initial', $quantity, 0, $quantity, $request->user()->id, 'Initial supply stock', (float) $consumable->unit_cost);
            }
        });

        return redirect()->route('admin.consumables.index')->with('success', 'Consumable added successfully.');
    }

    public function edit(Consumable $consumable): View
    {
        $this->authorize('update', $consumable);

        return view('admin.consumables.edit', compact('consumable'));
    }

    public function update(UpdateConsumableRequest $request, Consumable $consumable): RedirectResponse
    {
        DB::transaction(function () use ($request, $consumable) {
            $data = $request->validated();
            $before = (float) $consumable->quantity;
            $after = (float) $data['quantity'];
            $consumable->update($data);

            if ($before !== $after) {
                StockMovement::record($consumable, $after > $before ? 'adjustment_increase' : 'adjustment_decrease', $after - $before, $before, $after, $request->user()->id, 'Supply quantity updated', (float) $consumable->unit_cost);
            }
        });

        return redirect()->route('admin.consumables.index')->with('success', 'Consumable updated successfully.');
    }

    public function quickUpdate(QuickUpdateConsumableRequest $request, Consumable $consumable): RedirectResponse
    {
        $field = $request->validated('field');
        $value = (float) $request->validated('value');
        $adjustmentType = $request->validated('adjustment_type');
        $labels = [
            'quantity' => 'Stock quantity',
            'low_stock_threshold' => 'Reorder level',
            'unit_cost' => 'Unit cost',
        ];

        DB::transaction(function () use ($request, $consumable, $field, $value, $adjustmentType) {
            $before = (float) $consumable->{$field};
            $after = $field === 'quantity'
                ? ($adjustmentType === 'decrease' ? max(0, $before - $value) : $before + $value)
                : $value;
            $consumable->update([$field => $after]);

            if ($field === 'quantity' && $before !== $after) {
                StockMovement::record(
                    $consumable,
                    $after > $before ? 'adjustment_increase' : 'adjustment_decrease',
                    $after - $before,
                    $before,
                    $after,
                    $request->user()->id,
                    $request->validated('notes'),
                );
            }
        });

        return back()->with('success', $labels[$field].' updated successfully.');
    }

    public function destroy(Consumable $consumable): RedirectResponse
    {
        $this->authorize('delete', $consumable);

        $consumable->delete();

        return redirect()->route('admin.consumables.index')->with('success', 'Consumable archived successfully.');
    }

    public function restore(int $consumable): RedirectResponse
    {
        $consumable = Consumable::onlyTrashed()->findOrFail($consumable);
        $this->authorize('restore', $consumable);
        $consumable->restore();

        return redirect()->route('admin.consumables.index')->with('success', 'Consumable restored successfully.');
    }
}
