<?php

use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\PurchaseOrderReceiptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/activities', [DashboardController::class, 'activities'])->name('dashboard.activities');

    Route::resource('staff', StaffController::class)->except('show');
    Route::resource('customers', CustomerController::class)->only(['index', 'show', 'edit', 'update']);
    Route::resource('suppliers', SupplierController::class)->except('show');
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/{purchase_order}/receive', [PurchaseOrderReceiptController::class, 'store'])->name('purchase-orders.receive');

    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
});
