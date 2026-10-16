<x-layouts.app title="Point of Sale" :heading="'Point of Sale'">
    @if($errors->any())<div role="alert" class="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @push('admin-reset-actions')
    @if(auth()->user()->isAdmin())
        <x-reset-dot :action="route('pos.reset')" title="Reset Transactions" description="Deletes cash and fully paid credit sales across all dates and filters. Unpaid credit records, balances, payments, stock, delivery orders and saved reports remain. Deleted sales disappear from live sales totals. This cannot be undone." />
    @endif
    @endpush
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <form method="GET" action="{{ route('pos.index') }}" class="grid min-w-0 flex-1 grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center">
            <div class="relative col-span-2 min-w-0 sm:w-72">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                <x-text-input type="search" name="search" :value="request('search')" maxlength="100" placeholder="Transaction no., customer or product..." aria-label="Search POS transactions" class="!py-2 !pl-9" />
            </div>
            <x-select name="type" aria-label="Transaction type" class="!py-2 sm:!w-auto">
                <option value="">All Transactions</option>
                <option value="walk-in" @selected(request('type') === 'walk-in')>Walk-in</option>
                <option value="delivery" @selected(request('type') === 'delivery')>Delivery</option>
            </x-select>
            <x-select name="scope" aria-label="Recorded by" class="!py-2 sm:!w-auto"><option value="all">All Sales</option><option value="mine" @selected(request('scope') === 'mine')>My Sales</option></x-select>
            <label class="text-xs text-slate-500">From<x-text-input type="date" name="from" :value="request('from')" class="!py-2" /></label>
            <label class="text-xs text-slate-500">To<x-text-input type="date" name="to" :value="request('to')" class="!py-2" /></label>
            <x-button type="submit" variant="secondary" class="!py-2">Search</x-button>
            <x-button as="a" href="{{ route('pos.index') }}" variant="secondary" class="!py-2">Reset</x-button>
        </form>

        <x-button as="a" href="{{ route('pos.create') }}">
            <x-icon name="plus" class="size-4" /> Record a Sale
        </x-button>
    </div>

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
                            <th class="px-6 py-3">Transaction No.</th>
                            <th class="px-6 py-3">Type</th>
                            <th class="px-6 py-3">Customer</th>
                            <th class="px-6 py-3">Product</th>
                            <th class="px-6 py-3">Amount</th>
                            <th class="px-6 py-3">Payment</th><th class="px-6 py-3">Recorded By</th>
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
                                        {{ $transaction->payment_method === 'loan' ? 'Credit' : 'Cash' }}
                                    </x-badge>
                                </td>
                                <td class="px-6 py-3 text-sm">{{ $transaction->processedBy?->name ?? 'Unavailable' }}</td>
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
            <div class="border-t border-slate-100 bg-slate-50/50 px-4 py-3 dark:border-white/5 dark:bg-white/[0.02] sm:px-6">
                {{ $transactions->links('pagination.pos') }}
            </div>
        @endif
    </x-card>
</x-layouts.app>
