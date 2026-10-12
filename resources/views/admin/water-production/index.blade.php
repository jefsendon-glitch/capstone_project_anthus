<x-layouts.app title="Water Production" :heading="'Water Production Log'">
    @if($errors->any())
        <div role="alert" class="mb-3 rounded-xl bg-red-50 p-3 text-sm text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif
    @if(auth()->user()->isAdmin())
        <div class="mb-3 flex justify-end">
            <details class="w-full rounded-lg border border-red-200 p-2 sm:w-80 dark:border-red-900" @if($errors->has('reset_confirmation')) open @endif>
                <summary class="cursor-pointer text-xs font-semibold text-red-600 dark:text-red-400">Reset Production Records</summary>
                <form method="POST" action="{{ route('admin.water-production.reset') }}" class="mt-2 space-y-2" onsubmit="return confirm('Delete ALL water production logs, including records outside the current date filter? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <p class="text-xs text-slate-500">Deletes all production logs, including records outside the date filter. Current product stock and stock movement history stay unchanged. This cannot be undone.</p>
                    <x-input-label for="production-reset-confirmation" value="Type RESET to confirm" />
                    <x-text-input id="production-reset-confirmation" name="reset_confirmation" placeholder="RESET" pattern="RESET" autocomplete="off" required />
                    <x-button type="submit" variant="danger" class="!px-3 !py-1.5 !text-xs">Reset Production Records</x-button>
                </form>
            </details>
        </div>
    @endif
    <div class="mb-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
        @foreach(['batches' => 'Production Batches', 'productUnits' => 'Product Units Produced', 'filledContainers' => 'Gallon Containers Filled'] as $key => $label)
            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 dark:border-white/10 dark:bg-slate-800"><p class="text-xs text-slate-500">{{ $label }}</p><p class="mt-1 text-xl font-semibold tabular-nums">{{ number_format($summary[$key]) }}</p></div>
        @endforeach
    </div>
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <p class="text-xs text-slate-500">Totals follow the selected filters. Quantities count product units or filled containers, not liters or current stock.</p>
        @if(auth()->user()->isAdmin())
            <div class="flex flex-wrap gap-2"><x-button as="a" href="{{ route('admin.products.index') }}" variant="secondary" class="!py-1.5 !text-xs">Manage Product Stock</x-button><x-button as="a" href="{{ route('admin.inventory.gallon-inventory') }}" variant="secondary" class="!py-1.5 !text-xs">Fill Gallons</x-button></div>
        @endif
    </div>
    <p class="mb-3 text-xs text-slate-500">Water stock increases from Product Management and Fill movements from Gallon Tracking are logged automatically here.</p>

    <x-card class="mb-3" padding="p-3">
        <form method="GET" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            <div><x-input-label for="production-search" value="Product / Container" /><x-text-input id="production-search" name="search" :value="$filters['search'] ?? ''" placeholder="Search name or size..." maxlength="255" /></div>
            <div><x-input-label for="production-source" value="Source" /><x-select id="production-source" name="source"><option value="">All Sources</option><option value="product" @selected(($filters['source'] ?? '') === 'product')>Product Management</option><option value="gallon" @selected(($filters['source'] ?? '') === 'gallon')>Gallon Tracking</option></x-select></div>
            <div>
                <x-input-label for="date_from" value="Produced from" />
                <x-text-input id="date_from" type="date" name="date_from" :value="$dateFrom" />
            </div>
            <div>
                <x-input-label for="date_to" value="Produced to" />
                <x-text-input id="date_to" type="date" name="date_to" :value="$dateTo" />
            </div>
            <div class="flex items-end justify-end gap-2 sm:col-span-2 lg:col-span-4">
                <x-button as="a" href="{{ route('admin.water-production.index') }}" variant="secondary">Clear Filters</x-button>
                <x-button type="submit">Filter</x-button>
            </div>
        </form>
    </x-card>

    <x-card padding="p-0">
        @if($logs->isEmpty())
            <div class="p-6">
                <x-empty-state icon="beaker" :title="array_filter($filters) ? 'No production matches these filters' : 'No production logged yet'" description="Increase water product stock in Product Management or record a Fill movement in Gallon Tracking." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-3 py-2">Batch ID</th>
                            <th class="px-3 py-2">Product / Container</th>
                            <th class="px-3 py-2">Source</th>
                            <th class="px-3 py-2 text-right">Quantity</th>
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Logged By</th>
                            <th class="px-3 py-2">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($logs as $log)
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs">PROD-{{ str_pad((string) $log->id, 5, '0', STR_PAD_LEFT) }}</td>
                                <td class="px-3 py-2 text-sm font-medium text-slate-900 dark:text-white">{{ $log->container_name ?? $log->product?->name ?? 'Unavailable product' }} ({{ $log->container_size ?? $log->product?->size ?? '—' }})</td>
                                <td class="px-3 py-2 text-xs text-slate-500">{{ $log->container_name !== null ? 'Gallon Tracking · Filled' : 'Product Management' }}@if($log->station_gallon_movement_id)<p class="mt-1 font-mono">GAL-{{ $log->station_gallon_movement_id }}</p>@endif</td>
                                <td class="px-3 py-2 text-right font-mono text-sm text-slate-900 dark:text-white">{{ number_format($log->gallons_produced) }}<p class="text-xs text-slate-500">{{ $log->container_name !== null ? 'containers' : 'units' }}</p></td>
                                <td class="px-3 py-2 text-sm text-slate-600 dark:text-slate-300">{{ $log->production_date->format('M d, Y') }}</td>
                                <td class="px-3 py-2 text-sm text-slate-500 dark:text-slate-400">{{ $log->producedBy?->name ?? '—' }}</td>
                                <td class="px-3 py-2 text-xs text-slate-500">{{ $log->notes ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-4 dark:border-white/5">
                {{ $logs->links() }}
            </div>
        @endif
    </x-card>
</x-layouts.app>
