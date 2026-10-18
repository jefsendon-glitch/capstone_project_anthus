@php
    $nextStatuses = match ($order->status) {
        'pending' => ['confirmed' => 'Confirm order', 'cancelled' => 'Cancel order'],
        'confirmed' => ['out_for_delivery' => 'Out for delivery', 'cancelled' => 'Cancel order'],
        default => [],
    };
@endphp

<x-layouts.app title="Delivery Order" :heading="'Delivery Order'" :subheading="$order->order_code">
    <div class="mb-3"><a href="{{ route('deliveries.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 transition hover:text-primary-600 dark:text-slate-400"><x-icon name="chevron-left" class="size-4" /> All delivery orders</a></div>
    @if($errors->any())<div role="alert" class="mb-4 rounded-lg bg-danger-50 p-3 text-sm text-danger-700 dark:bg-danger-500/10 dark:text-danger-300">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <div class="grid grid-cols-1 items-start gap-4 md:grid-cols-3">
        <x-card padding="p-4" class="md:col-span-2">
            <div class="flex items-start justify-between gap-3"><div><p class="font-mono text-sm font-semibold text-primary-600">{{ $order->order_code }}</p><h2 class="mt-1 text-base font-semibold text-slate-900 dark:text-white">{{ $order->customer_name }}</h2></div><x-delivery-status-badge :status="$order->status" /></div>
            <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Payment: <span class="font-semibold text-slate-900 dark:text-white">{{ $order->payment_method === 'loan' ? 'Credit' : ($order->payment_method === 'cash' ? 'Cash on Delivery' : 'Not specified') }}</span></p>
            <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2"><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Preferred delivery date</dt><dd class="mt-1 text-slate-700 dark:text-slate-200">{{ $order->preferred_delivery_date?->format('F d, Y') ?? 'Not scheduled' }}</dd></div><div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Delivery address</dt><dd class="mt-1 leading-relaxed text-slate-700 dark:text-slate-200">{{ $order->customer_address }}</dd></div>@if($order->notes)<div class="sm:col-span-2"><dt class="sr-only">Customer Notes</dt><dd><details><summary class="cursor-pointer text-sm font-medium text-slate-500 dark:text-slate-400">Customer Notes</summary><p class="mt-2 max-h-24 overflow-y-auto break-words text-sm">{{ $order->notes }}</p></details></dd></div>@endif</dl>
            <div class="mt-3"><x-delivery-order-items :order="$order" /></div>
        </x-card>

        <div class="space-y-3">
            @if(!empty($nextStatuses))
                <x-card padding="p-4"><h2 class="text-base font-semibold">Next Action</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $order->status === 'pending' ? 'Review every item and the delivery address, then confirm this order.' : 'When all items are ready, mark this order as out for delivery.' }}</p>
                    <form method="POST" action="{{ route('deliveries.update-status', $order) }}" class="mt-3">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $order->status === 'pending' ? 'confirmed' : 'out_for_delivery' }}"><x-button type="submit" class="w-full">{{ $order->status === 'pending' ? 'Confirm Order' : 'Mark Out for Delivery' }}</x-button></form>
                    <details class="mt-3 border-t border-slate-100 pt-3 dark:border-white/10"><summary class="cursor-pointer text-sm text-slate-500 dark:text-slate-400">Other Actions</summary><form method="POST" action="{{ route('deliveries.update-status', $order) }}" class="mt-3" onsubmit="return confirm('Cancel this entire delivery order, including all its items?')">@csrf @method('PATCH')<input type="hidden" name="status" value="cancelled"><x-button type="submit" variant="danger" class="w-full">Cancel Entire Order</x-button></form></details>
                </x-card>
            @endif
            @if(in_array($order->status, ['confirmed', 'out_for_delivery']))
                <x-card padding="p-4" class="border-success-100 dark:border-success-500/20"><h2 class="text-base font-semibold text-slate-900 dark:text-white">Complete Delivery</h2><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Mark delivered only after all items are received. This records the sale and deducts stock.</p><form method="POST" action="{{ route('deliveries.fulfill', $order) }}" class="mt-3 space-y-3">@csrf @if($order->payment_method)<div class="rounded-xl bg-slate-50 p-3 text-sm dark:bg-white/5"><span class="text-slate-500 dark:text-slate-400">Payment:</span> <span class="font-semibold text-slate-900 dark:text-white">{{ $order->payment_method === 'loan' ? 'Credit' : 'Cash' }}</span></div>@else <x-select name="payment_method" required><option value="cash">Cash payment</option><option value="loan">Credit payment</option></x-select>@endif <x-button type="submit" class="w-full"><x-icon name="check-circle" class="size-4" /> Mark as Delivered</x-button></form></x-card>
            @endif
            @if(in_array($order->status, ['delivered', 'cancelled']))
                <x-card padding="p-4"><div class="flex items-center gap-3"><span class="rounded-xl {{ $order->status === 'delivered' ? 'bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-500' : 'bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-400' }} p-2.5"><x-icon :name="$order->status === 'delivered' ? 'check-circle' : 'x-circle'" class="size-5" /></span><div><h2 class="text-base font-semibold text-slate-900 dark:text-white">Order {{ ucfirst($order->status) }}</h2><p class="text-xs text-slate-500 dark:text-slate-400">No further status changes are available.</p></div></div></x-card>
            @endif
        </div>
    </div>
</x-layouts.app>
