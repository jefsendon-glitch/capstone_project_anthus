<?php

namespace App\Http\Requests;

use App\Models\DeliveryOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FulfillDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', DeliveryOrder::class);
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['nullable', Rule::in(['cash', 'loan'])],
        ];
    }
}
