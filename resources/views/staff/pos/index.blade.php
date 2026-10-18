<x-layouts.app title="Point of Sale" :heading="'Point of Sale'">
    @if($errors->any())<div role="alert" class="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2"><div><h2 class="text-base font-semibold">Sales History</h2><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Search and review recorded transactions.</p></div><x-button as="a" :href="route('pos.create')"><x-icon name="plus" class="size-4" /> Record a Sale</x-button></div>
    <x-card padding="p-4" class="mb-4">
        <form method="GET" action="{{ route('pos.index') }}" class="grid grid-cols-2 items-end gap-3 xl:grid-cols-6">
            <div class="col-span-2 min-w-0"><x-input-label for="pos-history-search" value="Search Transactions" /><div class="relative"><x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" /><x-text-input id="pos-history-search" type="search" name="search" :value="request('search')" maxlength="100" placeholder="Transaction, customer, or product..." class="!pl-9" /></div></div>
            <div><x-input-label for="pos-history-type" value="Transaction Type" /><x-select id="pos-history-type" name="type"><option value="">All Types</option><option value="walk-in" @selected(request('type') === 'walk-in')>Walk-in</option><option value="delivery" @selected(request('type') === 'delivery')>Delivery</option></x-select></div>
            <div><x-input-label for="pos-history-scope" value="Recorded By" /><x-select id="pos-history-scope" name="scope"><option value="all">All Sales</option><option value="mine" @selected(request('scope') === 'mine')>My Sales</option></x-select></div>
            <div><x-input-label for="pos-history-from" value="From" /><x-text-input id="pos-history-from" type="date" name="from" :value="request('from')" /></div>
            <div><x-input-label for="pos-history-to" value="To" /><x-text-input id="pos-history-to" type="date" name="to" :value="request('to')" /></div>
            <div class="col-span-2 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-3 dark:border-white/10 xl:col-span-6"><x-button as="a" :href="route('pos.index')" variant="secondary">Reset Filters</x-button><x-button type="submit"><x-icon name="search" class="size-4" /> Apply Filters</x-button></div>
        </form>
    </x-card>

    <x-card padding="p-0">
        @if($transactions->isEmpty())
            <div class="p-6">
                <x-empty-state icon="cart" :title="request()->filled('search') || request()->filled('type') ? 'No matching transactions' : 'No sales recorded yet'" :description="request()->filled('search') || request()->filled('type') ? 'Try a different search or transaction type.' : 'Walk-in and delivery sales you record will appear here.'" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Transaction No.</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">Amount</th>
                            <th class="px-4 py-3">Payment</th><th class="px-4 py-3">Recorded By</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($transactions as $transaction)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">
                                    <a href="{{ route('pos.show', $transaction) }}" class="hover:text-primary-600">{{ $transaction->transaction_code }}</a>
                                </td>
                                <td class="px-4 py-3">
                                    <x-badge :color="$transaction->transaction_type === 'delivery' ? 'secondary' : 'slate'">
                                        {{ $transaction->transaction_type === 'delivery' ? 'Delivery' : 'Walk-in' }}
                                    </x-badge>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $transaction->customer_name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $transaction->product_name }}@if($transaction->container_type)<span class="block text-xs text-slate-500">{{ ucfirst($transaction->container_type) }} Gallon</span>@endif × {{ $transaction->quantity }}</td>
                                <td class="px-4 py-3 font-mono text-sm font-medium text-slate-900 dark:text-white">₱{{ number_format($transaction->total_amount, 0) }}</td>
                                <td class="px-4 py-3">
                                    <x-badge :color="$transaction->payment_method === 'loan' ? 'warning' : 'success'">
                                        {{ $transaction->payment_method === 'loan' ? 'Credit' : 'Cash' }}
                                    </x-badge>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $transaction->processedBy?->name ?? 'Unavailable' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('pos.receipt', ['codes' => $transaction->transaction_code]) }}" class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:text-primary-700">
                                        <x-icon name="tag" class="size-4" /> Receipt
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 bg-slate-50/50 px-4 py-3 dark:border-white/5 dark:bg-white/[0.02] sm:px-6">
                {{ $transactions->links('pagination.pos') }}
            </div>
        @endif
    </x-card>
</x-layouts.app>
