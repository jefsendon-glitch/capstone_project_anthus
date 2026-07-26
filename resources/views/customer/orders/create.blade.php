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
</x-layouts.app>
