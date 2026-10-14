<div class="no-print mb-4">
    <p class="mb-3 text-xs text-slate-500">Procurement</p>
    <nav aria-label="Procurement sections" class="flex gap-2 overflow-x-auto pb-1">
        @foreach([
            ['admin.suppliers.index', 'admin.suppliers*', 'Supplier Management', 'building'],
            ['admin.consumables.index', 'admin.consumables*', 'Supplies', 'archive'],
            ['admin.purchase-orders.index', 'admin.purchase-orders*', 'Purchase Orders', 'cart'],
            ['admin.procurement.receiving', 'admin.procurement.receiving', 'Stock Receiving', 'truck'],
            ['admin.procurement.reorders', 'admin.procurement.reorders', 'Reorder Monitoring', 'alert-triangle'],
            ['admin.procurement.history', 'admin.procurement.history', 'Purchase History', 'clipboard'],
        ] as [$route, $pattern, $label, $icon])
            <a href="{{ route($route) }}" @if(request()->routeIs($pattern)) aria-current="page" @endif class="flex shrink-0 items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium {{ request()->routeIs($pattern) ? 'border-primary-600 bg-primary-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300' }}"><x-admin-nav-icon :name="$icon" /> {{ $label }}</a>
        @endforeach
    </nav>
</div>
