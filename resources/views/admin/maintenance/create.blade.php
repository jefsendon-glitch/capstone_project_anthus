<x-layouts.app title="Add Maintenance Log" :heading="'Add Maintenance Log'">
    <x-card class="mx-auto max-w-xl">
        <form method="POST" action="{{ route('admin.maintenance.store') }}" class="space-y-5">
            @csrf

            <div>
                <x-input-label for="equipment_name" value="Equipment name" />
                <x-text-input id="equipment_name" name="equipment_name" :value="old('equipment_name')" required autofocus />
                <x-input-error :messages="$errors->get('equipment_name')" />
            </div>

            <div>
                <x-input-label for="category" value="Category" />
                <x-select id="category" name="category" required>
                    @foreach(['filter', 'pump', 'uv_lamp', 'ozone', 'ro_membrane', 'other'] as $category)
                        <option value="{{ $category }}" @selected(old('category') === $category)>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('category')" />
            </div>

            <div class="grid grid-cols-2 gap-5">
                <div>
                    <x-input-label for="last_maintenance_date" value="Last maintenance date" />
                    <x-text-input id="last_maintenance_date" type="date" name="last_maintenance_date" :value="old('last_maintenance_date')" data-flatpickr />
                    <x-input-error :messages="$errors->get('last_maintenance_date')" />
                </div>

                <div>
                    <x-input-label for="next_due_date" value="Next due date" />
                    <x-text-input id="next_due_date" type="date" name="next_due_date" :value="old('next_due_date')" required data-flatpickr />
                    <x-input-error :messages="$errors->get('next_due_date')" />
                </div>
            </div>

            <div>
                <x-input-label for="status" value="Status" />
                <x-select id="status" name="status" required>
                    <option value="ok" @selected(old('status', 'ok') === 'ok')>OK</option>
                    <option value="due_soon" @selected(old('status') === 'due_soon')>Due Soon</option>
                    <option value="overdue" @selected(old('status') === 'overdue')>Overdue</option>
                </x-select>
                <x-input-error :messages="$errors->get('status')" />
            </div>

            <div>
                <x-input-label for="notes" value="Notes (optional)" />
                <x-textarea id="notes" name="notes">{{ old('notes') }}</x-textarea>
                <x-input-error :messages="$errors->get('notes')" />
            </div>

            <div class="flex justify-end gap-3">
                <x-button as="a" href="{{ route('admin.maintenance.index') }}" variant="secondary">Cancel</x-button>
                <x-button type="submit">Save Log</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
