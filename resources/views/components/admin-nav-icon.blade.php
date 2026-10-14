@props(['name', 'sidebar' => false])

@php
    $colors = [
        'home' => 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
        'cart' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'archive' => 'bg-teal-100 text-teal-700 dark:bg-teal-950 dark:text-teal-300',
        'cube' => 'bg-teal-100 text-teal-700 dark:bg-teal-950 dark:text-teal-300',
        'cog' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300',
        'building' => 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
        'users' => 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
        'truck' => 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
        'user-circle' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300',
        'chart' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'droplet' => 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
        'clipboard' => 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
        'swap' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300',
        'alert-triangle' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'credit-card' => 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
    ];
@endphp

<span {{ $attributes->class(['inline-flex shrink-0 items-center justify-center rounded-lg', $sidebar ? 'size-8' : 'size-6', $colors[$name] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300']) }}>
    <x-icon :name="$name" :class="$sidebar ? 'size-5' : 'size-4'" />
</span>
