@props(['name', 'title' => null, 'maxWidth' => 'md'])

@php
$maxWidths = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
];
@endphp

<div
    x-data="{ open: false }"
    x-on:open-modal.window="$event.detail === '{{ $name }}' && (open = true)"
    x-on:close-modal.window="$event.detail === '{{ $name }}' && (open = false)"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
>
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-900/50" x-on:click="open = false"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="relative w-full {{ $maxWidths[$maxWidth] }} rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900"
        >
            @if($title)
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $title }}</h3>
            @endif

            <div class="mt-4">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
