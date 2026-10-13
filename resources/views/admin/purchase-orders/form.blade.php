<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-slate-500 dark:text-slate-400">Order supplies and containers for station operations.</p>
    <x-button as="a" :href="route('admin.purchase-orders.index')" variant="secondary"><x-icon name="chevron-left" class="size-4" /> Purchase Orders</x-button>
</div>
@if($suppliers->isEmpty())
    <x-card class="mb-5"><p class="mb-3 text-sm">Add an active supplier before creating a purchase order.</p><x-button as="a" :href="route('admin.suppliers.create')">Add Supplier</x-button></x-card>
@endif
<form method="POST" action="{{ $editing ? route('admin.purchase-orders.update', $purchaseOrder) : route('admin.purchase-orders.store') }}">
    @csrf
    @if($editing) @method('put') @endif
    @if($errors->any())
        <div role="alert" class="mb-5 rounded-xl bg-danger-50 p-4 text-sm text-danger-700 dark:bg-danger-500/10 dark:text-danger-300">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_300px]">
        <div class="min-w-0 space-y-5">
            <x-card>
                <div class="mb-5 flex items-center gap-3"><span class="flex size-9 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-500/10"><x-icon name="building" class="size-5" /></span><div><h2 class="font-heading font-bold text-slate-900 dark:text-white">Order Details</h2><p class="text-xs text-slate-500">Choose a supplier and set your delivery schedule.</p></div></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><x-input-label for="supplier_id" value="Supplier" /><x-select id="supplier_id" name="supplier_id" x-model="supplierId" x-on:change="supplierChanged()" required><option value="">Select supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(old('supplier_id', $purchaseOrderFormData['supplier_id']) == $supplier->id)>{{ $supplier->name }}</option>@endforeach</x-select></div>
                    <div><x-input-label for="status" value="Order Status" /><x-select id="status" name="status" required><option value="ordered" @selected(old('status', $editing ? $purchaseOrder->status : 'ordered') === 'ordered')>Place order</option><option value="draft" @selected(old('status', $editing ? $purchaseOrder->status : 'ordered') === 'draft')>Save as draft</option>@if($editing)<option value="cancelled" @selected(old('status', $purchaseOrder->status) === 'cancelled')>Cancelled</option>@endif</x-select></div>
                    <div><x-input-label for="ordered_at" value="Order Date" /><x-text-input id="ordered_at" name="ordered_at" type="date" :value="old('ordered_at', $editing ? $purchaseOrder->ordered_at?->format('Y-m-d') : now()->toDateString())" required /></div>
                    <div><x-input-label for="expected_date" value="Expected Delivery (optional)" /><x-text-input id="expected_date" name="expected_date" type="date" :value="old('expected_date', $editing ? $purchaseOrder->expected_date?->format('Y-m-d') : null)" /></div>
                </div>
            </x-card>
            <x-card>
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3"><div><h2 class="font-heading font-bold text-slate-900 dark:text-white">Items to Purchase</h2><p class="mt-1 text-xs text-slate-500">Add supplies or containers, then enter quantity and unit cost.</p></div><x-button type="button" variant="secondary" x-on:click="addItem(); items[items.length - 1].itemable_type = 'consumable'"><x-icon name="plus" class="size-4" /> Add Item</x-button></div>
                <div class="space-y-3">
                    <template x-for="(item, index) in items" :key="item._key">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="mb-3 flex items-center justify-between"><span class="text-xs font-bold uppercase tracking-wide text-slate-500" x-text="'Item ' + (index + 1)"></span><button type="button" x-on:click="removeItem(index)" x-bind:disabled="items.length === 1" class="flex items-center gap-1 text-xs text-danger-600 disabled:opacity-30 dark:text-danger-400" aria-label="Remove item"><x-icon name="trash" class="size-4" /> Remove</button></div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div><label class="mb-1 block text-xs font-medium text-slate-500">Item Type</label><x-select x-model="item.itemable_type" x-on:change="item.itemable_id = ''; item.unit_cost = 0; item.quantity_ordered = 1" x-bind:name="'items['+index+'][itemable_type]'"><option value="consumable">Supply</option><option value="product">Container</option></x-select></div>
                                <div><label class="mb-1 block text-xs font-medium text-slate-500">Supply / Container</label><x-select x-model="item.itemable_id" x-on:change="item.itemable_id = $event.target.value; if (selectedItem(item)) chooseItem(item, selectedItem(item))" x-bind:name="'items['+index+'][itemable_id]'" required><option value="">Select item</option><template x-for="option in optionsFor(item)" :key="option.id"><option x-bind:value="option.id" x-bind:selected="String(option.id) === String(item.itemable_id)" x-text="option.label"></option></template></x-select><p x-show="optionsFor(item).length === 0" class="mt-1 text-xs text-warning-600" x-text="!supplierId && item.itemable_type === 'consumable' ? 'Select a supplier to see supplies.' : 'No available items for this supplier.'"></p></div>
                                <div><label class="mb-1 block text-xs font-medium text-slate-500">Quantity</label><x-text-input type="number" x-model="item.quantity_ordered" x-bind:step="item.itemable_type === 'product' ? 1 : 0.01" x-bind:min="item.itemable_type === 'product' ? 1 : 0.01" x-bind:name="'items['+index+'][quantity_ordered]'" required /></div>
                                <div><label class="mb-1 block text-xs font-medium text-slate-500">Unit Cost (PHP)</label><x-text-input type="number" step="0.01" min="0" max="99999999.99" x-model="item.unit_cost" x-bind:name="'items['+index+'][unit_cost]'" required /></div>
                            </div>
                            <div class="mt-3 flex items-center justify-between border-t border-slate-200 pt-3 dark:border-white/10"><span class="text-xs text-slate-500">Item Amount</span><span class="font-mono text-sm font-semibold text-slate-900 dark:text-white" x-text="money(lineTotal(item))"></span></div>
                        </div>
                    </template>
                </div>
            </x-card>
            <x-card><x-input-label for="notes" value="Notes (optional)" /><x-textarea id="notes" name="notes" maxlength="1000" placeholder="Delivery instructions or additional order details">{{ old('notes', $editing ? $purchaseOrder->notes : null) }}</x-textarea></x-card>
        </div>
        <x-card class="xl:sticky xl:top-6">
            <h2 class="font-heading font-bold text-slate-900 dark:text-white">Order Summary</h2>
            <div class="mt-5 flex justify-between text-sm"><span class="text-slate-500">Line Items</span><span class="font-semibold" x-text="items.length"></span></div>
            <div class="my-5 rounded-2xl bg-primary-50 p-4 dark:bg-primary-500/10"><p class="text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-300">Total Order Cost</p><p class="mt-2 break-words text-2xl font-extrabold text-primary-700 dark:text-primary-300" x-text="money(total)"></p></div>
            <p class="mb-5 text-xs leading-relaxed text-slate-500">Stock quantities update when you record the delivery in Stock Receiving.</p>
            <div class="space-y-2"><x-button type="submit" class="w-full" :disabled="$suppliers->isEmpty()"><x-icon name="check-circle" class="size-4" /> {{ $editing ? 'Save Changes' : 'Create Purchase Order' }}</x-button><x-button as="a" :href="$editing ? route('admin.purchase-orders.show', $purchaseOrder) : route('admin.purchase-orders.index')" variant="secondary" class="w-full">Cancel</x-button></div>
        </x-card>
    </div>
</form>
