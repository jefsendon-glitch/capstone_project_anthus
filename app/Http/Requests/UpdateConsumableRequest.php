<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConsumableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('consumable'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(['filter_treatment_materials', 'bottle_caps_seals', 'labels_packaging', 'cleaning_sanitation'])],
            'unit' => ['required', 'string', 'max:50'],
            'quantity' => ['required', 'integer', 'min:0', 'max:99999999'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:99999999'],
            'unit_cost' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $this->route('consumable')->supplier_id))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
