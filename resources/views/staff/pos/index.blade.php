<x-layouts.app title="Point of Sale" :heading="'Point of Sale'">
    @if(auth()->user()->isAdmin())
        <div class="mb-3 flex justify-end">
            <details class="w-full rounded-lg border border-red-200 p-3 sm:max-w-sm dark:border-red-900" @if($errors->has('reset_confirmation')) open @endif>
                <summary class="cursor-pointer text-xs font-semibold text-danger-600 dark:text-danger-400">Reset Transactions</summary>
                <form method="POST" action="{{ route('pos.reset') }}" class="mt-3 space-y-3" onsubmit="return confirm('Permanently delete all cash and settled credit transactions, including records outside the current filters? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <p class="text-xs leading-relaxed text-slate-500">Deletes cash and fully paid credit sales across all dates and filters. Unpaid credits, customer balances, payments, stock, delivery orders, and saved financial reports stay unchanged. Deleted sales disappear from live sales totals. This cannot be undone.</p>
                    <x-input-label for="pos-reset-confirmation" value="Type RESET to confirm" />
                    <x-text-input id="pos-reset-confirmation" name="reset_confirmation" placeholder="RESET" pattern="RESET" autocomplete="off" required />
                    <x-input-error :messages="$errors->get('reset_confirmation')" />
                    <x-button type="submit" variant="danger" class="w-full">Delete Transactions</x-button>
                </form>
            </details>
        </div>
    @endif
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
