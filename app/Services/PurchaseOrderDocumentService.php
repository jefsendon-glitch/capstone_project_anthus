<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class PurchaseOrderDocumentService
{
    public function snapshot(PurchaseOrder $order): array
    {
        $order->load(['supplier', 'items.itemable']);

        return [
            'station' => Setting::current()->only(['business_name', 'address', 'contact_number', 'contact_email']),
            'number' => $order->po_number,
            'status' => $order->status,
            'ordered_at' => $order->ordered_at?->format('F d, Y'),
            'expected_date' => $order->expected_date?->format('F d, Y'),
            'notes' => $order->notes,
            'supplier' => $order->supplier->only(['name', 'contact_person', 'email', 'phone', 'address']),
            'items' => $order->items->map(fn ($item) => [
                'name' => $item->itemable?->name ?? 'Unavailable item',
                'quantity' => (float) $item->quantity_ordered,
                'unit_cost' => (float) $item->unit_cost,
                'amount' => round((float) $item->quantity_ordered * (float) $item->unit_cost, 2),
            ])->all(),
            'total' => round($order->items->sum(fn ($item) => (float) $item->quantity_ordered * (float) $item->unit_cost), 2),
        ];
    }

    public function fingerprint(array $snapshot): string
    {
        return hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));
    }

    public function pdf(array $snapshot): string
    {
        return Pdf::loadView('admin.purchase-orders.pdf', ['order' => $snapshot])
            ->setPaper('a4')->setOption(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false])
            ->output();
    }

    public function filename(string $number): string
    {
        return 'Purchase-Order-'.Str::slug($number).'.pdf';
    }
}
