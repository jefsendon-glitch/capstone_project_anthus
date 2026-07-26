<?php

use App\Models\Consumable;
use App\Models\StockMovement;
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

test('admin and staff can both view stock movement history', function () {
    $consumable = Consumable::factory()->create();
    StockMovement::record($consumable, 'restock', 5, 10, 15, $this->admin->id);

    $this->actingAs($this->admin)->get(route('admin.stock-movements.index'))->assertOk()->assertSee('Restock');
    $this->actingAs($this->staffUser)->get(route('admin.stock-movements.index'))->assertOk();
});

test('stock movements can be filtered by item type', function () {
    $consumable = Consumable::factory()->create();
    StockMovement::record($consumable, 'restock', 5, 10, 15, $this->admin->id);

    $response = $this->actingAs($this->admin)->get(route('admin.stock-movements.index', ['item_type' => 'consumable']));

    $response->assertOk();
});
