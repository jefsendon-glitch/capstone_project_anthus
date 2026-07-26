@props([
    'actionUrl',
    'title',
    'mode' => 'add',
    'triggerIcon' => 'plus',
    'triggerColor' => 'text-slate-500 hover:text-primary-600',
])

<div x-data="{ open: false }" class="inline-flex">
    <button type="button" x-on:click="open = true" class="{{ $triggerColor }}" title="{{ $title }}">
        <x-icon :name="$triggerIcon" class="size-4" />
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
                            <x-text-input type="number" step="0.01" min="0.01" name="quantity" required />
                        </div>
                    @elseif($mode === 'update')
                        <div>
                            <x-input-label value="New quantity (absolute)" />
                            <x-text-input type="number" step="0.01" min="0" name="quantity" required />
                        </div>
                    @elseif($mode === 'adjust')
                        <div>
                            <x-input-label value="Adjustment (use - to subtract)" />
                            <x-text-input type="number" step="0.01" name="delta" required />
                        </div>
                        <div>
                            <x-input-label value="Reason" />
                            <x-textarea name="notes" required></x-textarea>
                        </div>
                    @endif

                    <x-button type="submit" class="w-full">{{ $title }}</x-button>
                </form>
            </div>
        </div>
    </template>
</div>
