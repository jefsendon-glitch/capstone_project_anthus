<x-layouts.app title="Edit Account" :heading="'Edit Account'">
    <x-card class="mx-auto max-w-xl" x-data="{ role: '{{ old('role', $member->hasRole('admin') ? 'admin' : 'staff') }}' }">
        <form method="POST" action="{{ route('admin.staff.update', $member) }}" class="space-y-5">
            @csrf
            @method('put')

            <div>
                <x-input-label for="name" value="Full name" />
                <x-text-input id="name" name="name" :value="old('name', $member->name)" required autofocus />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="email" value="Email address" />
                <x-text-input id="email" type="email" name="email" :value="old('email', $member->email)" required />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div>
                <x-input-label for="contact_number" value="Contact number" />
                <x-text-input id="contact_number" name="contact_number" :value="old('contact_number', $member->contact_number)" />
                <x-input-error :messages="$errors->get('contact_number')" />
            </div>

            <div>
                <x-input-label for="password" value="New password (leave blank to keep current)" />
                <x-text-input id="password" type="password" name="password" />
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div>
                <x-input-label for="role" value="Account type" />
                <x-select id="role" name="role" x-model="role" required :disabled="$member->id === auth()->id()">
                    <option value="staff" @selected(old('role', $member->hasRole('admin') ? 'admin' : 'staff') === 'staff')>Staff</option>
                    <option value="admin" @selected(old('role', $member->hasRole('admin') ? 'admin' : 'staff') === 'admin')>Owner / Admin</option>
                </x-select>
                @if($member->id === auth()->id())
                    <input type="hidden" name="role" value="{{ $member->hasRole('admin') ? 'admin' : 'staff' }}">
                    <p class="mt-1.5 text-xs text-slate-400">You cannot change your own account type.</p>
                @endif
                <x-input-error :messages="$errors->get('role')" />
            </div>

            <div>
                <x-input-label for="status" value="Account status" />
                <x-select id="status" name="status" required>
                    <option value="active" @selected(old('status', $member->status) === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $member->status) === 'inactive')>Inactive</option>
                </x-select>
                <p class="mt-1.5 text-xs text-slate-400">Inactive accounts cannot be used for day-to-day staff access.</p>
                <x-input-error :messages="$errors->get('status')" />
            </div>

            <div x-show="role === 'staff'" x-cloak class="space-y-5 rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                <div>
                    <x-input-label for="employee_id" value="Employee ID" />
                    <x-text-input id="employee_id" name="employee_id" :value="old('employee_id', $member->staff?->employee_id)" />
                    <x-input-error :messages="$errors->get('employee_id')" />
                </div>

                <div>
                    <x-input-label for="position" value="Position" />
                    <x-text-input id="position" name="position" :value="old('position', $member->staff?->position)" />
                    <x-input-error :messages="$errors->get('position')" />
                </div>

                <div>
                    <x-input-label for="hire_date" value="Hire date" />
                    <x-text-input id="hire_date" type="date" name="hire_date" :value="old('hire_date', $member->staff?->hire_date?->format('Y-m-d'))" data-flatpickr />
                    <x-input-error :messages="$errors->get('hire_date')" />
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <x-button as="a" href="{{ route('admin.staff.index') }}" variant="secondary">Cancel</x-button>
                <x-button type="submit">Update Account</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
