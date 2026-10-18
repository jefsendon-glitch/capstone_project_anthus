<?php

use App\Models\Product;
use App\Models\User;
use App\Services\DeliveryService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
    $this->water = Product::factory()->create(['name' => 'Alkaline Water', 'category' => 'alkaline',
        'requires_container_choice' => true, 'stock_quantity' => 0, 'unit_price' => 30]);
    $this->actingAs($this->admin);
    foreach (['Round Gallon', 'Slim Gallon'] as $type) {
        $this->post(route('admin.inventory.gallon-inventory.store'), ['name' => 'Station container',
            'type' => $type, 'size' => '5 Gallon', 'quantity' => 20])->assertSessionHasNoErrors();
    }
    $this->fill = function (string $type, int $quantity) {
        return $this->post(route('admin.inventory.gallon-inventory.move'), [
            'action' => 'fill', 'gallon_id' => DB::table('station_gallons')->where('type', $type)->value('id'),
            'product_id' => $this->water->id, 'quantity' => $quantity,
        ])->assertSessionHasNoErrors();
    };
});

test('alkaline slim filling and pos sales update only alkaline slim stock', function () {
    $this->water->update(['requires_container_choice' => false]);
    $other = Product::factory()->create(['category' => 'purified', 'requires_container_choice' => true,
        'round_stock' => 4, 'slim_stock' => 7, 'stock_quantity' => 11]);
    ($this->fill)('Slim Gallon', 8);
    expect($this->water->fresh()->requires_container_choice)->toBeTrue();
    ($this->fill)('Round Gallon', 5);
    expect((int) $this->water->fresh()->slim_stock)->toBe(8);
    expect((int) $this->water->fresh()->round_stock)->toBe(5);
    $this->post(route('pos.store'), ['transaction_type' => 'walk-in', 'payment_method' => 'cash', 'tendered_amount' => 100,
        'items' => json_encode([['product_id' => $this->water->id, 'container_type' => 'slim', 'quantity' => 3]])])
        ->assertRedirect()->assertSessionHasNoErrors();
    expect((int) $this->water->fresh()->slim_stock)->toBe(5);
    expect((int) $this->water->fresh()->round_stock)->toBe(5);
    expect($this->water->fresh()->stock_quantity)->toBe(10);
    expect($other->fresh()->stock_quantity)->toBe(11);
    expect((int) $other->fresh()->slim_stock)->toBe(7);
    $this->get(route('pos.create'))->assertOk()->assertViewHas('productsForCart', fn ($products) =>
        $products->firstWhere('id', $this->water->id)['slim_stock'] === 5);
});

test('customer slim order cannot use round stock and delivery deducts only slim once', function () {
    ($this->fill)('Round Gallon', 10);
    ($this->fill)('Slim Gallon', 3);
    $this->actingAs($this->customer);
    $data = ['customer_address' => 'Complete delivery address', 'payment_method' => 'cash',
        'items' => [['product_id' => $this->water->id, 'quantity' => 4, 'container_type' => 'slim']]];
    $this->post(route('customer.orders.store'), $data)->assertSessionHasErrors('items');
    $data['items'][0]['quantity'] = 2;
    $this->post(route('customer.orders.store'), $data)->assertSessionHasNoErrors();
    $order = $this->customer->deliveryOrders()->firstOrFail();
    expect((int) $this->water->fresh()->slim_stock)->toBe(3);
    $service = app(DeliveryService::class);
    $service->updateStatus($order, 'confirmed', $this->admin);
    $service->fulfill($order, 'cash', $this->admin);
    expect((int) $this->water->fresh()->slim_stock)->toBe(1);
    expect((int) $this->water->fresh()->round_stock)->toBe(10);
    expect($this->water->fresh()->stock_quantity)->toBe(11);
    $this->get(route('customer.orders.create'))->assertOk()->assertViewHas('productsForOrder', fn ($products) =>
        $products->firstWhere('id', $this->water->id)['slim_stock'] === 1);
});

test('delivery rechecks each container balance and rolls back all lines on shortage', function () {
    ($this->fill)('Round Gallon', 10);
    ($this->fill)('Slim Gallon', 3);
    $service = app(DeliveryService::class);
    $order = $service->placeOrder(['customer_address' => 'Address', 'payment_method' => 'cash', 'items' => [
        ['product_id' => $this->water->id, 'quantity' => 2, 'container_type' => 'round'],
        ['product_id' => $this->water->id, 'quantity' => 3, 'container_type' => 'slim'],
    ]], $this->customer);
    $service->updateStatus($order, 'confirmed', $this->admin);
    $this->water->update(['slim_stock' => 1, 'stock_quantity' => 11]);
    try {
        $service->fulfill($order, 'cash', $this->admin);
        $this->fail('Slim shortage must block the entire delivery.');
    } catch (\Illuminate\Validation\ValidationException $exception) {
        expect($exception->errors())->toHaveKey('payment_method');
    }
    expect((int) $this->water->fresh()->round_stock)->toBe(10);
    $this->assertDatabaseCount('sales_transactions', 0);
    expect($order->fresh()->status)->toBe('confirmed');
});

test('admin can allocate existing stock in the edit form and set opening balances at registration', function () {
    $this->water->update(['stock_quantity' => 12]);
    $this->post(route('pos.store'), ['transaction_type' => 'walk-in', 'payment_method' => 'cash', 'tendered_amount' => 100,
        'items' => json_encode([['product_id' => $this->water->id, 'quantity' => 1, 'container_type' => 'slim']])])
        ->assertSessionHasErrors('items');
    $this->patch(route('admin.products.update', $this->water), ['name' => 'Alkaline Water', 'size' => '5 Gallon',
        'unit_price' => 30, 'round_stock' => 5, 'slim_stock' => 7, 'low_stock_threshold' => 2, 'is_active' => 1])
        ->assertSessionHasNoErrors();
    expect($this->water->fresh()->stock_quantity)->toBe(12);
    expect((int) $this->water->fresh()->slim_stock)->toBe(7);
    expect($this->water->fresh()->availableStock(null))->toBe(0);
    $this->assertDatabaseHas('stock_movements', ['movable_id' => $this->water->id, 'quantity_delta' => 0]);
    $this->post(route('admin.products.store'), ['product_kind' => 'water', 'name' => 'Distilled Water',
        'size' => '5 Gallon', 'unit_price' => 35, 'round_stock' => 4, 'slim_stock' => 6,
        'low_stock_threshold' => 2, 'is_active' => 1])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('products', ['name' => 'Distilled Water', 'round_stock' => 4, 'slim_stock' => 6, 'stock_quantity' => 10]);
});
