<?php

use App\Models\Product;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->staffUser = User::factory()->create();
    $this->staffUser->assignRole('staff');
    Staff::factory()->create(['user_id' => $this->staffUser->id]);

    $this->customer = User::factory()->create(['credit_balance' => 0]);
    $this->customer->assignRole('customer');

    $this->product = Product::factory()->create(['unit_price' => 25, 'stock_quantity' => 50]);
});

test('customer can place a delivery order', function () {
    $response = $this->actingAs($this->customer)->post(route('customer.orders.store'), [
        'product_id' => $this->product->id,
        'quantity' => 2,
        'customer_address' => '123 Sample St, Bato, Camarines Sur',
        'payment_method' => 'cash',
        'preferred_delivery_date' => now()->addDay()->toDateString(),
    ]);

    $order = $this->customer->deliveryOrders()->latest()->first();

    expect($order)->not->toBeNull();
    expect((float) $order->total_amount)->toBe(50.0);
    expect($order->payment_method)->toBe('cash');
    expect($order->status)->toBe('pending');
    $response->assertRedirect(route('customer.orders.show', $order));
});

test('customer can order multiple different available products in one delivery order', function () {
    $secondProduct = Product::factory()->create(['unit_price' => 15, 'stock_quantity' => 10]);

    $response = $this->actingAs($this->customer)->post(route('customer.orders.store'), [
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 2],
            ['product_id' => $secondProduct->id, 'quantity' => 3],
        ],
        'customer_address' => '123 Sample St, Bato, Camarines Sur',
        'payment_method' => 'loan',
    ]);

    $order = $this->customer->deliveryOrders()->latest()->first();

    expect($order->items)->toHaveCount(2);
    expect((float) $order->total_amount)->toBe(95.0);
    expect($order->payment_method)->toBe('loan');
    $response->assertRedirect(route('customer.orders.show', $order));
});

test('customer cannot view another customers order', function () {
    $order = app(\App\Services\DeliveryService::class)->placeOrder([
        'product_id' => $this->product->id,
        'quantity' => 1,
        'customer_address' => 'Somewhere',
    ], $this->customer);

    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole('customer');

    $this->actingAs($otherCustomer)->get(route('customer.orders.show', $order))->assertForbidden();
});

test('staff fulfilling an order on credit updates stock and the customers balance', function () {
    $order = app(\App\Services\DeliveryService::class)->placeOrder([
        'product_id' => $this->product->id,
        'quantity' => 3,
        'customer_address' => 'Somewhere',
        'payment_method' => 'loan',
    ], $this->customer);

    app(\App\Services\DeliveryService::class)->updateStatus($order, 'confirmed', $this->staffUser);

    $response = $this->actingAs($this->staffUser)->post(route('deliveries.fulfill', $order), [
        'payment_method' => 'cash',
    ]);

    $response->assertRedirect(route('deliveries.show', $order));

    expect($order->fresh()->status)->toBe('delivered');
    expect($this->product->fresh()->stock_quantity)->toBe(47);
    expect((float) $this->customer->fresh()->credit_balance)->toBe(75.0);

    $this->assertDatabaseHas('sales_transactions', [
        'transaction_type' => 'delivery',
        'customer_id' => $this->customer->id,
        'payment_method' => 'loan',
    ]);
});

test('customer cannot place a credit order when their credit account is suspended', function () {
    $this->customer->update(['credit_status' => 'suspended']);

    $response = $this->actingAs($this->customer)->post(route('customer.orders.store'), [
        'product_id' => $this->product->id,
        'quantity' => 1,
        'customer_address' => 'Somewhere',
        'payment_method' => 'loan',
    ]);

    $response->assertSessionHasErrors('payment_method');
    expect($this->customer->deliveryOrders()->count())->toBe(0);
});

test('staff fulfilling an order with cash does not change the customers balance', function () {
    $order = app(\App\Services\DeliveryService::class)->placeOrder([
        'product_id' => $this->product->id,
        'quantity' => 1,
        'customer_address' => 'Somewhere',
    ], $this->customer);

    $this->actingAs($this->staffUser)->post(route('deliveries.fulfill', $order), [
        'payment_method' => 'cash',
    ]);

    expect((float) $this->customer->fresh()->credit_balance)->toBe(0.0);
});

