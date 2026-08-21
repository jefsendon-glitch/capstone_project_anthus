<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a user can view an uploaded profile photo', function () {
    Storage::fake('profile-images');
    config()->set('filesystems.profile_image_disk', 'profile-images');

    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ])->assertSessionHas('status', 'profile-updated');

    $user->refresh();

    Storage::disk('profile-images')->assertExists($user->avatar_path);
    $this->actingAs($user)->get($user->avatar_url)->assertOk();
});
