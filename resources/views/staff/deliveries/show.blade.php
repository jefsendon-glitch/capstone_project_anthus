@php
    $nextStatuses = match ($order->status) {
        'pending' => ['confirmed' => 'Confirm order', 'cancelled' => 'Cancel order'],
        'confirmed' => ['out_for_delivery' => 'Out for delivery', 'cancelled' => 'Cancel order'],
        default => [],
    };
@endphp

<x-layouts.app title="Delivery Order" :heading="'Delivery Order'" :subheading="$order->order_code">
    <div class="mb-6"><a href="{{ route('deliveries.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 transition hover:text-primary-600 dark:text-slate-400"><x-icon name="chevron-left" class="size-4" /> All delivery orders</a></div>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <div class="flex items-start justify-between gap-3"><div><p class="font-mono text-sm font-semibold text-primary-600">{{ $order->order_code }}</p><h2 class="mt-1 font-heading text-xl font-bold text-slate-900 dark:text-white">{{ $order->customer_name }}</h2></div><x-delivery-status-badge :status="$order->status" /></div>
            <div class="mt-6 grid gap-3 sm:grid-cols-2"><div class="rounded-2xl bg-primary-50 p-4 dark:bg-primary-500/10"><p class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Order value</p><p class="mt-1 font-mono text-xl font-bold text-primary-800 dark:text-primary-200">₱{{ number_format($order->total_amount, 2) }}</p></div><div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Payment</p><p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">{{ $order->payment_method ? ucfirst($order->payment_method) : 'To be collected' }}</p></div></div>
            <dl class="mt-6 grid gap-5 text-sm sm:grid-cols-2"><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Product</dt><dd class="mt-1 text-slate-700 dark:text-slate-200">{{ $order->product_name }} × {{ $order->quantity }} <span class="font-mono">(₱{{ number_format($order->unit_price, 2) }} each)</span></dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Preferred delivery date</dt><dd class="mt-1 text-slate-700 dark:text-slate-200">{{ $order->preferred_delivery_date?->format('F d, Y') ?? 'Not scheduled' }}</dd></div><div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Delivery address</dt><dd class="mt-1 leading-relaxed text-slate-700 dark:text-slate-200">{{ $order->customer_address }}</dd></div>@if($order->notes)<div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Customer notes</dt><dd class="mt-1 leading-relaxed text-slate-700 dark:text-slate-200">{{ $order->notes }}</dd></div>@endif</dl>
        </x-card>

        <div class="space-y-6">
            @if(!empty($nextStatuses))
                <x-card><h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Move Order Forward</h2><p class="mt-1 text-xs text-slate-400">Status changes follow the delivery workflow.</p><form method="POST" action="{{ route('deliveries.update-status', $order) }}" class="mt-4 space-y-3">@csrf @method('patch')<x-select name="status">@foreach($nextStatuses as $status => $label)<option value="{{ $status }}">{{ $label }}</option>@endforeach</x-select><x-button type="submit" variant="secondary" class="w-full">Update Status</x-button></form></x-card>
            @endif
            @if(in_array($order->status, ['confirmed', 'out_for_delivery']))
                <x-card class="border-success-100 dark:border-success-500/20"><h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Complete Delivery</h2><p class="mt-1 text-xs text-slate-400">Records the sale, adjusts inventory, and closes this order.</p><form method="POST" action="{{ route('deliveries.fulfill', $order) }}" class="mt-4 space-y-3">@csrf <x-select name="payment_method" required><option value="cash">Cash payment</option><option value="loan">Loan / credit payment</option></x-select><x-button type="submit" class="w-full"><x-icon name="check-circle" class="size-4" /> Mark as Delivered</x-button></form></x-card>
            @endif
            @if(in_array($order->status, ['delivered', 'cancelled']))
                <x-card><div class="flex items-center gap-3"><span class="rounded-xl {{ $order->status === 'delivered' ? 'bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-500' : 'bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-400' }} p-2.5"><x-icon :name="$order->status === 'delivered' ? 'check-circle' : 'x-circle'" class="size-5" /></span><div><h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Order {{ ucfirst($order->status) }}</h2><p class="text-xs text-slate-400">No further status changes are available.</p></div></div></x-card>
            @endif
        </div>
    </div>
</x-layouts.app>
