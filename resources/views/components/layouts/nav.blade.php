@php
$navLink = function (string $route) {
    $active = request()->routeIs($route.'*');

    return $active
        ? 'sidebar-nav-link is-active bg-gradient-to-r from-primary-500 to-primary-700 text-white'
        : 'sidebar-nav-link text-secondary-100/80 hover:bg-white/10 hover:text-white';
};

$inventoryActive = request()->routeIs('admin.products*') || request()->routeIs('admin.consumables*') || request()->routeIs('admin.maintenance*')
    || request()->routeIs('admin.gallon-stocks*') || request()->routeIs('admin.water-production*') || request()->routeIs('admin.stock-movements*');
$procurementActive = request()->routeIs('admin.suppliers*') || request()->routeIs('admin.purchase-orders*');
@endphp

@if(auth()->user()->isAdmin())
    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('admin.dashboard') }}">
        <x-icon name="home" class="size-5 shrink-0" />
        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Overview</span>
    </a>
    <a href="{{ route('pos.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('pos') }}">
        <x-icon name="cart" class="size-5 shrink-0" />
        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Point of Sale</span>
    </a>

    <div x-data="{ open: {{ $inventoryActive ? 'true' : 'false' }} }">
        <button
            type="button"
            x-on:click="open = !open; $nextTick(() => $dispatch('sidebar-navigation-resize'))"
            class="sidebar-nav-link flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium {{ $inventoryActive ? 'bg-white/10 text-white' : 'text-secondary-100/80 hover:bg-white/10 hover:text-white' }}"
        >
            <x-icon name="archive" class="size-5 shrink-0" />
            <span class="flex-1" x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Inventory</span>
            <x-icon name="chevron-down" class="size-4 shrink-0 transition-transform" x-bind:class="[open && 'rotate-180', $store.sidebar.collapsed && 'lg:hidden']" />
        </button>
        <div x-show="open" x-transition x-bind:class="$store.sidebar.collapsed && 'lg:hidden'" class="space-y-1 py-1">
            <a href="{{ route('admin.products.index') }}" class="flex items-center gap-3 rounded-xl py-2 pl-11 pr-3 text-sm font-medium {{ $navLink('admin.products') }}">
                <span>Products</span>
            </a>
            <a href="{{ route('admin.consumables.index') }}" class="flex items-center gap-3 rounded-xl py-2 pl-11 pr-3 text-sm font-medium {{ $navLink('admin.consumables') }}">
                <span>Supply Inventory</span>
            </a>
            <a href="{{ route('admin.gallon-stocks.index') }}" class="flex items-center gap-3 rounded-xl py-2 pl-11 pr-3 text-sm font-medium {{ $navLink('admin.gallon-stocks') }}">
                <span>Gallon Inventory</span>
            </a>
            <a href="{{ route('admin.water-production.index') }}" class="flex items-center gap-3 rounded-xl py-2 pl-11 pr-3 text-sm font-medium {{ $navLink('admin.water-production') }}">
                <span>Water Production</span>
            </a>
            <a href="{{ route('admin.maintenance.index') }}" class="flex items-center gap-3 rounded-xl py-2 pl-11 pr-3 text-sm font-medium {{ $navLink('admin.maintenance') }}">
                <span>Maintenance</span>
            </a>
            <a href="{{ route('admin.stock-movements.index') }}" class="flex items-center gap-3 rounded-xl py-2 pl-11 pr-3 text-sm font-medium {{ $navLink('admin.stock-movements') }}">
                <span>Stock Movements</span>
            </a>
        </div>
    </div>

    <div x-data="{ open: {{ $procurementActive ? 'true' : 'false' }} }">
        <button
            type="button"
            x-on:click="open = !open; $nextTick(() => $dispatch('sidebar-navigation-resize'))"
            class="sidebar-nav-link flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium {{ $procurementActive ? 'bg-white/10 text-white' : 'text-secondary-100/80 hover:bg-white/10 hover:text-white' }}"
        >
            <x-icon name="building" class="size-5 shrink-0" />
            <span class="flex-1" x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Procurement</span>
            <x-icon name="chevron-down" class="size-4 shrink-0 transition-transform" x-bind:class="[open && 'rotate-180', $store.sidebar.collapsed && 'lg:hidden']" />
        </button>
        <div x-show="open" x-transition x-bind:class="$store.sidebar.collapsed && 'lg:hidden'" class="space-y-1 py-1">
            <a href="{{ route('admin.suppliers.index') }}" class="flex items-center gap-3 rounded-xl py-2 pl-11 pr-3 text-sm font-medium {{ $navLink('admin.suppliers') }}">
                <span>Suppliers</span>
            </a>
            <a href="{{ route('admin.purchase-orders.index') }}" class="flex items-center gap-3 rounded-xl py-2 pl-11 pr-3 text-sm font-medium {{ $navLink('admin.purchase-orders') }}">
                <span>Purchase Orders</span>
            </a>
        </div>
    </div>

    <a href="{{ route('admin.customers.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('admin.customers') }}">
        <x-icon name="users" class="size-5 shrink-0" />
        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Customers</span>
    </a>
    <a href="{{ route('deliveries.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('deliveries') }}">
        <x-icon name="truck" class="size-5 shrink-0" />
        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Deliveries</span>
    </a>
    <a href="{{ route('admin.staff.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('admin.staff') }}">
        <x-icon name="user-circle" class="size-5 shrink-0" />
        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Staff</span>
    </a>
    <a href="{{ route('admin.reports.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('admin.reports') }}">
        <x-icon name="chart" class="size-5 shrink-0" />
        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Financial Reports</span>
    </a>
