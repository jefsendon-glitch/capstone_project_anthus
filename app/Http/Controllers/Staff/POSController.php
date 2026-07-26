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
use Illuminate\View\View;

class POSController extends Controller
{
    public function __construct(private readonly SalesService $sales)
    {
    }

    public function index(Request $request): View
    {
        $transactions = SalesTransaction::with(['processedBy', 'customer'])
            ->when($request->filled('type'), fn ($query) => $query->where('transaction_type', $request->string('type')))
            ->latest()
            ->paginate(15)
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
