<?php

use App\Models\Consumable;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->supplier = Supplier::create(['name' => 'Reset Supplier']);
    $this->supply = Consumable::factory()->create(['supplier_id' => $this->supplier->id, 'quantity' => 15]);
    foreach (['draft', 'partially_received'] as $status) {
        $order = PurchaseOrder::create(['po_number' => 'PO-RESET-'.$status, 'supplier_id' => $this->supplier->id, 'status' => $status]);
        $order->items()->create(['itemable_type' => $this->supply->getMorphClass(), 'itemable_id' => $this->supply->id, 'quantity_ordered' => 10, 'quantity_received' => $status === 'draft' ? 0 : 5, 'unit_cost' => 10]);
    }
});

test('admin reset removes all orders and items with a backup without reversing received stock', function () {
    Storage::fake('local');
    $movement = StockMovement::record($this->supply, 'purchase_receive', 5, 10, 15, $this->admin->id);
    $this->actingAs($this->admin)->get(route('admin.purchase-orders.index'))->assertOk()->assertSee('Reset Purchase Orders');
    $this->delete(route('admin.purchase-orders.reset'), ['reset_confirmation' => 'RESET'])
        ->assertRedirect(route('admin.purchase-orders.index'))->assertSessionHas('success');
    $this->assertDatabaseCount('purchase_orders', 0);
    $this->assertDatabaseCount('purchase_order_items', 0);
    $this->assertDatabaseHas('suppliers', ['id' => $this->supplier->id]);
    $this->assertDatabaseHas('stock_movements', ['id' => $movement->id]);
    expect((float) $this->supply->fresh()->quantity)->toBe(15.0)
        ->and(Storage::disk('local')->files('purchase-order-backups'))->toHaveCount(1);
});

test('purchase order reset requires confirmation and blocks staff', function () {
    $this->actingAs($this->admin)->delete(route('admin.purchase-orders.reset'), ['reset_confirmation' => 'wrong'])
        ->assertSessionHasErrors('reset_confirmation');
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $this->actingAs($staff)->delete(route('admin.purchase-orders.reset'), ['reset_confirmation' => 'RESET'])->assertForbidden();
    $this->assertDatabaseCount('purchase_orders', 2);
    $this->assertDatabaseCount('purchase_order_items', 2);
});
