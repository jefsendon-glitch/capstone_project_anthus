@if($paginator->count() > 0 || $paginator->currentPage() > 1)
<nav aria-label="Record pagination" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <p class="text-xs text-slate-500 dark:text-slate-400">
        <span class="font-medium text-slate-700 dark:text-slate-200">{{ $paginator->firstItem() ?? 0 }}&ndash;{{ $paginator->lastItem() ?? 0 }}</span>
        @if(method_exists($paginator, 'total'))
            of {{ $paginator->total() }} {{ $recordLabel ?? 'records' }}
        @endif
    </p>
    <div class="flex items-center justify-between gap-2 sm:justify-end">
        @if($paginator->onFirstPage())
            <x-button type="button" variant="info" disabled aria-label="Previous page" class="!px-3 !py-2 !text-xs">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6" /></svg>
                Previous
            </x-button>
        @else
            <x-button as="a" :href="$paginator->previousPageUrl()" rel="prev" variant="info" aria-label="Previous page" class="!px-3 !py-2 !text-xs">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6" /></svg>
                Previous
            </x-button>
        @endif
        <span class="whitespace-nowrap px-1 text-xs font-medium text-slate-600 dark:text-slate-300" aria-live="polite">Page {{ $paginator->currentPage() }}@if(method_exists($paginator, 'lastPage')) of {{ $paginator->lastPage() }}@endif</span>
        @if($paginator->hasMorePages())
            <x-button as="a" :href="$paginator->nextPageUrl()" rel="next" variant="primary" aria-label="Next page" class="!px-3 !py-2 !text-xs !shadow-sm">
                Next
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6" /></svg>
            </x-button>
        @else
            <x-button type="button" variant="primary" disabled aria-label="Next page" class="!px-3 !py-2 !text-xs !shadow-sm">
                Next
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6" /></svg>
            </x-button>
        @endif
    </div>
</nav>

@endif
