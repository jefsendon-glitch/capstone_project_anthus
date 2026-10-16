@props(['icon' => 'archive', 'title', 'description' => null])

<div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center sm:px-6 sm:py-10 dark:border-white/10">
    <div class="rounded-full bg-slate-100 p-3 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
        <x-icon :name="$icon" class="size-8" />
    </div>
    <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">{{ $title }}</h3>
    @if($description)
        <p class="mt-1 max-w-sm text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-6">{{ $action }}</div>
    @endisset
</div>
