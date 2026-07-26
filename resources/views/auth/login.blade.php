<x-layouts.guest title="Log in - Shaunti Water Refilling">
    <div x-data="{ accountCreated: @js(session('status') === 'account-created') }">
    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Welcome back</h2>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Log in to manage your water refilling account.</p>

    @if (session('status') && session('status') !== 'account-created')
        <div class="mt-4 rounded-xl bg-success-50 px-4 py-3 text-sm font-medium text-success-700 dark:bg-success-500/10 dark:text-success-500">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Email address" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-primary-600 focus:ring-primary-600">
                Remember me
            </label>

            <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                Forgot password?
            </a>
        </div>

        <x-button type="submit" class="w-full">Log in</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        Don't have an account?
        <a href="{{ route('register') }}" class="font-medium text-primary-600 hover:text-primary-700">Create one</a>
    </p>

    <div x-show="accountCreated" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="account-created-title">
        <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm"></div>
        <div x-show="accountCreated" x-transition.scale.origin.center class="relative w-full max-w-sm rounded-3xl bg-white p-6 text-center shadow-2xl dark:bg-slate-900">
            <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400"><x-icon name="check-circle" class="size-8" /></div>
            <h2 id="account-created-title" class="mt-4 text-xl font-bold text-slate-950 dark:text-white">Account created successfully</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Your account is ready. Please log in with your email and password to continue.</p>
            <x-button type="button" class="mt-6 w-full" x-on:click="accountCreated = false; $nextTick(() => document.getElementById('email').focus())">Continue to log in</x-button>
        </div>
    </div>
    </div>
</x-layouts.guest>
