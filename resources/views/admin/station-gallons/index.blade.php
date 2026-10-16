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
    <p class="mb-3 text-xs text-slate-500">Returned is an all-time count. Returned gallons are already included in Empty or Damaged.</p>

    @if($errors->any())
        <div role="alert" class="mb-5 rounded-xl bg-red-50 p-4 text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div class="mb-3 grid items-start gap-3 xl:grid-cols-5 [&_form_input]:!py-2 [&_form_select]:!py-2 [&_form_textarea]:!py-2 [&_form_label]:!text-xs">
        <x-card padding="p-3" class="xl:col-span-2">
            <details @if($gallons->isEmpty() || ($errors->any() && !old('action') && !old('edit_gallon_id'))) open @endif><summary class="cursor-pointer text-sm font-semibold">Register Gallon Type</summary>
            <form method="POST" action="{{ route('admin.inventory.gallon-inventory.store') }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                @csrf
                <div><x-input-label for="gallon-name" value="Gallon Name" /><x-text-input id="gallon-name" name="name" :value="!old('action') && !old('edit_gallon_id') ? old('name') : ''" placeholder="e.g. Station container" maxlength="255" required /></div>
                <div>
                    <x-input-label for="gallon-type" value="Gallon Type" />
                    <x-select id="gallon-type" name="type" required>
                        <option value="" disabled @selected(!old('type'))>Select gallon type</option>
                        <option value="Round Gallon" @selected(old('type') === 'Round Gallon')>Round Gallon</option>
                        <option value="Slim Gallon" @selected(old('type') === 'Slim Gallon')>Slim Gallon</option>
                    </x-select>
                </div>
                <div><x-input-label for="gallon-size" value="Size" /><x-text-input id="gallon-size" name="size" :value="!old('action') && !old('edit_gallon_id') ? old('size') : ''" placeholder="e.g. 5 gallons / 20 liters" maxlength="100" required /></div>
                <div><x-input-label for="opening-quantity" value="Initial Empty Stock" /><x-text-input id="opening-quantity" name="quantity" :value="!old('action') && !old('edit_gallon_id') ? old('quantity') : ''" placeholder="Enter initial empty stock" type="number" step="1" min="1" max="1000000" required /></div>
                <div class="flex justify-end border-t border-slate-100 pt-3 dark:border-white/10 sm:col-span-2"><x-button variant="success" type="submit">Register Gallons</x-button></div>
            </form>
            </details>
        </x-card>
        <x-card padding="p-3" class="xl:col-span-3">
            <details @if($errors->any() && old('action')) open @endif><summary class="cursor-pointer text-sm font-semibold">Record Gallon Movement</summary>
            @if($gallons->isEmpty())
                <p class="text-sm text-slate-500">Register a gallon type to begin adding, filling, and releasing containers.</p>
            @else
                <form method="POST" action="{{ route('admin.inventory.gallon-inventory.move') }}" class="mt-3 grid gap-3 sm:grid-cols-2" x-data="{
                    action: @js(in_array(old('action'), ['add', 'fill', 'release', 'return']) ? old('action') : 'add'),
                    gallonId: @js((string) old('gallon_id', $gallons->first()->id)),
                    customerId: @js((string) old('customer_id', '')),
                    returned: @js(old('action') ? old('quantity', '') : ''),
                    damaged: @js(old('damaged_quantity', 0)),
                    settlement: @js((string) old('settle_return', '1')),
                    loans: @js($loans),
                    get borrowed() { return Number(this.loans.find(loan => String(loan.station_gallon_id) === String(this.gallonId) &amp;&amp; String(loan.customer_id) === String(this.customerId))?.quantity ?? 0); },
                    get shortage() { return Math.max(0, this.borrowed - Number(this.returned || 0)); },
                    get usable() { return Math.max(0, Number(this.returned || 0) - Number(this.damaged || 0)); },
                    get invalidReturn() { return !this.customerId || this.borrowed === 0 || this.returned === '' || Number(this.returned) > this.borrowed || Number(this.returned) &lt; 0 || Number(this.damaged) > Number(this.returned) || Number(this.damaged) &lt; 0 || (Number(this.returned) === 0 &amp;&amp; this.settlement === '0'); }
                }">
                    @csrf
                    <div>
                        <x-input-label for="movement-action" value="Action" />
                        <x-select id="movement-action" name="action" x-model="action">
                            <optgroup label="Station inventory">
                                <option value="add">Add Empty Gallons</option>
                                <option value="fill">Fill Gallons</option>
                            </optgroup>
                            <optgroup label="Customer borrowing and returns">
                                <option value="release">Lend to Customer</option>
                                <option value="return">Return from Customer</option>
                            </optgroup>
                        </x-select>
                        <p class="mt-1 text-xs text-slate-500" x-text="{ add: 'Add newly purchased empty gallons to station stock.', fill: 'Fill empty gallons. Water production is recorded automatically.', release: 'Record filled gallons borrowed by a customer.', return: 'Record returned gallons and select how to handle any shortage.' }[action]"></p>
                    </div>
                    <div><x-input-label for="movement-gallon" value="Gallon" /><x-select id="movement-gallon" name="gallon_id" x-model="gallonId" required>@foreach($gallons as $gallon)<option value="{{ $gallon->id }}" @selected((string) old('gallon_id') === (string) $gallon->id)>{{ $gallon->name }} / {{ $gallon->type ?? 'Unspecified' }} — {{ $gallon->size }}</option>@endforeach</x-select></div>
                    <div class="gallon-customer-search" x-show="action === 'release' || action === 'return'" x-cloak>
                        <x-input-label for="movement-customer" value="Customer" /><x-select id="movement-customer" name="customer_id" x-model="customerId" data-tom-select placeholder="Search customer name..." x-effect="const needsCustomer = action === 'release' || action === 'return'; if ($el.tomselect) { needsCustomer ? $el.tomselect.enable() : $el.tomselect.disable(); }" x-bind:disabled="!(action === 'release' || action === 'return')" x-bind:required="action === 'release' || action === 'return'"><option value="">Select customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>{{ $customer->name }}</option>@endforeach</x-select>
                    </div>
                    <div x-show="action === 'return'" x-cloak class="flex flex-col justify-center rounded-lg bg-slate-50 px-3 py-2 dark:bg-white/5">
                        <p class="text-xs text-slate-500">Borrowed Balance</p>
                        <p class="text-sm font-semibold tabular-nums" x-text="customerId ? borrowed + ' gallons' : 'Select a customer'"></p>
                        <p x-show="customerId &amp;&amp; borrowed === 0" class="mt-1 text-xs text-red-600" role="status">No borrowed gallons for this selection.</p>
                    </div>
                    <div><label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300" for="movement-quantity" x-text="{ add: 'Quantity to Add', fill: 'Quantity to Fill', release: 'Quantity to Lend', return: 'Total Returned' }[action]">Quantity</label><x-text-input id="movement-quantity" name="quantity" x-model="returned" x-bind:max="action === 'return' ? borrowed : 1000000" :value="old('action') ? old('quantity') : ''" type="number" step="1" min="1" x-bind:min="action === 'return' ? 0 : 1" aria-describedby="returned-quantity-help" placeholder="Enter quantity" max="1000000" required /><p id="returned-quantity-help" x-show="action === 'return'" x-cloak class="mt-1 text-xs text-slate-500">Include usable and damaged gallons. Enter 0 if none were returned.</p></div>
                    <div x-show="action === 'return'" x-cloak>
                        <x-input-label for="returned-damaged" value="Damaged Returned" />
                        <x-text-input id="returned-damaged" name="damaged_quantity" x-model="damaged" x-bind:max="Number(returned || 0)" type="number" min="0" step="1" :value="old('damaged_quantity', 0)" x-bind:disabled="action !== 'return'" />
                        <p class="mt-1 text-xs text-slate-500">Included in Total Returned. Enter 0 if none are damaged.</p>
                    </div>
                    <div x-show="action === 'return'" x-cloak class="sm:col-span-2">
                        <x-input-label for="return-settlement" value="Remaining Gallons" />
                        <x-select id="return-settlement" name="settle_return" x-model="settlement" x-bind:disabled="action !== 'return'">
                            <option value="1" @selected(old('settle_return', '1') == '1')>Record shortage as missing</option>
                            <option value="0" @selected(old('settle_return', '1') == '0')>Keep shortage as borrowed</option>
                        </x-select>
                    </div>
                    <div x-show="action === 'return'" x-cloak class="rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-white/10 dark:bg-white/5 sm:col-span-2" aria-live="polite">
                        <p class="mb-2 text-xs font-semibold text-slate-700 dark:text-slate-300">Return Summary</p>
                        <dl class="grid grid-cols-3 gap-2 text-center">
                            <div><dt class="text-xs text-slate-500">Usable</dt><dd class="mt-1 text-base font-semibold tabular-nums" x-text="usable"></dd></div>
                            <div><dt class="text-xs text-slate-500">Damaged</dt><dd class="mt-1 text-base font-semibold tabular-nums" x-text="Number(damaged || 0)"></dd></div>
                            <div><dt class="text-xs text-slate-500" x-text="settlement === '1' ? 'Missing' : 'Still Borrowed'"></dt><dd class="mt-1 text-base font-semibold tabular-nums" x-text="shortage"></dd></div>
                        </dl>
                        <p x-show="Number(returned) > borrowed || Number(damaged) > Number(returned)" class="mt-2 text-xs text-red-600">Returned quantity cannot exceed the borrowed balance. Damaged quantity cannot exceed total returned.</p>
                    </div>
                    <details class="sm:col-span-2" @if(old('notes')) open @endif><summary class="cursor-pointer text-xs font-medium text-slate-600 dark:text-slate-300">Add Notes (Optional)</summary><div class="mt-2"><x-input-label for="movement-notes" value="Notes (Optional)" /><x-textarea id="movement-notes" name="notes" rows="2" maxlength="1000" placeholder="Add a short reason or describe the container condition.">{{ old('notes') }}</x-textarea></div></details>
                    <div class="flex justify-end border-t border-slate-100 pt-3 dark:border-white/10 sm:col-span-2"><x-button variant="success" type="submit" x-bind:disabled="action === 'return' &amp;&amp; invalidReturn"><span x-text="action === 'return' ? 'Save Customer Return' : 'Save Movement'">Save Movement</span></x-button></div>
                </form>
            @endif
            </details>
        </x-card>
    </div>

    <x-card padding="p-3" class="mb-3">
        <h3 class="mb-2 text-sm font-semibold">Container Inventory</h3>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-xs sm:text-sm">
            <thead><tr class="border-b border-slate-200"><th class="px-2 py-2">Gallon name / Size</th><th class="px-2 py-2">Gallon type</th>@foreach(['empty', 'filled', 'borrowed', 'damaged', 'missing', 'returned'] as $status)<th class="px-2 py-2 text-right">{{ ucfirst($status) }}@if($status === 'returned')<span class="block text-[10px] font-normal text-slate-500">All Time</span>@endif</th>@endforeach<th class="px-2 py-2 text-right">Actions</th></tr></thead>
            <tbody x-data="{ editing: @js((string) old('edit_gallon_id', '')) }">
                @forelse($gallons as $gallon)
                    <tr class="border-b border-slate-100">
                        <td class="px-2 py-2"><span class="block font-medium">{{ $gallon->name }}</span><span class="text-xs text-slate-500">{{ $gallon->size }}</span></td>
                        <td class="px-2 py-2">{{ $gallon->type ?? 'Unspecified' }}</td>
                        @foreach(['empty', 'filled', 'borrowed', 'damaged', 'missing', 'returned'] as $status)<td class="px-2 py-2 text-right tabular-nums">{{ number_format($gallon->$status) }}</td>@endforeach
                        <td class="px-2 py-2 text-right">
                            <button type="button" class="rounded-lg bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-800 hover:bg-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:hover:bg-blue-900" x-on:click="editing = editing === '{{ $gallon->id }}' ? '' : '{{ $gallon->id }}'" x-bind:aria-expanded="editing === '{{ $gallon->id }}'" aria-controls="gallon-editor-{{ $gallon->id }}">Edit</button>
                        </td>
                    </tr>
                    <tr id="gallon-editor-{{ $gallon->id }}" x-show="editing === '{{ $gallon->id }}'" x-cloak class="border-b border-slate-100 bg-slate-50/70 dark:bg-white/5">
                        <td colspan="9" class="p-3">
                                <form method="POST" action="{{ route('admin.inventory.gallon-inventory.update', $gallon->id) }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 [&_input]:!py-2 [&_select]:!py-2 [&_textarea]:!py-2 [&_label]:!text-xs">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="edit_gallon_id" value="{{ $gallon->id }}">
                                    <div>
                                        <x-input-label for="edit-name-{{ $gallon->id }}" value="Gallon Name" />
                                        <x-text-input id="edit-name-{{ $gallon->id }}" name="name" :value="(string) old('edit_gallon_id') === (string) $gallon->id ? old('name', $gallon->name) : $gallon->name" maxlength="255" required />
                                    </div>
                                    <div>
                                        <x-input-label for="edit-type-{{ $gallon->id }}" value="Gallon Type" />
                                        @php($editingType = (string) old('edit_gallon_id') === (string) $gallon->id ? old('type', $gallon->type) : $gallon->type)
                                        <x-select id="edit-type-{{ $gallon->id }}" name="type" required>
                                            <option value="" disabled @selected(!$editingType)>Select gallon type</option>
                                            @if($editingType && !in_array($editingType, ['Round Gallon', 'Slim Gallon']))
                                                <option value="{{ $editingType }}" selected>{{ $editingType }}</option>
                                            @endif
                                            <option value="Round Gallon" @selected($editingType === 'Round Gallon')>Round Gallon</option>
                                            <option value="Slim Gallon" @selected($editingType === 'Slim Gallon')>Slim Gallon</option>
                                        </x-select>
                                    </div>
                                    <div>
                                        <x-input-label for="edit-size-{{ $gallon->id }}" value="Size" />
                                        <x-text-input id="edit-size-{{ $gallon->id }}" name="size" :value="(string) old('edit_gallon_id') === (string) $gallon->id ? old('size', $gallon->size) : $gallon->size" maxlength="100" required />
                                    </div>
                                    <div>
                                        <x-input-label for="quantity-action-{{ $gallon->id }}" value="Stock Adjustment" />
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
                                    <div class="sm:col-span-2 lg:col-span-1">
                                        <x-input-label for="adjustment-notes-{{ $gallon->id }}" value="Reason (Optional)" />
                                        <x-textarea id="adjustment-notes-{{ $gallon->id }}" name="adjustment_notes" rows="1" maxlength="1000" placeholder="e.g. Stock count correction or newly purchased gallons">{{ (string) old('edit_gallon_id') === (string) $gallon->id ? old('adjustment_notes') : '' }}</x-textarea>
                                    </div>
                                    <div class="flex flex-wrap items-end justify-between gap-3 border-t border-slate-200 pt-3 dark:border-white/10 sm:col-span-2 lg:col-span-3">
                                        <p class="text-xs text-slate-500">{{ number_format($gallon->empty) }} empty available<br>Adjustments apply to empty stock only.</p>
                                        <div class="flex items-center gap-1">
                                            <x-button type="button" variant="ghost" class="!px-3 !py-1.5 !text-xs" x-on:click="editing = ''">Cancel</x-button>
                                            <x-button variant="success" type="submit" class="!px-3 !py-1.5 !text-xs">Save Changes</x-button>
                                        </div>
                                    </div>
                                </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="p-3 text-slate-500">No station gallons registered yet.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-card>

    <x-card padding="p-3" class="mb-3">
        <details><summary class="mb-2 cursor-pointer text-sm font-semibold">Outstanding Customer Gallons <span class="ml-1 text-xs font-normal text-slate-500">({{ $loans->count() }})</span></summary>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr><th class="px-3 py-2">Customer</th><th class="px-3 py-2">Gallon Type</th><th class="px-3 py-2">Borrowed</th></tr></thead><tbody>
            @forelse($loans as $loan)<tr class="border-t border-slate-100"><td class="px-3 py-2">{{ $loan->customer_name }}</td><td class="px-3 py-2">{{ $loan->name }} / {{ $loan->type ?? 'Unspecified type' }} / {{ $loan->size }}</td><td class="px-3 py-2">{{ $loan->quantity }}</td></tr>@empty<tr><td colspan="3" class="p-3 text-slate-500">No outstanding borrowed gallons.</td></tr>@endforelse
        </tbody></table></div></details>
    </x-card>

    <x-card padding="p-3" class="mb-3">
        <details>
        <summary class="cursor-pointer text-sm font-semibold">Customer Missing &amp; Damaged Return Records <span class="ml-1 text-xs font-normal text-slate-500">({{ $customerIssues->count() }})</span></summary>
        <div class="mt-3">
        <p class="mb-2 text-xs text-slate-500">Shows missing gallons and damaged returns by customer across all months. Missing gallons are not counted as returned. These records are cleared when movement history is reset.</p>
        <div x-data="{ search: '' }">
            <x-input-label for="issue-customer-search" value="Search Customer" />
            <x-text-input id="issue-customer-search" type="search" x-model="search" placeholder="Enter customer name..." class="mb-3" />
            <div class="overflow-x-auto"><table class="min-w-full text-left text-sm">
                <thead><tr>@foreach(['Date', 'Customer', 'Gallon', 'Record', 'Quantity', 'Notes'] as $label)<th class="px-3 py-2">{{ $label }}</th>@endforeach</tr></thead>
                <tbody>
                    @forelse($customerIssues as $issue)
                        <tr class="border-t border-slate-100" data-customer="{{ $issue->customer_name }}" x-show="$el.dataset.customer.toLowerCase().includes(search.toLowerCase())">
                            <td class="whitespace-nowrap px-3 py-2">{{ $issue->created_at }}</td>
                            <td class="px-3 py-2">{{ $issue->customer_name }}</td>
                            <td class="px-3 py-2">{{ $issue->gallon_name }} / {{ $issue->gallon_type ?? 'Unspecified' }} / {{ $issue->gallon_size }}</td>
                            <td class="px-3 py-2">{{ $issue->action === 'missing' ? 'Missing / Not returned' : 'Damaged returned' }}</td>
                            <td class="px-3 py-2">{{ $issue->quantity }}</td>
                            <td class="px-3 py-2">{{ $issue->notes }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-3 text-slate-500">No missing gallons or damaged returns recorded for customers.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
        </div>
        </details>
    </x-card>

    <x-card padding="p-3">
        <details @if(request()->has('page') || request()->has('month')) open @endif><summary class="mb-2 cursor-pointer text-sm font-semibold">Gallon Movement History <span class="ml-1 text-xs font-normal text-slate-500">({{ $movements->total() }})</span></summary>
        <form method="GET" action="{{ route('admin.inventory.gallon-inventory') }}" class="mb-3 flex flex-wrap items-end gap-2">
            <div><x-input-label for="movement-month" value="Month" /><x-text-input id="movement-month" name="month" type="month" :value="$month" class="!py-1.5" /></div>
            <x-button type="submit" class="!px-3 !py-1.5 !text-xs">Filter</x-button>
            <x-button as="a" href="{{ route('admin.inventory.gallon-inventory', ['month' => '']) }}" variant="secondary" class="!px-3 !py-1.5 !text-xs">All Months</x-button>
            <x-button type="submit" formaction="{{ route('admin.inventory.gallon-inventory.report') }}" variant="success" class="!px-3 !py-1.5 !text-xs">Generate Monthly Report</x-button>
        </form>
        <p class="mb-2 text-xs text-slate-500">{{ $month ? 'Showing movements for '.$month.'.' : 'Showing movements for all months.' }} Select a month to generate a printable report.</p>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr>@foreach(['Date', 'Gallon', 'Action', 'Movement', 'Quantity', 'Customer', 'Recorded By', 'Notes'] as $label)<th class="px-3 py-2">{{ $label }}</th>@endforeach</tr></thead><tbody>
            @forelse($movements as $movement)<tr class="border-t border-slate-100"><td class="whitespace-nowrap px-3 py-2">{{ $movement->created_at }}</td><td class="px-3 py-2">{{ $movement->gallon_name }} / {{ $movement->gallon_type ?? 'Unspecified type' }}</td><td class="px-3 py-2">{{ ucfirst($movement->action) }}</td><td class="px-3 py-2">{{ $movement->from_status ?? 'New' }} → {{ $movement->to_status }}</td><td class="px-3 py-2">{{ $movement->quantity }}</td><td class="px-3 py-2">{{ $movement->customer_name ?? '—' }}</td><td class="px-3 py-2">{{ $movement->recorded_by_name }}</td><td class="px-3 py-2">{{ $movement->notes }}</td></tr>@empty<tr><td colspan="8" class="p-3 text-slate-500">No movements recorded yet.</td></tr>@endforelse
        </tbody></table></div>
        <div class="mt-3">{{ $movements->links() }}</div></details>
    </x-card>
</x-layouts.app>
