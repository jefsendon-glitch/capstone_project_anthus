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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GallonStockController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', GallonStock::class);

        $products = Product::where('category', 'container')
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
            'total' => $products->sum(fn (Product $product) => $product->statusCounts->sum()),
            'empty' => $products->sum(fn (Product $product) => $product->statusCounts['empty']),
            'filled' => $products->sum(fn (Product $product) => $product->statusCounts['filled']),
            'withCustomers' => $products->sum(fn (Product $product) => $product->statusCounts['customer_owned']),
            'damaged' => $products->sum(fn (Product $product) => $product->statusCounts['damaged']),
            'missing' => $products->sum(fn (Product $product) => $product->statusCounts['missing']),
        ];
        $customers = User::role('customer')->orderBy('name')->get(['id', 'name']);

        return view('admin.gallon-stocks.index', compact('products', 'dashboard', 'customers'));
    }

    public function fill(Request $request, Product $product): RedirectResponse
    {
        return $this->move($request, $product, 'empty', 'filled', 'fill', 'Gallons filled');
    }

    public function release(Request $request, Product $product): RedirectResponse
    {
        $request->validate(['customer_id' => ['required', Rule::exists('users', 'id')], 'quantity' => ['required', 'integer', 'min:1'], 'notes' => ['nullable', 'string', 'max:1000']]);
        $this->authorize('adjust', GallonStock::class);
        DB::transaction(function () use ($request, $product) {
            $this->moveRows($product, 'filled', 'customer_owned', (int) $request->quantity, 'release', $request->user()->id, $request->notes);
            $balance = GallonCustomerBalance::firstOrCreate(['product_id' => $product->id, 'customer_id' => $request->integer('customer_id')]);
            $balance->increment('quantity', $request->integer('quantity'));
        });
        return back()->with('success', 'Gallons released to customer successfully.');
    }

    public function returnFromCustomer(Request $request, Product $product): RedirectResponse
    {
        $request->validate(['customer_id' => ['required', Rule::exists('users', 'id')], 'quantity' => ['required', 'integer', 'min:1'], 'notes' => ['nullable', 'string', 'max:1000']]);
        $this->authorize('adjust', GallonStock::class);
        DB::transaction(function () use ($request, $product) {
            $balance = GallonCustomerBalance::lockForUpdate()->firstOrCreate(['product_id' => $product->id, 'customer_id' => $request->integer('customer_id')]);
            if ($balance->quantity < $request->integer('quantity')) throw ValidationException::withMessages(['quantity' => 'Return quantity exceeds this customer\'s outstanding gallon balance.']);
            $this->moveRows($product, 'customer_owned', 'empty', (int) $request->quantity, 'returned', $request->user()->id, $request->notes);
            $balance->decrement('quantity', $request->integer('quantity'));
        });
        return back()->with('success', 'Returned gallons recorded successfully.');
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
}
