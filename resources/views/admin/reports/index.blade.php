<x-layouts.app title="Financial Reports" heading="Financial Reports">
    @include('admin.financial.nav', ['section' => 'overview'])
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div><h2 class="text-lg font-semibold text-slate-900 dark:text-white">Financial Overview</h2><p class="mt-1 text-xs text-slate-500">{{ $reportStart->format('M d, Y') }} – {{ $reportEnd->format('M d, Y') }}</p></div>
            <a href="{{ route('admin.financial.section', ['section' => 'exports']) }}" class="text-sm font-medium text-primary-600 dark:text-primary-400">Reports &amp; Export <span aria-hidden="true">→</span></a>
        </div>
        @if($errors->any())<div role="alert" class="rounded-lg bg-danger-50 p-3 text-sm text-danger-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <x-card padding="p-3">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="grid grid-cols-2 items-end gap-3 sm:flex sm:flex-wrap">
                <div class="min-w-0"><x-input-label for="date_from" value="From" /><x-text-input id="date_from" type="date" name="date_from" :value="$reportStart->toDateString()" required /></div>
                <div class="min-w-0"><x-input-label for="date_to" value="To" /><x-text-input id="date_to" type="date" name="date_to" :value="$reportEnd->toDateString()" required /></div>
                <x-button type="submit">Apply</x-button><x-button as="a" :href="route('admin.reports.index')" variant="secondary">Last 7 Days</x-button>
            </form>
        </x-card>
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <x-stat-card compact label="Sales Revenue" :value="'₱'.number_format($reportRevenue, 0)" hint="Selected period" icon="chart" color="primary" />
            <x-stat-card compact label="Recorded Expenses" :value="'₱'.number_format($financialSummary['Recorded Expenses'], 0)" hint="Selected period" icon="clipboard" color="danger" />
            <x-stat-card compact label="Collections" :value="'₱'.number_format($financialSummary['Customer Collections'], 0)" hint="Customer payments in period" icon="dollar" color="success" />
            <x-stat-card compact label="Receivables" :value="'₱'.number_format($financialSummary['Current Receivables'], 0)" hint="Current outstanding balance" icon="credit-card" color="warning" />
        </div>
        <div class="grid min-w-0 items-start gap-4 lg:grid-cols-3">
            <x-card class="min-w-0 lg:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-2"><h3 class="text-sm font-semibold text-slate-900 dark:text-white">Sales Revenue Trend</h3><span class="text-xs text-slate-500">Daily totals</span></div>
                <div class="relative mt-4 h-48 sm:h-60"><canvas id="salesTrendChart" aria-label="Daily sales revenue" role="img"></canvas></div>
                <div class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-3 dark:border-white/10">
                    <div><p class="text-xs text-slate-500">Transactions</p><p class="mt-1 text-sm font-semibold">{{ number_format($reportTransactions) }}</p></div>
                    <div><p class="text-xs text-slate-500">Average Sale</p><p class="mt-1 text-sm font-semibold">₱{{ number_format($averageTransaction, 0) }}</p></div>
                </div>
            </x-card>
            <x-card>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Sales Breakdown</h3>
                <div class="mt-4 space-y-4">
                    @foreach(['Cash Sales' => ['cash', 'success'], 'Credit Sales' => ['loan', 'warning']] as $label => [$key, $color])
                        @php $amount = (float) $cashVsLoan->get($key, 0); $percent = $reportRevenue > 0 ? min(100, max(0, $amount / $reportRevenue * 100)) : 0; @endphp
                        <div><div class="flex flex-wrap items-center justify-between gap-1 text-xs"><span class="text-slate-500">{{ $label }}</span><span class="font-semibold">₱{{ number_format($amount, 0) }}</span></div><div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-full rounded-full {{ $key === 'cash' ? 'bg-success-500' : 'bg-warning-500' }}" style="width: {{ $percent }}%"></div></div></div>
                    @endforeach
                </div>
                <dl class="mt-4 space-y-3 border-t border-slate-100 pt-4 text-xs dark:border-white/10">
                    <div class="flex flex-wrap justify-between gap-1"><dt class="text-slate-500">POS / Walk-in</dt><dd class="font-semibold">₱{{ number_format((float) $walkInVsDelivery->get('walk-in', 0), 0) }}</dd></div>
                    <div class="flex flex-wrap justify-between gap-1"><dt class="text-slate-500">Delivery</dt><dd class="font-semibold">₱{{ number_format((float) $walkInVsDelivery->get('delivery', 0), 0) }}</dd></div>
                    <div class="flex flex-wrap justify-between gap-1"><dt class="text-slate-500">Current Overdue Credit</dt><dd class="font-semibold text-danger-600 dark:text-danger-400">₱{{ number_format($financialSummary['Current Overdue Credit'], 0) }}</dd></div>
                </dl>
                <div class="mt-4 rounded-lg bg-primary-50 p-3 dark:bg-primary-500/10"><p class="text-xs text-primary-700 dark:text-primary-300">Sales Less Recorded Expenses</p><p class="mt-1 text-lg font-semibold text-primary-700 dark:text-primary-300">₱{{ number_format($financialSummary['Sales Less Recorded Expenses'], 0) }}</p></div>
            </x-card>
        </div>
        <x-card padding="p-0">
            <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3"><h3 class="text-sm font-semibold text-slate-900 dark:text-white">Top Products</h3><a href="{{ route('admin.financial.section', ['section' => 'sales', 'date_from' => $reportStart->toDateString(), 'date_to' => $reportEnd->toDateString()]) }}" class="text-xs font-medium text-primary-600 dark:text-primary-400">View Sales</a></div>
            @if($bestSellers->isEmpty())<p class="px-4 pb-4 text-sm text-slate-500">No sales recorded in this period.</p>@else
                <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-white/5"><tr><th class="px-4 py-2">Product</th><th class="px-4 py-2 text-right">Units Sold</th><th class="px-4 py-2 text-right">Revenue</th></tr></thead><tbody class="divide-y divide-slate-100 dark:divide-white/5">@foreach($bestSellers->take(5) as $row)<tr><td class="px-4 py-2.5 font-medium">{{ $row->product_name }}</td><td class="px-4 py-2.5 text-right">{{ number_format($row->total_quantity) }}</td><td class="px-4 py-2.5 text-right whitespace-nowrap">₱{{ number_format($row->total_revenue, 0) }}</td></tr>@endforeach</tbody></table></div>
            @endif
        </x-card>
        <p class="text-xs leading-relaxed text-slate-500">Collections are separate from sales revenue. Receivables and overdue credit show current balances; expenses include recorded entries only.</p>
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
            const money = value => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value);
            const tooltip = () => ({ backgroundColor: document.documentElement.classList.contains('dark') ? '#0f172a' : '#fff', titleColor: palette().text, bodyColor: palette().text, borderColor: palette().grid, borderWidth: 1, callbacks: { label: context => `${context.dataset.label || context.label}: ${money(context.raw)}` } });

            const renderCharts = () => {
                charts.splice(0).forEach(chart => chart.destroy());
                const colors = palette();
                charts.push(new window.Chart(document.getElementById('salesTrendChart'), {
                    type: 'line', data: { labels: chartData.labels, datasets: [{ label: 'Revenue', data: chartData.trend, borderColor: '#0284c7', backgroundColor: 'rgba(14, 165, 233, .16)', fill: true, tension: .35, borderWidth: 2, pointRadius: 2, pointHoverRadius: 5, pointBackgroundColor: '#fff', pointBorderColor: '#0284c7' }] },
                    options: { responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' }, plugins: { legend: { display: false }, tooltip: tooltip() }, scales: { x: { ticks: { color: colors.muted, maxRotation: 0, maxTicksLimit: 8 }, grid: { display: false }, border: { display: false } }, y: { beginAtZero: true, ticks: { color: colors.muted, callback: value => money(value) }, grid: { color: colors.grid }, border: { display: false } } } }
                }));
            };
            renderCharts();
            new MutationObserver(renderCharts).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        });
    </script>
    @endpush
</x-layouts.app>