test('a customer can view a receipt only after their delivery sale is completed', function () {
    $order = app(\App\Services\DeliveryService::class)->placeOrder([
        'product_id' => $this->product->id,
        'quantity' => 2,
        'customer_address' => 'Somewhere',
        'payment_method' => 'cash',
    ], $this->customer);

    $this->actingAs($this->customer)
        ->get(route('customer.orders.receipt', $order))
        ->assertNotFound();

    app(\App\Services\DeliveryService::class)->updateStatus($order, 'confirmed', $this->staffUser);
    $this->actingAs($this->staffUser)->post(route('deliveries.fulfill', $order), ['payment_method' => 'loan']);

    $this->assertDatabaseHas('sales_transactions', [
        'delivery_order_id' => $order->id,
        'payment_method' => 'cash',
    ]);

    $this->actingAs($this->customer)
        ->get(route('customer.orders.receipt', $order))
        ->assertOk()
        ->assertSee($order->order_code)
        ->assertSee('₱50.00');
});

test('a customer can view a receipt for an existing delivered order without sales transactions', function () {
    $order = app(\App\Services\DeliveryService::class)->placeOrder([
        'product_id' => $this->product->id,
        'quantity' => 2,
        'customer_address' => 'Somewhere',
        'payment_method' => 'cash',
    ], $this->customer);

    $order->update([
        'status' => 'delivered',
        'delivered_at' => now(),
    ]);

    expect($order->salesTransactions)->toBeEmpty();

    $this->actingAs($this->customer)
        ->get(route('customer.orders.receipt', $order))
        ->assertOk()
        ->assertSee($order->order_code)
        ->assertSee($this->product->name)
        ->assertSee('50.00');
});

test('staff cannot fulfill an order for more than the current stock on hand', function () {
    $order = app(\App\Services\DeliveryService::class)->placeOrder([
        'product_id' => $this->product->id,
        'quantity' => 3,
        'customer_address' => 'Somewhere',
    ], $this->customer);

    $this->product->update(['stock_quantity' => 1]);

    $response = $this->actingAs($this->staffUser)->post(route('deliveries.fulfill', $order), [
        'payment_method' => 'cash',
    ]);

    $response->assertSessionHasErrors('payment_method');
    expect($order->fresh()->status)->toBe('pending');
    expect($this->product->fresh()->stock_quantity)->toBe(1);
});

test('customer can cancel their own pending order', function () {
    $order = app(\App\Services\DeliveryService::class)->placeOrder([
        'product_id' => $this->product->id,
        'quantity' => 1,
        'customer_address' => 'Somewhere',
    ], $this->customer);

    $response = $this->actingAs($this->customer)->patch(route('customer.orders.cancel', $order));

    $response->assertRedirect(route('customer.orders.show', $order));
    expect($order->fresh()->status)->toBe('cancelled');
});

test('customer cannot cancel another customers order', function () {
    $order = app(\App\Services\DeliveryService::class)->placeOrder([
        'product_id' => $this->product->id,
        'quantity' => 1,
        'customer_address' => 'Somewhere',
    ], $this->customer);

    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole('customer');

    $this->actingAs($otherCustomer)->patch(route('customer.orders.cancel', $order))->assertForbidden();
    expect($order->fresh()->status)->toBe('pending');
});

test('customer cannot cancel an order that is already out for delivery', function () {
    $order = app(\App\Services\DeliveryService::class)->placeOrder([
        'product_id' => $this->product->id,
        'quantity' => 1,
        'customer_address' => 'Somewhere',
    ], $this->customer);

    $order->update(['status' => 'out_for_delivery']);

    $this->actingAs($this->customer)->patch(route('customer.orders.cancel', $order))->assertForbidden();
    expect($order->fresh()->status)->toBe('out_for_delivery');
});
