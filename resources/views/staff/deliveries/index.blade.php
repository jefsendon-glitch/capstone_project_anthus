<x-layouts.app title="Delivery Orders" :heading="'Delivery Orders'" :subheading="'Manage customer orders from confirmation through fulfillment'">
    @if($errors->any())
        <div role="alert" class="mb-3 rounded-xl bg-red-50 p-3 text-sm text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif
    @push('admin-reset-actions')
    @if(auth()->user()->isAdmin())
        <x-reset-dot :action="route('deliveries.reset')" title="Reset Delivery Orders" description="Deletes all delivery orders and their items, including pending orders and records outside the current filters. Sales, payments and inventory remain. This cannot be undone." />
    @endif
    @endpush
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach(['pending' => 'Pending', 'confirmed' => 'Confirmed', 'outForDelivery' => 'Out for Delivery', 'today' => 'Due Today'] as $key => $label)
            <x-stat-card variant="tinted" compact :label="$label" :value="number_format($deliveryStats[$key])" :icon="['pending' => 'clock', 'confirmed' => 'check-circle', 'outForDelivery' => 'truck', 'today' => 'calendar'][$key]" :color="['pending' => 'warning', 'confirmed' => 'success', 'outForDelivery' => 'delivery', 'today' => 'primary'][$key]" />
        @endforeach
    </div>

    <x-card class="mt-4" padding="p-4">
        <form method="GET" class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_11rem_11rem_auto]">
            <div>
                <x-input-label for="delivery-search" value="Search Delivery Orders" />
                <div class="relative"><x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" /><x-text-input id="delivery-search" type="search" name="search" value="{{ request('search') }}" placeholder="Search order ID, customer, or product..." class="pl-10" /></div>
            </div>
            <div>
                <x-input-label for="delivery-status" value="Status" />
                <x-select id="delivery-status" name="status"><option value="">All Statuses</option>@foreach(['pending' => 'Pending', 'confirmed' => 'Confirmed', 'out_for_delivery' => 'Out for Delivery', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'] as $status => $label)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>@endforeach</x-select>
            </div>
            <div><x-input-label for="delivery-schedule" value="Schedule" /><x-select id="delivery-schedule" name="schedule"><option value="">All Dates</option>@foreach(['overdue' => 'Overdue', 'today' => 'Today', 'upcoming' => 'Upcoming'] as $value => $label)<option value="{{ $value }}" @selected(request('schedule') === $value)>{{ $label }}</option>@endforeach</x-select></div>
            <div class="flex items-end gap-2"><x-button as="a" :href="route('deliveries.index')" variant="secondary">Clear</x-button><x-button type="submit"><x-icon name="search" class="size-4" /> Filter</x-button></div>
        </form>
    </x-card>

    <x-card class="mt-6" padding="p-0">
        @if($orders->isEmpty())
            <div class="p-6"><x-empty-state icon="truck" title="No delivery orders found" description="Customer advance orders will appear here when they are placed." /></div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead class="bg-slate-50/80 dark:bg-white/[0.03]"><tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"><th class="px-6 py-3.5">Order ID</th><th class="px-6 py-3.5 text-right">Amount</th><th class="px-6 py-3.5">Customer & Address</th><th class="px-6 py-3.5">Order Items</th><th class="px-6 py-3.5">Schedule</th><th class="px-6 py-3.5">Status</th><th class="px-6 py-3.5 text-right">Open</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($orders as $order)
                            <tr class="transition hover:bg-slate-50/80 dark:hover:bg-white/[0.03]">
                                <td class="px-6 py-4"><a href="{{ route('deliveries.show', $order) }}" class="font-mono text-sm font-bold text-primary-600 hover:text-primary-700">{{ $order->order_code }}</a></td>
                                <td class="whitespace-nowrap px-6 py-4 text-right font-mono text-sm tabular-nums text-slate-700 dark:text-slate-300">₱{{ number_format($order->total_amount, 0) }}</td>
                                <td class="px-6 py-4"><p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $order->customer_name }}</p><p class="max-w-56 truncate text-xs text-slate-500 dark:text-slate-400">{{ $order->customer_address }}</p></td>
                                <td class="min-w-56 px-6 py-4 text-sm text-slate-700 dark:text-slate-300"><x-delivery-order-items :order="$order" compact /></td>
                                <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">{{ $order->preferred_delivery_date?->format('M d, Y') ?? 'Not scheduled' }}@if(in_array($order->status, ['pending', 'confirmed', 'out_for_delivery']) && $order->preferred_delivery_date?->lt(today()))<div class="mt-1"><x-badge color="danger">Overdue</x-badge></div>@endif</td>
                                <td class="px-6 py-4"><x-delivery-status-badge :status="$order->status" /></td>
                                <td class="px-6 py-4 text-right"><a href="{{ route('deliveries.show', $order) }}" class="inline-flex rounded-lg p-2 text-slate-500 transition hover:bg-primary-50 hover:text-primary-600 dark:hover:bg-primary-500/10" aria-label="View delivery order {{ $order->order_code }}"><span class="mr-1 text-sm font-semibold">View Order</span><x-icon name="chevron-right" class="size-4" /></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-4 dark:border-white/5">{{ $orders->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
