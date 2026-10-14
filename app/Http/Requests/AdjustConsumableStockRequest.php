<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustConsumableStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('consumable'));
    }

    public function rules(): array
    {
        return [
            'adjustment_type' => ['required', Rule::in(['increase', 'decrease'])],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['required', 'string', 'max:1000'],
        ];
    }
}
