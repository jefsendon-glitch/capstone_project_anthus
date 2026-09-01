@php
    $purchaseOrderFormData = [
        'products' => $products->map(fn ($product) => [
            'id' => $product->id,
            'label' => $product->name.' ('.$product->size.')',
            'image_url' => $product->image_url,
        ])->values(),
        'consumables' => $consumables->map(fn ($consumable) => [
            'id' => $consumable->id,
            'label' => $consumable->name,
        ])->values(),
        'items' => [[
            'itemable_type' => 'product',
            'itemable_id' => '',
            'quantity_ordered' => 1,
            'unit_cost' => 0,
        ]],
    ];
@endphp

<x-layouts.app title="New Purchase Order" :heading="'New Purchase Order'">
    <div x-data="purchaseOrderForm($el.querySelector('[data-purchase-order-data]').textContent)" class="mx-auto max-w-3xl">
        <script type="application/json" data-purchase-order-data>@json($purchaseOrderFormData)</script>
        <x-card>
            <form method="POST" action="{{ route('admin.purchase-orders.store') }}" class="space-y-6">
                @csrf

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="supplier_id" value="Supplier" />
                        <p class="mt-1 text-xs text-slate-500">Select the supplier that will provide these items.</p>
                        <x-select id="supplier_id" name="supplier_id" required>
                            <option value="">Select supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </x-select>
                        <x-input-error :messages="$errors->get('supplier_id')" />
                    </div>
                    <div>
                        <x-input-label for="expected_date" value="Date (optional)" />
                        <p class="mt-1 text-xs text-slate-500">Record the relevant purchase-order date.</p>
                        <x-text-input id="expected_date" type="text" name="expected_date" data-flatpickr :value="old('expected_date')" />
                        <x-input-error :messages="$errors->get('expected_date')" />
                    </div>
                </div>

                <div>
                    <x-input-label value="Items to purchase" />
                    <p class="mt-1 text-xs text-slate-500">Products increase product inventory. Supplies increase consumable inventory; container products also update gallon inventory.</p>
                    <div class="mt-2 space-y-3">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="grid gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4 shadow-sm dark:border-white/10 dark:bg-white/5 sm:grid-cols-12">
                                <div class="sm:col-span-3">
                                    <label class="mb-1 block text-xs font-medium text-slate-500">Item type</label>
                                    <x-select x-model="item.itemable_type" x-on:change="item.itemable_id = ''" x-bind:name="'items['+index+'][itemable_type]'">
                                        <option value="product">Product</option>
                                        <option value="consumable">Supply</option>
                                    </x-select>
                                </div>
                                <div class="sm:col-span-4">
                                    <label class="mb-1 block text-xs font-medium text-slate-500">Product or supply</label>
                                    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                                        <input type="hidden" x-bind:name="'items['+index+'][itemable_id]'" x-bind:value="item.itemable_id">
                                        <button type="button" x-on:click="open = !open" x-bind:aria-expanded="open" class="flex w-full items-center gap-3 rounded-xl bg-white/90 px-3.5 py-2.5 text-left text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 transition hover:ring-slate-300 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 dark:bg-slate-800/90 dark:text-white dark:ring-white/10">
                                            <template x-if="selectedItem(item)">
                                                <span class="flex min-w-0 items-center gap-3">
                                                    <template x-if="item.itemable_type === 'product' && selectedItem(item).image_url">
                                                        <img :src="selectedItem(item).image_url" :alt="selectedItem(item).label" class="size-9 shrink-0 rounded-lg object-cover">
                                                    </template>
                                                    <template x-if="item.itemable_type !== 'product' || !selectedItem(item).image_url">
                                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-500 dark:bg-primary-500/10"><x-icon name="photo" class="size-4" /></span>
                                                    </template>
                                                    <span class="truncate" x-text="selectedItem(item).label"></span>
                                                </span>
                                            </template>
                                            <template x-if="!selectedItem(item)">
                                                <span class="text-slate-400" x-text="item.itemable_type === 'product' ? 'Select product' : 'Select supply'"></span>
                                            </template>
                                            <x-icon name="chevron-down" class="ml-auto size-4 shrink-0 text-slate-400" />
                                        </button>
                                        <div x-cloak x-show="open" x-transition class="absolute z-20 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl dark:border-white/10 dark:bg-slate-800">
                                            <template x-for="opt in optionsFor(item)" :key="opt.id">
                                                <button type="button" x-on:click="item.itemable_id = opt.id; open = false" class="flex w-full items-center gap-3 rounded-lg px-2.5 py-2 text-left text-sm text-slate-700 transition hover:bg-primary-50 hover:text-primary-800 dark:text-slate-200 dark:hover:bg-primary-500/10 dark:hover:text-primary-200">
                                                    <template x-if="item.itemable_type === 'product' && opt.image_url">
                                                        <img :src="opt.image_url" :alt="opt.label" class="size-10 shrink-0 rounded-lg object-cover">
                                                    </template>
                                                    <template x-if="item.itemable_type !== 'product' || !opt.image_url">
                                                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400 dark:bg-slate-700"><x-icon name="photo" class="size-4" /></span>
                                                    </template>
                                                    <span class="min-w-0 truncate" x-text="opt.label"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="mb-1 block text-xs font-medium text-slate-500">Quantity</label>
                                    <x-text-input type="number" step="0.01" min="0.01" placeholder="Qty" x-model="item.quantity_ordered" x-bind:name="'items['+index+'][quantity_ordered]'" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="mb-1 block text-xs font-medium text-slate-500">Unit cost</label>
                                    <x-text-input type="number" step="0.01" min="0" placeholder="Unit cost" x-model="item.unit_cost" x-bind:name="'items['+index+'][unit_cost]'" />
                                </div>
                                <div class="flex justify-end sm:col-span-1 sm:justify-center sm:pt-2.5">
                                    <button type="button" x-on:click="removeItem(index)" class="rounded-lg p-2 text-slate-400 transition hover:bg-danger-50 hover:text-danger-600 dark:hover:bg-danger-500/10" aria-label="Remove item">
                                        <x-icon name="trash" class="size-4" />
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <button type="button" x-on:click="addItem()" class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-primary-50 px-3 py-2 text-sm font-semibold text-primary-700 transition hover:bg-primary-100 dark:bg-primary-500/10 dark:text-primary-300 dark:hover:bg-primary-500/20">
                        <x-icon name="plus" class="size-4" /> Add item
                    </button>
                    <x-input-error :messages="$errors->get('items.*.itemable_id')" />
                    <x-input-error :messages="$errors->get('items')" />
                </div>

                <div>
                    <x-input-label for="notes" value="Notes (optional)" />
                    <x-textarea id="notes" name="notes">{{ old('notes') }}</x-textarea>
                    <x-input-error :messages="$errors->get('notes')" />
                </div>

                <div class="flex items-start gap-3 rounded-2xl border border-primary-100 bg-primary-50/60 p-4 text-sm dark:border-primary-500/20 dark:bg-primary-500/10">
                    <x-badge color="success"><x-icon name="check-circle" class="size-4" /> Inventory received automatically</x-badge>
                    <span class="pt-0.5 text-xs text-primary-700 dark:text-primary-300">Every new purchase order immediately updates product and supply stock. Container products also update gallon inventory.</span>
                </div>

                <div class="flex justify-end gap-3">
                    <x-button as="a" href="{{ route('admin.purchase-orders.index') }}" variant="secondary">Cancel</x-button>
                    <x-button type="submit">Create Purchase Order</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
