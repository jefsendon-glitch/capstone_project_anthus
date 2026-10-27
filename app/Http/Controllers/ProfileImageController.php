<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileImageController extends Controller
{
    public function show(Request $request, User $user): StreamedResponse
    {
        abort_unless($request->user()->is($user) && $user->avatar_path, 404);

        $diskName = config('filesystems.profile_image_disk');
        $disk = Storage::disk($diskName);

        abort_unless($disk->exists($user->avatar_path), 404);

        return $disk->response($user->avatar_path);
    }
}
