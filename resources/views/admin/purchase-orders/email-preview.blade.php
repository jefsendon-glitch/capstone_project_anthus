<x-layouts.app title="Send Purchase Order" :heading="'Email Purchase Order'">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-slate-500">Review the recipient, message, and attached purchase order before sending.</p>
        <x-button as="a" :href="route('admin.purchase-orders.show', $purchaseOrder)" variant="secondary">Back to Purchase Order</x-button>
    </div>
    @if(!$mailConfigured)
        <div role="alert" class="mb-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">Email sending is not configured. Complete the station email setup before sending. You can still review and download the PDF.</div>
    @endif
    @if($errors->any())<div role="alert" class="mb-4 rounded-xl bg-red-50 p-3 text-sm text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <div class="grid items-start gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2" padding="p-4">
            <h2 class="mb-3 text-sm font-semibold">Email Details</h2>
            <form method="POST" action="{{ route('admin.purchase-orders.email.send', $purchaseOrder) }}" class="grid gap-3 sm:grid-cols-2" x-data="{ sending: false, mailConfigured: @js($mailConfigured) }" x-on:submit="if (sending) { $event.preventDefault(); } else { sending = true; }">
                @csrf
                <input type="hidden" name="preview_token" value="{{ $previewToken }}">
                <div><x-input-label value="From" /><p class="break-all text-sm">{{ $sender }}</p></div>
                <div><x-input-label value="To (Supplier Email)" /><p class="break-all text-sm">{{ $order['supplier']['email'] }}</p></div>
                <div class="sm:col-span-2"><x-input-label for="email-subject" value="Subject" /><x-text-input id="email-subject" name="subject" :value="old('subject', $defaultSubject)" maxlength="200" required /></div>
                <div class="sm:col-span-2"><x-input-label for="email-message" value="Message" /><x-textarea id="email-message" name="message" rows="7" maxlength="5000" required>{{ old('message', $defaultMessage) }}</x-textarea></div>
                <label class="flex items-start gap-2 text-sm sm:col-span-2"><input class="mt-1" type="checkbox" name="confirmation" value="1" required><span>I have reviewed the supplier email, message, and purchase order PDF.</span></label>
                @if($isResend)
                    <label class="flex items-start gap-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200 sm:col-span-2"><input class="mt-1" type="checkbox" name="resend_confirmation" value="1" required><span>A previous email attempt exists. I have reviewed Email History and confirm that another copy should be sent.</span></label>
                @endif
                <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-3 sm:col-span-2 dark:border-white/10">
                    <x-button as="a" :href="route('admin.purchase-orders.pdf', $purchaseOrder)" variant="secondary">Download PDF</x-button>
                    <x-button type="submit" :disabled="!$mailConfigured" x-bind:disabled="sending || !mailConfigured"><span x-text="sending ? 'Sending...' : @js($isResend ? 'Confirm and Resend' : 'Confirm and Send')">Confirm and Send</span></x-button>
                </div>
            </form>
        </x-card>
        <x-card padding="p-4">
            <h2 class="mb-3 text-sm font-semibold">Attachment Preview</h2>
            <p class="break-all text-sm font-semibold text-primary-700 dark:text-primary-300">{{ $attachmentFilename }}</p>
            <dl class="mt-3 grid grid-cols-2 gap-2 text-sm"><dt class="text-slate-500">Supplier</dt><dd>{{ $order['supplier']['name'] }}</dd><dt class="text-slate-500">Items</dt><dd>{{ count($order['items']) }}</dd><dt class="text-slate-500">Total</dt><dd>PHP {{ number_format($order['total'], 2) }}</dd><dt class="text-slate-500">Expected</dt><dd>{{ $order['expected_date'] ?? 'To be confirmed' }}</dd></dl>
            <div class="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-500 dark:border-white/10">@foreach($order['items'] as $item)<p class="mb-1">{{ $item['name'] }} &times; {{ $item['quantity'] }}</p>@endforeach</div>
            <p class="mt-3 text-xs text-slate-500">The PDF contains the complete order details. Sending does not update stock or confirm supplier acceptance.</p>
        </x-card>
    </div>
</x-layouts.app>
