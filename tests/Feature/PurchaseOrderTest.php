<?php

use App\Models\Consumable;
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
    $this->product = Product::factory()->create(['category' => 'container', 'stock_quantity' => 10]);
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

test('admin creates a purchase order awaiting stock receiving', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.purchase-orders.store'), [
        'supplier_id' => $this->supplier->id,
        'items' => [
            ['itemable_type' => 'product', 'itemable_id' => $this->product->id, 'quantity_ordered' => 20, 'unit_cost' => 15],
        ],
    ]);

    $purchaseOrder = PurchaseOrder::latest()->first();
    $response->assertRedirect(route('admin.purchase-orders.show', $purchaseOrder));
    expect($purchaseOrder->fresh()->status)->toBe('ordered');
    expect($purchaseOrder->items)->toHaveCount(1);
    expect($this->product->fresh()->stock_quantity)->toBe(10);
    $this->assertDatabaseHas('expenses', [
        'purchase_order_id' => $purchaseOrder->id, 'reference' => $purchaseOrder->po_number,
        'amount' => 300, 'category' => 'Supplies & Materials',
    ]);
});

test('purchase order expenses follow draft publication and cancellation without duplicates', function () {
    $payload = [
        'supplier_id' => $this->supplier->id, 'status' => 'draft',
        'ordered_at' => today()->toDateString(),
        'items' => [['itemable_type' => 'product', 'itemable_id' => $this->product->id, 'quantity_ordered' => 2, 'unit_cost' => 12.50]],
    ];
    $this->actingAs($this->admin)->post(route('admin.purchase-orders.store'), $payload)->assertSessionHasNoErrors();
    $order = PurchaseOrder::latest('id')->firstOrFail();
    $this->assertDatabaseCount('expenses', 0);

    $payload['status'] = 'ordered';
    $payload['items'][0]['quantity_ordered'] = 3;
    $this->put(route('admin.purchase-orders.update', $order), $payload)->assertSessionHasNoErrors();
    app(\App\Services\PurchaseOrderExpenseService::class)->sync($order->fresh());
    $this->assertDatabaseCount('expenses', 1);
    $this->assertDatabaseHas('expenses', ['purchase_order_id' => $order->id, 'amount' => 37.50]);
    $summary = app(\App\Services\FinancialSummaryService::class)->summarize(today()->toDateString(), today()->toDateString());
    expect($summary['Recorded Expenses'])->toBe(37.50);
    $this->get(route('admin.financial.section', ['section' => 'expenses']))->assertOk()->assertSee($order->po_number);

    $this->post(route('admin.purchase-orders.cancel', $order))->assertSessionHasNoErrors();
    $this->assertDatabaseCount('expenses', 0);
});

test('old immediate receiving flags do not bypass stock receiving', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.purchase-orders.store'), [
        'supplier_id' => $this->supplier->id,
        'receive_immediately' => '1',
        'items' => [
            ['itemable_type' => 'product', 'itemable_id' => $this->product->id, 'quantity_ordered' => 20, 'unit_cost' => 15],
        ],
    ]);

    $purchaseOrder = PurchaseOrder::latest()->first();

    $response->assertRedirect(route('admin.purchase-orders.show', $purchaseOrder));
    expect($purchaseOrder->fresh()->status)->toBe('ordered');
    expect($this->product->fresh()->stock_quantity)->toBe(10);
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


test('purchase orders use the original workflow without supplier email actions', function () {
    $order = PurchaseOrder::create([
        'po_number' => 'PO-WITHOUT-EMAIL', 'supplier_id' => $this->supplier->id,
        'status' => 'ordered', 'ordered_at' => today(), 'created_by' => $this->admin->id,
    ]);
    $this->actingAs($this->admin)->get(route('admin.purchase-orders.show', $order))->assertOk()
        ->assertSee('Line Items')->assertSee('Details')->assertSee('Record Receipt')
        ->assertDontSee('Send to Supplier')->assertDontSee('Supplier Email')
        ->assertDontSee('Email History')->assertDontSee('Station email setup is incomplete.');
    $this->get('/admin/purchase-orders/'.$order->id.'/email')->assertNotFound();
    $this->post('/admin/purchase-orders/'.$order->id.'/email')->assertNotFound();
    $this->get('/admin/purchase-orders/'.$order->id.'/pdf')->assertNotFound();
});
