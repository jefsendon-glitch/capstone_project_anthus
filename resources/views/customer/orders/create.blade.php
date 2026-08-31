<<<<<<< ours
<x-layouts.app title="Place an Order" :heading="'Place an Order'" :subheading="'Add one or more products, then enter your delivery details.'">
    <x-card class="mx-auto max-w-3xl">
        <form method="POST" action="{{ route('customer.orders.store') }}" class="space-y-6" x-data='orderForm(@json($productsForOrder), @json(old("items", [])))'>
            @csrf
            <section>
                <div class="flex items-start justify-between gap-4"><div><h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Products to order</h2><p class="mt-1 text-xs text-slate-500">Choose each product and quantity. You can add more than one product.</p></div><x-button type="button" x-on:click="addItem()" variant="secondary"><x-icon name="plus" class="size-4" /> Add product</x-button></div>
                <x-input-error class="mt-2" :messages="$errors->get('items')" />
                <div class="mt-4 space-y-3"><template x-for="(item, index) in items" :key="item.key"><div class="grid gap-3 rounded-2xl bg-slate-50 p-4 sm:grid-cols-[minmax(0,1fr)_9rem_auto] dark:bg-white/5"><div><label class="text-sm font-medium text-slate-700 dark:text-slate-200" :for="'product-' + item.key">Product</label><select :id="'product-' + item.key" :name="'items[' + index + '][product_id]'" x-model="item.product_id" required class="mt-1 block w-full rounded-xl border-0 py-2.5 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-primary-600 dark:bg-slate-800 dark:text-white dark:ring-white/10"><option value="">Select product</option><template x-for="product in products" :key="product.id"><option :value="product.id" x-text="product.name + ' (' + product.size + ') — ₱' + Number(product.unit_price).toFixed(2)"></option></template></select><p class="mt-1 text-xs text-slate-400" x-show="productFor(item)"><span x-text="productFor(item)?.stock_quantity"></span> <span x-text="productFor(item)?.stock_unit_label"></span> available</p></div><div><label class="text-sm font-medium text-slate-700 dark:text-slate-200" :for="'quantity-' + item.key">Quantity</label><input :id="'quantity-' + item.key" type="number" min="1" :max="productFor(item)?.stock_quantity" :name="'items[' + index + '][quantity]'" x-model.number="item.quantity" required class="mt-1 block w-full rounded-xl border-0 py-2.5 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-primary-600 dark:bg-slate-800 dark:text-white dark:ring-white/10"><p class="mt-1 font-mono text-xs text-primary-600" x-show="productFor(item)" x-text="'₱' + itemTotal(item).toFixed(2)"></p></div><div class="flex items-end"><button type="button" x-on:click="removeItem(index)" x-bind:disabled="items.length === 1" class="rounded-xl p-2.5 text-slate-400 hover:bg-danger-50 hover:text-danger-600 disabled:cursor-not-allowed disabled:opacity-40" aria-label="Remove product"><x-icon name="trash" class="size-5" /></button></div></div></template></div>
                <div class="mt-4 flex items-center justify-between rounded-xl bg-primary-50 px-4 py-3 text-sm dark:bg-primary-500/10"><span class="font-medium text-slate-600 dark:text-slate-300">Estimated total</span><span class="font-mono text-base font-bold text-primary-600" x-text="'₱' + estimatedTotal.toFixed(2)"></span></div>
            </section>
            <section class="grid gap-5 sm:grid-cols-2"><div class="sm:col-span-2"><x-input-label for="customer_address" value="Delivery address" /><p class="mt-1 text-xs text-slate-500">Enter the complete address where the order should be delivered.</p><x-textarea id="customer_address" name="customer_address" class="mt-2" required>{{ old('customer_address', auth()->user()->address) }}</x-textarea><x-input-error :messages="$errors->get('customer_address')" /></div><div><x-input-label for="preferred_delivery_date" value="Preferred delivery date (optional)" /><p class="mt-1 text-xs text-slate-500">Choose the date you would prefer us to deliver.</p><x-text-input id="preferred_delivery_date" class="mt-2" type="date" name="preferred_delivery_date" :value="old('preferred_delivery_date')" data-flatpickr /><x-input-error :messages="$errors->get('preferred_delivery_date')" /></div><div><x-input-label for="notes" value="Order notes (optional)" /><p class="mt-1 text-xs text-slate-500">Add delivery instructions or other helpful details.</p><x-textarea id="notes" class="mt-2" name="notes">{{ old('notes') }}</x-textarea></div></section>
            <div class="flex justify-end gap-3"><x-button as="a" href="{{ route('customer.orders.index') }}" variant="secondary">Cancel</x-button><x-button type="submit" x-bind:disabled="!isValid">Place Order</x-button></div>
        </form>
    </x-card>
    @push('scripts')<script>function orderForm(products, oldItems) { return { products, items: oldItems.length ? oldItems.map((item, index) => ({ key: Date.now() + index, product_id: String(item.product_id || ''), quantity: Number(item.quantity || 1) })) : [{ key: Date.now(), product_id: '', quantity: 1 }], addItem() { this.items.push({ key: Date.now() + this.items.length, product_id: '', quantity: 1 }); }, removeItem(index) { if (this.items.length > 1) this.items.splice(index, 1); }, productFor(item) { return this.products.find(product => String(product.id) === String(item.product_id)); }, itemTotal(item) { return (this.productFor(item)?.unit_price || 0) * (item.quantity || 0); }, get estimatedTotal() { return this.items.reduce((total, item) => total + this.itemTotal(item), 0); }, get isValid() { return this.items.length > 0 && this.items.every(item => { const product = this.productFor(item); return product && item.quantity >= 1 && item.quantity <= product.stock_quantity; }); } }; }</script>@endpush
