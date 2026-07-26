<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConsumableStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('consumable'));
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'numeric', 'min:0'],
        ];
    }
}
