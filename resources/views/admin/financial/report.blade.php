<x-layouts.app title="Financial Report" :heading="'Financial Report FR-'.$report->id">
    @include('admin.financial.nav', ['section' => 'exports'])

    <style>
        @media print {
            @page { size: A4 portrait; margin: 16mm; }
            body * { visibility: hidden !important; }
            .report-paper, .report-paper * { visibility: visible !important; }
            .report-paper { position: absolute !important; inset: 0 auto auto 0 !important; width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; border: 0 !important; box-shadow: none !important; background: #fff !important; color: #172033 !important; }
            .report-paper .no-print { display: none !important; }
            .report-paper table { border-collapse: collapse !important; }
            .report-paper tr { break-inside: avoid; }
            .report-paper .report-note { color: #475569 !important; }
        }
    </style>

    @php
        $summary = $report->summary ?? [];
        $primaryMetrics = ['Sales Revenue', 'Recorded Expenses', 'Sales Less Recorded Expenses', 'Customer Collections'];
        $sections = [
            'Sales Performance' => ['Sales Revenue', 'Cash Sales', 'Credit Sales', 'POS Sales', 'Delivery Sales'],
            'Expenses and Collections' => ['Recorded Expenses', 'Sales Less Recorded Expenses', 'Customer Collections', 'Cash Sales Plus Collections'],
            'Receivables and Credit' => ['Current Receivables', 'Tracked Unpaid Credit', 'Receivables Reconciliation Difference', 'Current Overdue Credit'],
        ];
    @endphp

    <div class="no-print mb-4 flex flex-wrap gap-2">
        <x-button variant="info" type="button" onclick="window.print()">Print / Save PDF</x-button>
        <x-button as="a" :href="route('admin.financial.reports.export', $report)" variant="info">Export CSV</x-button>
        <x-button as="a" :href="route('admin.financial.section', ['section' => 'exports'])" variant="secondary">Report History</x-button>
    </div>

    <article class="report-paper mx-auto max-w-5xl rounded-xl border border-slate-200 bg-white p-6 text-slate-800 shadow-sm sm:p-10 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100">
        <header class="border-b-2 border-slate-800 pb-5 dark:border-slate-300">
            <div class="flex flex-wrap items-start justify-between gap-5">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Shuanti Water Refilling Station</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Financial Report</h1>
                    <p class="mt-1 text-sm text-slate-500">Official summary of recorded financial activity</p>
                </div>
                <div class="min-w-44 rounded-lg border border-slate-200 px-4 py-3 text-sm dark:border-slate-700">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Report Reference</p>
                    <p class="mt-1 font-mono text-lg font-bold">FR-{{ str_pad((string) $report->id, 5, '0', STR_PAD_LEFT) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ ucfirst($report->period_type) }} report</p>
                </div>
            </div>
            <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-3">
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Reporting Period</dt><dd class="mt-1 font-semibold">{{ $report->date_from->format('F d, Y') }} – {{ $report->date_to->format('F d, Y') }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date Generated</dt><dd class="mt-1 font-semibold">{{ $report->created_at->format('F d, Y · g:i A') }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Prepared By</dt><dd class="mt-1 font-semibold">{{ $report->generatedBy?->name ?? 'Deleted user' }}</dd></div>
            </dl>
        </header>

        <section class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="Key financial figures">
            @foreach($primaryMetrics as $label)
                @if(array_key_exists($label, $summary))
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                        <p class="mt-2 text-xl font-bold tabular-nums">₱{{ number_format((float) $summary[$label], 2) }}</p>
                    </div>
                @endif
            @endforeach
        </section>

        <div class="mt-7 space-y-6">
            @foreach($sections as $sectionTitle => $labels)
                @php $sectionRows = collect($labels)->filter(fn ($label) => array_key_exists($label, $summary)); @endphp
                @if($sectionRows->isNotEmpty())
                    <section>
                        <h2 class="border-b border-slate-300 pb-2 text-sm font-bold uppercase tracking-wider dark:border-slate-700">{{ $sectionTitle }}</h2>
                        <table class="mt-1 w-full text-sm">
                            <thead><tr class="text-left text-xs uppercase tracking-wide text-slate-500"><th class="py-2 font-semibold">Financial Measure</th><th class="py-2 text-right font-semibold">Amount (PHP)</th></tr></thead>
                            <tbody>
                                @foreach($sectionRows as $label)
                                    <tr class="border-t border-slate-100 dark:border-slate-800">
                                        <td class="py-2.5 {{ in_array($label, ['Sales Less Recorded Expenses', 'Receivables Reconciliation Difference'], true) ? 'font-semibold' : '' }}">{{ $label }}</td>
                                        <td class="py-2.5 text-right font-mono tabular-nums {{ in_array($label, ['Sales Less Recorded Expenses', 'Receivables Reconciliation Difference'], true) ? 'font-semibold' : '' }}">₱{{ number_format((float) $summary[$label], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>
                @endif
            @endforeach
        </div>

        <section class="report-note mt-7 rounded-lg border border-slate-200 bg-slate-50 p-4 text-xs leading-relaxed text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
            <h2 class="mb-1 font-bold uppercase tracking-wide">Notes and Basis</h2>
            <p>Amounts are shown in Philippine pesos (PHP) and reflect records available when this report was generated. Customer collections are reported separately from sales revenue. Recorded expenses include saved expense entries for the reporting period. Current receivables and overdue credit are balances as of the report generation date.</p>
        </section>

        <footer class="mt-12 grid gap-10 text-center text-xs sm:grid-cols-2">
            <div class="mx-auto w-full max-w-xs border-t border-slate-400 pt-2">Prepared by</div>
            <div class="mx-auto w-full max-w-xs border-t border-slate-400 pt-2">Reviewed by</div>
        </footer>
        <p class="mt-8 text-center text-[10px] text-slate-400">System-generated financial report · Reference FR-{{ str_pad((string) $report->id, 5, '0', STR_PAD_LEFT) }}</p>
    </article>
</x-layouts.app>
