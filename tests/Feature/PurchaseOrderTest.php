<?php

use App\Models\Product;
use App\Models\Consumable;
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

test('purchase orders reject supplies assigned to another supplier', function () {
    $otherSupplier = Supplier::create(['name' => 'Other supplier']);
    $supply = Consumable::factory()->create(['supplier_id' => $otherSupplier->id]);

    $this->actingAs($this->admin)->post(route('admin.purchase-orders.store'), [
        'supplier_id' => $this->supplier->id,
        'items' => [['itemable_type' => 'consumable', 'itemable_id' => $supply->id, 'quantity_ordered' => 5, 'unit_cost' => 10]],
    ])->assertSessionHasErrors('items.0.itemable_id');

    expect(PurchaseOrder::count())->toBe(0);
});

test('supply restock opens a purchase order with its supplier and cost', function () {
    $supply = Consumable::factory()->create(['supplier_id' => $this->supplier->id, 'unit_cost' => 12]);

    $this->actingAs($this->admin)->get(route('admin.purchase-orders.create', ['consumable_id' => $supply->id]))
        ->assertOk()
        ->assertViewHas('selectedSupplierId', $this->supplier->id)
        ->assertViewHas('selectedSupply', fn ($selected) => $selected->id === $supply->id);
});

test('a supplier with linked supplies cannot be deleted', function () {
    Consumable::factory()->create(['supplier_id' => $this->supplier->id]);

    $this->actingAs($this->admin)->delete(route('admin.suppliers.destroy', $this->supplier))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('suppliers', ['id' => $this->supplier->id]);
});

test('admin creates a purchase order and receives its items immediately', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.purchase-orders.store'), [
        'supplier_id' => $this->supplier->id,
        'items' => [
            ['itemable_type' => 'product', 'itemable_id' => $this->product->id, 'quantity_ordered' => 20, 'unit_cost' => 15],
        ],
    ]);

    $purchaseOrder = PurchaseOrder::latest()->first();
    $response->assertRedirect(route('admin.purchase-orders.show', $purchaseOrder));
    expect($purchaseOrder->fresh()->status)->toBe('received');
    expect($purchaseOrder->items)->toHaveCount(1);
    expect($this->product->fresh()->stock_quantity)->toBe(30);
});

test('purchase order creation always receives items even if an old client sends an unchecked flag', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.purchase-orders.store'), [
        'supplier_id' => $this->supplier->id,
        'receive_immediately' => '0',
        'items' => [
            ['itemable_type' => 'product', 'itemable_id' => $this->product->id, 'quantity_ordered' => 20, 'unit_cost' => 15],
        ],
    ]);

    $purchaseOrder = PurchaseOrder::latest()->first();

    $response->assertRedirect(route('admin.purchase-orders.show', $purchaseOrder));
    expect($purchaseOrder->fresh()->status)->toBe('received');
    expect($this->product->fresh()->stock_quantity)->toBe(30);
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
