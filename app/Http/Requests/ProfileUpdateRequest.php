<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:2000'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function after(): array
    {
        return [function (\Illuminate\Validation\Validator $validator) {
            $parts = $this->only(['first_name', 'middle_name', 'last_name']);
            if (collect($parts)->every(fn ($part) => is_string($part) || $part === null)
                && mb_strlen(implode(' ', array_filter($parts, fn ($part) => $part !== null && $part !== ''))) > 255) {
                $validator->errors()->add('last_name', 'Your complete name must not exceed 255 characters.');
            }
        }];
    }
}
