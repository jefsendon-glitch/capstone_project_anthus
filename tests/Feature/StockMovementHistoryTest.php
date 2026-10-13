<?php

use App\Models\Consumable;
use App\Models\StockMovement;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->staffUser = User::factory()->create();
    $this->staffUser->assignRole('staff');
    Staff::factory()->create(['user_id' => $this->staffUser->id]);
});

test('admin and staff can both view stock movement history', function () {
    $consumable = Consumable::factory()->create();
    StockMovement::record($consumable, 'restock', 5, 10, 15, $this->admin->id);

    $this->actingAs($this->admin)->get(route('admin.stock-movements.index'))->assertOk()->assertSee('Restock');
    $this->actingAs($this->staffUser)->get(route('admin.stock-movements.index'))->assertOk();
});

test('stock movements can be filtered by item type', function () {
    $consumable = Consumable::factory()->create();
    StockMovement::record($consumable, 'restock', 5, 10, 15, $this->admin->id);

    $response = $this->actingAs($this->admin)->get(route('admin.stock-movements.index', ['item_type' => 'consumable']));

    $response->assertOk();
});

test('admin reset clears both movement histories while preserving stock and a backup', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    $supply = Consumable::factory()->create(['quantity' => 15]);
    StockMovement::record($supply, 'restock', 5, 10, 15, $this->admin->id);
    $this->actingAs($this->admin)->post(route('admin.inventory.gallon-inventory.store'), [
        'name' => 'Reset Test Gallon', 'size' => '5 gallons', 'quantity' => 10,
    ])->assertSessionHasNoErrors();
    $gallon = DB::table('station_gallons')->first();
    $this->delete(route('admin.stock-movements.reset'), ['reset_confirmation' => 'RESET'])
        ->assertRedirect(route('admin.stock-movements.index'));
    $this->assertDatabaseCount('stock_movements', 0);
    $this->assertDatabaseCount('station_gallon_movements', 0);
    expect((float) $supply->fresh()->quantity)->toBe(15.0)
        ->and((int) DB::table('station_gallons')->where('id', $gallon->id)->value('empty'))->toBe(10)
        ->and(\Illuminate\Support\Facades\Storage::disk('local')->files('stock-history-backups'))->toHaveCount(1);
});

test('history reset requires confirmation and staff cannot use it', function () {
    $supply = Consumable::factory()->create();
    StockMovement::record($supply, 'restock', 5, 10, 15, $this->admin->id);
    $this->actingAs($this->admin)->delete(route('admin.stock-movements.reset'), ['reset_confirmation' => 'wrong'])->assertSessionHasErrors('reset_confirmation');
    $this->actingAs($this->staffUser)->delete(route('admin.stock-movements.reset'), ['reset_confirmation' => 'RESET'])->assertForbidden();
    $this->assertDatabaseCount('stock_movements', 1);
});

test('gallon tracking joins the history without treating transfers as stock increases', function () {
    $this->actingAs($this->admin)->post(route('admin.inventory.gallon-inventory.store'), [
        'name' => 'Round Tracking', 'size' => '5 gallons', 'quantity' => 10,
    ])->assertSessionHasNoErrors();
    $id = DB::table('station_gallons')->value('id');
    $this->post(route('admin.inventory.gallon-inventory.move'), [
        'gallon_id' => $id, 'action' => 'fill', 'quantity' => 4,
    ])->assertSessionHasNoErrors();
    $supply = Consumable::factory()->create(['name' => 'Test Supply']);
    StockMovement::record($supply, 'restock', 5, 10, 15, $this->admin->id);
    $supply->delete();

    $this->get(route('admin.stock-movements.index'))->assertOk()
        ->assertSee('Round Tracking')->assertSee('Test Supply')
        ->assertViewHas('movements', fn ($rows) => $rows->total() === 3);
    $this->get(route('admin.stock-movements.index', ['item_type' => 'station_gallon', 'type' => 'fill']))
        ->assertOk()->assertDontSee('Test Supply')
        ->assertViewHas('movements', fn ($rows) => $rows->total() === 1
            && (int) $rows->first()->quantity_delta === 0 && (int) $rows->first()->moved_quantity === 4);
    $this->get(route('admin.stock-movements.index', ['item_type' => 'wrong']))->assertSessionHasErrors('item_type');
    $this->get(route('admin.stock-movements.index', ['date_from' => '2026-10-12', 'date_to' => '2026-10-01']))->assertSessionHasErrors('date_to');
    $this->get(route('admin.stock-movements.index', ['date_to' => '2026-10-01']))->assertOk();
});
