<x-layouts.app title="Gallon Inventory" :heading="'Gallon Inventory'">
    @push('admin-reset-actions')
    @if(auth()->user()->isAdmin())
        <x-reset-dot :action="route('admin.inventory.gallon-inventory.reset')" title="Reset Gallon Records" description="History only keeps stock and customer loans. Entire inventory deletes all gallon types, quantities, customer loans and movements; totals return to zero. This cannot be undone.">
            <x-input-label for="header-reset-scope" value="Records to reset" />
            <x-select id="header-reset-scope" name="reset_scope" required>
                <option value="history" @selected(old('reset_scope', 'history') === 'history')>Movement history only</option>
                <option value="all" @selected(old('reset_scope') === 'all')>Entire gallon inventory</option>
            </x-select>
            <x-input-error :messages="$errors->get('reset_scope')" />
        </x-reset-dot>
    @endif
    @endpush

    <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
        <div>
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Station-Owned Gallon Containers</h2>
        <p class="mt-1 text-sm text-slate-500">Shuanti Water Refilling Station — container inventory, customer borrowing, and movement records.</p>
        </div>
    </div>

    <div class="mb-3 grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-7">
        <x-stat-card compact label="Total Gallons" :value="number_format($gallons->sum(fn ($g) => $g->empty + $g->filled + $g->borrowed + $g->damaged + $g->missing))" icon="cube" color="primary" />
        @foreach(['empty', 'filled', 'borrowed', 'returned', 'damaged', 'missing'] as $status)
            <x-stat-card compact :label="ucfirst($status).($status === 'returned' ? ' (All Time)' : '')" :value="number_format($gallons->sum($status))" :icon="['empty' => 'archive', 'filled' => 'droplet', 'borrowed' => 'users', 'returned' => 'check-circle', 'damaged' => 'alert-triangle', 'missing' => 'x-circle'][$status]" :color="['empty' => 'secondary', 'filled' => 'success', 'borrowed' => 'delivery', 'returned' => 'primary', 'damaged' => 'warning', 'missing' => 'danger'][$status]" />
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
                <div><x-input-label for="opening-quantity" value="Opening empty gallons" /><x-text-input id="opening-quantity" name="quantity" type="number" step="1" min="1" max="1000000" required /></div>
                <x-button variant="success" type="submit">Register Gallons</x-button>
            </form>
            </details>
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
                    <div><x-input-label value="Quantity" /><x-text-input name="quantity" type="number" step="1" min="1" max="1000000" required /></div>
                    <div><x-input-label value="Notes / reason" /><x-textarea name="notes" rows="2" maxlength="1000" x-bind:required="action === 'damage' || action === 'missing'"></x-textarea></div>
                    <x-button variant="success" type="submit">Save Movement</x-button>
                </form>
            @endif
            </details>
        </x-card>
    </div>

    <x-card padding="p-3" class="mb-3">
        <h3 class="mb-2 text-sm font-semibold">Container Inventory</h3>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-xs sm:text-sm">
            <thead><tr class="border-b border-slate-200"><th class="px-2 py-2">Type / Size</th>@foreach(['empty', 'filled', 'borrowed', 'damaged', 'missing', 'returned'] as $status)<th class="px-2 py-2 text-right">{{ ucfirst($status) }}@if($status === 'returned')<span class="block text-[10px] font-normal text-slate-500">All Time</span>@endif</th>@endforeach<th class="px-2 py-2 text-right">Actions</th></tr></thead>
            <tbody x-data="{ editing: @js((string) old('edit_gallon_id', '')) }">
                @forelse($gallons as $gallon)
                    <tr class="border-b border-slate-100">
                        <td class="px-2 py-2"><span class="block font-medium">{{ $gallon->name }}</span><span class="text-xs text-slate-500">{{ $gallon->size }}</span></td>
                        @foreach(['empty', 'filled', 'borrowed', 'damaged', 'missing', 'returned'] as $status)<td class="px-2 py-2 text-right tabular-nums">{{ number_format($gallon->$status) }}</td>@endforeach
                        <td class="px-2 py-2 text-right">
                            <button type="button" class="rounded-lg bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-800 hover:bg-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:hover:bg-blue-900" x-on:click="editing = editing === '{{ $gallon->id }}' ? '' : '{{ $gallon->id }}'" x-bind:aria-expanded="editing === '{{ $gallon->id }}'" aria-controls="gallon-editor-{{ $gallon->id }}">Edit</button>
                        </td>
                    </tr>
                    <tr id="gallon-editor-{{ $gallon->id }}" x-show="editing === '{{ $gallon->id }}'" x-cloak class="border-b border-slate-100 bg-slate-50/70 dark:bg-white/5">
                        <td colspan="8" class="p-3">
                                <form method="POST" action="{{ route('admin.inventory.gallon-inventory.update', $gallon->id) }}" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4 [&_input]:!py-1.5 [&_select]:!py-1.5 [&_textarea]:!py-1.5 [&_label]:!text-xs">
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
                                        <x-input-label for="adjustment-quantity-{{ $gallon->id }}" value="Quantity" />
                                        <x-text-input id="adjustment-quantity-{{ $gallon->id }}" name="adjustment_quantity" type="number" step="1" min="1" max="1000000" :value="(string) old('edit_gallon_id') === (string) $gallon->id ? old('adjustment_quantity') : ''" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <x-input-label for="adjustment-notes-{{ $gallon->id }}" value="Adjustment notes / reason" />
                                        <x-textarea id="adjustment-notes-{{ $gallon->id }}" name="adjustment_notes" rows="1" maxlength="1000">{{ (string) old('edit_gallon_id') === (string) $gallon->id ? old('adjustment_notes') : '' }}</x-textarea>
                                    </div>
                                    <div class="flex flex-wrap items-end justify-between gap-2 sm:col-span-2">
                                        <p class="text-xs text-slate-500">{{ number_format($gallon->empty) }} empty available<br>Adjustments update Total Gallons.</p>
                                        <div class="flex items-center gap-1">
                                            <x-button type="button" variant="ghost" class="!px-3 !py-1.5 !text-xs" x-on:click="editing = ''">Cancel</x-button>
                                            <x-button variant="success" type="submit" class="!px-3 !py-1.5 !text-xs">Save Changes</x-button>
                                        </div>
                                    </div>
                                </form>
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
        </tbody></table></div></details>
    </x-card>

    <x-card padding="p-3">
        <details @if(request()->has('page') || request()->has('month')) open @endif><summary class="mb-2 cursor-pointer text-sm font-semibold">Gallon Movement History <span class="ml-1 text-xs font-normal text-slate-500">({{ $movements->total() }})</span></summary>
        <form method="GET" action="{{ route('admin.inventory.gallon-inventory') }}" class="mb-3 flex flex-wrap items-end gap-2">
            <div><x-input-label for="movement-month" value="Movement month" /><x-text-input id="movement-month" name="month" type="month" :value="$month" class="!py-1.5" /></div>
            <x-button type="submit" class="!px-3 !py-1.5 !text-xs">Filter</x-button>
            <x-button as="a" href="{{ route('admin.inventory.gallon-inventory', ['month' => '']) }}" variant="secondary" class="!px-3 !py-1.5 !text-xs">All Months</x-button>
            <x-button type="submit" formaction="{{ route('admin.inventory.gallon-inventory.report') }}" variant="success" class="!px-3 !py-1.5 !text-xs">Generate Monthly Report</x-button>
        </form>
        <p class="mb-2 text-xs text-slate-500">{{ $month ? 'Showing movements for '.$month.'.' : 'Showing movements for all months.' }} Select a month to generate a printable report.</p>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr>@foreach(['Date', 'Gallon', 'Action', 'Movement', 'Quantity', 'Customer', 'Recorded By', 'Notes'] as $label)<th class="px-3 py-2">{{ $label }}</th>@endforeach</tr></thead><tbody>
            @forelse($movements as $movement)<tr class="border-t border-slate-100"><td class="whitespace-nowrap px-3 py-2">{{ $movement->created_at }}</td><td class="px-3 py-2">{{ $movement->gallon_name }}</td><td class="px-3 py-2">{{ ucfirst($movement->action) }}</td><td class="px-3 py-2">{{ $movement->from_status ?? 'New' }} → {{ $movement->to_status }}</td><td class="px-3 py-2">{{ $movement->quantity }}</td><td class="px-3 py-2">{{ $movement->customer_name ?? '—' }}</td><td class="px-3 py-2">{{ $movement->recorded_by_name }}</td><td class="px-3 py-2">{{ $movement->notes }}</td></tr>@empty<tr><td colspan="8" class="p-3 text-slate-500">No movements recorded yet.</td></tr>@endforelse
        </tbody></table></div>
        <div class="mt-3">{{ $movements->links() }}</div></details>
    </x-card>
</x-layouts.app>
