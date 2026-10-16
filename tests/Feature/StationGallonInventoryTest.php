<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
});

test('new inventory independently tracks stock, customer loans and returns', function () {
    $this->actingAs($this->admin)->post(route('admin.inventory.gallon-inventory.store'), [
        'type' => 'Round', 'name' => 'Round', 'size' => '5 gallons', 'quantity' => 10,
    ])->assertSessionHasNoErrors();
    $id = DB::table('station_gallons')->value('id');
    foreach ([
        ['action' => 'fill', 'quantity' => 6],
        ['action' => 'release', 'quantity' => 4],
        ['action' => 'return', 'settle_return' => false, 'quantity' => 1, 'return_status' => 'empty'],
        ['action' => 'return', 'settle_return' => false, 'quantity' => 1, 'return_status' => 'damaged'],
        ['action' => 'missing', 'quantity' => 1, 'from_status' => 'borrowed', 'notes' => 'Lost by customer'],
        ['action' => 'damage', 'quantity' => 1, 'from_status' => 'empty', 'notes' => 'Cracked container'],
    ] as $movement) {
        $this->post(route('admin.inventory.gallon-inventory.move'), $movement + [
            'gallon_id' => $id, 'customer_id' => $this->customer->id,
        ])->assertSessionHasNoErrors();
    }
    $this->assertDatabaseHas('station_gallons', [
        'id' => $id, 'empty' => 4, 'filled' => 2, 'borrowed' => 1,
        'damaged' => 2, 'missing' => 1, 'returned' => 2,
    ]);
    $this->assertDatabaseHas('station_gallon_loans', ['customer_id' => $this->customer->id, 'quantity' => 1]);
    $this->assertDatabaseCount('station_gallon_movements', 7);
    $this->assertDatabaseHas('water_production_logs', [
        'product_id' => null, 'container_name' => 'Round', 'container_size' => '5 gallons',
        'gallons_produced' => 6, 'produced_by' => $this->admin->id,
    ]);
    $this->assertDatabaseCount('water_production_logs', 1);
    $this->get(route('admin.water-production.index'))->assertOk()->assertSee('Gallon Tracking');
    $this->assertDatabaseCount('gallon_stocks', 0);
    $this->get(route('admin.inventory.gallon-inventory'))->assertOk()
        ->assertSee('Station-Owned Gallon Containers')
        ->assertSee('Register Gallon Type')->assertSee('Record Gallon Movement')
        ->assertSee('Container Inventory')->assertSee('Outstanding Customer Gallons')
        ->assertSee('Gallon Movement History')->assertSee('Generate Monthly Report')
        ->assertSee(route('admin.inventory.gallon-inventory.store'), false)
        ->assertSee(route('admin.inventory.gallon-inventory.move'), false)
        ->assertSee(route('admin.inventory.gallon-inventory.update', $id), false)
        ->assertSee($this->customer->name)->assertSee('Lost by customer');
    $this->post(route('admin.inventory.gallon-inventory.move'), [
        'gallon_id' => $id, 'customer_id' => $this->customer->id,
        'action' => 'return', 'settle_return' => false, 'quantity' => 2,
    ])->assertSessionHasErrors('quantity');
    $this->assertDatabaseCount('station_gallon_movements', 7);
    $this->post(route('admin.inventory.gallon-inventory.move'), [
        'gallon_id' => $id, 'action' => 'fill', 'quantity' => 5,
    ])->assertSessionHasErrors('quantity');
    $this->assertDatabaseHas('station_gallons', ['id' => $id, 'empty' => 4, 'filled' => 2]);
    $this->assertDatabaseCount('water_production_logs', 1);
});

