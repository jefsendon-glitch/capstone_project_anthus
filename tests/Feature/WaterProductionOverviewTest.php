<?php

use App\Models\Product;
use App\Models\User;
use App\Models\WaterProductionLog;
use Database\Seeders\RoleSeeder;

test('production overview separates sources and filters all summary quantities', function () {
    $this->seed(RoleSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $product = Product::factory()->create(['name' => 'Purified Water', 'category' => 'purified']);
    WaterProductionLog::create(['product_id' => $product->id, 'gallons_produced' => 12,
        'production_date' => '2026-10-10', 'produced_by' => $admin->id]);
    WaterProductionLog::create(['container_name' => 'Round', 'container_size' => '5 gallons',
        'gallons_produced' => 4, 'production_date' => '2026-10-11', 'produced_by' => $admin->id]);
    WaterProductionLog::create(['container_name' => 'Slim', 'container_size' => '5 gallons',
        'gallons_produced' => 8, 'production_date' => '2026-09-01', 'produced_by' => $admin->id]);
    $product->delete();
    $url = route('admin.water-production.index');
    $this->actingAs($admin)->get($url)->assertOk()->assertSee('Purified Water')
        ->assertViewHas('summary', ['batches' => 3, 'productUnits' => 12, 'filledContainers' => 12]);
    $this->get($url.'?source=gallon&date_from=2026-10-01&date_to=2026-10-31')->assertOk()
        ->assertViewHas('summary', ['batches' => 1, 'productUnits' => 0, 'filledContainers' => 4]);
    $this->get($url.'?search=Purified')->assertOk()
        ->assertViewHas('logs', fn ($logs) => $logs->total() === 1);
    $this->get($url.'?source=invalid')->assertSessionHasErrors('source');
    $this->get($url.'?date_from=2026-10-12&date_to=2026-10-01')->assertSessionHasErrors('date_to');
    $this->get($url.'?date_to=2026-10-31')->assertOk();
});
