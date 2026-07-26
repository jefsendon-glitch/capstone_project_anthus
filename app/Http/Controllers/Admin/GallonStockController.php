<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GallonStock;
use App\Models\Product;
use Illuminate\View\View;

class GallonStockController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', GallonStock::class);

        $products = Product::where('category', 'container')
            ->with('gallonStocks')
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) {
                $product->setRelation(
                    'statusCounts',
                    collect(GallonStock::STATUSES)->mapWithKeys(fn (string $status) => [
                        $status => $product->gallonStocks->firstWhere('status', $status)?->quantity ?? 0,
                    ]),
                );

                return $product;
            });

        return view('admin.gallon-stocks.index', compact('products'));
    }
}
