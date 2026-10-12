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
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'status' => ['sometimes', Rule::in(['draft', 'ordered'])],
            'ordered_at' => ['nullable', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:ordered_at'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'array'],
            'items.*.itemable_type' => ['required', Rule::in(['product', 'consumable'])],
            'items.*.itemable_id' => ['required', 'integer'],
            'items.*.quantity_ordered' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'items.*.unit_cost' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('ordered_at')) {
            $this->merge(['ordered_at' => $this->route('purchase_order')?->ordered_at?->toDateString() ?? now()->toDateString()]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $seen = [];
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

                $key = $type.':'.$id;
                if (isset($seen[$key])) {
                    $validator->errors()->add("items.{$index}.itemable_id", 'This item is already included. Update its quantity instead.');
                }
                $seen[$key] = true;
                if ($type === 'product' && (floor((float) $item['quantity_ordered']) !== (float) $item['quantity_ordered'] || (float) $item['quantity_ordered'] > 2147483647)) {
                    $validator->errors()->add("items.{$index}.quantity_ordered", 'Container orders must use whole pieces within the inventory limit.');
                }

                if ($type === 'consumable' && Consumable::whereKey($id)->whereNotNull('supplier_id')->where('supplier_id', '!=', $this->input('supplier_id'))->exists()) {
                    $validator->errors()->add("items.{$index}.itemable_id", 'This supply belongs to another supplier.');
                }
            }
        });
    }
}
