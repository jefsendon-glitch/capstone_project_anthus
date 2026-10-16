<x-layouts.app title="Staff Dashboard" :heading="'Dashboard'" :subheading="now()->format('l, F j, Y')">
    <x-portal-nav />
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0"><h2 class="break-words text-lg font-semibold">Welcome, {{ auth()->user()->first_name ?: auth()->user()->name }}</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Review today's activity and manage station transactions.</p></div>
        <x-button as="a" :href="route('pos.create')"><x-icon name="plus" class="size-4" /> Record a Sale</x-button>
    </div>
    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        <x-stat-card compact label="My Sales Today" value="₱{{ number_format($salesToday, 2) }}" :hint="$transactionsToday.' transactions recorded'" icon="dollar" color="primary" />
        <x-stat-card compact label="Delivery Queue" :value="$pendingDeliveries" hint="Pending or confirmed" icon="truck" color="delivery" />
        <x-stat-card compact label="Out for Delivery" :value="$outForDelivery" hint="Currently in transit" icon="truck" color="secondary" />
        <x-stat-card compact label="Low Stock Alerts" :value="$lowStockProducts + $lowStockConsumables" hint="Products and supplies" icon="alert-triangle" color="warning" />
    </div>

    <x-card padding="p-4" class="mt-4">
        <h2 class="text-base font-semibold">Daily Tasks</h2>
        <div class="mt-3 grid gap-2 sm:grid-cols-3">
            <x-button as="a" :href="route('deliveries.index')" variant="secondary"><x-icon name="truck" class="size-4" /> Manage Deliveries</x-button>
            <x-button as="a" :href="route('payments.index')" variant="secondary"><x-icon name="credit-card" class="size-4" /> Record a Collection</x-button>
            <x-button as="a" :href="route('staff.gallons.index')" variant="secondary"><x-icon name="archive" class="size-4" /> Release / Return Gallons</x-button>
        </div>
    </x-card>
    <div class="mt-4 grid items-start gap-4 lg:grid-cols-2">
        <x-card padding="p-4" class="overflow-hidden lg:order-2"><div class="flex items-center justify-between"><div><h2 class="text-base font-semibold text-slate-900 dark:text-white">My Sales Activity</h2><p class="text-xs text-slate-500 dark:text-slate-400">Sales you recorded in the last seven days</p></div><span class="rounded-xl bg-primary-50 p-2 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300"><x-icon name="chart" class="size-5" /></span></div><div class="relative mt-4 h-56 rounded-2xl border border-slate-100 bg-slate-50/70 p-3 dark:border-white/5 dark:bg-slate-950/30"><canvas id="staffSalesChart" class="block size-full" aria-label="Staff sales chart"></canvas></div></x-card>
        <x-card padding="p-0" class="lg:order-1"><div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-white/10"><div><h2 class="text-base font-semibold text-slate-900 dark:text-white">Priority Deliveries</h2><p class="text-xs text-slate-500 dark:text-slate-400">Active orders requiring attention</p></div><a href="{{ route('deliveries.index') }}" class="text-xs font-semibold text-primary-600 hover:text-primary-700">View all</a></div><nav aria-label="Delivery schedule filters" class="flex flex-wrap gap-2 border-b border-slate-100 px-4 pb-3 dark:border-white/10">@foreach(['overdue' => 'Overdue', 'today' => 'Today', 'upcoming' => 'Upcoming'] as $schedule => $label)<a href="{{ route('deliveries.index', ['schedule' => $schedule]) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-primary-700 hover:bg-primary-50 dark:border-white/10 dark:text-primary-300 dark:hover:bg-white/5">{{ $label }}</a>@endforeach</nav><div class="divide-y divide-slate-100 dark:divide-white/5">@forelse($priorityDeliveries as $order)<a href="{{ route('deliveries.show', $order) }}" class="block px-4 py-3 transition hover:bg-slate-50 dark:hover:bg-white/5"><div class="flex flex-wrap items-center justify-between gap-2"><p class="font-mono text-sm font-semibold text-primary-700 dark:text-primary-300">{{ $order->order_code }}</p><x-delivery-status-badge :status="$order->status" /></div><p class="mt-1 truncate text-sm font-medium text-slate-900 dark:text-white">{{ $order->customer_name }}</p><p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $order->items_summary }} · {{ $order->preferred_delivery_date?->format('M d') ?? 'No date' }}</p></a>@empty<p class="px-4 pb-4 text-sm text-slate-500 dark:text-slate-400">No active delivery orders.</p>@endforelse</div></x-card>
    </div>

    <x-card class="mt-4" padding="p-0">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 dark:border-white/10"><div><h2 class="text-base font-semibold">My Recent Transactions</h2><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Your 5 most recent sales</p></div><a href="{{ route('pos.index', ['scope' => 'mine']) }}" class="shrink-0 text-sm font-semibold text-primary-700 dark:text-primary-300">View All</a></div>
        <ul class="divide-y divide-slate-100 dark:divide-white/10">
            @forelse($recentTransactions as $transaction)
                <li><a href="{{ route('pos.show', $transaction) }}" class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-white/5 sm:grid-cols-[minmax(0,1fr)_auto_auto]">
                    <div class="min-w-0"><p class="font-mono text-sm font-semibold text-primary-700 dark:text-primary-300">{{ $transaction->transaction_code }}</p><p class="mt-1 truncate text-sm font-medium">{{ $transaction->customer_name ?? $transaction->customer?->name ?? 'Walk-in guest' }}</p><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $transaction->created_at->format('M d, Y - g:i A') }}</p></div>
                    <div class="col-start-1 row-start-2 flex flex-wrap gap-1 sm:col-start-auto sm:row-start-auto"><x-badge :color="$transaction->transaction_type === 'delivery' ? 'delivery' : 'primary'">{{ $transaction->transaction_type === 'delivery' ? 'Delivery' : 'Walk-in' }}</x-badge><x-badge :color="$transaction->payment_method === 'loan' ? 'warning' : 'success'">{{ $transaction->payment_method === 'loan' ? 'Credit' : 'Cash' }}</x-badge></div>
                    <p class="col-start-2 row-start-1 text-right text-sm font-semibold tabular-nums sm:col-start-auto">&#8369;{{ number_format($transaction->total_amount, 2) }}</p>
                </a></li>
            @empty<li class="p-4"><x-empty-state icon="cart" title="No sales recorded yet" description="Sales you record will appear here." /></li>@endforelse
        </ul>
    </x-card>

    @include('staff.partials.operations')
    <div class="mt-4"><details><summary class="cursor-pointer text-sm font-semibold">My Staff Details</summary><div class="mt-3">@include('staff.partials.account-overview')</div></details></div>

    @push('scripts')<script>document.addEventListener('DOMContentLoaded', () => { const dark = document.documentElement.classList.contains('dark'); const chart = new window.Chart(document.getElementById('staffSalesChart'), { type: 'line', data: { labels: @json($salesTrendLabels), datasets: [{ data: @json($salesTrendData), label: 'Sales', borderColor: '#0ea5e9', backgroundColor: 'rgba(14, 165, 233, 0.12)', borderWidth: 2.5, tension: 0.35, fill: true, pointRadius: 3, pointBackgroundColor: '#0ea5e9' }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { backgroundColor: dark ? '#0f172a' : '#fff', titleColor: dark ? '#f8fafc' : '#0f172a', bodyColor: dark ? '#cbd5e1' : '#475569', borderColor: dark ? 'rgba(148,163,184,.2)' : 'rgba(15,23,42,.12)', borderWidth: 1 } }, scales: { x: { ticks: { color: dark ? '#94a3b8' : '#475569' }, grid: { color: dark ? 'rgba(148,163,184,.15)' : 'rgba(100,116,139,.1)' } }, y: { beginAtZero: true, ticks: { color: dark ? '#94a3b8' : '#475569' }, grid: { color: dark ? 'rgba(148,163,184,.15)' : 'rgba(100,116,139,.1)' } } } } }); new MutationObserver(() => { const isDark = document.documentElement.classList.contains('dark'); const text = isDark ? '#94a3b8' : '#475569'; const grid = isDark ? 'rgba(148,163,184,.15)' : 'rgba(100,116,139,.1)'; chart.options.scales.x.ticks.color = text; chart.options.scales.y.ticks.color = text; chart.options.scales.x.grid.color = grid; chart.options.scales.y.grid.color = grid; chart.update(); }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] }); });</script>@endpush
</x-layouts.app>
