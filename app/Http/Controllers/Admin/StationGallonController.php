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
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'loan_search' => ['nullable', 'string', 'max:100'],
            'issue_search' => ['nullable', 'string', 'max:100'],
            'movement_search' => ['nullable', 'string', 'max:100'],
        ]);
        $month = $request->input('month') ?: '';
        $gallons = DB::table('station_gallons')->orderBy('name')->get();
        $customers = User::role('customer')->orderBy('name')->get(['id', 'name']);
        $movements = $this->searchRecords($this->movementQuery($month), $request->input('movement_search'),
            ['c.name', 'g.name', 'g.type', 'g.size', 'm.action', 'm.from_status', 'm.to_status', 'm.notes', 'u.name'])
            ->orderByDesc('m.created_at')->orderByDesc('m.id')
            ->paginate(5)->withQueryString()->fragment('movement-history');
        $loans = DB::table('station_gallon_loans as l')
            ->join('station_gallons as g', 'g.id', '=', 'l.station_gallon_id')
            ->join('users as u', 'u.id', '=', 'l.customer_id')
            ->where('l.quantity', '>', 0)
            ->select('l.station_gallon_id', 'l.customer_id', 'g.name', 'g.type', 'g.size', 'u.name as customer_name', 'l.quantity')->get();
        // Keep the complete balances available to the return form, regardless of table filters.
        $loanRecords = $this->searchRecords(DB::table('station_gallon_loans as l')
            ->join('station_gallons as g', 'g.id', '=', 'l.station_gallon_id')
            ->join('users as u', 'u.id', '=', 'l.customer_id')->where('l.quantity', '>', 0),
            $request->input('loan_search'), ['u.name', 'g.name', 'g.type', 'g.size'])
            ->select('g.name', 'g.type', 'g.size', 'u.name as customer_name', 'l.quantity')
            ->orderBy('u.name')->orderBy('l.id')->paginate(5, ['*'], 'loans_page')
            ->withQueryString()->fragment('customer-loans');

        $customerIssues = $this->movementQuery('')
            ->whereNotNull('m.customer_id')
            ->where(function (Builder $query) {
                $query->where('m.action', 'missing')->orWhere(function (Builder $query) {
                    $query->where('m.action', 'return')->where('m.to_status', 'damaged');
                });
            });
        $customerIssues = $this->searchRecords($customerIssues, $request->input('issue_search'),
            ['c.name', 'g.name', 'g.type', 'g.size', 'm.action', 'm.to_status', 'm.notes'])
            ->orderByDesc('m.id')->paginate(5, ['*'], 'issues_page')
            ->withQueryString()->fragment('customer-issues');

        return view('admin.station-gallons.index', compact('gallons', 'customers', 'movements', 'loans', 'loanRecords', 'month', 'customerIssues'));
    }

    private function searchRecords(Builder $query, ?string $search, array $columns): Builder
    {
        $search = trim($search ?? '');
        if ($search !== '') {
            $query->where(function (Builder $query) use ($search, $columns) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }

        return $query;
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
            ->select('m.*', 'g.name as gallon_name', 'g.type as gallon_type', 'g.size as gallon_size', 'u.name as recorded_by_name', 'c.name as customer_name');
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
            'type' => ['required', 'string', 'max:100'],
            'size' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);
        DB::transaction(function () use ($data, $request) {
            $id = DB::table('station_gallons')->insertGetId([
                'name' => $data['name'], 'type' => $data['type'], 'size' => $data['size'], 'empty' => $data['quantity'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->record($id, 'add', null, 'empty', $data['quantity'], $request->user()->id);
        });

        return back()->with('success', 'Gallon registered. Opening quantity added to empty stock.');
    }

    public function update(Request $request, int $gallon)
    {
        abort_unless(DB::table('station_gallons')->where('id', $gallon)->exists(), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'size' => ['required', 'string', 'max:100'],
            'quantity_action' => ['nullable', Rule::in(['increase', 'decrease'])],
            'adjustment_quantity' => ['nullable', 'required_with:quantity_action', 'integer', 'min:1', 'max:1000000'],
            'adjustment_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $data, $gallon) {
            $stock = DB::table('station_gallons')->where('id', $gallon)->lockForUpdate()->first();
            abort_unless($stock, 404);
            $changes = ['name' => $data['name'], 'type' => $data['type'], 'size' => $data['size'], 'updated_at' => now()];
            if (! empty($data['quantity_action'])) {
                $quantity = (int) $data['adjustment_quantity'];
                $decrease = $data['quantity_action'] === 'decrease';
                if ($decrease && $quantity > $stock->empty) {
                    throw ValidationException::withMessages([
                        'adjustment_quantity' => 'The decrease cannot exceed the available empty gallons.',
                    ]);
                }
                $changes['empty'] = $stock->empty + ($decrease ? -$quantity : $quantity);
                $this->record($gallon, $decrease ? 'decrease' : 'add', $decrease ? 'empty' : null,
                    $decrease ? 'removed' : 'empty', $quantity, $request->user()->id, null,
                    $data['adjustment_notes'] ?? 'Empty stock adjusted from the inventory form.');
            }
            DB::table('station_gallons')->where('id', $gallon)->update($changes);
        });

        return back()->with('success', 'Gallon details and any stock adjustment saved.');
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
        // Staff may process customer handovers only; inventory controls remain with Admin.
        abort_unless($request->user()->isAdmin()
            || ($request->user()->isStaff() && in_array($request->input('action'), ['release', 'return'], true)), 403);

        $data = $request->validate([
            'gallon_id' => ['required', 'integer', 'exists:station_gallons,id'],
            'action' => ['required', Rule::in(['add', 'fill', 'release', 'return', 'damage', 'missing'])],
            'quantity' => ['required', 'integer', 'min:'.($request->input('action') === 'return' ? 0 : 1), 'max:1000000'],
            'from_status' => ['nullable', 'required_if:action,damage', Rule::in(['empty', 'filled', 'borrowed'])],
            'return_status' => ['nullable', Rule::in(['empty', 'damaged'])],
            'damaged_quantity' => ['nullable', 'integer', 'min:0', 'max:1000000', 'lte:quantity'],
            'settle_return' => ['nullable', 'boolean'],
            'customer_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'required_if:action,damage,missing', 'string', 'max:1000'],
        ]);
        [$from, $to] = match ($data['action']) {
            'add' => [null, 'empty'],
            'fill' => ['empty', 'filled'],
            'release' => ['filled', 'borrowed'],
            'return' => ['borrowed', $data['return_status'] ?? 'empty'],
            'damage' => [$data['from_status'] ?? 'empty', 'damaged'],
            'missing' => [$data['from_status'] ?? 'borrowed', 'missing'],
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
                if ($data['action'] === 'return') {
                    if ($balance === 0) {
                        throw ValidationException::withMessages(['customer_id' => 'This customer has no borrowed gallons of this type.']);
                    }
                    $missing = ($data['settle_return'] ?? true) ? $balance - $quantity : 0;
                    if ($quantity === 0 && $missing === 0) {
                        throw ValidationException::withMessages(['quantity' => 'Enter at least 1 returned gallon, or choose Automatically record shortage as missing.']);
                    }
                    $damaged = (int) ($data['damaged_quantity'] ?? ($to === 'damaged' ? $quantity : 0));
                    $usable = $quantity - $damaged;
                    DB::table('station_gallon_loans')->where('station_gallon_id', $gallon->id)
                        ->where('customer_id', $customerId)->update(['quantity' => $balance - $quantity - $missing]);
                    DB::table('station_gallons')->where('id', $gallon->id)->update([
                        'borrowed' => $gallon->borrowed - $quantity - $missing,
                        'empty' => $gallon->empty + $usable,
                        'damaged' => $gallon->damaged + $damaged,
                        'missing' => $gallon->missing + $missing,
                        'returned' => $gallon->returned + $quantity,
                        'updated_at' => now(),
                    ]);
                    foreach (['empty' => $usable, 'damaged' => $damaged, 'missing' => $missing] as $status => $count) {
                        if ($count > 0) {
                            $notes = $status === 'missing'
                                ? 'Return shortage: '.$balance.' borrowed, '.$quantity.' returned; '.$missing.' recorded as missing.'
                                : ($status === 'damaged' ? 'Damaged return: '.$damaged.' of '.$quantity.' returned gallons.' : 'Usable return: '.$usable.' of '.$quantity.' returned gallons.');
                            if (! empty($data['notes'])) {
                                $notes .= ' Notes: '.$data['notes'];
                            }
                            $this->record($gallon->id, $status === 'missing' ? 'missing' : 'return', 'borrowed',
                                $status, $count, $request->user()->id, $customerId, $notes);
                        }
                    }

                    return;
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

        return back()->with('success', $data['action'] === 'return'
            ? 'Customer return saved. Damaged returns and any missing shortage are recorded under the customer.'
            : ($data['action'] === 'missing' && $customerId
            ? 'Missing gallons recorded under the customer. Borrowed balance updated.'
            : 'Gallon movement saved. Stock and history updated.'));
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
