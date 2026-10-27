<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:2000'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'first_name' => trim($request->first_name),
            'middle_name' => trim($request->middle_name),
            'last_name' => trim($request->last_name),
            'email' => $request->email,
            'contact_number' => $request->contact_number,
            'address' => $request->address,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('customer');

        return redirect(route('login', absolute: false))->with('status', 'account-created');
    }
}