=======
<x-layouts.app title="Place an Order" :heading="'Place an Order'">
    <x-card class="mx-auto max-w-xl">
        @if(auth()->user()->credit_balance > 0)
            <div class="mb-5 rounded-xl bg-warning-50 px-4 py-3 text-sm text-warning-700 dark:bg-warning-500/10 dark:text-warning-500">
                You have an outstanding balance of <span class="font-mono font-semibold">₱{{ number_format(auth()->user()->credit_balance, 2) }}</span>.
            </div>
        @endif

        <form method="POST" action="{{ route('customer.orders.store') }}" class="space-y-5" x-data='orderForm(@json($productsForOrder))'>
            @csrf

            <div>
                <x-input-label for="product_id" value="Product" />
                <select id="product_id" name="product_id" x-model="productId" required class="block w-full rounded-xl border-0 py-2.5 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-slate-800 dark:text-white dark:ring-white/10">
                    <option value="">Select product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                            {{ $product->name }} ({{ $product->size }}) — ₱{{ number_format($product->unit_price, 2) }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('product_id')" />
                <p class="mt-1.5 text-xs text-slate-400" x-show="selectedProduct" x-cloak>
                    <span x-text="selectedProduct?.stock_quantity"></span> <span x-text="selectedProduct?.stock_unit_label"></span> currently in stock
                </p>
                <div x-show="selectedProduct" x-cloak class="mt-3 flex items-center gap-3 rounded-2xl bg-slate-50 p-3 dark:bg-white/5">
                    <template x-if="selectedProduct?.image_url"><img :src="selectedProduct.image_url" :alt="selectedProduct.name" class="size-14 rounded-xl object-cover"></template>
                    <template x-if="!selectedProduct?.image_url"><span class="flex size-14 items-center justify-center rounded-xl bg-primary-50 text-primary-500 dark:bg-primary-500/10"><x-icon name="photo" class="size-6" /></span></template>
                    <div><p class="text-sm font-semibold text-slate-900 dark:text-white" x-text="selectedProduct?.name + ' (' + selectedProduct?.size + ')'"></p><p class="font-mono text-xs text-primary-600" x-text="'₱' + Number(selectedProduct?.unit_price || 0).toFixed(2)"></p></div>
                </div>
            </div>

            <div>
                <x-input-label for="quantity" value="Quantity" />
                <x-text-input id="quantity" type="number" min="1" name="quantity" x-model.number="quantity" x-bind:max="selectedProduct?.stock_quantity" :value="old('quantity', 1)" required />
                <x-input-error :messages="$errors->get('quantity')" />
            </div>

            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 text-sm dark:bg-white/5" x-show="selectedProduct" x-cloak>
                <span class="font-medium text-slate-500 dark:text-slate-400">Estimated Total</span>
                <span class="font-mono text-base font-bold text-primary-600" x-text="'₱' + estimatedTotal.toFixed(2)"></span>
            </div>

            <div>
                <x-input-label for="customer_address" value="Delivery address" />
                <x-textarea id="customer_address" name="customer_address" required>{{ old('customer_address', auth()->user()->address) }}</x-textarea>
                <x-input-error :messages="$errors->get('customer_address')" />
            </div>

            <div>
                <x-input-label for="preferred_delivery_date" value="Preferred delivery date (optional)" />
                <x-text-input id="preferred_delivery_date" type="date" name="preferred_delivery_date" :value="old('preferred_delivery_date')" data-flatpickr />
                <x-input-error :messages="$errors->get('preferred_delivery_date')" />
            </div>

            <div>
                <x-input-label for="notes" value="Notes (optional)" />
                <x-textarea id="notes" name="notes">{{ old('notes') }}</x-textarea>
            </div>

            <div class="flex justify-end gap-3">
                <x-button as="a" href="{{ route('customer.orders.index') }}" variant="secondary">Cancel</x-button>
                <x-button type="submit" x-bind:disabled="!selectedProduct || quantity < 1 || quantity > selectedProduct.stock_quantity">Place Order</x-button>
            </div>
        </form>
    </x-card>

    @push('scripts')
    <script>
        function orderForm(products) {
            return {
                products: products,
                productId: '{{ old('product_id') }}',
                quantity: {{ (int) old('quantity', 1) }},

                get selectedProduct() {
                    return this.products.find((p) => String(p.id) === String(this.productId)) ?? null;
                },

                get estimatedTotal() {
                    return this.selectedProduct ? this.selectedProduct.unit_price * (this.quantity || 0) : 0;
                },
            };
        }
    </script>
    @endpush
>>>>>>> theirs
</x-layouts.app>
