@php
    $purchaseOrderFormData = [
        'supplier_id' => old('supplier_id', $selectedSupplierId),
        'products' => $products->map(fn ($product) => ['id' => $product->id, 'label' => $product->name.' ('.$product->size.')'])->values(),
        'consumables' => $consumables->map(fn ($supply) => ['id' => $supply->id, 'label' => $supply->name.' ('.$supply->unit.')', 'supplier_id' => $supply->supplier_id, 'unit_cost' => (float) $supply->unit_cost])->values(),
        'items' => old('items', [[
            'itemable_type' => 'consumable', 'itemable_id' => $selectedSupply?->id ?? '',
            'quantity_ordered' => 1, 'unit_cost' => (float) ($selectedSupply?->unit_cost ?? 0),
        ]]),
    ];
@endphp

<x-layouts.app title="New Purchase Order" heading="New Purchase Order">
    <div x-data="purchaseOrderForm($el.querySelector('[data-purchase-order-data]').textContent)" class="mx-auto max-w-7xl">
        <script type="application/json" data-purchase-order-data>@json($purchaseOrderFormData)</script>
        @include('admin.purchase-orders.form', ['editing' => false])
    </div>
</x-layouts.app>
