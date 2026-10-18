<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    protected function prepareForValidation(): void
    {
        $types = \App\Models\Product::WATER_TYPES;
        $category = array_search($this->input('name'), $types, true);
        if ($this->input('product_kind') === 'other') {
            $this->merge(['category' => 'other']);
        } elseif ($category !== false) {
            $this->merge(['category' => $category]);
        }
        $this->merge(['requires_container_choice' => array_key_exists((string) $this->input('category'), $types)]);
        if ($this->boolean('requires_container_choice') && ($this->has('round_stock') || $this->has('slim_stock'))) {
            $this->merge(['stock_quantity' => (int) $this->input('round_stock', 0) + (int) $this->input('slim_stock', 0)]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')->where('category', $this->input('category'))->where('size', $this->input('size'))->ignore($this->route('product')?->id)],
            'requires_container_choice' => ['boolean'],
            'category' => ['required', Rule::in(['refill', 'alkaline', 'distilled', 'purified', 'container', 'other'])],
            'product_kind' => ['sometimes', Rule::in(['water', 'other'])],
            'size' => ['required', 'string', 'max:100'],
            'unit_price' => ['required', 'integer', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'round_stock' => ['exclude_unless:requires_container_choice,true', 'required_if:product_kind,water', 'required_with:slim_stock', 'integer', 'min:0'],
            'slim_stock' => ['exclude_unless:requires_container_choice,true', 'required_if:product_kind,water', 'required_with:round_stock', 'integer', 'min:0'],
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
