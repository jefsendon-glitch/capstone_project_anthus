<x-layouts.app title="Overview" :heading="'Overview'" :subheading="now()->format('l, F j, Y')">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5">
        <x-stat-card
            variant="solid"
            label="Today's Sales"
            value="₱{{ number_format($revenueToday, 0) }}"
            :hint="$transactionsToday.' transactions'"
            icon="dollar"
            color="primary"
        />
        <x-stat-card
            variant="solid"
            label="Weekly Sales"
            value="₱{{ number_format($revenueThisWeek, 0) }}"
            hint="This week"
            icon="dollar"
            color="secondary"
        />
        <x-stat-card
            variant="solid"
            label="Monthly Revenue"
            value="₱{{ number_format($revenueThisMonth, 0) }}"
            hint="This month"
            icon="chart"
            color="secondary"
        />
        <x-stat-card
            variant="solid"
            label="Total Transactions"
            :value="$totalTransactions"
            hint="All-time"
            icon="cart"
            color="primary"
        />
        <x-stat-card
            variant="solid"
            label="Total Customers"
            :value="$totalCustomers"
            hint="Registered accounts"
            icon="users"
            color="primary"
        />
        <x-stat-card
            variant="solid"
            label="Gallons Sold Today"
            :value="number_format($gallonsSoldToday, 0)"
            hint="Excludes containers"
            icon="droplet"
            color="secondary"
        />
        <x-stat-card
            variant="solid"
            label="Active Deliveries"
            :value="$activeDeliveriesCount"
            hint="Out for delivery"
            icon="truck"
            color="delivery"
        />
        <x-stat-card
            variant="solid"
            label="Pending Deliveries"
            :value="$pendingDeliveriesCount"
            hint="Awaiting dispatch"
            icon="clock"
            color="warning"
        />
        <x-stat-card
            variant="solid"
            label="Low Stock Items"
            :value="$lowStockProducts->count() + $lowStockConsumables->count()"
            hint="Restock needed"
            icon="alert-triangle"
            color="warning"
        />
        <x-stat-card
            variant="solid"
            label="Outstanding Balances"
            value="₱{{ number_format($totalOutstandingCredit, 0) }}"
            hint="Owed by customers"
            icon="credit-card"
            color="danger"
        />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-card class="overflow-hidden lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Weekly Revenue (Walk-in vs Delivery)</h2>
                <p class="text-xs text-slate-400">
                    This week: <span class="font-mono font-semibold text-slate-600 dark:text-slate-300">₱{{ number_format($revenueThisWeek, 0) }}</span>
                    · This month: <span class="font-mono font-semibold text-slate-600 dark:text-slate-300">₱{{ number_format($revenueThisMonth, 0) }}</span>
                </p>
            </div>
            <div class="relative mt-5 h-72 rounded-2xl border border-slate-100 bg-slate-50/70 p-3 dark:border-white/5 dark:bg-slate-950/30">
                <canvas id="salesTrendChart" class="block size-full" aria-label="Weekly sales chart"></canvas>
            </div>
            <div class="mt-4 flex items-center justify-center gap-6 text-sm">
                <span class="flex items-center gap-2 text-slate-600 dark:text-slate-300"><span class="size-2.5 rounded-full bg-secondary-500"></span> Walk-in</span>
                <span class="flex items-center gap-2 text-slate-600 dark:text-slate-300"><span class="size-2.5 rounded-full bg-delivery-500"></span> Delivery</span>
            </div>
        </x-card>

        <x-card class="overflow-hidden">
            <h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">System Alerts</h2>
            <div class="mt-4 space-y-2">
                @forelse($lowStockProducts->concat($lowStockConsumables) as $item)
                    <div class="flex items-center gap-2 rounded-xl bg-warning-50 px-3 py-2.5 text-sm text-warning-700 dark:bg-warning-500/10 dark:text-warning-500">
                        <x-icon name="alert-triangle" class="size-4 shrink-0" />
                        <span class="min-w-0 truncate">
                            @if($item instanceof \App\Models\Product)
                                {{ $item->name }} ({{ $item->size }}) — only {{ $item->stock_quantity }} left
                            @else
                                {{ $item->name }} — only {{ rtrim(rtrim($item->quantity, '0'), '.') }} {{ $item->unit }} left
                            @endif
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">No alerts right now — everything's healthy.</p>
                @endforelse

                @if($overdueMaintenance > 0)
                    <div class="flex items-center gap-2 rounded-xl bg-danger-50 px-3 py-2.5 text-sm text-danger-700 dark:bg-danger-500/10 dark:text-danger-500">
                        <x-icon name="cog" class="size-4 shrink-0" />
                        <span>{{ $overdueMaintenance }} maintenance {{ Str::plural('task', $overdueMaintenance) }} overdue</span>
                    </div>
                @endif
            </div>
        </x-card>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-card class="overflow-hidden lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Revenue Trend (6 Months)</h2>
            </div>
            <div class="relative mt-5 h-64 rounded-2xl border border-slate-100 bg-slate-50/70 p-3 dark:border-white/5 dark:bg-slate-950/30">
                <canvas id="revenueChart" class="block size-full" aria-label="Six-month revenue chart"></canvas>
            </div>
        </x-card>

        <x-card padding="p-0">
            <div class="flex items-center justify-between px-6 py-4">
                <h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Notifications</h2>
                @if($recentNotifications->whereNull('read_at')->isNotEmpty())
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="text-xs font-medium text-primary-600 hover:text-primary-700">Mark all read</button>
                    </form>
                @endif
            </div>

            @if($recentNotifications->isEmpty())
                <div class="px-6 pb-6">
                    <x-empty-state icon="bell" title="No notifications yet" description="Order and delivery alerts will show up here." />
                </div>
            @else
                <div class="max-h-72 divide-y divide-slate-100 overflow-y-auto dark:divide-white/5">
                    @foreach($recentNotifications as $notification)
                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                            @csrf
                            <button type="submit" class="flex w-full items-start gap-2.5 px-6 py-3 text-left hover:bg-slate-50 dark:hover:bg-white/5">
                                <span class="mt-1.5 size-2 shrink-0 rounded-full {{ $notification->read_at ? 'bg-transparent' : 'bg-primary-500' }}"></span>
                                <span class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                    <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">{{ $notification->data['message'] ?? '' }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                                </span>
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card padding="p-0">
            <div class="flex items-center justify-between px-6 py-4">
                <h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Water Inventory Summary</h2>
                <a href="{{ route('admin.products.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">View all</a>
            </div>

            @if($waterInventory->isEmpty())
                <div class="px-6 pb-6">
                    <x-empty-state icon="droplet" title="No water products yet" description="Add products to track inventory." />
                </div>
            @else
                <div class="divide-y divide-slate-100 dark:divide-white/5">
                    @foreach($waterInventory->groupBy('category') as $category => $products)
                        <div class="flex items-center gap-3 px-6 py-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-secondary-50 text-secondary-600 dark:bg-secondary-500/10 dark:text-secondary-400">
                                <x-icon name="droplet" class="size-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium capitalize text-slate-900 dark:text-white">{{ $category }}</p>
                                <p class="text-xs text-slate-400">{{ $products->count() }} {{ Str::plural('product', $products->count()) }}</p>
                            </div>
                            <p class="shrink-0 font-mono text-sm font-semibold text-slate-900 dark:text-white">{{ number_format($products->sum('stock_quantity'), 0) }} {{ $products->first()->stock_unit_label }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card padding="p-0">
            <div class="flex items-center justify-between px-6 py-4">
                <h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Bottle Inventory Summary</h2>
                <a href="{{ route('admin.products.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">View all</a>
            </div>

            @if($bottleInventory->isEmpty())
                <div class="px-6 pb-6">
                    <x-empty-state icon="archive" title="No bottle stock yet" description="Add container products to track inventory." />
                </div>
            @else
                <div class="divide-y divide-slate-100 dark:divide-white/5">
                    @foreach($bottleInventory as $bottle)
                        <div class="flex items-center gap-3 px-6 py-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                                <x-icon name="archive" class="size-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ $bottle->name }} ({{ $bottle->size }})</p>
                                @if($bottle->is_low_stock)
                                    <x-badge color="warning">Low stock</x-badge>
                                @endif
                            </div>
                            <p class="shrink-0 font-mono text-sm font-semibold text-slate-900 dark:text-white">{{ number_format($bottle->stock_quantity, 0) }} pcs</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card padding="p-0">
            <div class="flex items-center justify-between px-6 py-4">
                <h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Best-Selling Products</h2>
            </div>

            @if($bestSellers->isEmpty())
                <div class="px-6 pb-6">
                    <x-empty-state icon="tag" title="No sales yet" description="Best sellers will appear here once you record sales." />
                </div>
            @else
                <div class="divide-y divide-slate-100 dark:divide-white/5">
                    @foreach($bestSellers as $index => $product)
                        <div class="flex items-center gap-3 px-6 py-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-bold text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                                #{{ $index + 1 }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ $product->product_name }}</p>
                                <p class="text-xs text-slate-400">{{ number_format($product->total_qty, 0) }} units sold</p>
                            </div>
                            <p class="shrink-0 font-mono text-sm font-semibold text-slate-900 dark:text-white">₱{{ number_format($product->total_revenue, 0) }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card padding="p-0">
            <div class="flex items-center justify-between px-6 py-4">
                <h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Outstanding Customer Balances</h2>
                <a href="{{ route('admin.customers.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">View all</a>
            </div>

            @if($topDebtors->isEmpty())
                <div class="px-6 pb-6">
                    <x-empty-state icon="credit-card" title="No outstanding balances" description="Customer credit balances will appear here." />
                </div>
            @else
                <div class="divide-y divide-slate-100 dark:divide-white/5">
                    @foreach($topDebtors as $customer)
                        <a href="{{ route('admin.customers.show', $customer) }}" class="flex items-center gap-3 px-6 py-3 hover:bg-slate-50 dark:hover:bg-white/5">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-danger-50 text-danger-600 dark:bg-danger-500/10 dark:text-danger-500">
                                <x-icon name="user-circle" class="size-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ $customer->name }}</p>
                            </div>
                            <p class="shrink-0 font-mono text-sm font-semibold text-danger-600 dark:text-danger-500">₱{{ number_format($customer->credit_balance, 0) }}</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card padding="p-0">
            <div class="flex items-center justify-between px-6 py-4">
                <h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Recent Transactions</h2>
                <x-badge>{{ $totalTransactions }} total</x-badge>
            </div>

            @if($recentTransactions->isEmpty())
                <div class="px-6 pb-6">
                    <x-empty-state icon="cart" title="No sales yet" description="New transactions will appear here." />
                </div>
            @else
                <div class="divide-y divide-slate-100 dark:divide-white/5">
                    @foreach($recentTransactions as $transaction)
                        <a href="{{ route('pos.show', $transaction) }}" class="flex items-center gap-3 px-6 py-3 hover:bg-slate-50 dark:hover:bg-white/5">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full {{ $transaction->transaction_type === 'delivery' ? 'bg-delivery-100 text-delivery-600 dark:bg-delivery-500/10' : 'bg-primary-50 text-primary-600 dark:bg-primary-500/10' }}">
                                <x-icon :name="$transaction->transaction_type === 'delivery' ? 'truck' : 'user-circle'" class="size-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ $transaction->customer_name ?? $transaction->customer?->name ?? 'Guest' }}</p>
                                <p class="text-xs text-slate-400">{{ $transaction->transaction_code }} · {{ $transaction->created_at->format('M d, Y') }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="font-mono text-sm font-semibold text-slate-900 dark:text-white">₱{{ number_format($transaction->total_amount, 0) }}</p>
                                <x-badge :color="$transaction->payment_method === 'loan' ? 'warning' : 'success'">{{ $transaction->payment_method === 'loan' ? 'loan' : 'cash' }}</x-badge>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card padding="p-0">
            <div class="flex items-center justify-between px-6 py-4">
                <h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Upcoming Deliveries</h2>
                <a href="{{ route('deliveries.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">View all</a>
            </div>

            @if($pendingDeliveryOrders->isEmpty())
                <div class="px-6 pb-6">
                    <x-empty-state icon="truck" title="No upcoming deliveries" description="Advance orders will show up here." />
                </div>
            @else
                <div class="divide-y divide-slate-100 dark:divide-white/5">
                    @foreach($pendingDeliveryOrders as $order)
                        <a href="{{ route('deliveries.show', $order) }}" class="flex items-center gap-3 px-6 py-3 hover:bg-slate-50 dark:hover:bg-white/5">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-semibold text-primary-600">{{ $order->order_code }}</span>
                                    <x-delivery-status-badge :status="$order->status" />
                                </div>
                                <p class="mt-1 truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $order->customer_name }}</p>
                                <p class="truncate text-xs text-slate-400">{{ $order->product_name }} × {{ $order->quantity }} · {{ $order->preferred_delivery_date?->format('M d, Y') ?? 'No date set' }}</p>
                            </div>
                            <p class="shrink-0 font-mono text-sm font-semibold text-slate-900 dark:text-white">₱{{ number_format($order->total_amount, 0) }}</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>

    <div id="system-activity-log" data-activity-url="{{ route('admin.dashboard.activities') }}" class="mt-6">
        <form method="GET" action="{{ route('admin.dashboard') }}" class="mb-4 flex flex-wrap items-end justify-end gap-3">
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
            <div class="flex items-center justify-between px-6 py-4"><div><h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">System Activity Log</h2><p class="text-xs text-slate-400">The 10 most recent actions recorded in the system.</p></div><x-icon name="clock" class="size-5 text-primary-500" /></div>
            @if($recentActivities->isEmpty())
                <div class="px-6 pb-6"><x-empty-state icon="clock" title="No activity recorded yet" description="System actions will appear here as users work in the application." /></div>
            @else
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 dark:divide-white/10"><thead class="bg-slate-50/80 dark:bg-white/[0.03]"><tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"><th class="px-6 py-3.5">Date</th><th class="px-6 py-3.5">Time</th><th class="px-6 py-3.5">Action</th><th class="px-6 py-3.5">User</th></tr></thead><tbody class="divide-y divide-slate-100 dark:divide-white/5">@foreach($recentActivities as $activity)<tr class="transition hover:bg-slate-50/80 dark:hover:bg-white/[0.03]"><td class="whitespace-nowrap px-6 py-3.5 text-sm text-slate-600 dark:text-slate-300">{{ $activity->created_at->format('M d, Y') }}</td><td class="whitespace-nowrap px-6 py-3.5 font-mono text-sm text-slate-600 dark:text-slate-300">{{ $activity->created_at->format('h:i A') }}</td><td class="px-6 py-3.5 text-sm font-medium text-slate-900 dark:text-white">{{ $activity->description }}</td><td class="px-6 py-3.5 text-sm text-slate-600 dark:text-slate-300">{{ $activity->causer?->name ?? 'System' }}</td></tr>@endforeach</tbody></table></div><div class="flex items-center justify-between border-t border-slate-100 px-6 py-4 dark:border-white/5"><p class="text-xs text-slate-400">Showing {{ $recentActivities->firstItem() }}–{{ $recentActivities->lastItem() }} of {{ $recentActivities->total() }} activities</p><div class="flex gap-2">@if($recentActivities->onFirstPage())<span class="inline-flex items-center gap-1 rounded-xl bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-400 dark:bg-white/5"><x-icon name="chevron-left" class="size-4" /> Back</span>@else<a href="{{ $recentActivities->previousPageUrl() }}" class="inline-flex items-center gap-1 rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:ring-white/10"><x-icon name="chevron-left" class="size-4" /> Back</a>@endif @if($recentActivities->hasMorePages())<a href="{{ $recentActivities->nextPageUrl() }}" class="inline-flex items-center gap-1 rounded-xl bg-primary-600 px-3 py-2 text-sm font-semibold text-white hover:bg-primary-700">Next <x-icon name="chevron-right" class="size-4" /></a>@else<span class="inline-flex items-center gap-1 rounded-xl bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-400 dark:bg-white/5">Next <x-icon name="chevron-right" class="size-4" /></span>@endif</div></div>
            @endif
        </x-card>
    </div>

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

        const revenueChart = new window.Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: @json($revenueTrendLabels),
                datasets: [
                    {
                        label: 'Revenue',
                        data: @json($revenueTrendData),
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 3,
                        pointBackgroundColor: '#0ea5e9',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: dashboardTooltipColors().background, titleColor: dashboardTooltipColors().title, bodyColor: dashboardTooltipColors().body, borderColor: dashboardTooltipColors().border, borderWidth: 1, padding: 10, displayColors: false },
                },
                scales: {
                    x: { ticks: { color: dashboardTickColor() }, grid: { color: dashboardGridColor() } },
                    y: { beginAtZero: true, ticks: { color: dashboardTickColor() }, grid: { color: dashboardGridColor() } },
                },
            },
        });

        new MutationObserver(() => {
            [salesTrendChart, revenueChart].forEach((chart) => {
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
                            ? 'px-6 py-3.5 text-sm font-medium text-slate-900 dark:text-white'
                            : `whitespace-nowrap px-6 py-3.5 ${index === 1 ? 'font-mono ' : ''}text-sm text-slate-600 dark:text-slate-300`;
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
