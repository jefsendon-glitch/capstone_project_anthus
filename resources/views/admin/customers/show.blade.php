<x-layouts.app :title="$customer->name" :heading="$customer->name" :subheading="'Customer account, credit, and transaction history'">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 transition hover:text-primary-600 dark:text-slate-400"><x-icon name="chevron-left" class="size-4" /> All customers</a>
        <x-button as="a" href="{{ route('admin.customers.edit', $customer) }}" variant="secondary"><x-icon name="pencil" class="size-4" /> Edit customer</x-button>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-card>
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-primary-50 text-lg font-bold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">{{ Str::of($customer->name)->substr(0, 1)->upper() }}</span>
                    <div><h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Profile</h2><p class="text-xs text-slate-400">Customer details</p></div>
                </div>
                <x-badge :color="$customer->status === 'active' ? 'success' : 'danger'">{{ ucfirst($customer->status) }}</x-badge>
            </div>
            <dl class="mt-5 space-y-4 text-sm">
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Email</dt><dd class="mt-1 break-all font-medium text-slate-900 dark:text-white">{{ $customer->email }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Contact number</dt><dd class="mt-1 font-medium text-slate-900 dark:text-white">{{ $customer->contact_number ?? '—' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Address</dt><dd class="mt-1 leading-relaxed text-slate-600 dark:text-slate-300">{{ $customer->address ?? '—' }}</dd></div>
            </dl>
        </x-card>

        <x-card class="border-warning-100 dark:border-warning-500/20">
            <div class="flex items-center justify-between"><div><h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Credit Balance</h2><p class="text-xs text-slate-400">Current amount due</p></div><span class="rounded-xl bg-warning-50 p-2.5 text-warning-600 dark:bg-warning-500/10 dark:text-warning-500"><x-icon name="credit-card" class="size-5" /></span></div>
            <p class="mt-4 font-mono text-3xl font-bold {{ $customer->credit_balance > 0 ? 'text-warning-600 dark:text-warning-500' : 'text-success-600 dark:text-success-500' }}">₱{{ number_format($customer->credit_balance, 2) }}</p>
            <p class="mt-1 text-xs text-slate-400">Payments recorded: ₱{{ number_format($customerSummary['paymentTotal'], 2) }}</p>
            <form method="POST" action="{{ route('payments.store', $customer) }}" class="mt-5 flex items-center gap-2">
                @csrf
                <x-text-input type="number" step="0.01" min="0.01" :max="$customer->credit_balance" name="amount" placeholder="Payment amount" required :disabled="$customer->credit_balance <= 0" />
                <x-button type="submit" variant="secondary" :disabled="$customer->credit_balance <= 0">Record</x-button>
            </form>
        </x-card>

        <x-card>
            <h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Customer Activity</h2>
            <div class="mt-5 space-y-3">
                <div class="flex items-center justify-between rounded-xl bg-primary-50 px-3 py-2.5 dark:bg-primary-500/10"><span class="flex items-center gap-2 text-sm font-medium text-primary-700 dark:text-primary-300"><x-icon name="cart" class="size-4" /> POS purchases</span><span class="font-mono text-sm font-bold text-primary-700 dark:text-primary-300">₱{{ number_format($customerSummary['salesTotal'], 2) }}</span></div>
                <div class="flex items-center justify-between rounded-xl bg-delivery-50 px-3 py-2.5 dark:bg-delivery-500/10"><span class="flex items-center gap-2 text-sm font-medium text-delivery-700 dark:text-delivery-400"><x-icon name="truck" class="size-4" /> Delivery orders</span><span class="font-mono text-sm font-bold text-delivery-700 dark:text-delivery-400">₱{{ number_format($customerSummary['orderTotal'], 2) }}</span></div>
                <p class="pt-1 text-xs text-slate-400">Joined {{ $customer->created_at->format('M d, Y') }}</p>
            </div>
        </x-card>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card padding="p-0">
            <div class="flex items-center justify-between px-6 py-4"><div><h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Recent POS Purchases</h2><p class="text-xs text-slate-400">Walk-in sales linked to this customer</p></div><x-icon name="cart" class="size-5 text-primary-500" /></div>
            <div class="divide-y divide-slate-100 dark:divide-white/5">
                @forelse($customer->salesTransactions as $transaction)
                    <a href="{{ route('pos.show', $transaction) }}" class="flex items-center justify-between gap-3 px-6 py-3 transition hover:bg-slate-50 dark:hover:bg-white/5"><div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $transaction->product_name }} × {{ $transaction->quantity }}</p><p class="text-xs text-slate-400">{{ $transaction->transaction_code }} · {{ $transaction->created_at->format('M d, Y') }}</p></div><p class="shrink-0 font-mono text-sm font-semibold text-slate-900 dark:text-white">₱{{ number_format($transaction->total_amount, 2) }}</p></a>
                @empty
                    <p class="px-6 pb-6 text-sm text-slate-500 dark:text-slate-400">No POS purchases recorded for this customer.</p>
                @endforelse
            </div>
        </x-card>

        <x-card padding="p-0">
            <div class="flex items-center justify-between px-6 py-4"><div><h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Recent Delivery Orders</h2><p class="text-xs text-slate-400">Orders placed through the customer module</p></div><x-icon name="truck" class="size-5 text-delivery-500" /></div>
            <div class="divide-y divide-slate-100 dark:divide-white/5">
                @forelse($customer->deliveryOrders as $order)
                    <a href="{{ route('deliveries.show', $order) }}" class="flex items-center justify-between gap-3 px-6 py-3 transition hover:bg-slate-50 dark:hover:bg-white/5"><div class="min-w-0"><p class="font-mono text-xs font-semibold text-primary-600">{{ $order->order_code }}</p><p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ $order->product_name }} × {{ $order->quantity }}</p></div><div class="shrink-0 text-right"><x-delivery-status-badge :status="$order->status" /><p class="mt-1 font-mono text-xs text-slate-500 dark:text-slate-400">₱{{ number_format($order->total_amount, 2) }}</p></div></a>
                @empty
                    <p class="px-6 pb-6 text-sm text-slate-500 dark:text-slate-400">No delivery orders recorded for this customer.</p>
                @endforelse
            </div>
        </x-card>
    </div>

    <x-card class="mt-6" padding="p-0">
        <div class="flex items-center justify-between px-6 py-4"><div><h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Payment History</h2><p class="text-xs text-slate-400">Payments recorded against customer credit</p></div><x-icon name="credit-card" class="size-5 text-success-500" /></div>
        <div class="divide-y divide-slate-100 dark:divide-white/5">
            @forelse($customer->payments as $payment)
                <div class="flex items-center justify-between gap-3 px-6 py-3"><div><p class="font-mono text-sm font-semibold text-success-600 dark:text-success-500">₱{{ number_format($payment->amount, 2) }}</p><p class="text-xs text-slate-400">{{ $payment->payment_date->format('M d, Y') }} · {{ $payment->recordedBy?->name ?? 'System' }}</p></div>@if($payment->notes)<span class="max-w-56 truncate text-xs text-slate-500 dark:text-slate-400">{{ $payment->notes }}</span>@endif</div>
            @empty
                <p class="px-6 pb-6 text-sm text-slate-500 dark:text-slate-400">No payments recorded yet.</p>
            @endforelse
        </div>
    </x-card>
</x-layouts.app>
