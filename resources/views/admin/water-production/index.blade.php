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
    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-primary-100 bg-primary-50/60 p-4 text-sm dark:border-primary-500/20 dark:bg-primary-500/10">
        <x-badge color="success"><x-icon name="check-circle" class="size-4" /> Automatic production tracking</x-badge>
        <p class="pt-0.5 text-xs text-primary-700 dark:text-primary-300">Fill movements in Gallon Inventory automatically log the filled container quantity here.</p>
        <p class="pt-0.5 text-xs text-primary-700 dark:text-primary-300">Increasing a water product’s inventory automatically creates its production log and stock movement.</p>
    </div>

    <x-card class="mb-6">
        <form method="GET" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <x-input-label for="date_from" value="Produced from" />
                <x-text-input id="date_from" type="text" name="date_from" data-flatpickr :value="$dateFrom" placeholder="Start date" />
            </div>
            <div>
                <x-input-label for="date_to" value="Produced to" />
                <x-text-input id="date_to" type="text" name="date_to" data-flatpickr :value="$dateTo" placeholder="End date" />
            </div>
            <div class="flex items-end justify-end gap-3">
                <x-button as="a" href="{{ route('admin.water-production.index') }}" variant="secondary">Clear Filters</x-button>
                <x-button type="submit">Filter</x-button>
            </div>
        </form>
    </x-card>

    <x-card padding="p-0">
        @if($logs->isEmpty())
            <div class="p-6">
                <x-empty-state icon="beaker" title="No production logged yet" description="Log each purified water production batch here to keep inventory in sync." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-6 py-3">Product / Container</th>
                            <th class="px-6 py-3">Source</th>
                            <th class="px-6 py-3">Gallons Produced</th>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Logged By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($logs as $log)
                            <tr>
                                <td class="px-6 py-3 text-sm font-medium text-slate-900 dark:text-white">{{ $log->container_name ?? $log->product?->name ?? 'Unavailable product' }} ({{ $log->container_size ?? $log->product?->size ?? '—' }})</td>
                                <td class="px-6 py-3 text-xs text-slate-500">{{ $log->container_name !== null ? 'Gallon Inventory · Filled' : 'Product Inventory' }}</td>
                                <td class="px-6 py-3 font-mono text-sm text-slate-900 dark:text-white">{{ number_format($log->gallons_produced) }}</td>
                                <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $log->production_date->format('M d, Y') }}</td>
                                <td class="px-6 py-3 text-sm text-slate-500 dark:text-slate-400">{{ $log->producedBy?->name ?? '—' }}</td>
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
