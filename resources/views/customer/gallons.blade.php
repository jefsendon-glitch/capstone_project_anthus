<x-layouts.app title="My Gallons" :heading="'My Gallons'" :subheading="'Borrowed containers and returns recorded by the station'">
    <x-portal-nav />
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-800 dark:bg-primary-900/20">
        <div><p class="text-xs text-slate-500">Outstanding Borrowed Gallons</p><p class="mt-1 text-2xl font-semibold tabular-nums text-primary-700 dark:text-primary-300">{{ number_format($borrowedCount) }}</p></div>
        <p class="max-w-md text-xs text-slate-600 dark:text-slate-300">Arrange returns with the station. Missing and damaged records appear here once recorded by Admin. Contact the station if a record needs correction.</p>
    </div>
    <form method="GET" class="mb-4 flex gap-2">
        <x-text-input type="search" name="search" :value="request('search')" placeholder="Search gallon name, type, or condition..." aria-label="Search my gallon records" maxlength="100" class="!py-2" />
        <x-button type="submit" variant="secondary" class="!py-2 !text-xs">Search</x-button>
        @if(request()->filled('search'))<x-button as="a" :href="route('customer.gallons')" variant="secondary" class="!py-2 !text-xs">Clear</x-button>@endif
    </form>
    <div class="grid items-start gap-4 xl:grid-cols-2">
        <x-card padding="p-4">
            <h2 class="mb-3 text-sm font-semibold">Currently Borrowed</h2>
            <div class="overflow-x-auto"><table class="min-w-full text-left text-xs"><thead><tr class="border-b border-slate-200 text-slate-500 dark:border-white/10"><th class="py-2">Gallon</th><th class="py-2">Type / Size</th><th class="py-2 text-right">Quantity</th></tr></thead><tbody>
                @forelse($balances as $balance)<tr class="border-b border-slate-100 dark:border-white/5"><td class="py-3">{{ $balance->name }}</td><td class="py-3">{{ $balance->type ?? 'Unspecified' }} / {{ $balance->size }}</td><td class="py-3 text-right font-semibold">{{ $balance->quantity }}</td></tr>
                @empty<tr><td colspan="3" class="py-5 text-slate-500">{{ request()->filled('search') ? 'No borrowed gallons match your search.' : 'You have no outstanding borrowed gallons.' }}</td></tr>@endforelse
            </tbody></table></div><div class="mt-3">{{ $balances->links() }}</div>
        </x-card>
        <x-card padding="p-4">
            <h2 class="mb-1 text-sm font-semibold">Gallon History</h2><p class="mb-3 text-xs text-slate-500">Missing gallons are separate from returned gallons. History is available while retained by the station.</p>
            <div class="overflow-x-auto"><table class="min-w-full text-left text-xs"><thead><tr class="border-b border-slate-200 text-slate-500 dark:border-white/10"><th class="py-2">Date / Gallon</th><th class="px-2 py-2">Record</th><th class="py-2 text-right">Quantity</th></tr></thead><tbody>
                @forelse($history as $record)
                    <tr class="border-b border-slate-100 dark:border-white/5"><td class="py-3"><p>{{ $record->name }} / {{ $record->type ?? 'Unspecified' }}</p><p class="mt-1 text-slate-500">{{ \Illuminate\Support\Carbon::parse($record->created_at)->format('M d, Y') }}</p></td><td class="px-2 py-3"><x-badge :color="$record->to_status === 'missing' ? 'danger' : ($record->to_status === 'damaged' ? 'warning' : 'primary')">{{ $record->action === 'return' ? ($record->to_status === 'damaged' ? 'Damaged return' : 'Usable return') : ($record->action === 'release' ? 'Borrowed' : ucfirst($record->to_status)) }}</x-badge></td><td class="py-3 text-right font-semibold">{{ $record->quantity }}</td></tr>
                @empty<tr><td colspan="3" class="py-5 text-slate-500">No gallon history found.</td></tr>@endforelse
            </tbody></table></div><div class="mt-3">{{ $history->links() }}</div>
        </x-card>
    </div>
</x-layouts.app>
