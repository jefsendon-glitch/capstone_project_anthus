<?php

use App\Http\Controllers\Staff\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});
