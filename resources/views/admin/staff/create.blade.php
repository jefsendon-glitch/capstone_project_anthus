<x-layouts.app title="Add Staff / Admin Account" :heading="'Add Staff / Admin Account'">
    <x-card class="mx-auto max-w-xl" x-data="{ role: '{{ old('role', 'staff') }}' }">
        <form method="POST" action="{{ route('admin.staff.store') }}" class="space-y-5">
            @csrf

            <div>
                <x-input-label for="name" value="Full name" />
                <x-text-input id="name" name="name" :value="old('name')" required autofocus />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="email" value="Email address" />
                <x-text-input id="email" type="email" name="email" :value="old('email')" required />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div>
                <x-input-label for="contact_number" value="Contact number" />
                <x-text-input id="contact_number" name="contact_number" :value="old('contact_number')" />
                <x-input-error :messages="$errors->get('contact_number')" />
            </div>

            <div>
                <x-input-label for="password" value="Initial password" />
                <x-text-input id="password" type="password" name="password" required />
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div>
                <x-input-label for="role" value="Account type" />
                <x-select id="role" name="role" x-model="role" required>
                    <option value="staff" @selected(old('role', 'staff') === 'staff')>Staff</option>
                    <option value="admin" @selected(old('role') === 'admin')>Owner / Admin</option>
                </x-select>
                <x-input-error :messages="$errors->get('role')" />
            </div>

            <div>
                <x-input-label for="status" value="Account status" />
                <x-select id="status" name="status" required>
                    <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                </x-select>
                <x-input-error :messages="$errors->get('status')" />
            </div>

            <div x-show="role === 'staff'" x-cloak class="space-y-5 rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                <div>
                    <x-input-label for="employee_id" value="Employee ID" />
                    <x-text-input id="employee_id" name="employee_id" :value="old('employee_id')" />
                    <x-input-error :messages="$errors->get('employee_id')" />
                </div>

                <div>
                    <x-input-label for="position" value="Position" />
                    <x-text-input id="position" name="position" :value="old('position')" placeholder="e.g. Delivery Driver" />
                    <x-input-error :messages="$errors->get('position')" />
                </div>

                <div>
                    <x-input-label for="hire_date" value="Hire date" />
                    <x-text-input id="hire_date" type="date" name="hire_date" :value="old('hire_date')" data-flatpickr />
                    <x-input-error :messages="$errors->get('hire_date')" />
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <x-button as="a" href="{{ route('admin.staff.index') }}" variant="secondary">Cancel</x-button>
                <x-button type="submit">Save Account</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
