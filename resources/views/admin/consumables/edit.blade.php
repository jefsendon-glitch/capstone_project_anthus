<x-layouts.app title="Edit Supply" :heading="'Edit Supply'">
    <x-card class="mx-auto max-w-xl">
        <form method="POST" action="{{ route('admin.consumables.update', $consumable) }}" class="space-y-5">
            @csrf
            @method('put')

            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" :value="old('name', $consumable->name)" required autofocus />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div class="grid grid-cols-2 gap-5">
                <div>
                    <x-input-label for="category" value="Category" />
                    <x-select id="category" name="category" required>
                        @foreach(['bottle_caps', 'bottle_seals', 'labels', 'plastic_bags', 'water_filters', 'uv_lamps', 'cleaning_supplies'] as $category)
                            <option value="{{ $category }}" @selected(old('category', $consumable->category) === $category)>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('category')" />
                </div>

                <div>
                    <x-input-label for="unit" value="Unit" />
                    <x-text-input id="unit" name="unit" :value="old('unit', $consumable->unit)" required />
                    <x-input-error :messages="$errors->get('unit')" />
                </div>
            </div>

            <div class="grid grid-cols-3 gap-5">
                <div>
                    <x-input-label for="quantity" value="Quantity" />
                    <x-text-input id="quantity" type="number" step="0.01" min="0" name="quantity" :value="old('quantity', $consumable->quantity)" required />
                    <p class="mt-1 text-xs text-slate-500">Any quantity change is recorded as an adjustment in the stock movement history.</p>
                    <x-input-error :messages="$errors->get('quantity')" />
                </div>

                <div>
                    <x-input-label for="low_stock_threshold" value="Low stock threshold" />
                    <x-text-input id="low_stock_threshold" type="number" step="0.01" min="0" name="low_stock_threshold" :value="old('low_stock_threshold', $consumable->low_stock_threshold)" required />
                    <x-input-error :messages="$errors->get('low_stock_threshold')" />
                </div>

                <div>
                    <x-input-label for="unit_cost" value="Unit cost (₱)" />
                    <x-text-input id="unit_cost" type="number" step="0.01" min="0" name="unit_cost" :value="old('unit_cost', $consumable->unit_cost)" required />
                    <x-input-error :messages="$errors->get('unit_cost')" />
                </div>
            </div>

            <div>
                <x-input-label for="supplier_id" value="Supplier (optional)" />
                <x-select id="supplier_id" name="supplier_id" data-tom-select>
                    <option value="">— None —</option>
                    @foreach(\App\Models\Supplier::active()->orderBy('name')->get() as $supplier)
                        <option value="{{ $supplier->id }}" @selected(old('supplier_id', $consumable->supplier_id) == $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('supplier_id')" />
            </div>

            <div>
                <x-input-label for="notes" value="Notes (optional)" />
                <x-textarea id="notes" name="notes">{{ old('notes', $consumable->notes) }}</x-textarea>
                <x-input-error :messages="$errors->get('notes')" />
            </div>

            <div class="flex justify-end gap-3">
                <x-button as="a" href="{{ route('admin.consumables.index') }}" variant="secondary">Cancel</x-button>
                <x-button type="submit">Update Supply</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
