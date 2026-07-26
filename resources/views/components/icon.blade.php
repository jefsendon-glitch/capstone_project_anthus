@if($solid)
    <svg {{ $attributes->merge(['class' => 'size-5']) }} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="{{ $path }}" clip-rule="evenodd" />
    </svg>
@else
    <svg {{ $attributes->merge(['class' => 'size-5']) }} fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
    </svg>
@endif
