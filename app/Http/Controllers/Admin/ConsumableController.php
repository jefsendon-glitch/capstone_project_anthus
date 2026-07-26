<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConsumableRequest;
use App\Http\Requests\UpdateConsumableRequest;
use App\Models\Consumable;
use Illuminate\Http\RedirectResponse;
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
        Consumable::create($request->validated());

        return redirect()->route('admin.consumables.index')->with('success', 'Consumable added successfully.');
    }

    public function edit(Consumable $consumable): View
    {
        $this->authorize('update', $consumable);

        return view('admin.consumables.edit', compact('consumable'));
    }

    public function update(UpdateConsumableRequest $request, Consumable $consumable): RedirectResponse
    {
        $consumable->update($request->validated());

        return redirect()->route('admin.consumables.index')->with('success', 'Consumable updated successfully.');
    }

    public function destroy(Consumable $consumable): RedirectResponse
    {
        $this->authorize('delete', $consumable);

        $consumable->delete();

        return redirect()->route('admin.consumables.index')->with('success', 'Consumable deleted successfully.');
    }
}
