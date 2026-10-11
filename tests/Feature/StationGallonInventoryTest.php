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

test('old module is removed and new inventory is admin only', function () {
    $this->actingAs($this->admin)->get('/admin/gallon-stocks')->assertNotFound();
    $this->actingAs($this->customer)->get(route('admin.inventory.gallon-inventory'))->assertForbidden();
    $this->post(route('admin.inventory.gallon-inventory.store'), [
        'name' => 'Round', 'size' => '5 gallons', 'quantity' => 10,
    ])->assertForbidden();
    $this->assertDatabaseCount('station_gallons', 0);
});
