<div x-data="{ settingsOpen: @js($errors->getBag('systemReset')->any() || $errors->getBag('deliveryFee')->any()), submitting: false, confirmation: '' }" x-on:keydown.escape.window="if (!submitting) settingsOpen = false">
    <button type="button" x-on:click="settingsOpen = true; $nextTick(() => $refs.systemResetConfirmation.focus())" class="rounded-xl border border-transparent p-2.5 text-slate-500 transition hover:border-slate-200 hover:bg-slate-100 hover:text-primary-600 dark:text-slate-300 dark:hover:border-white/10 dark:hover:bg-white/5" title="Transaction Settings" aria-label="Transaction Settings" x-bind:aria-expanded="settingsOpen">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9.5 3-.7 2.2-2 .9-2.1-.5-2 3.4 1.5 1.7v2.6L2.7 15l2 3.4 2.1-.5 2 .9.7 2.2h4l.7-2.2 2-.9 2.1.5 2-3.4-1.5-1.7v-2.6l1.5-1.7-2-3.4-2.1.5-2-.9L13.5 3z"/><circle cx="11.5" cy="12" r="3"/></svg>
    </button>
    <template x-teleport="body">
        <div x-show="settingsOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="admin-transaction-settings-title">
            <div class="absolute inset-0 bg-slate-950/50" x-on:click="if (!submitting) settingsOpen = false"></div>
            <div class="relative max-h-[85vh] w-full max-w-lg overflow-y-auto rounded-xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-800 dark:bg-slate-900">
                <h2 id="admin-transaction-settings-title" class="text-base font-semibold">Transaction Settings</h2>
                @php($deliveryFeeSetting = \App\Models\Setting::current())
                <section class="mt-4 rounded-xl border border-slate-200 p-4 dark:border-white/10">
                    <h3 class="text-sm font-semibold">Delivery Fee</h3>
                    <p class="mt-1 text-xs text-slate-500">This amount is charged for each water gallon in a delivery order.</p>
                    <form method="POST" action="{{ route('admin.settings.delivery-fee.update') }}" class="mt-3 space-y-3">
                        @csrf @method('PATCH')
                        <div><x-input-label for="delivery-fee-per-gallon" value="Fee per gallon (₱)" /><x-text-input id="delivery-fee-per-gallon" name="delivery_fee_per_gallon" type="number" min="0" max="100000" step="0.01" value="{{ old('delivery_fee_per_gallon', $deliveryFeeSetting->delivery_fee_per_gallon) }}" required /><x-input-error :messages="$errors->getBag('deliveryFee')->get('delivery_fee_per_gallon')" /></div>
                        <div class="flex justify-end"><x-button type="submit" variant="secondary">Save Delivery Fee</x-button></div>
                    </form>
                </section>
                @if(session('success'))<p class="mt-3 text-xs font-medium text-success-600">{{ session('success') }}</p>@endif
                <p class="mt-2 text-sm font-semibold text-danger-600">Reset All Transactions and Stock</p>
                <ul class="mt-3 list-disc space-y-2 pl-4 text-xs leading-relaxed text-slate-500">
                    <li>Deletes sales, orders, deliveries, payments, expenses, reports, production, movement, maintenance, and notification history across all accounts.</li>
                    <li>Sets all product, Round/Slim, supplies, and gallon stock to zero. Clears customer loan balances and credit suspensions.</li>
                    <li>Keeps accounts, roles, profiles, product details, suppliers, inventory definitions, and station settings.</li>
                </ul>
                <p class="mt-3 text-xs text-slate-500">A private backup is saved before deletion. This cannot be undone from this screen.</p>
                <form method="POST" action="{{ route('admin.system.transactions.reset') }}" class="mt-4 space-y-3" x-on:submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }">
                    @csrf @method('DELETE')
                    <div><x-input-label for="system-reset-confirmation" value="Type RESET ALL to confirm" /><x-text-input id="system-reset-confirmation" name="reset_confirmation" x-ref="systemResetConfirmation" x-model="confirmation" autocomplete="off" pattern="RESET ALL" required /><x-input-error :messages="$errors->getBag('systemReset')->get('reset_confirmation')" /></div>
                    <div><x-input-label for="system-reset-password" value="Your Admin Password" /><x-text-input id="system-reset-password" name="password" type="password" autocomplete="current-password" required /><x-input-error :messages="$errors->getBag('systemReset')->get('password')" /></div>
                    <label class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300"><input type="checkbox" name="acknowledge_reset" value="1" required class="mt-0.5 rounded border-slate-300 text-danger-600">I understand that this resets transactions and stock for Admin, Staff, and Customer accounts.</label>
                    <x-input-error :messages="$errors->getBag('systemReset')->get('acknowledge_reset')" />
                    <div class="flex justify-end gap-2"><x-button type="button" variant="secondary" x-on:click="settingsOpen = false" x-bind:disabled="submitting">Cancel</x-button><x-button type="submit" variant="danger" x-bind:disabled="submitting || confirmation !== 'RESET ALL'"><span x-text="submitting ? 'Resetting…' : 'Reset All Transactions'">Reset All Transactions</span></x-button></div>
                </form>
            </div>
        </div>
    </template>
</div>
