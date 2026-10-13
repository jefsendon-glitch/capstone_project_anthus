<x-layouts.app title="Stock Movements" :heading="'Stock Movements'">
    @push('admin-reset-actions')
    @if(auth()->user()->isAdmin())
        <x-reset-dot :action="route('admin.stock-movements.reset')" title="Reset Stock Movement History" description="Deletes all product, supply and gallon movement records across every date and filter. Stock quantities, customer loans, sales and payments stay unchanged. A local backup is saved first. This cannot be undone from this page." />
    @endif
    @endpush
    <p class="mb-3 text-xs text-slate-500">Products, supplies, and gallon activity recorded by the system. Only additions and removals change total containers.</p>
    @if($errors->any())
        <div role="alert" class="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    <x-card padding="p-3" class="mb-3">
        <form method="GET" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            <div><x-input-label for="movement-item-type" value="Item Type" /><x-select id="movement-item-type" name="item_type"><option value="">All Items</option>@foreach($itemTypes as $value => $label)<option value="{{ $value }}" @selected(($filters['item_type'] ?? '') === $value)>{{ $label }}</option>@endforeach</x-select></div>
            <div><x-input-label for="movement-type" value="Movement Type" /><x-select id="movement-type" name="type"><option value="">All Movements</option>@foreach($movementTypes as $value => $label)<option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>@endforeach</x-select></div>
            <div><x-input-label for="movement-from" value="From" /><x-text-input id="movement-from" type="date" name="date_from" :value="$filters['date_from'] ?? ''" /></div>
            <div><x-input-label for="movement-to" value="To" /><x-text-input id="movement-to" type="date" name="date_to" :value="$filters['date_to'] ?? ''" /></div>
            <div class="flex justify-end gap-2 sm:col-span-2 lg:col-span-4"><x-button as="a" href="{{ route('admin.stock-movements.index') }}" variant="secondary" class="!py-1.5">Clear Filters</x-button><x-button type="submit" class="!py-1.5">Filter</x-button></div>
        </form>
    </x-card>
    <x-card padding="p-3">
        <p class="mb-2 text-xs text-slate-500">{{ number_format($movements->total()) }} movement records</p>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-xs">
                <thead><tr class="border-b border-slate-200">@foreach(['Date', 'Item / Source', 'Movement', 'Stock / Location', 'Quantity', 'Net Change', 'Customer', 'Reference', 'Recorded By', 'Notes'] as $label)<th class="px-2 py-2">{{ $label }}</th>@endforeach</tr></thead>
                <tbody>
                    @forelse($movements as $movement)
                        <tr class="border-b border-slate-100 dark:border-white/10">
                            <td class="whitespace-nowrap px-2 py-2">{{ \Carbon\Carbon::parse($movement->created_at)->format('M d, Y g:i A') }}</td>
                            <td class="px-2 py-2"><p class="font-medium">{{ $movement->item_name }} @if($movement->item_detail) / {{ $movement->item_detail }} @endif</p><p class="text-slate-500">{{ $itemTypes[$movement->item_type] ?? 'Other' }}</p></td>
                            <td class="px-2 py-2">{{ $movementTypes[$movement->type] ?? ucfirst(str_replace('_', ' ', $movement->type)) }}</td>
                            <td class="whitespace-nowrap px-2 py-2">
                                @if($movement->item_type === 'station_gallon')
                                    {{ ucfirst($movement->from_status ?? 'New') }} → {{ ucfirst($movement->to_status) }}
                                @else
                                    {{ $movement->quantity_before !== null ? number_format($movement->quantity_before, 2) : '—' }} → {{ $movement->quantity_after !== null ? number_format($movement->quantity_after, 2) : '—' }}
                                @endif
                            </td>
                            <td class="px-2 py-2 tabular-nums">{{ number_format($movement->moved_quantity ?? abs($movement->quantity_delta), 2) }}</td>
                            <td class="px-2 py-2 font-semibold tabular-nums {{ $movement->quantity_delta > 0 ? 'text-success-600' : ($movement->quantity_delta < 0 ? 'text-danger-600' : 'text-slate-500') }}">{{ $movement->quantity_delta > 0 ? '+' : '' }}{{ number_format($movement->quantity_delta, 2) }}</td>
                            <td class="px-2 py-2">{{ $movement->customer_name ?? '—' }}</td>
                            <td class="px-2 py-2 font-mono">{{ $movement->item_type === 'station_gallon' ? 'GAL-'.$movement->id : ($movement->reference_code ?? '—') }}</td>
                            <td class="px-2 py-2">{{ $movement->recorded_by_name ?? '—' }}</td>
                            <td class="min-w-40 px-2 py-2 text-slate-500">{{ $movement->notes ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="p-4 text-slate-500">No stock movements match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $movements->links() }}</div>
    </x-card>
</x-layouts.app>
