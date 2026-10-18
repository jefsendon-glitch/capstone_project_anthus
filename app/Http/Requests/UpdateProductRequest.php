<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }

    protected function prepareForValidation(): void
    {
        $types = \App\Models\Product::WATER_TYPES;
        $category = array_search($this->input('name'), $types, true);
        if ($this->input('category') !== 'other' && $category !== false) $this->merge(['category' => $category]);
        $this->merge(['requires_container_choice' => array_key_exists((string) $this->input('category'), $types)]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')->where('category', $this->input('category'))->where('size', $this->input('size'))->ignore($this->route('product')?->id)],
            'requires_container_choice' => ['boolean'],
            'category' => ['required', Rule::in(['refill', 'alkaline', 'distilled', 'purified', 'container', 'other'])],
            'size' => ['required', 'string', 'max:100'],
            'unit_price' => ['required', 'integer', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
