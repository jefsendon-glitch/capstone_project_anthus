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
    config()->set('filesystems.disks.product-images.driver', 'local');
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

    expect($product->gallonStocks()->count())->toBe(5);
});

test('opening quantity for a gallon type is recorded as empty gallon stock', function () {
    $this->actingAs($this->admin)->post(route('admin.products.store'), [
        'name' => 'Round 5 Gallon Container',
        'category' => 'container',
        'size' => '5 Gallon',
        'unit_price' => 0,
        'stock_quantity' => 12,
        'low_stock_threshold' => 3,
    ])->assertRedirect(route('admin.products.index'));

    $product = Product::where('name', 'Round 5 Gallon Container')->firstOrFail();

    expect($product->gallonStocks()->where('status', 'empty')->value('quantity'))->toBe(12);
    $this->assertDatabaseHas('stock_movements', [
        'movable_type' => \App\Models\GallonStock::class,
        'movable_id' => $product->gallonStocks()->where('status', 'empty')->value('id'),
        'type' => 'initial',
        'quantity_delta' => 12,
    ]);
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

test('a failed image upload does not create a product', function () {
    $disk = Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
    $disk->shouldReceive('putFileAs')->once()->andReturn(false);
    Storage::shouldReceive('disk')->with('public')->andReturn($disk);

    $this->actingAs($this->admin)->post(route('admin.products.store'), [
        'name' => 'Upload failure', 'category' => 'other', 'size' => '1 piece',
        'unit_price' => 25, 'stock_quantity' => 0, 'low_stock_threshold' => 0,
        'image' => UploadedFile::fake()->image('water.jpg'),
    ])->assertSessionHasErrors('image');

    $this->assertDatabaseMissing('products', ['name' => 'Upload failure']);
});

test('a failed replacement upload keeps the existing product image', function () {
    $storage = Storage::fake('public');
    $storage->put('products/original.jpg', 'original image');
    $product = Product::factory()->create(['category' => 'other', 'image_path' => 'products/original.jpg']);
    $disk = Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
    $disk->shouldReceive('putFileAs')->once()->andReturn(false);
    $disk->shouldNotReceive('delete');
    Storage::shouldReceive('disk')->with('public')->andReturn($disk);

    $this->actingAs($this->admin)->put(route('admin.products.update', $product), [
        'name' => $product->name, 'category' => 'other', 'size' => $product->size,
        'unit_price' => 25, 'stock_quantity' => 0, 'low_stock_threshold' => 0,
        'image' => UploadedFile::fake()->image('replacement.jpg'),
    ])->assertSessionHasErrors('image');

    expect($product->fresh()->image_path)->toBe('products/original.jpg');
    $storage->assertExists('products/original.jpg');
});

test('replacing a product image saves and serves the new image before removing the old one', function () {
    $storage = Storage::fake('public');
    $storage->put('products/original.jpg', 'original image');
    $product = Product::factory()->create(['category' => 'other', 'image_path' => 'products/original.jpg']);

    $this->actingAs($this->admin)->put(route('admin.products.update', $product), [
        'name' => $product->name, 'category' => 'other', 'size' => $product->size,
        'unit_price' => 25, 'stock_quantity' => 0, 'low_stock_threshold' => 0,
        'image' => UploadedFile::fake()->image('replacement.webp'),
    ])->assertSessionHasNoErrors()->assertRedirect(route('admin.products.index'));

    $product->refresh();
    expect($product->image_path)->not->toBe('products/original.jpg');
    $storage->assertExists($product->image_path);
    $storage->assertMissing('products/original.jpg');
    $this->get($product->image_url)->assertOk();
});

test('private cloud product images are streamed without a public bucket URL', function () {
    config()->set('filesystems.product_image_disk', 'private-images');
    config()->set('filesystems.disks.private-images.driver', 's3');
    $product = Product::factory()->create(['image_path' => 'products/private.jpg']);
    $disk = Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
    $disk->shouldReceive('exists')->with($product->image_path)->once()->andReturn(true);
    $disk->shouldReceive('response')->with($product->image_path)->once()->andReturn(
        response()->stream(fn () => print('private image bytes'), 200, ['Content-Type' => 'image/jpeg'])
    );
    $disk->shouldNotReceive('url');
    Storage::shouldReceive('disk')->with('private-images')->andReturn($disk);

    $this->get($product->image_url)->assertOk()->assertHeader('Content-Type', 'image/jpeg')
        ->assertStreamedContent('private image bytes');
});

test('an image storage exception is reported as an upload error', function () {
    $disk = Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
    $disk->shouldReceive('putFileAs')->once()->andThrow(
        \League\Flysystem\UnableToWriteFile::atLocation('products/upload.jpg')
    );
    Storage::shouldReceive('disk')->with('public')->andReturn($disk);

    $this->actingAs($this->admin)->post(route('admin.products.store'), [
        'name' => 'Storage exception', 'category' => 'other', 'size' => '1 piece',
        'unit_price' => 25, 'stock_quantity' => 0, 'low_stock_threshold' => 0,
        'image' => UploadedFile::fake()->image('water.jpg'),
    ])->assertSessionHasErrors('image');

    $this->assertDatabaseMissing('products', ['name' => 'Storage exception']);
});
