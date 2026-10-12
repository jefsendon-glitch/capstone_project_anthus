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
    <div x-data="purchaseOrderForm($el.querySelector('[data-purchase-order-data]').textContent)" class="mx-auto max-w-5xl">
        <script type="application/json" data-purchase-order-data>@json($purchaseOrderFormData)</script>
        <p class="mb-5 text-sm text-slate-500">Select a supplier, add supplies or containers, and review the order cost.</p>
        @if($suppliers->isEmpty())
            <x-card class="mb-5"><p class="mb-3 text-sm">Add an active supplier before creating a purchase order.</p><x-button as="a" :href="route('admin.suppliers.create')">Add Supplier</x-button></x-card>
        @endif
        <form method="POST" action="{{ route('admin.purchase-orders.store') }}" class="space-y-5">
            @csrf
            @if($errors->any())
                <div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
            @endif
            <x-card>
                <h2 class="mb-4 text-base font-semibold">Order Details</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div><x-input-label for="supplier_id" value="Supplier" /><x-select id="supplier_id" name="supplier_id" x-model="supplierId" x-on:change="supplierChanged()" required><option value="">Select supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(old('supplier_id', $selectedSupplierId) == $supplier->id)>{{ $supplier->name }}</option>@endforeach</x-select></div>
                    <div><x-input-label for="status" value="Order status" /><x-select id="status" name="status"><option value="ordered" @selected(old('status', 'ordered') === 'ordered')>Place order</option><option value="draft" @selected(old('status') === 'draft')>Save as draft</option></x-select></div>
                    <div><x-input-label for="ordered_at" value="Order date" /><x-text-input id="ordered_at" name="ordered_at" type="date" :value="old('ordered_at', now()->toDateString())" required /></div>
                    <div><x-input-label for="expected_date" value="Expected delivery (optional)" /><x-text-input id="expected_date" name="expected_date" type="date" :value="old('expected_date')" /></div>
                </div>
            </x-card>
            <x-card>
                <h2 class="mb-1 text-base font-semibold">Items to Purchase</h2>
                <p class="mb-4 text-xs text-slate-500">Supplies are filtered by supplier. Containers are ordered in whole pieces.</p>
                <div class="space-y-4">
                    <template x-for="(item, index) in items" :key="item._key">
                        <div class="rounded-xl border border-slate-200 p-4 dark:border-white/10">
                            <div class="mb-3 flex items-center justify-between"><span class="text-xs font-semibold text-slate-500" x-text="'Item ' + (index + 1)"></span><button type="button" x-on:click="removeItem(index)" x-bind:disabled="items.length === 1" class="text-xs text-red-600 disabled:opacity-40">Remove</button></div>
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-12">
                                <div class="lg:col-span-2"><label class="mb-1 block text-xs text-slate-500">Type</label><x-select x-model="item.itemable_type" x-on:change="item.itemable_id = ''; item.unit_cost = 0; item.quantity_ordered = 1" x-bind:name="'items['+index+'][itemable_type]'"><option value="consumable">Supply</option><option value="product">Container</option></x-select></div>
                                <div class="lg:col-span-4"><label class="mb-1 block text-xs text-slate-500">Item</label><x-select x-model="item.itemable_id" x-on:change="item.itemable_id = $event.target.value; if (selectedItem(item)) chooseItem(item, selectedItem(item))" x-bind:name="'items['+index+'][itemable_id]'" required><option value="">Select item</option><template x-for="option in optionsFor(item)" :key="option.id"><option x-bind:value="option.id" x-bind:selected="String(option.id) === String(item.itemable_id)" x-text="option.label"></option></template></x-select><p x-show="optionsFor(item).length === 0" class="mt-1 text-xs text-amber-600" x-text="!supplierId && item.itemable_type === 'consumable' ? 'Select a supplier first.' : 'No available items. Add a supply or container before ordering.'"></p></div>
                                <div class="lg:col-span-2"><label class="mb-1 block text-xs text-slate-500">Quantity</label><x-text-input type="number" x-model="item.quantity_ordered" x-bind:step="item.itemable_type === 'product' ? 1 : 0.01" x-bind:min="item.itemable_type === 'product' ? 1 : 0.01" x-bind:name="'items['+index+'][quantity_ordered]'" required /></div>
                                <div class="lg:col-span-2"><label class="mb-1 block text-xs text-slate-500">Unit cost (PHP)</label><x-text-input type="number" step="0.01" min="0" max="99999999.99" x-model="item.unit_cost" x-bind:name="'items['+index+'][unit_cost]'" required /></div>
                                <div class="lg:col-span-2"><label class="mb-1 block text-xs text-slate-500">Amount</label><p class="py-2.5 text-sm font-semibold" x-text="money(lineTotal(item))"></p></div>
                            </div>
                        </div>
                    </template>
                </div>
                <button type="button" x-on:click="addItem(); items[items.length - 1].itemable_type = 'consumable'" class="mt-4 text-sm font-semibold text-primary-600">+ Add item</button>
                <div class="mt-5 flex justify-end gap-5 border-t border-slate-200 pt-4 dark:border-white/10"><span class="text-sm text-slate-500">Total order cost</span><strong x-text="money(total)"></strong></div>
            </x-card>
            <x-card><x-input-label for="notes" value="Notes (optional)" /><x-textarea id="notes" name="notes" maxlength="1000">{{ old('notes') }}</x-textarea><p class="mt-4 text-xs text-slate-500">Inventory updates when delivered quantities are recorded in Stock Receiving.</p></x-card>
            <div class="flex justify-end gap-3"><x-button as="a" :href="route('admin.purchase-orders.index')" variant="secondary">Cancel</x-button><x-button type="submit" :disabled="$suppliers->isEmpty()">Create Purchase Order</x-button></div>
        </form>
    </div>
</x-layouts.app>
