<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReceivePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('receive', $this->route('purchase_order'));
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'receipt_token' => ['nullable', 'uuid'],
            'items.*' => ['required', 'array'],
            'items.*.purchase_order_item_id' => ['required', 'integer', 'distinct', Rule::exists('purchase_order_items', 'id')->where('purchase_order_id', $this->route('purchase_order')->id)],
            'items.*.quantity_received' => ['required', 'integer', 'min:0', 'max:9999999999'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $items = $this->route('purchase_order')->items()->get()->keyBy('id');
            $total = 0;
            foreach ($this->input('items', []) as $index => $line) {
                $item = $items->get($line['purchase_order_item_id']);
                $quantity = (float) $line['quantity_received'];
                $total += $quantity;
                if ($item->itemable instanceof Product && floor($quantity) !== $quantity) {
                    $validator->errors()->add("items.{$index}.quantity_received", 'Receive containers in whole pieces.');
                }
                if ($quantity > $item->remaining_quantity) {
                    $validator->errors()->add("items.{$index}.quantity_received", 'Quantity exceeds the remaining order quantity.');
                }
                if ($quantity > 0 && (! $item->itemable || $item->itemable->trashed())) {
                    $validator->errors()->add("items.{$index}.quantity_received", 'Restore the archived item before receiving stock.');
                }
            }
            if ($total <= 0) {
                $validator->errors()->add('items', 'Enter at least one delivered quantity greater than zero.');
            }
        });
    }
}
