<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreOrderRequest;
use App\Models\DeliveryOrder;
use App\Models\Product;
use App\Services\DeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly DeliveryService $delivery)
    {
    }

    public function index(Request $request): View
    {
        $orders = $request->user()->deliveryOrders()->with('items')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('customer.orders.index', compact('orders'));
    }

    public function create(): View
    {
        $products = Product::active()->where('stock_quantity', '>', 0)->orderBy('name')->get();

        $productsForOrder = $products->map(fn (Product $product) => [
            'id' => $product->id,
            'name' => $product->name,
            'size' => $product->size,
            'unit_price' => (float) $product->unit_price,
            'stock_quantity' => $product->stock_quantity,
            'stock_unit_label' => $product->stock_unit_label,
            'image_url' => $product->image_url,
        ])->values();

        return view('customer.orders.create', ['products' => $products, 'productsForOrder' => $productsForOrder]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $order = $this->delivery->placeOrder($request->validated(), $request->user());

        return redirect()->route('customer.orders.show', $order)->with('success', 'Order placed successfully.');
    }

    public function show(DeliveryOrder $order): View
    {
        $this->authorize('view', $order);

        $order->load('items.product');

        return view('customer.orders.show', compact('order'));
    }

    public function cancel(DeliveryOrder $order): RedirectResponse
    {
        $this->authorize('cancel', $order);

        $this->delivery->cancel($order, request()->user());

        return redirect()->route('customer.orders.show', $order)->with('success', 'Order cancelled.');
    }
}
