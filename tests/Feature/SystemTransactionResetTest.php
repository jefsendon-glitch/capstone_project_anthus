<?php

use App\Models\Consumable;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\FinancialReport;
use App\Models\MaintenanceLog;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesTransaction;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\Supplier;
use App\Models\SupplyUsage;
use App\Models\User;
use App\Models\WaterProductionLog;
use App\Services\DeliveryService;
use App\Services\SystemTransactionResetService;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create(['password' => 'reset-test-password']);
    $this->admin->assignRole('admin');
    $this->staff = User::factory()->create();
    $this->staff->assignRole('staff');
    Staff::factory()->create(['user_id' => $this->staff->id]);
    $this->customer = User::factory()->create(['credit_status' => 'suspended', 'credit_suspended_at' => now()]);
    $this->customer->assignRole('customer');
    DB::table('users')->where('id', $this->customer->id)->update(['credit_balance' => 90]);
    $this->product = Product::factory()->create(['category' => 'alkaline', 'requires_container_choice' => true,
        'stock_quantity' => 10, 'round_stock' => 4, 'slim_stock' => 6, 'unit_price' => 30]);
    $this->consumable = Consumable::factory()->create(['quantity' => 12]);
    $this->supplier = Supplier::create(['name' => 'Retained Supplier']);
    Setting::current()->update(['business_name' => 'Retained Station']);
    $order = app(DeliveryService::class)->placeOrder(['items' => [['product_id' => $this->product->id,
        'quantity' => 2, 'container_type' => 'slim']], 'payment_method' => 'cash', 'customer_address' => 'Test address'], $this->customer);
    SalesTransaction::create(['transaction_code' => 'TXN-RESET-1', 'transaction_type' => 'delivery',
        'customer_id' => $this->customer->id, 'delivery_order_id' => $order->id, 'customer_name' => $this->customer->name,
        'product_id' => $this->product->id, 'product_name' => $this->product->name, 'quantity' => 3,
        'unit_price' => 30, 'total_amount' => 90, 'payment_method' => 'loan', 'processed_by' => $this->staff->id]);
    CustomerPayment::create(['customer_id' => $this->customer->id, 'customer_name' => $this->customer->name,
        'amount' => 20, 'payment_date' => today(), 'recorded_by' => $this->admin->id]);
    $po = PurchaseOrder::create(['po_number' => 'PO-RESET-1', 'supplier_id' => $this->supplier->id,
        'status' => 'ordered', 'created_by' => $this->admin->id]);
    $po->items()->create(['itemable_type' => Product::class, 'itemable_id' => $this->product->id,
        'quantity_ordered' => 5, 'quantity_received' => 2, 'unit_cost' => 20]);
    DB::table('purchase_order_emails')->insert(['purchase_order_id' => $po->id, 'submission_token' => Str::uuid(),
        'recipient' => 'supplier@example.test', 'sender' => 'station@example.test', 'subject' => 'Test PO',
        'message' => 'Test', 'order_snapshot' => '{}', 'status' => 'sent']);
    Expense::create(['expense_date' => today(), 'category' => 'supplies', 'description' => 'Test purchase',
        'amount' => 100, 'purchase_order_id' => $po->id, 'recorded_by' => $this->admin->id]);
    FinancialReport::create(['period_type' => 'daily', 'date_from' => today(), 'date_to' => today(),
        'summary' => ['sales' => 90], 'generated_by' => $this->admin->id]);
    SupplyUsage::create(['operation' => 'Cleaning', 'operation_date' => today(), 'items' => [], 'recorded_by' => $this->admin->id]);
    MaintenanceLog::factory()->create();
    $this->gallonId = DB::table('station_gallons')->insertGetId(['name' => 'Retained containers',
        'type' => 'Slim Gallon', 'size' => '5 Gallon', 'empty' => 5, 'filled' => 3, 'borrowed' => 2]);
    $movementId = DB::table('station_gallon_movements')->insertGetId(['station_gallon_id' => $this->gallonId,
        'action' => 'fill', 'from_status' => 'empty', 'to_status' => 'filled', 'quantity' => 3,
        'recorded_by' => $this->admin->id, 'created_at' => now(), 'updated_at' => now()]);
    WaterProductionLog::create(['product_id' => $this->product->id, 'station_gallon_movement_id' => $movementId,
        'container_name' => 'Retained containers', 'gallons_produced' => 3, 'production_date' => today(), 'produced_by' => $this->admin->id]);
    DB::table('station_gallon_loans')->insert(['station_gallon_id' => $this->gallonId, 'customer_id' => $this->customer->id, 'quantity' => 2]);
    DB::table('gallon_customer_balances')->insert(['product_id' => $this->product->id, 'customer_id' => $this->customer->id, 'quantity' => 2]);
    DB::table('gallon_stocks')->insert(['product_id' => $this->product->id, 'status' => 'filled', 'quantity' => 3]);
    $this->product->stockMovements()->create(['reference_code' => 'PR-RESET-1', 'type' => 'purchase_receive',
        'quantity_before' => 8, 'quantity_after' => 10, 'quantity_delta' => 2, 'recorded_by' => $this->admin->id]);
    DB::table('stock_receipt_references')->insert(['reference_code' => 'PR-RESET-1', 'created_at' => now()]);
    $this->credentials = ['password' => 'reset-test-password', 'reset_confirmation' => 'RESET ALL', 'acknowledge_reset' => '1'];
    $this->url = route('admin.system.transactions.reset');
});

