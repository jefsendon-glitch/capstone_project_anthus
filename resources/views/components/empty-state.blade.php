@props(['icon' => 'archive', 'title', 'description' => null])

<div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 px-6 py-16 text-center dark:border-white/10">
    <div class="rounded-full bg-slate-100 p-4 text-slate-400 dark:bg-slate-800">
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
