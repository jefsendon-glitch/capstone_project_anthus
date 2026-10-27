<x-layouts.app title="My Dashboard" heading="Dashboard" :subheading="'Manage your deliveries, payments, and account balance'">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0"><h2 class="break-words text-lg font-semibold">Welcome, {{ auth()->user()->first_name ?: auth()->user()->name }}</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $station->business_name }}</p></div>
        <div class="flex flex-wrap gap-2"><x-button as="a" :href="route('customer.orders.create')"><x-icon name="plus" class="size-4" /> Place an Order</x-button><x-button as="a" :href="route('customer.gallons')" variant="secondary">My Gallons</x-button></div>
    </div>
    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-3">
        <x-stat-card compact label="Outstanding Balance" :value="mb_chr(8369, 'UTF-8').number_format(auth()->user()->credit_balance, 2)" :hint="auth()->user()->credit_balance > 0 ? 'Unpaid credit purchases' : 'Your account is settled'" icon="credit-card" :color="auth()->user()->credit_balance > 0 ? 'warning' : 'success'" />
        <x-stat-card compact label="Credit Access" :value="ucfirst(auth()->user()->credit_status ?? 'active')" hint="Managed by the station" icon="check-circle" :color="auth()->user()->credit_status === 'suspended' ? 'danger' : 'success'" />
        <x-stat-card compact label="Next Credit Due" :value="$nextDueDate ?? 'None recorded'" hint="For unpaid credit purchases" icon="clock" color="primary" />
        <x-stat-card compact label="Total Orders" :value="$totalOrders" hint="All delivery orders" icon="truck" color="primary" />
        <x-stat-card compact label="Active Orders" :value="$pendingOrders" hint="Being processed" icon="clock" color="delivery" />
        <x-stat-card compact label="Total Purchases" :value="mb_chr(8369, 'UTF-8').number_format($totalSpent, 2)" hint="Recorded cash and credit sales" icon="dollar" color="secondary" />
    </div>

    @if(auth()->user()->credit_status === 'suspended')
        <div class="mb-4 mt-4 rounded-xl border border-danger-200 bg-danger-50 p-4 text-danger-800 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-200">
            <div class="flex gap-3"><x-icon name="alert-triangle" class="mt-0.5 size-5 shrink-0" /><div><p class="font-semibold">Credit access suspended</p><p class="mt-1 text-sm">Your credit account is suspended. Contact the station to review your balance and credit access. Cash payments remain available.</p></div></div>
        </div>
    @endif


    <div class="mt-4 grid items-start gap-4">
        <x-card padding="p-0">
            <details class="group">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-xl px-4 py-3 outline-none transition hover:bg-slate-50 focus-visible:ring-2 focus-visible:ring-primary-500 dark:hover:bg-white/5">
                    <span><span class="block text-sm font-semibold text-slate-900 dark:text-white">Current Delivery</span>@if($currentOrder)<span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ $currentOrder->order_code }} · {{ $currentOrder->items_summary }}</span>@else<span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">No active deliveries</span>@endif</span>
                    <span class="flex shrink-0 items-center gap-2">@if($currentOrder)<x-delivery-status-badge :status="$currentOrder->status" />@endif<x-icon name="chevron-down" class="size-4 text-slate-500 transition-transform group-open:rotate-180" /></span>
                </summary>
                <div class="border-t border-slate-100 px-4 py-3 dark:border-white/10">
                    @if($currentOrder)
                        <dl class="grid gap-3 text-sm sm:grid-cols-2">
                            <div><dt class="text-xs text-slate-500 dark:text-slate-400">Preferred delivery date</dt><dd class="mt-1 font-medium">{{ $currentOrder->preferred_delivery_date?->format('M d, Y') ?? 'Not specified' }}</dd></div>
                            <div><dt class="text-xs text-slate-500 dark:text-slate-400">Order total</dt><dd class="mt-1 font-semibold tabular-nums">{{ mb_chr(8369, 'UTF-8') }}{{ number_format($currentOrder->total_amount, 2) }}</dd></div>
                            <div class="sm:col-span-2"><dt class="text-xs text-slate-500 dark:text-slate-400">Delivery address</dt><dd class="mt-1 break-words">{{ $currentOrder->customer_address }}</dd></div>
                        </dl>
                        <div class="mt-3"><x-button as="a" :href="route('customer.orders.show', $currentOrder)" variant="secondary">View Delivery Details <x-icon name="chevron-right" class="size-4" /></x-button>@if($pendingOrders > 1)<p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Showing your latest active order. There are {{ $pendingOrders }} active orders.</p>@endif</div>
                    @else
                        <x-empty-state icon="truck" title="No active deliveries" description="Your next delivery will appear here after you place an order." />
                    @endif
                </div>
            </details>
        </x-card>
    </div>

    <div class="mt-4 grid items-start gap-4 lg:grid-cols-2">
        <x-card padding="p-0">
            <details class="group" @if(request()->boolean('history_open')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-xl px-4 py-3 outline-none transition hover:bg-slate-50 focus-visible:ring-2 focus-visible:ring-primary-500 dark:hover:bg-white/5">
                    <span><span class="block text-sm font-semibold text-slate-900 dark:text-white">Order History</span><span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">Showing {{ $recentOrders->count() }} of {{ $recentOrders->total() }} orders</span></span>
                    <x-icon name="chevron-down" class="size-4 shrink-0 text-slate-500 transition-transform group-open:rotate-180" />
                </summary>
                <div class="border-t border-slate-100 dark:border-white/10">
                    <div class="flex justify-end px-4 py-2"><a href="{{ route('customer.orders.index') }}" class="text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-300">View all orders</a></div>
                    <div class="divide-y divide-slate-100 dark:divide-white/5">
                        @forelse($recentOrders as $order)
                            <a href="{{ route('customer.orders.show', $order) }}" class="flex items-center justify-between gap-3 px-4 py-3 transition hover:bg-slate-50 dark:hover:bg-white/5">
                                <div class="min-w-0"><p class="font-mono text-sm font-semibold text-primary-700 dark:text-primary-300">{{ $order->order_code }}</p><p class="text-xs text-slate-500 dark:text-slate-400">{{ $order->created_at->format('M d, Y') }}</p><p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ $order->items_summary }}</p><p class="text-xs text-slate-500 dark:text-slate-400">{{ mb_chr(8369, 'UTF-8') }}{{ number_format($order->total_amount, 2) }}</p></div>
                                <x-delivery-status-badge :status="$order->status" />
                            </a>
                        @empty
                            <div class="p-4"><x-empty-state icon="truck" title="No orders yet" description="Place your first delivery order when you need water." /></div>
                        @endforelse
                    </div>
                    @if($recentOrders->lastPage() > 1)
                        <nav aria-label="Order history pages" class="flex items-center justify-between border-t border-slate-100 px-4 py-3 dark:border-white/10">
                            @if($recentOrders->onFirstPage())
                                <span aria-disabled="true" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-400">Previous</span>
                            @else
                                <a href="{{ request()->fullUrlWithQuery(['orders_page' => $recentOrders->currentPage() - 1, 'history_open' => 1]) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-primary-700 hover:bg-primary-50 dark:text-primary-300 dark:hover:bg-white/5">Previous</a>
                            @endif
                            <span class="text-xs text-slate-500">Page {{ $recentOrders->currentPage() }} of {{ $recentOrders->lastPage() }}</span>
                            @if($recentOrders->hasMorePages())
                                <a href="{{ request()->fullUrlWithQuery(['orders_page' => $recentOrders->currentPage() + 1, 'history_open' => 1]) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-primary-700 hover:bg-primary-50 dark:text-primary-300 dark:hover:bg-white/5">Next</a>
                            @else
                                <span aria-disabled="true" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-400">Next</span>
                            @endif
                        </nav>
                    @endif
                </div>
            </details>
        </x-card>
        <x-card padding="p-0">
            <details class="group">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-xl px-4 py-3 outline-none transition hover:bg-slate-50 focus-visible:ring-2 focus-visible:ring-primary-500 dark:hover:bg-white/5">
                    <span><span class="block text-sm font-semibold text-slate-900 dark:text-white">Recent Payments</span><span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">Latest {{ $recentPayments->count() }} payments</span></span>
                    <x-icon name="chevron-down" class="size-4 shrink-0 text-slate-500 transition-transform group-open:rotate-180" />
                </summary>
                <div class="border-t border-slate-100 dark:border-white/10">
                    <div class="flex justify-end px-4 py-2"><a href="{{ route('customer.payments.index') }}" class="text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-300">View all payments</a></div>
                    <div class="divide-y divide-slate-100 dark:divide-white/5">
                        @forelse($recentPayments as $payment)
                            <div class="flex items-center justify-between px-4 py-3"><div><p class="font-mono text-sm font-bold text-success-600 dark:text-success-500">{{ mb_chr(8369, 'UTF-8') }}{{ number_format($payment->amount, 2) }}</p><p class="text-xs text-slate-500 dark:text-slate-400">{{ $payment->payment_date->format('M d, Y') }}</p></div><span class="rounded-xl bg-success-50 p-2 text-success-600 dark:bg-success-500/10 dark:text-success-500"><x-icon name="check-circle" class="size-4" /></span></div>
                        @empty
                            <div class="p-4"><x-empty-state icon="credit-card" title="No payments yet" description="Recorded payments will appear here." /></div>
                        @endforelse
                    </div>
                </div>
            </details>
        </x-card>
    </div>
</x-layouts.app>
