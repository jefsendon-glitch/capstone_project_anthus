<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consumable;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProcurementController extends Controller
{
    public function receiving(Request $request): View
    {
        $this->authorize('viewAny', PurchaseOrder::class);
        $request->validate(['supplier_id' => ['nullable', 'integer', 'exists:suppliers,id']]);

        $orders = PurchaseOrder::with(['supplier', 'items.itemable'])
            ->whereIn('status', ['ordered', 'partially_received'])
            ->when($request->input('supplier_id'), fn ($q, $id) => $q->where('supplier_id', $id))
            ->oldest('ordered_at')->paginate(15)->withQueryString();

        return view('admin.procurement.receiving', [
            'orders' => $orders,
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }

    public function reorders(Request $request): View
    {
        $this->authorize('viewAny', Consumable::class);
        $request->validate(['supplier_id' => ['nullable', 'integer', 'exists:suppliers,id']]);

        $supplies = Consumable::with('supplier')->lowStock()
            ->when($request->input('supplier_id'), fn ($q, $id) => $q->where('supplier_id', $id))
            ->orderBy('quantity')->orderBy('name')->paginate(15)->withQueryString();

        $pending = PurchaseOrderItem::where('itemable_type', (new Consumable)->getMorphClass())
            ->whereHas('purchaseOrder', fn ($q) => $q->whereIn('status', ['ordered', 'partially_received']))
            ->selectRaw('itemable_id, SUM(quantity_ordered - quantity_received) as outstanding')
            ->groupBy('itemable_id')->pluck('outstanding', 'itemable_id');

        return view('admin.procurement.reorders', [
            'supplies' => $supplies,
            'pending' => $pending,
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }

    public function history(Request $request): View
    {
        $this->authorize('viewAny', PurchaseOrder::class);
        $filters = $request->validate([
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => array_filter(['nullable', 'date', $request->filled('date_from') ? 'after_or_equal:date_from' : null]),
            'status' => ['nullable', 'in:ordered,partially_received,received,cancelled,draft'],
        ]);
        $query = PurchaseOrder::query()
            ->when($filters['supplier_id'] ?? null, fn ($q, $id) => $q->where('supplier_id', $id))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('ordered_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('ordered_at', '<=', $date))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status));

        $totals = PurchaseOrderItem::whereHas('purchaseOrder', function ($q) use ($query) {
            $q->whereIn('id', (clone $query)->select('id'))->whereNotIn('status', ['draft', 'cancelled']);
        })->selectRaw('COALESCE(SUM(quantity_ordered * unit_cost), 0) as ordered_cost, COALESCE(SUM(quantity_received * unit_cost), 0) as received_cost')->first();

        return view('admin.procurement.history', [
            'orders' => $query->with(['supplier', 'items.itemable'])->latest('ordered_at')->paginate(15)->withQueryString(),
            'totals' => $totals,
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }
}
