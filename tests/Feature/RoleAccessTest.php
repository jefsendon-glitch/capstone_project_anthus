<?php

use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

function makeStaffUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('staff');
    Staff::factory()->create(['user_id' => $user->id]);

    return $user;
}

function makeCustomerUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('customer');

    return $user;
}

test('admin is redirected to the admin dashboard', function () {
    $this->actingAs(makeAdmin())->get('/dashboard')->assertRedirect(route('admin.dashboard'));
});

test('staff is redirected to the staff dashboard', function () {
    $this->actingAs(makeStaffUser())->get('/dashboard')->assertRedirect(route('staff.dashboard'));
});

test('customer is redirected to the customer dashboard', function () {
    $this->actingAs(makeCustomerUser())->get('/dashboard')->assertRedirect(route('customer.dashboard'));
});

test('staff cannot access the admin dashboard', function () {
    $this->actingAs(makeStaffUser())->get(route('admin.dashboard'))->assertForbidden();
});

test('customer cannot access the admin dashboard', function () {
    $this->actingAs(makeCustomerUser())->get(route('admin.dashboard'))->assertForbidden();
});

test('only admin can manage customer accounts', function () {
    $this->actingAs(makeAdmin())->get(route('admin.customers.index'))->assertOk();
    $this->actingAs(makeStaffUser())->get(route('admin.customers.index'))->assertForbidden();
    $this->actingAs(makeCustomerUser())->get(route('admin.customers.index'))->assertForbidden();
});

test('staff can view products but cannot create them', function () {
    $staff = makeStaffUser();

    $this->actingAs($staff)->get(route('admin.products.index'))->assertOk();
    $this->actingAs($staff)->get(route('admin.products.create'))->assertForbidden();
});

test('only admin can access reports', function () {
    $this->actingAs(makeAdmin())->get(route('admin.reports.index'))->assertOk();
    $this->actingAs(makeStaffUser())->get(route('admin.reports.index'))->assertForbidden();
});
