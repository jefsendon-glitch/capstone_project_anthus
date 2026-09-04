<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Product::class);

        $archived = request()->boolean('archived');

        $query = $archived ? Product::onlyTrashed() : Product::query();
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

        Product::create($data);

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

    public function forceDelete(int $product): RedirectResponse
    {
        $product = Product::onlyTrashed()->findOrFail($product);

        $this->authorize('forceDelete', $product);

        $hasHistory = DB::table('sales_transactions')->where('product_id', $product->id)->exists()
            || DB::table('delivery_orders')->where('product_id', $product->id)->exists()
            || DB::table('delivery_order_items')->where('product_id', $product->id)->exists()
            || DB::table('gallon_stocks')->where('product_id', $product->id)->exists()
            || DB::table('water_production_logs')->where('product_id', $product->id)->exists();

        if ($hasHistory) {
            return redirect()->route('admin.products.index', ['archived' => 1])
                ->with('error', 'This product has historical records and cannot be permanently deleted.');
        }

        $imagePath = $product->image_path;
        $product->forceDelete();

        if ($imagePath) {
            Storage::disk(config('filesystems.product_image_disk'))->delete($imagePath);
        }

        return redirect()->route('admin.products.index', ['archived' => 1])
            ->with('success', 'Product permanently deleted.');
    }

}
