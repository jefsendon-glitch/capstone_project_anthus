<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProductOrderLines
{
    /** Prepare container variants against their own balances and one price. Call inside a transaction. */
    public function prepare(array $items): Collection
    {
        $items = collect($items);
        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Add at least one product.']);
        }
        $products = Product::whereIn('id', $items->pluck('product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        foreach ($items->groupBy('product_id') as $id => $group) {
            $product = $products->get($id);
            if (! $product || ! $product->is_active) {
                throw ValidationException::withMessages(['items' => 'One of the selected products is no longer available.']);
            }
            foreach ($group as $item) {
                $quantity = $item['quantity'] ?? null;
                $container = $item['container_type'] ?? null;
                if (filter_var($quantity, FILTER_VALIDATE_INT) === false || (int) $quantity < 1) {
                    throw ValidationException::withMessages(['items' => 'Enter a whole quantity of at least 1.']);
                }
                if (($container !== null && $container !== '' && ! in_array($container, ['round', 'slim'], true))
                    || ($product->requires_container_choice && ! in_array($container, ['round', 'slim'], true))) {
                    throw ValidationException::withMessages(['items' => 'Choose Round Gallon or Slim Gallon for '.$product->name.'.']);
                }
            }
            if ($group->sum('quantity') > $product->stock_quantity) {
                throw ValidationException::withMessages(['items' => 'Not enough total stock for '.$product->name.'.']);
            }
            foreach ($group->groupBy(fn ($item) => $item['container_type'] ?? '') as $container => $variant) {
                if ($variant->sum('quantity') > $product->availableStock($container ?: null)) {
                    throw ValidationException::withMessages(['items' => 'Not enough '.ucfirst($container).' Gallon stock for '.$product->name.'. Only '.$product->availableStock($container ?: null).' available.']);
                }
            }
        }

        return $items->groupBy(fn ($item) => $item['product_id'].':'.($item['container_type'] ?? ''))
            ->map(function ($group) use ($products) {
                $first = $group->first();
                $product = $products->get($first['product_id']);
                $quantity = (int) $group->sum('quantity');
                $amount = ((int) round((float) $product->unit_price * 100) * $quantity) / 100;

                return ['product' => $product, 'quantity' => $quantity, 'container_type' => ($first['container_type'] ?? null) ?: null,
                    'line_total' => $amount, 'total_amount' => $amount];
            })->values();
    }
}