test('confirmed admin reset backs up and clears all transactions and balances while retaining catalog and accounts', function () {
    $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Transaction Settings');
    $this->assertDatabaseCount('sales_transactions', 1);
    $passwordHash = $this->admin->password;
    $this->delete($this->url, $this->credentials)->assertRedirect(route('admin.dashboard'))->assertSessionHasNoErrors();
    foreach (SystemTransactionResetService::TRANSACTION_TABLES as $table) {
        $this->assertDatabaseCount($table, $table === 'activity_log' ? 1 : 0);
    }
    $this->assertDatabaseHas('products', ['id' => $this->product->id, 'stock_quantity' => 0, 'round_stock' => 0, 'slim_stock' => 0, 'unit_price' => 30]);
    $this->assertDatabaseHas('consumables', ['id' => $this->consumable->id, 'quantity' => 0]);
    $this->assertDatabaseHas('gallon_stocks', ['product_id' => $this->product->id, 'quantity' => 0]);
    $this->assertDatabaseHas('station_gallons', ['id' => $this->gallonId, 'name' => 'Retained containers', 'empty' => 0, 'filled' => 0, 'borrowed' => 0]);
    $this->assertDatabaseHas('users', ['id' => $this->customer->id, 'credit_balance' => 0, 'credit_status' => 'eligible']);
    expect($this->admin->fresh()->password)->toBe($passwordHash);
    expect($this->admin->fresh()->isAdmin())->toBeTrue();
    $this->assertDatabaseHas('staff', ['user_id' => $this->staff->id]);
    $this->assertDatabaseHas('suppliers', ['id' => $this->supplier->id, 'name' => 'Retained Supplier']);
    $this->assertDatabaseHas('settings', ['business_name' => 'Retained Station']);
    $files = Storage::disk('local')->files('system-reset-backups');
    expect($files)->toHaveCount(1);
    $backup = json_decode(Storage::disk('local')->get($files[0]), true, flags: JSON_THROW_ON_ERROR);
    expect($backup['tables']['sales_transactions'])->toHaveCount(1);
    expect($backup['balances']['products'][0]['stock_quantity'])->toBe(10);
    expect($backup['balances']['users'][0])->not->toHaveKey('password');
    $this->get(route('admin.dashboard'))->assertOk();
    $this->post(route('admin.inventory.gallon-inventory.move'), ['gallon_id' => $this->gallonId,
        'action' => 'add', 'quantity' => 3])->assertSessionHasNoErrors();
    $this->post(route('admin.inventory.gallon-inventory.move'), ['gallon_id' => $this->gallonId,
        'action' => 'fill', 'quantity' => 3, 'product_id' => $this->product->id])->assertSessionHasNoErrors();
    $this->post(route('pos.store'), ['transaction_type' => 'walk-in', 'payment_method' => 'cash', 'tendered_amount' => 30,
        'items' => json_encode([['product_id' => $this->product->id, 'quantity' => 1, 'container_type' => 'slim']])])
        ->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('products', ['id' => $this->product->id, 'stock_quantity' => 2, 'slim_stock' => 2, 'round_stock' => 0]);
    $this->assertDatabaseCount('sales_transactions', 1);
});

test('reset requires admin role password exact phrase and acknowledgment', function () {
    $this->actingAs($this->staff)->delete($this->url, $this->credentials)->assertForbidden();
    $this->actingAs($this->customer)->delete($this->url, $this->credentials)->assertForbidden();
    $this->actingAs($this->staff)->get(route('staff.dashboard'))->assertOk()->assertDontSee('Transaction Settings');
    $this->actingAs($this->admin);
    $this->delete($this->url, array_replace($this->credentials, ['password' => 'wrong']))->assertSessionHasErrorsIn('systemReset', 'password');
    $this->delete($this->url, array_replace($this->credentials, ['reset_confirmation' => 'RESET']))->assertSessionHasErrorsIn('systemReset', 'reset_confirmation');
    $this->delete($this->url, array_replace($this->credentials, ['acknowledge_reset' => '0']))->assertSessionHasErrorsIn('systemReset', 'acknowledge_reset');
    $this->assertDatabaseCount('sales_transactions', 1);
    expect($this->product->fresh()->stock_quantity)->toBe(10);
    expect(Storage::disk('local')->files('system-reset-backups'))->toBeEmpty();
});

test('backup failure blocks the reset before any deletion', function () {
    Storage::shouldReceive('disk')->with('local')->andReturn(new class {
        public function put($path, $contents) { return false; }
    });
    $this->actingAs($this->admin)->delete($this->url, $this->credentials)->assertSessionHasErrorsIn('systemReset', 'reset_confirmation');
    $this->assertDatabaseCount('sales_transactions', 1);
    $this->assertDatabaseCount('purchase_orders', 1);
    expect($this->product->fresh()->stock_quantity)->toBe(10);
});

test('failure after deletes rolls back every transaction and stock change', function () {
    $failOnce = true;
    DB::listen(function (QueryExecuted $query) use (&$failOnce) {
        if ($failOnce && str_starts_with($query->sql, 'update') && str_contains($query->sql, 'products')) {
            $failOnce = false;
            throw new RuntimeException('Simulated reset failure');
        }
    });
    try {
        app(SystemTransactionResetService::class)->reset($this->admin);
        $this->fail('The reset must fail.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Simulated reset failure');
    }
    $this->assertDatabaseCount('sales_transactions', 1);
    $this->assertDatabaseCount('purchase_order_items', 1);
    $this->assertDatabaseCount('station_gallon_movements', 1);
    expect($this->product->fresh()->stock_quantity)->toBe(10);
    expect((float) $this->customer->fresh()->credit_balance)->toBe(90.0);
});
