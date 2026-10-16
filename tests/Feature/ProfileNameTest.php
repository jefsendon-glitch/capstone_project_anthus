<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

test('every account role can save separate name fields while keeping the display name connected', function (string $role) {
    $this->seed(RoleSeeder::class);
    $user = User::factory()->create(['name' => 'Maria Clara de la Cruz']);
    $user->assignRole($role);
    $this->actingAs($user)->get(route('profile.edit'))->assertOk()
        ->assertSee('First Name')->assertSee('Middle Name (Optional)')->assertSee('Last Name');
    $this->patch(route('profile.update'), [
        'first_name' => ' María Clara ', 'middle_name' => ' Santos ', 'last_name' => ' de la Cruz ',
        'email' => $user->email, 'name' => 'Ignored name', 'status' => 'inactive',
    ])->assertSessionHasNoErrors()->assertSessionHas('status', 'profile-updated');
    $user->refresh();
    expect($user->first_name)->toBe('María Clara')->and($user->middle_name)->toBe('Santos')
        ->and($user->last_name)->toBe('de la Cruz')->and($user->name)->toBe('María Clara Santos de la Cruz')
        ->and($user->status)->toBe('active');
    $this->get(route('profile.edit'))->assertOk()->assertSee('María Clara Santos de la Cruz');
    $this->patch(route('profile.update'), ['first_name' => 'María Clara', 'middle_name' => '',
        'last_name' => 'de la Cruz', 'email' => $user->email])->assertSessionHasNoErrors();
    expect($user->fresh()->name)->toBe('María Clara de la Cruz')->and($user->fresh()->middle_name)->toBeNull();
})->with(['admin', 'staff', 'customer']);

test('profile requires first and last names and rejects oversized combined names', function () {
    $user = User::factory()->create(['name' => 'Original Name']);
    $this->actingAs($user)->patch(route('profile.update'), ['first_name' => ' ', 'last_name' => '',
        'email' => $user->email])->assertSessionHasErrors(['first_name', 'last_name']);
    $this->patch(route('profile.update'), ['first_name' => str_repeat('A', 100), 'middle_name' => str_repeat('B', 100),
        'last_name' => str_repeat('C', 100), 'email' => $user->email])->assertSessionHasErrors('last_name');
    expect($user->fresh()->name)->toBe('Original Name');
});

test('legacy name suggestions preserve the saved name until confirmed and follow admin name edits', function () {
    $this->seed(RoleSeeder::class);
    $user = User::factory()->create(['name' => 'Maria Clara de la Cruz']);
    $user->assignRole('customer');
    $this->actingAs($user)->get(route('profile.edit'))->assertOk()
        ->assertViewHas('profileNames', fn ($parts) => $parts['first_name'].' '.$parts['last_name'] === $user->name);
    expect($user->fresh()->first_name)->toBeNull()->and($user->fresh()->name)->toBe('Maria Clara de la Cruz');
    $user->update(['first_name' => 'Maria Clara', 'last_name' => 'de la Cruz']);
    $user->update(['name' => 'Updated Account Name']);
    expect($user->fresh()->first_name)->toBeNull()->and($user->fresh()->last_name)->toBeNull()
        ->and($user->fresh()->name)->toBe('Updated Account Name');
});
