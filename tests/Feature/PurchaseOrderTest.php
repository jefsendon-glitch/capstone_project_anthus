<?php

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Staff;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->staffUser = User::factory()->create();
    $this->staffUser->assignRole('staff');
    Staff::factory()->create(['user_id' => $this->staffUser->id]);

    $this->supplier = Supplier::create(['name' => 'AquaParts Supply']);
    $this->product = Product::factory()->create(['stock_quantity' => 10]);
});

test('admin can create a draft purchase order with items', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.purchase-orders.store'), [
        'supplier_id' => $this->supplier->id,
        'items' => [
            ['itemable_type' => 'product', 'itemable_id' => $this->product->id, 'quantity_ordered' => 20, 'unit_cost' => 15],
        ],
    ]);

    $purchaseOrder = PurchaseOrder::latest()->first();
    $response->assertRedirect(route('admin.purchase-orders.show', $purchaseOrder));
    expect($purchaseOrder->status)->toBe('draft');
    expect($purchaseOrder->items)->toHaveCount(1);
});

test('staff cannot access purchase orders', function () {
    $this->actingAs($this->staffUser)->get(route('admin.purchase-orders.index'))->assertForbidden();
});

test('receiving stock increments product inventory and logs a movement', function () {
    $purchaseOrder = PurchaseOrder::create([
        'po_number' => 'PO-TEST-001',
        'supplier_id' => $this->supplier->id,
        'status' => 'ordered',
        'created_by' => $this->admin->id,
    ]);
    $item = $purchaseOrder->items()->create([
        'itemable_type' => Product::class,
        'itemable_id' => $this->product->id,
        'quantity_ordered' => 20,
        'unit_cost' => 15,
    ]);

    $this->actingAs($this->admin)->post(route('admin.purchase-orders.receive', $purchaseOrder), [
        'items' => [
            ['purchase_order_item_id' => $item->id, 'quantity_received' => 20],
        ],
    ]);

    expect($this->product->fresh()->stock_quantity)->toBe(30);
    expect($purchaseOrder->fresh()->status)->toBe('received');
    $this->assertDatabaseHas('stock_movements', [
        'movable_type' => Product::class,
        'movable_id' => $this->product->id,
        'type' => 'purchase_receive',
        'quantity_delta' => 20,
    ]);
});

test('a draft purchase order cannot be edited once ordered', function () {
    $purchaseOrder = PurchaseOrder::create([
        'po_number' => 'PO-TEST-002',
        'supplier_id' => $this->supplier->id,
        'status' => 'ordered',
        'created_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)->get(route('admin.purchase-orders.edit', $purchaseOrder))
        ->assertRedirect(route('admin.purchase-orders.show', $purchaseOrder));
});
