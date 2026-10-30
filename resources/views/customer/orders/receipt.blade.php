<x-layouts.app title="Delivery Receipt" :heading="'Delivery Receipt'" :subheading="$order->order_code">
    <style>
        @media print {
            @page { size: A4 portrait; margin: 14mm; }
            body * { visibility: hidden !important; }
            .receipt-sheet, .receipt-sheet * { visibility: visible !important; }
            .receipt-sheet { position: absolute !important; inset: 0 auto auto 0 !important; width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; border: 0 !important; box-shadow: none !important; background: #fff !important; color: #172033 !important; }
            .receipt-sheet .no-print { display: none !important; }
            .receipt-sheet table { border-collapse: collapse !important; }
            .receipt-sheet tr { break-inside: avoid; }
        }
    </style>

    <div class="mx-auto max-w-3xl">
        <div class="no-print mb-4 flex flex-wrap items-center justify-between gap-3">
            <x-button as="a" href="{{ route('customer.orders.index') }}" variant="secondary"><x-icon name="chevron-left" class="size-4" /> Back to My Orders</x-button>
            <x-button type="button" onclick="window.print()"><x-icon name="tag" class="size-4" /> Print / Save PDF</x-button>
        </div>

        <article class="receipt-sheet overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-800 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100">
            <header class="border-b-4 border-primary-600 px-6 py-7 text-center sm:px-10">
                <img src="{{ asset('images/shaunti-water-logo.svg') }}" alt="{{ $business->business_name }} logo" class="mx-auto mb-3 h-16 w-40 object-contain print:h-14">
                <h1 class="font-heading text-xl font-bold text-slate-950 dark:text-white">{{ $business->business_name }}</h1>
                @if($business->address)<p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $business->address }}</p>@endif
                @if($business->contact_number || $business->contact_email)
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ collect([$business->contact_number, $business->contact_email])->filter()->implode(' Â· ') }}</p>
                @endif
                <div class="mt-5 inline-flex items-center rounded-full bg-emerald-50 px-4 py-1.5 text-xs font-bold uppercase tracking-[0.14em] text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">Delivery Receipt</div>
            </header>

            <div class="grid gap-6 px-6 py-6 sm:grid-cols-2 sm:px-10">
                <section>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Customer</h2>
                    <p class="mt-2 font-semibold text-slate-900 dark:text-white">{{ $order->customer_name ?: $order->customer?->name ?: 'Customer' }}</p>
                    @if($order->customer_address)<p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $order->customer_address }}</p>@endif
                </section>
                <section class="sm:text-right">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Order Details</h2>
                    <p class="mt-2 text-sm"><span class="text-slate-500">Receipt no.</span> <span class="ml-1 font-mono font-semibold text-slate-900 dark:text-white">{{ $order->order_code }}</span></p>
                    <p class="mt-1 text-sm"><span class="text-slate-500">Delivered</span> <span class="ml-1 font-medium text-slate-900 dark:text-white">{{ $order->delivered_at?->format('M j, Y Â· g:i A') ?? 'â€”' }}</span></p>
                    <p class="mt-1 text-sm"><span class="text-slate-500">Payment method</span> <span class="ml-1 font-medium text-slate-900 dark:text-white">{{ $order->payment_method === 'loan' ? 'Credit' : ucfirst($order->payment_method) }}</span></p>
                </section>
            </div>

            <div class="px-6 pb-2 sm:px-10">
                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
                    <table class="w-full min-w-[520px] text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-white/5 dark:text-slate-400">
                            <tr><th class="px-4 py-3 text-left font-semibold">Description</th><th class="px-3 py-3 text-center font-semibold">Qty</th><th class="px-3 py-3 text-right font-semibold">Unit Price</th><th class="px-4 py-3 text-right font-semibold">Amount</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                            @foreach($receiptLines as $line)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $line->product_name }}
                                        @if($line->container_type ?? null)<span class="mt-0.5 block text-xs font-normal text-slate-500">{{ ucfirst($line->container_type) }} gallon</span>@endif
                                    </td>
                                    <td class="px-3 py-3 text-center tabular-nums">{{ number_format($line->quantity) }}</td>
                                    <td class="px-3 py-3 text-right font-mono tabular-nums">â‚±{{ number_format((float) $line->unit_price, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums">â‚±{{ number_format((float) $line->total_amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t-2 border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-white/5">@if((float) $order->delivery_fee > 0)<tr><th colspan="3" class="px-4 py-2 text-right text-sm font-medium">Delivery fee ({{ number_format($order->delivery_fee_rate, 2) }} per gallon)</th><td class="px-4 py-2 text-right font-mono tabular-nums">₱{{ number_format($order->delivery_fee, 2) }}</td></tr>@endif
                            <tr><th colspan="3" class="px-4 py-4 text-right text-sm font-bold uppercase tracking-wide">Total</th><td class="px-4 py-4 text-right font-mono text-lg font-bold tabular-nums">â‚±{{ number_format((float) $total, 2) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if($order->payment_method === 'loan')
                <p class="mx-6 mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200 sm:mx-10">This order was charged to your credit account. Please settle the balance according to your account terms.</p>
            @endif

            <footer class="px-6 pb-7 pt-6 text-center sm:px-10">
                <p class="font-semibold text-slate-800 dark:text-slate-200">Thank you for choosing {{ $business->business_name }}.</p>
                <p class="mt-1 text-xs text-slate-500">Please keep this receipt for your records.</p>
            </footer>
        </article>
    </div>
</x-layouts.app>
