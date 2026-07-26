<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Shaunti Water Refilling' }}</title>

    <script>
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface font-sans antialiased dark:bg-slate-950">
    <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-4 py-10">
        <div class="pointer-events-none absolute -top-32 -right-32 size-96 rounded-full bg-primary-300/30 blur-3xl dark:bg-primary-500/10"></div>
        <div class="pointer-events-none absolute -bottom-40 -left-28 size-96 rounded-full bg-secondary-200/40 blur-3xl dark:bg-secondary-500/10"></div>
        <a href="/" class="mb-8 block w-full max-w-md" aria-label="Shaunti Water Refilling Station home">
            <img
                src="{{ asset('images/shaunti-water-logo.svg') }}"
                alt="Shaunti Water Refilling Station"
                class="mx-auto h-auto w-full max-w-sm dark:hidden"
            >
            <img
                src="{{ asset('images/shaunti-water-logo.svg') }}"
                alt="Shaunti Water Refilling Station"
                class="mx-auto hidden h-auto w-full max-w-sm brightness-0 invert dark:block"
            >
        </a>

        <div class="animate-enter w-full max-w-md rounded-3xl border border-white/70 bg-white/95 p-8 shadow-2xl shadow-slate-900/10 ring-1 ring-slate-900/5 backdrop-blur dark:border-white/5 dark:bg-slate-900/95 dark:ring-white/10">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
