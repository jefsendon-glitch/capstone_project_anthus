<?php

use App\Models\Product;
use App\Models\User;
use App\Models\StockMovement;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

test('gallon reset clears only connected water balances and records the stock reset', function (string $scope) {
    $this->seed(RoleSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $customer = User::factory()->create();
    $customer->assignRole('customer');
    $water = Product::factory()->create(['name' => 'Alkaline Water', 'category' => 'alkaline',
        'requires_container_choice' => true, 'stock_quantity' => 0, 'unit_price' => 30]);
    $unconnected = Product::factory()->create(['category' => 'purified', 'requires_container_choice' => true,
        'round_stock' => 2, 'slim_stock' => 3, 'stock_quantity' => 5]);
    $other = Product::factory()->create(['category' => 'other', 'requires_container_choice' => false, 'stock_quantity' => 15]);
    $this->actingAs($admin);
    foreach (['Round Gallon' => 5, 'Slim Gallon' => 3] as $type => $quantity) {
        $this->post(route('admin.inventory.gallon-inventory.store'), ['name' => 'Station containers', 'type' => $type,
            'size' => '5 Gallon', 'quantity' => 10])->assertSessionHasNoErrors();
        $id = DB::table('station_gallons')->where('type', $type)->value('id');
        $this->post(route('admin.inventory.gallon-inventory.move'), ['gallon_id' => $id, 'action' => 'fill',
            'quantity' => $quantity, 'product_id' => $water->id])->assertSessionHasNoErrors();
    }
    $roundId = DB::table('station_gallons')->where('type', 'Round Gallon')->value('id');
    $this->post(route('admin.inventory.gallon-inventory.move'), ['gallon_id' => $roundId,
        'action' => 'release', 'customer_id' => $customer->id, 'quantity' => 2])->assertSessionHasNoErrors();
    $this->post(route('pos.store'), ['transaction_type' => 'walk-in', 'payment_method' => 'cash', 'tendered_amount' => 100,
        'items' => json_encode([['product_id' => $water->id, 'quantity' => 2, 'container_type' => 'slim']])])
        ->assertSessionHasNoErrors();
    $url = route('admin.inventory.gallon-inventory.reset');
    $this->delete($url, ['reset_scope' => $scope, 'reset_confirmation' => 'RESET'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('products', ['id' => $water->id, 'stock_quantity' => 0, 'round_stock' => 0, 'slim_stock' => 0]);
    expect($unconnected->fresh()->stock_quantity)->toBe(5);
    expect($other->fresh()->stock_quantity)->toBe(15);
    $this->assertDatabaseCount('sales_transactions', 1);
    $this->assertDatabaseCount('station_gallon_movements', 0);
    $this->assertDatabaseHas('stock_movements', ['movable_id' => $water->id, 'type' => 'adjustment_decrease',
        'quantity_before' => 6, 'quantity_after' => 0, 'quantity_delta' => -6, 'recorded_by' => $admin->id]);
    if ($scope === 'history') {
        $this->assertDatabaseHas('station_gallon_loans', ['station_gallon_id' => $roundId, 'quantity' => 2]);
        $this->assertDatabaseHas('station_gallons', ['id' => $roundId, 'filled' => 3, 'borrowed' => 2]);
    } else {
        $this->assertDatabaseCount('station_gallon_loans', 0);
        $this->assertDatabaseCount('station_gallons', 0);
    }
    $count = StockMovement::count();
    $this->delete($url, ['reset_scope' => $scope, 'reset_confirmation' => 'RESET'])->assertSessionHasNoErrors();
    expect(StockMovement::count())->toBe($count);
})->with(['history', 'all']);
