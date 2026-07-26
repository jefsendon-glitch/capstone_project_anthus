@props(['variant' => 'primary', 'as' => 'button', 'type' => 'button'])

@php
$base = 'inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all duration-200 active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50';

$variants = [
    'primary' => 'bg-gradient-to-br from-primary-500 to-primary-700 text-white shadow-lg shadow-primary-600/20 hover:-translate-y-0.5 hover:from-primary-400 hover:to-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600',
    'secondary' => 'bg-white text-slate-700 shadow-sm ring-1 ring-slate-200 hover:-translate-y-0.5 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:ring-white/10 dark:hover:bg-slate-700',
    'danger' => 'bg-gradient-to-br from-danger-500 to-danger-700 text-white shadow-lg shadow-danger-600/20 hover:-translate-y-0.5',
    'ghost' => 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5',
];

$classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

@if($as === 'a')
    <a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