test('admin can edit registered gallon details while preserving inventory', function () {
    $this->actingAs($this->admin)->post(route('admin.inventory.gallon-inventory.store'), [
        'type' => 'Round', 'name' => 'Round', 'size' => '5 gallons', 'quantity' => 10,
    ])->assertSessionHasNoErrors();
    $id = DB::table('station_gallons')->value('id');
    $url = route('admin.inventory.gallon-inventory.update', $id);

    $this->patch($url, ['type' => 'Round', 'name' => 'Slim', 'size' => '20 liters', 'empty' => 999])
        ->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('station_gallons', [
        'id' => $id, 'type' => 'Round', 'name' => 'Slim', 'size' => '20 liters', 'empty' => 10,
    ]);
    $this->assertDatabaseCount('station_gallon_movements', 1);

    $this->patch($url, ['type' => 'Round', 'name' => '', 'size' => ''])->assertSessionHasErrors(['name', 'size']);
    $this->assertDatabaseHas('station_gallons', ['id' => $id, 'type' => 'Round', 'name' => 'Slim', 'size' => '20 liters']);
    $this->patch(route('admin.inventory.gallon-inventory.update', $id + 1), [
        'type' => 'Round', 'name' => 'Missing', 'size' => '5 gallons',
    ])->assertNotFound();

    $this->actingAs($this->customer)->patch($url, ['type' => 'Round', 'name' => 'Changed', 'size' => '1 liter'])->assertForbidden();
    $this->assertDatabaseHas('station_gallons', ['id' => $id, 'type' => 'Round', 'name' => 'Slim']);
});

test('edit quantity adjustments update totals and reject excessive deductions atomically', function () {
    $this->actingAs($this->admin)->post(route('admin.inventory.gallon-inventory.store'), [
        'type' => 'Round', 'name' => 'Round', 'size' => '5 gallons', 'quantity' => 10,
    ])->assertSessionHasNoErrors();
    $id = DB::table('station_gallons')->value('id');
    $url = route('admin.inventory.gallon-inventory.update', $id);
    $details = ['type' => 'Round', 'name' => 'Round', 'size' => '5 gallons', 'edit_gallon_id' => $id];

    $this->patch($url, $details + ['quantity_action' => 'increase', 'adjustment_quantity' => 5])
        ->assertSessionHasNoErrors();
    $this->assertDatabaseHas('station_gallons', ['id' => $id, 'empty' => 15]);
    $this->get(route('admin.inventory.gallon-inventory'))
        ->assertViewHas('gallons', fn ($rows) => $rows->sum('empty') === 15);

    $this->patch($url, $details + ['quantity_action' => 'decrease', 'adjustment_quantity' => 15, 'adjustment_notes' => 'Opening count correction'])
        ->assertSessionHasNoErrors();
    $this->assertDatabaseHas('station_gallons', ['id' => $id, 'empty' => 0]);
    $this->assertDatabaseHas('station_gallon_movements', [
        'station_gallon_id' => $id, 'action' => 'decrease', 'from_status' => 'empty',
        'to_status' => 'removed', 'quantity' => 15, 'notes' => 'Opening count correction',
    ]);
    $this->get(route('admin.inventory.gallon-inventory'))
        ->assertViewHas('gallons', fn ($rows) => $rows->sum('empty') === 0);

    $this->patch($url, ['type' => 'Round', 'name' => 'Changed', 'size' => '5 gallons', 'quantity_action' => 'decrease', 'adjustment_quantity' => 1])
        ->assertSessionHasErrors('adjustment_quantity');
    $this->assertDatabaseHas('station_gallons', ['id' => $id, 'type' => 'Round', 'name' => 'Round', 'empty' => 0]);
    foreach ([null, 0, -1, 1.5, 1000001] as $quantity) {
        $this->patch($url, $details + ['quantity_action' => 'increase', 'adjustment_quantity' => $quantity])
            ->assertSessionHasErrors('adjustment_quantity');
    }
    $this->assertDatabaseCount('station_gallon_movements', 3);
});

