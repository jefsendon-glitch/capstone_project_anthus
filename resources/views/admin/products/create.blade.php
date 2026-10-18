<x-layouts.app title="Add Product" :heading="'Add Product'">
    <x-card class="mx-auto max-w-xl">
        <form method="POST" action="{{ route('admin.products.store') }}" class="space-y-5" enctype="multipart/form-data" x-data="{ productKind: @js(old('product_kind', 'water')) }">
            @csrf

            <div>
                <x-input-label for="product-kind" value="Product Type" />
                <x-select id="product-kind" name="product_kind" x-model="productKind" required>
                    <option value="water">Water Product</option>
                    <option value="other">Other Product</option>
                </x-select>
                <x-input-error :messages="$errors->get('product_kind')" />
            </div>

            <div>
                <x-input-label for="image" value="Product image (optional)" />
                <input id="image" type="file" name="image" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-primary-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100 dark:text-slate-300 dark:file:bg-primary-500/10 dark:file:text-primary-400">
                <x-input-error :messages="$errors->get('image')" />
            </div>

            <div x-show="productKind === 'water'">
                <x-input-label for="name" value="Water Type" />
                <x-select id="name" name="name" x-bind:disabled="productKind !== 'water'" x-bind:required="productKind === 'water'"><option value="">Select water type</option>@foreach(\App\Models\Product::WATER_TYPES as $value)<option value="{{ $value }}" @selected(old('name') === $value)>{{ $value }}</option>@endforeach</x-select>
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div x-show="productKind === 'other'" x-cloak>
                <x-input-label for="other-product-name" value="Product Name" />
                <x-text-input id="other-product-name" name="name" :value="old('name')" placeholder="e.g. Cup, dispenser tap, or accessory" maxlength="255" x-bind:disabled="productKind !== 'other'" x-bind:required="productKind === 'other'" />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div class="grid grid-cols-2 gap-5">
                <p class="text-sm text-slate-500 dark:text-slate-400" x-text="productKind === 'water' ? 'Round and Slim use the same price, with separate stock balances.' : 'Other products use their own stock and do not require a Round or Slim container.'"></p>

                <div>
                    <x-input-label for="size" value="Size / Unit" />
                    <x-text-input id="size" name="size" :value="old('size')" x-bind:placeholder="productKind === 'water' ? 'e.g. 5 Gallon' : 'e.g. 1 pc, 1 pack, or Small'" required />
                    <x-input-error :messages="$errors->get('size')" />
                </div>
            </div>

            <div class="grid grid-cols-3 gap-5">
                <div>
                    <x-input-label for="unit_price" value="Unit price (₱)" />
                    <x-text-input id="unit_price" type="number" step="1" min="0" name="unit_price" :value="old('unit_price')" required />
                    <x-input-error :messages="$errors->get('unit_price')" />
                </div>

                <div x-show="productKind === 'other'" x-cloak>
                    <x-input-label for="stock_quantity" value="Opening Stock" />
                    <x-text-input id="stock_quantity" type="number" min="0" name="stock_quantity" :value="old('stock_quantity', 0)" x-bind:disabled="productKind === 'water'" x-bind:required="productKind === 'other'" />
                    <x-input-error :messages="$errors->get('stock_quantity')" />
                    <p class="mt-1 text-xs text-slate-500" x-text="productKind === 'water' ? 'Enter existing filled stock only. Future fills add stock through Gallon Tracking.' : 'Enter the quantity available for sale.'"></p>
                </div>

                <div>
                    <x-input-label for="low_stock_threshold" value="Low stock threshold" />
                    <x-text-input id="low_stock_threshold" type="number" min="0" name="low_stock_threshold" :value="old('low_stock_threshold', 10)" required />
                    <x-input-error :messages="$errors->get('low_stock_threshold')" />
                </div>
            </div>

            <div x-show="productKind === 'water'" class="grid grid-cols-2 gap-3">
                <div><x-input-label for="round-stock" value="Opening Round Stock" /><x-text-input id="round-stock" name="round_stock" type="number" min="0" :value="old('round_stock', 0)" x-bind:disabled="productKind !== 'water'" x-bind:required="productKind === 'water'" /><x-input-error :messages="$errors->get('round_stock')" /></div>
                <div><x-input-label for="slim-stock" value="Opening Slim Stock" /><x-text-input id="slim-stock" name="slim_stock" type="number" min="0" :value="old('slim_stock', 0)" x-bind:disabled="productKind !== 'water'" x-bind:required="productKind === 'water'" /><x-input-error :messages="$errors->get('slim_stock')" /></div>
                <p class="text-xs text-slate-500 sm:col-span-2">Enter existing filled stock only. Future fills add to the matching Round or Slim balance.</p>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-primary-600 focus:ring-primary-600">
                Active (available for sale)
            </label>

            <div class="flex justify-end gap-3">
                <x-button as="a" href="{{ route('admin.products.index') }}" variant="secondary">Cancel</x-button>
                <x-button variant="success" type="submit">Save Product</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
