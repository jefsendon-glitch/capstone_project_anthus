<?php

use App\Models\Consumable;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

test('draft orders can be edited placed and cancelled without changing stock', function () {
    $payload = [
        'supplier_id' => $this->supplier->id, 'status' => 'draft', 'ordered_at' => '2026-10-10',
        'expected_date' => '2026-10-15',
        'items' => [['itemable_type' => 'consumable', 'itemable_id' => $this->supply->id, 'quantity_ordered' => 5, 'unit_cost' => 12.50]],
    ];
    $this->actingAs($this->admin)->post(route('admin.purchase-orders.store'), $payload)->assertSessionHasNoErrors();
    $draft = PurchaseOrder::latest('id')->first();
    expect($draft->status)->toBe('draft');
    $this->get(route('admin.purchase-orders.edit', $draft))->assertOk();
    $payload['status'] = 'ordered';
    $this->put(route('admin.purchase-orders.update', $draft), $payload)->assertSessionHasNoErrors();
    expect($draft->fresh()->status)->toBe('ordered');
    $this->post(route('admin.purchase-orders.cancel', $draft))->assertSessionHasNoErrors();
    expect($draft->fresh()->status)->toBe('cancelled');
    expect((float) $this->supply->fresh()->quantity)->toBe(2.0);
});

test('partial orders cannot be cancelled and receipt tokens prevent duplicate partial deliveries', function () {
    $payload = ['receipt_token' => (string) Str::uuid(), 'items' => [['purchase_order_item_id' => $this->item->id, 'quantity_received' => 2]]];
    $this->actingAs($this->admin)->post(route('admin.purchase-orders.receive', $this->order), $payload)->assertSessionHasNoErrors();
    $this->post(route('admin.purchase-orders.receive', $this->order), $payload)->assertSessionHasErrors('items');
    DB::table('stock_movements')->delete();
    $this->post(route('admin.purchase-orders.receive', $this->order), $payload)->assertSessionHasErrors('items');
    expect((float) $this->supply->fresh()->quantity)->toBe(4.0);
    $this->post(route('admin.purchase-orders.cancel', $this->order))->assertSessionHasErrors('status');
    expect($this->order->fresh()->status)->toBe('partially_received');
});

test('fractional supply receipts complete correctly and update the latest supply cost', function () {
    $this->item->update(['quantity_ordered' => 0.3, 'unit_cost' => 12.50]);
    foreach ([0.1, 0.2] as $quantity) {
        $this->actingAs($this->admin)->post(route('admin.purchase-orders.receive', $this->order), [
            'items' => [['purchase_order_item_id' => $this->item->id, 'quantity_received' => $quantity]],
        ])->assertSessionHasNoErrors();
    }
    expect($this->order->fresh()->status)->toBe('received');
    expect((float) $this->supply->fresh()->quantity)->toBe(2.3);
    expect((float) $this->supply->fresh()->unit_cost)->toBe(12.5);
});

