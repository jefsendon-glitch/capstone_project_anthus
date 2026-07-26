<?php

use App\Models\GallonStock;
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

test('staff can add gallon stock', function () {
    $response = $this->actingAs($this->staffUser)->post(route('admin.gallon-stocks.add', $this->container), [
        'status' => 'company_owned',
        'quantity' => 20,
    ]);

    $response->assertRedirect();
    expect(GallonStock::where('product_id', $this->container->id)->where('status', 'company_owned')->first()->quantity)->toBe(20);
});

test('staff cannot transfer gallon stock but admin can', function () {
    GallonStock::where('product_id', $this->container->id)->where('status', 'company_owned')->update(['quantity' => 20]);

    $this->actingAs($this->staffUser)->post(route('admin.gallon-stocks.transfer', $this->container), [
        'from_status' => 'company_owned',
        'to_status' => 'filled',
        'quantity' => 5,
    ])->assertForbidden();

    $this->actingAs($this->admin)->post(route('admin.gallon-stocks.transfer', $this->container), [
        'from_status' => 'company_owned',
        'to_status' => 'filled',
        'quantity' => 5,
    ]);

    expect(GallonStock::where('product_id', $this->container->id)->where('status', 'company_owned')->first()->quantity)->toBe(15);
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
