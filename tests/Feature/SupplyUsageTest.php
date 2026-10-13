<?php

use App\Models\Consumable;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('admin records multiple supplies and procurement quantities decrease', function () {
    $supplies = Consumable::factory()->count(2)->create(['quantity' => 10]);
    $this->actingAs($this->admin)->get(route('admin.inventory.supply-usages.index'))->assertOk()->assertSee('Supplies Usage');
    $this->post(route('admin.inventory.supply-usages.store'), [
        'activity' => 'Packaging & Sealing', 'operation_date' => now()->toDateString(),
        'items' => $supplies->map(fn ($supply) => ['consumable_id' => $supply->id, 'quantity' => 2.25])->all(),
    ])->assertSessionHasNoErrors()->assertRedirect(route('admin.inventory.supply-usages.index'));
    foreach ($supplies as $supply) {
        expect((float) $supply->fresh()->quantity)->toBe(7.75);
        $this->assertDatabaseHas('stock_movements', ['movable_id' => $supply->id, 'type' => 'operation_usage', 'quantity_delta' => -2.25]);
    }
    $this->assertDatabaseHas('supply_usages', ['operation' => 'Packaging & Sealing', 'recorded_by' => $this->admin->id]);
    $this->get(route('admin.inventory.supply-usages.index'))->assertOk()->assertSee('Packaging & Sealing');
});

test('insufficient stock rejects the entire operation without any deductions', function () {
    $first = Consumable::factory()->create(['quantity' => 10]);
    $second = Consumable::factory()->create(['quantity' => 1]);
    $this->actingAs($this->admin)->post(route('admin.inventory.supply-usages.store'), [
        'activity' => 'Cleaning & Sanitation', 'operation_date' => now()->toDateString(),
        'items' => [['consumable_id' => $first->id, 'quantity' => 2], ['consumable_id' => $second->id, 'quantity' => 2]],
    ])->assertSessionHasErrors('items.1.quantity');
    expect((float) $first->fresh()->quantity)->toBe(10.0);
    $this->assertDatabaseCount('supply_usages', 0);
    $this->assertDatabaseCount('stock_movements', 0);
});

test('duplicate supplies and invalid quantities cannot deduct stock', function () {
    $supply = Consumable::factory()->create(['quantity' => 10]);
    $this->actingAs($this->admin)->post(route('admin.inventory.supply-usages.store'), [
        'activity' => 'Water Production', 'operation_date' => now()->toDateString(),
        'items' => [['consumable_id' => $supply->id, 'quantity' => -1], ['consumable_id' => $supply->id, 'quantity' => 1.001]],
    ])->assertSessionHasErrors();
    expect((float) $supply->fresh()->quantity)->toBe(10.0);
});

test('staff cannot access or record operation usage', function () {
    $user = User::factory()->create();
    $user->assignRole('staff');
    $this->actingAs($user)->get(route('admin.inventory.supply-usages.index'))->assertForbidden();
    $this->post(route('admin.inventory.supply-usages.store'), [])->assertForbidden();
});

test('other activity saves the operation entered by admin and deducts stock', function () {
    $supply = Consumable::factory()->create(['quantity' => 10]);
    $this->actingAs($this->admin)->post(route('admin.inventory.supply-usages.store'), [
        'activity' => 'Other', 'other_operation' => 'Station opening preparation',
        'operation_date' => now()->toDateString(),
        'items' => [['consumable_id' => $supply->id, 'quantity' => 2]],
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('supply_usages', ['operation' => 'Station opening preparation']);
    expect((float) $supply->fresh()->quantity)->toBe(8.0);
});

test('other activity requires an operation and unknown activities are rejected', function () {
    $supply = Consumable::factory()->create(['quantity' => 10]);
    $payload = [
        'activity' => 'Other', 'other_operation' => '   ',
        'operation_date' => now()->toDateString(),
        'items' => [['consumable_id' => $supply->id, 'quantity' => 2]],
    ];
    $this->actingAs($this->admin)->post(route('admin.inventory.supply-usages.store'), $payload)
        ->assertSessionHasErrors('other_operation');
    $payload['activity'] = 'Unknown activity';
    $this->post(route('admin.inventory.supply-usages.store'), $payload)
        ->assertSessionHasErrors('activity');

    $this->assertDatabaseCount('supply_usages', 0);
    expect((float) $supply->fresh()->quantity)->toBe(10.0);
});
