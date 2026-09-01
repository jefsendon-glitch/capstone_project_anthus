<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        $consumables = Consumable::orderBy('category')->orderBy('name')->paginate(15);

        return view('admin.consumables.index', compact('consumables'));
    }

    public function create(): View
    {
        $this->authorize('create', Consumable::class);

        return view('admin.consumables.create');
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

    public function destroy(Consumable $consumable): RedirectResponse
    {
        $this->authorize('delete', $consumable);

        $consumable->delete();

        return redirect()->route('admin.consumables.index')->with('success', 'Consumable deleted successfully.');
    }
}
