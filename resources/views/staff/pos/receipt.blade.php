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
                <div class="flex justify-between"><span class="text-slate-400">Payment</span><span class="text-slate-900 dark:text-white">{{ $transactions->first()->payment_method === 'loan' ? 'Credit / Loan' : 'Cash' }}</span></div>
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
                                        <span>{{ $line->product_name }}</span>
                                    </div>
                                </td>
                                <td class="py-2 text-center text-slate-600 dark:text-slate-300">{{ $line->quantity }}</td>
                                <td class="py-2 text-right font-mono text-slate-600 dark:text-slate-300">₱{{ number_format($line->unit_price, 2) }}</td>
                                <td class="py-2 text-right font-mono font-medium text-slate-900 dark:text-white">₱{{ number_format($line->total_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-300 pt-4 text-base font-bold text-slate-900 dark:border-white/10 dark:text-white">
                <span>Total</span>
                <span class="font-mono">₱{{ number_format($total, 2) }}</span>
            </div>

            <p class="mt-6 text-center text-xs text-slate-400">Thank you for your purchase!</p>
        </x-card>
    </div>
</x-layouts.app>
