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
        $user = $request->user()->loadMissing('staff');
        // Existing full names are shown as suggestions only; the user can confirm compound surnames.
        $legacyParts = preg_split('/\s+/u', trim($user->name), -1, PREG_SPLIT_NO_EMPTY);
        $lastName = count($legacyParts) > 1 ? array_pop($legacyParts) : '';

        return view('profile.edit', [
            'user' => $user,
            'profileNames' => [
                'first_name' => $user->first_name ?? implode(' ', $legacyParts),
                'middle_name' => $user->middle_name,
                'last_name' => $user->last_name ?? $lastName,
            ],
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
