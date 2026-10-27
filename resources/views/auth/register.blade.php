<x-layouts.guest title="Create an account - Shaunti Water Refilling">
    <div class="flex items-start gap-3">
        <span class="rounded-2xl bg-primary-50 p-3 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400"><x-icon name="user-circle" class="size-6" /></span>
        <div><h2 class="text-xl font-bold text-slate-950 dark:text-white">Create your account</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Order purified water for pickup or delivery in a few steps.</p></div>
    </div>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5">@csrf
        <div class="grid gap-4 sm:grid-cols-3">
            <div><x-input-label for="first_name" value="First name" /><x-text-input id="first_name" name="first_name" :value="old('first_name')" required autofocus autocomplete="given-name" /><x-input-error :messages="$errors->get('first_name')" /></div>
            <div><x-input-label for="middle_name" value="Middle name" /><x-text-input id="middle_name" name="middle_name" :value="old('middle_name')" required autocomplete="additional-name" /><x-input-error :messages="$errors->get('middle_name')" /></div>
            <div><x-input-label for="last_name" value="Last name" /><x-text-input id="last_name" name="last_name" :value="old('last_name')" required autocomplete="family-name" /><x-input-error :messages="$errors->get('last_name')" /></div>
        </div>
        <div><x-input-label for="email" value="Email address" /><x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="email" /><x-input-error :messages="$errors->get('email')" /></div>
        <div><x-input-label for="contact_number" value="Contact number (optional)" /><x-text-input id="contact_number" name="contact_number" :value="old('contact_number')" autocomplete="tel" /><x-input-error :messages="$errors->get('contact_number')" /></div>
        <div><x-input-label for="address" value="Delivery address" /><x-textarea id="address" name="address" required autocomplete="street-address" placeholder="Enter your complete delivery address">{{ old('address') }}</x-textarea><x-input-error :messages="$errors->get('address')" /></div>
        <div class="grid gap-5 sm:grid-cols-2"><div><x-input-label for="password" value="Password" /><x-text-input id="password" type="password" name="password" required autocomplete="new-password" /><x-input-error :messages="$errors->get('password')" /></div><div><x-input-label for="password_confirmation" value="Confirm password" /><x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" /><x-input-error :messages="$errors->get('password_confirmation')" /></div></div>
        <div class="rounded-xl bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-500 dark:bg-white/5 dark:text-slate-400"><span class="font-semibold text-slate-700 dark:text-slate-200">Secure sign-up:</span> use a unique password with at least eight characters. Your account is protected by role-based access controls.</div>
        <x-button type="submit" class="w-full">Create secure account <x-icon name="chevron-right" class="size-4" /></x-button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">Already have an account? <a href="{{ route('login') }}" class="font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400">Log in</a></p>
</x-layouts.guest>
