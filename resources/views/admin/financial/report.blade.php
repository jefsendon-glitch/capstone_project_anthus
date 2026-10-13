<x-layouts.app title="Financial Report" :heading="'Financial Report FR-'.$report->id">
    @include('admin.financial.nav', ['section' => 'exports'])
    <div class="no-print mb-4 flex flex-wrap gap-2"><x-button type="button" onclick="window.print()">Print / Save PDF</x-button><x-button as="a" :href="route('admin.financial.reports.export', $report)" variant="secondary">Export CSV</x-button></div>
    <x-card><h2 class="text-lg font-bold">Financial Report FR-{{ $report->id }}</h2><p class="mt-1 text-sm text-slate-500">{{ ucfirst($report->period_type) }} · {{ $report->date_from->format('M d, Y') }} – {{ $report->date_to->format('M d, Y') }}</p><p class="mt-1 text-xs text-slate-500">Generated {{ $report->created_at->format('M d, Y H:i') }} by {{ $report->generatedBy?->name ?? 'Deleted user' }}</p>
        <div class="mt-4 overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b text-left text-slate-500"><th class="py-3">Financial Summary</th><th class="py-3 text-right">Amount (PHP)</th></tr></thead><tbody class="divide-y divide-slate-100 dark:divide-white/5">@foreach($report->summary as $label => $amount)<tr><td class="py-3">{{ $label }}</td><td class="py-3 text-right font-mono">₱{{ number_format((float) $amount, 2) }}</td></tr>@endforeach</tbody></table></div>
        <p class="mt-4 text-xs text-slate-500">Receivables and overdue credit reflect balances when this report was generated. Customer collections are separate from sales revenue. Expenses include recorded entries only.</p>
    </x-card>
</x-layouts.app>
