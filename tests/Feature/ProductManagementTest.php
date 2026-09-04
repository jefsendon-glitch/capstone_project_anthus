<?php

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('admin can create a product', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.products.store'), [
        'name' => 'Purified Water',
        'category' => 'purified',
        'size' => '5 Gallon',
        'unit_price' => 25,
        'stock_quantity' => 100,
        'low_stock_threshold' => 10,
        'is_active' => '1',
    ]);

    $response->assertRedirect(route('admin.products.index'));
    $this->assertDatabaseHas('products', ['name' => 'Purified Water', 'size' => '5 Gallon']);
});

test('product photos are saved to the configured image disk', function () {
    Storage::fake('product-images');
    config()->set('filesystems.product_image_disk', 'product-images');

    $this->actingAs($this->admin)->post(route('admin.products.store'), [
        'name' => 'Purified Water',
        'category' => 'purified',
        'size' => '5 Gallon',
        'unit_price' => 25,
        'stock_quantity' => 100,
        'low_stock_threshold' => 10,
        'is_active' => '1',
        'image' => UploadedFile::fake()->image('water.jpg'),
    ])->assertRedirect(route('admin.products.index'));

    $product = Product::where('name', 'Purified Water')->firstOrFail();

    expect($product->image_path)->toStartWith('products/');
    Storage::disk('product-images')->assertExists($product->image_path);
    $this->get($product->image_url)->assertOk();
});

test('a product with stock at or below its threshold is flagged low stock', function () {
    $product = Product::factory()->create(['stock_quantity' => 5, 'low_stock_threshold' => 10]);

    expect($product->is_low_stock)->toBeTrue();
});

test('admin can update a product', function () {
    $product = Product::factory()->create(['unit_price' => 20]);

    $this->actingAs($this->admin)->put(route('admin.products.update', $product), [
        'name' => $product->name,
        'category' => $product->category,
        'size' => $product->size,
        'unit_price' => 30,
        'stock_quantity' => $product->stock_quantity,
        'low_stock_threshold' => $product->low_stock_threshold,
    ]);

    expect((float) $product->fresh()->unit_price)->toBe(30.0);
});

test('creating a container product automatically provisions its gallon stock rows', function () {
    $product = Product::factory()->create(['category' => 'container']);

    expect($product->gallonStocks()->count())->toBe(7);
});

test('admin can archive and restore a product', function () {
    $product = Product::factory()->create();

    $this->actingAs($this->admin)->delete(route('admin.products.destroy', $product));
    $this->assertSoftDeleted('products', ['id' => $product->id]);

    $this->actingAs($this->admin)->post(route('admin.products.restore', $product->id));
    $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
});

test('admin can permanently delete an archived product with no historical records', function () {
    $product = Product::factory()->create(['category' => 'purified']);

    $this->actingAs($this->admin)->delete(route('admin.products.destroy', $product));
    $this->actingAs($this->admin)->delete(route('admin.products.force-delete', $product->id))
        ->assertRedirect(route('admin.products.index', ['archived' => 1]));

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

test('admin can add stock to a product with an audit trail', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);

    $this->actingAs($this->admin)->post(route('admin.products.stock.add', $product), ['quantity' => 5]);

    expect($product->fresh()->stock_quantity)->toBe(15);
    $this->assertDatabaseHas('stock_movements', [
        'movable_type' => \App\Models\Product::class,
        'movable_id' => $product->id,
        'type' => 'restock',
        'quantity_delta' => 5,
    ]);
});
