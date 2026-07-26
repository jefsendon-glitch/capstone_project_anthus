<x-layouts.guest title="Create an account - Shaunti Water Refilling">
    <div class="flex items-start gap-3">
        <span class="rounded-2xl bg-primary-50 p-3 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400"><x-icon name="user-circle" class="size-6" /></span>
        <div><h2 class="text-xl font-bold text-slate-950 dark:text-white">Create your account</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Order purified water for pickup or delivery in a few steps.</p></div>
    </div>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5">@csrf
        <div><x-input-label for="name" value="Full name" /><x-text-input id="name" name="name" :value="old('name')" required autofocus autocomplete="name" /><x-input-error :messages="$errors->get('name')" /></div>
        <div><x-input-label for="email" value="Email address" /><x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="email" /><x-input-error :messages="$errors->get('email')" /></div>
        <div><x-input-label for="contact_number" value="Contact number (optional)" /><x-text-input id="contact_number" name="contact_number" :value="old('contact_number')" autocomplete="tel" /><x-input-error :messages="$errors->get('contact_number')" /></div>
        <div><x-input-label for="address" value="Delivery address (optional)" /><x-textarea id="address" name="address" autocomplete="street-address" placeholder="Add this now or from your profile later">{{ old('address') }}</x-textarea><x-input-error :messages="$errors->get('address')" /></div>
        <div class="grid gap-5 sm:grid-cols-2"><div><x-input-label for="password" value="Password" /><x-text-input id="password" type="password" name="password" required autocomplete="new-password" /><x-input-error :messages="$errors->get('password')" /></div><div><x-input-label for="password_confirmation" value="Confirm password" /><x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" /><x-input-error :messages="$errors->get('password_confirmation')" /></div></div>
        <div class="rounded-xl bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-500 dark:bg-white/5 dark:text-slate-400"><span class="font-semibold text-slate-700 dark:text-slate-200">Secure sign-up:</span> use a unique password with at least eight characters. Your account is protected by role-based access controls.</div>
        <x-button type="submit" class="w-full">Create secure account <x-icon name="chevron-right" class="size-4" /></x-button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">Already have an account? <a href="{{ route('login') }}" class="font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400">Log in</a></p>
</x-layouts.guest>
