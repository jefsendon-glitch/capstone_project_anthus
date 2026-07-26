<textarea {{ $attributes->merge([
    'rows' => 3,
    'class' => 'block w-full resize-y rounded-xl border-0 bg-white/90 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 transition duration-200 placeholder:text-slate-400 hover:ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-primary-500 dark:bg-slate-800/90 dark:text-white dark:ring-white/10 dark:hover:ring-white/20 dark:focus:ring-primary-400',
]) }}>{{ $slot }}</textarea>
