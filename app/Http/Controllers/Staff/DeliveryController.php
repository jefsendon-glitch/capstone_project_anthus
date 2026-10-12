<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\FulfillDeliveryRequest;
use App\Http\Requests\UpdateDeliveryStatusRequest;
use App\Models\DeliveryOrder;
use App\Notifications\OrderStatusUpdated;
use App\Services\DeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function __construct(private readonly DeliveryService $delivery)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('manage', DeliveryOrder::class);

        $ordersQuery = DeliveryOrder::with(['customer', 'items']);

        $deliveryStats = [
            'pending' => (clone $ordersQuery)->where('status', 'pending')->count(),
            'confirmed' => (clone $ordersQuery)->where('status', 'confirmed')->count(),
            'outForDelivery' => (clone $ordersQuery)->where('status', 'out_for_delivery')->count(),
            'today' => (clone $ordersQuery)->whereDate('preferred_delivery_date', today())->count(),
        ];

        $orders = $ordersQuery
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($query) use ($search) {
                    $query->where('order_code', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('product_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('preferred_delivery_date')
            ->paginate(15)
            ->withQueryString();

        return view('staff.deliveries.index', compact('orders', 'deliveryStats'));
    }

    public function show(DeliveryOrder $delivery): View
    {
        $this->authorize('manage', DeliveryOrder::class);

        $delivery->load(['customer', 'items.product', 'placedBy', 'updatedBy']);

        return view('staff.deliveries.show', ['order' => $delivery]);
    }

    public function reset(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $request->validate(['reset_confirmation' => ['required', 'in:RESET']]);

        DB::transaction(function () use ($request) {
            $orders = DeliveryOrder::query()->orderBy('id')->lockForUpdate()->get(['id']);
            DeliveryOrder::query()->delete();
            activity('delivery_orders')->causedBy($request->user())->event('reset')
                ->withProperties(['orders_deleted' => $orders->count()])
                ->log('Reset all delivery orders');
        });

        return redirect()->route('deliveries.index')
            ->with('success', 'Delivery orders reset successfully. Sales, payments, and inventory preserved.');
    }

    public function updateStatus(UpdateDeliveryStatusRequest $request, DeliveryOrder $delivery): RedirectResponse
    {
        $this->delivery->updateStatus($delivery, $request->validated('status'), $request->user());

        $delivery->customer?->notify(new OrderStatusUpdated($delivery));

        return back()->with('success', 'Delivery status updated.');
    }

    public function fulfill(FulfillDeliveryRequest $request, DeliveryOrder $delivery): RedirectResponse
    {
        $this->delivery->fulfill($delivery, $request->validated('payment_method'), $request->user());

        return redirect()->route('deliveries.show', $delivery)->with('success', 'Delivery fulfilled and sale recorded.');
    }
}
