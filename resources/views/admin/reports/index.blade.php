<x-layouts.app title="Financial Reports" :heading="'Financial Reports'">
    <div id="printable-report" class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-3">
                <img
                    src="{{ asset('images/shaunti-water-logo.svg') }}"
                    alt="{{ $business->business_name }}"
                    class="hidden size-12 shrink-0 rounded-2xl bg-white object-contain p-1.5 shadow-sm ring-1 ring-slate-900/5 sm:block dark:bg-white/90"
                >
                <div>
                    <p class="text-sm font-medium text-primary-600 dark:text-primary-400">Financial overview</p>
                    <h2 class="mt-1 text-2xl font-bold text-slate-950 dark:text-white">Revenue at a glance</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Live figures from completed sales records for {{ $reportStart->format('M d, Y') }} to {{ $reportEnd->format('M d, Y') }}.</p>
                </div>
            </div>
            <x-button class="no-print" type="button" variant="secondary" onclick="window.print()">
                <x-icon name="clipboard" class="size-4" /> Print / Save PDF
            </x-button>
        </div>

        <x-card class="no-print !p-4 sm:!p-5">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="grid gap-4 sm:grid-cols-[1fr_1fr_auto_auto] sm:items-end">
                <div>
                    <x-input-label for="date_from" value="From" />
                    <x-text-input id="date_from" type="date" name="date_from" :value="$reportStart->toDateString()" required />
                </div>
                <div>
                    <x-input-label for="date_to" value="To" />
                    <x-text-input id="date_to" type="date" name="date_to" :value="$reportEnd->toDateString()" required />
                </div>
                <x-button type="submit" class="w-full sm:w-auto"><x-icon name="chart" class="size-4" /> Generate report</x-button>
                <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center justify-center rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5">Last 7 days</a>
            </form>
        </x-card>

        <div class="hidden print:flex print:items-center print:gap-4 print:border-b-2 print:border-slate-900 print:pb-4">
            <img src="{{ asset('images/shaunti-water-logo.svg') }}" alt="{{ $business->business_name }}" class="h-16 w-16 shrink-0 object-contain">
            <div>
                <h1 class="text-2xl font-bold text-slate-950">{{ $business->business_name }}</h1>
                @if($business->address)
                    <p class="text-xs text-slate-600">{{ $business->address }}</p>
                @endif
                @if($business->contact_number || $business->contact_email)
                    <p class="text-xs text-slate-600">{{ collect([$business->contact_number, $business->contact_email])->filter()->implode(' · ') }}</p>
                @endif
                <p class="mt-1 text-sm font-semibold text-slate-700">Financial report · {{ $reportStart->format('F d, Y') }} – {{ $reportEnd->format('F d, Y') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Period Revenue" value="₱{{ number_format($reportRevenue, 2) }}" hint="Selected date range" icon="dollar" color="primary" variant="solid" />
            <x-stat-card label="Transactions" value="{{ number_format($reportTransactions) }}" hint="Sales transactions" icon="clipboard" color="secondary" variant="solid" />
            <x-stat-card label="Average Sale" value="₱{{ number_format($averageTransaction, 2) }}" hint="Per transaction" icon="chart" color="success" variant="solid" />
            <x-stat-card label="Top Product" value="{{ $bestSellers->first()?->product_name ?? '—' }}" hint="{{ $bestSellers->first() ? number_format($bestSellers->first()->total_quantity).' units sold' : 'No sales in this period' }}" icon="cube" color="delivery" variant="solid" />
        </div>

        <x-card>
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-950 dark:text-white">Revenue trend</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Daily sales recorded in the selected period.</p>
                </div>
                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"><span class="size-2 rounded-full bg-primary-500"></span> Revenue</span>
            </div>
            <div class="mt-5 h-72"><canvas id="salesTrendChart" aria-label="Daily revenue trend" role="img"></canvas></div>
        </x-card>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-card>
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">Payment mix</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Revenue by payment method.</p>
                    </div>
                    <span class="rounded-xl bg-success-50 p-2 text-success-600 dark:bg-success-500/10 dark:text-success-400"><x-icon name="credit-card" class="size-5" /></span>
                </div>
                <div class="mt-5 h-56"><canvas id="paymentMixChart" aria-label="Payment method revenue split" role="img"></canvas></div>
            </x-card>

            <x-card>
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">Sales channel</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Walk-in and delivery revenue.</p>
                    </div>
                    <span class="rounded-xl bg-delivery-50 p-2 text-delivery-600 dark:bg-delivery-500/10 dark:text-delivery-400"><x-icon name="truck" class="size-5" /></span>
                </div>
                <div class="mt-5 h-56"><canvas id="channelMixChart" aria-label="Sales channel revenue split" role="img"></canvas></div>
            </x-card>
        </div>

        <x-card padding="p-0">
            <div class="flex items-center justify-between px-6 py-5">
                <div>
                    <h2 class="text-base font-semibold text-slate-950 dark:text-white">Best-selling products</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ranked by revenue for this report period.</p>
                </div>
                <span class="text-xs font-medium text-slate-400">{{ $bestSellers->count() }} products</span>
            </div>
            @if($bestSellers->isEmpty())
                <div class="px-6 pb-6"><x-empty-state icon="cube" title="No sales recorded in this period" /></div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                        <thead><tr class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-white/5 dark:text-slate-400"><th class="px-6 py-3">Product</th><th class="px-6 py-3">Units sold</th><th class="px-6 py-3 text-right">Revenue</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                            @foreach($bestSellers as $row)
                                <tr><td class="px-6 py-4 text-sm font-semibold text-slate-900 dark:text-white">{{ $row->product_name }}</td><td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">{{ number_format($row->total_quantity) }}</td><td class="px-6 py-4 text-right font-mono text-sm font-semibold text-slate-900 dark:text-white">₱{{ number_format($row->total_revenue, 2) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <p class="hidden print:block print:mt-2 print:text-xs print:text-slate-400">Generated by {{ auth()->user()->name }} on {{ now()->format('F d, Y g:i A') }}</p>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.Chart) return;

            const chartData = {
                labels: @json($trendLabels), trend: @json($trendData),
                cash: {{ (float) $cashVsLoan->get('cash', 0) }}, loan: {{ (float) $cashVsLoan->get('loan', 0) }},
                walkIn: {{ (float) $walkInVsDelivery->get('walk-in', 0) }}, delivery: {{ (float) $walkInVsDelivery->get('delivery', 0) }},
            };
            const charts = [];
            const palette = () => document.documentElement.classList.contains('dark')
                ? { text: '#cbd5e1', muted: '#94a3b8', grid: 'rgba(148, 163, 184, .16)' }
                : { text: '#334155', muted: '#64748b', grid: 'rgba(148, 163, 184, .22)' };
            const money = value => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value);
            const tooltip = () => ({ backgroundColor: document.documentElement.classList.contains('dark') ? '#0f172a' : '#fff', titleColor: palette().text, bodyColor: palette().text, borderColor: palette().grid, borderWidth: 1, callbacks: { label: context => `${context.dataset.label || context.label}: ${money(context.raw)}` } });

            const renderCharts = () => {
                charts.splice(0).forEach(chart => chart.destroy());
                const colors = palette();
                charts.push(new window.Chart(document.getElementById('salesTrendChart'), {
                    type: 'line', data: { labels: chartData.labels, datasets: [{ label: 'Revenue', data: chartData.trend, borderColor: '#0284c7', backgroundColor: 'rgba(14, 165, 233, .16)', fill: true, tension: .35, borderWidth: 3, pointRadius: 3, pointHoverRadius: 5, pointBackgroundColor: '#fff', pointBorderColor: '#0284c7' }] },
                    options: { responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' }, plugins: { legend: { display: false }, tooltip: tooltip() }, scales: { x: { ticks: { color: colors.muted, maxRotation: 0 }, grid: { display: false }, border: { display: false } }, y: { beginAtZero: true, ticks: { color: colors.muted, callback: value => money(value) }, grid: { color: colors.grid }, border: { display: false } } } }
                }));
                const doughnut = (element, labels, data, colorset) => new window.Chart(document.getElementById(element), { type: 'doughnut', data: { labels, datasets: [{ data, backgroundColor: colorset, borderColor: document.documentElement.classList.contains('dark') ? '#0f172a' : '#fff', borderWidth: 4, hoverOffset: 5 }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '66%', plugins: { legend: { position: 'bottom', labels: { color: colors.text, padding: 18, usePointStyle: true, pointStyle: 'circle' } }, tooltip: tooltip() } } });
                charts.push(doughnut('paymentMixChart', ['Cash', 'Loan / Credit'], [chartData.cash, chartData.loan], ['#10b981', '#8b5cf6']));
                charts.push(doughnut('channelMixChart', ['Walk-in', 'Delivery'], [chartData.walkIn, chartData.delivery], ['#0284c7', '#f59e0b']));
            };
            renderCharts();
            new MutationObserver(renderCharts).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        });
    </script>
    @endpush
</x-layouts.app>
