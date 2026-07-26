<?php

namespace App\Http\Requests;

use App\Models\Consumable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsumableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Consumable::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(['bottle_caps', 'bottle_seals', 'labels', 'plastic_bags', 'water_filters', 'uv_lamps', 'cleaning_supplies'])],
            'unit' => ['required', 'string', 'max:50'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'low_stock_threshold' => ['required', 'numeric', 'min:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
