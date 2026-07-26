@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'mt-2 space-y-1 text-sm font-medium text-danger-600 dark:text-danger-400']) }}>
        @foreach ((array) $messages as $message)
            <li class="flex items-start gap-1.5"><x-icon name="alert-triangle" class="mt-0.5 size-4 shrink-0" />{{ $message }}</li>
        @endforeach
    </ul>
@endif
