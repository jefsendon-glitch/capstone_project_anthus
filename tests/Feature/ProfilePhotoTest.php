<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a user can view an uploaded profile photo', function () {
    Storage::fake('profile-images');
    config()->set('filesystems.profile_image_disk', 'profile-images');

    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'first_name' => 'Profile',
        'last_name' => 'Owner',
        'email' => $user->email,
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ])->assertSessionHas('status', 'profile-updated');

    $user->refresh();

    Storage::disk('profile-images')->assertExists($user->avatar_path);
    $this->actingAs($user)->get($user->avatar_url)->assertOk();
});

test('private cloud profile photos are streamed only to their owner', function () {
    config()->set('filesystems.profile_image_disk', 'private-images');
    config()->set('filesystems.disks.private-images.driver', 's3');
    $user = User::factory()->create(['avatar_path' => 'avatars/private.jpg']);
    $otherUser = User::factory()->create();
    $disk = Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
    $disk->shouldReceive('exists')->with($user->avatar_path)->once()->andReturn(true);
    $disk->shouldReceive('response')->with($user->avatar_path)->once()->andReturn(
        response()->stream(fn () => print('private avatar bytes'), 200, ['Content-Type' => 'image/jpeg'])
    );
    $disk->shouldNotReceive('url');
    Storage::shouldReceive('disk')->with('private-images')->andReturn($disk);

    $this->actingAs($otherUser)->get($user->avatar_url)->assertNotFound();
    $this->actingAs($user)->get($user->avatar_url)->assertOk()->assertHeader('Content-Type', 'image/jpeg')
        ->assertStreamedContent('private avatar bytes');
});
