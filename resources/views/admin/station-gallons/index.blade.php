<x-layouts.app title="Gallon Inventory" :heading="'Gallon Inventory'">
    <div class="mb-3">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Station-Owned Gallon Containers</h2>
        <p class="mt-1 text-sm text-slate-500">Shuanti Water Refilling Station — container inventory, customer borrowing, and movement records.</p>
    </div>

    <div class="mb-3 grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-7">
        <x-card padding="px-3 py-2"><p class="text-xs text-slate-500">Total Gallons</p><p class="text-xl font-semibold tabular-nums">{{ number_format($gallons->sum(fn ($g) => $g->empty + $g->filled + $g->borrowed + $g->damaged + $g->missing)) }}</p></x-card>
        @foreach(['empty', 'filled', 'borrowed', 'returned', 'damaged', 'missing'] as $status)
            <x-card padding="px-3 py-2"><p class="text-xs text-slate-500">{{ ucfirst($status) }}{{ $status === 'returned' ? ' (All Time)' : '' }}</p><p class="text-xl font-semibold tabular-nums">{{ number_format($gallons->sum($status)) }}</p></x-card>
        @endforeach
    </div>
    <p class="mb-3 text-xs text-slate-500">Returns are cumulative; returned containers are already included in empty or damaged stock.</p>

    @if($errors->any())
        <div role="alert" class="mb-5 rounded-xl bg-red-50 p-4 text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div class="mb-3 grid items-start gap-3 lg:grid-cols-2">
        <x-card padding="p-3">
            <details @if($gallons->isEmpty() || ($errors->any() && !old('action') && !old('edit_gallon_id'))) open @endif><summary class="cursor-pointer text-sm font-semibold">Register Gallon Type</summary>
            <form method="POST" action="{{ route('admin.inventory.gallon-inventory.store') }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                @csrf
                <div><x-input-label for="gallon-name" value="Gallon name / type" /><x-text-input id="gallon-name" name="name" placeholder="Round gallon" maxlength="255" required /></div>
                <div><x-input-label for="gallon-size" value="Size" /><x-text-input id="gallon-size" name="size" placeholder="5 gallons" maxlength="100" required /></div>
                <div><x-input-label for="opening-quantity" value="Opening empty gallons" /><x-text-input id="opening-quantity" name="quantity" type="number" min="1" max="1000000" required /></div>
                <x-button type="submit">Register Gallons</x-button>
            </form>
        </x-card>
        <x-card padding="p-3">
            <details @if($errors->any() && old('action')) open @endif><summary class="cursor-pointer text-sm font-semibold">Record Gallon Movement</summary>
            @if($gallons->isEmpty())
                <p class="text-sm text-slate-500">Register a gallon type to begin adding, filling, and releasing containers.</p>
            @else
                <form method="POST" action="{{ route('admin.inventory.gallon-inventory.move') }}" class="mt-3 grid gap-3 sm:grid-cols-2" x-data="{ action: 'add', source: 'empty' }">
                    @csrf
                    <div><x-input-label value="Gallon type" /><x-select name="gallon_id" required>@foreach($gallons as $gallon)<option value="{{ $gallon->id }}">{{ $gallon->name }} — {{ $gallon->size }}</option>@endforeach</x-select></div>
                    <div><x-input-label value="Action" /><x-select name="action" x-model="action"><option value="add">Add empty gallons</option><option value="fill">Fill gallons</option><option value="release">Release to customer</option><option value="return">Return from customer</option><option value="damage">Report damaged</option><option value="missing">Report missing</option></x-select></div>
                    <div x-show="action === 'damage' || action === 'missing'" x-cloak><x-input-label value="Current location / condition" /><x-select name="from_status" x-model="source"><option value="empty">Empty</option><option value="filled">Filled</option><option value="borrowed">Borrowed by customer</option></x-select></div>
                    <div x-show="action === 'release' || action === 'return' || ((action === 'damage' || action === 'missing') && source === 'borrowed')" x-cloak>
                        <x-input-label value="Customer" /><x-select name="customer_id" x-bind:required="action === 'release' || action === 'return' || ((action === 'damage' || action === 'missing') && source === 'borrowed')"><option value="">Select customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}</option>@endforeach</x-select>
                    </div>
                    <div x-show="action === 'return'" x-cloak><x-input-label value="Returned condition" /><x-select name="return_status"><option value="empty">Usable / Empty</option><option value="damaged">Damaged</option></x-select></div>
                    <div><x-input-label value="Quantity" /><x-text-input name="quantity" type="number" min="1" max="1000000" required /></div>
                    <div><x-input-label value="Notes / reason" /><x-textarea name="notes" rows="2" maxlength="1000" x-bind:required="action === 'damage' || action === 'missing'"></x-textarea></div>
                    <x-button type="submit">Save Movement</x-button>
                </form>
            @endif
        </x-card>
    </div>

    <x-card padding="p-3" class="mb-3">
        <h3 class="mb-2 text-sm font-semibold">Container Inventory</h3>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm">
            <thead><tr class="border-b border-slate-200"><th class="px-3 py-2">Type / Size</th>@foreach(['empty', 'filled', 'borrowed', 'damaged', 'missing', 'returned'] as $status)<th class="px-3 py-2">{{ ucfirst($status) }}{{ $status === 'returned' ? ' (All Time)' : '' }}</th>@endforeach<th class="px-3 py-2">Actions</th></tr></thead>
            <tbody>
                @forelse($gallons as $gallon)
                    <tr class="border-b border-slate-100">
                        <td class="px-3 py-2 font-medium">{{ $gallon->name }} / {{ $gallon->size }}</td>
                        @foreach(['empty', 'filled', 'borrowed', 'damaged', 'missing', 'returned'] as $status)<td class="px-3 py-2">{{ number_format($gallon->$status) }}</td>@endforeach
                        <td class="px-3 py-2 align-top">
                            <details @if((string) old('edit_gallon_id') === (string) $gallon->id) open @endif>
                                <summary class="cursor-pointer font-semibold text-primary-600">Edit</summary>
                                <form method="POST" action="{{ route('admin.inventory.gallon-inventory.update', $gallon->id) }}" class="mt-3 min-w-56 space-y-3">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="edit_gallon_id" value="{{ $gallon->id }}">
                                    <div>
                                        <x-input-label for="edit-name-{{ $gallon->id }}" value="Gallon name / type" />
                                        <x-text-input id="edit-name-{{ $gallon->id }}" name="name" :value="(string) old('edit_gallon_id') === (string) $gallon->id ? old('name', $gallon->name) : $gallon->name" maxlength="255" required />
                                    </div>
                                    <div>
                                        <x-input-label for="edit-size-{{ $gallon->id }}" value="Size" />
                                        <x-text-input id="edit-size-{{ $gallon->id }}" name="size" :value="(string) old('edit_gallon_id') === (string) $gallon->id ? old('size', $gallon->size) : $gallon->size" maxlength="100" required />
                                    </div>
                                    <div>
                                        <x-input-label for="quantity-action-{{ $gallon->id }}" value="Adjust quantity" />
                                        <x-select id="quantity-action-{{ $gallon->id }}" name="quantity_action">
                                            @foreach(['' => 'Keep current quantity', 'increase' => 'Increase empty gallons', 'decrease' => 'Decrease empty gallons'] as $value => $label)
                                                <option value="{{ $value }}" @selected((string) old('edit_gallon_id') === (string) $gallon->id && old('quantity_action', '') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </x-select>
                                    </div>
                                    <div>
                                        <x-input-label for="adjustment-quantity-{{ $gallon->id }}" value="Quantity to increase / decrease" />
                                        <x-text-input id="adjustment-quantity-{{ $gallon->id }}" name="adjustment_quantity" type="number" min="1" max="1000000" :value="(string) old('edit_gallon_id') === (string) $gallon->id ? old('adjustment_quantity') : ''" />
                                        <p class="mt-1 text-xs text-slate-500">{{ number_format($gallon->empty) }} empty gallons available. Adjustments also update Total Gallons.</p>
                                    </div>
                                    <div>
                                        <x-input-label for="adjustment-notes-{{ $gallon->id }}" value="Adjustment notes / reason" />
                                        <x-textarea id="adjustment-notes-{{ $gallon->id }}" name="adjustment_notes" rows="2" maxlength="1000">{{ (string) old('edit_gallon_id') === (string) $gallon->id ? old('adjustment_notes') : '' }}</x-textarea>
                                    </div>
                                    <x-button type="submit">Save Changes</x-button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="p-3 text-slate-500">No station gallons registered yet.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-card>

    <x-card padding="p-3" class="mb-3">
        <details><summary class="mb-2 cursor-pointer text-sm font-semibold">Outstanding Customer Gallons <span class="ml-1 text-xs font-normal text-slate-500">({{ $loans->count() }})</span></summary>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr><th class="px-3 py-2">Customer</th><th class="px-3 py-2">Gallon Type</th><th class="px-3 py-2">Borrowed</th></tr></thead><tbody>
            @forelse($loans as $loan)<tr class="border-t border-slate-100"><td class="px-3 py-2">{{ $loan->customer_name }}</td><td class="px-3 py-2">{{ $loan->name }} / {{ $loan->size }}</td><td class="px-3 py-2">{{ $loan->quantity }}</td></tr>@empty<tr><td colspan="3" class="p-3 text-slate-500">No outstanding borrowed gallons.</td></tr>@endforelse
        </tbody></table></div>
    </x-card>

    <x-card padding="p-3">
        <details @if(request()->has('page')) open @endif><summary class="mb-2 cursor-pointer text-sm font-semibold">Gallon Movement History <span class="ml-1 text-xs font-normal text-slate-500">({{ $movements->total() }})</span></summary>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr>@foreach(['Date', 'Gallon', 'Action', 'Movement', 'Quantity', 'Customer', 'Recorded By', 'Notes'] as $label)<th class="px-3 py-2">{{ $label }}</th>@endforeach</tr></thead><tbody>
            @forelse($movements as $movement)<tr class="border-t border-slate-100"><td class="whitespace-nowrap px-3 py-2">{{ $movement->created_at }}</td><td class="px-3 py-2">{{ $movement->gallon_name }}</td><td class="px-3 py-2">{{ ucfirst($movement->action) }}</td><td class="px-3 py-2">{{ $movement->from_status ?? 'New' }} → {{ $movement->to_status }}</td><td class="px-3 py-2">{{ $movement->quantity }}</td><td class="px-3 py-2">{{ $movement->customer_name ?? '—' }}</td><td class="px-3 py-2">{{ $movement->recorded_by_name }}</td><td class="px-3 py-2">{{ $movement->notes }}</td></tr>@empty<tr><td colspan="8" class="p-3 text-slate-500">No movements recorded yet.</td></tr>@endforelse
        </tbody></table></div>
        <div class="mt-3">{{ $movements->links() }}</div></details>
    </x-card>
</x-layouts.app>
