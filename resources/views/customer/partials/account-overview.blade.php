<x-card padding="p-4">
    <div class="flex items-center justify-between gap-3"><h2 class="text-base font-semibold">My Account</h2><a href="{{ route('customer.account') }}" class="shrink-0 text-sm font-semibold text-primary-700 dark:text-primary-300">View Details</a></div>
    <dl class="mt-3 space-y-3 text-sm">
        <div class="flex flex-wrap items-center justify-between gap-2"><dt class="text-slate-500 dark:text-slate-400">Credit Access</dt><dd><x-badge :color="auth()->user()->credit_status === 'suspended' ? 'danger' : 'success'">{{ ucfirst(auth()->user()->credit_status ?? 'active') }}</x-badge></dd></div>
        <div class="flex flex-wrap items-center justify-between gap-2"><dt class="text-slate-500 dark:text-slate-400">Gallons to Return</dt><dd><a href="{{ route('customer.gallons') }}" class="font-semibold tabular-nums text-primary-700 dark:text-primary-300">{{ number_format($borrowedGallons) }} <span aria-hidden="true">&rarr;</span></a></dd></div>
        <div class="border-t border-slate-100 pt-3 dark:border-white/10"><dt class="text-xs text-slate-500 dark:text-slate-400">Default Delivery Address</dt><dd class="mt-1 break-words">{{ auth()->user()->address ?: 'No default address saved.' }}</dd></div>
    </dl>
    <a href="{{ route('profile.edit') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-primary-700 dark:text-primary-300">Update Profile <x-icon name="chevron-right" class="size-4" /></a>
</x-card>
