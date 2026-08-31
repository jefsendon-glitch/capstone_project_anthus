<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PurchaseOrder::class);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'ordered_at' => ['nullable', 'date'],
            'expected_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receive_immediately' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.itemable_type' => ['required', Rule::in(['product', 'consumable'])],
            'items.*.itemable_id' => ['required', 'integer'],
            'items.*.quantity_ordered' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ];
    }
}
