@props(['actionUrl', 'title', 'field', 'label', 'currentValue', 'triggerText', 'requiresReason' => false])

<div x-data="{ open: false }" class="inline-flex">
    <button type="button" x-on:click="open = true" class="font-mono text-sm text-primary-600 underline decoration-dotted underline-offset-4 hover:text-primary-700 dark:text-primary-400" title="{{ $title }}">
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
                    <div>
                        <x-input-label :value="$label" />
                        <x-text-input type="number" step="1" min="0" name="value" :value="$currentValue" required autofocus />
                    </div>
                    @if($requiresReason)
                        <div>
                            <x-input-label value="Reason" />
                            <x-textarea name="notes" required></x-textarea>
                        </div>
                    @endif
                    <x-button type="submit" class="w-full">Save</x-button>
                </form>
            </div>
        </div>
    </template>
</div>
