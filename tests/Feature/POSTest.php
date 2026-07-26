<?php

use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->staffUser = User::factory()->create();
    $this->staffUser->assignRole('staff');
    Staff::factory()->create(['user_id' => $this->staffUser->id]);

    $this->product = Product::factory()->create(['unit_price' => 25, 'stock_quantity' => 100]);
});

test('recording a cash walk-in sale decrements stock and creates a transaction code', function () {
    $response = $this->actingAs($this->staffUser)->post(route('pos.store'), [
        'transaction_type' => 'walk-in',
        'payment_method' => 'cash',
        'customer_name' => 'Guest Buyer',
        'tendered_amount' => 100,
        'items' => json_encode([['product_id' => $this->product->id, 'quantity' => 3]]),
    ]);

    $this->product->refresh();

    expect($this->product->stock_quantity)->toBe(97);

    $this->assertDatabaseHas('sales_transactions', [
        'product_id' => $this->product->id,
        'quantity' => 3,
        'total_amount' => 75,
        'payment_method' => 'cash',
    ]);

    $transaction = SalesTransaction::first();
    expect($transaction->transaction_code)->toStartWith('TXN-');
    $response->assertRedirect(route('pos.receipt', ['codes' => $transaction->transaction_code]));
});

test('a cart with multiple products creates one transaction row per line item', function () {
    $second = Product::factory()->create(['unit_price' => 10, 'stock_quantity' => 50]);

    $this->actingAs($this->staffUser)->post(route('pos.store'), [
        'transaction_type' => 'walk-in',
        'payment_method' => 'cash',
        'tendered_amount' => 100,
        'items' => json_encode([
            ['product_id' => $this->product->id, 'quantity' => 2],
            ['product_id' => $second->id, 'quantity' => 5],
        ]),
    ]);

    expect(SalesTransaction::count())->toBe(2);
    expect($this->product->fresh()->stock_quantity)->toBe(98);
    expect($second->fresh()->stock_quantity)->toBe(45);
});

test('recording a loan sale requires a customer and increases their credit balance by the full cart total', function () {
    $customer = User::factory()->create(['credit_balance' => 0]);
    $customer->assignRole('customer');
    $second = Product::factory()->create(['unit_price' => 10, 'stock_quantity' => 50]);

    $this->actingAs($this->staffUser)->post(route('pos.store'), [
        'transaction_type' => 'walk-in',
        'payment_method' => 'loan',
        'customer_id' => $customer->id,
        'items' => json_encode([
            ['product_id' => $this->product->id, 'quantity' => 2],
            ['product_id' => $second->id, 'quantity' => 1],
        ]),
    ]);

    expect((float) $customer->fresh()->credit_balance)->toBe(60.0);
});

test('a loan sale without a customer fails validation', function () {
    $this->actingAs($this->staffUser)->post(route('pos.store'), [
        'transaction_type' => 'walk-in',
        'payment_method' => 'loan',
        'items' => json_encode([['product_id' => $this->product->id, 'quantity' => 1]]),
    ])->assertSessionHasErrors('customer_id');
});

test('an empty cart fails validation', function () {
    $this->actingAs($this->staffUser)->post(route('pos.store'), [
        'transaction_type' => 'walk-in',
        'payment_method' => 'cash',
        'items' => json_encode([]),
    ])->assertSessionHasErrors('items');
});

test('attempting to sell more than the available stock is rejected and stock is left untouched', function () {
    $this->product->update(['stock_quantity' => 5]);

    $this->actingAs($this->staffUser)->post(route('pos.store'), [
        'transaction_type' => 'walk-in',
        'payment_method' => 'cash',
        'tendered_amount' => 10000,
        'items' => json_encode([['product_id' => $this->product->id, 'quantity' => 100]]),
    ])->assertSessionHasErrors('items');

    expect($this->product->fresh()->stock_quantity)->toBe(5);
    expect(SalesTransaction::count())->toBe(0);
});

test('an oversold line item in a multi-item cart rolls back the whole transaction', function () {
    $this->product->update(['stock_quantity' => 5]);
    $second = Product::factory()->create(['unit_price' => 10, 'stock_quantity' => 50]);

    $this->actingAs($this->staffUser)->post(route('pos.store'), [
        'transaction_type' => 'walk-in',
        'payment_method' => 'cash',
        'tendered_amount' => 10000,
        'items' => json_encode([
            ['product_id' => $second->id, 'quantity' => 2],
            ['product_id' => $this->product->id, 'quantity' => 100],
        ]),
    ])->assertSessionHasErrors('items');

    expect($second->fresh()->stock_quantity)->toBe(50);
    expect($this->product->fresh()->stock_quantity)->toBe(5);
    expect(SalesTransaction::count())->toBe(0);
});

test('a completed sale shows a printable receipt with all line items and the correct total', function () {
    $second = Product::factory()->create(['unit_price' => 10, 'stock_quantity' => 50]);

    $response = $this->actingAs($this->staffUser)->post(route('pos.store'), [
        'transaction_type' => 'walk-in',
        'payment_method' => 'cash',
        'customer_name' => 'Guest Buyer',
        'tendered_amount' => 100,
        'items' => json_encode([
            ['product_id' => $this->product->id, 'quantity' => 2],
            ['product_id' => $second->id, 'quantity' => 3],
        ]),
    ]);

    $codes = SalesTransaction::pluck('transaction_code')->implode(',');
    $response->assertRedirect(route('pos.receipt', ['codes' => $codes]));

    $this->actingAs($this->staffUser)->get(route('pos.receipt', ['codes' => $codes]))
        ->assertOk()
        ->assertSee($this->product->name)
        ->assertSee($second->name)
        ->assertSee('Guest Buyer')
        ->assertSee(number_format(80, 2));
});

test('visiting the receipt route with no codes 404s', function () {
    $this->actingAs($this->staffUser)->get(route('pos.receipt'))->assertNotFound();
});

test('a single historical transaction can still be reprinted from its own receipt link', function () {
    $this->actingAs($this->staffUser)->post(route('pos.store'), [
        'transaction_type' => 'walk-in',
        'payment_method' => 'cash',
        'tendered_amount' => 50,
        'items' => json_encode([['product_id' => $this->product->id, 'quantity' => 1]]),
    ]);

    $transaction = SalesTransaction::first();

    $this->actingAs($this->staffUser)->get(route('pos.receipt', ['codes' => $transaction->transaction_code]))
        ->assertOk()
        ->assertSee($transaction->transaction_code);
});
