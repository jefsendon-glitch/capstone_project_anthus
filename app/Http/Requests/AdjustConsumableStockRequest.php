<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustConsumableStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('consumable'));
    }

    public function rules(): array
    {
        return [
            'delta' => ['required', 'numeric'],
            'notes' => ['required', 'string', 'max:1000'],
        ];
    }
}
