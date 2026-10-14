<x-layouts.app title="Supplies Usage" :heading="'Supplies Usage'">
    <p class="mb-5 text-sm text-slate-500">Record supplies consumed for each operation. Saving automatically deducts quantities from Procurement Supplies.</p>
    <x-card>
        <h2 class="mb-4 text-lg font-bold">Record Activities</h2>
        @if($errors->any())
            <ul class="mb-4 text-sm text-red-600">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        @endif
        @if($supplies->isEmpty())
            <p class="text-sm text-slate-500">Add supplies in Procurement before recording usage.</p>
        @else
            <form method="POST" action="{{ route('admin.inventory.supply-usages.store') }}" class="space-y-4"
                x-data="{ activity: {{ \Illuminate\Support\Js::from(old('activity', '')) }}, items: {{ \Illuminate\Support\Js::from(array_values(old('items', [['consumable_id' => '', 'quantity' => '']]))) }} }">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="activity" value="Activities" />
                        <x-select id="activity" name="activity" x-model="activity" required>
                            <option value="">Select activity</option>
                            @foreach($activities as $activity)
                                <option value="{{ $activity }}" @selected(old('activity') === $activity)>{{ $activity }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <div><x-input-label for="operation_date" value="Operation Date" /><x-text-input type="date" id="operation_date" name="operation_date" :value="old('operation_date', now()->toDateString())" :max="now()->toDateString()" required /></div>
                </div>
                <div x-show="activity === 'Other'" x-cloak>
                    <x-input-label for="other_operation" value="Specify Operation" />
                    <x-text-input id="other_operation" name="other_operation" :value="old('other_operation')" maxlength="255" placeholder="Type the operation" x-bind:required="activity === 'Other'" x-bind:disabled="activity !== 'Other'" />
                </div>
                <template x-for="(item, index) in items" :key="index">
                    <div class="grid items-end gap-3 sm:grid-cols-[1fr_160px_auto]">
                        <div><label class="text-sm font-medium">Supply / Available Stock</label>
                            <x-select x-bind:name="'items[' + index + '][consumable_id]'" x-model="item.consumable_id" required>
                                <option value="">Select supply</option>
                                @foreach($supplies as $supply)<option value="{{ $supply->id }}">{{ $supply->name }} — {{ $supply->quantity }} {{ $supply->unit }} available</option>@endforeach
                            </x-select>
                        </div>
                        <div><label class="text-sm font-medium">Quantity Used</label><x-text-input type="number" x-bind:name="'items[' + index + '][quantity]'" x-model="item.quantity" min="1" max="99999999" step="1" required /></div>
                        <x-button type="button" variant="secondary" x-on:click="items.splice(index, 1)" x-bind:disabled="items.length === 1">Remove</x-button>
                    </div>
                </template>
                <x-button type="button" variant="secondary" x-on:click="items.push({ consumable_id: '', quantity: '' })" x-bind:disabled="items.length >= 100">Add Supply</x-button>
                <div><x-input-label for="notes" value="Notes (optional)" /><x-textarea id="notes" name="notes" maxlength="1000">{{ old('notes') }}</x-textarea></div>
                <x-button type="submit">Save Usage &amp; Deduct Stock</x-button>
            </form>
        @endif
    </x-card>
    <x-card class="mt-6">
        <h2 class="mb-4 text-lg font-bold">Operation Usage History</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead><tr class="border-b text-slate-500"><th class="p-3">Date / Record</th><th class="p-3">Operation</th><th class="p-3">Supplies Used</th><th class="p-3">Recorded By</th><th class="p-3">Notes</th></tr></thead>
                <tbody>
                    @forelse($usages as $usage)
                        <tr class="border-b border-slate-100 dark:border-white/10">
                            <td class="p-3 whitespace-nowrap">{{ $usage->operation_date->format('M d, Y') }}<span class="block text-xs text-slate-500">#{{ $usage->id }}</span></td>
                            <td class="p-3">{{ $usage->operation }}</td>
                            <td class="p-3">@foreach($usage->items as $item)<div>{{ $item['name'] }}: {{ number_format((float) $item['quantity'], 2) }} {{ $item['unit'] }}</div>@endforeach</td>
                            <td class="p-3">{{ $usage->recordedBy?->name ?? 'Deleted user' }}</td>
                            <td class="p-3">{{ $usage->notes ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-6 text-center text-slate-500">No operation supplies recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $usages->links() }}</div>
    </x-card>
</x-layouts.app>
