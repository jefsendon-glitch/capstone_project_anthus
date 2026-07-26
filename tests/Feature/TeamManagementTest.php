<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('admin can create a staff account', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.staff.store'), [
        'name' => 'New Staff',
        'email' => 'newstaff@example.com',
        'password' => 'Password123!',
        'role' => 'staff',
        'employee_id' => 'EMP-9001',
        'position' => 'Delivery Driver',
    ]);

    $response->assertRedirect(route('admin.staff.index'));

    $user = User::where('email', 'newstaff@example.com')->firstOrFail();
    expect($user->hasRole('staff'))->toBeTrue();
    expect($user->staff)->not->toBeNull();
    expect($user->staff->employee_id)->toBe('EMP-9001');
});

test('admin can create another admin account', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.staff.store'), [
        'name' => 'Second Admin',
        'email' => 'second-admin@example.com',
        'password' => 'Password123!',
        'role' => 'admin',
    ]);

    $response->assertRedirect(route('admin.staff.index'));

    $user = User::where('email', 'second-admin@example.com')->firstOrFail();
    expect($user->hasRole('admin'))->toBeTrue();
    expect($user->staff)->toBeNull();
});

test('creating a staff account requires an employee id', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.staff.store'), [
        'name' => 'No Employee Id',
        'email' => 'noeid@example.com',
        'password' => 'Password123!',
        'role' => 'staff',
    ]);

    $response->assertSessionHasErrors('employee_id');
});

test('staff and customers cannot access team management', function () {
    $staffUser = User::factory()->create();
    $staffUser->assignRole('staff');

    $customerUser = User::factory()->create();
    $customerUser->assignRole('customer');

    $this->actingAs($staffUser)->get(route('admin.staff.index'))->assertForbidden();
    $this->actingAs($customerUser)->get(route('admin.staff.index'))->assertForbidden();
});

test('admin cannot delete their own account', function () {
    $response = $this->actingAs($this->admin)->delete(route('admin.staff.destroy', $this->admin));

    $response->assertRedirect();
    $this->assertModelExists($this->admin);
});

test('admin can delete a staff account', function () {
    $staffUser = User::factory()->create();
    $staffUser->assignRole('staff');

    $response = $this->actingAs($this->admin)->delete(route('admin.staff.destroy', $staffUser));

    $response->assertRedirect(route('admin.staff.index'));
    $this->assertSoftDeleted($staffUser);
});

test('admin can change a staff members role to admin', function () {
    $staffUser = User::factory()->create();
    $staffUser->assignRole('staff');
    \App\Models\Staff::factory()->create(['user_id' => $staffUser->id]);

    $response = $this->actingAs($this->admin)->put(route('admin.staff.update', $staffUser), [
        'name' => $staffUser->name,
        'email' => $staffUser->email,
        'role' => 'admin',
    ]);

    $response->assertRedirect(route('admin.staff.index'));
    expect($staffUser->fresh()->hasRole('admin'))->toBeTrue();
    expect($staffUser->fresh()->staff)->toBeNull();
});
