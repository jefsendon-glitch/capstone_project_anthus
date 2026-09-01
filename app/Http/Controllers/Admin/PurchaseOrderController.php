<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Models\Consumable;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\PurchaseOrderReceivingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    private const ITEMABLE_TYPES = [
        'product' => Product::class,
        'consumable' => Consumable::class,
    ];

    public function __construct(private readonly PurchaseOrderReceivingService $receiving)
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $purchaseOrders = PurchaseOrder::with('supplier')->withCount('items')->latest()->paginate(15);

        return view('admin.purchase-orders.index', compact('purchaseOrders'));
    }

    public function create(): View
    {
        $this->authorize('create', PurchaseOrder::class);

        return view('admin.purchase-orders.create', [
            'suppliers' => Supplier::active()->orderBy('name')->get(),
            'products' => Product::orderBy('category')->orderBy('name')->get(),
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
                'status' => 'ordered',
                'ordered_at' => $validated['ordered_at'] ?? now()->toDateString(),
                'expected_date' => $validated['expected_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $this->syncItems($purchaseOrder, $validated['items']);

            $purchaseOrder->load('items');
            $this->receiving->receive($purchaseOrder, $purchaseOrder->items->map(fn ($item) => [
                'purchase_order_item_id' => $item->id,
                'quantity_received' => $item->quantity_ordered,
            ])->all(), $request->user());

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
            'products' => Product::orderBy('name')->get(),
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
            $purchase_order->update([
                'supplier_id' => $validated['supplier_id'],
                'status' => $validated['status'],
                'ordered_at' => $validated['ordered_at'] ?? null,
                'expected_date' => $validated['expected_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $purchase_order->items()->delete();
            $this->syncItems($purchase_order, $validated['items']);
        });

        return redirect()->route('admin.purchase-orders.show', $purchase_order)->with('success', 'Purchase order updated successfully.');
    }

    public function destroy(PurchaseOrder $purchase_order): RedirectResponse
    {
        $this->authorize('delete', $purchase_order);

        if ($purchase_order->status !== 'draft') {
            return redirect()->route('admin.purchase-orders.index')->with('error', 'Only draft purchase orders can be deleted.');
        }

        $purchase_order->delete();

        return redirect()->route('admin.purchase-orders.index')->with('success', 'Purchase order deleted successfully.');
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
        $today = now()->format('Ymd');
        $sequence = PurchaseOrder::where('po_number', 'like', "PO-{$today}-%")->count() + 1;

        return sprintf('PO-%s-%03d', $today, $sequence);
    }
}
