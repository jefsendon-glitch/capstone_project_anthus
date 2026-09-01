<?php

use App\Http\Controllers\Admin\ConsumableController;
use App\Http\Controllers\Admin\ConsumableStockController;
use App\Http\Controllers\Admin\GallonStockController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductStockController;
use App\Http\Controllers\Admin\StockMovementController;
use App\Http\Controllers\Admin\WaterProductionController;
use App\Http\Controllers\ConsumableRestockController;
use App\Http\Controllers\GallonStockMovementController;
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
        Route::post('products/{product}/stock/add', [ProductStockController::class, 'add'])->name('products.stock.add');
        Route::post('products/{product}/stock/update', [ProductStockController::class, 'update'])->name('products.stock.update');
        Route::post('products/{product}/stock/adjust', [ProductStockController::class, 'adjust'])->name('products.stock.adjust');

        Route::resource('consumables', ConsumableController::class)->except('show');
        Route::resource('maintenance', MaintenanceController::class)->except('show');

        Route::post('consumables/{consumable}/restock', [ConsumableRestockController::class, 'store'])->name('consumables.restock');
        Route::post('consumables/{consumable}/stock/update', [ConsumableStockController::class, 'update'])->name('consumables.stock.update');
        Route::post('consumables/{consumable}/stock/adjust', [ConsumableStockController::class, 'adjust'])->name('consumables.stock.adjust');

        Route::get('gallon-stocks', [GallonStockController::class, 'index'])->name('gallon-stocks.index');
        Route::post('gallon-stocks/{product}/add', [GallonStockMovementController::class, 'addStock'])->name('gallon-stocks.add');
        Route::post('gallon-stocks/{product}/transfer', [GallonStockMovementController::class, 'transfer'])->name('gallon-stocks.transfer');

        Route::resource('water-production', WaterProductionController::class)->only(['index']);

        Route::get('stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');
    });

    Route::resource('pos', POSController::class, ['parameters' => ['pos' => 'transaction']])->only(['index', 'create', 'store', 'show']);
    Route::get('pos-receipt', [POSController::class, 'receipt'])->name('pos.receipt');

    Route::resource('deliveries', DeliveryController::class)->only(['index', 'show']);
    Route::patch('deliveries/{delivery}/status', [DeliveryController::class, 'updateStatus'])->name('deliveries.update-status');
    Route::post('deliveries/{delivery}/fulfill', [DeliveryController::class, 'fulfill'])->name('deliveries.fulfill');

    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('payments/{customer}', [PaymentController::class, 'store'])->name('payments.store');
});
