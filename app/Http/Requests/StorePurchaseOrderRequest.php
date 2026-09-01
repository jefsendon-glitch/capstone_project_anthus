<?php

namespace App\Http\Requests;

use App\Models\Consumable;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PurchaseOrder::class);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'ordered_at' => ['nullable', 'date'],
            'expected_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.itemable_type' => ['required', Rule::in(['product', 'consumable'])],
            'items.*.itemable_id' => ['required', 'integer'],
            'items.*.quantity_ordered' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ((array) $this->input('items', []) as $index => $item) {
                $type = $item['itemable_type'] ?? null;
                $id = $item['itemable_id'] ?? null;

                if (! is_numeric($id) || ! in_array($type, ['product', 'consumable'], true)) {
                    continue;
                }

                $exists = $type === 'product'
                    ? Product::where('category', 'container')->whereKey($id)->exists()
                    : Consumable::whereKey($id)->exists();

                if (! $exists) {
                    $validator->errors()->add("items.{$index}.itemable_id", 'Select a current '.$type.' from the list.');
                }
            }
        });
    }
}
