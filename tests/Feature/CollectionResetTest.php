<?php

use App\Models\CustomerPayment;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\User;
use App\Services\FinancialSummaryService;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->customer = User::factory()->create(['credit_balance' => 50]);
    $this->customer->assignRole('customer');
    foreach (['2026-10-13', '2026-09-30'] as $date) {
        CustomerPayment::create(['customer_id' => $this->customer->id, 'customer_name' => $this->customer->name, 'amount' => 25, 'payment_date' => $date]);
    }
});

test('reset removes only period payments and does not reverse credit balances or allocations', function () {
    $product = Product::factory()->create(['category' => 'purified']);
    $sale = SalesTransaction::create([
        'transaction_code' => 'COLLECTION-RESET', 'transaction_type' => 'walk-in',
        'product_id' => $product->id, 'product_name' => $product->name, 'quantity' => 1,
        'unit_price' => 100, 'total_amount' => 100, 'payment_method' => 'loan',
        'customer_id' => $this->customer->id, 'credit_paid_amount' => 50,
    ]);
    $this->actingAs($this->admin)->delete(route('admin.financial.collections.reset'), [
        'date_from' => '2026-10-01', 'date_to' => '2026-10-31', 'reset_confirmation' => 'RESET',
    ])->assertRedirect(route('admin.financial.section', ['section' => 'collections', 'date_from' => '2026-10-01', 'date_to' => '2026-10-31']));
    $this->assertDatabaseCount('customer_payments', 1);
    expect(CustomerPayment::whereDate('payment_date', '2026-09-30')->count())->toBe(1);
    expect((float) $this->customer->fresh()->credit_balance)->toBe(50.0)
        ->and((float) $sale->fresh()->credit_paid_amount)->toBe(50.0)
        ->and(app(FinancialSummaryService::class)->summarize('2026-10-01', '2026-10-31')['Customer Collections'])->toBe(0.0);
    $this->get(route('admin.financial.section', ['section' => 'collections']))->assertOk()->assertSee('Reset Collections');
});

test('collection reset requires valid dates confirmation and admin access', function () {
    $payload = ['date_from' => '2026-10-01', 'date_to' => '2026-10-31', 'reset_confirmation' => 'wrong'];
    $this->actingAs($this->admin)->delete(route('admin.financial.collections.reset'), $payload)->assertSessionHasErrors('reset_confirmation');
    $payload = ['date_from' => '2026-10-31', 'date_to' => '2026-10-01', 'reset_confirmation' => 'RESET'];
    $this->delete(route('admin.financial.collections.reset'), $payload)->assertSessionHasErrors('date_to');
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $this->actingAs($staff)->delete(route('admin.financial.collections.reset'), $payload)->assertForbidden();
    $this->assertDatabaseCount('customer_payments', 2);
});
