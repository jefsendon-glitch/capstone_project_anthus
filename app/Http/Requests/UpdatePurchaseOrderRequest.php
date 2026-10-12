<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdatePurchaseOrderRequest extends StorePurchaseOrderRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('purchase_order'));
    }

    public function rules(): array
    {
        return array_replace(parent::rules(), [
            'status' => ['required', Rule::in(['draft', 'ordered', 'cancelled'])],
        ]);
    }
}
