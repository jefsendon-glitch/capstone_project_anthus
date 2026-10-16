<x-layouts.app title="Stock Overview" :heading="'Stock Overview'" :subheading="'Current product and supply quantities maintained by Admin'">
    <x-portal-nav />
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-slate-500">Review stock before sales or delivery. Contact Admin for restocking or corrections.</p>
        <x-button as="a" :href="route('pos.create')" class="!py-2 !text-xs">Record a Sale</x-button>
    </div>
    <form method="GET" class="mb-4 flex gap-2"><x-text-input name="search" type="search" :value="request('search')" placeholder="Search products or supplies..." aria-label="Search stock" maxlength="100" class="!py-2" /><x-button type="submit" variant="secondary" class="!py-2 !text-xs">Search</x-button>@if(request()->filled('search'))<x-button as="a" :href="route('staff.inventory')" variant="secondary" class="!py-2 !text-xs">Clear</x-button>@endif</form>
    <div class="grid items-start gap-4 xl:grid-cols-2">
        <x-card padding="p-4"><h2 class="mb-3 text-base font-semibold">Products</h2><div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr class="border-b border-slate-200 text-slate-500 dark:border-white/10"><th class="py-2">Product / Size</th><th class="px-2 py-2">Available</th><th class="py-2">Status</th></tr></thead><tbody>
            @forelse($products as $product)<tr class="border-b border-slate-100 dark:border-white/5"><td class="py-3">{{ $product->name }} / {{ $product->size }}</td><td class="px-2 py-3">{{ $product->stock_quantity }} {{ $product->stock_unit_label }}</td><td class="py-3"><x-badge :color="$product->stock_quantity <= 0 ? 'danger' : ($product->is_low_stock ? 'warning' : 'success')">{{ $product->stock_quantity <= 0 ? 'Out of stock' : ($product->is_low_stock ? 'Low stock' : 'Available') }}</x-badge></td></tr>@empty<tr><td colspan="3" class="py-5 text-slate-500">No products found.</td></tr>@endforelse
        </tbody></table></div><div class="mt-3">{{ $products->links() }}</div></x-card>
        <x-card padding="p-4"><h2 class="mb-3 text-base font-semibold">Supplies</h2><div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr class="border-b border-slate-200 text-slate-500 dark:border-white/10"><th class="py-2">Supply</th><th class="px-2 py-2">Available</th><th class="py-2">Status</th></tr></thead><tbody>
            @forelse($supplies as $supply)<tr class="border-b border-slate-100 dark:border-white/5"><td class="py-3">{{ $supply->name }}</td><td class="px-2 py-3">{{ number_format($supply->quantity, 2) }} {{ $supply->unit }}</td><td class="py-3"><x-badge :color="$supply->is_out_of_stock ? 'danger' : ($supply->is_low_stock ? 'warning' : 'success')">{{ $supply->is_out_of_stock ? 'Out of stock' : ($supply->is_low_stock ? 'Low stock' : 'Available') }}</x-badge></td></tr>@empty<tr><td colspan="3" class="py-5 text-slate-500">No supplies found.</td></tr>@endforelse
        </tbody></table></div><div class="mt-3">{{ $supplies->links() }}</div></x-card>
    </div>
</x-layouts.app>
