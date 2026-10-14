<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\PurchaseOrder;

class PurchaseOrderExpenseService
{
    public function sync(PurchaseOrder $order): void
    {
        if (in_array($order->status, ['draft', 'cancelled'], true)) {
            Expense::where('purchase_order_id', $order->id)->delete();
            return;
        }

        $amount = $order->items()->selectRaw('COALESCE(SUM(quantity_ordered * unit_cost), 0) as total')->value('total');
        Expense::updateOrCreate(['purchase_order_id' => $order->id], [
            'expense_date' => $order->ordered_at ?? $order->created_at->toDateString(),
            'category' => 'Supplies & Materials',
            'description' => 'Purchase order '.$order->po_number,
            'amount' => $amount,
            'reference' => $order->po_number,
            'notes' => 'Automatically recorded from Procurement. Based on ordered quantities and unit costs.',
            'recorded_by' => $order->created_by,
        ]);
    }
}
