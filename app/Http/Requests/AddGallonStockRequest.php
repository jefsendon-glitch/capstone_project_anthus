<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddGallonStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('addStock', GallonStock::class);
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['required', 'string', 'max:1000'],
        ];
    }
}
