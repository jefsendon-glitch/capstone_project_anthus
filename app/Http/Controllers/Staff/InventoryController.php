<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Consumable;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = trim($request->input('search', ''));
        $products = Product::active()->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
            ->where('name', 'like', '%'.$search.'%')->orWhere('size', 'like', '%'.$search.'%')))
            ->orderBy('name')->orderBy('id')->paginate(5, ['*'], 'products_page')->withQueryString();
        $supplies = Consumable::when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')->orderBy('id')->paginate(5, ['*'], 'supplies_page')->withQueryString();

        return view('staff.inventory', compact('products', 'supplies'));
    }
}
