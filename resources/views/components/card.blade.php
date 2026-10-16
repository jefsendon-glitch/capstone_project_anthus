@props(['padding' => 'p-6'])

<div {{ $attributes->merge(['class' => "ui-card min-w-0 rounded-xl border border-slate-200 bg-white $padding shadow-sm dark:border-slate-800 dark:bg-slate-900"]) }}>
    {{ $slot }}
</div>
