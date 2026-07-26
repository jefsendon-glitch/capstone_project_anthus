@props(['label', 'value', 'icon' => 'chart', 'color' => 'primary', 'hint' => null, 'variant' => 'plain'])

@php
$iconColors = [
    'primary' => 'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400',
    'secondary' => 'bg-secondary-50 text-secondary-600 dark:bg-secondary-500/10 dark:text-secondary-400',
    'success' => 'bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-500',
    'warning' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-500',
    'danger' => 'bg-danger-50 text-danger-600 dark:bg-danger-500/10 dark:text-danger-500',
    'delivery' => 'bg-delivery-100 text-delivery-600 dark:bg-delivery-500/10 dark:text-delivery-400',
];

$solidColors = [
    'primary' => '!bg-primary-600',
    'secondary' => '!bg-secondary-600',
    'success' => '!bg-success-600',
    'warning' => '!bg-warning-600',
    'danger' => '!bg-danger-600',
    'delivery' => '!bg-delivery-600',
];

$tintedColors = [
    'primary' => 'bg-primary-50 dark:bg-primary-500/10',
    'secondary' => 'bg-secondary-50 dark:bg-secondary-500/10',
    'success' => 'bg-success-50 dark:bg-success-500/10',
    'warning' => 'bg-warning-50 dark:bg-warning-500/10',
    'danger' => 'bg-danger-50 dark:bg-danger-500/10',
    'delivery' => 'bg-delivery-50 dark:bg-delivery-500/10',
];

$tintedText = [
    'primary' => 'text-primary-700 dark:text-primary-400',
    'secondary' => 'text-secondary-700 dark:text-secondary-400',
    'success' => 'text-success-700 dark:text-success-500',
    'warning' => 'text-warning-700 dark:text-warning-500',
    'danger' => 'text-danger-700 dark:text-danger-500',
    'delivery' => 'text-delivery-700 dark:text-delivery-400',
];
@endphp

@if($variant === 'solid')
    <x-card class="flex min-h-36 items-start justify-between overflow-hidden bg-gradient-to-br {{ $solidColors[$color] ?? $solidColors['primary'] }} text-white">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-white/80">{{ $label }}</p>
            <p class="mt-2 text-2xl font-bold">{{ $value }}</p>
            @if($hint)
                <p class="mt-1 text-xs text-white/70">{{ $hint }}</p>
            @endif
        </div>
        <div class="rounded-2xl bg-white/20 p-3 text-white">
            <x-icon :name="$icon" class="size-6" />
        </div>
    </x-card>
@elseif($variant === 'tinted')
    <x-card class="flex min-h-36 items-start justify-between {{ $tintedColors[$color] ?? $tintedColors['warning'] }}">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide {{ $tintedText[$color] ?? $tintedText['warning'] }}">{{ $label }}</p>
            <p class="mt-2 text-2xl font-bold {{ $tintedText[$color] ?? $tintedText['warning'] }}">{{ $value }}</p>
            @if($hint)
                <p class="mt-1 text-xs {{ $tintedText[$color] ?? $tintedText['warning'] }} opacity-80">{{ $hint }}</p>
            @endif
        </div>
        <div class="rounded-2xl bg-white/50 p-3 dark:bg-white/10 {{ $tintedText[$color] ?? $tintedText['warning'] }}">
            <x-icon :name="$icon" class="size-6" />
        </div>
    </x-card>
@else
    <x-card class="flex min-h-36 items-start justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $label }}</p>
            <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ $value }}</p>
            @if($hint)
                <p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>
            @endif
        </div>
        <div class="rounded-2xl p-3 {{ $iconColors[$color] ?? $iconColors['primary'] }}">
            <x-icon :name="$icon" class="size-6" />
        </div>
    </x-card>
@endif
