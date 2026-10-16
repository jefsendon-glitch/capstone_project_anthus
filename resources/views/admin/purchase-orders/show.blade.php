@php
$statusColors = ['draft' => 'slate', 'ordered' => 'primary', 'partially_received' => 'warning', 'received' => 'success', 'cancelled' => 'danger'];
$canReceive = in_array($purchaseOrder->status, ['ordered', 'partially_received']);
@endphp

<x-layouts.app title="Purchase Order" :heading="$purchaseOrder->po_number">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <x-badge :color="$statusColors[$purchaseOrder->status] ?? 'slate'">{{ ucfirst(str_replace('_', ' ', $purchaseOrder->status)) }}</x-badge>
        <div class="flex flex-wrap gap-3">
            <x-button as="a" :href="route('admin.purchase-orders.index')" variant="secondary">Purchase Orders</x-button>
            @if(in_array($purchaseOrder->status, ['draft', 'ordered']))
                @can('update', $purchaseOrder)
                    <form method="POST" action="{{ route('admin.purchase-orders.cancel', $purchaseOrder) }}" onsubmit="return confirm('Cancel this unreceived order?')">@csrf<x-button type="submit" variant="danger">Cancel Order</x-button></form>
                @endcan
            @endif
            @if($purchaseOrder->status === 'draft')
                @can('update', $purchaseOrder)
                    <x-button as="a" href="{{ route('admin.purchase-orders.edit', $purchaseOrder) }}" variant="info">
                        <x-icon name="pencil" class="size-4" /> Edit
                    </x-button>
                @endcan
                @can('delete', $purchaseOrder)
                    <form method="POST" action="{{ route('admin.purchase-orders.destroy', $purchaseOrder) }}" onsubmit="return confirm('Delete this purchase order?')">
                        @csrf
                        @method('delete')
                        <x-button type="submit" variant="danger">
                            <x-icon name="trash" class="size-4" /> Delete
                        </x-button>
                    </form>
                @endcan
            @endif
        </div>
    </div>

    @foreach($errors->all() as $error)<p class="mb-2 text-sm text-red-600" role="alert">{{ $error }}</p>@endforeach
    <x-card class="mb-4" padding="p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold">Supplier Email</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $purchaseOrder->supplier->email ?: 'No supplier email registered.' }}</p>
                @if(!$mailConfigured)<p class="mt-1 text-xs text-amber-700 dark:text-amber-300">Station email setup is incomplete. Email sending will be available after configuration.</p>@endif
                @if(!in_array($purchaseOrder->status, ['ordered', 'partially_received']))<p class="mt-1 text-xs text-slate-500">Only ordered or partially received purchase orders can be sent.</p>@endif
            </div>
            <div class="flex flex-wrap gap-2">
                <x-button as="a" :href="route('admin.purchase-orders.pdf', $purchaseOrder)" variant="secondary" class="!py-2 !text-xs">Download PDF</x-button>
                @if(!$purchaseOrder->supplier->email)
                    <x-button as="a" :href="route('admin.suppliers.edit', $purchaseOrder->supplier)" variant="info" class="!py-2 !text-xs">Add Supplier Email</x-button>
                @elseif(in_array($purchaseOrder->status, ['ordered', 'partially_received']))
                    <x-button as="a" :href="route('admin.purchase-orders.email.preview', $purchaseOrder)" class="!py-2 !text-xs">{{ $hasSentEmail ? 'Resend to Supplier' : ($emailHistory->first()?->status === 'failed' ? 'Retry Email' : 'Send to Supplier') }}</x-button>
                @endif
            </div>
        </div>
        <details id="email-history" class="mt-3 border-t border-slate-100 pt-3 dark:border-white/10" @if(request()->has('emails_page') || session('error')) open @endif>
            <summary class="cursor-pointer text-xs font-semibold">Email History ({{ $emailHistory->total() }})</summary>
            <p class="mt-2 text-xs text-slate-500">Sent means the mail server accepted the email. It does not confirm delivery, reading, or supplier acceptance.</p>
            <div class="mt-2 overflow-x-auto"><table class="min-w-full text-left text-xs">
                <thead><tr>@foreach(['Attempted', 'Recipient', 'Status', 'Admin', 'Details'] as $label)<th class="px-2 py-2">{{ $label }}</th>@endforeach</tr></thead>
                <tbody>
                    @forelse($emailHistory as $email)
                        <tr class="border-t border-slate-100 dark:border-white/10">
                            <td class="whitespace-nowrap px-2 py-2">{{ $email->created_at->format('M d, Y h:i A') }}</td>
                            <td class="px-2 py-2">{{ $email->recipient }}</td>
                            <td class="px-2 py-2"><x-badge :color="$email->status === 'sent' ? 'success' : ($email->status === 'failed' ? 'danger' : 'warning')">{{ $email->status === 'sending' ? 'Sending / Unconfirmed' : ucfirst($email->status) }}</x-badge></td>
                            <td class="px-2 py-2">{{ $email->sentBy?->name ?? 'Deleted account' }}</td>
                            <td class="px-2 py-2">{{ $email->failure_reason ?: ($email->sent_at ? 'Sent '.$email->sent_at->format('M d, Y h:i A') : 'Check with the supplier before retrying an interrupted attempt.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-2 py-3 text-slate-500">No emails have been sent for this purchase order.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            <div class="mt-2">{{ $emailHistory->links() }}</div>
        </details>
    </x-card>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Line Items</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="py-2">Item</th>
                            <th class="py-2 text-right">Ordered</th>
                            <th class="py-2 text-right">Received</th>
                            <th class="py-2 text-right">Unit Cost</th>
                            <th class="py-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($purchaseOrder->items as $item)
                            <tr>
                                <td class="py-2 text-sm font-medium text-slate-900 dark:text-white">{{ $item->itemable?->name ?? 'Deleted item' }}</td>
                                <td class="py-2 text-right font-mono text-sm text-slate-600 dark:text-slate-300">{{ rtrim(rtrim($item->quantity_ordered, '0'), '.') }}</td>
                                <td class="py-2 text-right font-mono text-sm text-slate-600 dark:text-slate-300">{{ rtrim(rtrim($item->quantity_received, '0'), '.') }}</td>
                                <td class="py-2 text-right font-mono text-sm text-slate-900 dark:text-white">₱{{ number_format($item->unit_cost, 2) }}</td>
                                <td class="py-2 text-right font-mono text-sm text-slate-900 dark:text-white">₱{{ number_format((float) $item->unit_cost * (float) $item->quantity_ordered, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($canReceive)
                @can('receive', $purchaseOrder)
                    <div class="mt-6 border-t border-slate-100 pt-6 dark:border-white/10">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Receive Stock</h3>
                        <form method="POST" action="{{ route('admin.purchase-orders.receive', $purchaseOrder) }}" class="mt-3 space-y-3">
                            @csrf
                            <input type="hidden" name="receipt_token" value="{{ old('receipt_token', (string) \Illuminate\Support\Str::uuid()) }}">
                            @foreach($errors->all() as $error)<p class="text-sm text-red-600">{{ $error }}</p>@endforeach
                            @foreach($purchaseOrder->items as $item)
                                @if($item->remaining_quantity > 0)
                                    <div class="grid items-center gap-3 sm:grid-cols-[minmax(0,1fr)_140px_auto]">
                                        <input type="hidden" name="items[{{ $loop->index }}][purchase_order_item_id]" value="{{ $item->id }}">
                                        <span class="w-40 shrink-0 truncate text-sm text-slate-700 dark:text-slate-300">{{ $item->itemable?->name }}</span>
                                        <x-text-input
                                            type="number" step="1" min="0" :max="$item->remaining_quantity"
                                            name="items[{{ $loop->index }}][quantity_received]"
                                            placeholder="Qty received"
                                            :value="old('items.'.$loop->index.'.quantity_received', 0)"
                                        />
                                        <span class="shrink-0 text-xs text-slate-400">of {{ rtrim(rtrim($item->remaining_quantity, '0'), '.') }} remaining</span>
                                    </div>
                                @endif
                            @endforeach
                            <x-button variant="success" type="submit">Record Receipt</x-button>
                        </form>
                    </div>
                @endcan
            @endif
        </x-card>

        <x-card>
            <h2 class="font-heading text-base font-bold text-slate-900 dark:text-white">Details</h2>
            <div class="mt-4 rounded-2xl bg-primary-50 p-4 dark:bg-primary-500/10"><p class="text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-300">Total Order Cost</p><p class="mt-2 text-2xl font-extrabold text-primary-700 dark:text-primary-300">₱{{ number_format($purchaseOrder->items->sum(fn ($item) => (float) $item->quantity_ordered * (float) $item->unit_cost), 2) }}</p></div>
            <div class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-slate-400">Supplier</span><span class="text-slate-900 dark:text-white">{{ $purchaseOrder->supplier->name }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Ordered</span><span class="text-slate-900 dark:text-white">{{ $purchaseOrder->ordered_at?->format('M d, Y') ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Expected delivery</span><span class="text-slate-900 dark:text-white">{{ $purchaseOrder->expected_date?->format('M d, Y') ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Created by</span><span class="text-slate-900 dark:text-white">{{ $purchaseOrder->createdBy?->name ?? '—' }}</span></div>
                @if($purchaseOrder->notes)
                    <div class="border-t border-slate-100 pt-3 dark:border-white/10">
                        <p class="text-slate-400">Notes</p>
                        <p class="mt-1 text-slate-700 dark:text-slate-300">{{ $purchaseOrder->notes }}</p>
                    </div>
                @endif
            </div>
        </x-card>
    </div>
</x-layouts.app>
