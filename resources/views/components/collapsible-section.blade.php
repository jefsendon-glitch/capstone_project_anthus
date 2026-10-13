@props(['title', 'open' => false])

<details {{ $attributes->merge(['class' => 'overview-section group mt-4']) }} @if($open) open @endif>
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-white dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden">
        <span>{{ $title }}</span>
        <x-icon name="chevron-down" class="size-4 shrink-0 text-slate-400 transition-transform group-open:rotate-180" />
    </summary>
    <div class="mt-3 min-w-0">{{ $slot }}</div>
</details>
