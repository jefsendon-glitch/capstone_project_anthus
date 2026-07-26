<?php

use App\Models\Consumable;
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

test('admin can create a consumable', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.consumables.store'), [
        'name' => 'Carbon Filter',
        'category' => 'water_filters',
        'unit' => 'pcs',
        'quantity' => 20,
        'low_stock_threshold' => 5,
        'unit_cost' => 100,
    ]);

    $response->assertRedirect(route('admin.consumables.index'));
    $this->assertDatabaseHas('consumables', ['name' => 'Carbon Filter']);
});

test('staff cannot create a consumable but can restock one', function () {
    $consumable = Consumable::factory()->create(['quantity' => 10]);

    $this->actingAs($this->staffUser)->post(route('admin.consumables.store'), [
        'name' => 'Should Fail',
        'category' => 'water_filters',
        'unit' => 'pcs',
        'quantity' => 5,
        'low_stock_threshold' => 5,
        'unit_cost' => 10,
    ])->assertForbidden();

    $response = $this->actingAs($this->staffUser)->post(route('admin.consumables.restock', $consumable), [
        'quantity_added' => 15,
    ]);

    $response->assertRedirect();
    expect((float) $consumable->fresh()->quantity)->toBe(25.0);
    $this->assertDatabaseHas('stock_movements', [
        'movable_type' => \App\Models\Consumable::class,
        'movable_id' => $consumable->id,
        'type' => 'restock',
        'quantity_delta' => 15,
    ]);
});

test('staff cannot adjust consumable stock but admin can', function () {
    $consumable = Consumable::factory()->create(['quantity' => 20]);

    $this->actingAs($this->staffUser)->post(route('admin.consumables.stock.adjust', $consumable), [
        'delta' => -5,
        'notes' => 'Damaged in storage',
    ])->assertForbidden();

    $this->actingAs($this->admin)->post(route('admin.consumables.stock.adjust', $consumable), [
        'delta' => -5,
        'notes' => 'Damaged in storage',
    ]);

    expect((float) $consumable->fresh()->quantity)->toBe(15.0);
});
