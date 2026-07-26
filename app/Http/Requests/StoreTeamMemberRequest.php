<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::in(['staff', 'admin'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'employee_id' => ['required_if:role,staff', 'nullable', 'string', 'max:100', 'unique:staff,employee_id'],
            'position' => ['nullable', 'string', 'max:100'],
            'hire_date' => ['nullable', 'date'],
        ];
    }
}
