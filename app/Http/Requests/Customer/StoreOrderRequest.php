<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // "credit" is customer-facing; the sales ledger stores it as "loan".
        if ($this->input('payment_method') === 'credit') {
            $this->merge(['payment_method' => 'loan']);
        }

        if (! $this->has('items') && $this->filled('product_id')) {
            $this->merge(['items' => [[
                'product_id' => $this->input('product_id'),
                'quantity' => $this->input('quantity'),
                'container_type' => $this->input('container_type'),
            ]]]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()->isCustomer();
    }

    public function messages(): array
    {
        return ['customer_address.required' => 'Enter your delivery address before placing an order.'];
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.container_type' => ['nullable', Rule::in(['round', 'slim'])],
            'customer_address' => ['required', 'string', 'max:2000'],
            'payment_method' => ['required', Rule::in(['cash', 'loan'])],
            'preferred_delivery_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
