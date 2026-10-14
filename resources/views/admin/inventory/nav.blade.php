<div class="no-print mb-4">
    <p class="mb-3 text-xs text-slate-500">Inventory Management</p>
    <nav aria-label="Inventory Management sections" class="flex gap-2 overflow-x-auto pb-1">
        @foreach([
            ['admin.products.index', 'admin.products*', 'Product', 'cube'],
            ['admin.inventory.gallon-inventory', 'admin.inventory.gallon-inventory*', 'Gallon Tracking', 'archive'],
            ['admin.water-production.index', 'admin.water-production*', 'Water Production', 'droplet'],
            ['admin.inventory.supply-usages.index', 'admin.inventory.supply-usages*', 'Supplies Usage', 'clipboard'],
            ['admin.stock-movements.index', 'admin.stock-movements*', 'Stock Movements', 'swap'],
        ] as [$route, $pattern, $label, $icon])
            <a href="{{ route($route) }}" @if(request()->routeIs($pattern)) aria-current="page" @endif class="flex shrink-0 items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium {{ request()->routeIs($pattern) ? 'border-primary-600 bg-primary-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300' }}"><x-admin-nav-icon :name="$icon" /> {{ $label }}</a>
        @endforeach
    </nav>
</div>
