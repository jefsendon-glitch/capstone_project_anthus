<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfileImageController extends Controller
{
    public function show(Request $request, User $user): BinaryFileResponse|RedirectResponse
    {
        abort_unless(
            $user->avatar_path && ($request->user()->is($user) || $request->user()->hasAnyRole(['admin', 'staff'])),
            404,
        );

        $diskName = config('filesystems.profile_image_disk');
        $disk = Storage::disk($diskName);

        abort_unless($disk->exists($user->avatar_path), 404);

        if (config("filesystems.disks.{$diskName}.driver") !== 'local') {
            return redirect()->away($disk->url($user->avatar_path));
        }

        return response()->file($disk->path($user->avatar_path));
    }
}
