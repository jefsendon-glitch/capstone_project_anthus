@props(['disabled' => false])

@php
    $isPassword = $attributes->get('type') === 'password';
    $inputAttributes = $isPassword ? $attributes->except('type') : $attributes;
    $inputClass = 'block w-full rounded-xl border-0 bg-white/90 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 transition duration-200 placeholder:text-slate-400 hover:ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500 dark:bg-slate-800/90 dark:text-white dark:ring-white/10 dark:hover:ring-white/20 dark:focus:ring-primary-400';
@endphp

@if($isPassword)
    <div class="relative" x-data="{ visible: false }">
        <input
            type="password"
            x-bind:type="visible ? 'text' : 'password'"
            {{ $disabled ? 'disabled' : '' }}
            {!! $inputAttributes->merge(['class' => $inputClass.' pr-12']) !!}
        >
        <button
            type="button"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 transition hover:text-primary-600 focus:outline-none dark:text-slate-500 dark:hover:text-primary-400"
            x-on:click="visible = !visible"
            x-bind:aria-label="visible ? 'Hide password' : 'Show password'"
            x-bind:title="visible ? 'Hide password' : 'Show password'"
        >
            <svg x-show="!visible" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 12a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
            <svg x-show="visible" x-cloak class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.584 10.587A2.25 2.25 0 0013.416 13.4M9.88 5.09A9.72 9.72 0 0112 4.875c6 0 9.75 7.125 9.75 7.125a17.48 17.48 0 01-3.168 3.925M6.228 6.228A17.55 17.55 0 002.25 12s3.75 7.125 9.75 7.125a9.72 9.72 0 004.224-.965"/></svg>
        </button>
    </div>
@else
    <input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => $inputClass]) !!}>
@endif
