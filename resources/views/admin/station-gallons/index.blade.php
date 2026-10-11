<x-layouts.app title="Gallon Inventory" :heading="'Gallon Inventory'">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Station-Owned Gallon Containers</h2>
        <p class="mt-1 text-sm text-slate-500">Shuanti Water Refilling Station — container inventory, customer borrowing, and movement records.</p>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-card><p class="text-sm text-slate-500">Total Station Gallons</p><p class="text-2xl font-bold">{{ number_format($gallons->sum(fn ($g) => $g->empty + $g->filled + $g->borrowed + $g->damaged + $g->missing)) }}</p></x-card>
        @foreach(['empty', 'filled', 'borrowed', 'returned', 'damaged', 'missing'] as $status)
            <x-card><p class="text-sm text-slate-500">{{ ucfirst($status) }}{{ $status === 'returned' ? ' (All Time)' : '' }}</p><p class="text-2xl font-bold">{{ number_format($gallons->sum($status)) }}</p></x-card>
        @endforeach
    </div>
    <p class="mb-6 text-sm text-slate-500">Returned gallons enter empty or damaged stock. The return total counts all return events, including repeat returns, and is not added to the station total.</p>

    @if($errors->any())
        <div role="alert" class="mb-5 rounded-xl bg-red-50 p-4 text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        <x-card>
            <h3 class="mb-4 text-lg font-semibold">Register Gallon Type</h3>
            <form method="POST" action="{{ route('admin.inventory.gallon-inventory.store') }}" class="space-y-4">
                @csrf
                <div><x-input-label for="gallon-name" value="Gallon name / type" /><x-text-input id="gallon-name" name="name" placeholder="Round gallon" maxlength="255" required /></div>
                <div><x-input-label for="gallon-size" value="Size" /><x-text-input id="gallon-size" name="size" placeholder="5 gallons" maxlength="100" required /></div>
                <div><x-input-label for="opening-quantity" value="Opening empty gallons" /><x-text-input id="opening-quantity" name="quantity" type="number" min="1" max="1000000" required /></div>
                <x-button type="submit">Register Gallons</x-button>
            </form>
        </x-card>
        <x-card>
            <h3 class="mb-4 text-lg font-semibold">Record Gallon Movement</h3>
            @if($gallons->isEmpty())
                <p class="text-sm text-slate-500">Register a gallon type to begin adding, filling, and releasing containers.</p>
            @else
                <form method="POST" action="{{ route('admin.inventory.gallon-inventory.move') }}" class="space-y-4" x-data="{ action: 'add', source: 'empty' }">
                    @csrf
                    <div><x-input-label value="Gallon type" /><x-select name="gallon_id" required>@foreach($gallons as $gallon)<option value="{{ $gallon->id }}">{{ $gallon->name }} — {{ $gallon->size }}</option>@endforeach</x-select></div>
                    <div><x-input-label value="Action" /><x-select name="action" x-model="action"><option value="add">Add empty gallons</option><option value="fill">Fill gallons</option><option value="release">Release to customer</option><option value="return">Return from customer</option><option value="damage">Report damaged</option><option value="missing">Report missing</option></x-select></div>
                    <div x-show="action === 'damage' || action === 'missing'" x-cloak><x-input-label value="Current location / condition" /><x-select name="from_status" x-model="source"><option value="empty">Empty</option><option value="filled">Filled</option><option value="borrowed">Borrowed by customer</option></x-select></div>
                    <div x-show="action === 'release' || action === 'return' || ((action === 'damage' || action === 'missing') && source === 'borrowed')" x-cloak>
                        <x-input-label value="Customer" /><x-select name="customer_id" x-bind:required="action === 'release' || action === 'return' || ((action === 'damage' || action === 'missing') && source === 'borrowed')"><option value="">Select customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}</option>@endforeach</x-select>
                    </div>
                    <div x-show="action === 'return'" x-cloak><x-input-label value="Returned condition" /><x-select name="return_status"><option value="empty">Usable / Empty</option><option value="damaged">Damaged</option></x-select></div>
                    <div><x-input-label value="Quantity" /><x-text-input name="quantity" type="number" min="1" max="1000000" required /></div>
                    <div><x-input-label value="Notes / reason" /><x-textarea name="notes" maxlength="1000" x-bind:required="action === 'damage' || action === 'missing'"></x-textarea></div>
                    <x-button type="submit">Save Movement</x-button>
                </form>
            @endif
        </x-card>
    </div>

    <x-card class="mb-6">
        <h3 class="mb-4 text-lg font-semibold">Container Inventory</h3>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm">
            <thead><tr class="border-b border-slate-200"><th class="p-3">Type / Size</th>@foreach(['empty', 'filled', 'borrowed', 'damaged', 'missing', 'returned'] as $status)<th class="p-3">{{ ucfirst($status) }}{{ $status === 'returned' ? ' (All Time)' : '' }}</th>@endforeach</tr></thead>
            <tbody>@forelse($gallons as $gallon)<tr class="border-b border-slate-100"><td class="p-3 font-medium">{{ $gallon->name }} / {{ $gallon->size }}</td>@foreach(['empty', 'filled', 'borrowed', 'damaged', 'missing', 'returned'] as $status)<td class="p-3">{{ number_format($gallon->$status) }}</td>@endforeach</tr>@empty<tr><td colspan="7" class="p-6 text-slate-500">No station gallons registered yet.</td></tr>@endforelse</tbody>
        </table></div>
    </x-card>

    <x-card class="mb-6">
        <h3 class="mb-4 text-lg font-semibold">Outstanding Customer Gallons</h3>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr><th class="p-3">Customer</th><th class="p-3">Gallon Type</th><th class="p-3">Borrowed</th></tr></thead><tbody>
            @forelse($loans as $loan)<tr class="border-t border-slate-100"><td class="p-3">{{ $loan->customer_name }}</td><td class="p-3">{{ $loan->name }} / {{ $loan->size }}</td><td class="p-3">{{ $loan->quantity }}</td></tr>@empty<tr><td colspan="3" class="p-6 text-slate-500">No outstanding borrowed gallons.</td></tr>@endforelse
        </tbody></table></div>
    </x-card>

    <x-card>
        <h3 class="mb-4 text-lg font-semibold">Gallon Movement History</h3>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead><tr>@foreach(['Date', 'Gallon', 'Action', 'Movement', 'Quantity', 'Customer', 'Recorded By', 'Notes'] as $label)<th class="p-3">{{ $label }}</th>@endforeach</tr></thead><tbody>
            @forelse($movements as $movement)<tr class="border-t border-slate-100"><td class="whitespace-nowrap p-3">{{ $movement->created_at }}</td><td class="p-3">{{ $movement->gallon_name }}</td><td class="p-3">{{ ucfirst($movement->action) }}</td><td class="p-3">{{ $movement->from_status ?? 'New' }} → {{ $movement->to_status }}</td><td class="p-3">{{ $movement->quantity }}</td><td class="p-3">{{ $movement->customer_name ?? '—' }}</td><td class="p-3">{{ $movement->recorded_by_name }}</td><td class="p-3">{{ $movement->notes }}</td></tr>@empty<tr><td colspan="8" class="p-6 text-slate-500">No movements recorded yet.</td></tr>@endforelse
        </tbody></table></div>
        <div class="mt-4">{{ $movements->links() }}</div>
    </x-card>
</x-layouts.app>
