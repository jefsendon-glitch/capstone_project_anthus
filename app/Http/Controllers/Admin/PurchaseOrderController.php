<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Models\Consumable;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    private const ITEMABLE_TYPES = [
        'product' => Product::class,
        'consumable' => Consumable::class,
    ];

    public function index(): View
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        request()->validate([
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'status' => ['nullable', 'in:draft,ordered,partially_received,received,cancelled'],
            'date_from' => ['nullable', 'date'],
            'date_to' => array_filter(['nullable', 'date', request()->filled('date_from') ? 'after_or_equal:date_from' : null]),
        ]);

        $dateFrom = request('date_from');
        $dateTo = request('date_to');
        $purchaseOrders = PurchaseOrder::with('supplier')
            ->withCount('items')
            ->when(request('status'), fn ($query) => $query->where('status', request('status')))
            ->when(request('supplier_id'), fn ($query) => $query->where('supplier_id', request('supplier_id')))
            ->when($dateFrom, fn ($query) => $query->whereDate('ordered_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('ordered_at', '<=', $dateTo))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.purchase-orders.index', compact('purchaseOrders', 'dateFrom', 'dateTo') + [
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PurchaseOrder::class);

        request()->validate([
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'consumable_id' => ['nullable', 'integer', 'exists:consumables,id'],
        ]);

        $selectedSupply = Consumable::with('supplier')->find(request('consumable_id'));

        return view('admin.purchase-orders.create', [
            'selectedSupply' => $selectedSupply,
            'selectedSupplierId' => $selectedSupply?->supplier_id ?? request('supplier_id'),
            'suppliers' => Supplier::active()->orderBy('name')->get(),
            'products' => Product::where('category', 'container')->orderBy('name')->get(),
            'consumables' => Consumable::orderBy('name')->get(),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $purchaseOrder = DB::transaction(function () use ($validated, $request) {
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $this->generatePoNumber(),
                'supplier_id' => $validated['supplier_id'],
                'status' => $validated['status'] ?? 'ordered',
                'ordered_at' => $validated['ordered_at'] ?? now()->toDateString(),
                'expected_date' => $validated['expected_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $this->syncItems($purchaseOrder, $validated['items']);
            app(\App\Services\PurchaseOrderExpenseService::class)->sync($purchaseOrder);

            return $purchaseOrder;
        });

        return redirect()->route('admin.purchase-orders.show', $purchaseOrder)->with('success', 'Purchase order created successfully.');
    }

    public function show(PurchaseOrder $purchase_order): View
    {
        $this->authorize('view', $purchase_order);

        $purchase_order->load(['supplier', 'items.itemable', 'createdBy']);

        return view('admin.purchase-orders.show', ['purchaseOrder' => $purchase_order]);
    }

    public function edit(PurchaseOrder $purchase_order): View|RedirectResponse
    {
        $this->authorize('update', $purchase_order);

        if ($purchase_order->status !== 'draft') {
            return Redirect::route('admin.purchase-orders.show', $purchase_order)->with('error', 'Only draft purchase orders can be edited.');
        }

        $purchase_order->load('items.itemable');

        return view('admin.purchase-orders.edit', [
            'purchaseOrder' => $purchase_order,
            'suppliers' => Supplier::active()->orderBy('name')->get(),
            'products' => Product::where('category', 'container')->orderBy('name')->get(),
            'consumables' => Consumable::orderBy('name')->get(),
        ]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchase_order): RedirectResponse
    {
        if ($purchase_order->status !== 'draft') {
            return redirect()->route('admin.purchase-orders.show', $purchase_order)->with('error', 'Only draft purchase orders can be edited.');
        }

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $purchase_order) {
            $purchase_order = PurchaseOrder::whereKey($purchase_order->id)->lockForUpdate()->firstOrFail();
            if ($purchase_order->status !== 'draft') {
                throw ValidationException::withMessages(['status' => 'Only draft purchase orders can be edited.']);
            }
            $purchase_order->update([
                'supplier_id' => $validated['supplier_id'],
                'status' => $validated['status'],
                'ordered_at' => $validated['ordered_at'] ?? $purchase_order->ordered_at ?? now()->toDateString(),
                'expected_date' => $validated['expected_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $purchase_order->items()->delete();
            $this->syncItems($purchase_order, $validated['items']);
            app(\App\Services\PurchaseOrderExpenseService::class)->sync($purchase_order);
        });

        return redirect()->route('admin.purchase-orders.show', $purchase_order)->with('success', 'Purchase order updated successfully.');
    }

    public function destroy(PurchaseOrder $purchase_order): RedirectResponse
    {
        $this->authorize('delete', $purchase_order);

        if ($purchase_order->status !== 'draft') {
            return redirect()->route('admin.purchase-orders.index')->with('error', 'Only draft purchase orders can be deleted.');
        }

        DB::transaction(function () use ($purchase_order) {
            $purchase_order = PurchaseOrder::whereKey($purchase_order->id)->lockForUpdate()->firstOrFail();
            if ($purchase_order->status !== 'draft') {
                throw ValidationException::withMessages(['status' => 'Only draft purchase orders can be deleted.']);
            }
            $purchase_order->delete();
        });

        return redirect()->route('admin.purchase-orders.index')->with('success', 'Purchase order deleted successfully.');
    }

    public function reset(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $request->validate(['reset_confirmation' => ['required', 'in:RESET']]);

        $count = DB::transaction(function () use ($request) {
            $orders = PurchaseOrder::orderBy('id')->lockForUpdate()->get();
            $items = DB::table('purchase_order_items')->whereIn('purchase_order_id', $orders->pluck('id'))
                ->orderBy('id')->lockForUpdate()->get();
            $backup = 'purchase-order-backups/purchase-orders-'.now()->format('Ymd-His').'-'.Str::uuid().'.json';
            if (! Storage::disk('local')->put($backup, json_encode([
                'created_at' => now()->toIso8601String(), 'purchase_orders' => $orders,
                'purchase_order_items' => $items,
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))) {
                throw ValidationException::withMessages(['reset_confirmation' => 'Backup failed. No purchase orders were deleted.']);
            }
            foreach ($orders->pluck('id')->chunk(500) as $ids) {
                DB::table('purchase_order_items')->whereIn('purchase_order_id', $ids)->delete();
                PurchaseOrder::whereIn('id', $ids)->delete();
            }
            activity('purchase_orders')->causedBy($request->user())->event('reset')
                ->withProperties(['orders_deleted' => $orders->count(), 'items_deleted' => $items->count(), 'backup' => $backup])
                ->log('Reset all purchase order records');

            return $orders->count();
        });

        return redirect()->route('admin.purchase-orders.index')
            ->with('success', "{$count} purchase orders and their items deleted. Stock quantities, suppliers, stock movements and expenses preserved.");
    }

    private function syncItems(PurchaseOrder $purchaseOrder, array $items): void
    {
        foreach ($items as $item) {
            $purchaseOrder->items()->create([
                'itemable_type' => self::ITEMABLE_TYPES[$item['itemable_type']],
                'itemable_id' => $item['itemable_id'],
                'quantity_ordered' => $item['quantity_ordered'],
                'unit_cost' => $item['unit_cost'],
            ]);
        }
    }

    private function generatePoNumber(): string
    {
        return 'PO-'.now()->format('Ymd').'-'.Str::ulid();
    }

    public function cancel(PurchaseOrder $purchase_order): RedirectResponse
    {
        $this->authorize('update', $purchase_order);
        DB::transaction(function () use ($purchase_order) {
            $order = PurchaseOrder::whereKey($purchase_order->id)->lockForUpdate()->firstOrFail();
            if (! in_array($order->status, ['draft', 'ordered'], true) || $order->items()->where('quantity_received', '>', 0)->exists()) {
                throw ValidationException::withMessages(['status' => 'Only unreceived orders can be cancelled.']);
            }
            $order->update(['status' => 'cancelled']);
            app(\App\Services\PurchaseOrderExpenseService::class)->sync($order);
        });

        return redirect()->route('admin.purchase-orders.show', $purchase_order)->with('success', 'Purchase order cancelled.');
    }
}
