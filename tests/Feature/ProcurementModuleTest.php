<?php

use App\Models\Consumable;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->supplier = Supplier::create(['name' => 'Procurement supplier']);
    $this->supply = Consumable::factory()->create([
        'supplier_id' => $this->supplier->id, 'quantity' => 2, 'low_stock_threshold' => 5,
    ]);
    $this->order = PurchaseOrder::create([
        'po_number' => 'PO-MODULE-001', 'supplier_id' => $this->supplier->id,
        'status' => 'ordered', 'ordered_at' => '2026-10-12', 'created_by' => $this->admin->id,
    ]);
    $this->item = $this->order->items()->create([
        'itemable_type' => Consumable::class, 'itemable_id' => $this->supply->id,
        'quantity_ordered' => 10, 'unit_cost' => 12,
    ]);
});

test('procurement screens render for admins and reject other users', function () {
    foreach (['receiving', 'reorders', 'history'] as $page) {
        $this->actingAs($this->admin)->get(route('admin.procurement.'.$page))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('admin.procurement.'.$page))->assertForbidden();
    }
});

test('partial and final deliveries update supply stock and remove completed orders from receiving', function () {
    $this->actingAs($this->admin)->post(route('admin.purchase-orders.receive', $this->order), [
        'items' => [['purchase_order_item_id' => $this->item->id, 'quantity_received' => 4]],
    ])->assertSessionHasNoErrors();
    expect((float) $this->supply->fresh()->quantity)->toBe(6.0);
    expect($this->order->fresh()->status)->toBe('partially_received');

    $this->post(route('admin.purchase-orders.receive', $this->order), [
        'items' => [['purchase_order_item_id' => $this->item->id, 'quantity_received' => 6]],
    ])->assertSessionHasNoErrors();
    expect((float) $this->supply->fresh()->quantity)->toBe(12.0);
    expect($this->order->fresh()->status)->toBe('received');
    $this->get(route('admin.procurement.receiving'))->assertViewHas('orders', fn ($orders) => $orders->isEmpty());
    $this->post(route('admin.purchase-orders.receive', $this->order), [
        'items' => [['purchase_order_item_id' => $this->item->id, 'quantity_received' => 6]],
    ])->assertSessionHasErrors();
    expect((float) $this->supply->fresh()->quantity)->toBe(12.0);
});

test('receiving rejects over delivery duplicate lines and foreign order lines without changing stock', function () {
    $foreign = PurchaseOrder::create([
        'po_number' => 'PO-FOREIGN', 'supplier_id' => $this->supplier->id,
        'status' => 'ordered', 'created_by' => $this->admin->id,
    ])->items()->create(['itemable_type' => Consumable::class, 'itemable_id' => $this->supply->id, 'quantity_ordered' => 10, 'unit_cost' => 12]);
    foreach ([
        [['purchase_order_item_id' => $this->item->id, 'quantity_received' => 11]],
        [['purchase_order_item_id' => $this->item->id, 'quantity_received' => 0]],
        [['purchase_order_item_id' => $foreign->id, 'quantity_received' => 1]],
        [['purchase_order_item_id' => $this->item->id, 'quantity_received' => 1], ['purchase_order_item_id' => $this->item->id, 'quantity_received' => 1]],
    ] as $lines) {
        $this->actingAs($this->admin)->post(route('admin.purchase-orders.receive', $this->order), ['items' => $lines])->assertSessionHasErrors();
    }
    expect((float) $this->supply->fresh()->quantity)->toBe(2.0);
});

test('reorder monitoring shows low stock and incoming balance but excludes healthy and archived supplies', function () {
    Consumable::factory()->create(['quantity' => 20, 'low_stock_threshold' => 5]);
    Consumable::factory()->create(['quantity' => 0])->delete();
    $this->actingAs($this->admin)->get(route('admin.procurement.reorders'))
        ->assertViewHas('supplies', fn ($supplies) => $supplies->count() === 1 && $supplies->first()->id === $this->supply->id)
        ->assertViewHas('pending', fn ($pending) => (float) $pending[$this->supply->id] === 10.0);
});

test('purchase history applies supplier date filters and distinguishes ordered from received value', function () {
    $this->item->update(['quantity_received' => 4]);
    $this->order->update(['status' => 'partially_received']);
    $this->actingAs($this->admin)->get(route('admin.procurement.history', [
        'supplier_id' => $this->supplier->id, 'date_from' => '2026-10-01', 'date_to' => '2026-10-31',
    ]))->assertOk()->assertViewHas('totals', fn ($totals) => (float) $totals->ordered_cost === 120.0 && (float) $totals->received_cost === 48.0);
    $this->get(route('admin.procurement.history', ['date_from' => '2026-11-01']))
        ->assertViewHas('orders', fn ($orders) => $orders->isEmpty());
    $this->get(route('admin.procurement.history', ['date_to' => '2026-10-31']))->assertOk();
});
