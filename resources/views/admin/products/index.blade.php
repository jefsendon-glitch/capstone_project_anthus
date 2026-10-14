<x-layouts.app title="Products" :heading="'Products'">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="inline-flex rounded-xl bg-slate-100 p-1 dark:bg-white/5">
            <a href="{{ route('admin.products.index') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ ! $archived ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-800 dark:text-white' : 'text-slate-500 dark:text-slate-400' }}">Active</a>
            <a href="{{ route('admin.products.index', ['archived' => 1]) }}" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ $archived ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-800 dark:text-white' : 'text-slate-500 dark:text-slate-400' }}">Archived</a>
        </div>
        @can('create', \App\Models\Product::class)
            <x-button variant="success" as="a" href="{{ route('admin.products.create') }}">
                <x-icon name="plus" class="size-4" /> Add Product
            </x-button>
        @endcan
    </div>

    @if($archived)
        <p class="mb-4 rounded-lg border border-red-200 bg-danger-50 p-3 text-xs leading-relaxed text-danger-700 dark:border-red-900 dark:bg-danger-500/10 dark:text-danger-400">Permanent deletion also removes this product’s sales, stock movements, production, gallon balances, and purchase/delivery items. Empty orders are deleted; other products in mixed orders are retained. Related unpaid customer balances are reduced. Live sales totals change; payments and saved financial reports remain. This cannot be undone.</p>
    @endif

    <form method="GET" action="{{ route('admin.products.index') }}" class="mb-4 flex flex-wrap items-center gap-2">
        @if($archived)<input type="hidden" name="archived" value="1">@endif
        <div class="relative min-w-0 flex-1 sm:max-w-sm">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <x-text-input type="search" name="search" :value="request('search')" maxlength="100" placeholder="Search name, category or size..." aria-label="Search products" class="!py-2 !pl-9" />
        </div>
        <x-button type="submit" variant="secondary" class="!py-2">Search</x-button>
        @if(request()->filled('search'))<a href="{{ route('admin.products.index', $archived ? ['archived' => 1] : []) }}" class="px-2 text-xs font-medium text-slate-500 hover:text-primary-600">Clear</a>@endif
    </form>

    <x-card padding="p-0">
        @if($products->isEmpty())
            <div class="p-6">
                <x-empty-state icon="cube" :title="request()->filled('search') ? 'No matching products' : ($archived ? 'No archived products' : 'No products yet')" :description="request()->filled('search') ? 'Try another product name, category or size.' : 'Add purified, alkaline, distilled, mineral, or slim water products here.'" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-6 py-3">Product</th>
                            <th class="px-6 py-3">Category</th>
                            <th class="px-6 py-3">Size</th>
                            <th class="px-6 py-3">Price</th>
                            <th class="px-6 py-3">Stock</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach($products as $product)
                            <tr>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-100 text-slate-400 dark:bg-white/5">
                                            @if($product->image_url)
                                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="size-full object-cover">
                                            @else
                                                <x-icon name="photo" class="size-4" />
                                            @endif
                                        </span>
                                        <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $product->name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-sm text-slate-500 dark:text-slate-400">{{ ucfirst($product->category) }}</td>
                                <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $product->size }}</td>
                                <td class="px-6 py-3 font-mono text-sm text-slate-900 dark:text-white">₱{{ number_format($product->unit_price, 2) }}</td>
                                <td class="px-6 py-3">
                                    @if(! $archived)
                                        @can('update', $product)
                                            <x-stock-action-modal
                                                :action-url="route('admin.products.stock.adjust', $product)"
                                                :title="'Adjust Stock — '.$product->name"
                                                mode="adjust"
                                                adjustment-uses-direction
                                                :trigger-text="$product->stock_quantity"
                                                :trigger-color="$product->is_low_stock
                                                    ? 'inline-flex cursor-pointer items-center rounded-full bg-danger-50 px-2.5 py-1 text-xs font-medium text-danger-700 hover:bg-danger-100 focus:outline-none focus:ring-2 focus:ring-danger-500 dark:bg-danger-500/10 dark:text-danger-500'
                                                    : 'inline-flex cursor-pointer items-center rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-700 hover:bg-success-100 focus:outline-none focus:ring-2 focus:ring-success-500 dark:bg-success-500/10 dark:text-success-500'"
                                            />
                                        @else
                                            <x-badge :color="$product->is_low_stock ? 'danger' : 'success'">{{ $product->stock_quantity }}</x-badge>
                                        @endcan
                                    @else
                                        <x-badge :color="$product->is_low_stock ? 'danger' : 'success'">{{ $product->stock_quantity }}</x-badge>
                                    @endif
                                </td>
                                <td class="px-6 py-3">
                                    <x-badge :color="$product->is_active ? 'success' : 'slate'">{{ $product->is_active ? 'Active' : 'Inactive' }}</x-badge>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        @if($archived)
                                            @can('restore', $product)
                                                <form method="POST" action="{{ route('admin.products.restore', $product) }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-emerald-100 p-2 text-emerald-700 transition hover:bg-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 dark:hover:bg-emerald-900" title="Restore product">
                                                        <x-icon name="check-circle" class="size-4" />
                                                    </button>
                                                </form>
                                            @endcan
                                            @can('forceDelete', $product)
                                                <form method="POST" action="{{ route('admin.products.force-delete', $product) }}" onsubmit="return confirm('Permanently delete this product AND all its linked sales, inventory, production, purchase and delivery records? Related unpaid customer balances will be reduced. This cannot be undone.')">
                                                    @csrf
                                                    @method('delete')
                                                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-red-100 p-2 text-red-700 transition hover:bg-red-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-500 dark:bg-red-950 dark:text-red-300 dark:hover:bg-red-900" title="Permanently delete product">
                                                        <x-icon name="trash" class="size-4" />
                                                    </button>
                                                </form>
                                            @endcan
                                        @else
                                            @can('update', $product)
                                                <a href="{{ route('admin.products.edit', $product) }}" class="inline-flex items-center justify-center rounded-lg bg-blue-100 p-2 text-blue-700 transition hover:bg-blue-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-500 dark:bg-blue-950 dark:text-blue-300 dark:hover:bg-blue-900" title="Edit">
                                                    <x-icon name="pencil" class="size-4" />
                                                </a>
                                            @endcan
                                            @can('delete', $product)
                                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Archive this product?')">
                                                    @csrf
                                                    @method('delete')
                                                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-red-100 p-2 text-red-700 transition hover:bg-red-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-500 dark:bg-red-950 dark:text-red-300 dark:hover:bg-red-900" title="Archive">
                                                        <x-icon name="archive" class="size-4" />
                                                    </button>
                                                </form>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-4 dark:border-white/5">
                {{ $products->links() }}
            </div>
        @endif
    </x-card>
</x-layouts.app>
