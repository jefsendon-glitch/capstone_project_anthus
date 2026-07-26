<x-layouts.app title="Add Supplier" :heading="'Add Supplier'">
    <x-card class="mx-auto max-w-xl">
        <form method="POST" action="{{ route('admin.suppliers.store') }}" class="space-y-5">
            @csrf

            <div>
                <x-input-label for="name" value="Supplier name" />
                <x-text-input id="name" name="name" :value="old('name')" required autofocus />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div class="grid grid-cols-2 gap-5">
                <div>
                    <x-input-label for="contact_person" value="Contact person (optional)" />
                    <x-text-input id="contact_person" name="contact_person" :value="old('contact_person')" />
                    <x-input-error :messages="$errors->get('contact_person')" />
                </div>

                <div>
                    <x-input-label for="phone" value="Phone (optional)" />
                    <x-text-input id="phone" name="phone" :value="old('phone')" />
                    <x-input-error :messages="$errors->get('phone')" />
                </div>
            </div>

            <div>
                <x-input-label for="email" value="Email (optional)" />
                <x-text-input id="email" type="email" name="email" :value="old('email')" />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div>
                <x-input-label for="address" value="Address (optional)" />
                <x-textarea id="address" name="address">{{ old('address') }}</x-textarea>
                <x-input-error :messages="$errors->get('address')" />
            </div>

            <div>
                <x-input-label for="notes" value="Notes (optional)" />
                <x-textarea id="notes" name="notes">{{ old('notes') }}</x-textarea>
                <x-input-error :messages="$errors->get('notes')" />
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-primary-600 focus:ring-primary-600">
                Active
            </label>

            <div class="flex justify-end gap-3">
                <x-button as="a" href="{{ route('admin.suppliers.index') }}" variant="secondary">Cancel</x-button>
                <x-button type="submit">Save Supplier</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
