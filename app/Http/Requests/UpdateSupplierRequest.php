<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('supplier'));
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [function (\Illuminate\Validation\Validator $validator) {
            $parts = $this->only(['first_name', 'middle_name', 'last_name']);
            if (collect($parts)->every(fn ($part) => is_string($part) || $part === null)
                && mb_strlen(implode(' ', array_filter($parts, fn ($part) => $part !== null && $part !== ''))) > 255) {
                $validator->errors()->add('last_name', 'The complete supplier name must not exceed 255 characters.');
            }
        }];
    }
}
