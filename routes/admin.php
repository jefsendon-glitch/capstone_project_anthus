<?php

use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProcurementController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\PurchaseOrderReceiptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('inventory/supplies-usage', [\App\Http\Controllers\Admin\SupplyUsageController::class, 'index'])->name('inventory.supply-usages.index');
    Route::post('inventory/supplies-usage', [\App\Http\Controllers\Admin\SupplyUsageController::class, 'store'])->name('inventory.supply-usages.store');
    Route::get('/dashboard/activities', [DashboardController::class, 'activities'])->name('dashboard.activities');

    Route::resource('staff', StaffController::class)->except('show');
    Route::resource('customers', CustomerController::class)->only(['index', 'show', 'edit', 'update']);
    Route::resource('suppliers', SupplierController::class)->except('show');
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::get('procurement/receiving', [ProcurementController::class, 'receiving'])->name('procurement.receiving');
    Route::get('procurement/reorders', [ProcurementController::class, 'reorders'])->name('procurement.reorders');
    Route::get('procurement/history', [ProcurementController::class, 'history'])->name('procurement.history');
    Route::post('purchase-orders/{purchase_order}/receive', [PurchaseOrderReceiptController::class, 'store'])->name('purchase-orders.receive');
    Route::post('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');

    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('financial/reports/{report}/export', [\App\Http\Controllers\Admin\FinancialController::class, 'export'])->name('financial.reports.export');
    Route::get('financial/reports/{report}', [\App\Http\Controllers\Admin\FinancialController::class, 'show'])->name('financial.reports.show');
    Route::delete('financial/reports/{report}', [\App\Http\Controllers\Admin\FinancialController::class, 'destroyReport'])->name('financial.reports.destroy');
    Route::post('financial/reports', [\App\Http\Controllers\Admin\FinancialController::class, 'generate'])->name('financial.reports.generate');
    Route::post('financial/expenses', [\App\Http\Controllers\Admin\FinancialController::class, 'storeExpense'])->name('financial.expenses.store');
    Route::delete('financial/collections/reset', [\App\Http\Controllers\Admin\FinancialController::class, 'resetCollections'])->name('financial.collections.reset');
    Route::get('financial/{section}', [\App\Http\Controllers\Admin\FinancialController::class, 'index'])->whereIn('section', ['sales', 'expenses', 'collections', 'exports'])->name('financial.section');
});
