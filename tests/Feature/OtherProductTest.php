<?php

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->actingAs($this->admin);
});

test('admin can create edit and sell a non-water product without container choices', function () {
    $this->get(route('admin.products.create'))->assertOk()->assertSee('Other Product')->assertSee('Product Name');
    $data = ['product_kind' => 'other', 'name' => 'Dispenser Tap', 'size' => '1 pc', 'unit_price' => 40,
        'stock_quantity' => 10, 'low_stock_threshold' => 2, 'is_active' => 1];
    $this->post(route('admin.products.store'), $data)->assertSessionHasNoErrors();
    $product = Product::where('name', 'Dispenser Tap')->firstOrFail();
    expect($product->category)->toBe('other');
    expect($product->requires_container_choice)->toBeFalse();
    expect($product->stock_unit_label)->toBe('pcs');
    $this->assertDatabaseCount('gallon_stocks', 0);
    $this->get(route('admin.products.edit', $product))->assertOk()->assertSee('Dispenser Tap');
    $this->patch(route('admin.products.update', $product), array_replace($data, ['category' => 'other', 'unit_price' => 45]))
        ->assertSessionHasNoErrors();
    $this->post(route('pos.store'), ['transaction_type' => 'walk-in', 'payment_method' => 'cash', 'tendered_amount' => 100,
        'items' => json_encode([['product_id' => $product->id, 'quantity' => 2]])])->assertRedirect()->assertSessionHasNoErrors();
    expect($product->fresh()->stock_quantity)->toBe(8);
    $this->assertDatabaseHas('sales_transactions', ['product_id' => $product->id, 'quantity' => 2,
        'total_amount' => 90, 'container_type' => null]);
    $this->get(route('admin.inventory.gallon-inventory'))->assertOk()
        ->assertViewHas('waterProducts', fn ($products) => ! $products->contains('id', $product->id));
});

test('other product registration validates stock and rejects duplicate items', function () {
    $data = ['product_kind' => 'other', 'name' => 'Paper Cups', 'size' => '1 pack', 'unit_price' => 20,
        'stock_quantity' => 5, 'low_stock_threshold' => 1, 'is_active' => 1];
    $this->post(route('admin.products.store'), $data)->assertSessionHasNoErrors();
    $this->post(route('admin.products.store'), $data)->assertSessionHasErrors('name');
    $this->post(route('admin.products.store'), array_replace($data, ['name' => '', 'stock_quantity' => 1.5]))
        ->assertSessionHasErrors(['name', 'stock_quantity']);
    $this->assertDatabaseCount('products', 1);
});
