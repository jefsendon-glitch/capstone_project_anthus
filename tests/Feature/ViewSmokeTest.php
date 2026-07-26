<?php

use App\Models\Consumable;
use App\Models\MaintenanceLog;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesTransaction;
use App\Models\Staff;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DeliveryService;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->staffUser = User::factory()->create();
    $this->staffUser->assignRole('staff');
    Staff::factory()->create(['user_id' => $this->staffUser->id]);

    $this->customerUser = User::factory()->create(['address' => 'Sample Address']);
    $this->customerUser->assignRole('customer');

    $this->product = Product::factory()->create();
    $this->consumable = Consumable::factory()->create();
    $this->maintenanceLog = MaintenanceLog::factory()->create();

    $this->supplier = Supplier::create(['name' => 'Smoke Test Supplier']);
    $this->purchaseOrder = PurchaseOrder::create([
        'po_number' => 'PO-SMOKE-001',
        'supplier_id' => $this->supplier->id,
        'status' => 'draft',
        'created_by' => $this->admin->id,
    ]);

    $this->transaction = SalesTransaction::create([
        'transaction_code' => 'TXN-SMOKE-001',
        'transaction_type' => 'walk-in',
        'product_id' => $this->product->id,
        'product_name' => $this->product->name,
        'quantity' => 1,
        'unit_price' => $this->product->unit_price,
        'total_amount' => $this->product->unit_price,
        'payment_method' => 'cash',
        'processed_by' => $this->staffUser->id,
    ]);

    $this->order = app(DeliveryService::class)->placeOrder([
        'product_id' => $this->product->id,
        'quantity' => 1,
        'customer_address' => 'Sample Address',
    ], $this->customerUser);
});

test('admin pages render without errors', function () {
    $this->actingAs($this->admin);

    $this->get(route('admin.dashboard'))->assertOk();
    $this->get(route('admin.products.index'))->assertOk();
    $this->get(route('admin.products.create'))->assertOk();
    $this->get(route('admin.products.edit', $this->product))->assertOk();
    $this->get(route('admin.consumables.index'))->assertOk();
    $this->get(route('admin.consumables.create'))->assertOk();
    $this->get(route('admin.consumables.edit', $this->consumable))->assertOk();
    $this->get(route('admin.maintenance.index'))->assertOk();
    $this->get(route('admin.maintenance.create'))->assertOk();
    $this->get(route('admin.maintenance.edit', $this->maintenanceLog))->assertOk();
    $this->get(route('admin.gallon-stocks.index'))->assertOk();
    $this->get(route('admin.water-production.index'))->assertOk();
    $this->get(route('admin.water-production.create'))->assertOk();
    $this->get(route('admin.stock-movements.index'))->assertOk();
    $this->get(route('admin.suppliers.index'))->assertOk();
    $this->get(route('admin.suppliers.create'))->assertOk();
    $this->get(route('admin.purchase-orders.index'))->assertOk();
    $this->get(route('admin.purchase-orders.create'))->assertOk();
    $this->get(route('admin.purchase-orders.show', $this->purchaseOrder))->assertOk();
    $this->get(route('admin.purchase-orders.edit', $this->purchaseOrder))->assertOk();
    $this->get(route('admin.reports.index'))->assertOk();
    $this->get(route('admin.customers.index'))->assertOk();
    $this->get(route('admin.customers.show', $this->customerUser))->assertOk();
    $this->get(route('admin.customers.edit', $this->customerUser))->assertOk();
    $this->get(route('admin.staff.index'))->assertOk();
    $this->get(route('admin.staff.create'))->assertOk();
    $this->get(route('admin.staff.edit', $this->staffUser))->assertOk();
    $this->get(route('pos.index'))->assertOk();
    $this->get(route('pos.create'))->assertOk();
    $this->get(route('pos.show', $this->transaction))->assertOk();
    $this->get(route('deliveries.index'))->assertOk();
    $this->get(route('deliveries.show', $this->order))->assertOk();
    $this->get(route('payments.index'))->assertOk();
    $this->get(route('profile.edit'))->assertOk();
});

test('staff pages render without errors', function () {
    $this->actingAs($this->staffUser);

    $this->get(route('staff.dashboard'))->assertOk();
    $this->get(route('admin.products.index'))->assertOk();
    $this->get(route('admin.consumables.index'))->assertOk();
    $this->get(route('admin.maintenance.index'))->assertOk();
    $this->get(route('admin.gallon-stocks.index'))->assertOk();
    $this->get(route('admin.water-production.index'))->assertOk();
    $this->get(route('admin.stock-movements.index'))->assertOk();
    $this->get(route('pos.index'))->assertOk();
    $this->get(route('pos.create'))->assertOk();
    $this->get(route('deliveries.index'))->assertOk();
    $this->get(route('deliveries.show', $this->order))->assertOk();
    $this->get(route('payments.index'))->assertOk();
    $this->get(route('profile.edit'))->assertOk();
});

test('customer pages render without errors', function () {
    $this->actingAs($this->customerUser);

    $this->get(route('customer.dashboard'))->assertOk();
    $this->get(route('customer.orders.index'))->assertOk();
    $this->get(route('customer.orders.create'))->assertOk();
    $this->get(route('customer.orders.show', $this->order))->assertOk();
    $this->get(route('customer.payments.index'))->assertOk();
    $this->get(route('profile.edit'))->assertOk();
});

test('auth pages render without errors', function () {
    $this->get(route('login'))->assertOk();
    $this->get(route('register'))->assertOk();
    $this->get(route('password.request'))->assertOk();
});
