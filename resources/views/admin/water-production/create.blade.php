<x-layouts.app title="Log Production" :heading="'Log Production'">
    <x-card class="mx-auto max-w-xl">
        <form method="POST" action="{{ route('admin.water-production.store') }}" class="space-y-5">
            @csrf

            <div>
                <x-input-label for="product_id" value="Product" />
                <x-select id="product_id" name="product_id" required>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }} ({{ $product->size }})</option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('product_id')" />
            </div>

            <div class="grid grid-cols-2 gap-5">
                <div>
                    <x-input-label for="gallons_produced" value="Gallons produced" />
                    <x-text-input id="gallons_produced" type="number" min="1" name="gallons_produced" :value="old('gallons_produced')" required />
                    <x-input-error :messages="$errors->get('gallons_produced')" />
                </div>

                <div>
                    <x-input-label for="production_date" value="Production date" />
                    <x-text-input id="production_date" type="text" name="production_date" data-flatpickr :value="old('production_date', now()->format('Y-m-d'))" required />
                    <x-input-error :messages="$errors->get('production_date')" />
                </div>
            </div>

            <div>
                <x-input-label for="notes" value="Notes (optional)" />
                <x-textarea id="notes" name="notes">{{ old('notes') }}</x-textarea>
                <x-input-error :messages="$errors->get('notes')" />
            </div>

            <div class="flex justify-end gap-3">
                <x-button as="a" href="{{ route('admin.water-production.index') }}" variant="secondary">Cancel</x-button>
                <x-button type="submit">Log Production</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
