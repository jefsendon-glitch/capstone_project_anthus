@if($paginator->count() > 0 || $paginator->currentPage() > 1)
    <nav role="navigation" aria-label="Record pagination" class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-slate-500 dark:text-slate-400" aria-live="polite">
            @if(method_exists($paginator, 'total'))
                Showing {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} records
                <span class="ml-1">· Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
            @else
                Page {{ $paginator->currentPage() }}
            @endif
        </p>
        <div class="flex items-center gap-2">
            @if($paginator->onFirstPage())
                <x-button type="button" variant="secondary" disabled aria-label="Previous page" class="!px-3 !py-1.5 !text-xs">Previous</x-button>
            @else
                <x-button as="a" :href="$paginator->previousPageUrl()" rel="prev" variant="secondary" aria-label="Previous page" class="!px-3 !py-1.5 !text-xs">Previous</x-button>
            @endif
            @if($paginator->hasMorePages())
                <x-button as="a" :href="$paginator->nextPageUrl()" rel="next" variant="secondary" aria-label="Next page" class="!px-3 !py-1.5 !text-xs">Next</x-button>
            @else
                <x-button type="button" variant="secondary" disabled aria-label="Next page" class="!px-3 !py-1.5 !text-xs">Next</x-button>
            @endif
        </div>
    </nav>
@endif
