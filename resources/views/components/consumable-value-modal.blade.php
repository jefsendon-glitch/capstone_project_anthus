@props(['actionUrl', 'title', 'field', 'label', 'currentValue', 'triggerText', 'requiresReason' => false, 'adjustmentMode' => false])

<div x-data="{ open: false }" class="inline-flex">
    <button type="button" x-on:click="open = true" class="rounded-lg bg-amber-100 px-2 py-1 font-mono text-sm text-amber-800 underline decoration-dotted underline-offset-4 hover:bg-amber-200 dark:bg-amber-950 dark:text-amber-300 dark:hover:bg-amber-900" title="{{ $title }}">
        {{ $triggerText }}
    </button>

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50" x-on:click="open = false"></div>
            <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ $title }}</h3>
                <form method="POST" action="{{ $actionUrl }}" class="mt-4 space-y-3">
                    @csrf
                    <input type="hidden" name="field" value="{{ $field }}">
                    @if($adjustmentMode)
                        <fieldset>
                            <legend class="text-sm font-medium text-slate-700 dark:text-slate-300">Adjustment type</legend>
                            <div class="mt-2 flex gap-4">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300"><input type="radio" name="adjustment_type" value="increase" checked> Increase</label>
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300"><input type="radio" name="adjustment_type" value="decrease"> Decrease</label>
                            </div>
                        </fieldset>
                    @endif
                    <div>
                        <x-input-label :value="$label" />
                        <x-text-input type="number" :step="$field === 'unit_cost' ? 0.01 : 1" :min="$adjustmentMode ? 1 : 0" name="value" :value="$adjustmentMode ? null : $currentValue" required autofocus />
                    </div>
                    @if($requiresReason)
                        <div>
                            <x-input-label value="Reason" />
                            <x-textarea name="notes" required></x-textarea>
                        </div>
                    @endif
                    <x-button type="submit" variant="warning" class="w-full">Save</x-button>
                </form>
            </div>
        </div>
    </template>
</div>
