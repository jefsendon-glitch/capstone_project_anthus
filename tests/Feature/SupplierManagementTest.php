<?php

use App\Models\Staff;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->staffUser = User::factory()->create();
    $this->staffUser->assignRole('staff');
    Staff::factory()->create(['user_id' => $this->staffUser->id]);
});

test('admin can create a supplier', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.suppliers.store'), [
        'name' => 'AquaParts Supply',
        'contact_person' => 'Jane Doe',
        'phone' => '0917 000 0000',
        'is_active' => '1',
    ]);

    $response->assertRedirect(route('admin.suppliers.index'));
    $this->assertDatabaseHas('suppliers', ['name' => 'AquaParts Supply']);
});

test('staff cannot access supplier management', function () {
    $this->actingAs($this->staffUser)->get(route('admin.suppliers.index'))->assertForbidden();
    $this->actingAs($this->staffUser)->post(route('admin.suppliers.store'), ['name' => 'Should Fail'])->assertForbidden();
});

test('admin can update a supplier', function () {
    $supplier = Supplier::create(['name' => 'Old Name']);

    $this->actingAs($this->admin)->put(route('admin.suppliers.update', $supplier), [
        'name' => 'New Name',
        'is_active' => '1',
    ]);

    expect($supplier->fresh()->name)->toBe('New Name');
});
