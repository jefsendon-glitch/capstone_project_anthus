<x-layouts.app title="My Gallons" :heading="'My Gallons'" :subheading="'Your borrowed gallons, returns, and container records'">

    <x-card padding="p-4" class="mb-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="rounded-lg bg-primary-50 p-3 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"><x-icon name="archive" class="size-6" /></span>
                <div><p class="text-sm text-slate-500 dark:text-slate-400">Gallons to Return</p><p class="mt-1 text-3xl font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format($borrowedCount) }} <span class="text-sm font-normal text-slate-500">{{ (int) $borrowedCount === 1 ? 'gallon' : 'gallons' }}</span></p></div>
            </div>
            <p class="max-w-md text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $borrowedCount > 0 ? 'These gallons are still borrowed under your account. Arrange your return with the station.' : 'You have no outstanding borrowed gallons.' }}</p>
        </div>
    </x-card>

    <form method="GET" action="{{ route('customer.gallons') }}" class="mb-4 grid gap-2 sm:flex sm:items-end">
        <div class="min-w-0 flex-1"><x-input-label for="gallon-search" value="Search My Gallons" /><div class="relative"><x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" /><x-text-input id="gallon-search" type="search" name="search" :value="request('search')" placeholder="Gallon name, type, missing, or damaged..." maxlength="100" class="!py-2 !pl-9" /></div></div>
        <div class="flex gap-2"><x-button type="submit" variant="secondary" class="!py-2">Search</x-button>@if(request()->filled('search'))<x-button as="a" :href="route('customer.gallons')" variant="secondary" class="!py-2">Clear</x-button>@endif</div>
    </form>
    @if($errors->any())<div role="alert" class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

    <div class="space-y-4">
        <x-card padding="p-0">
            <details class="group">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-xl px-4 py-3 outline-none transition hover:bg-slate-50 focus-visible:ring-2 focus-visible:ring-primary-500 dark:hover:bg-white/5">
                    <span><span class="block text-sm font-semibold">Current Borrowed</span><span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">Remaining balance for each gallon type</span></span>
                    <span class="flex shrink-0 items-center gap-2"><x-badge color="primary">{{ $balances->total() }} {{ $balances->total() === 1 ? 'record' : 'records' }}</x-badge><x-icon name="chevron-down" class="size-4 text-slate-500 transition-transform group-open:rotate-180" /></span>
                </summary>
                <div class="border-t border-slate-100 dark:border-white/10">
            <ul class="divide-y divide-slate-100 dark:divide-white/10" aria-label="Currently borrowed gallons">
                @forelse($balances as $balance)
                    <li class="flex items-center justify-between gap-3 px-4 py-3"><div class="min-w-0"><p class="break-words text-sm font-semibold text-slate-900 dark:text-white">{{ $balance->name }}</p><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $balance->type ?? 'Unspecified type' }} &middot; {{ $balance->size }}</p></div><div class="shrink-0 text-right"><p class="text-lg font-semibold tabular-nums text-primary-700 dark:text-primary-300">{{ number_format($balance->quantity) }}</p><p class="text-xs text-slate-500 dark:text-slate-400">to return</p></div></li>
                @empty<li class="p-4"><x-empty-state icon="archive" :title="request()->filled('search') ? 'No matching borrowed gallons' : 'No gallons to return'" :description="request()->filled('search') ? 'Try another gallon name or type. Condition searches apply to history.' : 'New gallon loans recorded by the station will appear here.'" /></li>@endforelse
            </ul>
            <div class="border-t border-slate-100 px-4 py-3 dark:border-white/10">{{ $balances->links() }}</div>
                </div>
            </details>
        </x-card>

        <x-card padding="p-0">
            <details class="group">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-xl px-4 py-3 outline-none transition hover:bg-slate-50 focus-visible:ring-2 focus-visible:ring-primary-500 dark:hover:bg-white/5">
                    <span><span class="block text-sm font-semibold">Gallon History</span><span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">Borrowing, returns, missing, and damaged gallons</span></span>
                    <span class="flex shrink-0 items-center gap-2"><x-badge>{{ $history->total() }} {{ $history->total() === 1 ? 'record' : 'records' }}</x-badge><x-icon name="chevron-down" class="size-4 text-slate-500 transition-transform group-open:rotate-180" /></span>
                </summary>
                <div class="border-t border-slate-100 dark:border-white/10">
            <ul class="divide-y divide-slate-100 dark:divide-white/10" aria-label="Gallon movement history">
                @forelse($history as $record)
                    @php
                        $recordLabel = $record->action === 'return' ? ($record->to_status === 'damaged' ? 'Damaged return' : 'Usable return') : ($record->action === 'release' ? 'Borrowed' : ucfirst($record->to_status));
                        $recordColor = match ($record->to_status) { 'missing' => 'danger', 'damaged' => 'warning', 'empty' => 'success', default => 'primary' };
                    @endphp
                    <li class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 px-4 py-3 sm:grid-cols-[minmax(0,1fr)_11rem_5rem]">
                        <div class="min-w-0"><p class="break-words text-sm font-semibold text-slate-900 dark:text-white">{{ $record->name }}</p><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $record->type ?? 'Unspecified type' }} &middot; {{ $record->size }}</p><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ \Illuminate\Support\Carbon::parse($record->created_at)->format('M d, Y - g:i A') }}</p></div>
                        <div class="col-start-1 row-start-2 sm:col-start-auto sm:row-start-auto"><x-badge :color="$recordColor">{{ $recordLabel }}</x-badge></div>
                        <div class="col-start-2 row-start-1 text-right sm:col-start-auto"><p class="text-lg font-semibold tabular-nums">{{ number_format($record->quantity) }}</p><p class="text-xs text-slate-500 dark:text-slate-400">{{ (int) $record->quantity === 1 ? 'gallon' : 'gallons' }}</p></div>
                    </li>
                @empty<li class="p-4"><x-empty-state icon="archive" title="No gallon history found" :description="request()->filled('search') ? 'Try a different gallon name, type, or condition.' : 'Movements recorded by the station will appear here.'" /></li>@endforelse
            </ul>
            <div class="border-t border-slate-100 px-4 py-3 dark:border-white/10">{{ $history->links() }}</div>
                </div>
            </details>
        </x-card>
    </div>
    <p class="mt-4 text-xs leading-relaxed text-slate-500 dark:text-slate-400">Missing gallons are not counted as returned. Records are updated by station staff or Admin and remain available while retained by the station. Contact the station if a record needs correction.</p>
</x-layouts.app>
