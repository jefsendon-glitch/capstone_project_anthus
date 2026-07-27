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
<body
    x-data="{ sidebarOpen: false, logoutConfirm: false }"
    class="min-h-screen bg-surface font-sans antialiased dark:bg-slate-950"
>
    @php
        $avatarUrl = auth()->user()->avatar_path ? Storage::disk('public')->url(auth()->user()->avatar_path) : null;
    @endphp
    <div class="app-canvas flex min-h-screen">
        {{-- Mobile sidebar backdrop --}}
        <div
            x-show="sidebarOpen"
            x-transition.opacity
            x-cloak
            class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"
            x-on:click="sidebarOpen = false"
        ></div>

        {{-- Sidebar --}}
        <aside
            x-bind:class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0', $store.sidebar.collapsed ? 'lg:w-20' : 'lg:w-72']"
            class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-white/5 bg-gradient-to-b from-[#0c2340] via-[#102f52] to-[#071a30] shadow-2xl shadow-slate-950/20 transition-all duration-200 lg:sticky lg:top-0 lg:inset-auto lg:h-screen lg:self-start"
        >
            <div class="flex h-20 shrink-0 items-center px-4" x-bind:class="$store.sidebar.collapsed && 'lg:justify-center lg:px-2'">
                <a href="{{ route('dashboard') }}" class="block w-full" aria-label="Shaunti Water Refilling Station dashboard">
                    <img
                        src="{{ asset('images/shaunti-water-logo.svg') }}"
                        alt="Shaunti Water Refilling Station"
                        class="h-14 w-full object-contain brightness-0 invert"
                        x-bind:class="$store.sidebar.collapsed && 'lg:h-12'"
                    >
                </a>
            </div>

            {{-- Profile card --}}
            <div class="shrink-0 px-4">
                <div
                    class="flex items-center gap-3 rounded-2xl bg-sidebar-accent p-3"
                    x-bind:class="$store.sidebar.collapsed && 'lg:justify-center lg:px-2'"
                >
                    @if($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="{{ auth()->user()->name }}" class="size-9 shrink-0 rounded-full object-cover ring-2 ring-white/25">
                    @else
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-500 text-sm font-semibold text-white">{{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                    @endif
                    <div class="min-w-0" x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">
                        <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                        <span class="mt-0.5 inline-flex items-center rounded-full bg-white/10 px-2 py-0.5 text-xs font-medium text-secondary-300">
                            {{ auth()->user()->role_label }}
                        </span>
                    </div>
                </div>
            </div>

            <nav
                x-data="{
                    needsScroll: false,
                    assessHeight() {
                        this.$nextTick(() => {
                            this.needsScroll = this.$el.scrollHeight > this.$el.clientHeight;
                        });
                    }
                }"
                x-init="assessHeight(); window.addEventListener('resize', () => assessHeight())"
                x-on:sidebar-navigation-resize.window="assessHeight()"
                x-bind:class="needsScroll ? 'overflow-y-auto' : 'overflow-visible'"
                class="min-h-0 flex-1 space-y-1 px-4 py-4"
            >
                @include('components.layouts.nav')
            </nav>

            <div class="shrink-0 space-y-1 border-t border-sidebar-accent p-4">
                <button
                    type="button"
                    x-on:click="$store.sidebar.toggle()"
                    class="hidden w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-200 hover:bg-white/5 lg:flex"
                    x-bind:class="$store.sidebar.collapsed && 'lg:justify-center'"
                >
                    <x-icon name="chevrons-left" class="size-5 shrink-0 transition-transform" x-bind:class="$store.sidebar.collapsed && 'rotate-180'" />
                    <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Collapse</span>
                </button>

                <button
                        type="button"
                        x-on:click="logoutConfirm = true"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-200 hover:bg-white/5"
                        x-bind:class="$store.sidebar.collapsed && 'lg:justify-center'"
                    >
                        <x-icon name="logout" class="size-5 shrink-0" />
                        <span x-bind:class="$store.sidebar.collapsed && 'lg:hidden'">Sign Out</span>
                    </button>
            </div>
        </aside>

        {{-- Main column --}}
        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Topbar --}}
            <header class="sticky top-0 z-30 flex h-[4.5rem] shrink-0 items-center gap-4 border-b border-slate-200/80 bg-white/80 px-4 shadow-sm shadow-slate-200/30 backdrop-blur-xl sm:px-6 dark:border-white/10 dark:bg-slate-900/80 dark:shadow-none">
                <button type="button" class="rounded-xl p-2 text-slate-500 transition hover:bg-slate-100 hover:text-primary-600 lg:hidden dark:hover:bg-white/5" x-on:click="sidebarOpen = true" aria-label="Open navigation">
                    <x-icon name="menu" class="size-6" />
                </button>

                <div class="min-w-0 flex-1">
                    <h1 class="font-heading truncate text-xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $heading ?? ($title ?? 'Shaunti Water') }}</h1>
                    @if(isset($subheading))
                        <p class="truncate text-sm text-slate-500 dark:text-slate-400">{{ $subheading }}</p>
                    @endif
                </div>

                <div class="ml-auto flex items-center gap-2 sm:gap-3">
                    {{-- Dark mode toggle --}}
                    <button
                        type="button"
                        x-on:click="$store.theme.toggle()"
                        class="rounded-xl border border-transparent p-2.5 text-slate-500 transition hover:border-slate-200 hover:bg-slate-100 hover:text-primary-600 dark:text-slate-300 dark:hover:border-white/10 dark:hover:bg-white/5"
                    >
                        <x-icon name="moon" class="size-5" x-show="!$store.theme.dark" />
                        <x-icon name="sun" class="size-5" x-show="$store.theme.dark" x-cloak />
                    </button>

                    {{-- Notification bell --}}
                    @php
                        $unreadNotifications = auth()->user()->unreadNotifications()->take(8)->get();
                        $unreadCount = auth()->user()->unreadNotifications()->count();
                    @endphp
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" x-on:click="open = !open" class="relative rounded-xl border border-transparent p-2.5 text-slate-500 transition hover:border-slate-200 hover:bg-slate-100 hover:text-primary-600 dark:text-slate-300 dark:hover:border-white/10 dark:hover:bg-white/5" aria-label="Open notifications">
                            <x-icon name="bell" class="size-5" />
                            @if($unreadCount > 0)
                                <span class="absolute -top-0.5 -right-0.5 flex size-4 items-center justify-center rounded-full bg-danger-600 text-[10px] font-bold text-white">
                                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                                </span>
                            @endif
                        </button>
                        <div
                            x-show="open"
                            x-on:click.outside="open = false"
                            x-transition
                            x-cloak
                            class="absolute right-0 mt-2 w-80 rounded-2xl bg-white p-2 shadow-lg ring-1 ring-slate-900/5 dark:bg-slate-900 dark:ring-white/10"
                        >
                            <div class="flex items-center justify-between px-2 py-1.5">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">Notifications</p>
                                @if($unreadCount > 0)
                                    <form method="POST" action="{{ route('notifications.read-all') }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium text-primary-600 hover:text-primary-700">Mark all read</button>
                                    </form>
                                @endif
                            </div>

                            @if($unreadNotifications->isEmpty())
                                <x-empty-state icon="bell" title="No notifications yet" description="Order and delivery alerts will show up here." />
                            @else
                                <div class="max-h-96 divide-y divide-slate-100 overflow-y-auto dark:divide-white/5">
                                    @foreach($unreadNotifications as $notification)
                                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                            @csrf
                                            <button type="submit" class="block w-full rounded-xl px-3 py-2.5 text-left hover:bg-slate-50 dark:hover:bg-white/5">
                                                <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                                <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">{{ $notification->data['message'] ?? '' }}</p>
                                                <p class="mt-1 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Profile dropdown --}}
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" x-on:click="open = !open" class="flex items-center rounded-full ring-2 ring-primary-100 transition hover:ring-primary-300 dark:ring-primary-500/20" aria-label="Open profile menu">
                            @if($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="{{ auth()->user()->name }}" class="size-9 rounded-full object-cover shadow-sm ring-2 ring-primary-100 dark:ring-primary-500/20">
                            @else
                                <span class="flex size-9 items-center justify-center rounded-full bg-gradient-to-br from-primary-400 to-primary-700 text-sm font-semibold text-white shadow-sm">{{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                            @endif
                        </button>
                        <div
                            x-show="open"
                            x-on:click.outside="open = false"
                            x-transition
                            x-cloak
                            class="absolute right-0 mt-2 w-56 rounded-2xl bg-white p-2 shadow-lg ring-1 ring-slate-900/5 dark:bg-slate-900 dark:ring-white/10"
                        >
                            <div class="border-b border-slate-100 px-3 py-2 dark:border-white/10">
                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->role_label }}</p>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="mt-1 flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5">
                                <x-icon name="user-circle" class="size-5" /> My Profile
                            </a>
                            <button type="button" x-on:click="logoutConfirm = true; open = false" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5"><x-icon name="logout" class="size-5" /> Sign Out</button>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-4 py-7 sm:px-6 lg:px-8">
                @if(isset($breadcrumbs))
                    <nav class="mb-4 flex text-sm text-slate-500 dark:text-slate-400">
                        {{ $breadcrumbs }}
                    </nav>
                @endif

                @if (session('success'))
                    <div class="mb-6 rounded-2xl bg-success-50 px-4 py-3 text-sm font-medium text-success-700 dark:bg-success-500/10 dark:text-success-500">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 rounded-2xl bg-danger-50 px-4 py-3 text-sm font-medium text-danger-700 dark:bg-danger-500/10 dark:text-danger-500">
                        {{ session('error') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <div x-show="logoutConfirm" x-cloak x-on:keydown.escape.window="logoutConfirm = false" class="fixed inset-0 z-[100] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="logout-dialog-title">
        <div x-show="logoutConfirm" x-transition.opacity class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" x-on:click="logoutConfirm = false"></div>
        <div x-show="logoutConfirm" x-transition.scale.origin.center class="relative w-full max-w-sm rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-slate-900/5 dark:bg-slate-900 dark:ring-white/10">
            <div class="flex size-12 items-center justify-center rounded-2xl bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400"><x-icon name="logout" class="size-6" /></div>
            <h2 id="logout-dialog-title" class="mt-4 text-lg font-bold text-slate-950 dark:text-white">Sign out of your account?</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">You will need to enter your email and password again to access the system.</p>
            <div class="mt-6 flex justify-end gap-3"><x-button type="button" variant="secondary" x-on:click="logoutConfirm = false">Cancel</x-button><form method="POST" action="{{ route('logout') }}">@csrf <x-button type="submit">Yes, sign out</x-button></form></div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
