<x-layouts.app title="My Account" :heading="'My Account'" :subheading="'Your balance, credit status, and purchase records'">
    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <x-stat-card compact label="Outstanding Balance" :value="'₱'.number_format(auth()->user()->credit_balance, 2)" icon="credit-card" color="warning" />
        <x-stat-card compact label="Credit Status" :value="ucfirst(auth()->user()->credit_status ?? 'active')" icon="check-circle" :color="auth()->user()->credit_status === 'suspended' ? 'danger' : 'success'" />
        <x-stat-card compact label="Next Payment Due" :value="$nextDueDate ? \Illuminate\Support\Carbon::parse($nextDueDate)->format('M d, Y') : 'No due date'" icon="clock" color="primary" />
    </div>
    <div class="grid items-start gap-4 xl:grid-cols-3">
        <x-card padding="p-4" class="xl:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-2"><div><h2 class="text-base font-semibold">Purchase History</h2><p class="mt-1 text-xs text-slate-500">Your walk-in and completed delivery sales recorded by the station.</p></div><x-button as="a" :href="route('customer.payments.index')" variant="info" class="!py-1.5 !text-sm">View Payments</x-button></div>
            <form method="GET" class="my-3 flex gap-2">
                <x-text-input name="search" type="search" :value="request('search')" placeholder="Search transaction or product..." aria-label="Search purchase history" maxlength="100" class="!py-2" />
                <x-button type="submit" variant="secondary" class="!py-2 !text-sm">Search</x-button>
                @if(request()->filled('search'))<x-button as="a" :href="route('customer.account')" variant="secondary" class="!py-2 !text-sm">Clear</x-button>@endif
            </form>
            <div class="overflow-x-auto"><table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-slate-500 dark:border-white/10"><tr>@foreach(['Transaction', 'Product', 'Payment', 'Total', 'Unpaid'] as $label)<th class="px-2 py-2">{{ $label }}</th>@endforeach</tr></thead>
                <tbody>
                    @forelse($purchases as $purchase)
                        <tr class="border-b border-slate-100 dark:border-white/5">
                            <td class="px-2 py-3"><p class="font-medium text-primary-700 dark:text-primary-300">{{ $purchase->transaction_code }}</p><p class="mt-1 text-slate-500">{{ $purchase->created_at->format('M d, Y') }} · {{ $purchase->transaction_type === 'delivery' ? 'Delivery' : 'Walk-in' }}</p></td>
                            <td class="px-2 py-3">{{ $purchase->product_name }} &times; {{ $purchase->quantity }}</td>
                            <td class="px-2 py-3"><x-badge :color="$purchase->payment_method === 'loan' ? 'warning' : 'success'">{{ $purchase->payment_method === 'loan' ? 'Credit' : 'Cash' }}</x-badge></td>
                            <td class="whitespace-nowrap px-2 py-3 tabular-nums">₱{{ number_format($purchase->total_amount, 2) }}</td>
                            <td class="whitespace-nowrap px-2 py-3 tabular-nums">₱{{ number_format($purchase->payment_method === 'loan' ? max(0, (float) $purchase->total_amount - (float) $purchase->credit_paid_amount) : 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-2 py-5 text-slate-500">{{ request()->filled('search') ? 'No purchases match your search.' : 'No purchases have been recorded yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            <div class="mt-3">{{ $purchases->links() }}</div>
        </x-card>
        <div class="space-y-3">
            <x-card padding="p-4"><h2 class="text-base font-semibold">Account Details</h2><dl class="mt-3 grid gap-2 text-xs"><div><dt class="text-slate-500">Name</dt><dd class="mt-0.5">{{ auth()->user()->name }}</dd></div><div><dt class="text-slate-500">Contact Number</dt><dd class="mt-0.5">{{ auth()->user()->contact_number ?: 'Not provided' }}</dd></div><div><dt class="text-slate-500">Delivery Address</dt><dd class="mt-0.5">{{ auth()->user()->address ?: 'Add your delivery address in Profile.' }}</dd></div></dl><x-button as="a" :href="route('profile.edit')" variant="secondary" class="mt-3 !py-1.5 !text-sm">Update Profile</x-button><p class="mt-3 text-xs text-slate-500">Balances and credit access are maintained by the station. Payments appear after they are recorded.</p></x-card>
            <x-station-contact :station="$station" />
        </div>
    </div>
</x-layouts.app>
