<x-layouts.guest title="Forgot password - Shaunti Water Refilling">
    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Forgot your password?</h2>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
        Enter your email and we'll send you a link to reset it.
    </p>

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-success-50 px-4 py-3 text-sm font-medium text-success-700 dark:bg-success-500/10 dark:text-success-500">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Email address" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <x-button type="submit" class="w-full">Email password reset link</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:text-primary-700">Back to log in</a>
    </p>
</x-layouts.guest>
