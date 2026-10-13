@props(['action', 'title', 'description', 'fields' => [], 'disabled' => false])

<div x-data="{ resetOpen: {{ $errors->has('reset_confirmation') || $errors->has('reset_scope') ? 'true' : 'false' }} }" x-on:keydown.escape.window="resetOpen = false">
    <button type="button" x-on:click="resetOpen = true; $nextTick(() => $refs.resetInput.focus())" class="flex size-7 shrink-0 items-center justify-center rounded-full hover:bg-danger-50 dark:hover:bg-danger-500/10" title="{{ $title }}" aria-label="{{ $title }}">
        <span class="size-2.5 rounded-full bg-danger-500" aria-hidden="true"></span>
    </button>
    <template x-teleport="body">
    <div x-show="resetOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="{{ $title }}">
        <div class="absolute inset-0 bg-slate-950/50" x-on:click="resetOpen = false"></div>
        <div class="relative w-full max-w-sm rounded-xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-base font-semibold">{{ $title }}</h2>
            <p class="mt-2 text-xs leading-relaxed text-slate-500">{{ $description }}</p>
            <form method="POST" action="{{ $action }}" class="mt-4 space-y-3">
                @csrf @method('DELETE')
                @foreach($fields as $field => $value)<input type="hidden" name="{{ $field }}" value="{{ $value }}">@endforeach
                {{ $slot }}
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Type RESET to confirm
                    <x-text-input x-ref="resetInput" name="reset_confirmation" placeholder="RESET" pattern="RESET" autocomplete="off" required class="mt-1" />
                </label>
                <x-input-error :messages="$errors->get('reset_confirmation')" />
                <div class="flex justify-end gap-2"><x-button type="button" variant="secondary" x-on:click="resetOpen = false">Cancel</x-button><x-button type="submit" variant="danger" :disabled="$disabled">Reset Records</x-button></div>
            </form>
        </div>
    </div>
    </template>
</div>
