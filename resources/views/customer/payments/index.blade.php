<x-layouts.app title="My Payments" :heading="'My Payments'">
    <x-card class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Outstanding Balance</p>
                <p class="mt-1 font-mono text-2xl font-bold {{ auth()->user()->credit_balance > 0 ? 'text-warning-600' : 'text-success-600' }}">
                    ₱{{ number_format(auth()->user()->credit_balance, 2) }}
                </p>
            </div>
            <p class="text-sm text-slate-400">Payments are recorded by staff when received.</p>
        </div>
    </x-card>

    <x-card padding="p-0">
        @if($payments->isEmpty())
            <div class="p-6">
                <x-empty-state icon="dollar" title="No payments yet" description="Payments you make toward your balance will appear here." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Amount</th>
                            <th class="px-6 py-3">Recorded By</th>
                            <th class="px-6 py-3">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($payments as $payment)
                            <tr>
                                <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $payment->payment_date->format('M d, Y') }}</td>
                                <td class="px-6 py-3 font-mono text-sm font-semibold text-slate-900 dark:text-white">₱{{ number_format($payment->amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $payment->staff_name ?? '—' }}</td>
                                <td class="px-6 py-3 text-sm text-slate-400">{{ $payment->notes ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-4 dark:border-white/5">
                {{ $payments->links() }}
            </div>
        @endif
    </x-card>
</x-layouts.app>
