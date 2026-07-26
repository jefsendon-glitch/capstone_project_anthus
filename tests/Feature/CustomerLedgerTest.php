<?php

use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->staffUser = User::factory()->create();
    $this->staffUser->assignRole('staff');
    Staff::factory()->create(['user_id' => $this->staffUser->id]);

    $this->customer = User::factory()->create(['credit_balance' => 100]);
    $this->customer->assignRole('customer');
});

test('staff can record a payment that reduces the customers balance', function () {
    $response = $this->actingAs($this->staffUser)->post(route('payments.store', $this->customer), [
        'amount' => 40,
    ]);

    $response->assertRedirect();

    expect((float) $this->customer->fresh()->credit_balance)->toBe(60.0);

    $this->assertDatabaseHas('customer_payments', [
        'customer_id' => $this->customer->id,
        'amount' => 40,
        'recorded_by' => $this->staffUser->id,
    ]);
});

test('customers cannot access the payments ledger', function () {
    $this->actingAs($this->customer)->get(route('payments.index'))->assertForbidden();
});

test('searching the payments ledger only matches customer-role accounts, never staff or admin', function () {
    $this->staffUser->update(['email' => 'staff-shaunti@example.com']);
    $this->customer->update(['email' => 'customer-shaunti@example.com']);

    $response = $this->actingAs($this->staffUser)->get(route('payments.index', ['search' => 'shaunti']));

    $response->assertOk();
    $response->assertViewHas('customers', function ($customers) {
        return $customers->pluck('id')->contains($this->customer->id)
            && ! $customers->pluck('id')->contains($this->staffUser->id);
    });
});
