<?php

use App\Models\CustomerPayment;
use App\Models\DeliveryOrder;
use App\Models\GallonStock;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesTransaction;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->customer = User::factory()->create(['credit_balance' => 100]);
    $this->customer->assignRole('customer');
    $this->product = Product::factory()->create(['category' => 'purified']);
    $this->other = Product::factory()->create(['category' => 'purified', 'stock_quantity' => 20]);
});

test('archived product history is deleted and mixed orders retain unrelated products', function () {
    SalesTransaction::create([
        'transaction_code' => 'DELETE-HISTORY', 'transaction_type' => 'walk-in',
        'product_id' => $this->product->id, 'product_name' => $this->product->name,
        'quantity' => 1, 'unit_price' => 50, 'total_amount' => 50, 'payment_method' => 'loan',
        'customer_id' => $this->customer->id, 'credit_paid_amount' => 10,
    ]);
    CustomerPayment::create(['customer_id' => $this->customer->id, 'customer_name' => $this->customer->name, 'amount' => 10, 'payment_date' => today()]);
    StockMovement::record($this->product, 'initial', 5, 0, 5, $this->admin->id);
    $gallon = GallonStock::create(['product_id' => $this->product->id, 'status' => 'empty', 'quantity' => 5]);
    StockMovement::record($gallon, 'initial', 5, 0, 5, $this->admin->id);
    DB::table('gallon_customer_balances')->insert(['product_id' => $this->product->id, 'customer_id' => $this->customer->id, 'quantity' => 1]);
    DB::table('water_production_logs')->insert(['product_id' => $this->product->id, 'gallons_produced' => 5, 'production_date' => today()->toDateString()]);
    $delivery = DeliveryOrder::create([
        'order_code' => 'DO-MIXED', 'customer_id' => $this->customer->id,
        'customer_name' => $this->customer->name, 'customer_address' => 'Address',
        'product_id' => $this->product->id, 'product_name' => $this->product->name,
        'quantity' => 2, 'unit_price' => 50, 'total_amount' => 100, 'status' => 'pending',
    ]);
    foreach ([$this->product, $this->other] as $product) {
        $delivery->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => 1, 'unit_price' => 50, 'total_amount' => 50]);
    }
    $supplier = Supplier::create(['name' => 'Deletion supplier']);
    $purchase = PurchaseOrder::create(['po_number' => 'PO-MIXED', 'supplier_id' => $supplier->id, 'status' => 'ordered']);
    foreach ([$this->product, $this->other] as $product) {
        $purchase->items()->create(['itemable_type' => $product->getMorphClass(), 'itemable_id' => $product->id, 'quantity_ordered' => 5, 'quantity_received' => 0, 'unit_cost' => 10]);
    }
    $this->product->delete();
    $this->actingAs($this->admin)->delete(route('admin.products.force-delete', $this->product->id))->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseMissing('products', ['id' => $this->product->id]);
    foreach (['sales_transactions', 'gallon_stocks', 'gallon_customer_balances', 'water_production_logs', 'stock_movements'] as $table) $this->assertDatabaseCount($table, 0);
    $this->assertDatabaseCount('customer_payments', 1);
    expect((float) $this->customer->fresh()->credit_balance)->toBe(60.0)
        ->and((int) $this->other->fresh()->stock_quantity)->toBe(20)
        ->and($delivery->fresh()->product_id)->toBe($this->other->id)
        ->and((float) $delivery->fresh()->total_amount)->toBe(50.0);
    $this->assertDatabaseCount('delivery_order_items', 1);
    $this->assertDatabaseCount('purchase_order_items', 1);
});

test('permanent deletion removes product only orders and requires admin and archived product', function () {
    $supplier = Supplier::create(['name' => 'Deletion supplier']);
    $purchase = PurchaseOrder::create(['po_number' => 'PO-ONLY', 'supplier_id' => $supplier->id, 'status' => 'ordered']);
    $purchase->items()->create(['itemable_type' => $this->product->getMorphClass(), 'itemable_id' => $this->product->id, 'quantity_ordered' => 5, 'quantity_received' => 0, 'unit_cost' => 10]);
    $this->actingAs($this->admin)->delete(route('admin.products.force-delete', $this->product->id))->assertNotFound();
    $this->product->delete();
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $this->actingAs($staff)->delete(route('admin.products.force-delete', $this->product->id))->assertForbidden();
    $this->actingAs($this->admin)->delete(route('admin.products.force-delete', $this->product->id))->assertRedirect();
    $this->assertDatabaseMissing('purchase_orders', ['id' => $purchase->id]);
    $this->assertDatabaseMissing('products', ['id' => $this->product->id]);
});
