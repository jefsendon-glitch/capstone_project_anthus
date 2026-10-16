<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\Setting;
use App\Models\User;
use App\Services\SalesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class POSController extends Controller
{
    public function __construct(private readonly SalesService $sales)
    {
    }

    public function index(Request $request): View
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:walk-in,delivery'],
            'scope' => ['nullable', 'in:mine,all'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
        ]);
        $search = trim((string) $request->input('search', ''));
        $transactions = SalesTransaction::with(['processedBy', 'customer'])
            ->when($request->input('scope') === 'mine', fn ($query) => $query->where('processed_by', $request->user()->id))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->input('to')))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('transaction_code', 'like', '%'.$search.'%')
                        ->orWhere('customer_name', 'like', '%'.$search.'%')
                        ->orWhere('product_name', 'like', '%'.$search.'%')
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($request->filled('type'), fn ($query) => $query->where('transaction_type', $request->string('type')))
            ->latest()->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('staff.pos.index', compact('transactions'));
    }

    public function create(): View
    {
        $products = Product::active()->orderBy('name')->get();
        $customers = User::role('customer')->orderBy('name')->get();

        $productsForCart = $products->map(fn (Product $product) => [
            'id' => $product->id,
            'name' => $product->name,
            'category' => $product->category,
            'size' => $product->size,
            'unit_price' => (float) $product->unit_price,
            'stock_quantity' => $product->stock_quantity,
            'is_low_stock' => $product->is_low_stock,
            'stock_unit_label' => $product->stock_unit_label,
            'image_url' => $product->image_url,
        ])->values();

        return view('staff.pos.create', compact('products', 'customers', 'productsForCart'));
    }

    public function reset(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $request->validate(['reset_confirmation' => ['required', 'in:RESET']]);

        [$deleted, $retained] = DB::transaction(function () use ($request) {
            $transactions = SalesTransaction::orderBy('id')->lockForUpdate()->get(['id', 'payment_method', 'total_amount', 'credit_paid_amount']);
            $eligible = $transactions->filter(fn ($sale) => $sale->payment_method === 'cash'
                || (float) $sale->credit_paid_amount >= (float) $sale->total_amount);
            $deleted = 0;
            foreach ($eligible->pluck('id')->chunk(500) as $ids) {
                $deleted += SalesTransaction::whereIn('id', $ids)->delete();
            }
            $retained = $transactions->count() - $eligible->count();
            activity('sales_transactions')->causedBy($request->user())->event('reset')
                ->withProperties(['transactions_deleted' => $deleted, 'unpaid_credit_retained' => $retained])
                ->log('Reset settled POS transactions');

            return [$deleted, $retained];
        });

        return redirect()->route('pos.index')->with('success',
            "{$deleted} settled transactions deleted. {$retained} unpaid credit transactions retained. Stock, customer balances, payments, and delivery orders preserved.");
    }

    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $transactions = $this->sales->recordSale($request->validated(), $request->user());

        $itemCount = $transactions->count();
        $total = $transactions->sum('total_amount');

        return redirect()->route('pos.receipt', ['codes' => $transactions->pluck('transaction_code')->implode(',')])->with(
            'success',
            "Sale recorded: {$itemCount} ".str('item')->plural($itemCount)." totaling ₱".number_format($total, 2)
        );
    }

    public function show(SalesTransaction $transaction): View
    {
        $transaction->load(['processedBy', 'customer', 'product']);

        return view('staff.pos.show', compact('transaction'));
    }

    public function receipt(Request $request): View
    {
        $codes = array_filter(explode(',', (string) $request->query('codes')));

        abort_if(empty($codes), 404);

        $transactions = SalesTransaction::with(['processedBy', 'customer', 'product'])
            ->whereIn('transaction_code', $codes)
            ->orderBy('id')
            ->get();

        abort_if($transactions->isEmpty(), 404);

        $business = Setting::current();

        return view('staff.pos.receipt', [
            'transactions' => $transactions,
            'business' => $business,
            'receiptNumber' => $transactions->first()->transaction_code,
            'total' => $transactions->sum('total_amount'),
        ]);
    }
}
