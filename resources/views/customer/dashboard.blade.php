<x-layouts.app title="My Dashboard" heading="Dashboard" :subheading="'Manage your deliveries, payments, and account balance'">
    <x-portal-nav />
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0"><h2 class="break-words text-lg font-semibold">Welcome, {{ auth()->user()->first_name ?: auth()->user()->name }}</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $station->business_name }}</p></div>
        <x-button as="a" :href="route('customer.orders.create')"><x-icon name="plus" class="size-4" /> Place an Order</x-button>
    </div>
    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        <x-stat-card compact label="Outstanding Balance" value="₱{{ number_format(auth()->user()->credit_balance, 2) }}" :hint="auth()->user()->credit_balance > 0 ? 'Payment can be recorded by staff' : 'Your account is settled'" icon="credit-card" :color="auth()->user()->credit_balance > 0 ? 'warning' : 'success'" />
        <x-stat-card compact label="Total Orders" :value="$totalOrders" hint="All delivery orders" icon="truck" color="primary" />
        <x-stat-card compact label="Active Orders" :value="$pendingOrders" hint="Being processed" icon="clock" color="delivery" />
        <x-stat-card compact label="Total Purchases" value="₱{{ number_format($totalSpent, 2) }}" hint="Recorded cash and credit sales" icon="dollar" color="secondary" />
    </div>

    @if(auth()->user()->credit_status === 'suspended')
        <div class="mb-4 mt-4 rounded-xl border border-danger-200 bg-danger-50 p-4 text-danger-800 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-200">
            <div class="flex gap-3"><x-icon name="alert-triangle" class="mt-0.5 size-5 shrink-0" /><div><p class="font-semibold">Credit access suspended</p><p class="mt-1 text-sm">Your credit account is suspended. Contact the station to review your balance and credit access. Cash payments remain available.</p></div></div>
        </div>
    @endif


    <div class="mt-4 grid items-start gap-4 lg:grid-cols-3">
        <x-card padding="p-4" class="lg:col-span-2">
            <div class="mb-3 flex items-center justify-between gap-3"><h2 class="text-base font-semibold">Current Delivery</h2><a href="{{ route('customer.orders.index') }}" class="shrink-0 text-sm font-semibold text-primary-700 dark:text-primary-300">View Orders</a></div>
            @if($currentOrder)
                <div class="flex flex-wrap items-center justify-between gap-2"><p class="font-mono text-sm font-semibold text-primary-700 dark:text-primary-300">{{ $currentOrder->order_code }}</p><x-delivery-status-badge :status="$currentOrder->status" /></div>
                <p class="mt-3 break-words text-sm font-medium">{{ $currentOrder->items_summary }}</p>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-500 dark:text-slate-400">Delivery Date</dt><dd class="mt-1 font-medium">{{ $currentOrder->preferred_delivery_date?->format('M d, Y') ?? 'Not specified' }}</dd></div>
                    <div><dt class="text-xs text-slate-500 dark:text-slate-400">Order Total</dt><dd class="mt-1 font-semibold tabular-nums">&#8369;{{ number_format($currentOrder->total_amount, 2) }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-slate-500 dark:text-slate-400">Delivery Address</dt><dd class="mt-1 break-words">{{ $currentOrder->customer_address }}</dd></div>
                </dl>
                <div class="mt-4 border-t border-slate-100 pt-3 dark:border-white/10"><x-button as="a" :href="route('customer.orders.show', $currentOrder)" variant="secondary">View Delivery Details <x-icon name="chevron-right" class="size-4" /></x-button>@if($pendingOrders > 1)<p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Showing your latest active order. View Orders to see all {{ $pendingOrders }} active orders.</p>@endif</div>
            @else
                <x-empty-state icon="truck" title="No active deliveries" description="Your next delivery will appear here after you place an order." />
            @endif
        </x-card>
        @include('customer.partials.account-overview')
    </div>

    <div class="mt-4 grid items-start gap-4 lg:grid-cols-2">
        <x-card padding="p-0"><div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-white/10"><div><h2 class="text-base font-semibold text-slate-900 dark:text-white">Recent Orders</h2><p class="text-xs text-slate-500 dark:text-slate-400">Your 5 most recent orders</p></div><a href="{{ route('customer.orders.index') }}" class="text-sm font-semibold text-primary-600 hover:text-primary-700">View all</a></div><div class="divide-y divide-slate-100 dark:divide-white/5">@forelse($recentOrders as $order)<a href="{{ route('customer.orders.show', $order) }}" class="flex items-center justify-between gap-3 px-4 py-3 transition hover:bg-slate-50 dark:hover:bg-white/5"><div class="min-w-0"><p class="font-mono text-sm font-semibold text-primary-700 dark:text-primary-300">{{ $order->order_code }}</p><p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ $order->items_summary }}</p><p class="text-xs text-slate-500 dark:text-slate-400">₱{{ number_format($order->total_amount, 2) }}</p></div><x-delivery-status-badge :status="$order->status" /></a>@empty<div class="p-4"><x-empty-state icon="truck" title="No orders yet" description="Place your first delivery order when you need water." /></div>@endforelse</div></x-card>
        <x-card padding="p-0"><div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-white/10"><div><h2 class="text-base font-semibold text-slate-900 dark:text-white">Recent Payments</h2><p class="text-xs text-slate-500 dark:text-slate-400">Your 5 most recent payments</p></div><a href="{{ route('customer.payments.index') }}" class="text-sm font-semibold text-primary-600 hover:text-primary-700">View all</a></div><div class="divide-y divide-slate-100 dark:divide-white/5">@forelse($recentPayments as $payment)<div class="flex items-center justify-between px-4 py-3"><div><p class="font-mono text-sm font-bold text-success-600 dark:text-success-500">₱{{ number_format($payment->amount, 2) }}</p><p class="text-xs text-slate-500 dark:text-slate-400">{{ $payment->payment_date->format('M d, Y') }}</p></div><span class="rounded-xl bg-success-50 p-2 text-success-600 dark:bg-success-500/10 dark:text-success-500"><x-icon name="check-circle" class="size-4" /></span></div>@empty<div class="p-4"><x-empty-state icon="credit-card" title="No payments yet" description="Recorded payments will appear here." /></div>@endforelse</div></x-card>
    </div>
</x-layouts.app>
