<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuickUpdateConsumableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('consumable'));
    }

    public function rules(): array
    {
        return [
            'field' => ['required', Rule::in(['quantity', 'low_stock_threshold', 'unit_cost'])],
            'value' => ['required', 'integer', 'min:0', Rule::when($this->input('field') === 'quantity', ['min:1'])],
            'adjustment_type' => ['nullable', Rule::in(['increase', 'decrease']), 'required_if:field,quantity'],
            'notes' => ['nullable', 'string', 'max:1000', 'required_if:field,quantity'],
        ];
    }
}
