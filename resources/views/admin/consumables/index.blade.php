<x-layouts.app title="Supply Inventory" :heading="'Supply Inventory'">
    <div class="mb-6 flex justify-end">
        @can('create', \App\Models\Consumable::class)
            <x-button as="a" href="{{ route('admin.consumables.create') }}">
                <x-icon name="plus" class="size-4" /> Add Supply
            </x-button>
        @endcan
    </div>

    <x-card padding="p-0">
        @if($consumables->isEmpty())
            <div class="p-6">
                <x-empty-state icon="archive" title="No supplies yet" description="Track bottle caps, seals, labels, plastic bags, water filters, UV lamps, and cleaning supplies here." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Category</th>
                            <th class="px-6 py-3">Quantity</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($consumables as $consumable)
                            <tr x-data="{ open: false }">
                                <td class="px-6 py-3 text-sm font-medium text-slate-900 dark:text-white">{{ $consumable->name }}</td>
                                <td class="px-6 py-3 text-sm text-slate-500 dark:text-slate-400">{{ ucfirst(str_replace('_', ' ', $consumable->category)) }}</td>
                                <td class="px-6 py-3 font-mono text-sm text-slate-600 dark:text-slate-300">{{ rtrim(rtrim($consumable->quantity, '0'), '.') }} {{ $consumable->unit }}</td>
                                <td class="px-6 py-3">
                                    <x-badge :color="$consumable->is_low_stock ? 'danger' : 'success'">{{ $consumable->is_low_stock ? 'Low Stock' : 'Healthy' }}</x-badge>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        @can('restock', \App\Models\Consumable::class)
                                            <button type="button" x-on:click="open = true" class="text-slate-500 hover:text-primary-600" title="Add stock">
                                                <x-icon name="plus" class="size-4" />
                                            </button>
                                        @endcan
                                        @can('update', $consumable)
                                            <x-stock-action-modal
                                                :action-url="route('admin.consumables.stock.adjust', $consumable)"
                                                :title="'Adjust Stock — '.$consumable->name"
                                                mode="adjust"
                                                trigger-icon="swap"
                                            />
                                        @endcan
                                        @can('update', $consumable)
                                            <a href="{{ route('admin.consumables.edit', $consumable) }}" class="text-slate-500 hover:text-primary-600">
                                                <x-icon name="pencil" class="size-4" />
                                            </a>
                                        @endcan
                                        @can('delete', $consumable)
                                            <form method="POST" action="{{ route('admin.consumables.destroy', $consumable) }}" onsubmit="return confirm('Delete this consumable?')">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="text-slate-500 hover:text-danger-600">
                                                    <x-icon name="trash" class="size-4" />
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                                <template x-teleport="body">
                                    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                        <div class="fixed inset-0 bg-slate-900/50" x-on:click="open = false"></div>
                                        <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900">
                                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Restock {{ $consumable->name }}</h3>
                                            <form method="POST" action="{{ route('admin.consumables.restock', $consumable) }}" class="mt-4 space-y-3">
                                                @csrf
                                                <div>
                                                    <x-input-label value="Quantity to add" />
                                                    <x-text-input type="number" step="0.01" min="0.01" name="quantity_added" required />
                                                </div>
                                                <div>
                                                    <x-input-label value="Cost (optional)" />
                                                    <x-text-input type="number" step="0.01" min="0" name="cost" />
                                                </div>
                                                <x-button type="submit" class="w-full">Add Stock</x-button>
                                            </form>
                                        </div>
                                    </div>
                                </template>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-4 dark:border-white/5">
                {{ $consumables->links() }}
            </div>
        @endif
    </x-card>
</x-layouts.app>
