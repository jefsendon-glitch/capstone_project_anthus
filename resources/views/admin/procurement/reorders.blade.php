<x-layouts.app title="Reorder Monitoring" heading="Reorder Monitoring">
    <p class="mb-5 text-sm text-slate-500">Supplies at or below their reorder level. Check incoming quantities before placing another order.</p>
    @include('admin.procurement.filters')
    <x-card>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm">
            <thead><tr class="text-slate-500"><th class="py-3">Supply</th><th>Supplier</th><th>On hand</th><th>Reorder level</th><th>Incoming</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($supplies as $supply)
                    <tr class="border-t border-slate-200 dark:border-white/10">
                        <td class="py-4 font-medium">{{ $supply->name }}</td><td>{{ $supply->supplier?->name ?? 'Unassigned' }}</td><td>{{ number_format($supply->quantity, 2) }} {{ $supply->unit }}</td><td>{{ number_format($supply->low_stock_threshold, 2) }}</td><td>{{ number_format($pending[$supply->id] ?? 0, 2) }}</td>
                        <td><x-badge :color="$supply->is_out_of_stock ? 'danger' : 'warning'">{{ $supply->is_out_of_stock ? 'Out of stock' : 'Low stock' }}</x-badge></td>
                        <td>
                            @if($supply->supplier?->is_active)
                                <a class="text-primary-600" href="{{ route('admin.purchase-orders.create', ['consumable_id' => $supply->id]) }}">Create PO</a>
                            @else
                                <a class="text-primary-600" href="{{ route('admin.consumables.edit', $supply) }}">Assign active supplier</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-slate-500">No supplies need replenishment for this filter.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="mt-5">{{ $supplies->links() }}</div>
    </x-card>
</x-layouts.app>
