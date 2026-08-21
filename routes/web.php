<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileImageController;
use App\Http\Controllers\ProductImageController;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::get('/product-images/{product}', [ProductImageController::class, 'show'])->name('products.image');

Route::get('/', function () {
    return view('welcome', [
        'products' => Product::active()->orderBy('category')->orderBy('name')->take(12)->get(),
    ]);
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile-images/{user}', [ProfileImageController::class, 'show'])->name('profile.image');

    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/staff.php';
require __DIR__.'/customer.php';
require __DIR__.'/backoffice.php';
