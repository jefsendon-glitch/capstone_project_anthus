<x-card padding="p-4" class="mb-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><p class="text-xs text-slate-500">{{ $station->business_name }}</p><h2 class="mt-1 text-sm font-semibold">Customer Account</h2></div>
        <x-button as="a" :href="route('customer.account')" variant="info" class="!py-1.5 !text-xs">View My Account</x-button>
    </div>
    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-3">
        <div><dt class="text-xs text-slate-500">Credit Access</dt><dd class="mt-1"><x-badge :color="auth()->user()->credit_status === 'suspended' ? 'danger' : 'success'">{{ ucfirst(auth()->user()->credit_status ?? 'active') }}</x-badge></dd></div>
        <div><dt class="text-xs text-slate-500">Borrowed Gallons</dt><dd class="mt-1"><a href="{{ route('customer.gallons') }}" class="font-semibold text-primary-700 dark:text-primary-300">{{ number_format($borrowedGallons) }} outstanding &rarr;</a></dd></div>
        <div><dt class="text-xs text-slate-500">Delivery Address</dt><dd class="mt-1">{{ auth()->user()->address ?: 'Add your address in Profile.' }}</dd></div>
    </dl>
</x-card>
