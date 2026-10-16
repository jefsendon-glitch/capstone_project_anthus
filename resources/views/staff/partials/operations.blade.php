<div class="mt-4 grid items-start gap-4 lg:grid-cols-2">
    <x-card padding="p-4">
        <details>
            <summary class="cursor-pointer text-sm font-semibold">Inventory Alerts ({{ $lowStockProducts + $lowStockConsumables }})</summary>
            <p class="my-3 text-xs text-slate-500">Current station stock. Admin manages restocking and stock corrections.</p>
            <div class="flex flex-wrap gap-2"><x-button as="a" :href="route('admin.products.index')" variant="info" class="!py-1.5 !text-xs">View Products</x-button><x-button as="a" :href="route('staff.inventory')" variant="secondary" class="!py-1.5 !text-xs">View Supplies</x-button></div>
            <h3 class="mb-1 mt-3 text-xs font-semibold">Products Requiring Attention</h3>
            @forelse($stockAlerts as $product)<div class="flex items-center justify-between gap-2 border-b border-slate-100 py-2 text-xs dark:border-white/5"><span>{{ $product->name }} / {{ $product->size }}</span><span class="shrink-0 font-semibold text-amber-700 dark:text-amber-300">{{ $product->stock_quantity }} {{ $product->stock_unit_label }}</span></div>@empty<p class="py-2 text-xs text-slate-500">No product stock alerts.</p>@endforelse
            <h3 class="mb-1 mt-3 text-xs font-semibold">Supplies Requiring Attention</h3>
            @forelse($supplyAlerts as $supply)<div class="flex items-center justify-between gap-2 border-b border-slate-100 py-2 text-xs dark:border-white/5"><span>{{ $supply->name }}</span><span class="shrink-0 font-semibold text-amber-700 dark:text-amber-300">{{ number_format($supply->quantity, 2) }} {{ $supply->unit }}</span></div>@empty<p class="py-2 text-xs text-slate-500">No supply stock alerts.</p>@endforelse
            <p class="mt-3 text-xs text-slate-500">Up to 5 alerts per category. View inventory for the full list.</p>
        </details>
    </x-card>
    <x-card padding="p-4">
        <details>
            <summary class="cursor-pointer text-sm font-semibold">My Recent Collections</summary>
            <div class="mt-3 divide-y divide-slate-100 dark:divide-white/5">
                @forelse($recentCollections as $collection)
                    <div class="flex items-center justify-between gap-2 py-2 text-xs"><div><p class="font-medium">{{ $collection->customer?->name ?? $collection->customer_name }}</p><p class="mt-1 text-slate-500">{{ $collection->payment_date->format('M d, Y') }}</p></div><span class="font-semibold tabular-nums text-primary-700 dark:text-primary-300">₱{{ number_format($collection->amount, 2) }}</span></div>
                @empty<p class="py-2 text-xs text-slate-500">No customer collections recorded under your account yet.</p>@endforelse
            </div>
            <x-button as="a" :href="route('payments.index')" variant="info" class="mt-3 !py-1.5 !text-xs">Customer Payments</x-button>
        </details>
    </x-card>
</div>
