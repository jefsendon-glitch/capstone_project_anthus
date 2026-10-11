<?php

use App\Models\GallonStock;
use App\Models\GallonCustomerBalance;
use App\Models\Product;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->staffUser = User::factory()->create();
    $this->staffUser->assignRole('staff');
    Staff::factory()->create(['user_id' => $this->staffUser->id]);

    $this->container = Product::factory()->create(['category' => 'container']);
});

test('only admins can add gallon stock', function () {
    $response = $this->actingAs($this->staffUser)->post(route('admin.gallon-stocks.add', $this->container), [
        'status' => 'empty',
        'quantity' => 20,
        'notes' => 'Opening stock count',
    ]);

    $response->assertForbidden();
    expect(GallonStock::where('product_id', $this->container->id)->where('status', 'empty')->first()->quantity)->toBe(0);

    $this->actingAs($this->admin)->post(route('admin.gallon-stocks.add', $this->container), [
        'quantity' => 20,
        'notes' => 'Opening stock count',
    ])->assertRedirect();

    expect(GallonStock::where('product_id', $this->container->id)->where('status', 'empty')->first()->quantity)->toBe(20);
});

test('admin can add a gallon type with opening empty stock', function () {
    $this->actingAs($this->admin)->post(route('admin.gallon-stocks.store'), [
        'gallon_type' => 'Round',
        'size' => '5 Gallons',
        'quantity' => 25,
        'low_stock_threshold' => 8,
        'notes' => 'New supplier delivery',
    ])->assertRedirect();

    $product = Product::where('name', 'Round 5 Gallons')->firstOrFail();
    expect($product->category)->toBe('container');
    expect($product->gallonStocks()->where('status', 'empty')->value('quantity'))->toBe(25);
    $this->assertDatabaseHas('stock_movements', [
        'movable_type' => GallonStock::class,
        'movable_id' => $product->gallonStocks()->where('status', 'empty')->value('id'),
        'type' => 'initial',
        'quantity_delta' => 25,
    ]);
});

test('staff cannot transfer gallon stock but admin can', function () {
    GallonStock::where('product_id', $this->container->id)->where('status', 'empty')->update(['quantity' => 20]);

    $this->actingAs($this->staffUser)->post(route('admin.gallon-stocks.transfer', $this->container), [
        'from_status' => 'empty',
        'to_status' => 'filled',
        'quantity' => 5,
        'notes' => 'Filled for station stock',
    ])->assertForbidden();

    $this->actingAs($this->admin)->post(route('admin.gallon-stocks.transfer', $this->container), [
        'from_status' => 'empty',
        'to_status' => 'filled',
        'quantity' => 5,
        'notes' => 'Filled for station stock',
    ]);

    expect(GallonStock::where('product_id', $this->container->id)->where('status', 'empty')->first()->quantity)->toBe(15);
    expect(GallonStock::where('product_id', $this->container->id)->where('status', 'filled')->first()->quantity)->toBe(5);
});

test('transferring to damaged requires a reason', function () {
    GallonStock::where('product_id', $this->container->id)->where('status', 'filled')->update(['quantity' => 10]);

    $this->actingAs($this->admin)->post(route('admin.gallon-stocks.transfer', $this->container), [
        'from_status' => 'filled',
        'to_status' => 'damaged',
        'quantity' => 2,
    ])->assertSessionHasErrors('notes');

    expect(GallonStock::where('product_id', $this->container->id)->where('status', 'damaged')->first()->quantity)->toBe(0);
});

