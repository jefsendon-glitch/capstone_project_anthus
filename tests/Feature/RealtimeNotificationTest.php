<?php

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

test('a user can retrieve their live notifications', function () {
    $user = User::factory()->create();

    DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'test',
        'notifiable_type' => $user->getMorphClass(),
        'notifiable_id' => $user->id,
        'data' => ['title' => 'Order update', 'message' => 'Your order is confirmed.', 'url' => '/customer/orders/1'],
    ]);

    $this->actingAs($user)
        ->getJson(route('realtime.notifications'))
        ->assertOk()
        ->assertJsonPath('notifications.0.title', 'Order update');
});

test('guests cannot retrieve live notifications', function () {
    $this->getJson(route('realtime.notifications'))->assertUnauthorized();
});
