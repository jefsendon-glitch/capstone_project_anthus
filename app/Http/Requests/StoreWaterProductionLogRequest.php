<?php

namespace App\Http\Requests;

use App\Models\WaterProductionLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWaterProductionLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', WaterProductionLog::class);
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('category', '!=', 'container')),
            ],
            'gallons_produced' => ['required', 'integer', 'min:1'],
            'production_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
