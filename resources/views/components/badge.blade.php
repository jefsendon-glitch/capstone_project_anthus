@props(['color' => 'slate'])

@php
$colors = [
    'slate' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    'primary' => 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300',
    'success' => 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-500',
    'warning' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-500',
    'danger' => 'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-500',
    'secondary' => 'bg-secondary-50 text-secondary-700 dark:bg-secondary-500/10 dark:text-secondary-300',
    'delivery' => 'bg-delivery-100 text-delivery-700 dark:bg-delivery-500/10 dark:text-delivery-500',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ' . ($colors[$color] ?? $colors['slate'])]) }}>
    {{ $slot }}
</span>
