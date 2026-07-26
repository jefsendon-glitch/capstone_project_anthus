<x-layouts.app title="Customer Payments" :heading="'Customer Payments'">
    <div class="mb-6">
        <form method="GET" class="max-w-xs">
            <x-text-input type="search" name="search" value="{{ request('search') }}" placeholder="Search customers..." />
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
                                    <form method="POST" action="{{ route('payments.store', $customer) }}" class="flex items-center justify-end gap-2">
                                        @csrf
                                        <input type="number" step="0.01" min="0.01" name="amount" placeholder="Amount" required class="w-32 rounded-xl border-0 py-2 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-slate-800 dark:text-white dark:ring-white/10">
                                        <x-button type="submit" variant="secondary">Record</x-button>
                                    </form>
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
</x-layouts.app>
