@php
$typeColors = [
    'initial' => 'slate',
    'restock' => 'success',
    'production' => 'success',
    'purchase_receive' => 'success',
    'returned' => 'delivery',
    'adjustment_increase' => 'warning',
    'adjustment_decrease' => 'warning',
    'damage' => 'danger',
    'missing' => 'danger',
    'sale_deduction' => 'primary',
];
$itemTypes = ['product' => 'Product', 'consumable' => 'Supply', 'gallon_stock' => 'Gallon Stock'];
$movementTypes = ['initial', 'restock', 'adjustment_increase', 'adjustment_decrease', 'damage', 'missing', 'returned', 'sale_deduction', 'production', 'purchase_receive'];
@endphp

<x-layouts.app title="Stock Movement History" :heading="'Stock Movement History'">
    <x-card class="mb-6">
        <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <x-input-label value="Item type" />
                <x-select name="item_type">
                    <option value="">All items</option>
                    @foreach($itemTypes as $value => $label)
                        <option value="{{ $value }}" @selected($itemType === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </div>
            <div>
                <x-input-label value="Movement type" />
                <x-select name="type">
                    <option value="">All types</option>
                    @foreach($movementTypes as $value)
                        <option value="{{ $value }}" @selected($type === $value)>{{ ucfirst(str_replace('_', ' ', $value)) }}</option>
                    @endforeach
                </x-select>
            </div>
            <div>
                <x-input-label value="From" />
                <x-text-input type="text" name="date_from" data-flatpickr :value="$dateFrom" />
            </div>
            <div>
                <x-input-label value="To" />
                <x-text-input type="text" name="date_to" data-flatpickr :value="$dateTo" />
            </div>
            <div class="sm:col-span-2 lg:col-span-4 flex justify-end gap-3">
                <x-button as="a" href="{{ route('admin.stock-movements.index') }}" variant="secondary">Reset</x-button>
                <x-button type="submit">Filter</x-button>
            </div>
        </form>
    </x-card>

    <x-card padding="p-0">
        @if($movements->isEmpty())
            <div class="p-6">
                <x-empty-state icon="swap" title="No stock movements yet" description="Every add, adjustment, restock, and receipt will show up here." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-6 py-3">Item</th>
                            <th class="px-6 py-3">Type</th>
                            <th class="px-6 py-3">Before → After</th>
                            <th class="px-6 py-3">Delta</th>
                            <th class="px-6 py-3">By</th>
                            <th class="px-6 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($movements as $movement)
                            <tr>
                                <td class="px-6 py-3 text-sm font-medium text-slate-900 dark:text-white">
                                    @if($movement->movable instanceof \App\Models\GallonStock)
                                        {{ $movement->movable->product?->name }} — {{ $movement->movable->status_label }}
                                    @else
                                        {{ $movement->movable?->name ?? 'Deleted item' }}
                                    @endif
                                </td>
                                <td class="px-6 py-3">
                                    <x-badge :color="$typeColors[$movement->type] ?? 'slate'">{{ ucfirst(str_replace('_', ' ', $movement->type)) }}</x-badge>
                                </td>
                                <td class="px-6 py-3 font-mono text-sm text-slate-600 dark:text-slate-300">
                                    {{ $movement->quantity_before ?? '—' }} → {{ $movement->quantity_after ?? '—' }}
                                </td>
                                <td class="px-6 py-3 font-mono text-sm font-semibold {{ $movement->quantity_delta >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                    {{ $movement->quantity_delta >= 0 ? '+' : '' }}{{ rtrim(rtrim($movement->quantity_delta, '0'), '.') }}
                                </td>
                                <td class="px-6 py-3 text-sm text-slate-500 dark:text-slate-400">{{ $movement->recordedBy?->name ?? '—' }}</td>
                                <td class="px-6 py-3 text-sm text-slate-500 dark:text-slate-400">{{ $movement->created_at->format('M d, Y g:i A') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-4 dark:border-white/5">
                {{ $movements->links() }}
            </div>
        @endif
    </x-card>
</x-layouts.app>
