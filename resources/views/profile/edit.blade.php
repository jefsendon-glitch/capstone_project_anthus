<x-layouts.app title="My Profile" :heading="'My Profile'">
    @php($avatarUrl = $user->avatar_path ? Storage::disk('public')->url($user->avatar_path) : null)
    <div class="mx-auto max-w-5xl space-y-6">
        <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-primary-700 via-primary-600 to-cyan-500 p-6 text-white shadow-xl shadow-primary-950/15 sm:p-8">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    @if($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="size-16 rounded-2xl object-cover ring-2 ring-white/30">
                    @else
                        <div class="flex size-16 items-center justify-center rounded-2xl bg-white/15 text-2xl font-bold ring-1 ring-white/30">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</div>
                    @endif
                    <div><p class="text-sm font-semibold text-white/75">Account profile</p><h2 class="mt-1 text-2xl font-bold">{{ $user->name }}</h2><p class="mt-1 text-sm text-white/80">{{ $user->role_label }} · Member since {{ $user->created_at->format('M Y') }}</p></div>
                </div>
                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-white/15 px-3 py-1.5 text-sm font-semibold ring-1 ring-white/20"><span class="size-2 rounded-full bg-emerald-300"></span> Active account</span>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-[1.35fr_.65fr]">
            <x-card>
                <div class="flex items-start gap-3"><span class="rounded-xl bg-primary-50 p-2 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400"><x-icon name="user-circle" class="size-5" /></span><div><h2 class="text-base font-semibold text-slate-950 dark:text-white">Personal details</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Keep your contact information current for account support and deliveries.</p></div></div>
                @if (session('status') === 'profile-updated')<div class="mt-5 rounded-xl bg-success-50 px-4 py-3 text-sm font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">Your profile has been updated.</div>@endif
                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 grid gap-5 sm:grid-cols-2">@csrf @method('patch')
                    <div class="sm:col-span-2" x-data="{ preview: null }">
                        <x-input-label for="avatar" value="Profile photo (optional)" />
                        <div class="mt-2 flex flex-wrap items-center gap-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-3 dark:border-white/15 dark:bg-white/5">
                            <template x-if="preview"><img :src="preview" alt="New profile photo preview" class="size-14 rounded-xl object-cover"></template>
                            <template x-if="!preview"><div class="flex size-14 items-center justify-center rounded-xl bg-primary-100 text-lg font-bold text-primary-700 dark:bg-primary-500/15 dark:text-primary-300">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</div></template>
                            <div class="min-w-0 flex-1"><p class="text-sm font-medium text-slate-700 dark:text-slate-200">JPG, PNG, or WEBP up to 2 MB</p><p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Your photo appears in the navigation and account menu.</p></div>
                            <label for="avatar" class="cursor-pointer rounded-xl bg-white px-3 py-2 text-sm font-semibold text-primary-700 shadow-sm ring-1 ring-slate-200 hover:bg-primary-50 dark:bg-slate-800 dark:text-primary-300 dark:ring-white/10 dark:hover:bg-slate-700">Choose photo</label>
                            <input id="avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="sr-only" x-on:change="const file = $event.target.files[0]; preview = file ? URL.createObjectURL(file) : null">
                        </div>
                        <x-input-error :messages="$errors->get('avatar')" />
                    </div>
                    <div><x-input-label for="name" value="Full name" /><x-text-input id="name" name="name" :value="old('name', $user->name)" required autocomplete="name" /><x-input-error :messages="$errors->get('name')" /></div>
                    <div><x-input-label for="email" value="Email address" /><x-text-input id="email" type="email" name="email" :value="old('email', $user->email)" required autocomplete="email" /><x-input-error :messages="$errors->get('email')" /></div>
                    <div class="sm:col-span-2"><x-input-label for="contact_number" value="Contact number" /><x-text-input id="contact_number" name="contact_number" :value="old('contact_number', $user->contact_number)" autocomplete="tel" /><x-input-error :messages="$errors->get('contact_number')" /></div>
                    @if($user->isCustomer())<div class="sm:col-span-2"><x-input-label for="address" value="Default delivery address" /><x-textarea id="address" name="address" autocomplete="street-address">{{ old('address', $user->address) }}</x-textarea><x-input-error :messages="$errors->get('address')" /></div>@endif
                    <div class="sm:col-span-2"><x-button type="submit"><x-icon name="check-circle" class="size-4" /> Save profile</x-button></div>
                </form>
            </x-card>

            <div class="space-y-6"><x-card><div class="flex items-start gap-3"><span class="rounded-xl bg-success-50 p-2 text-success-600 dark:bg-success-500/10 dark:text-success-400"><x-icon name="cog" class="size-5" /></span><div><h2 class="text-base font-semibold text-slate-950 dark:text-white">Account summary</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Your access is protected by your password and role permissions.</p></div></div><dl class="mt-5 space-y-3 text-sm"><div class="flex justify-between gap-4"><dt class="text-slate-500 dark:text-slate-400">Role</dt><dd class="font-semibold text-slate-900 dark:text-white">{{ $user->role_label }}</dd></div><div class="flex justify-between gap-4"><dt class="text-slate-500 dark:text-slate-400">Status</dt><dd class="font-semibold text-success-600 dark:text-success-400">Active</dd></div><div class="flex justify-between gap-4"><dt class="text-slate-500 dark:text-slate-400">Email</dt><dd class="max-w-[13rem] truncate font-medium text-slate-900 dark:text-white">{{ $user->email }}</dd></div></dl></x-card>
                <x-card><h2 class="text-base font-semibold text-slate-950 dark:text-white">Security</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Choose a strong, unique password you do not reuse elsewhere.</p>@if (session('status') === 'password-updated')<div class="mt-4 rounded-xl bg-success-50 px-4 py-3 text-sm font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">Your password has been updated.</div>@endif<form method="POST" action="{{ route('password.update') }}" class="mt-5 space-y-4">@csrf @method('put')<div><x-input-label for="current_password" value="Current password" /><x-text-input id="current_password" type="password" name="current_password" autocomplete="current-password" /><x-input-error :messages="$errors->getBag('updatePassword')->get('current_password')" /></div><div><x-input-label for="password" value="New password" /><x-text-input id="password" type="password" name="password" autocomplete="new-password" /><x-input-error :messages="$errors->getBag('updatePassword')->get('password')" /></div><div><x-input-label for="password_confirmation" value="Confirm new password" /><x-text-input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" /><x-input-error :messages="$errors->getBag('updatePassword')->get('password_confirmation')" /></div><x-button type="submit" variant="secondary" class="w-full">Update password</x-button></form></x-card></div>
        </div>
    </div>
</x-layouts.app>
