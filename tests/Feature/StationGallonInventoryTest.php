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
        'name' => 'Round', 'size' => '5 gallons', 'quantity' => 10,
    ])->assertSessionHasNoErrors();
    $id = DB::table('station_gallons')->value('id');
    foreach ([
        ['action' => 'fill', 'quantity' => 6],
        ['action' => 'release', 'quantity' => 4],
        ['action' => 'return', 'quantity' => 1, 'return_status' => 'empty'],
        ['action' => 'return', 'quantity' => 1, 'return_status' => 'damaged'],
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
    $this->get(route('admin.water-production.index'))->assertOk()->assertSee('Gallon Inventory');
    $this->assertDatabaseCount('gallon_stocks', 0);
    $this->get(route('admin.inventory.gallon-inventory'))->assertOk()->assertSee('Station-Owned Gallon Containers');
    $this->post(route('admin.inventory.gallon-inventory.move'), [
        'gallon_id' => $id, 'customer_id' => $this->customer->id,
        'action' => 'return', 'quantity' => 2,
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
        'name' => 'Round', 'size' => '5 gallons', 'quantity' => 10,
    ])->assertSessionHasNoErrors();
    $id = DB::table('station_gallons')->value('id');
    $url = route('admin.inventory.gallon-inventory.update', $id);

    $this->patch($url, ['name' => 'Slim', 'size' => '20 liters', 'empty' => 999])
        ->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('station_gallons', [
        'id' => $id, 'name' => 'Slim', 'size' => '20 liters', 'empty' => 10,
    ]);
    $this->assertDatabaseCount('station_gallon_movements', 1);

    $this->patch($url, ['name' => '', 'size' => ''])->assertSessionHasErrors(['name', 'size']);
    $this->assertDatabaseHas('station_gallons', ['id' => $id, 'name' => 'Slim', 'size' => '20 liters']);
    $this->patch(route('admin.inventory.gallon-inventory.update', $id + 1), [
        'name' => 'Missing', 'size' => '5 gallons',
    ])->assertNotFound();

    $this->actingAs($this->customer)->patch($url, ['name' => 'Changed', 'size' => '1 liter'])->assertForbidden();
    $this->assertDatabaseHas('station_gallons', ['id' => $id, 'name' => 'Slim']);
});

test('edit quantity adjustments update totals and reject excessive deductions atomically', function () {
    $this->actingAs($this->admin)->post(route('admin.inventory.gallon-inventory.store'), [
        'name' => 'Round', 'size' => '5 gallons', 'quantity' => 10,
    ])->assertSessionHasNoErrors();
    $id = DB::table('station_gallons')->value('id');
    $url = route('admin.inventory.gallon-inventory.update', $id);
    $details = ['name' => 'Round', 'size' => '5 gallons', 'edit_gallon_id' => $id];

    $this->patch($url, $details + ['quantity_action' => 'increase', 'adjustment_quantity' => 5])
        ->assertSessionHasNoErrors();
    $this->assertDatabaseHas('station_gallons', ['id' => $id, 'empty' => 15]);
    $this->get(route('admin.inventory.gallon-inventory'))
        ->assertSee('Total Gallons</p><p class="text-xl font-semibold tabular-nums">15</p>', false);

    $this->patch($url, $details + ['quantity_action' => 'decrease', 'adjustment_quantity' => 15, 'adjustment_notes' => 'Opening count correction'])
        ->assertSessionHasNoErrors();
    $this->assertDatabaseHas('station_gallons', ['id' => $id, 'empty' => 0]);
    $this->assertDatabaseHas('station_gallon_movements', [
        'station_gallon_id' => $id, 'action' => 'decrease', 'from_status' => 'empty',
        'to_status' => 'removed', 'quantity' => 15, 'notes' => 'Opening count correction',
    ]);
    $this->get(route('admin.inventory.gallon-inventory'))
        ->assertSee('Total Gallons</p><p class="text-xl font-semibold tabular-nums">0</p>', false);

    $this->patch($url, ['name' => 'Changed', 'size' => '5 gallons', 'quantity_action' => 'decrease', 'adjustment_quantity' => 1])
        ->assertSessionHasErrors('adjustment_quantity');
    $this->assertDatabaseHas('station_gallons', ['id' => $id, 'name' => 'Round', 'empty' => 0]);
    foreach ([null, 0, -1, 1.5, 1000001] as $quantity) {
        $this->patch($url, $details + ['quantity_action' => 'increase', 'adjustment_quantity' => $quantity])
            ->assertSessionHasErrors('adjustment_quantity');
    }
    $this->assertDatabaseCount('station_gallon_movements', 3);
});

test('reset requires confirmation and admin access and supports history or entire inventory', function () {
    $this->actingAs($this->admin)->post(route('admin.inventory.gallon-inventory.store'), [
        'name' => 'Round', 'size' => '5 gallons', 'quantity' => 10,
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
        'name' => 'Slim', 'size' => '5 gallons', 'quantity' => 3,
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('station_gallons', ['name' => 'Slim', 'empty' => 3]);
});

test('monthly movement filter and report include only the selected month across pages', function () {
    $this->actingAs($this->admin)->post(route('admin.inventory.gallon-inventory.store'), [
        'name' => 'Round', 'size' => '5 gallons', 'quantity' => 10,
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
        'name' => 'Round', 'size' => '5 gallons', 'quantity' => 10,
    ])->assertForbidden();
    $this->assertDatabaseCount('station_gallons', 0);
});
