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
            'category' => ['required', Rule::in(['filter_treatment_materials', 'bottle_caps_seals', 'labels_packaging', 'cleaning_sanitation', 'bottle_caps', 'bottle_seals', 'labels', 'plastic_bags', 'water_filters', 'treatment_materials', 'uv_lamps', 'cleaning_supplies'])],
            'unit' => ['required', 'string', 'max:50'],
            'quantity' => ['required', 'integer', 'min:0', 'max:99999999'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:99999999'],
            'unit_cost' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
