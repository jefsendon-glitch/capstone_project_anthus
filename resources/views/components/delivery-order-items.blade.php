@props(['order', 'compact' => false])
@php
    $lines = $order->items->isNotEmpty() ? $order->items : collect([(object) [
        'product_name' => $order->product_name, 'quantity' => $order->quantity,
        'unit_price' => $order->unit_price, 'total_amount' => $order->total_amount, 'container_type' => $order->container_type,
    ]]);
@endphp
@if($compact)
    <details>
        <summary class="cursor-pointer text-sm font-semibold text-primary-700 dark:text-primary-300">{{ $lines->count() }} {{ $lines->count() === 1 ? 'product' : 'products' }} &middot; {{ number_format($lines->sum('quantity')) }} total units</summary>
        <ul class="mt-2 max-h-40 space-y-2 overflow-y-auto pr-2 text-sm" tabindex="0" aria-label="Order item list">
            @foreach($lines as $line)<li class="flex items-start justify-between gap-3"><span class="min-w-0 break-words">{{ $line->product_name }}@if($line->container_type ?? null)<span class="ml-1 text-xs font-medium text-primary-700 dark:text-primary-300">{{ ucfirst($line->container_type) }} Gallon</span>@endif</span><span class="shrink-0 font-semibold tabular-nums">Qty {{ number_format($line->quantity) }}</span></li>@endforeach
        </ul>
    </details>
@else
    <div class="flex flex-wrap items-center justify-between gap-2"><h2 class="text-base font-semibold">Order Items</h2><p class="text-xs text-slate-500 dark:text-slate-400">{{ $lines->count() }} {{ $lines->count() === 1 ? 'product' : 'products' }} &middot; {{ number_format($lines->sum('quantity')) }} total units</p></div>
    @if($lines->count() > 4)<p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Scroll within the item list to view all products.</p>@endif
    <ul class="mt-2 max-h-64 overflow-y-auto divide-y divide-slate-100 rounded-lg border border-slate-200 dark:divide-white/10 dark:border-white/10" tabindex="0" aria-label="Order items, quantities, and prices">
        @foreach($lines as $line)
            <li class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-2 px-3 py-2">
                <div class="min-w-0"><p class="break-words text-sm font-semibold">{{ $line->product_name }}@if($line->container_type ?? null)<span class="ml-1 text-xs font-medium text-primary-700 dark:text-primary-300">{{ ucfirst($line->container_type) }} Gallon</span>@endif</p><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Quantity: <span class="font-semibold">{{ number_format($line->quantity) }}</span> &times; â‚±{{ number_format($line->unit_price, 0) }} each</p></div>
                <p class="text-right text-sm font-semibold tabular-nums"><span class="mr-2 text-xs font-normal text-slate-500 sm:hidden">Line Total</span>â‚±{{ number_format($line->total_amount, 0) }}</p>
            </li>
        @endforeach
    </ul>
    <div class="mt-3 space-y-1 rounded-lg bg-primary-50 p-3 text-sm text-primary-800 dark:bg-primary-500/10 dark:text-primary-200"><div class="flex items-center justify-between"><span>Items subtotal</span><span class="tabular-nums">₱{{ number_format($order->total_amount - $order->delivery_fee, 2) }}</span></div>@if((float) $order->delivery_fee > 0)<div class="flex items-center justify-between"><span>Delivery fee at ₱{{ number_format($order->delivery_fee_rate, 2) }} per gallon</span><span class="tabular-nums">₱{{ number_format($order->delivery_fee, 2) }}</span></div>@endif<div class="flex items-center justify-between border-t border-primary-200 pt-1 dark:border-primary-400/20"><span class="font-semibold">Total Amount</span><span class="text-lg font-semibold tabular-nums">â‚±{{ number_format($order->total_amount, 0) }}</span></div>
@endif
