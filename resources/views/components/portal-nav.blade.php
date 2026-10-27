@php
    $links = auth()->user()->isCustomer() ? [
        ['customer.dashboard', 'Overview'], ['customer.orders.index', 'My Orders'],
        ['customer.payments.index', 'Payments'], ['customer.gallons', 'My Gallons'],
        ['profile.edit', 'My Profile'],
    ] : (auth()->user()->isStaff() ? [
        ['staff.dashboard', 'Overview'], ['pos.index', 'Point of Sale'], ['deliveries.index', 'Deliveries'],
        ['payments.index', 'Collections'], ['staff.gallons.index', 'Gallon Tracking'], ['staff.inventory', 'Stock Overview'], ['profile.edit', 'My Profile'],
    ] : []);
@endphp
@if($links)
    <nav aria-label="Account navigation" class="mb-4 flex flex-wrap gap-1 rounded-xl border border-slate-200 bg-white p-1 dark:border-white/10 dark:bg-slate-900">
        @foreach($links as [$route, $label])
            @php($active = request()->routeIs(str_ends_with($route, '.index') ? substr($route, 0, -6).'.*' : $route))
            <a href="{{ route($route) }}" @if($active) aria-current="page" @endif class="rounded-lg px-3 py-2 text-sm font-semibold transition {{ $active ? 'bg-primary-600 text-white' : 'text-slate-600 hover:bg-primary-50 hover:text-primary-700 dark:text-slate-300 dark:hover:bg-white/5' }}">{{ $label }}</a>
        @endforeach
    </nav>
@endif
