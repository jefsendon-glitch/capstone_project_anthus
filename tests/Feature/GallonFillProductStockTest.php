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
    $this->water = Product::factory()->create(['name' => 'Purified Water', 'category' => 'purified',
        'stock_quantity' => 0, 'unit_price' => 25, 'is_active' => true, 'requires_container_choice' => true]);
    $this->actingAs($this->admin)->post(route('admin.inventory.gallon-inventory.store'), [
        'name' => 'Station container', 'type' => 'Round Gallon', 'size' => '5 gallons', 'quantity' => 10,
    ])->assertSessionHasNoErrors();
    $this->gallonId = DB::table('station_gallons')->value('id');
    $this->fill = ['gallon_id' => $this->gallonId, 'action' => 'fill', 'quantity' => 6, 'product_id' => $this->water->id];
});

test('filling credits the selected product and a mixed container sale deducts shared stock', function () {
    $other = Product::factory()->create(['category' => 'alkaline', 'stock_quantity' => 7]);
    $this->post(route('admin.inventory.gallon-inventory.move'), $this->fill)->assertSessionHasNoErrors();
    expect($this->water->fresh()->stock_quantity)->toBe(6);
    expect($other->fresh()->stock_quantity)->toBe(7);
    $this->assertDatabaseHas('station_gallons', ['id' => $this->gallonId, 'empty' => 4, 'filled' => 6]);
    $this->assertDatabaseHas('water_production_logs', ['product_id' => $this->water->id, 'gallons_produced' => 6]);
    $this->assertDatabaseHas('stock_movements', ['movable_id' => $this->water->id, 'movable_type' => Product::class,
        'type' => 'production', 'quantity_before' => 0, 'quantity_after' => 6, 'quantity_delta' => 6]);
    $this->post(route('pos.store'), ['transaction_type' => 'walk-in', 'payment_method' => 'cash', 'tendered_amount' => 75, 'items' => json_encode([
        ['product_id' => $this->water->id, 'container_type' => 'round', 'quantity' => 1],
        ['product_id' => $this->water->id, 'container_type' => 'slim', 'quantity' => 2],
    ])])->assertRedirect()->assertSessionHasNoErrors();
    expect($this->water->fresh()->stock_quantity)->toBe(3);
    $this->get(route('admin.water-production.index').'?search=Purified')->assertOk()->assertSee('Purified Water');
});

test('invalid fill product or insufficient empty gallons leaves all stock and logs unchanged', function () {
    $invalidProducts = [null, 999999,
        Product::factory()->create(['category' => 'container'])->id,
        Product::factory()->create(['category' => 'alkaline', 'is_active' => false])->id];
    $archived = Product::factory()->create(['category' => 'purified']);
    $archived->delete();
    $invalidProducts[] = $archived->id;
    foreach ($invalidProducts as $id) {
        $this->post(route('admin.inventory.gallon-inventory.move'), array_replace($this->fill, ['product_id' => $id]))
            ->assertSessionHasErrors('product_id');
    }
    $this->post(route('admin.inventory.gallon-inventory.move'), array_replace($this->fill, ['quantity' => 11]))
        ->assertSessionHasErrors('quantity');
    expect($this->water->fresh()->stock_quantity)->toBe(0);
    $this->assertDatabaseHas('station_gallons', ['id' => $this->gallonId, 'empty' => 10, 'filled' => 0]);
    $this->assertDatabaseCount('water_production_logs', 0);
    $this->assertDatabaseCount('station_gallon_movements', 1);
    $this->assertDatabaseCount('stock_movements', 0);
});

test('delivery deducts filled product stock only when delivered and cannot deduct twice', function () {
    $this->post(route('admin.inventory.gallon-inventory.move'), $this->fill)->assertSessionHasNoErrors();
    $customer = User::factory()->create();
    $customer->assignRole('customer');
    $service = app(DeliveryService::class);
    $order = $service->placeOrder(['items' => [['product_id' => $this->water->id, 'quantity' => 2, 'container_type' => 'round']],
        'customer_address' => 'Test address', 'payment_method' => 'cash'], $customer);
    expect($this->water->fresh()->stock_quantity)->toBe(6);
    $service->updateStatus($order, 'confirmed', $this->admin);
    $service->fulfill($order, 'cash', $this->admin);
    expect($this->water->fresh()->stock_quantity)->toBe(4);
    try {
        $service->fulfill($order, 'cash', $this->admin);
        $this->fail('A delivered order must not deduct stock again.');
    } catch (\Illuminate\Validation\ValidationException $exception) {
        expect($this->water->fresh()->stock_quantity)->toBe(4);
    }
});
