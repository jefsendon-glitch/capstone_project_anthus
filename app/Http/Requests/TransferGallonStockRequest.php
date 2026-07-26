<?php

namespace App\Http\Requests;

use App\Models\GallonStock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferGallonStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('adjust', GallonStock::class);
    }

    public function rules(): array
    {
        return [
            'from_status' => ['required', Rule::in(GallonStock::STATUSES)],
            'to_status' => ['required', Rule::in(GallonStock::STATUSES), 'different:from_status'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => [Rule::requiredIf(fn () => in_array($this->input('to_status'), ['damaged', 'missing'])), 'nullable', 'string', 'max:1000'],
        ];
    }
}
