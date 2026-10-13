<x-layouts.app title="Gallon Inventory" :heading="'Gallon Inventory'">
    @push('admin-reset-actions')
    @if(auth()->user()->isAdmin())
        <x-reset-dot :action="route('admin.inventory.gallon-inventory.reset')" title="Reset Gallon Records" description="History only keeps stock and customer loans. Entire inventory deletes all gallon types, quantities, customer loans and movements; totals return to zero. This cannot be undone.">
            <x-input-label for="header-reset-scope" value="Records to reset" />
            <x-select id="header-reset-scope" name="reset_scope" required>
                <option value="history" @selected(old('reset_scope', 'history') === 'history')>Movement history only</option>
                <option value="all" @selected(old('reset_scope') === 'all')>Entire gallon inventory</option>
            </x-select>
            <x-input-error :messages="$errors->get('reset_scope')" />
        </x-reset-dot>
    @endif
    @endpush

    <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
        <div>
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Station-Owned Gallon Containers</h2>
        <p class="mt-1 text-sm text-slate-500">Shuanti Water Refilling Station — container inventory, customer borrowing, and movement records.</p>
        </div>
    </div>

    <div class="mb-3 grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-7">
        <x-stat-card compact label="Total Gallons" :value="number_format($gallons->sum(fn ($g) => $g->empty + $g->filled + $g->borrowed + $g->damaged + $g->missing))" icon="cube" color="primary" />
        @foreach(['empty', 'filled', 'borrowed', 'returned', 'damaged', 'missing'] as $status)
            <x-stat-card compact :label="ucfirst($status).($status === 'returned' ? ' (All Time)' : '')" :value="number_format($gallons->sum($status))" :icon="['empty' => 'archive', 'filled' => 'droplet', 'borrowed' => 'users', 'returned' => 'check-circle', 'damaged' => 'alert-triangle', 'missing' => 'x-circle'][$status]" :color="['empty' => 'secondary', 'filled' => 'success', 'borrowed' => 'delivery', 'returned' => 'primary', 'damaged' => 'warning', 'missing' => 'danger'][$status]" />
        @endforeach
    </div>
    <p class="mb-3 text-xs text-slate-500">Returns are cumulative; returned containers are already included in empty or damaged stock.</p>

    @if($errors->any())
        <div role="alert" class="mb-5 rounded-xl bg-red-50 p-4 text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div class="mb-3 grid items-start gap-3 lg:grid-cols-2">
        <x-card padding="p-3">
        </x-card>
</x-layouts.app>
