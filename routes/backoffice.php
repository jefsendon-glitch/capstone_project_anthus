<?php

use App\Http\Controllers\Admin\ConsumableController;
use App\Http\Controllers\Admin\ConsumableStockController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductStockController;
use App\Http\Controllers\Admin\StockMovementController;
use App\Http\Controllers\Admin\WaterProductionController;
use App\Http\Controllers\ConsumableRestockController;
use App\Http\Controllers\Staff\DeliveryController;
use App\Http\Controllers\Staff\PaymentController;
use App\Http\Controllers\Staff\POSController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin|staff'])->group(function () {
    // Products/Consumables/Maintenance: index+show viewable by staff; create/update/destroy
    // are gated inside each controller/policy to admin only.
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('products', ProductController::class)->except('show');
        Route::post('products/{product}/restore', [ProductController::class, 'restore'])->name('products.restore');
        Route::delete('products/{product}/permanent', [ProductController::class, 'forceDelete'])->name('products.force-delete');
        Route::post('products/{product}/stock/add', [ProductStockController::class, 'add'])->name('products.stock.add');
        Route::post('products/{product}/stock/update', [ProductStockController::class, 'update'])->name('products.stock.update');
        Route::post('products/{product}/stock/adjust', [ProductStockController::class, 'adjust'])->name('products.stock.adjust');

        Route::get('consumables/report', [ConsumableController::class, 'report'])->name('consumables.report');
        Route::resource('consumables', ConsumableController::class)->except('show');
        Route::post('consumables/{consumable}/quick-update', [ConsumableController::class, 'quickUpdate'])->name('consumables.quick-update');
        Route::resource('maintenance', MaintenanceController::class)->except('show');

        Route::post('consumables/{consumable}/restock', [ConsumableRestockController::class, 'store'])->name('consumables.restock');
        Route::post('consumables/{consumable}/stock/update', [ConsumableStockController::class, 'update'])->name('consumables.stock.update');
        Route::post('consumables/{consumable}/stock/adjust', [ConsumableStockController::class, 'adjust'])->name('consumables.stock.adjust');

        Route::middleware('role:admin')->group(function () {
            Route::get('inventory/gallon-inventory', [\App\Http\Controllers\Admin\StationGallonController::class, 'index'])->name('inventory.gallon-inventory');
            Route::get('inventory/gallon-inventory/report', [\App\Http\Controllers\Admin\StationGallonController::class, 'report'])->name('inventory.gallon-inventory.report');
            Route::post('inventory/gallon-inventory', [\App\Http\Controllers\Admin\StationGallonController::class, 'store'])->name('inventory.gallon-inventory.store');
            Route::post('inventory/gallon-inventory/movements', [\App\Http\Controllers\Admin\StationGallonController::class, 'move'])->name('inventory.gallon-inventory.move');
            Route::delete('inventory/gallon-inventory/reset', [\App\Http\Controllers\Admin\StationGallonController::class, 'reset'])->name('inventory.gallon-inventory.reset');
            Route::patch('inventory/gallon-inventory/{gallon}', [\App\Http\Controllers\Admin\StationGallonController::class, 'update'])->whereNumber('gallon')->name('inventory.gallon-inventory.update');
        });

        Route::resource('water-production', WaterProductionController::class)->only(['index']);
        Route::delete('water-production/reset', [WaterProductionController::class, 'reset'])
            ->middleware('role:admin')->name('water-production.reset');

        Route::get('stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');
        Route::delete('stock-movements/reset', [StockMovementController::class, 'reset'])->middleware('role:admin')->name('stock-movements.reset');
    });

    Route::delete('pos/reset', [POSController::class, 'reset'])->middleware('role:admin')->name('pos.reset');
    Route::resource('pos', POSController::class, ['parameters' => ['pos' => 'transaction']])->only(['index', 'create', 'store', 'show']);
    Route::get('pos-receipt', [POSController::class, 'receipt'])->name('pos.receipt');

    Route::delete('deliveries/reset', [DeliveryController::class, 'reset'])
        ->middleware('role:admin')->name('deliveries.reset');
    Route::resource('deliveries', DeliveryController::class)->only(['index', 'show']);
    Route::patch('deliveries/{delivery}/status', [DeliveryController::class, 'updateStatus'])->name('deliveries.update-status');
    Route::post('deliveries/{delivery}/fulfill', [DeliveryController::class, 'fulfill'])->name('deliveries.fulfill');

    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('payments/{customer}', [PaymentController::class, 'store'])->name('payments.store');
});
