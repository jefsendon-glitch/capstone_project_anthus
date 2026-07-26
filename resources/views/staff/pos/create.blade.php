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
    <form
        method="POST"
        action="{{ route('pos.store') }}"
        x-data='posForm(@json($productsForCart))'
        class="grid grid-cols-1 gap-6 lg:grid-cols-3"
    >
        @csrf
        <input type="hidden" name="transaction_type" x-bind:value="transactionType">
        <input type="hidden" name="payment_method" x-bind:value="paymentMethod">
        <input type="hidden" name="customer_id" x-bind:value="customerId">
        <input type="hidden" name="items" x-bind:value="itemsJson">

        @if($errors->any())
            <div class="lg:col-span-3 rounded-2xl bg-danger-50 px-4 py-3 text-sm text-danger-700 dark:bg-danger-500/10 dark:text-danger-500">
                <ul class="list-inside list-disc">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Left: product selection --}}
        <div class="space-y-6 lg:col-span-2">
            <x-card>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Transaction Type:</p>
                <div class="mt-3 flex gap-3">
                    <button
                        type="button"
                        x-on:click="transactionType = 'walk-in'"
                        x-bind:class="transactionType === 'walk-in' ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' : 'border-slate-200 text-slate-500 dark:border-white/10 dark:text-slate-400'"
                        class="flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold"
                    >
                        <x-icon name="user-circle" class="size-4" /> Walk-In
                    </button>
                    <button
                        type="button"
                        x-on:click="transactionType = 'delivery'"
                        x-bind:class="transactionType === 'delivery' ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' : 'border-slate-200 text-slate-500 dark:border-white/10 dark:text-slate-400'"
                        class="flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold"
                    >
                        <x-icon name="truck" class="size-4" /> Delivery
                    </button>
                </div>
            </x-card>

            <x-card>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                        <x-icon name="search" class="size-4 text-slate-400" />
                    </div>
                    <input
                        type="search"
                        x-model="search"
                        placeholder="Search products..."
                        class="block w-full rounded-full border-0 py-2.5 pl-11 pr-4 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-slate-800 dark:text-white dark:ring-white/10"
                    >
                </div>
            </x-card>

            <div class="flex flex-wrap gap-2">
                @foreach($categories as $key => $label)
                    <button
                        type="button"
                        x-on:click="category = '{{ $key }}'"
                        x-bind:class="category === '{{ $key }}' ? 'bg-primary-600 text-white' : 'bg-white text-slate-600 ring-1 ring-inset ring-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:ring-white/10'"
                        class="rounded-full px-4 py-1.5 text-sm font-semibold"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <template x-for="product in filteredProducts" x-bind:key="product.id">
                    <button
                        type="button"
                        x-on:click="addToCart(product)"
                        x-bind:disabled="product.stock_quantity <= 0"
                        class="rounded-2xl bg-white p-4 text-left shadow-sm ring-1 ring-slate-900/5 transition hover:ring-primary-300 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-slate-900 dark:ring-white/10"
                    >
                        <div class="mb-3 overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-800">
                            <template x-if="product.image_url">
                                <img :src="product.image_url" :alt="product.name" class="h-32 w-full object-cover">
                            </template>
                            <template x-if="!product.image_url">
                                <div class="flex h-32 items-center justify-center text-primary-400">
                                    <x-icon name="photo" class="size-9" />
                                </div>
                            </template>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-white" x-text="product.name + ' (' + product.size + ')'"></p>
                                <p class="text-xs text-slate-400" x-text="categoryLabel(product.category)"></p>
                            </div>
                            <p class="shrink-0 font-mono text-base font-bold text-primary-600" x-text="'₱' + product.unit_price"></p>
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
        <div class="lg:sticky lg:top-20 lg:self-start">
            <x-card>
                <div class="flex items-center justify-between">
                    <h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Current Order</h2>
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

                <div class="mt-4 space-y-3" x-show="cart.length > 0" x-cloak>
                    <template x-for="(item, index) in cart" x-bind:key="item.product_id">
                        <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-3 dark:border-white/5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-900 dark:text-white" x-text="item.name + ' (' + item.size + ')'"></p>
                                <p class="font-mono text-xs text-slate-400" x-text="'₱' + item.unit_price + ' each'"></p>
                                <div class="mt-1 flex items-center gap-2">
                                    <button type="button" x-on:click="decrementQty(index)" class="flex size-6 items-center justify-center rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-white/10 dark:text-slate-300">–</button>
                                    <span class="w-6 text-center text-sm font-semibold text-slate-900 dark:text-white" x-text="item.quantity"></span>
                                    <button type="button" x-on:click="incrementQty(index)" class="flex size-6 items-center justify-center rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-white/10 dark:text-slate-300">+</button>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-2">
                                <p class="font-mono text-sm font-semibold text-slate-900 dark:text-white" x-text="'₱' + (item.unit_price * item.quantity).toFixed(2)"></p>
                                <button type="button" x-on:click="removeFromCart(index)" class="text-slate-400 hover:text-danger-600">
                                    <x-icon name="trash" class="size-4" />
                                </button>
                            </div>
                        </div>
                    </template>

                    <div class="flex items-center justify-between pt-1 text-base font-bold text-slate-900 dark:text-white">
                        <span>Total</span>
                        <span class="font-mono" x-text="'₱' + subtotal.toFixed(2)"></span>
                    </div>

                    <div class="space-y-3 pt-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Payment Method</p>
                            <div class="mt-2 grid grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    x-on:click="paymentMethod = 'cash'"
                                    x-bind:class="paymentMethod === 'cash' ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' : 'border-slate-200 text-slate-500 dark:border-white/10 dark:text-slate-400'"
                                    class="flex flex-col items-center gap-1.5 rounded-xl border-2 py-3 text-sm font-semibold"
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
                            <x-input-label value="Customer (required for credit)" />
                            <select x-model="customerId" class="block w-full rounded-xl border-0 py-2.5 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-slate-800 dark:text-white dark:ring-white/10">
                                <option value="">Select customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->email }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="paymentMethod === 'cash'" x-cloak>
                            <x-input-label value="Customer name (optional)" />
                            <input type="text" name="customer_name" x-model="customerName" placeholder="Walk-in guest name" class="block w-full rounded-xl border-0 py-2.5 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-slate-800 dark:text-white dark:ring-white/10">
                        </div>

                        <div x-show="paymentMethod === 'cash'" x-cloak class="rounded-2xl border border-primary-100 bg-primary-50/60 p-3.5 dark:border-primary-500/20 dark:bg-primary-500/10">
                            <x-input-label for="tendered_amount" value="Amount received" class="text-primary-800 dark:text-primary-200" />
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 font-mono text-sm font-semibold text-primary-600">&#8369;</span>
                                <input id="tendered_amount" type="number" name="tendered_amount" min="0" step="0.01" inputmode="decimal" x-model.number="tenderedAmount" placeholder="0.00" class="block w-full rounded-xl border-0 bg-white py-2.5 pl-8 pr-3.5 font-mono text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-primary-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-primary-500/30">
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
                    return JSON.stringify(this.cart.map((item) => ({ product_id: item.product_id, quantity: item.quantity })));
                },

                get tenderIsSufficient() {
                    return Number(this.tenderedAmount || 0) >= this.subtotal;
                },

                get changeAmount() {
                    return Math.max(0, Number(this.tenderedAmount || 0) - this.subtotal);
                },

                formatCurrency(amount) {
                    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(amount || 0));
                },

                categoryLabel(category) {
                    const labels = { refill: 'Refill', alkaline: 'Alkaline', distilled: 'Distilled', purified: 'Purified', mineral: 'Mineral', container: 'Container' };
                    return labels[category] ?? category;
                },

                addToCart(product) {
                    const existing = this.cart.find((item) => item.product_id === product.id);
                    if (existing) {
                        if (existing.quantity < product.stock_quantity) {
                            existing.quantity++;
                        }
                    } else if (product.stock_quantity > 0) {
                        this.cart.push({
                            product_id: product.id,
                            name: product.name,
                            size: product.size,
                            unit_price: product.unit_price,
                            image_url: product.image_url,
                            stock_quantity: product.stock_quantity,
                            quantity: 1,
                        });
                    }
                },

                removeFromCart(index) {
                    this.cart.splice(index, 1);
                },

                incrementQty(index) {
                    const item = this.cart[index];
                    if (item.quantity < item.stock_quantity) {
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
