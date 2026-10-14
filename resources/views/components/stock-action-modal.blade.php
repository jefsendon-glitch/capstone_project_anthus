@props([
    'actionUrl',
    'title',
    'mode' => 'add',
    'triggerIcon' => 'plus',
    'triggerColor' => null,
    'triggerText' => null,
    'adjustmentUsesDirection' => false,
])

@php
    $triggerColor ??= $mode === 'add'
        ? 'rounded-lg bg-emerald-100 p-2 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-950 dark:text-emerald-300'
        : 'rounded-lg bg-amber-100 p-2 text-amber-800 hover:bg-amber-200 dark:bg-amber-950 dark:text-amber-300';
@endphp

<div x-data="{ open: false }" class="inline-flex">
    <button type="button" x-on:click="open = true" class="{{ $triggerColor }}" title="{{ $title }}">
        @if($triggerText !== null)
            {{ $triggerText }}
        @else
            <x-icon :name="$triggerIcon" class="size-4" />
        @endif
    </button>

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50" x-on:click="open = false"></div>
            <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ $title }}</h3>
                <form method="POST" action="{{ $actionUrl }}" class="mt-4 space-y-3">
                    @csrf

                    {{ $slot }}

                    @if($mode === 'add')
                        <div>
                            <x-input-label value="Quantity to add" />
                            <x-text-input type="number" step="1" min="1" name="quantity" required />
                        </div>
                    @elseif($mode === 'update')
                        <div>
                            <x-input-label value="New quantity (absolute)" />
                            <x-text-input type="number" step="1" min="0" name="quantity" required />
                        </div>
                    @elseif($mode === 'adjust')
                        @if($adjustmentUsesDirection)
                            <fieldset>
                                <legend class="text-sm font-medium text-slate-700 dark:text-slate-300">Adjustment type</legend>
                                <div class="mt-2 flex gap-4">
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                        <input type="radio" name="adjustment_type" value="increase" checked>
                                        Increase
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                        <input type="radio" name="adjustment_type" value="decrease">
                                        Decrease
                                    </label>
                                </div>
                            </fieldset>
                            <div>
                                <x-input-label value="Quantity" />
                                <x-text-input type="number" step="1" min="1" name="quantity" required />
                            </div>
                        @else
                            <div>
                                <x-input-label value="Adjustment (use - to subtract)" />
                                <x-text-input type="number" step="1" name="delta" required />
                            </div>
                        @endif
                        <div>
                            <x-input-label value="Reason" />
                            <x-textarea name="notes" required></x-textarea>
                        </div>
                    @endif

                    <x-button type="submit" :variant="$mode === 'add' ? 'success' : 'warning'" class="w-full">{{ $title }}</x-button>
                </form>
            </div>
        </div>
    </template>
</div>
