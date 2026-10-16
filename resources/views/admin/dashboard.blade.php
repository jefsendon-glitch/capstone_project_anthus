<x-layouts.app title="Dashboard" heading="Dashboard" :subheading="now()->format('l, F j, Y')">
    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <x-stat-card compact label="Today's Sales" :value="'₱'.number_format($revenueToday, 0)" :hint="$transactionsToday.' transactions'" icon="dollar" color="primary" />
            <x-stat-card compact label="Monthly Revenue" :value="'₱'.number_format($revenueThisMonth, 0)" hint="This month" icon="chart" color="secondary" />
            <x-stat-card compact label="Active Deliveries" :value="number_format($activeDeliveriesCount)" :hint="$pendingDeliveriesCount.' pending dispatch'" icon="truck" color="delivery" />
            <x-stat-card compact label="Outstanding Balances" :value="'₱'.number_format($totalOutstandingCredit, 0)" hint="Customer receivables" icon="credit-card" color="danger" />
        </div>
        <x-collapsible-section title="Sales & Operations" open>
            <div class="grid min-w-0 gap-4 lg:grid-cols-3">
                <x-card class="min-w-0 lg:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-2"><h2 class="text-sm font-semibold text-slate-900 dark:text-white">Weekly Sales</h2><a href="{{ route('admin.reports.index') }}" class="text-xs font-medium text-primary-600 dark:text-primary-400">Financial Reports</a></div>
                    <p class="mt-1 text-xs text-slate-500">₱{{ number_format($revenueThisWeek, 0) }} this week</p>
                    <div class="relative mt-4 h-48 sm:h-56"><canvas id="salesTrendChart" aria-label="Weekly POS and delivery sales" role="img"></canvas></div>
                    <div class="mt-3 flex justify-center gap-4 text-xs text-slate-500"><span class="flex items-center gap-2"><span class="size-2 rounded-full bg-secondary-500"></span>POS / Walk-in</span><span class="flex items-center gap-2"><span class="size-2 rounded-full bg-delivery-500"></span>Delivery</span></div>
                </x-card>
                <x-card>
                    <div class="flex items-center justify-between gap-2"><h2 class="text-sm font-semibold text-slate-900 dark:text-white">Needs Attention</h2><x-icon name="alert-triangle" class="size-4 text-warning-600" /></div>
                    <div class="mt-4 space-y-3">
                        <a href="{{ route('admin.procurement.reorders') }}" class="flex items-center justify-between gap-2 rounded-lg bg-warning-50 p-3 text-sm text-warning-700 dark:bg-warning-500/10 dark:text-warning-500"><span>Low Stock Supplies</span><span class="font-bold">{{ $lowStockConsumables->count() }}</span></a>
                        <a href="{{ route('admin.products.index') }}" class="flex items-center justify-between gap-2 rounded-lg bg-warning-50 p-3 text-sm text-warning-700 dark:bg-warning-500/10 dark:text-warning-500"><span>Low Stock Products</span><span class="font-bold">{{ $lowStockProducts->count() }}</span></a>
                        <a href="{{ route('deliveries.index', ['status' => 'pending']) }}" class="flex items-center justify-between gap-2 rounded-lg bg-delivery-50 p-3 text-sm text-delivery-700 dark:bg-delivery-500/10 dark:text-delivery-400"><span>Pending Deliveries</span><span class="font-bold">{{ $pendingDeliveriesCount }}</span></a>
                        @if($overdueMaintenance > 0)<a href="{{ route('admin.maintenance.index') }}" class="flex items-center justify-between gap-2 rounded-lg bg-danger-50 p-3 text-sm text-danger-700 dark:bg-danger-500/10 dark:text-danger-400"><span>Overdue Maintenance</span><span class="font-bold">{{ $overdueMaintenance }}</span></a>@endif
                    </div>
                    <div class="mt-4 flex justify-between border-t border-slate-100 pt-3 text-xs dark:border-white/10"><span class="text-slate-500">Gallons Sold Today</span><span class="font-semibold">{{ number_format($gallonsSoldToday) }}</span></div>
                </x-card>
            </div>
        </x-collapsible-section>
        <x-collapsible-section title="Recent Transactions & Upcoming Deliveries" open>
            <div class="grid min-w-0 gap-4 md:grid-cols-2">
                <x-card padding="p-0">
                    <div class="flex items-center justify-between gap-2 px-4 py-3"><h2 class="text-sm font-semibold text-slate-900 dark:text-white">Recent Transactions</h2><a href="{{ route('pos.index') }}" class="text-xs font-medium text-primary-600 dark:text-primary-400">View All</a></div>
                    <div class="divide-y divide-slate-100 dark:divide-white/5">
                        @forelse($recentTransactions->take(5) as $transaction)
                            <a href="{{ route('pos.show', $transaction) }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-white/5"><span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400"><x-icon :name="$transaction->transaction_type === 'delivery' ? 'truck' : 'cart'" class="size-4" /></span><div class="min-w-0 flex-1"><p class="truncate text-sm font-medium">{{ $transaction->customer_name ?? $transaction->customer?->name ?? 'Guest' }}</p><p class="truncate text-xs text-slate-500">{{ $transaction->transaction_code }} · {{ $transaction->created_at->format('M d, h:i A') }}</p></div><span class="shrink-0 text-sm font-semibold">₱{{ number_format($transaction->total_amount, 0) }}</span></a>
                        @empty<p class="px-4 pb-4 text-sm text-slate-500">No recent transactions.</p>@endforelse
                    </div>
                </x-card>
                <x-card padding="p-0">
                    <div class="flex items-center justify-between gap-2 px-4 py-3"><h2 class="text-sm font-semibold text-slate-900 dark:text-white">Upcoming Deliveries</h2><a href="{{ route('deliveries.index') }}" class="text-xs font-medium text-primary-600 dark:text-primary-400">View All</a></div>
                    <div class="divide-y divide-slate-100 dark:divide-white/5">
                        @forelse($pendingDeliveryOrders->take(5) as $order)
                            <a href="{{ route('deliveries.show', $order) }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-white/5"><span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-delivery-50 text-delivery-600 dark:bg-delivery-500/10 dark:text-delivery-400"><x-icon name="truck" class="size-4" /></span><div class="min-w-0 flex-1"><p class="truncate text-sm font-medium">{{ $order->customer_name }}</p><p class="truncate text-xs text-slate-500">{{ $order->order_code }} · {{ $order->preferred_delivery_date?->format('M d, Y') ?? 'Not scheduled' }}</p></div><x-delivery-status-badge :status="$order->status" /></a>
                        @empty<p class="px-4 pb-4 text-sm text-slate-500">No upcoming deliveries.</p>@endforelse
                    </div>
                </x-card>
            </div>
        </x-collapsible-section>
    </div>
    <x-collapsible-section title="System Activity Log" :open="request()->hasAny(['activity_date_from', 'activity_date_to', 'activity_page'])">
    <div id="system-activity-log" data-activity-url="{{ route('admin.dashboard.activities') }}" class="min-w-0">
        <form method="GET" action="{{ route('admin.dashboard') }}" class="mb-3 grid grid-cols-2 items-end gap-2 sm:flex sm:flex-wrap sm:justify-end">
            <div>
                <x-input-label for="activity_date_from" value="Activity from" />
                <x-text-input id="activity_date_from" type="text" name="activity_date_from" data-flatpickr :value="$activityDateFrom" placeholder="Start date" />
            </div>
            <div>
                <x-input-label for="activity_date_to" value="Activity to" />
                <x-text-input id="activity_date_to" type="text" name="activity_date_to" data-flatpickr :value="$activityDateTo" placeholder="End date" />
            </div>
            <x-button as="a" href="{{ route('admin.dashboard') }}" variant="secondary">Reset</x-button>
            <x-button type="submit">Filter log</x-button>
        </form>
        <x-card padding="p-0">
            <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3"><div><h2 class="font-heading text-sm font-bold text-slate-900 dark:text-white">System Activity Log</h2><p class="text-xs text-slate-400">The 10 most recent actions recorded in the system.</p></div><x-icon name="clock" class="size-5 text-primary-500" /></div>
            @if($recentActivities->isEmpty())
                <div class="px-4 pb-4"><x-empty-state icon="clock" title="No activity recorded yet" description="System actions will appear here as users work in the application." /></div>
            @else
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 dark:divide-white/10"><thead class="bg-slate-50/80 dark:bg-white/[0.03]"><tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"><th class="px-4 py-2.5">Date</th><th class="px-4 py-2.5">Time</th><th class="px-4 py-2.5">Action</th><th class="px-4 py-2.5">User</th></tr></thead><tbody class="divide-y divide-slate-100 dark:divide-white/5">@foreach($recentActivities as $activity)<tr class="transition hover:bg-slate-50/80 dark:hover:bg-white/[0.03]"><td class="whitespace-nowrap px-4 py-2.5 text-sm text-slate-600 dark:text-slate-300">{{ $activity->created_at->format('M d, Y') }}</td><td class="whitespace-nowrap px-4 py-2.5 font-mono text-sm text-slate-600 dark:text-slate-300">{{ $activity->created_at->format('h:i A') }}</td><td class="px-4 py-2.5 text-sm font-medium text-slate-900 dark:text-white">{{ $activity->description }}</td><td class="px-4 py-2.5 text-sm text-slate-600 dark:text-slate-300">{{ $activity->causer?->name ?? 'System' }}</td></tr>@endforeach</tbody></table></div><div class="border-t border-slate-100 px-4 py-3 dark:border-white/5">{{ $recentActivities->links('pagination.records', ['recordLabel' => 'activities']) }}</div>
            @endif
        </x-card>
    </div>

    </x-collapsible-section>

    @push('scripts')
    <script>
        function dashboardIsDark() {
            return document.documentElement.classList.contains('dark');
        }

        function dashboardTickColor() {
            return dashboardIsDark() ? '#94a3b8' : '#475569';
        }

        function dashboardGridColor() {
            return dashboardIsDark() ? 'rgba(148, 163, 184, 0.15)' : 'rgba(100, 116, 139, 0.1)';
        }

        function dashboardTooltipColors() {
            return dashboardIsDark()
                ? { background: '#0f172a', title: '#f8fafc', body: '#cbd5e1', border: 'rgba(148, 163, 184, 0.2)' }
                : { background: '#ffffff', title: '#0f172a', body: '#475569', border: 'rgba(15, 23, 42, 0.12)' };
        }

        document.addEventListener('DOMContentLoaded', () => {
        const salesTrendChart = new window.Chart(document.getElementById('salesTrendChart'), {
            type: 'bar',
            data: {
                labels: @json($trendLabels),
                datasets: [
                    {
                        label: 'Walk-in',
                        data: @json($trendWalkIn),
                        backgroundColor: '#22d3ee',
                        borderRadius: 6,
                    },
                    {
                        label: 'Delivery',
                        data: @json($trendDelivery),
                        backgroundColor: '#8b5cf6',
                        borderRadius: 6,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: dashboardTooltipColors().background, titleColor: dashboardTooltipColors().title, bodyColor: dashboardTooltipColors().body, borderColor: dashboardTooltipColors().border, borderWidth: 1, padding: 10, displayColors: true },
                },
                scales: {
                    x: { ticks: { color: dashboardTickColor() }, grid: { color: dashboardGridColor() } },
                    y: { beginAtZero: true, ticks: { color: dashboardTickColor() }, grid: { color: dashboardGridColor() } },
                },
            },
        });

        document.querySelectorAll('.overview-section').forEach((section) => {
            section.addEventListener('toggle', () => {
                if (section.open) {
                    requestAnimationFrame(() => {
                        [salesTrendChart].forEach((chart) => chart.resize());
                    });
                }
            });
        });

        new MutationObserver(() => {
            [salesTrendChart].forEach((chart) => {
                chart.options.scales.x.ticks.color = dashboardTickColor();
                chart.options.scales.y.ticks.color = dashboardTickColor();
                chart.options.scales.x.grid.color = dashboardGridColor();
                chart.options.scales.y.grid.color = dashboardGridColor();
                chart.options.plugins.tooltip.backgroundColor = dashboardTooltipColors().background;
                chart.options.plugins.tooltip.titleColor = dashboardTooltipColors().title;
                chart.options.plugins.tooltip.bodyColor = dashboardTooltipColors().body;
                chart.options.plugins.tooltip.borderColor = dashboardTooltipColors().border;
                chart.update();
            });
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

        const activityLog = document.getElementById('system-activity-log');
        const activityTableBody = activityLog?.querySelector('tbody');

        const refreshActivities = async () => {
            if (!activityLog) return;

            try {
                const response = await fetch(activityLog.dataset.activityUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!response.ok) return;

                const { activities } = await response.json();
                if (!activityTableBody) {
                    if (activities.length) window.location.reload();
                    return;
                }
                const rows = document.createDocumentFragment();

                activities.forEach((activity) => {
                    const row = document.createElement('tr');
                    row.className = 'transition hover:bg-slate-50/80 dark:hover:bg-white/[0.03]';

                    [activity.date, activity.time, activity.description, activity.user].forEach((value, index) => {
                        const cell = document.createElement('td');
                        cell.textContent = value;
                        cell.className = index === 2
                            ? 'px-4 py-2.5 text-sm font-medium text-slate-900 dark:text-white'
                            : `whitespace-nowrap px-4 py-2.5 ${index === 1 ? 'font-mono ' : ''}text-sm text-slate-600 dark:text-slate-300`;
                        rows.appendChild(row).appendChild(cell);
                    });
                });

                if (activities.length) activityTableBody.replaceChildren(rows);
            } catch {
                // Keep the current log visible and try again on the next refresh.
            }
        };

        const activityFiltersApplied = new URLSearchParams(window.location.search).has('activity_date_from')
            || new URLSearchParams(window.location.search).has('activity_date_to');

        if (!activityFiltersApplied) window.setInterval(refreshActivities, 15_000);
        });
    </script>
    @endpush
</x-layouts.app>
