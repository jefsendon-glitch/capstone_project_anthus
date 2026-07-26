<?php

use App\Models\Product;
use App\Models\Staff;
use App\Models\User;
use App\Notifications\LowStockAlert;
use App\Notifications\NewDeliveryOrderPlaced;
use App\Notifications\OrderStatusUpdated;
use App\Services\DeliveryService;
use App\Services\SalesService;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->staffUser = User::factory()->create();
    $this->staffUser->assignRole('staff');
    Staff::factory()->create(['user_id' => $this->staffUser->id]);

    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');

    $this->product = Product::factory()->create(['unit_price' => 30, 'stock_quantity' => 10, 'low_stock_threshold' => 5]);
});

test('placing a delivery order notifies admin and staff', function () {
    app(DeliveryService::class)->placeOrder([
        'product_id' => $this->product->id,
        'quantity' => 1,
        'customer_address' => 'Somewhere',
    ], $this->customer);

    expect($this->admin->fresh()->unreadNotifications()->where('type', NewDeliveryOrderPlaced::class)->count())->toBe(1);
    expect($this->staffUser->fresh()->unreadNotifications()->where('type', NewDeliveryOrderPlaced::class)->count())->toBe(1);
    expect($this->customer->fresh()->unreadNotifications()->count())->toBe(0);
});

test('fulfilling a delivery order notifies the customer', function () {
    $order = app(DeliveryService::class)->placeOrder([
        'product_id' => $this->product->id,
        'quantity' => 1,
        'customer_address' => 'Somewhere',
    ], $this->customer);

    $this->customer->notifications()->delete();

    app(DeliveryService::class)->updateStatus($order, 'confirmed', $this->staffUser);
    app(DeliveryService::class)->fulfill($order, 'cash', $this->staffUser);

    expect($this->customer->fresh()->unreadNotifications()->where('type', OrderStatusUpdated::class)->count())->toBe(1);
});

test('updating a delivery status notifies the customer', function () {
    $order = app(DeliveryService::class)->placeOrder([
        'product_id' => $this->product->id,
        'quantity' => 1,
        'customer_address' => 'Somewhere',
    ], $this->customer);

    $this->customer->notifications()->delete();

    $this->actingAs($this->staffUser)->patch(route('deliveries.update-status', $order), ['status' => 'confirmed']);

    expect($this->customer->fresh()->unreadNotifications()->where('type', OrderStatusUpdated::class)->count())->toBe(1);
});

test('a sale that pushes stock below threshold notifies admin and staff exactly once', function () {
    $this->actingAs($this->staffUser)->post(route('pos.store'), [
        'transaction_type' => 'walk-in',
        'payment_method' => 'cash',
        'tendered_amount' => 200,
        'items' => json_encode([['product_id' => $this->product->id, 'quantity' => 6]]),
    ]);

    expect($this->product->fresh()->stock_quantity)->toBe(4);
    expect($this->admin->fresh()->unreadNotifications()->where('type', LowStockAlert::class)->count())->toBe(1);

    // A second sale while still low stock should not create a duplicate unread alert.
    $this->actingAs($this->staffUser)->post(route('pos.store'), [
        'transaction_type' => 'walk-in',
        'payment_method' => 'cash',
        'tendered_amount' => 50,
        'items' => json_encode([['product_id' => $this->product->id, 'quantity' => 1]]),
    ]);

    expect($this->admin->fresh()->unreadNotifications()->where('type', LowStockAlert::class)->count())->toBe(1);
});

test('a user can mark their own notification as read and gets redirected to its url', function () {
    app(DeliveryService::class)->placeOrder([
        'product_id' => $this->product->id,
        'quantity' => 1,
        'customer_address' => 'Somewhere',
    ], $this->customer);

    $notification = $this->admin->fresh()->unreadNotifications()->first();

    $response = $this->actingAs($this->admin)->post(route('notifications.read', $notification));

    $response->assertRedirect($notification->data['url']);
    expect($this->admin->fresh()->unreadNotifications()->count())->toBe(0);
});

test('a user cannot mark another users notification as read', function () {
    app(DeliveryService::class)->placeOrder([
        'product_id' => $this->product->id,
        'quantity' => 1,
        'customer_address' => 'Somewhere',
    ], $this->customer);

    $notification = $this->admin->fresh()->unreadNotifications()->first();

    $this->actingAs($this->staffUser)->post(route('notifications.read', $notification))->assertForbidden();
    expect($this->admin->fresh()->unreadNotifications()->count())->toBe(1);
});

test('mark all as read clears every unread notification for the user', function () {
    app(DeliveryService::class)->placeOrder([
        'product_id' => $this->product->id,
        'quantity' => 1,
        'customer_address' => 'Somewhere',
    ], $this->customer);

    app(SalesService::class)->recordSale([
        'transaction_type' => 'walk-in',
        'payment_method' => 'cash',
        'tendered_amount' => 200,
        'customer_id' => null,
        'customer_name' => 'Guest',
        'notes' => null,
        'items' => [['product_id' => $this->product->id, 'quantity' => 6]],
    ], $this->staffUser);

    expect($this->admin->fresh()->unreadNotifications()->count())->toBe(2);

    $this->actingAs($this->admin)->post(route('notifications.read-all'));

    expect($this->admin->fresh()->unreadNotifications()->count())->toBe(0);
});
