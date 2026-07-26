<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('maintenance'));
    }

    public function rules(): array
    {
        return [
            'equipment_name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(['filter', 'pump', 'uv_lamp', 'ozone', 'ro_membrane', 'other'])],
            'last_maintenance_date' => ['nullable', 'date'],
            'next_due_date' => ['required', 'date'],
            'status' => ['required', Rule::in(['ok', 'due_soon', 'overdue'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
