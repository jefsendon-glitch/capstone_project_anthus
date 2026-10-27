<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Supplier::class);

        $suppliers = Supplier::withCount(['consumables', 'purchaseOrders'])->orderBy('name')->paginate(15);

        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        $this->authorize('create', Supplier::class);

        return view('admin.suppliers.create');
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['name'] = $this->fullName($data);
        unset($data['first_name'], $data['middle_name'], $data['last_name']);
        $data['is_active'] = $request->boolean('is_active', true);

        Supplier::create($data);

        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier added successfully.');
    }

    public function edit(Supplier $supplier): View
    {
        $this->authorize('update', $supplier);

        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validated();
        $data['name'] = $this->fullName($data);
        unset($data['first_name'], $data['middle_name'], $data['last_name']);
        $data['is_active'] = $request->boolean('is_active');

        $supplier->update($data);

        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorize('delete', $supplier);

        if ($supplier->consumables()->withTrashed()->exists() || $supplier->purchaseOrders()->exists()) {
            return back()->with('error', 'This supplier has linked supplies or purchase orders. Set it inactive instead.');
        }

        $supplier->delete();

        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier deleted successfully.');
    }

    private function fullName(array $data): string
    {
        return implode(' ', array_filter([
            trim($data['first_name']),
            trim($data['middle_name'] ?? ''),
            trim($data['last_name']),
        ], fn ($part) => $part !== ''));
    }
}
