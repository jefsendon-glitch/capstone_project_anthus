<x-layouts.app title="Stock Receiving" heading="Stock Receiving">
    <p class="mb-5 text-sm text-slate-500">Record delivered quantities against outstanding orders to update inventory.</p>
    @include('admin.procurement.filters')
    @foreach($errors->all() as $error)<p class="mb-2 text-sm text-red-600">{{ $error }}</p>@endforeach
    <div class="space-y-5">
        @forelse($orders as $order)
            <x-card>
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div><a href="{{ route('admin.purchase-orders.show', $order) }}" class="font-semibold text-primary-600">{{ $order->po_number }}</a><p class="text-sm text-slate-500">{{ $order->supplier?->name }} · Expected {{ $order->expected_date?->format('M d, Y') ?? 'No date set' }}</p></div>
                    <x-badge :color="$order->status === 'partially_received' ? 'warning' : 'primary'">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</x-badge>
                </div>
                <form method="POST" action="{{ route('admin.purchase-orders.receive', $order) }}">
                    @csrf
                    <input type="hidden" name="receipt_token" value="{{ old('receipt_order_id') == $order->id ? old('receipt_token', (string) \Illuminate\Support\Str::uuid()) : \Illuminate\Support\Str::uuid() }}">
                    <input type="hidden" name="receipt_order_id" value="{{ $order->id }}">
                    <div class="overflow-x-auto"><table class="mb-4 min-w-full text-left text-sm">
                        <thead><tr class="text-slate-500"><th class="py-2">Supply / Material</th><th>Ordered</th><th>Received</th><th>Remaining</th><th>Delivered now</th></tr></thead>
                        <tbody>
                            @foreach($order->items as $item)
                                @if($item->remaining_quantity > 0)
                                    <tr class="border-t border-slate-200 dark:border-white/10">
                                        <td class="py-3">{{ $item->itemable?->name ?? 'Archived or deleted item' }}</td><td>{{ number_format($item->quantity_ordered, 2) }}</td><td>{{ number_format($item->quantity_received, 2) }}</td><td>{{ number_format($item->remaining_quantity, 2) }}</td>
                                        <td class="w-40"><input type="hidden" name="items[{{ $loop->index }}][purchase_order_item_id]" value="{{ $item->id }}"><x-text-input type="number" step="1" min="0" :max="$item->remaining_quantity" name="items[{{ $loop->index }}][quantity_received]" :value="old('receipt_order_id') == $order->id ? old('items.'.$loop->index.'.quantity_received', 0) : 0" required /></td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table></div>
                    <x-button variant="success" type="submit">Record Delivery</x-button>
                </form>
            </x-card>
        @empty
            <x-card><x-empty-state icon="clipboard" title="No outstanding deliveries" description="There are no purchase orders awaiting stock receiving." /></x-card>
        @endforelse
    </div>
    <div class="mt-5">{{ $orders->links() }}</div>
</x-layouts.app>
