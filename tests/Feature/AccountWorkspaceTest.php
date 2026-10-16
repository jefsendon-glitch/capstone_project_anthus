<?php

use App\Models\Consumable;
use App\Models\CustomerPayment;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->staff = User::factory()->create();
    $this->staff->assignRole('staff');
    Staff::factory()->create(['user_id' => $this->staff->id, 'employee_id' => 'EMP-PORTAL', 'position' => 'Station Operator']);
    $this->customer = User::factory()->create(['credit_balance' => 100, 'address' => 'Customer delivery address']);
    $this->customer->assignRole('customer');
    $this->other = User::factory()->create();
    $this->other->assignRole('customer');
    $this->product = Product::factory()->create(['stock_quantity' => 2, 'low_stock_threshold' => 5, 'is_active' => true]);
});

test('customer account displays only their own admin recorded purchases with search and pagination', function () {
    foreach (range(1, 7) as $i) {
        SalesTransaction::create([
            'transaction_code' => 'PORTAL-'.$i, 'transaction_type' => 'walk-in', 'customer_id' => $this->customer->id,
            'product_id' => $this->product->id, 'product_name' => 'Customer water '.$i, 'quantity' => 1,
            'unit_price' => 10, 'total_amount' => 10, 'payment_method' => 'cash', 'processed_by' => $this->admin->id,
        ]);
    }
    SalesTransaction::create([
        'transaction_code' => 'PRIVATE-OTHER', 'transaction_type' => 'walk-in', 'customer_id' => $this->other->id,
        'product_id' => $this->product->id, 'product_name' => 'Private purchase', 'quantity' => 1,
        'unit_price' => 999, 'total_amount' => 999, 'payment_method' => 'cash', 'processed_by' => $this->admin->id,
    ]);
    Setting::current()->update(['contact_number' => '09123456789']);
    $this->actingAs($this->customer)->get(route('customer.account'))->assertOk()
        ->assertViewHas('purchases', fn ($rows) => $rows->total() === 7 && $rows->count() === 5)
        ->assertSee('09123456789')->assertDontSee('PRIVATE-OTHER')->assertDontSee('Private purchase');
    $this->get(route('customer.account', ['search' => 'PORTAL-1']))->assertOk()
        ->assertViewHas('purchases', fn ($rows) => $rows->total() === 1)->assertSee('PORTAL-1');
    $this->get(route('customer.account', ['page' => 2]))->assertOk()
        ->assertViewHas('purchases', fn ($rows) => $rows->count() === 2);
});

test('customer gallon pages reflect admin borrowing and short returns without exposing another customer', function () {
    $this->actingAs($this->admin)->post(route('admin.inventory.gallon-inventory.store'), [
        'name' => 'Portal container', 'type' => 'Round Gallon', 'size' => '5 gallons', 'quantity' => 10,
    ])->assertSessionHasNoErrors();
    $id = DB::table('station_gallons')->value('id');
    $url = route('admin.inventory.gallon-inventory.move');
    $this->post($url, ['gallon_id' => $id, 'action' => 'fill', 'quantity' => 10])->assertSessionHasNoErrors();
    foreach ([$this->customer, $this->other] as $customer) {
        $this->post($url, ['gallon_id' => $id, 'action' => 'release', 'quantity' => 5, 'customer_id' => $customer->id])->assertSessionHasNoErrors();
    }
    $this->actingAs($this->customer)->get(route('customer.gallons'))->assertOk()
        ->assertViewHas('borrowedCount', 5)->assertViewHas('history', fn ($rows) => $rows->total() === 1);
    $this->actingAs($this->admin)->post($url, [
        'gallon_id' => $id, 'action' => 'return', 'quantity' => 3, 'damaged_quantity' => 1, 'customer_id' => $this->customer->id,
    ])->assertSessionHasNoErrors();
    $this->actingAs($this->customer)->get(route('customer.gallons'))->assertOk()
        ->assertViewHas('borrowedCount', 0)->assertViewHas('history', fn ($rows) => $rows->total() === 4)
        ->assertSee('Damaged return')->assertSee('Missing')->assertDontSee($this->other->name);
    $this->get(route('customer.gallons', ['search' => 'missing']))->assertOk()
        ->assertViewHas('history', fn ($rows) => $rows->total() === 1);
    $this->actingAs($this->other)->get(route('customer.gallons'))->assertOk()
        ->assertViewHas('borrowedCount', 5)->assertViewHas('history', fn ($rows) => $rows->total() === 1);
});

test('staff workspace shows shared stock but only the signed in staff collections', function () {
    CustomerPayment::create(['customer_id' => $this->customer->id, 'customer_name' => $this->customer->name,
        'amount' => 25, 'payment_date' => today(), 'recorded_by' => $this->staff->id]);
    CustomerPayment::create(['customer_id' => $this->other->id, 'customer_name' => 'Private collection customer',
        'amount' => 50, 'payment_date' => today(), 'recorded_by' => $this->admin->id]);
    $this->actingAs($this->staff)->get(route('staff.dashboard'))->assertOk()
        ->assertViewHas('collectionsToday', 25.0)
        ->assertViewHas('recentCollections', fn ($rows) => $rows->count() === 1)
        ->assertSee('EMP-PORTAL')->assertSee('Station Operator')->assertSee($this->product->name)
        ->assertDontSee('Private collection customer');
    $this->get(route('profile.edit'))->assertOk()->assertSee('Employment Details')->assertSee('EMP-PORTAL');
    $this->get(route('admin.customers.index'))->assertForbidden();
    $this->get(route('admin.inventory.gallon-inventory'))->assertForbidden();
    $this->get(route('admin.consumables.index'))->assertForbidden();
    $this->get(route('staff.inventory'))->assertOk()->assertSee($this->product->name);
});

