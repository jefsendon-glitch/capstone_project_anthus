<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }

    public function rules(): array
    {
        if ($this->route('product')->requires_container_choice) {
            return [
                'round_stock' => ['required', 'integer', 'min:0', 'max:1000000'],
                'slim_stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            ];
        }
        return [
            'quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
