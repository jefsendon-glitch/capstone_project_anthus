<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consumable;
use App\Models\GallonStock;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function reset(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $request->validate(['reset_confirmation' => ['required', 'in:RESET']]);

        $count = DB::transaction(function () use ($request) {
            $stock = DB::table('stock_movements')->orderBy('id')->lockForUpdate()->get();
            $gallons = DB::table('station_gallon_movements')->orderBy('id')->lockForUpdate()->get();
            $backup = 'stock-history-backups/stock-movements-'.now()->format('Ymd-His').'-'.Str::uuid().'.json';
            if (! Storage::disk('local')->put($backup, json_encode([
                'created_at' => now()->toIso8601String(), 'stock_movements' => $stock,
                'station_gallon_movements' => $gallons,
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))) {
                throw \Illuminate\Validation\ValidationException::withMessages(['reset_confirmation' => 'Backup failed. No history records were deleted.']);
            }
            foreach ($stock as $movement) {
                if ($movement->type === 'purchase_receive' && str_starts_with($movement->reference_code ?? '', 'PR-')) {
                    DB::table('stock_receipt_references')->insertOrIgnore([
                        'reference_code' => $movement->reference_code, 'created_at' => $movement->created_at,
                    ]);
                }
            }
            DB::table('stock_movements')->delete();
            DB::table('station_gallon_movements')->delete();
            activity('stock_movements')->causedBy($request->user())->event('reset')
                ->withProperties(['stock_movements_deleted' => $stock->count(), 'gallon_movements_deleted' => $gallons->count(), 'backup' => $backup])
                ->log('Reset all stock movement history');

            return $stock->count() + $gallons->count();
        });

        return redirect()->route('admin.stock-movements.index')
            ->with('success', "{$count} history records cleared. Stock quantities, customer loans, sales and payments preserved.");
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', StockMovement::class);
        $itemTypes = ['product' => 'Products', 'consumable' => 'Supplies', 'station_gallon' => 'Gallon Tracking', 'gallon_stock' => 'Legacy Gallons'];
        $movementTypes = ['initial' => 'Opening Stock', 'restock' => 'Restock', 'production' => 'Water Production',
            'purchase_receive' => 'Purchase Received', 'operation_usage' => 'Operation Supplies Usage', 'adjustment_increase' => 'Quantity Increase',
            'adjustment_decrease' => 'Quantity Decrease', 'sale_deduction' => 'Sale / Delivery',
            'add' => 'Add Gallons', 'decrease' => 'Remove Gallons', 'fill' => 'Fill Gallons',
            'release' => 'Release to Customer', 'return' => 'Customer Return', 'returned' => 'Legacy Return',
            'damage' => 'Damaged', 'missing' => 'Missing'];
        $filters = $request->validate([
            'item_type' => ['nullable', Rule::in(array_keys($itemTypes))],
            'type' => ['nullable', Rule::in(array_keys($movementTypes))],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ]);
        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            $request->validate(['date_to' => ['after_or_equal:date_from']]);
        }
        $stock = DB::table('stock_movements as m')
            ->leftJoin('products as p', function ($join) { $join->on('p.id', '=', 'm.movable_id')->where('m.movable_type', (new Product)->getMorphClass()); })
            ->leftJoin('consumables as s', function ($join) { $join->on('s.id', '=', 'm.movable_id')->where('m.movable_type', (new Consumable)->getMorphClass()); })
            ->leftJoin('gallon_stocks as g', function ($join) { $join->on('g.id', '=', 'm.movable_id')->where('m.movable_type', (new GallonStock)->getMorphClass()); })
            ->leftJoin('products as gp', 'gp.id', '=', 'g.product_id')
            ->leftJoin('users as u', 'u.id', '=', 'm.recorded_by')->leftJoin('users as c', 'c.id', '=', 'm.customer_id')
            ->select('m.id', 'm.type', 'm.quantity_before', 'm.quantity_after', 'm.quantity_delta', 'm.notes', 'm.reference_code', 'm.created_at', 'u.name as recorded_by_name', 'c.name as customer_name')
            ->selectRaw("COALESCE(p.name, s.name, gp.name, 'Deleted item') as item_name, COALESCE(p.size, s.unit, gp.size) as item_detail, g.status as from_status, NULL as to_status, NULL as moved_quantity")
            ->selectRaw('CASE m.movable_type WHEN ? THEN ? WHEN ? THEN ? ELSE ? END as item_type', [(new Product)->getMorphClass(), 'product', (new Consumable)->getMorphClass(), 'consumable', 'gallon_stock']);
        $gallons = DB::table('station_gallon_movements as m')
            ->join('station_gallons as g', 'g.id', '=', 'm.station_gallon_id')
            ->leftJoin('users as u', 'u.id', '=', 'm.recorded_by')->leftJoin('users as c', 'c.id', '=', 'm.customer_id')
            ->select('m.id', 'm.action as type')
            ->selectRaw('NULL as quantity_before, NULL as quantity_after, CASE WHEN m.action = ? THEN -m.quantity WHEN m.action = ? THEN m.quantity ELSE 0 END as quantity_delta', ['decrease', 'add'])
            ->addSelect('m.notes')->selectRaw("'GAL-' as reference_code")
            ->addSelect('m.created_at', 'u.name as recorded_by_name', 'c.name as customer_name', 'g.name as item_name', 'g.size as item_detail', 'm.from_status', 'm.to_status', 'm.quantity as moved_quantity')
            ->selectRaw("'station_gallon' as item_type");
        $query = DB::query()->fromSub($stock->unionAll($gallons), 'history');
        foreach (['item_type', 'type'] as $field) {
            if (! empty($filters[$field])) $query->where($field, $filters[$field]);
        }
        if (! empty($filters['date_from'])) $query->whereDate('created_at', '>=', $filters['date_from']);
        if (! empty($filters['date_to'])) $query->whereDate('created_at', '<=', $filters['date_to']);
        $movements = $query->orderByDesc('created_at')->orderByDesc('id')->orderBy('item_type')->paginate(20)->withQueryString();

        return view('admin.stock-movements.index', compact('movements', 'filters', 'itemTypes', 'movementTypes'));
    }
}
