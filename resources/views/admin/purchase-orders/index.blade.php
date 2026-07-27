@php
$statusColors = ['draft' => 'slate', 'ordered' => 'primary', 'partially_received' => 'warning', 'received' => 'success', 'cancelled' => 'danger'];
@endphp

<x-layouts.app title="Purchase Orders" :heading="'Purchase Orders'">
    <div class="mb-6 flex justify-end">
        @can('create', \App\Models\PurchaseOrder::class)
            <x-button as="a" href="{{ route('admin.purchase-orders.create') }}">
                <x-icon name="plus" class="size-4" /> New Purchase Order
            </x-button>
        @endcan
    </div>

    <x-card padding="p-0">
        @if($purchaseOrders->isEmpty())
            <div class="p-6">
                <x-empty-state icon="clipboard" title="No purchase orders yet" description="Create a purchase order to restock from your suppliers." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-6 py-3">PO Number</th>
                            <th class="px-6 py-3">Supplier</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Expected Date</th>
                            <th class="px-6 py-3 text-right">Items</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($purchaseOrders as $purchaseOrder)
                            <tr>
                                <td class="px-6 py-3">
                                    <a href="{{ route('admin.purchase-orders.show', $purchaseOrder) }}" class="font-mono text-sm font-semibold text-primary-600 hover:text-primary-700">{{ $purchaseOrder->po_number }}</a>
                                </td>
                                <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $purchaseOrder->supplier->name }}</td>
                                <td class="px-6 py-3">
                                    <x-badge :color="$statusColors[$purchaseOrder->status] ?? 'slate'">{{ ucfirst(str_replace('_', ' ', $purchaseOrder->status)) }}</x-badge>
                                </td>
                                <td class="px-6 py-3 text-sm text-slate-500 dark:text-slate-400">{{ $purchaseOrder->expected_date?->format('M d, Y') ?? '—' }}</td>
                                <td class="px-6 py-3 text-right text-sm text-slate-500 dark:text-slate-400">{{ $purchaseOrder->items_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-4 dark:border-white/5">
                {{ $purchaseOrders->links() }}
            </div>
        @endif
    </x-card>
</x-layouts.app>
