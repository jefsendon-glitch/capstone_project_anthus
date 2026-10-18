<?php

use App\Models\Product;
use App\Models\User;
use App\Services\DeliveryService;
use Database\Seeders\RoleSeeder;

test('alkaline replaces mineral in product registration and supports filling gallons', function () {
    $this->seed(RoleSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get(route('admin.products.create'))->assertOk()
        ->assertSee('Alkaline Water')->assertDontSee('Mineral Water');
    $this->post(route('admin.products.store'), ['name' => 'Alkaline Water', 'size' => '5 Gallon',
        'unit_price' => 30, 'stock_quantity' => 0, 'low_stock_threshold' => 2, 'is_active' => 1])->assertSessionHasNoErrors();
    $water = Product::where('name', 'Alkaline Water')->firstOrFail();
    expect($water->category)->toBe('alkaline');
    expect($water->requires_container_choice)->toBeTrue();
    $this->post(route('admin.inventory.gallon-inventory.store'), ['name' => 'Station container',
        'type' => 'Round Gallon', 'size' => '5 Gallon', 'quantity' => 5])->assertSessionHasNoErrors();
    $this->post(route('admin.inventory.gallon-inventory.move'), [
        'gallon_id' => \Illuminate\Support\Facades\DB::table('station_gallons')->value('id'),
        'action' => 'fill', 'quantity' => 3, 'product_id' => $water->id,
    ])->assertSessionHasNoErrors();
    expect($water->fresh()->stock_quantity)->toBe(3);
    $this->post(route('admin.products.store'), ['name' => 'Mineral Water', 'category' => 'mineral',
        'size' => '5 Gallon', 'unit_price' => 30, 'stock_quantity' => 0, 'low_stock_threshold' => 2])
        ->assertSessionHasErrors('category');
});

test('mineral conversion preserves stock price identity and historical order labels', function () {
    $this->seed(RoleSeeder::class);
    $customer = User::factory()->create();
    $customer->assignRole('customer');
    $mineral = Product::factory()->create(['name' => 'Mineral Water', 'category' => 'mineral',
        'stock_quantity' => 12, 'unit_price' => 30, 'requires_container_choice' => false]);
    $order = app(DeliveryService::class)->placeOrder(['items' => [['product_id' => $mineral->id, 'quantity' => 2]],
        'customer_address' => 'Test address', 'payment_method' => 'cash'], $customer);
    $migration = require database_path('migrations/2026_10_18_230000_replace_mineral_products_with_alkaline.php');
    $migration->up();
    $mineral->refresh();
    expect($mineral->name)->toBe('Alkaline Water');
    expect($mineral->category)->toBe('alkaline');
    expect($mineral->requires_container_choice)->toBeTrue();
    expect($mineral->stock_quantity)->toBe(12);
    expect((float) $mineral->unit_price)->toBe(30.0);
    expect($order->items()->first()->product_name)->toBe('Mineral Water');
    expect($order->items()->first()->product_id)->toBe($mineral->id);
    $migration->up();
    expect($mineral->fresh()->stock_quantity)->toBe(12);
});
