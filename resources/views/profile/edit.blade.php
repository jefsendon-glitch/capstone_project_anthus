<x-layouts.app title="My Profile" :heading="'My Profile'" :subheading="'Manage your personal details, delivery information, and password.'">
    @php($avatarUrl = $user->avatar_url)

    @if (session('status') === 'profile-updated')
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">Your profile has been updated.</div>
    @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex min-w-0 items-center gap-4">
            @if($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="size-14 shrink-0 rounded-xl object-cover ring-1 ring-slate-200 dark:ring-white/10">
            @else
                <div class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-primary-100 text-xl font-bold text-primary-700 dark:bg-primary-500/15 dark:text-primary-300">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</div>
            @endif
            <div class="min-w-0"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Signed in as</p><h2 class="mt-1 truncate text-lg font-bold text-slate-950 dark:text-white">{{ $user->name }}</h2><p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $user->email }}</p></div>
        </div>
        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"><span class="size-2 rounded-full bg-emerald-500"></span>{{ ucfirst($user->status) }} account</span>
    </div>

    <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
        <x-card>
            <div class="mb-5"><h2 class="text-lg font-semibold text-slate-950 dark:text-white">Personal information</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">These details identify your account and help the station contact you.</p></div>
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5">@csrf @method('patch')
                <div x-data="{ preview: null }">
                    <x-input-label for="avatar" value="Profile photo" />
                    <div class="mt-2 flex flex-wrap items-center gap-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3 dark:border-white/15 dark:bg-white/5">
                        <template x-if="preview"><img :src="preview" alt="New profile photo preview" class="size-12 rounded-lg object-cover"></template>
                        <template x-if="!preview">
                            @if($avatarUrl)<img src="{{ $avatarUrl }}" alt="Current profile photo" class="size-12 rounded-lg object-cover">
                            @else<div class="flex size-12 items-center justify-center rounded-lg bg-primary-100 font-bold text-primary-700 dark:bg-primary-500/15 dark:text-primary-300">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</div>@endif
                        </template>
                        <div class="min-w-0 flex-1"><p class="text-sm font-medium text-slate-700 dark:text-slate-200">JPG, PNG, or WEBP · Maximum 2 MB</p><p class="mt-0.5 text-xs text-slate-500">Shown in your account menu.</p></div>
                        <label for="avatar" class="cursor-pointer rounded-lg bg-white px-3 py-2 text-sm font-semibold text-primary-700 shadow-sm ring-1 ring-slate-200 hover:bg-primary-50 dark:bg-slate-800 dark:text-primary-300 dark:ring-white/10">Choose photo</label>
                        <input id="avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="sr-only" x-on:change="const file = $event.target.files[0]; preview = file ? URL.createObjectURL(file) : null">
                    </div>
                    <x-input-error :messages="$errors->get('avatar')" />
                </div>

                <fieldset><legend class="mb-2 text-sm font-semibold text-slate-800 dark:text-slate-200">Full name</legend>
                    @if(!$user->first_name)<p class="mb-3 text-xs leading-relaxed text-slate-500">Check the suggested name fields, especially if you have a compound first or last name.</p>@endif
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div><x-input-label for="first_name" value="First name" /><x-text-input id="first_name" name="first_name" :value="old('first_name', $profileNames['first_name'])" maxlength="100" required autocomplete="given-name" /><x-input-error :messages="$errors->get('first_name')" /></div>
                        <div><x-input-label for="middle_name" value="Middle name" /><x-text-input id="middle_name" name="middle_name" :value="old('middle_name', $profileNames['middle_name'])" maxlength="100" required autocomplete="additional-name" /><x-input-error :messages="$errors->get('middle_name')" /></div>
                        <div><x-input-label for="last_name" value="Last name" /><x-text-input id="last_name" name="last_name" :value="old('last_name', $profileNames['last_name'])" maxlength="100" required autocomplete="family-name" /><x-input-error :messages="$errors->get('last_name')" /></div>
                    </div>
                </fieldset>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div><x-input-label for="email" value="Email address" /><x-text-input id="email" type="email" name="email" :value="old('email', $user->email)" required autocomplete="email" /><x-input-error :messages="$errors->get('email')" /><p class="mt-1 text-xs text-slate-500">Used to sign in and receive account notices.</p></div>
                    <div><x-input-label for="contact_number" value="Contact number" /><x-text-input id="contact_number" name="contact_number" :value="old('contact_number', $user->contact_number)" autocomplete="tel" /><x-input-error :messages="$errors->get('contact_number')" /><p class="mt-1 text-xs text-slate-500">For delivery coordination and support.</p></div>
                </div>
                @if($user->isCustomer())
                    <div><x-input-label for="address" value="Default delivery address" /><x-textarea id="address" name="address" required autocomplete="street-address" placeholder="House number, street, barangay, and city">{{ old('address', $user->address) }}</x-textarea><x-input-error :messages="$errors->get('address')" /><p class="mt-1 text-xs text-slate-500">You can still choose a different address when placing an order.</p></div>
                @endif
                <div class="border-t border-slate-100 pt-4 dark:border-white/10"><x-button type="submit"><x-icon name="check-circle" class="size-4" /> Save personal information</x-button></div>
            </form>
        </x-card>

        <aside class="space-y-5">
            <x-card>
                <h2 class="text-base font-semibold text-slate-950 dark:text-white">Account information</h2>
                <dl class="mt-4 divide-y divide-slate-100 text-sm dark:divide-white/10">
                    <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">Account type</dt><dd class="text-right font-medium">{{ $user->role_label }}</dd></div>
                    <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">Member since</dt><dd class="text-right font-medium">{{ $user->created_at->format('M Y') }}</dd></div>
                    <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">Account status</dt><dd class="text-right font-medium text-emerald-700 dark:text-emerald-300">{{ ucfirst($user->status) }}</dd></div>
                </dl>
                @if($user->isStaff())
                    <div class="mt-4 border-t border-slate-100 pt-4 dark:border-white/10"><h3 class="text-sm font-semibold">Employment details</h3><p class="mt-1 text-xs text-slate-500">Managed by your administrator.</p><dl class="mt-3 space-y-2 text-sm"><div class="flex justify-between gap-3"><dt class="text-slate-500">Employee ID</dt><dd>{{ $user->staff?->employee_id ?? 'Not assigned' }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-500">Position</dt><dd>{{ $user->staff?->position ?? 'Staff' }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-500">Hire date</dt><dd>{{ $user->staff?->hire_date?->format('M d, Y') ?? 'Not recorded' }}</dd></div></dl></div>
                @endif
            </x-card>

            <x-card>
                <h2 class="text-base font-semibold text-slate-950 dark:text-white">Password and security</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Change your password here. Use a strong password you do not use on other sites.</p>
                @if (session('status') === 'password-updated')<div class="mt-4 rounded-lg bg-success-50 px-3 py-2 text-sm font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">Password updated.</div>@endif
                <form method="POST" action="{{ route('password.update') }}" class="mt-4 space-y-4">@csrf @method('put')
                    <div><x-input-label for="current_password" value="Current password" /><x-text-input id="current_password" type="password" name="current_password" autocomplete="current-password" /><x-input-error :messages="$errors->getBag('updatePassword')->get('current_password')" /></div>
                    <div><x-input-label for="password" value="New password" /><x-text-input id="password" type="password" name="password" autocomplete="new-password" /><x-input-error :messages="$errors->getBag('updatePassword')->get('password')" /></div>
                    <div><x-input-label for="password_confirmation" value="Confirm new password" /><x-text-input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" /><x-input-error :messages="$errors->getBag('updatePassword')->get('password_confirmation')" /></div>
                    <x-button type="submit" variant="secondary" class="w-full">Update password</x-button>
                </form>
            </x-card>
        </aside>
    </div>
</x-layouts.app>
