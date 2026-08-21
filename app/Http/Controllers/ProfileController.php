<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->except('avatar'));

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk(config('filesystems.profile_image_disk'))->delete($user->avatar_path);
            }

            $user->avatar_path = $request->file('avatar')->store('profile-photos', config('filesystems.profile_image_disk'));
        }

        $user->save();

        return back()->with('status', 'profile-updated');
    }
}
