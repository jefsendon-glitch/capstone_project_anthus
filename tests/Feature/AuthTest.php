<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('users can view the login page', function () {
    $this->get('/login')->assertOk();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
});

test('users cannot authenticate with an invalid password', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('registering assigns the customer role', function () {
    $response = $this->post('/register', [
        'name' => 'Test Customer',
        'email' => 'newcustomer@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $user = User::where('email', 'newcustomer@example.com')->firstOrFail();

    expect($user->hasRole('customer'))->toBeTrue();
    expect((float) $user->credit_balance)->toBe(0.0);

    $response->assertRedirect(route('login', absolute: false));
    $this->assertGuest();
});

test('users can access their dashboard without email verification', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('customer.dashboard'));
});