test('container deliveries update current station inventory and its movement history', function () {
    $container = Product::factory()->create(['category' => 'container', 'name' => 'Round Container', 'size' => '5 Gallon', 'stock_quantity' => 0]);
    $item = $this->order->items()->create(['itemable_type' => Product::class, 'itemable_id' => $container->id, 'quantity_ordered' => 3, 'unit_cost' => 150]);
    $this->actingAs($this->admin)->post(route('admin.purchase-orders.receive', $this->order), [
        'items' => [['purchase_order_item_id' => $item->id, 'quantity_received' => 2]],
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('station_gallons', ['name' => 'Round Container', 'size' => '5 Gallon', 'empty' => 2]);
    $this->assertDatabaseHas('station_gallon_movements', ['action' => 'add', 'to_status' => 'empty', 'quantity' => 2, 'notes' => 'Received against '.$this->order->po_number]);
    $this->post(route('admin.purchase-orders.receive', $this->order), [
        'items' => [['purchase_order_item_id' => $item->id, 'quantity_received' => 1]],
    ])->assertSessionHasNoErrors();
    expect(DB::table('station_gallons')->where('name', 'Round Container')->count())->toBe(1);
    expect(DB::table('station_gallons')->where('name', 'Round Container')->value('empty'))->toBe(3);
    expect($container->fresh()->stock_quantity)->toBe(3);
});

test('ambiguous station container matches roll back the entire receipt', function () {
    $container = Product::factory()->create(['category' => 'container', 'name' => 'Duplicate Container', 'size' => '5 Gallon', 'stock_quantity' => 0]);
    foreach ([1, 2] as $quantity) {
        DB::table('station_gallons')->insert(['name' => $container->name, 'size' => $container->size, 'empty' => $quantity, 'created_at' => now(), 'updated_at' => now()]);
    }
    $item = $this->order->items()->create(['itemable_type' => Product::class, 'itemable_id' => $container->id, 'quantity_ordered' => 3, 'unit_cost' => 150]);
    $this->actingAs($this->admin)->post(route('admin.purchase-orders.receive', $this->order), [
        'items' => [['purchase_order_item_id' => $item->id, 'quantity_received' => 1]],
    ])->assertSessionHasErrors('items');
    expect($container->fresh()->stock_quantity)->toBe(0);
    expect((float) $item->fresh()->quantity_received)->toBe(0.0);
});

test('archived supplies and their suppliers are protected from permanent deletion', function () {
    $this->actingAs($this->admin)->delete(route('admin.consumables.destroy', $this->supply))->assertSessionHas('error');
    $this->supply->delete();
    $this->actingAs($this->admin)->delete(route('admin.suppliers.destroy', $this->supplier))->assertSessionHas('error');
    $this->assertDatabaseHas('suppliers', ['id' => $this->supplier->id]);
    $this->assertSoftDeleted('consumables', ['id' => $this->supply->id]);
    $this->get(route('admin.purchase-orders.show', $this->order))->assertOk()->assertSee($this->supply->name);
    $this->post(route('admin.purchase-orders.receive', $this->order), [
        'items' => [['purchase_order_item_id' => $this->item->id, 'quantity_received' => 1]],
    ])->assertSessionHasErrors('items.0.quantity_received');
});

test('inactive supplier selection remains visible when editing its existing supply', function () {
    $this->supplier->update(['is_active' => false]);
    $this->actingAs($this->admin)->get(route('admin.consumables.edit', $this->supply))->assertOk()->assertSee($this->supplier->name);
    $this->put(route('admin.consumables.update', $this->supply), [
        'name' => $this->supply->name, 'category' => 'filter_treatment_materials', 'unit' => $this->supply->unit,
        'quantity' => 2.5, 'low_stock_threshold' => 5, 'unit_cost' => 12.50, 'supplier_id' => $this->supplier->id,
    ])->assertSessionHasNoErrors();
    expect($this->supply->fresh()->supplier_id)->toBe($this->supplier->id);
});

test('order validation rejects duplicate items malformed lines fractional containers and reversed dates', function () {
    $container = Product::factory()->create(['category' => 'container']);
    foreach ([
        ['items' => [['itemable_type' => 'product', 'itemable_id' => $container->id, 'quantity_ordered' => 1.5, 'unit_cost' => 10]]],
        ['items' => ['malformed']],
        ['items' => [['itemable_type' => 'consumable', 'itemable_id' => $this->supply->id, 'quantity_ordered' => 1, 'unit_cost' => 10], ['itemable_type' => 'consumable', 'itemable_id' => $this->supply->id, 'quantity_ordered' => 1, 'unit_cost' => 10]]],
        ['ordered_at' => '2026-10-12', 'expected_date' => '2026-10-11', 'items' => [['itemable_type' => 'consumable', 'itemable_id' => $this->supply->id, 'quantity_ordered' => 1, 'unit_cost' => 10]]],
    ] as $payload) {
        $this->actingAs($this->admin)->post(route('admin.purchase-orders.store'), $payload + ['supplier_id' => $this->supplier->id])->assertSessionHasErrors();
    }
    expect(PurchaseOrder::count())->toBe(1);
});
