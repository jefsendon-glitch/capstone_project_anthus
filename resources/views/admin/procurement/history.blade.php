<x-layouts.app title="Purchase History" heading="Purchase History">
    <p class="mb-5 text-sm text-slate-500">Review purchase orders, supplier transactions, and the cost of quantities received.</p>
    @include('admin.procurement.filters', ['history' => true])
    @foreach($errors->all() as $error)<p class="mb-2 text-sm text-red-600">{{ $error }}</p>@endforeach
    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <x-card><p class="text-sm text-slate-500">Ordered value</p><p class="text-2xl font-semibold">₱{{ number_format($totals->ordered_cost, 2) }}</p></x-card>
        <x-card><p class="text-sm text-slate-500">Received value</p><p class="text-2xl font-semibold">₱{{ number_format($totals->received_cost, 2) }}</p></x-card>
    </div>
    <p class="mb-4 text-xs text-slate-500">Totals follow selected filters and exclude draft and cancelled orders. Received value reflects stock receipts, rather than payments.</p>
    <x-card>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm">
            <thead><tr class="text-slate-500"><th class="py-3">Purchase order</th><th>Supplier</th><th>Ordered date</th><th>Status</th><th>Ordered value</th><th>Received value</th></tr></thead>
            <tbody>
                @forelse($orders as $order)
                    <tr class="border-t border-slate-200 dark:border-white/10">
                        <td class="py-4"><a class="font-semibold text-primary-600" href="{{ route('admin.purchase-orders.show', $order) }}">{{ $order->po_number }}</a><p class="text-xs text-slate-500">{{ $order->items->count() }} line items</p></td>
                        <td>{{ $order->supplier?->name ?? 'Unavailable supplier' }}</td><td>{{ $order->ordered_at?->format('M d, Y') ?? '—' }}</td><td>{{ ucfirst(str_replace('_', ' ', $order->status)) }}</td>
                        <td>₱{{ number_format($order->items->sum(fn ($item) => $item->quantity_ordered * $item->unit_cost), 2) }}</td><td>₱{{ number_format($order->items->sum(fn ($item) => $item->quantity_received * $item->unit_cost), 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-slate-500">No purchases match the selected filters.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="mt-5">{{ $orders->links() }}</div>
    </x-card>
</x-layouts.app>
