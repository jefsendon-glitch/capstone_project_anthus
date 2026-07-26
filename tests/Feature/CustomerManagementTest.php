<?php

use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->staffUser = User::factory()->create(['email' => 'staff-shaunti@example.com']);
    $this->staffUser->assignRole('staff');
    Staff::factory()->create(['user_id' => $this->staffUser->id]);

    $this->customer = User::factory()->create(['name' => 'Juan Dela Cruz', 'email' => 'customer-shaunti@example.com']);
    $this->customer->assignRole('customer');
});

test('staff cannot access customer management', function () {
    $this->actingAs($this->staffUser)->get(route('admin.customers.index'))->assertForbidden();
});

test('searching customers only matches customer-role accounts, never staff or admin', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.customers.index', ['search' => 'shaunti']));

    $response->assertOk();
    $response->assertViewHas('customers', function ($customers) {
        return $customers->pluck('id')->contains($this->customer->id)
            && ! $customers->pluck('id')->contains($this->staffUser->id)
            && ! $customers->pluck('id')->contains($this->admin->id);
    });
});

test('searching customers by name still works', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.customers.index', ['search' => 'Juan']));

    $response->assertViewHas('customers', function ($customers) {
        return $customers->pluck('id')->contains($this->customer->id);
    });
});
