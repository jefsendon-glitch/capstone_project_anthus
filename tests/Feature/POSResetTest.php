<?php

use App\Models\CustomerPayment;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SalesService;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->customer = User::factory()->create(['credit_balance' => 50]);
    $this->customer->assignRole('customer');
    $this->product = Product::factory()->create(['stock_quantity' => 10]);
    foreach (['cash', 'loan'] as $method) {
        SalesTransaction::create([
            'transaction_code' => 'RESET-'.$method, 'transaction_type' => 'walk-in',
            'customer_id' => $this->customer->id, 'product_id' => $this->product->id,
            'product_name' => $this->product->name, 'quantity' => 1, 'unit_price' => 50,
            'total_amount' => 50, 'payment_method' => $method, 'credit_paid_amount' => 0,
        ]);
    }
});

test('reset deletes settled sales while retaining credit and payment history without changing stock', function () {
    CustomerPayment::create(['customer_id' => $this->customer->id, 'customer_name' => $this->customer->name, 'amount' => 10, 'payment_date' => today()]);
    $settled = SalesTransaction::create([
        'transaction_code' => 'RESET-paid', 'transaction_type' => 'delivery',
        'product_id' => $this->product->id, 'product_name' => $this->product->name,
        'quantity' => 1, 'unit_price' => 20, 'total_amount' => 20, 'payment_method' => 'loan', 'credit_paid_amount' => 20,
    ]);
    $this->actingAs($this->admin)->delete(route('pos.reset'), ['reset_confirmation' => 'RESET'])
        ->assertRedirect(route('pos.index'))->assertSessionHas('success');
    $this->assertDatabaseCount('sales_transactions', 1);
    $this->assertDatabaseHas('sales_transactions', ['transaction_code' => 'RESET-loan']);
    $this->assertDatabaseMissing('sales_transactions', ['id' => $settled->id]);
    $this->assertDatabaseCount('customer_payments', 1);
    expect((int) $this->product->fresh()->stock_quantity)->toBe(10)
        ->and((float) $this->customer->fresh()->credit_balance)->toBe(50.0);
});

test('reset requires confirmation and only admins can use it', function () {
    $this->actingAs($this->admin)->delete(route('pos.reset'), ['reset_confirmation' => 'wrong'])->assertSessionHasErrors('reset_confirmation');
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $this->actingAs($staff)->delete(route('pos.reset'), ['reset_confirmation' => 'RESET'])->assertForbidden();
    $this->assertDatabaseCount('sales_transactions', 2);
});

test('transaction numbers are not reused after resetting sales history', function () {
    $code = 'TXN-'.today()->format('Ymd').'-005';
    StockMovement::record($this->product, 'sale_deduction', -1, 11, 10, $this->admin->id, referenceCode: $code);
    expect(app(SalesService::class)->generateCode('TXN'))->toBe('TXN-'.today()->format('Ymd').'-006');
});
