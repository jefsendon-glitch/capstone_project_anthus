@props(['product'])
@php
    $restore = (string) old('product_stock_id') === (string) $product->id;
@endphp
<div class="inline-flex" x-data="{ open: @js($restore && $errors->any()), round: @js($restore ? old('round_stock', $product->round_stock) : $product->round_stock), slim: @js($restore ? old('slim_stock', $product->slim_stock) : $product->slim_stock) }" x-on:keydown.escape.window="open = false">
    <button type="button" x-on:click="open = true" class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700 hover:bg-primary-100 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-primary-500/10 dark:text-primary-400" title="Edit stock quantity" aria-label="Edit stock quantity for {{ $product->name }}">
        {{ number_format($product->stock_quantity) }} <x-icon name="pencil" class="size-3" />
    </button>
    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50" x-on:click="open = false"></div>
            <div role="dialog" aria-modal="true" aria-labelledby="stock-title-{{ $product->id }}" class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-xl dark:bg-slate-900">
                <h3 id="stock-title-{{ $product->id }}" class="text-base font-semibold">Edit Stock Quantity</h3>
                <p class="mt-1 text-sm text-slate-500">{{ $product->name }} · {{ $product->size }}</p>
                <form method="POST" action="{{ route('admin.products.stock.update', $product) }}" class="mt-4 space-y-3">
                    @csrf
                    <input type="hidden" name="product_stock_id" value="{{ $product->id }}">
                    @if($product->requires_container_choice)
                        <div class="grid grid-cols-2 gap-3">
                            <div><x-input-label for="stock-round-{{ $product->id }}" value="Round Quantity" /><x-text-input id="stock-round-{{ $product->id }}" name="round_stock" type="number" min="0" max="1000000" x-model="round" required />@if($restore)<x-input-error :messages="$errors->get('round_stock')" />@endif</div>
                            <div><x-input-label for="stock-slim-{{ $product->id }}" value="Slim Quantity" /><x-text-input id="stock-slim-{{ $product->id }}" name="slim_stock" type="number" min="0" max="1000000" x-model="slim" required />@if($restore)<x-input-error :messages="$errors->get('slim_stock')" />@endif</div>
                        </div>
                        <p class="text-sm font-semibold">Total Stock: <span x-text="Number(round || 0) + Number(slim || 0)"></span></p>
                        <p class="text-xs text-slate-500">Enter the current quantities. These replace the recorded balances.</p>
                        @if($product->availableStock(null) > 0)<p class="text-xs text-amber-600">Include the {{ $product->availableStock(null) }} unassigned gallons in the actual Round/Slim counts.</p>@endif
                    @else
                        <div><x-input-label for="stock-quantity-{{ $product->id }}" value="Stock Quantity" /><x-text-input id="stock-quantity-{{ $product->id }}" name="quantity" type="number" min="0" :value="$restore ? old('quantity', $product->stock_quantity) : $product->stock_quantity" required />@if($restore)<x-input-error :messages="$errors->get('quantity')" />@endif</div>
                        <p class="text-xs text-slate-500">Enter the current quantity. This replaces the recorded stock.</p>
                    @endif
                    <div class="flex justify-end gap-2"><x-button type="button" variant="secondary" x-on:click="open = false">Cancel</x-button><x-button type="submit" variant="info">Save Stock</x-button></div>
                </form>
            </div>
        </div>
    </template>
</div>
