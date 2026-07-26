<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddProductStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('addStock', $this->route('product'));
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
