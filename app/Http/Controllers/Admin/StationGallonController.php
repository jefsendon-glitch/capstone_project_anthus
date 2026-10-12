<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WaterProductionLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StationGallonController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = $request->input('month') ?: '';
        $gallons = DB::table('station_gallons')->orderBy('name')->get();
        $customers = User::role('customer')->orderBy('name')->get(['id', 'name']);
        $movements = $this->movementQuery($month)->orderByDesc('m.created_at')->orderByDesc('m.id')
            ->paginate(20)->withQueryString();
        $loans = DB::table('station_gallon_loans as l')
            ->join('station_gallons as g', 'g.id', '=', 'l.station_gallon_id')
            ->join('users as u', 'u.id', '=', 'l.customer_id')
            ->where('l.quantity', '>', 0)
            ->select('g.name', 'g.size', 'u.name as customer_name', 'l.quantity')->get();

        return view('admin.station-gallons.index', compact('gallons', 'customers', 'movements', 'loans', 'month'));
    }

    public function report(Request $request)
    {
        $data = $request->validate(['month' => ['required', 'date_format:Y-m']]);
        $month = $data['month'];
        $movements = $this->movementQuery($month)->orderBy('m.created_at')->orderBy('m.id')->get();
        $summary = $movements->groupBy('action')->map(fn ($rows) => $rows->sum('quantity'));
        $monthLabel = CarbonImmutable::createFromFormat('!Y-m', $month)->format('F Y');

        return view('admin.station-gallons.report', compact('month', 'monthLabel', 'movements', 'summary'));
    }

    private function movementQuery(string $month): Builder
    {
        $query = DB::table('station_gallon_movements as m')
            ->join('station_gallons as g', 'g.id', '=', 'm.station_gallon_id')
            ->join('users as u', 'u.id', '=', 'm.recorded_by')
            ->leftJoin('users as c', 'c.id', '=', 'm.customer_id')
            ->select('m.*', 'g.name as gallon_name', 'g.size as gallon_size', 'u.name as recorded_by_name', 'c.name as customer_name');
        if ($month !== '') {
            $start = CarbonImmutable::createFromFormat('!Y-m', $month, config('app.timezone'));
            $query->where('m.created_at', '>=', $start)->where('m.created_at', '<', $start->addMonth());
        }

        return $query;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'size' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);
        DB::transaction(function () use ($data, $request) {
            $id = DB::table('station_gallons')->insertGetId([
                'name' => $data['name'], 'size' => $data['size'], 'empty' => $data['quantity'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->record($id, 'add', null, 'empty', $data['quantity'], $request->user()->id);
        });

        return back()->with('success', 'Station gallon type created.');
    }

    public function update(Request $request, int $gallon)
    {
        abort_unless(DB::table('station_gallons')->where('id', $gallon)->exists(), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'size' => ['required', 'string', 'max:100'],
            'quantity_action' => ['nullable', Rule::in(['increase', 'decrease'])],
            'adjustment_quantity' => ['nullable', 'required_with:quantity_action', 'integer', 'min:1', 'max:1000000'],
            'adjustment_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $data, $gallon) {
            $stock = DB::table('station_gallons')->where('id', $gallon)->lockForUpdate()->first();
            abort_unless($stock, 404);
            $changes = ['name' => $data['name'], 'size' => $data['size'], 'updated_at' => now()];
            if (! empty($data['quantity_action'])) {
                $quantity = (int) $data['adjustment_quantity'];
                $decrease = $data['quantity_action'] === 'decrease';
                if ($decrease && $quantity > $stock->empty) {
                    throw ValidationException::withMessages([
                        'adjustment_quantity' => 'Not enough empty gallons available to decrease by this quantity.',
                    ]);
                }
                $changes['empty'] = $stock->empty + ($decrease ? -$quantity : $quantity);
                $this->record($gallon, $decrease ? 'decrease' : 'add', $decrease ? 'empty' : null,
                    $decrease ? 'removed' : 'empty', $quantity, $request->user()->id, null,
                    $data['adjustment_notes'] ?? 'Quantity adjusted from container inventory.');
            }
            DB::table('station_gallons')->where('id', $gallon)->update($changes);
        });

        return back()->with('success', 'Registered gallon updated successfully.');
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'reset_scope' => ['required', Rule::in(['history', 'all'])],
            'reset_confirmation' => ['required', Rule::in(['RESET'])],
        ]);

        DB::transaction(function () use ($data) {
            DB::table('station_gallons')->orderBy('id')->lockForUpdate()->get();
            DB::table('station_gallon_movements')->delete();
            if ($data['reset_scope'] === 'all') {
                DB::table('station_gallon_loans')->delete();
                DB::table('station_gallons')->delete();
            }
        });

        return redirect()->route('admin.inventory.gallon-inventory')->with('success',
            $data['reset_scope'] === 'all'
                ? 'Gallon inventory, customer loan records, and movement history reset successfully.'
                : 'Gallon movement history cleared. Inventory quantities and customer loans preserved.');
    }

    public function move(Request $request)
    {
        $data = $request->validate([
            'gallon_id' => ['required', 'integer', 'exists:station_gallons,id'],
            'action' => ['required', Rule::in(['add', 'fill', 'release', 'return', 'damage', 'missing'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'from_status' => ['nullable', Rule::in(['empty', 'filled', 'borrowed'])],
            'return_status' => ['nullable', Rule::in(['empty', 'damaged'])],
            'customer_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'required_if:action,damage,missing', 'string', 'max:1000'],
        ]);
        [$from, $to] = match ($data['action']) {
            'add' => [null, 'empty'],
            'fill' => ['empty', 'filled'],
            'release' => ['filled', 'borrowed'],
            'return' => ['borrowed', $data['return_status'] ?? 'empty'],
            'damage' => [$data['from_status'] ?? 'empty', 'damaged'],
            'missing' => [$data['from_status'] ?? 'empty', 'missing'],
        };
        $customerId = null;
        if ($from === 'borrowed' || $to === 'borrowed') {
            $customer = User::find($data['customer_id'] ?? 0);
            if (! $customer?->isCustomer()) {
                throw ValidationException::withMessages(['customer_id' => 'Select the customer who borrowed these gallons.']);
            }
            $customerId = $customer->id;
        }
        DB::transaction(function () use ($request, $data, $from, $to, $customerId) {
            // Serialize all changes to this gallon type, including its customer balances.
            $gallon = DB::table('station_gallons')->where('id', $data['gallon_id'])->lockForUpdate()->first();
            abort_unless($gallon, 404);
            $quantity = (int) $data['quantity'];
            if ($from && $gallon->$from < $quantity) {
                throw ValidationException::withMessages(['quantity' => "Not enough {$from} gallons available."]);
            }
            if ($customerId) {
                $loan = DB::table('station_gallon_loans')->where('station_gallon_id', $gallon->id)->where('customer_id', $customerId);
                $balance = (int) $loan->value('quantity');
                if ($from === 'borrowed' && $balance < $quantity) {
                    throw ValidationException::withMessages(['quantity' => 'Quantity exceeds this customer’s borrowed gallons.']);
                }
                DB::table('station_gallon_loans')->updateOrInsert(
                    ['station_gallon_id' => $gallon->id, 'customer_id' => $customerId],
                    ['quantity' => $balance + ($to === 'borrowed' ? $quantity : -$quantity)],
                );
            }
            $changes = [$to => $gallon->$to + $quantity, 'updated_at' => now()];
            if ($from) $changes[$from] = $gallon->$from - $quantity;
            if ($data['action'] === 'return') $changes['returned'] = $gallon->returned + $quantity;
            DB::table('station_gallons')->where('id', $gallon->id)->update($changes);
            $movementId = $this->record($gallon->id, $data['action'], $from, $to, $quantity, $request->user()->id, $customerId, $data['notes'] ?? null);
            if ($data['action'] === 'fill') {
                WaterProductionLog::create([
                    'station_gallon_movement_id' => $movementId,
                    'container_name' => $gallon->name,
                    'container_size' => $gallon->size,
                    'gallons_produced' => $quantity,
                    'produced_by' => $request->user()->id,
                    'production_date' => now()->toDateString(),
                    'notes' => $data['notes'] ?? null,
                ]);
            }
        });

        return back()->with('success', 'Gallon inventory and movement history updated.');
    }

    private function record(int $id, string $action, ?string $from, string $to, int $quantity, int $userId, ?int $customerId = null, ?string $notes = null): int
    {
        return DB::table('station_gallon_movements')->insertGetId([
            'station_gallon_id' => $id, 'action' => $action, 'from_status' => $from, 'to_status' => $to,
            'quantity' => $quantity, 'recorded_by' => $userId, 'customer_id' => $customerId, 'notes' => $notes,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
