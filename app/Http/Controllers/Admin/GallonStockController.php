<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GallonCustomerBalance;
use App\Models\GallonStock;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GallonStockController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', GallonStock::class);

        $allProducts = Product::where('category', 'container')
            ->with('gallonStocks')
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) {
                $product->setRelation(
                    'statusCounts',
                    collect(GallonStock::STATUSES)->mapWithKeys(fn (string $status) => [
                        $status => $product->gallonStocks->firstWhere('status', $status)?->quantity ?? 0,
                    ]),
                );
                $available = $product->statusCounts['filled'] + $product->statusCounts['empty'];
                $product->setAttribute('available_quantity', $available);
                $product->setAttribute('stock_status', $available <= 0 ? 'Out of Stock' : ($available <= $product->low_stock_threshold ? 'Low Stock' : 'In Stock'));

                return $product;
            });

        $dashboard = [
            'total' => $allProducts->sum(fn (Product $product) => $product->statusCounts->sum()),
            'empty' => $allProducts->sum(fn (Product $product) => $product->statusCounts['empty']),
            'filled' => $allProducts->sum(fn (Product $product) => $product->statusCounts['filled']),
            'withCustomers' => $allProducts->sum(fn (Product $product) => $product->statusCounts['customer_owned']),
            'damaged' => $allProducts->sum(fn (Product $product) => $product->statusCounts['damaged']),
            'missing' => $allProducts->sum(fn (Product $product) => $product->statusCounts['missing']),
        ];
        $search = trim((string) request('search'));
        $type = (string) request('type');
        $stockStatus = (string) request('stock_status');
        $types = $allProducts->map(fn (Product $product) => $this->typeOf($product))->unique()->sort()->values();
        $filtered = $allProducts->filter(fn (Product $product) =>
            ($search === '' || str_contains(strtolower($product->name.' '.$product->size.' GAL-'.str_pad((string) $product->id, 4, '0', STR_PAD_LEFT)), strtolower($search)))
            && ($type === '' || $this->typeOf($product) === $type)
            && ($stockStatus === '' || $product->stock_status === $stockStatus)
        )->values();
        $perPage = 10;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $products = (new LengthAwarePaginator($filtered->forPage($page, $perPage), $filtered->count(), $perPage, $page, ['path' => request()->url()]))->withQueryString();
        $customers = User::role('customer')->orderBy('name')->get(['id', 'name']);

        $gallonOptions = $allProducts->map(fn (Product $product) => [
            'id' => $product->id,
            'label' => $this->typeOf($product).' - '.$product->size,
            'empty' => $product->statusCounts['empty'],
        ])->values();

        return view('admin.gallon-stocks.index', compact('products', 'dashboard', 'customers', 'search', 'type', 'stockStatus', 'types', 'gallonOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('adjust', GallonStock::class);
        $data = $request->validate([
            'gallon_type' => ['required', Rule::in(['Round', 'Slim', 'Square'])],
            'size' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1'],
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $product = Product::create([
                'name' => $data['gallon_type'].' '.$data['size'],
                'category' => 'container',
                'size' => $data['size'],
                'unit_price' => 0,
                'stock_quantity' => 0,
                'low_stock_threshold' => $data['low_stock_threshold'],
                'is_active' => false,
            ]);
            $empty = $product->gallonStocks()->where('status', 'empty')->firstOrFail();
            $empty->update(['quantity' => $data['quantity']]);
            StockMovement::record($empty, 'initial', $data['quantity'], 0, $data['quantity'], $request->user()->id, $data['notes'] ?: 'Initial gallon inventory');
        });

        return back()->with('success', 'Gallon type added successfully.');
    }

    public function fill(Request $request, Product $product): RedirectResponse
    {
        $this->ensureContainer($product);
        return $this->move($request, $product, 'empty', 'filled', 'fill', 'Gallons filled');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->ensureContainer($product);
        $this->authorize('adjust', GallonStock::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'size' => ['required', 'string', 'max:100'],
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
        ]);

        $product->update($data);

        return back()->with('success', 'Gallon type updated successfully.');
    }

    public function release(Request $request, Product $product): RedirectResponse
    {
        $this->ensureContainer($product);
        $request->validate(['customer_id' => ['required', Rule::exists('users', 'id')], 'quantity' => ['required', 'integer', 'min:1'], 'notes' => ['nullable', 'string', 'max:1000']]);
        $customer = User::findOrFail($request->integer('customer_id'));
        if (! $customer->isCustomer()) {
            throw ValidationException::withMessages(['customer_id' => 'Select a registered customer.']);
        }
        $this->authorize('adjust', GallonStock::class);
        DB::transaction(function () use ($request, $product, $customer) {
            $notes = 'Customer: '.$customer->name.($request->notes ? ' — '.$request->notes : '');
            $this->moveRows($product, 'filled', 'customer_owned', (int) $request->quantity, 'release', $request->user()->id, $notes);
            $balance = GallonCustomerBalance::firstOrCreate(['product_id' => $product->id, 'customer_id' => $request->integer('customer_id')]);
            $balance->increment('quantity', $request->integer('quantity'));
        });
        return back()->with('success', 'Gallons released to customer successfully.');
    }

    public function returnFromCustomer(Request $request, Product $product): RedirectResponse
    {
        $this->ensureContainer($product);
        $request->validate(['customer_id' => ['required', Rule::exists('users', 'id')], 'return_status' => ['required', Rule::in(['empty', 'damaged'])], 'quantity' => ['required', 'integer', 'min:1'], 'notes' => ['nullable', 'string', 'max:1000']]);
        $customer = User::findOrFail($request->integer('customer_id'));
        if (! $customer->isCustomer()) {
            throw ValidationException::withMessages(['customer_id' => 'Select a registered customer.']);
        }
        $this->authorize('adjust', GallonStock::class);
        DB::transaction(function () use ($request, $product, $customer) {
            $balance = GallonCustomerBalance::lockForUpdate()->firstOrCreate(['product_id' => $product->id, 'customer_id' => $request->integer('customer_id')]);
            if ($balance->quantity < $request->integer('quantity')) throw ValidationException::withMessages(['quantity' => 'Return quantity exceeds this customer\'s outstanding gallon balance.']);
            $returnStatus = $request->string('return_status')->toString();
            $notes = 'Customer: '.$customer->name.($request->notes ? ' — '.$request->notes : '');
            $this->moveRows($product, 'customer_owned', $returnStatus, (int) $request->quantity, $returnStatus === 'damaged' ? 'damage' : 'returned', $request->user()->id, $notes);
            $balance->decrement('quantity', $request->integer('quantity'));
        });
        return back()->with('success', 'Returned gallons recorded successfully.');
    }

    public function adjust(Request $request, Product $product): RedirectResponse
    {
        $this->ensureContainer($product);
        $this->authorize('adjust', GallonStock::class);
        $data = $request->validate([
            'status' => ['required', Rule::in(['empty', 'filled', 'damaged', 'missing'])],
            'adjustment_type' => ['required', Rule::in(['increase', 'decrease'])],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($data, $product, $request) {
            $row = GallonStock::where(['product_id' => $product->id, 'status' => $data['status']])->lockForUpdate()->firstOrFail();
            if ($data['adjustment_type'] === 'decrease' && $row->quantity < $data['quantity']) {
                throw ValidationException::withMessages(['quantity' => "Not enough {$row->status_label} gallons to decrease."]);
            }
            $before = $row->quantity;
            $after = $data['adjustment_type'] === 'increase' ? $before + $data['quantity'] : $before - $data['quantity'];
            $row->update(['quantity' => $after]);
            StockMovement::record($row, 'adjustment_'.$data['adjustment_type'], $after - $before, $before, $after, $request->user()->id, $data['notes']);
        });

        return back()->with('success', 'Gallon stock adjusted successfully.');
    }

    private function move(Request $request, Product $product, string $from, string $to, string $type, string $message): RedirectResponse
    {
        $request->validate(['quantity' => ['required', 'integer', 'min:1'], 'notes' => ['nullable', 'string', 'max:1000']]);
        $this->authorize('adjust', GallonStock::class);
        DB::transaction(fn () => $this->moveRows($product, $from, $to, (int) $request->quantity, $type, $request->user()->id, $request->notes));
        return back()->with('success', $message.' successfully.');
    }

    private function moveRows(Product $product, string $from, string $to, int $quantity, string $type, int $userId, ?string $notes): void
    {
        $fromRow = GallonStock::where(['product_id' => $product->id, 'status' => $from])->lockForUpdate()->firstOrFail();
        if ($fromRow->quantity < $quantity) throw ValidationException::withMessages(['quantity' => "Not enough {$fromRow->status_label} gallons available."]);
        $toRow = GallonStock::where(['product_id' => $product->id, 'status' => $to])->lockForUpdate()->firstOrFail();
        $before = $fromRow->quantity; $fromRow->decrement('quantity', $quantity);
        StockMovement::record($fromRow, $type, -$quantity, $before, $before - $quantity, $userId, $notes);
        $before = $toRow->quantity; $toRow->increment('quantity', $quantity);
        StockMovement::record($toRow, $type, $quantity, $before, $before + $quantity, $userId, $notes);
    }

    private function typeOf(Product $product): string
    {
        return str($product->name)->before(' ')->title()->toString();
    }

    private function ensureContainer(Product $product): void
    {
        abort_unless($product->category === 'container', 404);
    }
}
