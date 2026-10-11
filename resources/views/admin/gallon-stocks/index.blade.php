<x-layouts.app title="Gallon Inventory" :heading="'Gallon Inventory'">
    <style>
        .gallon-records { --gallon-blue: #1684c4; --gallon-green: #16a34a; --gallon-orange: #ea7a16; --gallon-purple: #7c3aed; --gallon-red: #dc2626; }
        .gallon-records .gallon-action { display:inline-flex; align-items:center; gap:.45rem; border-radius:.45rem; padding:.65rem .85rem; color:#fff; font-size:.8rem; font-weight:700; box-shadow:0 2px 4px rgb(15 23 42 / .12); transition:filter .15s ease, transform .15s ease; }
        .gallon-records .gallon-action:hover { filter:brightness(.94); transform:translateY(-1px); }
        .gallon-records .gallon-action--blue { background:var(--gallon-blue); }.gallon-records .gallon-action--green { background:var(--gallon-green); }.gallon-records .gallon-action--orange { background:var(--gallon-orange); }.gallon-records .gallon-action--purple { background:var(--gallon-purple); }.gallon-records .gallon-action--red { background:var(--gallon-red); }.gallon-records .gallon-action--slate { background:#52657b; }
        .gallon-records .gallon-table th { background:#f0f9ff; color:#164e63; white-space:nowrap; }.gallon-records .gallon-table td,.gallon-records .gallon-table th { border-right:1px solid #e0f2fe; }.gallon-records .gallon-table tr:hover td { background:#f8fdff; }
        .gallon-records .row-action { display:inline-flex; align-items:center; justify-content:center; width:2rem; height:2rem; border-radius:.4rem; border:1px solid #dce7f4; box-shadow:0 1px 2px rgb(15 23 42 / .06); }.gallon-records .row-action--blue { background:var(--gallon-blue); color:#fff; border-color:var(--gallon-blue); }.gallon-records .row-action--red { background:var(--gallon-red); color:#fff; border-color:var(--gallon-red); }.gallon-records .row-action--green { background:#dcfce7; color:#15803d; border-color:#bbf7d0; }.gallon-records .row-action--orange { background:#fff7ed; color:#c2410c; border-color:#fed7aa; }.gallon-records .row-action--purple { background:#f3e8ff; color:#7e22ce; border-color:#e9d5ff; }
        @media print { @page { size: letter portrait; margin: 10mm; } .no-print { display:none!important; } main { padding:0!important; } th,td{padding:4px!important;font-size:8px!important;} }
    </style>
    <section class="gallon-records" x-data='{
        quickOpen: false, quickAction: "", quickProduct: "", damageStatus: "damaged",
        fillUrl: @js(route("admin.gallon-stocks.fill", ["product" => "__PRODUCT__"])),
        releaseUrl: @js(route("admin.gallon-stocks.release", ["product" => "__PRODUCT__"])),
        returnUrl: @js(route("admin.gallon-stocks.return", ["product" => "__PRODUCT__"])),
        transferUrl: @js(route("admin.gallon-stocks.transfer", ["product" => "__PRODUCT__"])),
        adjustUrl: @js(route("admin.gallon-stocks.adjust", ["product" => "__PRODUCT__"])),
        openQuick(action) { this.quickAction = action; this.quickProduct = ""; this.quickOpen = true; },
        quickUrl(template) { return template.replace("__PRODUCT__", this.quickProduct); }
    }'>
    <div class="no-print mb-5 flex flex-col gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:bg-slate-900 dark:ring-white/10 lg:flex-row lg:items-center lg:justify-between">
        <div><h2 class="text-xl font-bold text-slate-900 dark:text-white">Gallon Records</h2><p class="mt-1 text-sm text-slate-500">List of gallon types and their current inventory status.</p></div>
        <div class="flex flex-wrap gap-2">
            @can('adjust', \App\Models\GallonStock::class)
            <button type="button" x-on:click="openQuick('add')" class="gallon-action gallon-action--blue"><x-icon name="plus" class="size-4" /> Add Gallon</button>
            <button type="button" x-on:click="openQuick('fill')" class="gallon-action gallon-action--green"><x-icon name="droplet" class="size-4" /> Fill Gallons</button>
            <button type="button" x-on:click="openQuick('release')" class="gallon-action gallon-action--orange"><x-icon name="truck" class="size-4" /> Release</button>
            <button type="button" x-on:click="openQuick('return')" class="gallon-action gallon-action--purple"><x-icon name="swap" class="size-4" /> Return</button>
            <button type="button" x-on:click="openQuick('damage')" class="gallon-action gallon-action--red"><x-icon name="alert-triangle" class="size-4" /> Damaged/Missing</button>
            <button type="button" x-on:click="openQuick('adjust')" class="gallon-action gallon-action--slate"><x-icon name="cog" class="size-4" /> Adjust Stock</button>
            @endcan
        </div>
    </div>

    <template x-teleport="body">
        <div x-show="quickOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4">
            <div class="fixed inset-0 bg-slate-950/55" x-on:click="quickOpen = false"></div>
            <div class="relative my-6 w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900">
                <button type="button" x-on:click="quickOpen = false" class="absolute right-4 top-4 text-slate-400 hover:text-slate-700 dark:hover:text-white"><x-icon name="x" class="size-5" /></button>
                <h3 x-show="quickAction === 'add'" class="text-lg font-bold text-slate-900 dark:text-white">Add Gallon Type</h3>
                <h3 x-show="quickAction === 'fill'" class="text-lg font-bold text-slate-900 dark:text-white">Fill Gallons</h3>
                <h3 x-show="quickAction === 'release'" class="text-lg font-bold text-slate-900 dark:text-white">Release to Customer</h3>
                <h3 x-show="quickAction === 'return'" class="text-lg font-bold text-slate-900 dark:text-white">Return Gallons</h3>
                <h3 x-show="quickAction === 'damage'" class="text-lg font-bold text-slate-900 dark:text-white">Damaged / Missing Gallons</h3>
                <h3 x-show="quickAction === 'adjust'" class="text-lg font-bold text-slate-900 dark:text-white">Adjust Stock</h3>

                <form x-show="quickAction === 'add'" method="POST" action="{{ route('admin.gallon-stocks.store') }}" class="mt-5 space-y-3">@csrf
                    <div><x-input-label value="Gallon Type" /><x-select name="gallon_type" required><option value="Round">Round</option><option value="Slim">Slim</option><option value="Square">Square</option></x-select></div>
                    <div><x-input-label value="Size" /><x-select name="size" required><option value="5 Gallons">5 Gallons</option><option value="10 Liters">10 Liters</option><option value="20 Liters">20 Liters</option></x-select></div>
                    <div><x-input-label value="Initial Quantity" /><x-text-input type="number" min="1" name="quantity" required /></div>
                    <div><x-input-label value="Reorder Level" /><x-text-input type="number" min="0" name="low_stock_threshold" value="10" required /></div>
                    <div><x-input-label value="Remarks" /><x-textarea name="notes"></x-textarea></div><x-button type="submit" class="w-full">Save Gallon Type</x-button>
                </form>

                <form x-show="quickAction === 'fill'" method="POST" x-bind:action="quickUrl(fillUrl)" class="mt-5 space-y-3">@csrf
                    <div><x-input-label value="Gallon Type" /><x-select x-model="quickProduct" required><option value="">Select gallon type</option>@foreach($gallonOptions as $option)<option value="{{ $option['id'] }}">{{ $option['label'] }} — Empty: {{ $option['empty'] }}</option>@endforeach</x-select></div>
                    <div><x-input-label value="Quantity to Fill" /><x-text-input type="number" min="1" name="quantity" required /></div><div><x-input-label value="Remarks" /><x-textarea name="notes"></x-textarea></div><x-button type="submit" class="w-full">Fill Gallons</x-button>
                </form>

                <form x-show="quickAction === 'release'" method="POST" x-bind:action="quickUrl(releaseUrl)" class="mt-5 space-y-3">@csrf
                    <div><x-input-label value="Customer" /><x-select name="customer_id" required><option value="">Select customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}</option>@endforeach</x-select></div>
                    <div><x-input-label value="Gallon Type" /><x-select x-model="quickProduct" required><option value="">Select gallon type</option>@foreach($gallonOptions as $option)<option value="{{ $option['id'] }}">{{ $option['label'] }}</option>@endforeach</x-select></div><div><x-input-label value="Quantity" /><x-text-input type="number" min="1" name="quantity" required /></div><div><x-input-label value="Reference / Remarks" /><x-textarea name="notes"></x-textarea></div><x-button type="submit" class="w-full">Release Gallons</x-button>
                </form>

                <form x-show="quickAction === 'return'" method="POST" x-bind:action="quickUrl(returnUrl)" class="mt-5 space-y-3">@csrf
                    <div><x-input-label value="Customer" /><x-select name="customer_id" required><option value="">Select customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}</option>@endforeach</x-select></div><div><x-input-label value="Gallon Type" /><x-select x-model="quickProduct" required><option value="">Select gallon type</option>@foreach($gallonOptions as $option)<option value="{{ $option['id'] }}">{{ $option['label'] }}</option>@endforeach</x-select></div><div><x-input-label value="Condition" /><x-select name="return_status" required><option value="empty">Usable (Empty)</option><option value="damaged">Damaged</option></x-select></div><div><x-input-label value="Quantity" /><x-text-input type="number" min="1" name="quantity" required /></div><div><x-input-label value="Remarks" /><x-textarea name="notes"></x-textarea></div><x-button type="submit" class="w-full">Return Gallons</x-button>
                </form>

                <form x-show="quickAction === 'damage'" method="POST" x-bind:action="quickUrl(transferUrl)" class="mt-5 space-y-3">@csrf
                    <div><x-input-label value="Gallon Type" /><x-select x-model="quickProduct" required><option value="">Select gallon type</option>@foreach($gallonOptions as $option)<option value="{{ $option['id'] }}">{{ $option['label'] }}</option>@endforeach</x-select></div><div><x-input-label value="From" /><x-select name="from_status" required><option value="empty">Empty</option><option value="filled">Filled</option></x-select></div><div><x-input-label value="Type" /><div class="mt-2 flex gap-5 text-sm"><label><input x-model="damageStatus" type="radio" name="to_status" value="damaged" checked> Damaged</label><label><input x-model="damageStatus" type="radio" name="to_status" value="missing"> Missing</label></div></div><div><x-input-label value="Quantity" /><x-text-input type="number" min="1" name="quantity" required /></div><div><x-input-label value="Reason" /><x-textarea name="notes" required></x-textarea></div><x-button type="submit" class="w-full">Save Record</x-button>
                </form>

                <form x-show="quickAction === 'adjust'" method="POST" x-bind:action="quickUrl(adjustUrl)" class="mt-5 space-y-3">@csrf
                    <div><x-input-label value="Gallon Type" /><x-select x-model="quickProduct" required><option value="">Select gallon type</option>@foreach($gallonOptions as $option)<option value="{{ $option['id'] }}">{{ $option['label'] }}</option>@endforeach</x-select></div><div><x-input-label value="Stock Bucket" /><x-select name="status" required><option value="empty">Empty</option><option value="filled">Filled</option><option value="damaged">Damaged</option><option value="missing">Missing</option></x-select></div><div><x-input-label value="Adjustment Type" /><div class="mt-2 flex gap-5 text-sm"><label><input type="radio" name="adjustment_type" value="increase" checked> Increase</label><label><input type="radio" name="adjustment_type" value="decrease"> Decrease</label></div></div><div><x-input-label value="Quantity" /><x-text-input type="number" min="1" name="quantity" required /></div><div><x-input-label value="Reason" /><x-textarea name="notes" required></x-textarea></div><x-button type="submit" class="w-full">Save Adjustment</x-button>
                </form>
            </div>
        </div>
    </template>
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach(['total' => 'Total Gallons', 'empty' => 'Empty Gallons', 'filled' => 'Filled Gallons', 'withCustomers' => 'Gallons with Customers', 'damaged' => 'Damaged Gallons', 'missing' => 'Missing Gallons'] as $key => $label)
            <x-card><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($dashboard[$key]) }}</p></x-card>
        @endforeach
    </div>
    <div class="no-print mb-5 flex flex-wrap justify-end gap-2">
        <a href="{{ route('admin.stock-movements.index', ['item_type' => 'gallon_stock']) }}" class="inline-flex items-center gap-2 rounded-xl border border-primary-200 bg-white px-3.5 py-2 text-sm font-semibold text-primary-700 shadow-sm transition hover:bg-primary-50 dark:border-primary-500/20 dark:bg-slate-900 dark:text-primary-300 dark:hover:bg-primary-500/10"><x-icon name="swap" class="size-4" /> View Movement History</a>
        <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-white/10 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-white/5"><x-icon name="clipboard" class="size-4" /> Print Report</button>
    </div>
    <x-card class="no-print mb-6">
        <form method="GET" class="grid gap-4 md:grid-cols-4">
            <div><x-input-label value="Search" /><x-text-input name="search" :value="$search" placeholder="ID, type, or size" /></div>
            <div><x-input-label value="Gallon type" /><x-select name="type"><option value="">All types</option>@foreach($types as $option)<option value="{{ $option }}" @selected($type === $option)>{{ $option }}</option>@endforeach</x-select></div>
            <div><x-input-label value="Stock status" /><x-select name="stock_status"><option value="">All statuses</option>@foreach(['In Stock', 'Low Stock', 'Out of Stock'] as $option)<option value="{{ $option }}" @selected($stockStatus === $option)>{{ $option }}</option>@endforeach</x-select></div>
            <div class="flex items-end gap-3"><x-button as="a" href="{{ route('admin.gallon-stocks.index') }}" variant="secondary">Reset</x-button><x-button type="submit"><x-icon name="search" class="size-4" /> Search</x-button></div>
        </form>
    </x-card>
    <x-card padding="p-0">
        @if($products->isEmpty())
            <div class="p-6">
                <x-empty-state icon="droplet" title="No gallon containers yet" description="Add a product in the 'Container' category to start tracking gallon stock." />
            </div>
        @else
            <div id="gallon-inventory-table" class="overflow-x-auto scroll-mt-6">
                <table class="gallon-table min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3 text-center">#</th>
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
                            <tr x-data="{ addOpen: false, editOpen: false, transferOpen: false, fillOpen: false, releaseOpen: false, returnOpen: false }">
                                <td class="px-4 py-3 text-center text-sm font-semibold text-slate-500">{{ $products->firstItem() + $loop->index }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-500">GAL-{{ str_pad((string) $product->id, 4, '0', STR_PAD_LEFT) }}</td>
                                <td class="px-4 py-3"><div class="flex items-center gap-2"><span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300"><x-icon name="droplet" class="size-5" /></span><span class="text-sm font-semibold text-slate-900 dark:text-white">{{ str($product->name)->before(' ')->title() }}</span></div></td>
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
                                    <div class="flex items-center justify-end gap-1.5">
                                        @can('addStock', \App\Models\GallonStock::class)
                                            <button type="button" x-on:click="addOpen = true" class="row-action row-action--blue" title="Add stock">
                                                <x-icon name="plus" class="size-4" />
                                            </button>
                                        @endcan
                                        @can('adjust', \App\Models\GallonStock::class)
                                            <button type="button" x-on:click="editOpen = true" class="row-action row-action--blue" title="Edit gallon type"><x-icon name="pencil" class="size-4" /></button>
                                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Archive this gallon type?')">@csrf @method('DELETE')<button type="submit" class="row-action row-action--red" title="Archive gallon type"><x-icon name="archive" class="size-4" /></button></form>
                                            <button type="button" x-on:click="fillOpen = true" class="row-action row-action--green" title="Fill gallons"><x-icon name="droplet" class="size-4" /></button>
                                            <button type="button" x-on:click="releaseOpen = true" class="row-action row-action--orange" title="Release to customer"><x-icon name="truck" class="size-4" /></button>
                                            <button type="button" x-on:click="returnOpen = true" class="row-action row-action--purple" title="Return from customer"><x-icon name="swap" class="size-4" /></button>
                                            <button type="button" x-on:click="transferOpen = true" class="row-action row-action--red" title="Adjust / Damaged / Missing">
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

                                <template x-teleport="body">
                                    <div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                        <div class="fixed inset-0 bg-slate-900/50" x-on:click="editOpen = false"></div>
                                        <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900">
                                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Edit Gallon Type</h3>
                                            <form method="POST" action="{{ route('admin.gallon-stocks.update', $product) }}" class="mt-4 space-y-3">
                                                @csrf @method('PATCH')
                                                <div><x-input-label value="Gallon type / name" /><x-text-input name="name" :value="$product->name" required /></div>
                                                <div><x-input-label value="Size" /><x-text-input name="size" :value="$product->size" required /></div>
                                                <div><x-input-label value="Reorder level" /><x-text-input type="number" min="0" name="low_stock_threshold" :value="$product->low_stock_threshold" required /></div>
                                                <div class="flex justify-end gap-3"><x-button type="button" x-on:click="editOpen = false" variant="secondary">Cancel</x-button><x-button type="submit">Save Changes</x-button></div>
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
            <div class="flex flex-col gap-3 border-t border-slate-100 px-6 py-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between dark:border-white/5"> <span>Showing {{ $products->firstItem() ?? 0 }} to {{ $products->lastItem() ?? 0 }} of {{ $products->total() }} records</span>{{ $products->links() }}</div>
        @endif
    </x-card>
    </section>
</x-layouts.app>
