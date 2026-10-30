<x-layouts.app title="Receipt" :heading="'Receipt'">
    <div class="mx-auto max-w-xl">
        <div class="no-print mb-4 flex items-center justify-between gap-3">
            <x-button as="a" href="{{ route('pos.index') }}" variant="secondary">
                <x-icon name="chevron-left" class="size-4" /> Back to Transactions
            </x-button>
            <x-button type="button" onclick="window.print()">
                <x-icon name="tag" class="size-4" /> Print Receipt
            </x-button>
        </div>

        <x-card class="print:shadow-none print:ring-0">
            <div class="text-center">
                <img src="{{ asset('images/shaunti-water-logo.svg') }}" alt="{{ $business->business_name }} logo" class="mx-auto mb-3 h-14 w-36 object-contain print:h-12">
                <p class="font-heading text-lg font-bold text-slate-900 dark:text-white">{{ $business->business_name }}</p>
                @if($business->address)
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $business->address }}</p>
                @endif
                @if($business->contact_number || $business->contact_email)
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ collect([$business->contact_number, $business->contact_email])->filter()->implode(' · ') }}
                    </p>
                @endif
            </div>

            <div class="mt-4 border-t border-dashed border-slate-300 pt-4 text-sm dark:border-white/10">
                <div class="flex justify-between"><span class="text-slate-400">Receipt No.</span><span class="font-mono font-medium text-slate-900 dark:text-white">{{ $receiptNumber }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Date</span><span class="text-slate-900 dark:text-white">{{ $transactions->first()->created_at->format('M j, Y g:i A') }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Type</span><span class="text-slate-900 dark:text-white">{{ $transactions->first()->transaction_type === 'delivery' ? 'Delivery' : 'Walk-in' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Cashier</span><span class="text-slate-900 dark:text-white">{{ $transactions->first()->processedBy?->name ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Customer</span><span class="text-slate-900 dark:text-white">{{ $transactions->first()->customer?->name ?? $transactions->first()->customer_name ?? 'Walk-in guest' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Payment</span><span class="text-slate-900 dark:text-white">{{ $transactions->first()->payment_method === 'loan' ? 'Credit' : 'Cash' }}</span></div>
                @if($transactions->first()->payment_method === 'cash')
                    <div class="flex justify-between"><span class="text-slate-400">Amount received</span><span class="font-mono text-slate-900 dark:text-white">&#8369;{{ number_format((float) $transactions->first()->tendered_amount, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400">Change</span><span class="font-mono font-semibold text-slate-900 dark:text-white">&#8369;{{ number_format((float) $transactions->first()->change_amount, 2) }}</span></div>
                @endif
            </div>

            <div class="mt-4 border-t border-dashed border-slate-300 pt-4 dark:border-white/10">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                            <th class="pb-2 font-medium">Item</th>
                            <th class="pb-2 text-center font-medium">Qty</th>
                            <th class="pb-2 text-right font-medium">Price</th>
                            <th class="pb-2 text-right font-medium">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $line)
                            <tr class="border-t border-slate-100 dark:border-white/5">
                                <td class="py-2 text-slate-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        @if($line->product?->image_url)
                                            <img src="{{ $line->product->image_url }}" alt="{{ $line->product_name }}" class="size-9 rounded-lg object-cover print:size-8">
                                        @else
                                            <span class="flex size-9 items-center justify-center rounded-lg bg-slate-100 text-slate-400 print:hidden"><x-icon name="photo" class="size-4" /></span>
                                        @endif
                                        <span>{{ $line->product_name }}@if($line->container_type ?? null)<span class="block text-xs text-slate-500">{{ ucfirst($line->container_type) }} Gallon</span>@endif</span>
                                    </div>
                                </td>
                                <td class="py-2 text-center text-slate-600 dark:text-slate-300">{{ $line->quantity }}</td>
                                <td class="py-2 text-right font-mono text-slate-600 dark:text-slate-300">&#8369;{{ number_format($line->unit_price, 2) }}</td>
                                <td class="py-2 text-right font-mono font-medium text-slate-900 dark:text-white">&#8369;{{ number_format((float) $line->total_amount - (float) $line->delivery_fee, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    @php($feeLine = $transactions->first(fn ($transaction) => (float) $transaction->delivery_fee > 0))
                    @php($productsSubtotal = $transactions->sum(fn ($transaction) => (float) $transaction->total_amount - (float) $transaction->delivery_fee))
                    <tfoot class="border-t border-slate-200 dark:border-white/10">
                        <tr><th colspan="3" class="pt-3 text-right text-xs font-medium text-slate-500">Products subtotal</th><td class="pt-3 text-right font-mono text-slate-900 dark:text-white">&#8369;{{ number_format($productsSubtotal, 2) }}</td></tr>
                        @if($feeLine)
                            <tr><th colspan="3" class="pt-2 text-right text-xs font-medium text-slate-500">Delivery fee ({{ $feeLine->delivery_gallon_count }} {{ $feeLine->delivery_gallon_count === 1 ? 'gallon' : 'gallons' }} &times; &#8369;{{ number_format($feeLine->delivery_fee_rate, 2) }})</th><td class="pt-2 text-right font-mono text-slate-900 dark:text-white">&#8369;{{ number_format($feeLine->delivery_fee, 2) }}</td></tr>
                        @endif
                        <tr class="border-t border-slate-200 dark:border-white/10"><th colspan="3" class="pt-2 text-right text-sm font-bold">Total to pay</th><td class="pt-2 text-right font-mono font-bold text-slate-900 dark:text-white">&#8369;{{ number_format($total, 2) }}</td></tr>
                    </tfoot>
                </table>
            </div>

            <p class="mt-6 text-center text-xs text-slate-400">Thank you for your purchase!</p>
        </x-card>
    </div>
</x-layouts.app>
