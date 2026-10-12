<?php

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WaterProductionLog;
use Database\Seeders\RoleSeeder;

test('only admin can reset confirmed production logs without changing stock or movements', function () {
    $this->seed(RoleSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $customer = User::factory()->create();
    $customer->assignRole('customer');
    $product = Product::factory()->create(['category' => 'purified', 'stock_quantity' => 20]);
    foreach (['2026-09-01', '2026-10-01'] as $date) {
        WaterProductionLog::create([
            'product_id' => $product->id, 'gallons_produced' => 10,
            'produced_by' => $admin->id, 'production_date' => $date,
        ]);
    }
    $movement = StockMovement::record($product, 'production', 10, 10, 20, $admin->id);
    $url = route('admin.water-production.reset');
    $this->actingAs($staff)->delete($url, ['reset_confirmation' => 'RESET'])->assertForbidden();
    $this->actingAs($customer)->delete($url, ['reset_confirmation' => 'RESET'])->assertForbidden();
    $this->actingAs($admin)->delete($url)->assertSessionHasErrors('reset_confirmation');
    $this->delete($url, ['reset_confirmation' => 'wrong'])->assertSessionHasErrors('reset_confirmation');
    $this->assertDatabaseCount('water_production_logs', 2);

    $this->delete($url, ['reset_confirmation' => 'RESET', 'date_from' => '2026-10-01'])
        ->assertRedirect(route('admin.water-production.index'))->assertSessionHasNoErrors();
    $this->assertDatabaseCount('water_production_logs', 0);
    $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 20]);
    $this->assertDatabaseHas('stock_movements', ['id' => $movement->id]);
    $this->get(route('admin.water-production.index'))->assertOk()->assertSee('No production logged yet');
});
