<x-layouts.app title="Products" :heading="'Products'">
    <div class="mb-6 flex items-center justify-between">
        <div class="inline-flex rounded-xl bg-slate-100 p-1 dark:bg-white/5">
            <a href="{{ route('admin.products.index') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ ! $archived ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-800 dark:text-white' : 'text-slate-500 dark:text-slate-400' }}">Active</a>
            <a href="{{ route('admin.products.index', ['archived' => 1]) }}" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ $archived ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-800 dark:text-white' : 'text-slate-500 dark:text-slate-400' }}">Archived</a>
        </div>
        @can('create', \App\Models\Product::class)
            <x-button as="a" href="{{ route('admin.products.create') }}">
                <x-icon name="plus" class="size-4" /> Add Product
            </x-button>
        @endcan
    </div>

    <x-card padding="p-0">
        @if($products->isEmpty())
            <div class="p-6">
                <x-empty-state icon="cube" :title="$archived ? 'No archived products' : 'No products yet'" description="Add purified, alkaline, distilled, mineral, or slim water products here." />
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
                                    <x-badge :color="$product->is_low_stock ? 'danger' : 'success'">{{ $product->stock_quantity }}</x-badge>
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
                                                    <button type="submit" class="text-slate-500 hover:text-success-600" title="Restore product">
                                                        <x-icon name="check-circle" class="size-4" />
                                                    </button>
                                                </form>
                                            @endcan
                                            @can('forceDelete', $product)
                                                <form method="POST" action="{{ route('admin.products.force-delete', $product) }}" onsubmit="return confirm('Permanently delete this product? This cannot be undone.')">
                                                    @csrf
                                                    @method('delete')
                                                    <button type="submit" class="text-slate-500 hover:text-danger-600" title="Permanently delete product">
                                                        <x-icon name="trash" class="size-4" />
                                                    </button>
                                                </form>
                                            @endcan
                                        @else
                                            @can('addStock', $product)
                                                <x-stock-action-modal
                                                    :action-url="route('admin.products.stock.add', $product)"
                                                    :title="'Add Stock — '.$product->name"
                                                    mode="add"
                                                    trigger-icon="plus"
                                                />
                                            @endcan
                                            @can('update', $product)
                                                <x-stock-action-modal
                                                    :action-url="route('admin.products.stock.adjust', $product)"
                                                    :title="'Adjust Stock — '.$product->name"
                                                    mode="adjust"
                                                    trigger-icon="swap"
                                                />
                                                <a href="{{ route('admin.products.edit', $product) }}" class="text-slate-500 hover:text-primary-600" title="Edit">
                                                    <x-icon name="pencil" class="size-4" />
                                                </a>
                                            @endcan
                                            @can('delete', $product)
                                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Archive this product?')">
                                                    @csrf
                                                    @method('delete')
                                                    <button type="submit" class="text-slate-500 hover:text-danger-600" title="Archive">
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
