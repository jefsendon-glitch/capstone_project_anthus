<?php

namespace App\Http\Requests;

use App\Models\DeliveryOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliveryStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', DeliveryOrder::class);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['pending', 'confirmed', 'out_for_delivery', 'cancelled'])],
        ];
    }
}
