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

test('only admins can manage consumables', function () {
    $consumable = Consumable::factory()->create(['quantity' => 10]);

    $this->actingAs($this->staffUser)->post(route('admin.consumables.store'), [
        'name' => 'Should Fail',
        'category' => 'water_filters',
        'unit' => 'pcs',
        'quantity' => 5,
        'low_stock_threshold' => 5,
        'unit_cost' => 10,
    ])->assertForbidden();

    $this->actingAs($this->staffUser)->post(route('admin.consumables.restock', $consumable), [
        'quantity_added' => 15,
    ])->assertForbidden();
});

test('staff cannot adjust consumable stock but admin can', function () {
    $consumable = Consumable::factory()->create(['quantity' => 20]);

    $this->actingAs($this->staffUser)->post(route('admin.consumables.stock.adjust', $consumable), [
        'adjustment_type' => 'decrease',
        'quantity' => 5,
        'notes' => 'Damaged in storage',
    ])->assertForbidden();

    $this->actingAs($this->admin)->post(route('admin.consumables.stock.adjust', $consumable), [
        'adjustment_type' => 'decrease',
        'quantity' => 5,
        'notes' => 'Damaged in storage',
    ]);

    expect((float) $consumable->fresh()->quantity)->toBe(15.0);
    foreach (['increase', 'decrease'] as $direction) {
        $this->post(route('admin.consumables.quick-update', $consumable), [
            'field' => 'quantity', 'adjustment_type' => $direction,
            'value' => 1.5, 'notes' => 'Fractional count',
        ])->assertSessionHasErrors('value');
        $this->post(route('admin.consumables.stock.adjust', $consumable), [
            'adjustment_type' => $direction, 'quantity' => 1.5, 'notes' => 'Fractional count',
        ])->assertSessionHasErrors('quantity');
    }
    expect((float) $consumable->fresh()->quantity)->toBe(15.0);
});

test('admin can permanently delete a consumable', function () {
    $consumable = Consumable::factory()->create();

    $this->actingAs($this->admin)->delete(route('admin.consumables.destroy', $consumable))
        ->assertRedirect(route('admin.consumables.index'));

    $this->assertDatabaseMissing('consumables', ['id' => $consumable->id]);
    $this->actingAs($this->admin)->get(route('admin.consumables.index'))
        ->assertOk()->assertDontSee('Archived')->assertDontSee('Restore supply');
});

test('staff cannot permanently delete supplies', function () {
    $consumable = Consumable::factory()->create();

    $this->actingAs($this->staffUser)->delete(route('admin.consumables.destroy', $consumable))
        ->assertForbidden();

    $this->assertDatabaseHas('consumables', ['id' => $consumable->id, 'deleted_at' => null]);
});

test('purchase order references protect supplies from permanent deletion', function () {
    $consumable = Consumable::factory()->create();
    $supplier = \App\Models\Supplier::create(['name' => 'Supply Deletion Test Supplier']);
    $order = \App\Models\PurchaseOrder::create([
        'po_number' => 'PO-DELETE-TEST', 'supplier_id' => $supplier->id,
        'status' => 'ordered', 'ordered_at' => now()->toDateString(), 'created_by' => $this->admin->id,
    ]);
    $order->items()->create([
        'itemable_type' => $consumable->getMorphClass(), 'itemable_id' => $consumable->id,
        'quantity_ordered' => 5, 'quantity_received' => 0, 'unit_cost' => 10,
    ]);

    $this->actingAs($this->admin)->from(route('admin.consumables.index'))
        ->delete(route('admin.consumables.destroy', $consumable))->assertSessionHas('error');

    $this->assertDatabaseHas('consumables', ['id' => $consumable->id, 'deleted_at' => null]);
});

test('consumable stock report only shows the selected stock status', function () {
    Consumable::factory()->create([
        'name' => 'Available Filter',
        'quantity' => 10,
        'low_stock_threshold' => 5,
    ]);
    Consumable::factory()->create([
        'name' => 'Reorder Caps',
        'quantity' => 5,
        'low_stock_threshold' => 5,
    ]);
    Consumable::factory()->create([
        'name' => 'Empty Sanitizer',
        'quantity' => 0,
        'low_stock_threshold' => 5,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.consumables.report', ['status' => 'low_stock']))
        ->assertOk()
        ->assertSee('Low-stock items')
        ->assertSee('Reorder Caps')
        ->assertDontSee('Available Filter')
        ->assertDontSee('Empty Sanitizer');

    $this->actingAs($this->admin)
        ->get(route('admin.consumables.report', ['status' => 'out_of_stock']))
        ->assertOk()
        ->assertSee('Empty Sanitizer')
        ->assertDontSee('Available Filter')
        ->assertDontSee('Reorder Caps');

    $this->actingAs($this->admin)
        ->get(route('admin.consumables.report', ['status' => 'in_stock']))
        ->assertOk()
        ->assertSee('Available Filter')
        ->assertDontSee('Reorder Caps')
        ->assertDontSee('Empty Sanitizer');
});

test('admin can quick-update stock and decimal consumable costs', function () {
    $consumable = Consumable::factory()->create(['quantity' => 10, 'low_stock_threshold' => 5, 'unit_cost' => 12]);

    $this->actingAs($this->admin)->post(route('admin.consumables.quick-update', $consumable), [
        'field' => 'quantity',
        'adjustment_type' => 'increase',
        'value' => 5,
        'notes' => 'Counted new delivery',
    ])->assertSessionHasNoErrors();

    expect((float) $consumable->fresh()->quantity)->toBe(15.0);
    $this->assertDatabaseHas('stock_movements', [
        'movable_type' => Consumable::class,
        'movable_id' => $consumable->id,
        'quantity_delta' => 5,
        'notes' => 'Counted new delivery',
    ]);

    $this->actingAs($this->admin)->post(route('admin.consumables.quick-update', $consumable), [
        'field' => 'unit_cost',
        'value' => 12.5,
    ])->assertSessionHasNoErrors();
    expect((float) $consumable->fresh()->unit_cost)->toBe(12.5);
});
