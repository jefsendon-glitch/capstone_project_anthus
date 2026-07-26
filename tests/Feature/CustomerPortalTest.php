<?php

use App\Models\CustomerPayment;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\User;
use App\Services\DeliveryService;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->customer = User::factory()->create(['credit_balance' => 75]);
    $this->customer->assignRole('customer');

    $this->product = Product::factory()->create(['unit_price' => 40, 'stock_quantity' => 20]);
});

test('the customer dashboard reflects real order counts and lifetime spend', function () {
    app(DeliveryService::class)->placeOrder([
        'product_id' => $this->product->id,
        'quantity' => 1,
        'customer_address' => 'Somewhere',
    ], $this->customer);

    SalesTransaction::create([
        'transaction_code' => 'TXN-PORTAL-001',
        'transaction_type' => 'walk-in',
        'customer_id' => $this->customer->id,
        'product_id' => $this->product->id,
        'product_name' => $this->product->name,
        'quantity' => 2,
        'unit_price' => 40,
        'total_amount' => 80,
        'payment_method' => 'cash',
    ]);

    $response = $this->actingAs($this->customer)->get(route('customer.dashboard'));

    $response->assertOk();
    $response->assertSee('₱75'); // outstanding balance
    $response->assertSee('₱80'); // total spent
});

test('the customer can view their full payment history', function () {
    CustomerPayment::create([
        'customer_id' => $this->customer->id,
        'customer_name' => $this->customer->name,
        'amount' => 25,
        'payment_date' => now()->subDays(3)->toDateString(),
        'staff_name' => 'Shaunti Staff',
    ]);

    $response = $this->actingAs($this->customer)->get(route('customer.payments.index'));

    $response->assertOk();
    $response->assertSee('₱25.00');
    $response->assertSee('Shaunti Staff');
});

test('a customer cannot see another customers payment history', function () {
    $other = User::factory()->create();
    $other->assignRole('customer');

    CustomerPayment::create([
        'customer_id' => $other->id,
        'customer_name' => $other->name,
        'amount' => 999,
        'payment_date' => now()->toDateString(),
    ]);

    $response = $this->actingAs($this->customer)->get(route('customer.payments.index'));

    $response->assertOk();
    $response->assertDontSee('999.00');
});

test('the order index can be filtered by status', function () {
    $delivery = app(DeliveryService::class);
    $pending = $delivery->placeOrder(['product_id' => $this->product->id, 'quantity' => 1, 'customer_address' => 'A'], $this->customer);
    $cancelled = $delivery->placeOrder(['product_id' => $this->product->id, 'quantity' => 1, 'customer_address' => 'B'], $this->customer);
    $cancelled->update(['status' => 'cancelled']);

    $response = $this->actingAs($this->customer)->get(route('customer.orders.index', ['status' => 'cancelled']));

    $response->assertOk();
    $response->assertSee($cancelled->order_code);
    $response->assertDontSee($pending->order_code);
});
