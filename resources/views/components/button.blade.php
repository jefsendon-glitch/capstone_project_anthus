@props(['variant' => 'primary', 'as' => 'button', 'type' => 'button'])

@php
$base = 'inline-flex shrink-0 items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 disabled:pointer-events-none disabled:opacity-50';

$variants = [
    'primary' => 'bg-primary-600 text-white shadow-sm hover:bg-primary-700',
    'secondary' => 'bg-white text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:ring-white/10 dark:hover:bg-slate-700',
    'danger' => 'bg-danger-600 text-white shadow-sm hover:bg-danger-700',
    'ghost' => 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5',
    'success' => 'bg-emerald-600 text-white shadow-sm hover:bg-emerald-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600',
    'warning' => 'bg-amber-100 text-amber-900 ring-1 ring-amber-200 hover:bg-amber-200 dark:bg-amber-950 dark:text-amber-200 dark:ring-amber-800 dark:hover:bg-amber-900',
    'info' => 'bg-sky-100 text-sky-800 ring-1 ring-sky-200 hover:bg-sky-200 dark:bg-sky-950 dark:text-sky-200 dark:ring-sky-800 dark:hover:bg-sky-900',
];

$classes = 'ui-button ui-button-'.$variant.' '.$base.' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if($as === 'a')
    <a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
