<x-layouts.app title="Gallon Inventory" :heading="'Gallon Inventory'">
    <x-card padding="p-0">
        @if($products->isEmpty())
            <div class="p-6">
                <x-empty-state icon="droplet" title="No gallon containers yet" description="Add a product in the 'Container' category to start tracking gallon stock." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-6 py-3">Container</th>
                            <th class="px-4 py-3 text-center">Filled</th>
                            <th class="px-4 py-3 text-center">Empty</th>
                            <th class="px-4 py-3 text-center">Customer-Owned</th>
                            <th class="px-4 py-3 text-center">Company-Owned</th>
                            <th class="px-4 py-3 text-center">Returned</th>
                            <th class="px-4 py-3 text-center">Damaged</th>
                            <th class="px-4 py-3 text-center">Missing</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($products as $product)
                            <tr x-data="{ addOpen: false, transferOpen: false }">
                                <td class="px-6 py-3 text-sm font-medium text-slate-900 dark:text-white">{{ $product->name }} ({{ $product->size }})</td>
                                <td class="px-4 py-3 text-center"><x-badge color="success">{{ $product->statusCounts['filled'] }}</x-badge></td>
                                <td class="px-4 py-3 text-center"><x-badge color="slate">{{ $product->statusCounts['empty'] }}</x-badge></td>
                                <td class="px-4 py-3 text-center"><x-badge color="primary">{{ $product->statusCounts['customer_owned'] }}</x-badge></td>
                                <td class="px-4 py-3 text-center"><x-badge color="secondary">{{ $product->statusCounts['company_owned'] }}</x-badge></td>
                                <td class="px-4 py-3 text-center"><x-badge color="delivery">{{ $product->statusCounts['returned'] }}</x-badge></td>
                                <td class="px-4 py-3 text-center"><x-badge color="warning">{{ $product->statusCounts['damaged'] }}</x-badge></td>
                                <td class="px-4 py-3 text-center"><x-badge color="danger">{{ $product->statusCounts['missing'] }}</x-badge></td>
                                <td class="px-6 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        @can('addStock', \App\Models\GallonStock::class)
                                            <button type="button" x-on:click="addOpen = true" class="text-slate-500 hover:text-primary-600" title="Add stock">
                                                <x-icon name="plus" class="size-4" />
                                            </button>
                                        @endcan
                                        @can('adjust', \App\Models\GallonStock::class)
                                            <button type="button" x-on:click="transferOpen = true" class="text-slate-500 hover:text-primary-600" title="Transfer / adjust">
                                                <x-icon name="swap" class="size-4" />
                                            </button>
                                        @endcan
                                    </div>
                                </td>

                                <template x-teleport="body">
                                    <div x-show="addOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                        <div class="fixed inset-0 bg-slate-900/50" x-on:click="addOpen = false"></div>
                                        <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900">
                                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Add Gallon Stock — {{ $product->name }}</h3>
                                            <form method="POST" action="{{ route('admin.gallon-stocks.add', $product) }}" class="mt-4 space-y-3">
                                                @csrf
                                                <div>
                                                    <x-input-label value="Status" />
                                                    <x-select name="status" required>
                                                        @foreach(\App\Models\GallonStock::STATUSES as $status)
                                                            <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                                        @endforeach
                                                    </x-select>
                                                </div>
                                                <div>
                                                    <x-input-label value="Quantity" />
                                                    <x-text-input type="number" min="1" name="quantity" required />
                                                </div>
                                                <div>
                                                    <x-input-label value="Notes (optional)" />
                                                    <x-textarea name="notes"></x-textarea>
                                                </div>
                                                <x-button type="submit" class="w-full">Add Stock</x-button>
                                            </form>
                                        </div>
                                    </div>
                                </template>

                                <template x-teleport="body">
                                    <div x-show="transferOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                        <div class="fixed inset-0 bg-slate-900/50" x-on:click="transferOpen = false"></div>
                                        <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900">
                                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Transfer Gallons — {{ $product->name }}</h3>
                                            <form method="POST" action="{{ route('admin.gallon-stocks.transfer', $product) }}" class="mt-4 space-y-3">
                                                @csrf
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div>
                                                        <x-input-label value="From" />
                                                        <x-select name="from_status" required>
                                                            @foreach(\App\Models\GallonStock::STATUSES as $status)
                                                                <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                                            @endforeach
                                                        </x-select>
                                                    </div>
                                                    <div>
                                                        <x-input-label value="To" />
                                                        <x-select name="to_status" required>
                                                            @foreach(\App\Models\GallonStock::STATUSES as $status)
                                                                <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                                            @endforeach
                                                        </x-select>
                                                    </div>
                                                </div>
                                                <div>
                                                    <x-input-label value="Quantity" />
                                                    <x-text-input type="number" min="1" name="quantity" required />
                                                </div>
                                                <div>
                                                    <x-input-label value="Reason / notes" />
                                                    <x-textarea name="notes" placeholder="Required when moving to Damaged or Missing"></x-textarea>
                                                </div>
                                                <x-button type="submit" class="w-full">Transfer</x-button>
                                            </form>
                                        </div>
                                    </div>
                                </template>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layouts.app>
