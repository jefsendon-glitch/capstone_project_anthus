<x-layouts.app title="Delivery Orders" :heading="'Delivery Orders'" :subheading="'Manage customer orders from confirmation through fulfillment'">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card variant="solid" label="Pending" :value="$deliveryStats['pending']" hint="Need confirmation" icon="clock" color="warning" />
        <x-stat-card variant="solid" label="Confirmed" :value="$deliveryStats['confirmed']" hint="Ready to dispatch" icon="check-circle" color="primary" />
        <x-stat-card variant="solid" label="Out for Delivery" :value="$deliveryStats['outForDelivery']" hint="Currently in transit" icon="truck" color="delivery" />
        <x-stat-card variant="solid" label="Due Today" :value="$deliveryStats['today']" hint="Preferred delivery date" icon="calendar" color="secondary" />
    </div>

    <x-card class="mt-6" padding="p-4">
        <form method="GET" class="grid gap-3 md:grid-cols-[minmax(0,1fr)_12rem_auto]">
            <div class="relative"><x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" /><x-text-input type="search" name="search" value="{{ request('search') }}" placeholder="Search code, customer, or product..." class="pl-10" /></div>
            <x-select name="status"><option value="">All statuses</option>@foreach(['pending', 'confirmed', 'out_for_delivery', 'delivered', 'cancelled'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>@endforeach</x-select>
            <div class="flex gap-2"><x-button type="submit"><x-icon name="search" class="size-4" /> Filter</x-button>@if(request()->hasAny(['search', 'status']))<x-button as="a" href="{{ route('deliveries.index') }}" variant="secondary">Reset</x-button>@endif</div>
        </form>
    </x-card>

    <x-card class="mt-6" padding="p-0">
        @if($orders->isEmpty())
            <div class="p-6"><x-empty-state icon="truck" title="No delivery orders found" description="Customer advance orders will appear here when they are placed." /></div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead class="bg-slate-50/80 dark:bg-white/[0.03]"><tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"><th class="px-6 py-3.5">Order</th><th class="px-6 py-3.5">Customer & Address</th><th class="px-6 py-3.5">Product</th><th class="px-6 py-3.5">Schedule</th><th class="px-6 py-3.5">Status</th><th class="px-6 py-3.5 text-right">Open</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($orders as $order)
                            <tr class="transition hover:bg-slate-50/80 dark:hover:bg-white/[0.03]">
                                <td class="px-6 py-4"><a href="{{ route('deliveries.show', $order) }}" class="font-mono text-sm font-bold text-primary-600 hover:text-primary-700">{{ $order->order_code }}</a><p class="mt-1 text-xs text-slate-400">₱{{ number_format($order->total_amount, 2) }}</p></td>
                                <td class="px-6 py-4"><p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $order->customer_name }}</p><p class="max-w-56 truncate text-xs text-slate-500 dark:text-slate-400">{{ $order->customer_address }}</p></td>
                                <td class="px-6 py-4 text-sm text-slate-700 dark:text-slate-300">{{ $order->items_summary }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">{{ $order->preferred_delivery_date?->format('M d, Y') ?? 'Not scheduled' }}</td>
                                <td class="px-6 py-4"><x-delivery-status-badge :status="$order->status" /></td>
                                <td class="px-6 py-4 text-right"><a href="{{ route('deliveries.show', $order) }}" class="inline-flex rounded-lg p-2 text-slate-500 transition hover:bg-primary-50 hover:text-primary-600 dark:hover:bg-primary-500/10" aria-label="Open delivery order"><x-icon name="chevron-right" class="size-5" /></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-4 dark:border-white/5">{{ $orders->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
