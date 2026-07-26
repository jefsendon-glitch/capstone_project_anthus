<x-layouts.app title="Edit Customer" :heading="'Edit Customer'">
    <x-card class="mx-auto max-w-xl">
        <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="space-y-5">
            @csrf
            @method('put')

            <div>
                <x-input-label for="name" value="Full name" />
                <x-text-input id="name" name="name" :value="old('name', $customer->name)" required autofocus />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="email" value="Email address" />
                <x-text-input id="email" type="email" name="email" :value="old('email', $customer->email)" required />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div>
                <x-input-label for="contact_number" value="Contact number" />
                <x-text-input id="contact_number" name="contact_number" :value="old('contact_number', $customer->contact_number)" />
                <x-input-error :messages="$errors->get('contact_number')" />
            </div>

            <div>
                <x-input-label for="address" value="Address" />
                <x-textarea id="address" name="address">{{ old('address', $customer->address) }}</x-textarea>
                <x-input-error :messages="$errors->get('address')" />
            </div>

            <div>
                <x-input-label for="status" value="Status" />
                <x-select id="status" name="status" required>
                    <option value="active" @selected(old('status', $customer->status) === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $customer->status) === 'inactive')>Inactive</option>
                </x-select>
                <x-input-error :messages="$errors->get('status')" />
            </div>

            <div class="flex justify-end gap-3">
                <x-button as="a" href="{{ route('admin.customers.index') }}" variant="secondary">Cancel</x-button>
                <x-button type="submit">Update Customer</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
