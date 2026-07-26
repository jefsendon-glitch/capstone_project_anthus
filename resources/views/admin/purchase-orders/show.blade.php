@php
$statusColors = ['draft' => 'slate', 'ordered' => 'primary', 'partially_received' => 'warning', 'received' => 'success', 'cancelled' => 'danger'];
$canReceive = in_array($purchaseOrder->status, ['ordered', 'partially_received']);
@endphp

<x-layouts.app title="Purchase Order" :heading="$purchaseOrder->po_number">
    <div class="mb-6 flex items-center justify-between">
        <x-badge :color="$statusColors[$purchaseOrder->status] ?? 'slate'">{{ ucfirst(str_replace('_', ' ', $purchaseOrder->status)) }}</x-badge>
        <div class="flex gap-3">
            @if($purchaseOrder->status === 'draft')
                @can('update', $purchaseOrder)
                    <x-button as="a" href="{{ route('admin.purchase-orders.edit', $purchaseOrder) }}" variant="secondary">
                        <x-icon name="pencil" class="size-4" /> Edit
                    </x-button>
                @endcan
                @can('delete', $purchaseOrder)
                    <form method="POST" action="{{ route('admin.purchase-orders.destroy', $purchaseOrder) }}" onsubmit="return confirm('Delete this purchase order?')">
                        @csrf
                        @method('delete')
                        <x-button type="submit" variant="danger">
                            <x-icon name="trash" class="size-4" /> Delete
                        </x-button>
                    </form>
                @endcan
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Line Items</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="py-2">Item</th>
                            <th class="py-2 text-right">Ordered</th>
                            <th class="py-2 text-right">Received</th>
                            <th class="py-2 text-right">Unit Cost</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($purchaseOrder->items as $item)
                            <tr>
                                <td class="py-2 text-sm font-medium text-slate-900 dark:text-white">{{ $item->itemable?->name ?? 'Deleted item' }}</td>
                                <td class="py-2 text-right font-mono text-sm text-slate-600 dark:text-slate-300">{{ rtrim(rtrim($item->quantity_ordered, '0'), '.') }}</td>
                                <td class="py-2 text-right font-mono text-sm text-slate-600 dark:text-slate-300">{{ rtrim(rtrim($item->quantity_received, '0'), '.') }}</td>
                                <td class="py-2 text-right font-mono text-sm text-slate-900 dark:text-white">₱{{ number_format($item->unit_cost, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($canReceive)
                @can('receive', $purchaseOrder)
                    <div class="mt-6 border-t border-slate-100 pt-6 dark:border-white/10">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Receive Stock</h3>
                        <form method="POST" action="{{ route('admin.purchase-orders.receive', $purchaseOrder) }}" class="mt-3 space-y-3">
                            @csrf
                            @foreach($purchaseOrder->items as $item)
                                @if($item->remaining_quantity > 0)
                                    <div class="flex items-center gap-3">
                                        <input type="hidden" name="items[{{ $loop->index }}][purchase_order_item_id]" value="{{ $item->id }}">
                                        <span class="w-40 shrink-0 truncate text-sm text-slate-700 dark:text-slate-300">{{ $item->itemable?->name }}</span>
                                        <x-text-input
                                            type="number" step="0.01" min="0" :max="$item->remaining_quantity"
                                            name="items[{{ $loop->index }}][quantity_received]"
                                            placeholder="Qty received"
                                            :value="$item->remaining_quantity"
                                        />
                                        <span class="shrink-0 text-xs text-slate-400">of {{ rtrim(rtrim($item->remaining_quantity, '0'), '.') }} remaining</span>
                                    </div>
                                @endif
                            @endforeach
                            <x-button type="submit">Record Receipt</x-button>
                        </form>
                    </div>
                @endcan
            @endif
        </x-card>

        <x-card>
            <h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Details</h2>
            <div class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-slate-400">Supplier</span><span class="text-slate-900 dark:text-white">{{ $purchaseOrder->supplier->name }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Ordered</span><span class="text-slate-900 dark:text-white">{{ $purchaseOrder->ordered_at?->format('M d, Y') ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Expected</span><span class="text-slate-900 dark:text-white">{{ $purchaseOrder->expected_date?->format('M d, Y') ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Created by</span><span class="text-slate-900 dark:text-white">{{ $purchaseOrder->createdBy?->name ?? '—' }}</span></div>
                @if($purchaseOrder->notes)
                    <div class="border-t border-slate-100 pt-3 dark:border-white/10">
                        <p class="text-slate-400">Notes</p>
                        <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $purchaseOrder->notes }}</p>
                    </div>
                @endif
            </div>
        </x-card>
    </div>
</x-layouts.app>