test('releasing and returning gallons keeps stock and customer balance in sync', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');
    GallonStock::where('product_id', $this->container->id)->where('status', 'filled')->update(['quantity' => 10]);

    $this->actingAs($this->admin)->post(route('admin.gallon-stocks.release', $this->container), [
        'customer_id' => $customer->id,
        'quantity' => 3,
        'notes' => 'Delivery DEL-001',
    ])->assertRedirect();

    expect(GallonStock::where('product_id', $this->container->id)->where('status', 'filled')->value('quantity'))->toBe(7);
    expect(GallonStock::where('product_id', $this->container->id)->where('status', 'customer_owned')->value('quantity'))->toBe(3);
    expect(GallonCustomerBalance::where(['product_id' => $this->container->id, 'customer_id' => $customer->id])->value('quantity'))->toBe(3);

    $this->actingAs($this->admin)->post(route('admin.gallon-stocks.return', $this->container), [
        'customer_id' => $customer->id,
        'return_status' => 'empty',
        'quantity' => 2,
        'notes' => 'Returned in good condition',
    ])->assertRedirect();

    expect(GallonStock::where('product_id', $this->container->id)->where('status', 'customer_owned')->value('quantity'))->toBe(1);
    expect(GallonStock::where('product_id', $this->container->id)->where('status', 'empty')->value('quantity'))->toBe(2);
    expect(GallonCustomerBalance::where(['product_id' => $this->container->id, 'customer_id' => $customer->id])->value('quantity'))->toBe(1);
    $this->assertDatabaseHas('stock_movements', ['type' => 'release', 'recorded_by' => $this->admin->id, 'customer_id' => $customer->id]);
    $this->assertDatabaseHas('stock_movements', ['type' => 'returned', 'recorded_by' => $this->admin->id, 'customer_id' => $customer->id]);
});

test('gallon dashboard tracks returns without double counting station containers', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');
    $this->actingAs($this->admin);

    $this->post(route('admin.gallon-stocks.add', $this->container), ['quantity' => 10])
        ->assertSessionHasNoErrors();
    $this->post(route('admin.gallon-stocks.fill', $this->container), ['quantity' => 6])
        ->assertSessionHasNoErrors();
    $this->post(route('admin.gallon-stocks.release', $this->container), [
        'customer_id' => $customer->id, 'quantity' => 4,
    ])->assertSessionHasNoErrors();

    foreach (['empty', 'damaged'] as $status) {
        $this->post(route('admin.gallon-stocks.return', $this->container), [
            'customer_id' => $customer->id, 'quantity' => 1, 'return_status' => $status,
        ])->assertSessionHasNoErrors();
    }

    foreach (['damaged', 'missing'] as $status) {
        $this->post(route('admin.gallon-stocks.transfer', $this->container), [
            'from_status' => 'empty', 'to_status' => $status,
            'quantity' => 1, 'notes' => 'Physical inspection',
        ])->assertSessionHasNoErrors();
    }

    $this->get(route('admin.inventory.gallon-inventory'))
        ->assertOk()
        ->assertSee('Borrowed Gallons')
        ->assertViewHas('dashboard', fn ($dashboard) =>
            $dashboard['total'] === 10
            && $dashboard['empty'] === 3
            && $dashboard['filled'] === 2
            && $dashboard['withCustomers'] === 2
            && $dashboard['returned'] === 2
            && $dashboard['damaged'] === 2
            && $dashboard['missing'] === 1
        );

    $this->post(route('admin.gallon-stocks.return', $this->container), [
        'customer_id' => $customer->id, 'quantity' => 3, 'return_status' => 'empty',
    ])->assertSessionHasErrors('quantity');
    expect($this->container->gallonStocks()->sum('quantity'))->toEqual(10);
});

test('admin can adjust stock with a reason without allowing negative quantities', function () {
    GallonStock::where('product_id', $this->container->id)->where('status', 'empty')->update(['quantity' => 8]);

    $this->actingAs($this->admin)->post(route('admin.gallon-stocks.adjust', $this->container), [
        'status' => 'empty',
        'adjustment_type' => 'decrease',
        'quantity' => 3,
        'notes' => 'Physical count correction',
    ])->assertRedirect();

    expect(GallonStock::where('product_id', $this->container->id)->where('status', 'empty')->value('quantity'))->toBe(5);
    $this->assertDatabaseHas('stock_movements', ['type' => 'adjustment_decrease', 'quantity_delta' => -3]);

    $this->actingAs($this->admin)->post(route('admin.gallon-stocks.adjust', $this->container), [
        'status' => 'empty',
        'adjustment_type' => 'decrease',
        'quantity' => 6,
        'notes' => 'Should be blocked',
    ])->assertSessionHasErrors('quantity');

    expect(GallonStock::where('product_id', $this->container->id)->where('status', 'empty')->value('quantity'))->toBe(5);
});
