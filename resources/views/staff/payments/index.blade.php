<x-layouts.app title="Collections" :heading="'Collections'" :subheading="'Record customer credit payments and review collection history'">
    @if($errors->any())<div role="alert" class="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <div class="mb-4">
        <form method="GET" class="flex max-w-lg gap-2">
            <x-text-input type="search" name="search" value="{{ request('search') }}" placeholder="Search customers..." aria-label="Search customers" maxlength="100" /><x-button type="submit" variant="secondary">Search</x-button><x-button as="a" :href="route('payments.index')" variant="secondary">Clear</x-button>
        </form>
    </div>

    <x-card padding="p-0">
        @if($customers->isEmpty())
            <div class="p-6">
                <x-empty-state icon="users" title="No customers found" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-6 py-3">Customer</th>
                            <th class="px-6 py-3">Outstanding Balance</th>
                            <th class="px-6 py-3 text-right">Record Payment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($customers as $customer)
                            <tr>
                                <td class="px-6 py-3 text-sm">
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $customer->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $customer->email }}</p>
                                </td>
                                <td class="px-6 py-3 font-mono text-sm font-medium {{ $customer->credit_balance > 0 ? 'text-warning-600' : 'text-success-600' }}">
                                    ₱{{ number_format($customer->credit_balance, 2) }}
                                </td>
                                <td class="px-6 py-3 text-right">
                                    @if($customer->credit_balance > 0)
                                    <form method="POST" action="{{ route('payments.store', $customer) }}" class="flex items-center justify-end gap-2">
                                        @csrf
                                        <input type="number" step="0.01" min="0.01" max="{{ $customer->credit_balance }}" aria-label="Payment amount for {{ $customer->name }}" name="amount" placeholder="Amount" required class="w-32 rounded-xl border-0 py-2 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-slate-800 dark:text-white dark:ring-white/10">
                                        <x-button type="submit" variant="secondary">Record</x-button>
                                    </form>
                                    @else<span class="text-sm text-slate-500">No balance due</span>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-4 dark:border-white/5">
                {{ $customers->links() }}
            </div>
        @endif
    </x-card>
    <x-card class="mt-4" padding="p-4">
        <h2 class="text-base font-semibold">{{ auth()->user()->isStaff() ? 'My Collection History' : 'Collection History' }}</h2>
        <p class="mt-1 text-xs text-slate-500">{{ auth()->user()->isStaff() ? 'Payments recorded under your account.' : 'Payments recorded by station staff and Admin.' }}</p>
        <form method="GET" class="my-3 grid items-end gap-2 sm:grid-cols-2 xl:grid-cols-4">
            @if(request()->filled('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
            <label class="text-xs text-slate-500">Customer<x-text-input name="history_search" type="search" :value="request('history_search')" maxlength="100" placeholder="Search collection history..." /></label>
            <label class="text-xs text-slate-500">From<x-text-input name="history_from" type="date" :value="request('history_from')" /></label>
            <label class="text-xs text-slate-500">To<x-text-input name="history_to" type="date" :value="request('history_to')" /></label>
            <div class="flex gap-2"><x-button type="submit" variant="secondary">Filter</x-button><x-button as="a" :href="route('payments.index', request()->only('search'))" variant="secondary">Clear</x-button></div>
        </form>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr class="border-b border-slate-200 text-slate-500 dark:border-white/10"><th class="p-2">Date</th><th class="p-2">Customer</th><th class="p-2 text-right">Amount</th><th class="p-2">Recorded By</th></tr></thead><tbody>
            @forelse($collections as $collection)
                <tr class="border-b border-slate-100 dark:border-white/5"><td class="whitespace-nowrap p-2">{{ $collection->payment_date->format('M d, Y') }}</td><td class="p-2">{{ $collection->customer?->name ?? $collection->customer_name }}</td><td class="p-2 text-right tabular-nums">&#8369;{{ number_format($collection->amount, 2) }}</td><td class="p-2">{{ $collection->recordedBy?->name ?? $collection->staff_name ?? 'Unavailable' }}</td></tr>
            @empty<tr><td colspan="4" class="p-3 text-slate-500">No collections found. Try another customer or date range.</td></tr>@endforelse
        </tbody></table></div><div class="mt-3">{{ $collections->links() }}</div>
    </x-card>
</x-layouts.app>
