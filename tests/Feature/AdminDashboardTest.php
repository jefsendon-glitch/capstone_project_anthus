<?php

use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->customer = User::factory()->create(['credit_balance' => 150]);
    $this->customer->assignRole('customer');

    $this->product = Product::factory()->create(['unit_price' => 30]);
});

test('the dashboard reflects real revenue and outstanding credit totals from the database', function () {
    SalesTransaction::create([
        'transaction_code' => 'TXN-DASH-001',
        'transaction_type' => 'walk-in',
        'product_id' => $this->product->id,
        'product_name' => $this->product->name,
        'quantity' => 2,
        'unit_price' => 30,
        'total_amount' => 60,
        'payment_method' => 'cash',
    ]);

    $old = SalesTransaction::create([
        'transaction_code' => 'TXN-DASH-002',
        'transaction_type' => 'walk-in',
        'product_id' => $this->product->id,
        'product_name' => $this->product->name,
        'quantity' => 1,
        'unit_price' => 30,
        'total_amount' => 30,
        'payment_method' => 'cash',
    ]);
    // Backdate outside of Eloquent's auto-touch so it lands in "this month" but not "today".
    DB::table('sales_transactions')->where('id', $old->id)->update(['created_at' => now()->subDays(10)]);

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('₱60');
    $response->assertSee('₱90');
    $response->assertSee('₱150');
});
