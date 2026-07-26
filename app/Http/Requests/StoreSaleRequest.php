<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isStaff();
    }

    protected function prepareForValidation(): void
    {
        $items = json_decode((string) $this->input('items'), true);

        $this->merge([
            'items' => is_array($items) ? $items : [],
        ]);
    }

    public function rules(): array
    {
        return [
            'transaction_type' => ['required', Rule::in(['walk-in', 'delivery'])],
            'payment_method' => ['required', Rule::in(['cash', 'loan'])],
            'customer_id' => ['required_if:payment_method,loan', 'nullable', 'exists:users,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'tendered_amount' => ['required_if:payment_method,cash', 'nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
