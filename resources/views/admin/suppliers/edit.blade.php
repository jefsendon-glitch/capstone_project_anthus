@php
    $nameWords = preg_split('/\s+/', trim($supplier->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $supplierNameParts = count($nameWords) > 1
        ? [$nameWords[0], implode(' ', array_slice($nameWords, 1, -1)), $nameWords[count($nameWords) - 1]]
        : ['', '', $nameWords[0] ?? ''];
@endphp

<x-layouts.app title="Edit Supplier" :heading="'Edit Supplier'">
    <x-card class="mx-auto max-w-xl">
        <form method="POST" action="{{ route('admin.suppliers.update', $supplier) }}" class="space-y-5">
            @csrf
            @method('put')

            <fieldset>
                <legend class="text-sm font-semibold text-slate-700 dark:text-slate-300">Supplier Name</legend>
                <div class="mt-2 grid gap-3 sm:grid-cols-3">
                    <div><x-input-label for="first_name" value="First Name" /><x-text-input id="first_name" name="first_name" :value="old('first_name', $supplierNameParts[0])" maxlength="100" required autofocus autocomplete="given-name" /><x-input-error :messages="$errors->get('first_name')" /></div>
                    <div><x-input-label for="middle_name" value="Middle Name (Optional)" /><x-text-input id="middle_name" name="middle_name" :value="old('middle_name', $supplierNameParts[1])" maxlength="100" autocomplete="additional-name" /><x-input-error :messages="$errors->get('middle_name')" /></div>
                    <div><x-input-label for="last_name" value="Last Name" /><x-text-input id="last_name" name="last_name" :value="old('last_name', $supplierNameParts[2])" maxlength="100" required autocomplete="family-name" /><x-input-error :messages="$errors->get('last_name')" /></div>
                </div>
            </fieldset>

            <div class="grid grid-cols-2 gap-5">
                <div>
                    <x-input-label for="contact_person" value="Contact person (optional)" />
                    <x-text-input id="contact_person" name="contact_person" :value="old('contact_person', $supplier->contact_person)" />
                    <x-input-error :messages="$errors->get('contact_person')" />
                </div>

                <div>
                    <x-input-label for="phone" value="Phone (optional)" />
                    <x-text-input id="phone" name="phone" :value="old('phone', $supplier->phone)" />
                    <x-input-error :messages="$errors->get('phone')" />
                </div>
            </div>

            <div>
                <x-input-label for="email" value="Email (optional)" />
                <x-text-input id="email" type="email" name="email" :value="old('email', $supplier->email)" />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div>
                <x-input-label for="address" value="Address (optional)" />
                <x-textarea id="address" name="address">{{ old('address', $supplier->address) }}</x-textarea>
                <x-input-error :messages="$errors->get('address')" />
            </div>

            <div>
                <x-input-label for="notes" value="Notes (optional)" />
                <x-textarea id="notes" name="notes">{{ old('notes', $supplier->notes) }}</x-textarea>
                <x-input-error :messages="$errors->get('notes')" />
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier->is_active)) class="rounded border-slate-300 text-primary-600 focus:ring-primary-600">
                Active
            </label>

            <div class="flex justify-end gap-3">
                <x-button as="a" href="{{ route('admin.suppliers.index') }}" variant="secondary">Cancel</x-button>
                <x-button variant="info" type="submit">Update Supplier</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
