<x-layouts.app title="Gallon Inventory" :heading="'Gallon Inventory'">
    <style>@media print { @page { size: letter portrait; margin: 10mm; } .no-print { display:none!important; } main { padding:0!important; } th,td{padding:4px!important;font-size:8px!important;} }</style>
    <div class="no-print mb-6 flex flex-wrap justify-end gap-3">
        <x-button as="a" href="{{ route('admin.products.create', ['category' => 'container']) }}" variant="secondary"><x-icon name="plus" class="size-4" /> Add Gallon Type</x-button>
        <x-button as="a" href="{{ route('admin.stock-movements.index', ['item_type' => 'gallon_stock']) }}" variant="secondary"><x-icon name="swap" class="size-4" /> Movement History</x-button>
        <x-button type="button" onclick="window.print()"><x-icon name="clipboard" class="size-4" /> Print Report</x-button>
    </div>
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach(['total' => 'Total Gallons', 'empty' => 'Empty Gallons', 'filled' => 'Filled Gallons', 'withCustomers' => 'Gallons with Customers', 'damaged' => 'Damaged Gallons', 'missing' => 'Missing Gallons'] as $key => $label)
            <x-card><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($dashboard[$key]) }}</p></x-card>
        @endforeach
    </div>
    <x-card class="no-print mb-6">
        <form method="GET" class="grid gap-4 md:grid-cols-4">
            <div><x-input-label value="Search" /><x-text-input name="search" :value="$search" placeholder="ID, type, or size" /></div>
            <div><x-input-label value="Gallon type" /><x-select name="type"><option value="">All types</option>@foreach($types as $option)<option value="{{ $option }}" @selected($type === $option)>{{ $option }}</option>@endforeach</x-select></div>
            <div><x-input-label value="Stock status" /><x-select name="stock_status"><option value="">All statuses</option>@foreach(['In Stock', 'Low Stock', 'Out of Stock'] as $option)<option value="{{ $option }}" @selected($stockStatus === $option)>{{ $option }}</option>@endforeach</x-select></div>
            <div class="flex items-end gap-3"><x-button as="a" href="{{ route('admin.gallon-stocks.index') }}" variant="secondary">Reset</x-button><x-button type="submit">Filter</x-button></div>
        </form>
    </x-card>
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
                            <th class="px-4 py-3">Gallon ID</th>
                            <th class="px-4 py-3">Gallon Type</th>
                            <th class="px-4 py-3">Size</th>
                            <th class="px-4 py-3 text-center">Total</th>
                            <th class="px-4 py-3 text-center">Stock Status</th>
                            <th class="px-4 py-3 text-center">Filled</th>
                            <th class="px-4 py-3 text-center">Empty</th>
                            <th class="px-4 py-3 text-center">Customer-Owned</th>
                            <th class="px-4 py-3 text-center">Damaged</th>
                            <th class="px-4 py-3 text-center">Missing</th>
                            <th class="px-4 py-3 text-center">Reorder Level</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($products as $product)
                            <tr x-data="{ addOpen: false, transferOpen: false, fillOpen: false, releaseOpen: false, returnOpen: false }">
                                <td class="px-4 py-3 font-mono text-xs text-slate-500">GAL-{{ str_pad((string) $product->id, 4, '0', STR_PAD_LEFT) }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">{{ str($product->name)->before(' ')->title() }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $product->size }}</td>
                                <td class="px-4 py-3 text-center font-mono text-sm font-semibold">{{ $product->statusCounts->sum() }}</td>
                                <td class="px-4 py-3 text-center"><x-badge :color="$product->stock_status === 'In Stock' ? 'success' : ($product->stock_status === 'Low Stock' ? 'warning' : 'danger')">{{ $product->stock_status }}</x-badge></td>
                                <td class="px-4 py-3 text-center"><x-badge color="success">{{ $product->statusCounts['filled'] }}</x-badge></td>
                                <td class="px-4 py-3 text-center"><x-badge color="slate">{{ $product->statusCounts['empty'] }}</x-badge></td>
                                <td class="px-4 py-3 text-center"><x-badge color="primary">{{ $product->statusCounts['customer_owned'] }}</x-badge></td>
                                <td class="px-4 py-3 text-center"><x-badge color="warning">{{ $product->statusCounts['damaged'] }}</x-badge></td>
                                <td class="px-4 py-3 text-center"><x-badge color="danger">{{ $product->statusCounts['missing'] }}</x-badge></td>
                                <td class="px-4 py-3 text-center font-mono text-sm">{{ $product->low_stock_threshold }}</td>
                                <td class="px-6 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        @can('addStock', \App\Models\GallonStock::class)
                                            <button type="button" x-on:click="addOpen = true" class="text-slate-500 hover:text-primary-600" title="Add stock">
                                                <x-icon name="plus" class="size-4" />
                                            </button>
                                        @endcan
                                        @can('adjust', \App\Models\GallonStock::class)
                                            <a href="{{ route('admin.products.edit', $product) }}" class="text-slate-500 hover:text-primary-600" title="Edit gallon type"><x-icon name="pencil" class="size-4" /></a>
                                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Archive this gallon type?')">@csrf @method('DELETE')<button type="submit" class="text-slate-500 hover:text-danger-600" title="Archive gallon type"><x-icon name="archive" class="size-4" /></button></form>
                                            <button type="button" x-on:click="fillOpen = true" class="text-slate-500 hover:text-primary-600" title="Fill gallons"><x-icon name="droplet" class="size-4" /></button>
                                            <button type="button" x-on:click="releaseOpen = true" class="text-slate-500 hover:text-primary-600" title="Release to customer"><x-icon name="truck" class="size-4" /></button>
                                            <button type="button" x-on:click="returnOpen = true" class="text-slate-500 hover:text-primary-600" title="Return from customer"><x-icon name="swap" class="size-4" /></button>
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
                                                <p class="rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-600 dark:bg-white/5 dark:text-slate-300">Newly acquired gallons are added to <strong>Empty Gallons</strong>.</p>
                                                <div>
                                                    <x-input-label value="Quantity" />
                                                    <x-text-input type="number" min="1" name="quantity" required />
                                                </div>
                                                <div>
                                                    <x-input-label value="Adjustment reason" />
                                                    <x-textarea name="notes" required></x-textarea>
                                                </div>
                                                <x-button type="submit" class="w-full">Add Stock</x-button>
                                            </form>
                                        </div>
                                    </div>
                                </template>

                                @foreach(['fill' => ['fillOpen', 'Fill Gallons', 'admin.gallon-stocks.fill', false], 'release' => ['releaseOpen', 'Release Gallons', 'admin.gallon-stocks.release', true], 'return' => ['returnOpen', 'Return Gallons', 'admin.gallon-stocks.return', true]] as [$open, $title, $route, $needsCustomer])
                                    <template x-teleport="body"><div x-show="{{ $open }}" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"><div class="fixed inset-0 bg-slate-900/50" x-on:click="{{ $open }} = false"></div><div class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900"><h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ $title }} — {{ $product->name }}</h3><form method="POST" action="{{ route($route, $product) }}" class="mt-4 space-y-3">@csrf @if($needsCustomer)<div><x-input-label value="Customer"/><x-select name="customer_id" required><option value="">Select customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}</option>@endforeach</x-select></div>@endif @if($route === 'admin.gallon-stocks.return')<div><x-input-label value="Return condition"/><x-select name="return_status" required><option value="empty">Usable / Empty</option><option value="damaged">Damaged</option></x-select></div>@endif<div><x-input-label value="Quantity"/><x-text-input type="number" min="1" name="quantity" required/></div><div><x-input-label value="Notes"/><x-textarea name="notes"></x-textarea></div><x-button type="submit" class="w-full">{{ $title }}</x-button></form></div></div></template>
                                @endforeach

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
                                                    <x-textarea name="notes" placeholder="Required" required></x-textarea>
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
            <div class="border-t border-slate-100 px-6 py-4 dark:border-white/5">{{ $products->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
