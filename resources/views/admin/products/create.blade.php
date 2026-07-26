<x-layouts.app title="Add Product" :heading="'Add Product'">
    <x-card class="mx-auto max-w-xl">
        <form method="POST" action="{{ route('admin.products.store') }}" class="space-y-5" enctype="multipart/form-data">
            @csrf

            <div>
                <x-input-label for="image" value="Product image (optional)" />
                <input id="image" type="file" name="image" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-primary-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100 dark:text-slate-300 dark:file:bg-primary-500/10 dark:file:text-primary-400">
                <x-input-error :messages="$errors->get('image')" />
            </div>

            <div>
                <x-input-label for="name" value="Product name" />
                <x-text-input id="name" name="name" :value="old('name')" required autofocus />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div class="grid grid-cols-2 gap-5">
                <div>
                    <x-input-label for="category" value="Category" />
                    <x-select id="category" name="category" required>
                        @foreach(['refill', 'alkaline', 'distilled', 'purified', 'mineral', 'container'] as $category)
                            <option value="{{ $category }}" @selected(old('category') === $category)>{{ ucfirst($category) }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('category')" />
                </div>

                <div>
                    <x-input-label for="size" value="Size" />
                    <x-text-input id="size" name="size" :value="old('size')" placeholder="5 Gallon, 1 Liter..." required />
                    <x-input-error :messages="$errors->get('size')" />
                </div>
            </div>

            <div class="grid grid-cols-3 gap-5">
                <div>
                    <x-input-label for="unit_price" value="Unit price (₱)" />
                    <x-text-input id="unit_price" type="number" step="0.01" min="0" name="unit_price" :value="old('unit_price')" required />
                    <x-input-error :messages="$errors->get('unit_price')" />
                </div>

                <div>
                    <x-input-label for="stock_quantity" value="Stock quantity" />
                    <x-text-input id="stock_quantity" type="number" min="0" name="stock_quantity" :value="old('stock_quantity', 0)" required />
                    <x-input-error :messages="$errors->get('stock_quantity')" />
                </div>

                <div>
                    <x-input-label for="low_stock_threshold" value="Low stock threshold" />
                    <x-text-input id="low_stock_threshold" type="number" min="0" name="low_stock_threshold" :value="old('low_stock_threshold', 10)" required />
                    <x-input-error :messages="$errors->get('low_stock_threshold')" />
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-primary-600 focus:ring-primary-600">
                Active (available for sale)
            </label>

            <div class="flex justify-end gap-3">
                <x-button as="a" href="{{ route('admin.products.index') }}" variant="secondary">Cancel</x-button>
                <x-button type="submit">Save Product</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
