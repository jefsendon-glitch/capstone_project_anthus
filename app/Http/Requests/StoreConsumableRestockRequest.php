<?php

namespace App\Http\Requests;

use App\Models\Consumable;
use Illuminate\Foundation\Http\FormRequest;

class StoreConsumableRestockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('restock', Consumable::class);
    }

    public function rules(): array
    {
        return [
            'quantity_added' => ['required', 'numeric', 'min:0.01'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
