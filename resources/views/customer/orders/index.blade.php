<x-layouts.app title="My Orders" :heading="'My Orders'">
    <div class="mb-6 flex items-center justify-between gap-4">
        <form method="GET">
            <x-select name="status" onchange="this.form.submit()" class="w-auto">
                <option value="">All Statuses</option>
                @foreach(['pending' => 'Pending', 'confirmed' => 'Confirmed', 'out_for_delivery' => 'Out for Delivery', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
        </form>

        <x-button as="a" href="{{ route('customer.orders.create') }}">
            <x-icon name="plus" class="size-4" /> Place an Order
        </x-button>
    </div>

    <x-card padding="p-0">
        @if($orders->isEmpty())
            <div class="p-6">
                <x-empty-state icon="truck" title="No orders yet" description="Place an advance delivery order and track its status here." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-6 py-3">Code</th>
                            <th class="px-6 py-3">Product</th>
                            <th class="px-6 py-3">Total</th>
                            <th class="px-6 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($orders as $order)
                            <tr>
                                <td class="px-6 py-3 text-sm font-medium text-slate-900 dark:text-white">
                                    <a href="{{ route('customer.orders.show', $order) }}" class="hover:text-primary-600">{{ $order->order_code }}</a>
                                </td>
                                <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $order->product_name }} × {{ $order->quantity }}</td>
                                <td class="px-6 py-3 font-mono text-sm text-slate-900 dark:text-white">₱{{ number_format($order->total_amount, 2) }}</td>
                                <td class="px-6 py-3"><x-delivery-status-badge :status="$order->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-4 dark:border-white/5">
                {{ $orders->links() }}
            </div>
        @endif
    </x-card>
</x-layouts.app>
