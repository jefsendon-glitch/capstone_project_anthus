@php
$navLink = function (string $route) {
    $active = request()->routeIs($route.'*') || ($route === 'admin.reports' && request()->routeIs('admin.financial.*'));

    return $active
        ? 'sidebar-nav-link is-active bg-gradient-to-r from-primary-500 to-primary-700 text-white'
        : 'sidebar-nav-link text-secondary-100/80 hover:bg-white/10 hover:text-white';
};

$inventoryActive = request()->routeIs('staff.inventory') || request()->routeIs('admin.products*') || (auth()->user()->isStaff() && request()->routeIs('admin.maintenance*', 'admin.consumables*'))
    || request()->routeIs('admin.inventory.*') || request()->routeIs('admin.water-production*') || request()->routeIs('admin.stock-movements*');
$procurementActive = request()->routeIs('admin.suppliers*') || request()->routeIs('admin.purchase-orders*') || request()->routeIs('admin.consumables*') || request()->routeIs('admin.procurement.*');
@endphp

@if(auth()->user()->isAdmin())
    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('admin.dashboard') }}">
        <x-icon name="home" class="size-5 shrink-0" />
        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Dashboard</span>
    </a>
    <a href="{{ route('pos.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('pos') }}">
        <x-icon name="cart" class="size-5 shrink-0" />
        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Point of Sale</span>
    </a>

    <a href="{{ route('admin.products.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $inventoryActive ? 'sidebar-nav-link is-active bg-gradient-to-r from-primary-500 to-primary-700 text-white' : 'sidebar-nav-link text-secondary-100/80 hover:bg-white/10 hover:text-white' }}">
        <x-icon name="archive" class="size-5 shrink-0" />
        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Inventory Management</span>
    </a>
    <a href="{{ route('admin.maintenance.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('admin.maintenance') }}">
        <x-icon name="cog" class="size-5 shrink-0" />
        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Maintenance</span>
    </a>

    <a href="{{ route('admin.suppliers.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $procurementActive ? 'sidebar-nav-link is-active bg-gradient-to-r from-primary-500 to-primary-700 text-white' : 'sidebar-nav-link text-secondary-100/80 hover:bg-white/10 hover:text-white' }}">
        <x-icon name="building" class="size-5 shrink-0" />
        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Procurement</span>
    </a>
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
    <a href="{{ route('staff.gallons.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('staff.gallons') }}">
        <x-icon name="archive" class="size-5 shrink-0" /><span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Gallon Tracking</span>
    </a>
    <a href="{{ route('payments.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('payments') }}">
        <x-icon name="credit-card" class="size-5 shrink-0" />
        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Collections</span>
    </a>
    <a href="{{ route('staff.inventory') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $inventoryActive ? 'sidebar-nav-link is-active bg-primary-600 text-white' : 'sidebar-nav-link text-secondary-100/80 hover:bg-white/10 hover:text-white' }}">
        <x-icon name="archive" class="size-5 shrink-0" />
        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Inventory Management</span>
    </a>
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
    <a href="{{ route('customer.gallons') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('customer.gallons') }}">
        <x-icon name="archive" class="size-5 shrink-0" /><span x-bind:class="$store.sidebar.collapsed &amp;&amp; 'lg:hidden'">My Gallons</span>
    </a>
    <a href="{{ route('customer.account') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('customer.account') }}">
        <x-icon name="user-circle" class="size-5 shrink-0" /><span x-bind:class="$store.sidebar.collapsed &amp;&amp; 'lg:hidden'">My Account</span>
    </a>
@endif

<div class="my-3 border-t border-white/10"></div>

<a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ $navLink('profile') }}">
    <x-icon name="user-circle" class="size-5 shrink-0" />
    <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Profile</span>
</a>
