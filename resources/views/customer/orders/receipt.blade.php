<x-layouts.app title="Delivery Receipt" :heading="'Delivery Receipt'" :subheading="$order->order_code">
    <div class="mx-auto max-w-xl">
        <div class="no-print mb-4 flex items-center justify-between gap-3">
            <x-button as="a" href="{{ route('customer.orders.index') }}" variant="secondary">
                <x-icon name="chevron-left" class="size-4" /> Back to My Orders
            </x-button>
            <x-button type="button" onclick="window.print()">
                <x-icon name="tag" class="size-4" /> Print Receipt
            </x-button>
        </div>

        <x-card class="print:shadow-none print:ring-0">
            <div class="text-center">
                <p class="font-heading text-lg font-bold text-slate-900 dark:text-white">{{ $business->business_name }}</p>
                @if($business->address)<p class="text-xs text-slate-500 dark:text-slate-400">{{ $business->address }}</p>@endif
                @if($business->contact_number || $business->contact_email)<p class="text-xs text-slate-500 dark:text-slate-400">{{ collect([$business->contact_number, $business->contact_email])->filter()->implode(' · ') }}</p>@endif
            </div>

            <div class="mt-4 space-y-1 border-t border-dashed border-slate-300 pt-4 text-sm dark:border-white/10">
                <div class="flex justify-between"><span class="text-slate-400">Order no.</span><span class="font-mono font-medium text-slate-900 dark:text-white">{{ $order->order_code }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Delivered</span><span class="text-slate-900 dark:text-white">{{ $order->delivered_at?->format('M j, Y g:i A') }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Payment</span><span class="text-slate-900 dark:text-white">{{ $order->payment_method === 'loan' ? 'Credit / Loan' : 'Cash' }}</span></div>
            </div>

            <div class="mt-4 border-t border-dashed border-slate-300 pt-4 dark:border-white/10">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-xs uppercase tracking-wide text-slate-400"><th class="pb-2 font-medium">Item</th><th class="pb-2 text-center font-medium">Qty</th><th class="pb-2 text-right font-medium">Price</th><th class="pb-2 text-right font-medium">Amount</th></tr></thead>
                    <tbody>
                        @foreach($transactions as $line)
                            <tr class="border-t border-slate-100 dark:border-white/5"><td class="py-2 text-slate-900 dark:text-white">{{ $line->product_name }}</td><td class="py-2 text-center text-slate-600 dark:text-slate-300">{{ $line->quantity }}</td><td class="py-2 text-right font-mono text-slate-600 dark:text-slate-300">₱{{ number_format($line->unit_price, 2) }}</td><td class="py-2 text-right font-mono font-medium text-slate-900 dark:text-white">₱{{ number_format($line->total_amount, 2) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-300 pt-4 text-base font-bold text-slate-900 dark:border-white/10 dark:text-white"><span>Total</span><span class="font-mono">₱{{ number_format($total, 2) }}</span></div>
            @if($order->payment_method === 'loan')<p class="mt-3 text-center text-xs text-slate-500">Credit payment is due within 14 days of delivery.</p>@endif
            <p class="mt-6 text-center text-xs text-slate-400">Thank you for your purchase!</p>
        </x-card>
    </div>
</x-layouts.app>
