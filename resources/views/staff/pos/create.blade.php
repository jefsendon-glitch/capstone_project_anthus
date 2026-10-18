@php
$categories = [
    'all' => 'All',
    'refill' => 'Refill',
    'alkaline' => 'Alkaline',
    'distilled' => 'Distilled',
    'purified' => 'Purified',
    'mineral' => 'Mineral',
    'container' => 'Container',
];
@endphp

<x-layouts.app title="Point of Sale" :heading="'Point of Sale'" :subheading="now()->format('l, F j, Y')">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2"><p class="text-sm text-slate-500 dark:text-slate-400">Select products, review the order, then record payment.</p><x-button as="a" :href="route('pos.index')" variant="secondary">Sales History</x-button></div>
    <form
        method="POST"
        action="{{ route('pos.store') }}"
        x-data='posForm(@json($productsForCart))'
        class="grid grid-cols-1 items-start gap-4 lg:grid-cols-[minmax(0,1fr)_22rem]"
    >
        @csrf
        <input type="hidden" name="transaction_type" x-bind:value="transactionType">
        <input type="hidden" name="payment_method" x-bind:value="paymentMethod">
        <input type="hidden" name="customer_id" x-bind:value="customerId">
        <input type="hidden" name="items" x-bind:value="itemsJson">

        @if($errors->any())
            <div class="lg:col-span-2 rounded-2xl bg-danger-50 px-4 py-3 text-sm text-danger-700 dark:bg-danger-500/10 dark:text-danger-500">
                <ul class="list-inside list-disc">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Left: product selection --}}
        <div class="min-w-0 space-y-3">
            <x-card padding="p-4">
                <div class="mb-3 flex items-center justify-between gap-2"><h2 class="text-base font-semibold">Products</h2><button type="button" x-on:click="search = ''; category = 'all'" x-show="search || category !== 'all'" x-cloak class="text-sm font-semibold text-primary-700 dark:text-primary-300">Clear Filters</button></div>
                <div class="grid items-end gap-3 xl:grid-cols-[minmax(0,1fr)_auto]">
                    <div class="min-w-0"><x-input-label for="pos-product-search" value="Search Products" /><div class="relative"><x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" /><x-text-input id="pos-product-search" type="search" x-model="search" placeholder="Product name..." class="!pl-9" /></div></div>
                    <fieldset><legend class="mb-1 text-sm font-medium text-slate-700 dark:text-slate-300">Transaction Type</legend><div class="grid grid-cols-2 gap-2">
                        @foreach(['walk-in' => ['Walk-in', 'user-circle'], 'delivery' => ['Delivery', 'truck']] as $value => [$label, $icon])
                            <button type="button" x-on:click="transactionType = '{{ $value }}'" x-bind:aria-pressed="transactionType === '{{ $value }}'" x-bind:class="transactionType === '{{ $value }}' ? 'border-primary-600 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' : 'border-slate-200 bg-white text-slate-600 dark:border-white/10 dark:bg-slate-800 dark:text-slate-300'" class="flex min-h-10 items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm font-semibold"><x-icon :name="$icon" class="size-4" /> {{ $label }}</button>
                        @endforeach
                    </div></fieldset>
                </div>
                <div class="mt-3 border-t border-slate-100 pt-3 dark:border-white/10"><p class="mb-2 text-xs font-medium text-slate-500 dark:text-slate-400">Product Category</p><div class="flex flex-wrap gap-2" role="group" aria-label="Product categories">
                    @foreach($categories as $key => $label)
                        <button type="button" x-on:click="category = '{{ $key }}'" x-bind:aria-pressed="category === '{{ $key }}'" x-bind:class="category === '{{ $key }}' ? 'bg-primary-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'" class="rounded-lg px-3 py-2 text-sm font-medium">{{ $label }}</button>
                    @endforeach
                </div></div>
                <div class="mt-3 border-t border-slate-100 pt-3 dark:border-white/10"><x-input-label for="pos-container-type" value="Container Type for Water" /><x-select id="pos-container-type" x-model="containerType"><option value="round">Round Gallon</option><option value="slim">Slim Gallon</option></x-select><p class="mt-1 text-xs text-slate-500">Select a container, then add a water product. Both choices use the same price and stock.</p></div>
                <p class="mt-3 text-xs text-slate-500 dark:text-slate-400" aria-live="polite" x-text="filteredProducts.length + ' products available. Select a product to add it to the order.'"></p>
            </x-card>

            <div class="grid max-h-[32rem] grid-cols-2 gap-3 overflow-y-auto p-1 xl:grid-cols-3" tabindex="0" aria-label="Available products">
                <template x-for="product in filteredProducts" x-bind:key="product.id">
                    <button
                        type="button"
                        x-on:click="addToCart(product)"
                        x-bind:disabled="product.stock_quantity <= cartQuantity(product.id)"
                        class="min-w-0 rounded-xl bg-white p-3 text-left shadow-sm ring-1 ring-slate-900/5 transition hover:ring-primary-300 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-slate-900 dark:ring-white/10"
                    >
                        <div class="mb-2 overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800">
                            <template x-if="product.image_url">
                                <img :src="product.image_url" :alt="product.name" class="h-20 w-full object-contain">
                            </template>
                            <template x-if="!product.image_url">
                                <div class="flex h-20 items-center justify-center text-primary-400">
                                    <x-icon name="photo" class="size-9" />
                                </div>
                            </template>
                        </div>
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-white" x-text="product.name + ' (' + product.size + ')'"></p>
                                <p class="text-xs text-slate-400" x-text="categoryLabel(product.category)"></p>
                            </div>
                            <p class="shrink-0 font-mono text-base font-bold text-primary-600" x-text="formatCurrency(product.unit_price)"></p>
                        </div>
                        <div class="mt-3 flex items-center justify-between text-xs">
                            <span
                                class="font-medium"
                                x-bind:class="product.is_low_stock ? 'text-danger-600' : 'text-success-600'"
                                x-text="product.stock_quantity + ' ' + product.stock_unit_label + ' left'"
                            ></span>
                            <span x-show="product.is_low_stock" x-cloak class="flex items-center gap-1 font-semibold text-warning-600">
                                <x-icon name="alert-triangle" class="size-3.5" /> Low
                            </span>
                        </div>
                    </button>
                </template>

                <p x-show="filteredProducts.length === 0" x-cloak class="col-span-full py-10 text-center text-sm text-slate-400">
                    No products match your search.
                </p>
            </div>
        </div>

        {{-- Right: current order / cart --}}
        <div class="min-w-0 lg:sticky lg:top-20 lg:self-start">
            <x-card padding="p-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Current Order <span class="ml-1 text-xs font-normal text-slate-500" x-text="'(' + cart.length + ' items)'"></span></h2>
                    <span class="flex items-center gap-1 rounded-full bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">
                        <x-icon name="user-circle" class="size-3.5" x-show="transactionType === 'walk-in'" />
                        <x-icon name="truck" class="size-3.5" x-show="transactionType === 'delivery'" />
                        <span x-text="transactionType === 'walk-in' ? 'Walk-In' : 'Delivery'"></span>
                    </span>
                </div>
                <p class="text-sm text-slate-400">{{ now()->format('M j, Y') }}</p>

                <div class="mt-4" x-show="cart.length === 0">
                    <div class="flex flex-col items-center justify-center py-10 text-center">
                        <x-icon name="cart" class="size-10 text-slate-300 dark:text-slate-700" />
                        <p class="mt-3 text-sm font-medium text-slate-400">Cart is empty</p>
                        <p class="text-xs text-slate-400">Click a product to add</p>
                    </div>
                </div>

                <div class="mt-3 space-y-3" x-show="cart.length > 0" x-cloak>
                    <div class="max-h-60 space-y-3 overflow-y-auto pr-1" tabindex="0" aria-label="Current order items">
                    <template x-for="(item, index) in cart" x-bind:key="item.product_id + ':' + (item.container_type || '')">
                        <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-3 dark:border-white/5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-900 dark:text-white" x-text="item.name + ' (' + item.size + ')' + (item.container_type ? ' - ' + containerLabel(item.container_type) : '')"></p>
                                <p class="font-mono text-xs text-slate-400" x-text="formatCurrency(item.unit_price) + ' each'"></p>
                                <div class="mt-1 flex items-center gap-2">
                                    <button type="button" x-on:click="decrementQty(index)" aria-label="Decrease quantity" class="flex size-8 items-center justify-center rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-white/10 dark:text-slate-300">–</button>
                                    <span class="w-6 text-center text-sm font-semibold text-slate-900 dark:text-white" x-text="item.quantity"></span>
                                    <button type="button" x-on:click="incrementQty(index)" aria-label="Increase quantity" class="flex size-6 items-center justify-center rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-white/10 dark:text-slate-300">+</button>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-2">
                                <p class="font-mono text-sm font-semibold text-slate-900 dark:text-white" x-text="formatCurrency(item.unit_price * item.quantity)"></p>
                                <button type="button" x-on:click="removeFromCart(index)" aria-label="Remove item" class="text-slate-400 hover:text-danger-600">
                                    <x-icon name="trash" class="size-4" />
                                </button>
                            </div>
                        </div>
                    </template>
                    </div>

                    <div class="flex items-center justify-between rounded-lg bg-primary-50 p-3 text-base font-bold dark:bg-primary-500/10 text-slate-900 dark:text-white">
                        <span>Total</span>
                        <span class="font-mono" x-text="formatCurrency(subtotal)"></span>
                    </div>

                    <div class="space-y-3 pt-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Payment Method</p>
                            <div class="mt-2 grid grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    x-on:click="paymentMethod = 'cash'"
                                    x-bind:class="paymentMethod === 'cash' ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' : 'border-slate-200 text-slate-500 dark:border-white/10 dark:text-slate-400'"
                                    class="flex items-center justify-center gap-2 rounded-lg border py-2 text-sm font-semibold"
                                >
                                    <x-icon name="dollar" class="size-5" /> Cash
                                </button>
                                <button
                                    type="button"
                                    x-on:click="paymentMethod = 'loan'"
                                    x-bind:class="paymentMethod === 'loan' ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' : 'border-slate-200 text-slate-500 dark:border-white/10 dark:text-slate-400'"
                                    class="flex flex-col items-center gap-1.5 rounded-xl border-2 py-3 text-sm font-semibold"
                                >
                                    <x-icon name="credit-card" class="size-5" /> Credit
                                </button>
                            </div>
                        </div>

                        <div x-show="paymentMethod === 'loan'" x-cloak>
                            <x-input-label for="pos-customer" value="Customer (Required for Credit)" />
                            <select id="pos-customer" x-model="customerId" class="block w-full rounded-xl border-0 py-2.5 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-slate-800 dark:text-white dark:ring-white/10">
                                <option value="">Select customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" @disabled($customer->credit_status !== 'eligible')>{{ $customer->name }} ({{ $customer->credit_status === 'eligible' ? $customer->email : 'credit suspended — cash only' }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="paymentMethod === 'cash'" x-cloak>
                            <x-input-label for="pos-customer-name" value="Customer Name (Optional)" />
                            <input id="pos-customer-name" type="text" name="customer_name" x-model="customerName" placeholder="Walk-in guest name" class="block w-full rounded-xl border-0 py-2.5 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-slate-800 dark:text-white dark:ring-white/10">
                        </div>

                        <div x-show="paymentMethod === 'cash'" x-cloak class="rounded-2xl border border-primary-100 bg-primary-50/60 p-3.5 dark:border-primary-500/20 dark:bg-primary-500/10">
                            <x-input-label for="tendered_amount" value="Amount received" class="text-primary-800 dark:text-primary-200" />
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 font-mono text-sm font-semibold text-primary-600">&#8369;</span>
                                <input id="tendered_amount" type="number" name="tendered_amount" min="0" step="1" inputmode="numeric" x-model.number="tenderedAmount" placeholder="0" class="block w-full rounded-xl border-0 bg-white py-2.5 pl-8 pr-3.5 font-mono text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-primary-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-primary-500/30">
                            </div>
                            <div class="mt-3 flex items-center justify-between text-sm">
                                <span class="font-medium text-primary-800 dark:text-primary-200">Change</span>
                                <span class="font-mono text-base font-bold text-primary-700 dark:text-primary-300" x-text="formatCurrency(changeAmount)"></span>
                            </div>
                            <p x-show="tenderedAmount && !tenderIsSufficient" x-cloak class="mt-2 text-xs font-medium text-danger-600 dark:text-danger-400" x-text="'Add ' + formatCurrency(subtotal - tenderedAmount) + ' more to complete payment.'"></p>
                        </div>

                        <button
                            type="submit"
                            x-bind:disabled="cart.length === 0 || (paymentMethod === 'loan' && !customerId) || (paymentMethod === 'cash' && !tenderIsSufficient)"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <x-icon name="check-circle" class="size-4" /> Record Transaction
                        </button>
                    </div>
                </div>
            </x-card>
        </div>
    </form>

    @push('scripts')
    <script>
        function posForm(products) {
            return {
                products: products,
                search: '',
                category: 'all',
                containerType: 'round',
                transactionType: 'walk-in',
                paymentMethod: 'cash',
                customerId: '',
                customerName: '',
                tenderedAmount: '',
                cart: [],

                get filteredProducts() {
                    const term = this.search.toLowerCase();
                    return this.products.filter((p) => {
                        const matchesCategory = this.category === 'all' || p.category === this.category;
                        const matchesSearch = p.name.toLowerCase().includes(term);
                        return matchesCategory && matchesSearch;
                    });
                },

                get subtotal() {
                    return this.cart.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
                },

                get itemsJson() {
                    return JSON.stringify(this.cart.map((item) => ({ product_id: item.product_id, quantity: item.quantity, container_type: item.container_type })));
                },

                get tenderIsSufficient() {
                    return Number(this.tenderedAmount || 0) >= this.subtotal;
                },

                get changeAmount() {
                    return Math.max(0, Number(this.tenderedAmount || 0) - this.subtotal);
                },

                formatCurrency(amount) {
                    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Number(amount || 0));
                },

                categoryLabel(category) {
                    const labels = { refill: 'Refill', alkaline: 'Alkaline', distilled: 'Distilled', purified: 'Purified', mineral: 'Mineral', container: 'Container' };
                    return labels[category] ?? category;
                },

                containerLabel(type) { return type === 'slim' ? 'Slim Gallon' : 'Round Gallon'; },
                cartQuantity(id) { return this.cart.filter(item => item.product_id === id).reduce((sum, item) => sum + item.quantity, 0); },
                addToCart(product) {
                    if (this.cartQuantity(product.id) >= product.stock_quantity) return;
                    const container = product.requires_container_choice ? this.containerType : null;
                    const existing = this.cart.find(item => item.product_id === product.id && item.container_type === container);
                    if (existing) existing.quantity++;
                    else this.cart.push({ product_id: product.id, name: product.name, size: product.size, unit_price: product.unit_price, image_url: product.image_url, stock_quantity: product.stock_quantity, container_type: container, quantity: 1 });
                },

                removeFromCart(index) {
                    this.cart.splice(index, 1);
                },

                incrementQty(index) {
                    const item = this.cart[index];
                    if (this.cartQuantity(item.product_id) < item.stock_quantity) {
                        item.quantity++;
                    }
                },

                decrementQty(index) {
                    if (this.cart[index].quantity > 1) {
                        this.cart[index].quantity--;
                    } else {
                        this.cart.splice(index, 1);
                    }
                },
            };
        }
    </script>
    @endpush
</x-layouts.app>