test('reset requires confirmation and admin access and supports history or entire inventory', function () {
    $this->actingAs($this->admin)->post(route('admin.inventory.gallon-inventory.store'), [
        'type' => 'Round', 'name' => 'Round', 'size' => '5 gallons', 'quantity' => 10,
    ])->assertSessionHasNoErrors();
    $id = DB::table('station_gallons')->value('id');
    foreach (['fill', 'release'] as $action) {
        $this->post(route('admin.inventory.gallon-inventory.move'), [
            'gallon_id' => $id, 'action' => $action, 'quantity' => 2, 'customer_id' => $this->customer->id,
        ])->assertSessionHasNoErrors();
    }
    $url = route('admin.inventory.gallon-inventory.reset');
    $this->delete($url, ['reset_scope' => 'all'])->assertSessionHasErrors('reset_confirmation');
    $this->delete($url, ['reset_scope' => 'invalid', 'reset_confirmation' => 'RESET'])->assertSessionHasErrors('reset_scope');
    $this->assertDatabaseCount('station_gallon_movements', 3);
    $this->actingAs($this->customer)->delete($url, ['reset_scope' => 'all', 'reset_confirmation' => 'RESET'])->assertForbidden();
    $this->assertDatabaseCount('station_gallons', 1);

    $this->actingAs($this->admin)->delete($url, ['reset_scope' => 'history', 'reset_confirmation' => 'RESET'])
        ->assertRedirect(route('admin.inventory.gallon-inventory'))->assertSessionHasNoErrors();
    $this->assertDatabaseCount('station_gallon_movements', 0);
    $this->assertDatabaseHas('station_gallons', ['id' => $id, 'empty' => 8, 'borrowed' => 2]);
    $this->assertDatabaseHas('station_gallon_loans', ['station_gallon_id' => $id, 'quantity' => 2]);

    $this->delete($url, ['reset_scope' => 'all', 'reset_confirmation' => 'RESET'])->assertSessionHasNoErrors();
    $this->assertDatabaseCount('station_gallon_movements', 0);
    $this->assertDatabaseCount('station_gallon_loans', 0);
    $this->assertDatabaseCount('station_gallons', 0);
    $this->assertDatabaseHas('users', ['id' => $this->customer->id]);
    $this->assertDatabaseHas('water_production_logs', [
        'container_name' => 'Round', 'gallons_produced' => 2, 'station_gallon_movement_id' => null,
    ]);
    $this->get(route('admin.water-production.index'))->assertOk()->assertSee('Round');
    $this->post(route('admin.inventory.gallon-inventory.store'), [
        'type' => 'Round', 'name' => 'Slim', 'size' => '5 gallons', 'quantity' => 3,
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('station_gallons', ['type' => 'Round', 'name' => 'Slim', 'empty' => 3]);
});

test('monthly movement filter and report include only the selected month across pages', function () {
    $this->actingAs($this->admin)->post(route('admin.inventory.gallon-inventory.store'), [
        'type' => 'Round', 'name' => 'Round', 'size' => '5 gallons', 'quantity' => 10,
    ])->assertSessionHasNoErrors();
    $id = DB::table('station_gallons')->value('id');
    DB::table('station_gallon_movements')->delete();
    foreach (['2026-09-30 23:59:59', '2026-10-01 00:00:00', '2026-10-31 23:59:59', '2026-11-01 00:00:00'] as $date) {
        DB::table('station_gallon_movements')->insert([
            'station_gallon_id' => $id, 'action' => 'add', 'to_status' => 'empty',
            'quantity' => 3, 'recorded_by' => $this->admin->id, 'notes' => 'Boundary '.$date,
            'created_at' => $date, 'updated_at' => $date,
        ]);
    }
    for ($i = 0; $i < 21; $i++) {
        DB::table('station_gallon_movements')->insert([
            'station_gallon_id' => $id, 'action' => 'fill', 'from_status' => 'empty', 'to_status' => 'filled',
            'quantity' => 2, 'recorded_by' => $this->admin->id,
            'created_at' => '2026-10-15 12:00:00', 'updated_at' => '2026-10-15 12:00:00',
        ]);
    }
    $this->get(route('admin.inventory.gallon-inventory', ['month' => '2026-10']))
        ->assertOk()->assertViewHas('movements', fn ($rows) => $rows->total() === 23)
        ->assertSee('month=2026-10', false)->assertDontSee('Boundary 2026-11-01');
    $this->get(route('admin.inventory.gallon-inventory.report', ['month' => '2026-10']))
        ->assertOk()->assertSee('October 2026')
        ->assertSee('Boundary 2026-10-01')->assertSee('Boundary 2026-10-31')
        ->assertDontSee('Boundary 2026-09-30')->assertDontSee('Boundary 2026-11-01')
        ->assertViewHas('movements', fn ($rows) => $rows->count() === 23)
        ->assertViewHas('summary', fn ($summary) => $summary['add'] === 6 && $summary['fill'] === 42);
    $this->get(route('admin.inventory.gallon-inventory.report', ['month' => '2026-08']))
        ->assertOk()->assertSee('No gallon movements recorded');
    $this->get(route('admin.inventory.gallon-inventory', ['month' => '2026-13']))->assertSessionHasErrors('month');
    $this->get(route('admin.inventory.gallon-inventory.report'))->assertSessionHasErrors('month');
    $this->actingAs($this->customer)->get(route('admin.inventory.gallon-inventory.report', ['month' => '2026-10']))->assertForbidden();
});

test('old module is removed and new inventory is admin only', function () {
    $this->actingAs($this->admin)->get('/admin/gallon-stocks')->assertNotFound();
    $this->actingAs($this->customer)->get(route('admin.inventory.gallon-inventory'))->assertForbidden();
    $this->post(route('admin.inventory.gallon-inventory.store'), [
        'type' => 'Round', 'name' => 'Round', 'size' => '5 gallons', 'quantity' => 10,
    ])->assertForbidden();
    $this->assertDatabaseCount('station_gallons', 0);
});


test('missing reports transfer only available stock and preserve totals on rejection', function () {
    $this->actingAs($this->admin)->post(route('admin.inventory.gallon-inventory.store'), [
        'name' => 'Station A', 'type' => 'Slim', 'size' => '5 gallons', 'quantity' => 5,
    ])->assertSessionHasNoErrors();
    $id = DB::table('station_gallons')->value('id');
    $url = route('admin.inventory.gallon-inventory.move');
    $report = ['gallon_id' => $id, 'action' => 'missing', 'quantity' => 2, 'notes' => 'Count discrepancy'];
    $this->post($url, $report)->assertSessionHasErrors('customer_id');
    $this->post($url, $report + ['from_status' => 'empty'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('station_gallons', ['id' => $id, 'empty' => 3, 'missing' => 2, 'type' => 'Slim']);
    $this->post($url, array_replace($report, ['from_status' => 'empty', 'quantity' => 4]))->assertSessionHasErrors('quantity');
    $this->post($url, $report + ['from_status' => 'borrowed'])->assertSessionHasErrors('customer_id');
    $this->assertDatabaseHas('station_gallons', ['id' => $id, 'empty' => 3, 'missing' => 2]);
    $this->assertDatabaseCount('station_gallon_movements', 2);
});

test('unreturned customer gallons are recorded as missing against the correct borrower', function () {
    $this->actingAs($this->admin)->post(route('admin.inventory.gallon-inventory.store'), [
        'name' => 'Station containers', 'type' => 'Round Gallon', 'size' => '5 gallons', 'quantity' => 10,
    ])->assertSessionHasNoErrors();
    $id = DB::table('station_gallons')->value('id');
    $url = route('admin.inventory.gallon-inventory.move');
    foreach (['fill', 'release'] as $action) {
        $this->post($url, [
            'gallon_id' => $id, 'action' => $action, 'quantity' => 4, 'customer_id' => $this->customer->id,
        ])->assertSessionHasNoErrors();
    }
    $report = [
        'gallon_id' => $id, 'action' => 'missing', 'quantity' => 3,
        'customer_id' => $this->customer->id, 'notes' => 'Customer did not return the containers',
    ];
    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole('customer');
    $this->post($url, array_replace($report, ['customer_id' => $otherCustomer->id]))
        ->assertSessionHasErrors('quantity');
    $this->post($url, $report)->assertSessionHasNoErrors();
    $this->assertDatabaseHas('station_gallons', [
        'id' => $id, 'empty' => 6, 'filled' => 0, 'borrowed' => 1, 'missing' => 3, 'returned' => 0,
    ]);
    $this->assertDatabaseHas('station_gallon_loans', [
        'station_gallon_id' => $id, 'customer_id' => $this->customer->id, 'quantity' => 1,
    ]);
    $this->assertDatabaseHas('station_gallon_movements', [
        'station_gallon_id' => $id, 'action' => 'missing', 'from_status' => 'borrowed',
        'to_status' => 'missing', 'quantity' => 3, 'customer_id' => $this->customer->id,
        'recorded_by' => $this->admin->id, 'notes' => $report['notes'],
    ]);
    $this->post($url, $report)->assertSessionHasErrors('quantity');
    $this->post($url, [
        'gallon_id' => $id, 'action' => 'return', 'settle_return' => false, 'quantity' => 1, 'customer_id' => $this->customer->id,
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('station_gallons', [
        'id' => $id, 'empty' => 7, 'borrowed' => 0, 'missing' => 3, 'returned' => 1,
    ]);
    $this->get(route('admin.inventory.gallon-inventory'))->assertOk()
        ->assertSee($this->customer->name)->assertSee($report['notes']);
    $this->assertDatabaseCount('station_gallon_movements', 5);
});


test('short returns automatically record missing and damaged customer gallons atomically', function () {
    $this->actingAs($this->admin)->post(route('admin.inventory.gallon-inventory.store'), [
        'name' => 'Station', 'type' => 'Round Gallon', 'size' => '5 gallons', 'quantity' => 10,
    ])->assertSessionHasNoErrors();
    $id = DB::table('station_gallons')->value('id');
    $url = route('admin.inventory.gallon-inventory.move');
    foreach (['fill', 'release'] as $action) {
        $this->post($url, ['gallon_id' => $id, 'action' => $action, 'quantity' => 10,
            'customer_id' => $this->customer->id])->assertSessionHasNoErrors();
    }
    $return = ['gallon_id' => $id, 'action' => 'return', 'quantity' => 7,
        'damaged_quantity' => 2, 'customer_id' => $this->customer->id];
    $this->post($url, array_replace($return, ['damaged_quantity' => 8]))->assertSessionHasErrors('damaged_quantity');
    $this->post($url, array_replace($return, ['quantity' => 11]))->assertSessionHasErrors('quantity');
    $this->assertDatabaseCount('station_gallon_movements', 3);
    $this->post($url, $return)->assertSessionHasNoErrors();
    $this->assertDatabaseHas('station_gallons', ['id' => $id, 'borrowed' => 0, 'empty' => 5,
        'damaged' => 2, 'missing' => 3, 'returned' => 7]);
    $this->assertDatabaseHas('station_gallon_loans', ['station_gallon_id' => $id,
        'customer_id' => $this->customer->id, 'quantity' => 0]);
    foreach (['empty' => 5, 'damaged' => 2, 'missing' => 3] as $status => $quantity) {
        $this->assertDatabaseHas('station_gallon_movements', ['station_gallon_id' => $id,
            'customer_id' => $this->customer->id, 'from_status' => 'borrowed', 'to_status' => $status,
            'action' => $status === 'missing' ? 'missing' : 'return', 'quantity' => $quantity]);
    }
    $this->get(route('admin.inventory.gallon-inventory', ['month' => '2020-01']))->assertOk()
        ->assertSee('Customer Missing &amp; Damaged Return Records', false)
        ->assertViewHas('customerIssues', fn ($rows) => $rows->count() === 2
            && $rows->every(fn ($row) => $row->customer_id === $this->customer->id));
    $this->post($url, $return)->assertSessionHasErrors('quantity');
    $this->assertDatabaseCount('station_gallon_movements', 6);
});

test('zero returned gallons closes only the selected customer loan as missing', function () {
    $this->actingAs($this->admin)->post(route('admin.inventory.gallon-inventory.store'), [
        'name' => 'Station', 'type' => 'Slim Gallon', 'size' => '5 gallons', 'quantity' => 10,
    ])->assertSessionHasNoErrors();
    $id = DB::table('station_gallons')->value('id');
    $url = route('admin.inventory.gallon-inventory.move');
    $other = User::factory()->create(); $other->assignRole('customer');
    $this->post($url, ['gallon_id' => $id, 'action' => 'fill', 'quantity' => 10])->assertSessionHasNoErrors();
    foreach ([$this->customer, $other] as $customer) {
        $this->post($url, ['gallon_id' => $id, 'action' => 'release', 'quantity' => 5,
            'customer_id' => $customer->id])->assertSessionHasNoErrors();
    }
    $this->post($url, ['gallon_id' => $id, 'action' => 'return', 'quantity' => 0,
        'damaged_quantity' => 0, 'customer_id' => $this->customer->id])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('station_gallons', ['id' => $id, 'borrowed' => 5, 'missing' => 5, 'returned' => 0]);
    $this->assertDatabaseHas('station_gallon_loans', ['station_gallon_id' => $id, 'customer_id' => $other->id, 'quantity' => 5]);
    $this->assertDatabaseHas('station_gallon_movements', ['station_gallon_id' => $id,
        'customer_id' => $this->customer->id, 'action' => 'missing', 'quantity' => 5]);
});
