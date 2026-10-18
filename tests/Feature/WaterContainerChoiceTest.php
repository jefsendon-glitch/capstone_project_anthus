<?php

use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\User;
use App\Services\DeliveryService;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->staff = User::factory()->create();
    $this->staff->assignRole('staff');
    $this->customer = User::factory()->create(['credit_status' => 'eligible', 'credit_balance' => 0]);
    $this->customer->assignRole('customer');
    $this->water = Product::factory()->create(['name' => 'Purified Water', 'category' => 'purified',
        'size' => '5 Gallon', 'unit_price' => 25, 'stock_quantity' => 10, 'is_active' => true, 'requires_container_choice' => true]);
});

test('admin registers water once without container names and duplicate products are rejected', function () {
    $data = ['name' => 'Distilled Water', 'size' => '5 Gallon', 'unit_price' => 30,
        'stock_quantity' => 10, 'low_stock_threshold' => 2, 'is_active' => 1];
    $this->actingAs($this->admin)->post(route('admin.products.store'), $data)->assertSessionHasNoErrors();
    $this->assertDatabaseHas('products', ['name' => 'Distilled Water', 'category' => 'distilled', 'requires_container_choice' => true]);
    $this->post(route('admin.products.store'), $data)->assertSessionHasErrors('name');
    expect(Product::where('name', 'Distilled Water')->count())->toBe(1);
    $this->get(route('admin.products.create'))->assertOk()->assertSee('Water Type')->assertSee('Alkaline Water')->assertDontSee('Mineral Water');
    $this->get(route('admin.products.edit', $this->water))->assertOk();
});

test('pos round and slim lines share the same price and deduct one combined stock balance', function () {
    $items = [['product_id' => $this->water->id, 'quantity' => 2, 'container_type' => 'round'],
        ['product_id' => $this->water->id, 'quantity' => 3, 'container_type' => 'slim']];
    $response = $this->actingAs($this->staff)->post(route('pos.store'), [
        'transaction_type' => 'walk-in', 'payment_method' => 'cash', 'tendered_amount' => 150, 'items' => json_encode($items),
    ])->assertSessionHasNoErrors();
    expect((float) $this->water->fresh()->stock_quantity)->toBe(5.0);
    $this->assertDatabaseCount('sales_transactions', 2);
    expect((float) SalesTransaction::sum('total_amount'))->toBe(125.0);
    $this->assertDatabaseHas('sales_transactions', ['container_type' => 'round', 'quantity' => 2, 'unit_price' => 25]);
    $this->assertDatabaseHas('sales_transactions', ['container_type' => 'slim', 'quantity' => 3, 'unit_price' => 25]);
    $this->get($response->headers->get('Location'))->assertOk()->assertSee('Round Gallon')->assertSee('Slim Gallon');
});

test('missing invalid and oversold container selections leave records and stock unchanged', function () {
    $this->actingAs($this->staff);
    foreach ([null, 'square'] as $type) {
        $this->post(route('pos.store'), ['transaction_type' => 'walk-in', 'payment_method' => 'cash', 'tendered_amount' => 500,
            'items' => json_encode([['product_id' => $this->water->id, 'quantity' => 1, 'container_type' => $type]])])->assertSessionHasErrors();
    }
    $this->post(route('pos.store'), ['transaction_type' => 'walk-in', 'payment_method' => 'cash', 'tendered_amount' => 500,
        'items' => json_encode([['product_id' => $this->water->id, 'quantity' => 6, 'container_type' => 'round'],
            ['product_id' => $this->water->id, 'quantity' => 6, 'container_type' => 'slim']])])->assertSessionHasErrors('items');
    $this->assertDatabaseCount('sales_transactions', 0);
    expect((float) $this->water->fresh()->stock_quantity)->toBe(10.0);
});

test('customer orders retain both containers through fulfillment and customer receipts', function () {
    $this->actingAs($this->customer)->post(route('customer.orders.store'), [
        'items' => [['product_id' => $this->water->id, 'quantity' => 2, 'container_type' => 'round'],
            ['product_id' => $this->water->id, 'quantity' => 3, 'container_type' => 'slim']],
        'customer_address' => 'Complete address', 'payment_method' => 'cash',
    ])->assertSessionHasNoErrors();
    $order = $this->customer->deliveryOrders()->firstOrFail();
    expect($order->items)->toHaveCount(2);
    $this->get(route('customer.orders.show', $order))->assertOk()->assertSee('Round Gallon')->assertSee('Slim Gallon');
    $service = app(DeliveryService::class);
    $service->updateStatus($order, 'confirmed', $this->staff);
    $service->fulfill($order, 'cash', $this->staff);
    expect((float) $this->water->fresh()->stock_quantity)->toBe(5.0);
    $this->assertDatabaseCount('sales_transactions', 2);
    $this->get(route('customer.orders.receipt', $order))->assertOk()->assertSee('Round Gallon')->assertSee('Slim Gallon');
});

test('fulfillment checks combined container quantities and rolls back if shared stock runs short', function () {
    $service = app(DeliveryService::class);
    $order = $service->placeOrder(['items' => [['product_id' => $this->water->id, 'quantity' => 3, 'container_type' => 'round'],
        ['product_id' => $this->water->id, 'quantity' => 3, 'container_type' => 'slim']], 'customer_address' => 'Address'], $this->customer);
    $service->updateStatus($order, 'confirmed', $this->staff);
    $this->water->update(['stock_quantity' => 5]);
    try {
        $service->fulfill($order, 'cash', $this->staff);
        $this->fail('Combined container quantities must not exceed shared stock.');
    } catch (\Illuminate\Validation\ValidationException $exception) {
        expect($exception->errors())->toHaveKey('payment_method');
    }
    expect($order->fresh()->status)->toBe('confirmed')->and((float) $this->water->fresh()->stock_quantity)->toBe(5.0);
    $this->assertDatabaseCount('sales_transactions', 0);
});

test('historical orders without a container choice remain fulfillable after upgrading products', function () {
    $this->water->update(['requires_container_choice' => false]);
    $service = app(DeliveryService::class);
    $order = $service->placeOrder(['product_id' => $this->water->id, 'quantity' => 2, 'customer_address' => 'Address'], $this->customer);
    $this->water->update(['requires_container_choice' => true]);
    $service->updateStatus($order, 'confirmed', $this->staff);
    $service->fulfill($order, 'cash', $this->staff);
    expect($order->fresh()->status)->toBe('delivered');
    $this->assertDatabaseHas('sales_transactions', ['delivery_order_id' => $order->id, 'container_type' => null]);
});
