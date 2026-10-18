<x-layouts.app title="Order Details" heading="Order Details" :subheading="$order->order_code">
    <x-card padding="p-4" class="mx-auto max-w-3xl">
        <div class="flex flex-wrap items-center justify-between gap-2"><p class="font-mono text-sm font-semibold text-primary-700 dark:text-primary-300">{{ $order->order_code }}</p><x-delivery-status-badge :status="$order->status" /></div>
        <dl class="my-3 grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-xs text-slate-500 dark:text-slate-400">Payment Method</dt><dd class="mt-1 font-medium">{{ $order->payment_method === 'loan' ? 'Credit (due within 14 days after delivery)' : ($order->payment_method === 'cash' ? 'Cash on Delivery' : 'To be confirmed') }}</dd></div>
            <div><dt class="text-xs text-slate-500 dark:text-slate-400">Preferred Delivery Date</dt><dd class="mt-1 font-medium">{{ $order->preferred_delivery_date?->format('M d, Y') ?? 'Not specified' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-xs text-slate-500 dark:text-slate-400">Delivery Address</dt><dd class="mt-1 break-words">{{ $order->customer_address }}</dd></div>
        </dl>
        <div class="border-t border-slate-100 pt-3 dark:border-white/10"><x-delivery-order-items :order="$order" /></div>
        <div class="mt-3 flex flex-col gap-2 border-t border-slate-100 pt-3 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between"><p class="text-xs text-slate-500 dark:text-slate-400">Placed orders cannot be cancelled by customers.</p><x-button as="a" :href="route('customer.orders.index')" variant="secondary">Back to My Orders</x-button></div>
    </x-card>
</x-layouts.app>
