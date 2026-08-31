<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReceivePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderReceivingService;
use Illuminate\Http\RedirectResponse;

class PurchaseOrderReceiptController extends Controller
{
    public function __construct(private readonly PurchaseOrderReceivingService $receiving)
    {
    }

    public function store(ReceivePurchaseOrderRequest $request, PurchaseOrder $purchase_order): RedirectResponse
    {
        if (! in_array($purchase_order->status, ['ordered', 'partially_received'])) {
            return back()->with('error', 'This purchase order cannot receive stock in its current status.');
        }

        $this->receiving->receive($purchase_order, $request->validated('items'), $request->user());

        return redirect()->route('admin.purchase-orders.show', $purchase_order)->with('success', 'Stock received successfully.');
    }
}
