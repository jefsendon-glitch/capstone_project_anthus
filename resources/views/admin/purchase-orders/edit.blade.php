@php
    $initialItems = $purchaseOrder->items->map(fn ($i) => [
        'itemable_type' => $i->itemable_type === \App\Models\Product::class ? 'product' : 'consumable',
        'itemable_id' => $i->itemable_id,
        'quantity_ordered' => (float) $i->quantity_ordered,
        'unit_cost' => (float) $i->unit_cost,
    ])->values();

    $purchaseOrderFormData = [
        'supplier_id' => old('supplier_id', $purchaseOrder->supplier_id),
        'products' => $products->map(fn ($product) => [
            'id' => $product->id,
            'label' => $product->name.' ('.$product->size.')',
        ])->values(),
        'consumables' => $consumables->map(fn ($consumable) => [
            'id' => $consumable->id,
            'label' => $consumable->name,
            'supplier_id' => $consumable->supplier_id,
            'unit_cost' => (float) $consumable->unit_cost,
        ])->values(),
        'items' => old('items', $initialItems),
    ];
@endphp

<x-layouts.app title="Edit Purchase Order" :heading="'Edit '.$purchaseOrder->po_number">
    <div x-data="purchaseOrderForm($el.querySelector('[data-purchase-order-data]').textContent)" class="mx-auto max-w-7xl">
        <script type="application/json" data-purchase-order-data>@json($purchaseOrderFormData)</script>
        @include('admin.purchase-orders.form', ['editing' => true])
    </div>
</x-layouts.app>
