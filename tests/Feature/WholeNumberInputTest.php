<?php

use Illuminate\Support\Facades\Validator;

test('numeric form fields accept whole numbers and reject decimals', function (string $class, string $field) {
    $request = $class::create('/', 'POST', ['field' => 'unit_cost']);
    $rules = $request->rules()[$field];
    expect(Validator::make(['number' => '12'], ['number' => $rules])->passes())->toBeTrue();
    foreach (['12.5', '0.01', '1e2'] as $value) {
        expect(Validator::make(['number' => $value], ['number' => $rules])->fails())->toBeTrue();
    }
})->with([
    [\App\Http\Requests\StoreProductRequest::class, 'unit_price'],
    [\App\Http\Requests\UpdateProductRequest::class, 'unit_price'],
    [\App\Http\Requests\StoreConsumableRequest::class, 'unit_cost'],
    [\App\Http\Requests\UpdateConsumableRequest::class, 'unit_cost'],
    [\App\Http\Requests\QuickUpdateConsumableRequest::class, 'value'],
    [\App\Http\Requests\StoreConsumableRestockRequest::class, 'cost'],
    [\App\Http\Requests\StorePurchaseOrderRequest::class, 'items.*.unit_cost'],
    [\App\Http\Requests\StoreCustomerPaymentRequest::class, 'amount'],
    [\App\Http\Requests\StoreSaleRequest::class, 'tendered_amount'],
]);
