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

<x-layouts.app title="Stock Report" :heading="'Consumable Stock Report'">
    <style>
        @media print {
            .no-print { display: none !important; }
            main { padding: 0 !important; }
        }
    </style>

    <x-card class="no-print mb-6">
        <form method="GET" class="grid gap-4 md:grid-cols-3">
            <div>
                <x-input-label value="Categories" />
                <x-select name="categories[]" multiple size="4">
                    @foreach($availableCategories as $category)
                        <option value="{{ $category }}" @selected(in_array($category, $categories, true))>{{ $categoryLabels[$category] ?? ucfirst(str_replace('_', ' ', $category)) }}</option>
                    @endforeach
                </x-select>
            </div>
            <div>
                <x-input-label value="Stock status" />
                <x-select name="status">
                    <option value="all" @selected($status === 'all')>Complete supply list</option>
                    <option value="low_stock" @selected($status === 'low_stock')>Low-stock items</option>
                    <option value="out_of_stock" @selected($status === 'out_of_stock')>Out-of-stock items</option>
                </x-select>
            </div>
            <div class="flex items-end gap-3">
                <x-button as="a" href="{{ route('admin.consumables.report') }}" variant="secondary">Reset</x-button>
                <x-button type="submit">Generate Report</x-button>
            </div>
        </form>
    </x-card>

    <x-card padding="p-0">
        <div class="flex items-start justify-between gap-4 px-6 py-5">
            <div>
                <h2 class="font-heading text-lg font-bold text-slate-900 dark:text-white">Consumable Stock Report</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Generated {{ now()->format('M d, Y g:i A') }} · {{ $consumables->count() }} {{ Str::plural('supply', $consumables->count()) }}</p>
            </div>
            <button type="button" onclick="window.print()" class="no-print inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700"><x-icon name="clipboard" class="size-4" /> Print</button>
        </div>

        <div class="overflow-x-auto border-t border-slate-100 dark:border-white/5">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        <th class="px-6 py-3">Supply ID</th><th class="px-6 py-3">Supply Name</th><th class="px-6 py-3">Category</th><th class="px-6 py-3">Unit</th><th class="px-6 py-3">Stock</th><th class="px-6 py-3">Reorder Level</th><th class="px-6 py-3">Unit Cost</th><th class="px-6 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                    @forelse($consumables as $consumable)
                        <tr>
                            <td class="px-6 py-3 font-mono text-xs text-slate-500">SUP-{{ str_pad((string) $consumable->id, 4, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-6 py-3 text-sm font-medium text-slate-900 dark:text-white">{{ $consumable->name }}</td>
                            <td class="px-6 py-3 text-sm text-slate-500">{{ $categoryLabels[$consumable->category] ?? ucfirst(str_replace('_', ' ', $consumable->category)) }}</td>
                            <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $consumable->unit }}</td>
                            <td class="px-6 py-3 font-mono text-sm text-slate-600 dark:text-slate-300">{{ rtrim(rtrim($consumable->quantity, '0'), '.') }}</td>
                            <td class="px-6 py-3 font-mono text-sm text-slate-600 dark:text-slate-300">{{ rtrim(rtrim($consumable->low_stock_threshold, '0'), '.') }}</td>
                            <td class="px-6 py-3 font-mono text-sm text-slate-600 dark:text-slate-300">₱{{ number_format((float) $consumable->unit_cost, 2) }}</td>
                            <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $consumable->is_out_of_stock ? 'Out of Stock' : ($consumable->is_low_stock ? 'Low Stock' : 'In Stock') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-6 py-8 text-center text-sm text-slate-500">No supplies match this report.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</x-layouts.app>
