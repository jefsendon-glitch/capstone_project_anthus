<form method="GET" class="mb-6 flex flex-wrap items-end gap-4">
    <div>
        <x-input-label for="supplier_id" value="Supplier" />
        <x-select id="supplier_id" name="supplier_id">
            <option value="">All suppliers</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </x-select>
    </div>
    @if($history ?? false)
        <div><x-input-label for="date_from" value="Ordered from" /><x-text-input id="date_from" name="date_from" type="date" :value="request('date_from')" /></div>
        <div><x-input-label for="date_to" value="Ordered to" /><x-text-input id="date_to" name="date_to" type="date" :value="request('date_to')" /></div>
        <div>
            <x-input-label for="status" value="Status" />
            <x-select id="status" name="status">
                <option value="">All statuses</option>
                @foreach(['draft', 'ordered', 'partially_received', 'received', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </x-select>
        </div>
    @endif
    <x-button type="submit">Filter</x-button>
    <x-button as="a" :href="url()->current()" variant="secondary">Reset</x-button>
</form>
