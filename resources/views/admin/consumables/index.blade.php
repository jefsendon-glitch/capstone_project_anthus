@php
    $categoryLabels = [
        'filter_treatment_materials' => 'Filter Cartridges & Treatment Materials',
        'bottle_caps_seals' => 'Bottle Caps & Seals',
        'labels_packaging' => 'Labels & Packaging',
        'cleaning_sanitation' => 'Cleaning & Sanitation Supplies',
        'water_filters' => 'Filter Cartridges & Treatment Materials',
        'treatment_materials' => 'Filter Cartridges & Treatment Materials',
        'uv_lamps' => 'Filter Cartridges & Treatment Materials',
        'bottle_caps' => 'Bottle Caps & Seals',
        'bottle_seals' => 'Bottle Caps & Seals',
        'labels' => 'Labels & Packaging',
        'plastic_bags' => 'Labels & Packaging',
        'cleaning_supplies' => 'Cleaning & Sanitation Supplies',
    ];
@endphp

<x-layouts.app title="Consumable Inventory" :heading="'Consumable Inventory'">
    <x-card class="mb-6">
        <p class="text-sm text-slate-600 dark:text-slate-300">Manage materials used in water production and daily operations.</p>
        <ul class="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-2 dark:text-slate-300">
            <li>• Filter cartridges and treatment materials</li>
            <li>• Bottle caps and seals</li>
            <li>• Labels and packaging</li>
            <li>• Cleaning and sanitation supplies</li>
            <li>• Stock quantity and reorder level</li>
        </ul>
    </x-card>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total Supplies" :value="$summary['totalSupplies']" icon="archive" color="primary" hint="Active consumable records" />
        <x-stat-card label="Total Stock" :value="number_format((float) $summary['totalStock'], 2)" icon="cube" color="success" hint="Across all supply units" />
        <x-stat-card label="Low Stock" :value="$summary['lowStock']" icon="alert-triangle" color="warning" hint="At or below reorder level" />
        <x-stat-card label="Out of Stock" :value="$summary['outOfStock']" icon="x-circle" color="danger" hint="Needs replenishment" />
    </div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="inline-flex rounded-xl bg-slate-100 p-1 dark:bg-white/5">
            <a href="{{ route('admin.consumables.index') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ ! $archived ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-800 dark:text-white' : 'text-slate-500 dark:text-slate-400' }}">Active</a>
            <a href="{{ route('admin.consumables.index', ['archived' => 1]) }}" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ $archived ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-800 dark:text-white' : 'text-slate-500 dark:text-slate-400' }}">Archived</a>
        </div>
        <div class="flex items-center gap-3">
            <x-button as="a" href="{{ route('admin.stock-movements.index', ['item_type' => 'consumable']) }}" variant="secondary"><x-icon name="swap" class="size-4" /> Stock History</x-button>
            @if(! $archived)
                @can('create', \App\Models\Consumable::class)
                    <x-button as="a" href="{{ route('admin.consumables.create') }}"><x-icon name="plus" class="size-4" /> Add Supply</x-button>
                @endcan
            @endif
        </div>
    </div>

    <x-card padding="p-0">
        @if($consumables->isEmpty())
            <div class="p-6"><x-empty-state icon="archive" :title="$archived ? 'No archived supplies' : 'No supplies yet'" description="Add materials used in water production and daily operations." /></div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-6 py-3">Supply ID</th><th class="px-6 py-3">Supply Name</th><th class="px-6 py-3">Category</th><th class="px-6 py-3">Unit</th><th class="px-6 py-3">Stock Quantity</th><th class="px-6 py-3">Reorder Level</th><th class="px-6 py-3">Unit Cost</th><th class="px-6 py-3">Stock Status</th><th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($consumables as $consumable)
                            <tr x-data="{ open: false }">
                                <td class="px-6 py-3 font-mono text-xs text-slate-500 dark:text-slate-400">SUP-{{ str_pad((string) $consumable->id, 4, '0', STR_PAD_LEFT) }}</td>
                                <td class="px-6 py-3 text-sm font-medium text-slate-900 dark:text-white">{{ $consumable->name }}</td>
                                <td class="px-6 py-3 text-sm text-slate-500 dark:text-slate-400">{{ $categoryLabels[$consumable->category] ?? ucfirst(str_replace('_', ' ', $consumable->category)) }}</td>
                                <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $consumable->unit }}</td>
                                <td class="px-6 py-3 font-mono text-sm text-slate-600 dark:text-slate-300">{{ rtrim(rtrim($consumable->quantity, '0'), '.') }}</td>
                                <td class="px-6 py-3 font-mono text-sm text-slate-600 dark:text-slate-300">{{ rtrim(rtrim($consumable->low_stock_threshold, '0'), '.') }}</td>
                                <td class="px-6 py-3 font-mono text-sm text-slate-600 dark:text-slate-300">₱{{ number_format((float) $consumable->unit_cost, 2) }}</td>
                                <td class="px-6 py-3">
                                    @if($consumable->is_out_of_stock)<x-badge color="danger">Out of Stock</x-badge>
                                    @elseif($consumable->is_low_stock)<x-badge color="warning">Low Stock</x-badge>
                                    @else<x-badge color="success">In Stock</x-badge>@endif
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        @if($archived)
                                            @can('restore', $consumable)
                                                <form method="POST" action="{{ route('admin.consumables.restore', $consumable) }}">@csrf<button type="submit" class="text-slate-500 hover:text-success-600" title="Restore supply"><x-icon name="check-circle" class="size-4" /></button></form>
                                            @endcan
                                        @else
                                            @can('restock', \App\Models\Consumable::class)
                                                <button type="button" x-on:click="open = true" class="text-slate-500 hover:text-primary-600" title="Add stock"><x-icon name="plus" class="size-4" /></button>
                                            @endcan
                                            @can('update', $consumable)
                                                <x-stock-action-modal :action-url="route('admin.consumables.stock.adjust', $consumable)" :title="'Adjust Stock — '.$consumable->name" mode="adjust" adjustment-uses-direction trigger-icon="swap" />
                                                <a href="{{ route('admin.consumables.edit', $consumable) }}" class="text-slate-500 hover:text-primary-600" title="Edit supply"><x-icon name="pencil" class="size-4" /></a>
                                            @endcan
                                            @can('delete', $consumable)
                                                <form method="POST" action="{{ route('admin.consumables.destroy', $consumable) }}" onsubmit="return confirm('Archive this supply?')">@csrf @method('delete')<button type="submit" class="text-slate-500 hover:text-danger-600" title="Archive supply"><x-icon name="archive" class="size-4" /></button></form>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                                @if(! $archived)
                                    <template x-teleport="body"><div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"><div class="fixed inset-0 bg-slate-900/50" x-on:click="open = false"></div><div class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900"><h3 class="text-base font-semibold text-slate-900 dark:text-white">Restock {{ $consumable->name }}</h3><form method="POST" action="{{ route('admin.consumables.restock', $consumable) }}" class="mt-4 space-y-3">@csrf<div><x-input-label value="Quantity to add" /><x-text-input type="number" step="0.01" min="0.01" name="quantity_added" required /></div><div><x-input-label value="Cost (optional)" /><x-text-input type="number" step="0.01" min="0" name="cost" /></div><x-button type="submit" class="w-full">Add Stock</x-button></form></div></div></template>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-4 dark:border-white/5">{{ $consumables->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
