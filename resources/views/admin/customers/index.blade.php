<x-layouts.app title="Customers" :heading="'Customers'" :subheading="'Customer accounts, orders, sales, and credit balances'">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card variant="tinted" label="Customers" :value="$customerStats['total']" hint="Registered accounts" icon="users" color="primary" />
        <x-stat-card variant="tinted" label="Active Customers" :value="$customerStats['active']" hint="Available to transact" icon="check-circle" color="success" />
        <x-stat-card variant="tinted" label="With Balance" :value="$customerStats['withBalance']" hint="Credit payments due" icon="credit-card" color="warning" />
        <x-stat-card variant="tinted" label="Outstanding Credit" value="₱{{ number_format($customerStats['outstanding'], 2) }}" hint="Across all customers" icon="dollar" color="danger" />
    </div>

    <x-card class="mt-6" padding="p-4">
        <form method="GET" class="grid gap-3 md:grid-cols-[minmax(0,1fr)_11rem_11rem_auto]">
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                <x-text-input type="search" name="search" value="{{ request('search') }}" placeholder="Search customer name or email..." class="pl-10" />
            </div>
            <x-select name="status">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </x-select>
            <x-select name="balance">
                <option value="">All balances</option>
                <option value="outstanding" @selected(request('balance') === 'outstanding')>With balance</option>
            </x-select>
            <div class="flex gap-2">
                <x-button type="submit"><x-icon name="search" class="size-4" /> Filter</x-button>
                @if(request()->hasAny(['search', 'status', 'balance']))
                    <x-button as="a" href="{{ route('admin.customers.index') }}" variant="secondary">Reset</x-button>
                @endif
            </div>
        </form>
    </x-card>

    <x-card class="mt-6" padding="p-0">
        @if($customers->isEmpty())
            <div class="p-6">
                <x-empty-state icon="users" title="No customers found" description="Try changing the filters, or ask customers to register an account." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead class="bg-slate-50/80 dark:bg-white/[0.03]">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-6 py-3.5">Customer</th>
                            <th class="px-6 py-3.5">Activity</th>
                            <th class="px-6 py-3.5">Credit Balance</th>
                            <th class="px-6 py-3.5">Status</th>
                            <th class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($customers as $customer)
                            <tr class="transition hover:bg-slate-50/80 dark:hover:bg-white/[0.03]">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @if($customer->avatar_url)
                                            <img src="{{ $customer->avatar_url }}" alt="{{ $customer->name }}" class="size-10 shrink-0 rounded-full object-cover" />
                                        @else
                                            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-bold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">{{ Str::of($customer->name)->substr(0, 1)->upper() }}</span>
                                        @endif
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.customers.show', $customer) }}" class="block truncate text-sm font-semibold text-slate-900 hover:text-primary-600 dark:text-white">{{ $customer->name }}</a>
                                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $customer->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1.5 text-xs">
                                        <x-badge color="primary"><x-icon name="truck" class="size-3" /> {{ $customer->delivery_orders_count }} orders</x-badge>
                                        <x-badge color="secondary"><x-icon name="cart" class="size-3" /> {{ $customer->sales_transactions_count }} sales</x-badge>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-mono text-sm font-semibold {{ $customer->credit_balance > 0 ? 'text-warning-600 dark:text-warning-500' : 'text-success-600 dark:text-success-500' }}">₱{{ number_format($customer->credit_balance, 2) }}</td>
                                <td class="px-6 py-4"><x-badge :color="$customer->status === 'active' ? 'success' : 'danger'">{{ ucfirst($customer->status) }}</x-badge></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.customers.show', $customer) }}" class="rounded-lg p-2 text-slate-500 transition hover:bg-primary-50 hover:text-primary-600 dark:hover:bg-primary-500/10" title="View customer"><x-icon name="chevron-right" class="size-5" /></a>
                                        <a href="{{ route('admin.customers.edit', $customer) }}" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-primary-600 dark:hover:bg-white/5" title="Edit customer"><x-icon name="pencil" class="size-4" /></a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-4 dark:border-white/5">{{ $customers->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
