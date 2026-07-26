<x-layouts.app title="Point of Sale" :heading="'Point of Sale'">
    <div class="mb-6 flex items-center justify-between gap-4">
        <form method="GET">
            <x-select name="type" onchange="this.form.submit()" class="w-auto">
                <option value="">All Types</option>
                <option value="walk-in" @selected(request('type') === 'walk-in')>Walk-in</option>
                <option value="delivery" @selected(request('type') === 'delivery')>Delivery</option>
            </x-select>
        </form>

        <x-button as="a" href="{{ route('pos.create') }}">
            <x-icon name="plus" class="size-4" /> Record a Sale
        </x-button>
    </div>

    <x-card padding="p-0">
        @if($transactions->isEmpty())
            <div class="p-6">
                <x-empty-state icon="cart" title="No sales recorded yet" description="Walk-in and delivery sales you record will appear here." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-6 py-3">Code</th>
                            <th class="px-6 py-3">Type</th>
                            <th class="px-6 py-3">Customer</th>
                            <th class="px-6 py-3">Product</th>
                            <th class="px-6 py-3">Amount</th>
                            <th class="px-6 py-3">Payment</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($transactions as $transaction)
                            <tr>
                                <td class="px-6 py-3 text-sm font-medium text-slate-900 dark:text-white">
                                    <a href="{{ route('pos.show', $transaction) }}" class="hover:text-primary-600">{{ $transaction->transaction_code }}</a>
                                </td>
                                <td class="px-6 py-3">
                                    <x-badge :color="$transaction->transaction_type === 'delivery' ? 'secondary' : 'slate'">
                                        {{ $transaction->transaction_type === 'delivery' ? 'Delivery' : 'Walk-in' }}
                                    </x-badge>
                                </td>
                                <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $transaction->customer_name ?? '—' }}</td>
                                <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $transaction->product_name }} × {{ $transaction->quantity }}</td>
                                <td class="px-6 py-3 font-mono text-sm font-medium text-slate-900 dark:text-white">₱{{ number_format($transaction->total_amount, 2) }}</td>
                                <td class="px-6 py-3">
                                    <x-badge :color="$transaction->payment_method === 'loan' ? 'warning' : 'success'">
                                        {{ $transaction->payment_method === 'loan' ? 'Loan' : 'Cash' }}
                                    </x-badge>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('pos.receipt', ['codes' => $transaction->transaction_code]) }}" class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:text-primary-700">
                                        <x-icon name="tag" class="size-4" /> Receipt
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-4 dark:border-white/5">
                {{ $transactions->links() }}
            </div>
        @endif
    </x-card>
</x-layouts.app>
