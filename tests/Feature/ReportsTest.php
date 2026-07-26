<?php

use App\Models\Product;
use App\Models\SalesTransaction;
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
});

test('only admin can view reports', function () {
    $this->actingAs($this->admin)->get(route('admin.reports.index'))->assertOk();
    $this->actingAs($this->staffUser)->get(route('admin.reports.index'))->assertForbidden();
});

test('todays revenue reflects sales transactions recorded today', function () {
    $product = Product::factory()->create(['unit_price' => 50]);

    SalesTransaction::create([
        'transaction_code' => 'TXN-TEST-001',
        'transaction_type' => 'walk-in',
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 2,
        'unit_price' => 50,
        'total_amount' => 100,
        'payment_method' => 'cash',
        'processed_by' => $this->staffUser->id,
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.reports.index'));

    $response->assertOk();
    $response->assertViewHas('revenueToday', 100.0);
});
