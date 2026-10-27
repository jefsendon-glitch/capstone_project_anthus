<x-layouts.app title="Add Product" :heading="'Add Product'" :subheading="'Add a water refill or other product to inventory.'">
    <x-card class="mx-auto max-w-3xl" padding="p-5 sm:p-6">
        <form method="POST" action="{{ route('admin.products.store') }}" class="space-y-6" enctype="multipart/form-data" x-data="{ productKind: @js(old('product_kind', 'water')) }">
            @csrf

            <section class="space-y-4" aria-labelledby="product-details-heading">
                <div class="border-b border-slate-100 pb-3 dark:border-white/10">
                    <h2 id="product-details-heading" class="text-base font-semibold text-slate-900 dark:text-white">Product Details</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Choose the product type, name, size, and image.</p>
                </div>

                <div>
                    <x-input-label for="image" value="Product Image (Optional)" />
                    <input id="image" type="file" name="image" accept="image/png,image/jpeg,image/webp" class="mt-1 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-primary-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100 dark:border-white/10 dark:bg-slate-800 dark:text-slate-300 dark:file:bg-primary-500/10 dark:file:text-primary-400">
                    <p class="mt-1 text-xs text-slate-500">PNG, JPG, or WEBP image.</p>
                    <x-input-error :messages="$errors->get('image')" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="product-kind" value="Product Type" />
                        <x-select id="product-kind" name="product_kind" x-model="productKind" required>
                            <option value="water">Water Product</option>
                            <option value="other">Other Product</option>
                        </x-select>
                        <x-input-error :messages="$errors->get('product_kind')" />
                    </div>

                    <div>
                        <x-input-label for="size" value="Size / Unit" />
                        <x-text-input id="size" name="size" :value="old('size')" x-bind:placeholder="productKind === 'water' ? 'e.g. 5 Gallon' : 'e.g. 1 pc, 1 pack, or Small'" required />
                        <x-input-error :messages="$errors->get('size')" />
                    </div>

                    <div x-show="productKind === 'water'">
                        <x-input-label for="water-type" value="Water Type" />
                        <x-select id="water-type" name="name" x-bind:disabled="productKind !== 'water'" x-bind:required="productKind === 'water'"><option value="">Select water type</option>@foreach(\App\Models\Product::WATER_TYPES as $value)<option value="{{ $value }}" @selected(old('name') === $value)>{{ $value }}</option>@endforeach</x-select>
                        <x-input-error :messages="$errors->get('name')" />
                    </div>

                    <div x-show="productKind === 'other'" x-cloak>
                        <x-input-label for="other-product-name" value="Product Name" />
                        <x-text-input id="other-product-name" name="name" :value="old('name')" placeholder="e.g. Cup, dispenser tap, or accessory" maxlength="255" x-bind:disabled="productKind !== 'other'" x-bind:required="productKind === 'other'" />
                        <x-input-error :messages="$errors->get('name')" />
                    </div>

                </div>
            </section>

            <section class="space-y-4" aria-labelledby="pricing-inventory-heading">
                <div class="border-b border-slate-100 pb-3 dark:border-white/10">
                    <h2 id="pricing-inventory-heading" class="text-base font-semibold text-slate-900 dark:text-white">Pricing &amp; Inventory</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400" x-text="productKind === 'water' ? 'Water uses one price with separate Round and Slim stock.' : 'Set the sale price and starting stock for this product.'"></p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <x-input-label for="unit_price" value="Unit Price (₱)" />
                        <x-text-input id="unit_price" type="number" step="1" min="0" name="unit_price" :value="old('unit_price')" required />
                        <x-input-error :messages="$errors->get('unit_price')" />
                    </div>

                    <div x-show="productKind === 'other'" x-cloak>
                        <x-input-label for="stock_quantity" value="Opening Stock" />
                        <x-text-input id="stock_quantity" type="number" min="0" name="stock_quantity" :value="old('stock_quantity', 0)" x-bind:disabled="productKind === 'water'" x-bind:required="productKind === 'other'" />
                        <x-input-error :messages="$errors->get('stock_quantity')" />
                        <p class="mt-1 text-xs text-slate-500">Quantity available for sale.</p>
                    </div>

                    <div>
                        <x-input-label for="low_stock_threshold" value="Low Stock Alert At" />
                        <x-text-input id="low_stock_threshold" type="number" min="0" name="low_stock_threshold" :value="old('low_stock_threshold', 10)" required />
                        <x-input-error :messages="$errors->get('low_stock_threshold')" />
                    </div>
                </div>

                <div x-show="productKind === 'water'" x-cloak class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-white/5">
                    <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Opening Filled Stock</h3>
                    <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="round-stock" value="Round Gallons" />
                            <x-text-input id="round-stock" name="round_stock" type="number" min="0" :value="old('round_stock', 0)" x-bind:disabled="productKind !== 'water'" x-bind:required="productKind === 'water'" />
                            <x-input-error :messages="$errors->get('round_stock')" />
                        </div>
                        <div>
                            <x-input-label for="slim-stock" value="Slim Gallons" />
                            <x-text-input id="slim-stock" name="slim_stock" type="number" min="0" :value="old('slim_stock', 0)" x-bind:disabled="productKind !== 'water'" x-bind:required="productKind === 'water'" />
                            <x-input-error :messages="$errors->get('slim_stock')" />
                        </div>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-slate-500">Enter existing filled stock only. Future fills add stock through Gallon Tracking.</p>
                </div>
            </section>

            <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4 text-sm dark:border-white/10">
                <input type="checkbox" name="is_active" value="1" checked class="mt-0.5 rounded border-slate-300 text-primary-600 focus:ring-primary-600">
                <span><span class="block font-medium text-slate-800 dark:text-slate-200">Active product</span><span class="mt-0.5 block text-xs text-slate-500">Available to sell in POS.</span></span>
            </label>

            <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end dark:border-white/10">
                <x-button as="a" href="{{ route('admin.products.index') }}" variant="secondary">Cancel</x-button>
                <x-button variant="success" type="submit">Save Product</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