@elseif(auth()->user()->isStaff())
    <a href="{{ route('staff.dashboard') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('staff.dashboard') }}">
        <x-icon name="home" class="size-5 shrink-0" />
        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Dashboard</span>
    </a>
    <a href="{{ route('pos.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('pos') }}">
        <x-icon name="cart" class="size-5 shrink-0" />
        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Point of Sale</span>
    </a>
    <a href="{{ route('deliveries.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('deliveries') }}">
        <x-icon name="truck" class="size-5 shrink-0" />
        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Deliveries</span>
    </a>
    <a href="{{ route('payments.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('payments') }}">
        <x-icon name="credit-card" class="size-5 shrink-0" />
        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Payments</span>
    </a>
    <div x-data="{ open: {{ $inventoryActive ? 'true' : 'false' }} }">
        <button type="button" x-on:click="open = !open; $nextTick(() => $dispatch('sidebar-navigation-resize'))" class="sidebar-nav-link flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium {{ $inventoryActive ? 'bg-white/10 text-white' : 'text-secondary-100/80 hover:bg-white/10 hover:text-white' }}">
            <x-icon name="archive" class="size-5 shrink-0" />
            <span class="flex-1" x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Inventory</span>
            <x-icon name="chevron-down" class="size-4 shrink-0 transition-transform" x-bind:class="[open && 'rotate-180', $store.sidebar.collapsed && 'lg:hidden']" />
        </button>
        <div x-show="open" x-transition x-bind:class="$store.sidebar.collapsed && 'lg:hidden'" class="space-y-1 py-1">
            <a href="{{ route('admin.products.index') }}" class="flex items-center gap-3 rounded-xl py-2 pl-11 pr-3 text-sm font-medium {{ $navLink('admin.products') }}">Products</a>
            <a href="{{ route('admin.consumables.index') }}" class="flex items-center gap-3 rounded-xl py-2 pl-11 pr-3 text-sm font-medium {{ $navLink('admin.consumables') }}">Supply Inventory</a>
            <a href="{{ route('admin.gallon-stocks.index') }}" class="flex items-center gap-3 rounded-xl py-2 pl-11 pr-3 text-sm font-medium {{ $navLink('admin.gallon-stocks') }}">Gallon Inventory</a>
            <a href="{{ route('admin.water-production.index') }}" class="flex items-center gap-3 rounded-xl py-2 pl-11 pr-3 text-sm font-medium {{ $navLink('admin.water-production') }}">Water Production</a>
            <a href="{{ route('admin.maintenance.index') }}" class="flex items-center gap-3 rounded-xl py-2 pl-11 pr-3 text-sm font-medium {{ $navLink('admin.maintenance') }}">Maintenance</a>
            <a href="{{ route('admin.stock-movements.index') }}" class="flex items-center gap-3 rounded-xl py-2 pl-11 pr-3 text-sm font-medium {{ $navLink('admin.stock-movements') }}">Stock Movements</a>
        </div>
    </div>
@else
    <a href="{{ route('customer.dashboard') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('customer.dashboard') }}">
        <x-icon name="home" class="size-5 shrink-0" />
        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Dashboard</span>
    </a>
    <a href="{{ route('customer.orders.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('customer.orders') }}">
        <x-icon name="truck" class="size-5 shrink-0" />
        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">My Orders</span>
    </a>
    <a href="{{ route('customer.payments.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('customer.payments') }}">
        <x-icon name="dollar" class="size-5 shrink-0" />
        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">My Payments</span>
    </a>
@endif

<div class="my-3 border-t border-white/10"></div>

<a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('profile') }}">
    <x-icon name="user-circle" class="size-5 shrink-0" />
    <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Profile</span>
</a>
