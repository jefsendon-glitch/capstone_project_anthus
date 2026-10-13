<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Product::class);
        request()->validate(['search' => ['nullable', 'string', 'max:100']]);

        $archived = request()->boolean('archived');

        $query = $archived ? Product::onlyTrashed() : Product::query();
        $search = trim((string) request('search', ''));
        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('category', 'like', '%'.$search.'%')
                    ->orWhere('size', 'like', '%'.$search.'%');
            });
        }
        $products = $query->orderBy('category')->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.products.index', compact('products', 'archived'));
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.create');
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', config('filesystems.product_image_disk'));
        }

        DB::transaction(function () use ($data, $request) {
            $product = Product::create($data);

            // Container products are tracked by their gallon state. Place the
            // opening quantity in Empty Gallons so it is immediately usable.
            if ($product->category === 'container' && $product->stock_quantity > 0) {
                $emptyStock = $product->gallonStocks()->where('status', 'empty')->firstOrFail();
                $quantity = (int) $product->stock_quantity;
                $emptyStock->update(['quantity' => $quantity]);
                StockMovement::record($emptyStock, 'initial', $quantity, 0, $quantity, $request->user()->id, 'Initial gallon inventory');
            } elseif ($product->stock_quantity > 0) {
                $quantity = (float) $product->stock_quantity;
                StockMovement::record($product, 'initial', $quantity, 0, $quantity, $request->user()->id, 'Opening product stock');
            }
        });

        return redirect()->route('admin.products.index')->with('success', 'Product added successfully.');
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('admin.products.edit', compact('product'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk(config('filesystems.product_image_disk'))->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', config('filesystems.product_image_disk'));
        }

        $product->update($data);

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product archived successfully.');
    }

    public function restore(int $product): RedirectResponse
    {
        $product = Product::onlyTrashed()->findOrFail($product);

        $this->authorize('restore', $product);

        $product->restore();

        return redirect()->route('admin.products.index')->with('success', 'Product restored successfully.');
    }

    public function forceDelete(int $product, \App\Services\ProductPermanentDeletionService $deletion): RedirectResponse
    {
        $product = Product::onlyTrashed()->findOrFail($product);

        $this->authorize('forceDelete', $product);

        $imagePath = $deletion->delete($product, request()->user());

        if ($imagePath) {
            Storage::disk(config('filesystems.product_image_disk'))->delete($imagePath);
        }

        return redirect()->route('admin.products.index', ['archived' => 1])
            ->with('success', 'Product and its linked historical records permanently deleted. Related unpaid customer balances adjusted. Other products and payment records preserved.');
    }

}
