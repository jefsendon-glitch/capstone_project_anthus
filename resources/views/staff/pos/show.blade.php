<x-layouts.app title="Sale Details" :heading="'Sale Details'">
    <x-card class="mx-auto max-w-xl">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $transaction->transaction_code }}</p>
                <p class="mt-1 font-mono text-2xl font-bold text-slate-900 dark:text-white">₱{{ number_format($transaction->total_amount, 2) }}</p>
            </div>
            <x-badge :color="$transaction->transaction_type === 'delivery' ? 'secondary' : 'slate'">
                {{ $transaction->transaction_type === 'delivery' ? 'Delivery' : 'Walk-in' }}
            </x-badge>
        </div>

        <dl class="mt-6 space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-slate-400">Product</dt><dd class="font-medium text-slate-900 dark:text-white">{{ $transaction->product_name }} × {{ $transaction->quantity }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Unit price</dt><dd class="font-mono text-slate-900 dark:text-white">₱{{ number_format($transaction->unit_price, 2) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Recorded by</dt><dd class="font-medium text-slate-900 dark:text-white">{{ $transaction->processedBy?->name }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Customer</dt><dd class="font-medium text-slate-900 dark:text-white">{{ $transaction->customer_name ?? '—' }}</dd></div>
            <div class="flex justify-between">
                <dt class="text-slate-400">Payment</dt>
                <dd><x-badge :color="$transaction->payment_method === 'loan' ? 'warning' : 'success'">{{ $transaction->payment_method === 'loan' ? 'Loan' : 'Cash' }}</x-badge></dd>
            </div>
        </dl>

        <div class="mt-6 flex gap-3">
            <x-button as="a" href="{{ route('pos.index') }}" variant="secondary" class="w-full">Back to Transactions</x-button>
            <x-button as="a" href="{{ route('pos.receipt', ['codes' => $transaction->transaction_code]) }}" class="w-full">
                <x-icon name="tag" class="size-4" /> Print Receipt
            </x-button>
        </div>
    </x-card>
</x-layouts.app>
