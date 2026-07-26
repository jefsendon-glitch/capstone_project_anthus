<?php

use App\Models\MaintenanceLog;
use App\Models\Staff;
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

test('admin can create a maintenance log', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.maintenance.store'), [
        'equipment_name' => 'RO Machine #1',
        'category' => 'ro_membrane',
        'next_due_date' => now()->addMonth()->toDateString(),
        'status' => 'ok',
    ]);

    $response->assertRedirect(route('admin.maintenance.index'));
    $this->assertDatabaseHas('maintenance_logs', ['equipment_name' => 'RO Machine #1']);
});

test('staff can view maintenance logs but cannot create one', function () {
    $this->actingAs($this->staffUser)->get(route('admin.maintenance.index'))->assertOk();

    $this->actingAs($this->staffUser)->post(route('admin.maintenance.store'), [
        'equipment_name' => 'Should Fail',
        'category' => 'filter',
        'next_due_date' => now()->addMonth()->toDateString(),
        'status' => 'ok',
    ])->assertForbidden();
});

test('an overdue log with a due date in the past is flagged overdue', function () {
    $log = MaintenanceLog::factory()->create([
        'next_due_date' => now()->subDays(3),
        'status' => 'overdue',
    ]);

    expect($log->is_overdue)->toBeTrue();
});
