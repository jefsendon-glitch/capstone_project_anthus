<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function index(): RedirectResponse
    {
        return match (true) {
            auth()->user()->isAdmin() => redirect()->route('admin.dashboard'),
            auth()->user()->isStaff() => redirect()->route('staff.dashboard'),
            default => redirect()->route('customer.dashboard'),
        };
    }
}