test('customer account data is protected by customer role and linked across navigation', function () {
    $this->actingAs($this->staff)->get(route('customer.account'))->assertForbidden();
    $this->get(route('customer.gallons'))->assertForbidden();
    $this->actingAs($this->customer)->get(route('customer.dashboard'))->assertOk()
        ->assertSee(route('customer.gallons'), false)->assertSee(route('customer.account'), false)
        ->assertSee('Customer delivery address');
    $this->get(route('profile.edit'))->assertOk()->assertSee('Credit Status')->assertSee('Customer Account');
    $this->get(route('staff.dashboard'))->assertForbidden();
    $this->get(route('staff.inventory'))->assertForbidden();
});

test('staff stock overview follows admin stock changes with independent pagination', function () {
    foreach (range(1, 7) as $i) {
        Consumable::factory()->create(['name' => 'Supply '.$i, 'quantity' => 10]);
    }
    $supply = Consumable::where('name', 'Supply 7')->first();
    $supply->update(['quantity' => 3]);
    $this->actingAs($this->staff)->get(route('staff.inventory'))->assertOk()
        ->assertViewHas('supplies', fn ($rows) => $rows->total() === 7 && $rows->count() === 5)
        ->assertDontSee('Unit Cost')->assertDontSee('Adjust Stock');
    $this->get(route('staff.inventory', ['search' => 'Supply 7']))->assertOk()
        ->assertViewHas('supplies', fn ($rows) => $rows->total() === 1 && (float) $rows->first()->quantity === 3.0);
    $this->get(route('staff.inventory', ['supplies_page' => 2]))->assertOk()
        ->assertViewHas('supplies', fn ($rows) => $rows->currentPage() === 2 && $rows->count() === 2);
});

test('staff collection history is private searchable and paginated', function () {
    foreach (range(1, 7) as $i) {
        CustomerPayment::create(['customer_id' => $this->customer->id, 'customer_name' => $this->customer->name,
            'amount' => 10, 'payment_date' => today(), 'recorded_by' => $this->staff->id]);
    }
    CustomerPayment::create(['customer_id' => $this->other->id, 'customer_name' => 'Other operator collection',
        'amount' => 10, 'payment_date' => today(), 'recorded_by' => $this->admin->id]);
    $this->actingAs($this->staff)->get(route('payments.index'))->assertOk()
        ->assertViewHas('collections', fn ($rows) => $rows->total() === 7 && $rows->count() === 5);
    $this->get(route('payments.index', ['collections_page' => 2]))->assertOk()
        ->assertViewHas('collections', fn ($rows) => $rows->count() === 2);
    $this->get(route('payments.index', ['history_search' => 'Other operator collection']))->assertOk()
        ->assertViewHas('collections', fn ($rows) => $rows->total() === 0);
    $this->get(route('payments.index', ['history_from' => today()->addDay()->toDateString()]))->assertOk()
        ->assertViewHas('collections', fn ($rows) => $rows->total() === 0);
    $this->get(route('payments.index', ['history_to' => today()->toDateString()]))->assertOk()
        ->assertViewHas('collections', fn ($rows) => $rows->total() === 7);
});

test('staff can filter sales by operator and date without changing the shared default', function () {
    foreach ([$this->staff, $this->admin] as $operator) {
        SalesTransaction::create(['transaction_code' => 'FILTER-'.$operator->id, 'transaction_type' => 'walk-in',
            'product_id' => $this->product->id, 'product_name' => 'Water', 'quantity' => 1,
            'unit_price' => 10, 'total_amount' => 10, 'payment_method' => 'cash', 'processed_by' => $operator->id]);
    }
    $this->actingAs($this->staff)->get(route('pos.index'))->assertOk()
        ->assertViewHas('transactions', fn ($rows) => $rows->total() === 2);
    $this->get(route('pos.index', ['scope' => 'mine', 'from' => today()->toDateString(), 'to' => today()->toDateString()]))->assertOk()
        ->assertViewHas('transactions', fn ($rows) => $rows->total() === 1 && $rows->first()->processed_by === $this->staff->id);
    $this->get(route('pos.index', ['from' => today()->addDay()->toDateString()]))->assertOk()
        ->assertViewHas('transactions', fn ($rows) => $rows->total() === 0);
    $this->get(route('pos.index', ['to' => today()->toDateString()]))->assertOk()
        ->assertViewHas('transactions', fn ($rows) => $rows->total() === 2);
    $this->get(route('pos.index', ['from' => '2026-10-10', 'to' => '2026-10-01']))->assertSessionHasErrors('to');
});

test('delivery schedule filters exclude completed orders and distinguish active dates', function () {
    foreach (['overdue' => -1, 'today' => 0, 'upcoming' => 1, 'completed' => -2] as $code => $offset) {
        \App\Models\DeliveryOrder::create(['order_code' => 'SCHEDULE-'.$code, 'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name, 'customer_address' => 'Delivery address',
            'product_id' => $this->product->id, 'product_name' => 'Water', 'quantity' => 1,
            'unit_price' => 10, 'total_amount' => 10, 'payment_method' => 'cash',
            'status' => $code === 'completed' ? 'delivered' : 'pending',
            'preferred_delivery_date' => today()->addDays($offset), 'placed_by' => $this->customer->id]);
    }
    $this->actingAs($this->staff);
    foreach (['overdue', 'today', 'upcoming'] as $schedule) {
        $this->get(route('deliveries.index', ['schedule' => $schedule]))->assertOk()
            ->assertViewHas('orders', fn ($rows) => $rows->total() === 1 && $rows->first()->order_code === 'SCHEDULE-'.$schedule);
    }
    $this->get(route('deliveries.index', ['schedule' => 'invalid']))->assertSessionHasErrors('schedule');
});
