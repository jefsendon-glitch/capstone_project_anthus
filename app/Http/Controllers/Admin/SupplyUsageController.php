<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consumable;
use App\Models\StockMovement;
use App\Models\SupplyUsage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SupplyUsageController extends Controller
{
    private const ACTIVITIES = [
        'Water Production', 'Packaging & Sealing', 'Cleaning & Sanitation',
        'Equipment Maintenance', 'Sales Operations', 'Delivery Operations',
        'Administrative Operations', 'Other',
    ];

    public function index(): View
    {
        return view('admin.supply-usages.index', [
            'activities' => self::ACTIVITIES,
            'supplies' => Consumable::orderBy('name')->get(),
            'usages' => SupplyUsage::with('recordedBy')->orderByDesc('operation_date')->orderByDesc('id')->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'activity' => ['required', Rule::in(self::ACTIVITIES)],
            'other_operation' => ['exclude_unless:activity,Other', 'required', 'string', 'max:255'],
            'operation_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.consumable_id' => ['required', 'integer', 'distinct', Rule::exists('consumables', 'id')->whereNull('deleted_at')],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99999999'],
        ]);
        $data['operation'] = $data['activity'] === 'Other' ? $data['other_operation'] : $data['activity'];

        DB::transaction(function () use ($data, $request) {
            $supplies = Consumable::whereIn('id', array_column($data['items'], 'consumable_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $items = [];
            foreach ($data['items'] as $index => $item) {
                $supply = $supplies->get($item['consumable_id']);
                if (! $supply || (float) $item['quantity'] > (float) $supply->quantity) {
                    throw ValidationException::withMessages([
                        "items.$index.quantity" => $supply
                            ? "Insufficient stock for {$supply->name}. Available: {$supply->quantity} {$supply->unit}."
                            : 'This supply is no longer available.',
                    ]);
                }
                $items[] = [
                    'consumable_id' => $supply->id, 'name' => $supply->name,
                    'unit' => $supply->unit, 'quantity' => $item['quantity'],
                ];
            }

            $usage = SupplyUsage::create([
                'operation' => $data['operation'], 'operation_date' => $data['operation_date'],
                'notes' => $data['notes'] ?? null, 'recorded_by' => $request->user()->id, 'items' => $items,
            ]);
            foreach ($items as $item) {
                $supply = $supplies->get($item['consumable_id']);
                $before = (float) $supply->quantity;
                $after = round($before - (float) $item['quantity'], 2);
                $supply->update(['quantity' => $after]);
                StockMovement::record($supply, 'operation_usage', -(float) $item['quantity'], $before, $after,
                    $request->user()->id, "Usage #{$usage->id}: {$data['operation']} ({$data['operation_date']})",
                    (float) $supply->unit_cost);
            }
        });

        return redirect()->route('admin.inventory.supply-usages.index')
            ->with('success', 'Operation supplies recorded. Procurement stock quantities updated automatically.');
    }
}
