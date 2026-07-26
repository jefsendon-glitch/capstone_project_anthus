<?php

namespace App\Http\Requests;

use App\Models\GallonStock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddGallonStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('addStock', GallonStock::class);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(GallonStock::STATUSES)],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
