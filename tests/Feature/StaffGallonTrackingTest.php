<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->operator = User::factory()->create();
    $this->operator->assignRole('staff');
    $this->borrower = User::factory()->create();
    $this->borrower->assignRole('customer');
    $this->actingAs($this->admin)->post(route('admin.inventory.gallon-inventory.store'), [
        'name' => 'Shared container', 'type' => 'Round Gallon', 'size' => '5 gallons', 'quantity' => 10,
    ])->assertSessionHasNoErrors();
    $this->gallonId = DB::table('station_gallons')->value('id');
    $this->post(route('admin.inventory.gallon-inventory.move'), [
        'gallon_id' => $this->gallonId, 'action' => 'fill', 'quantity' => 10,
    ])->assertSessionHasNoErrors();
});

test('staff handovers update shared stock customer balances and attributed missing and damage records', function () {
    $url = route('staff.gallons.move');
    $base = ['gallon_id' => $this->gallonId, 'customer_id' => $this->borrower->id];
    $this->actingAs($this->operator)->get(route('staff.gallons.index'))->assertOk()
        ->assertSee($url, false)->assertSee('Available Filled Gallons')
        ->assertDontSee('Register Gallon Type')->assertDontSee('Stock Adjustment')
        ->assertDontSee('Generate Monthly Report')->assertDontSee('value="fill"', false);
    $this->post($url, $base + ['action' => 'release', 'quantity' => 5])->assertSessionHasNoErrors();
    $this->post($url, $base + ['action' => 'return', 'quantity' => 3, 'damaged_quantity' => 1])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('station_gallons', ['id' => $this->gallonId, 'filled' => 5, 'empty' => 2,
        'borrowed' => 0, 'damaged' => 1, 'missing' => 2, 'returned' => 3]);
    foreach (['empty' => 2, 'damaged' => 1, 'missing' => 2] as $status => $quantity) {
        $this->assertDatabaseHas('station_gallon_movements', ['customer_id' => $this->borrower->id,
            'to_status' => $status, 'quantity' => $quantity, 'recorded_by' => $this->operator->id]);
    }
    $this->get(route('staff.gallons.index', ['issue_search' => $this->borrower->name]))->assertOk()
        ->assertViewHas('customerIssues', fn ($rows) => $rows->total() === 2)
        ->assertSee($this->operator->name)->assertSee(route('staff.gallons.index').'#customer-issues', false);
    $this->actingAs($this->admin)->get(route('admin.inventory.gallon-inventory'))->assertOk()
        ->assertViewHas('customerIssues', fn ($rows) => $rows->total() === 2)->assertSee($this->operator->name);
    $this->actingAs($this->borrower)->get(route('customer.gallons'))->assertOk()->assertViewHas('borrowedCount', 0);
});

test('staff can keep a partial return outstanding and invalid handovers leave balances intact', function () {
    $url = route('staff.gallons.move');
    $base = ['gallon_id' => $this->gallonId, 'customer_id' => $this->borrower->id];
    $this->actingAs($this->operator)->post($url, $base + ['action' => 'release', 'quantity' => 11])->assertSessionHasErrors('quantity');
    $this->post($url, $base + ['action' => 'release', 'quantity' => 5])->assertSessionHasNoErrors();
    $this->post($url, $base + ['action' => 'return', 'quantity' => 6])->assertSessionHasErrors('quantity');
    $this->post($url, $base + ['action' => 'return', 'quantity' => 2, 'damaged_quantity' => 3])->assertSessionHasErrors('damaged_quantity');
    $this->post($url, $base + ['action' => 'return', 'quantity' => 2, 'settle_return' => '0'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('station_gallon_loans', ['customer_id' => $this->borrower->id, 'quantity' => 3]);
    $this->assertDatabaseHas('station_gallons', ['id' => $this->gallonId, 'borrowed' => 3, 'missing' => 0]);
    $this->post($url, $base + ['action' => 'return', 'quantity' => 0])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('station_gallons', ['id' => $this->gallonId, 'borrowed' => 0, 'missing' => 3]);
});

test('staff cannot bypass restricted actions and customers cannot access staff gallon operations', function () {
    $this->actingAs($this->operator);
    foreach (['add', 'fill', 'damage', 'missing'] as $action) {
        $this->post(route('staff.gallons.move'), ['gallon_id' => $this->gallonId,
            'action' => $action, 'quantity' => 1])->assertForbidden();
    }
    $this->post(route('admin.inventory.gallon-inventory.store'), [])->assertForbidden();
    $this->patch(route('admin.inventory.gallon-inventory.update', $this->gallonId), [])->assertForbidden();
    $this->delete(route('admin.inventory.gallon-inventory.reset'), [])->assertForbidden();
    $this->post(route('admin.inventory.gallon-inventory.move'), [])->assertForbidden();
    $this->assertDatabaseCount('station_gallon_movements', 2);
    $this->assertDatabaseHas('station_gallons', ['id' => $this->gallonId, 'filled' => 10]);
    $this->actingAs($this->borrower)->get(route('staff.gallons.index'))->assertForbidden();
    $this->post(route('staff.gallons.move'), [])->assertForbidden();
});
