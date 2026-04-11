<?php

use App\Http\Controllers\Api;
use App\Http\Controllers\Api\Inventory;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Inventory Module API Routes
|--------------------------------------------------------------------------
|
| These routes extend Snipe-IT for inventory management features including:
| - Stock Thresholds and Low Stock Alerts
| - Goods Received Notes (GRN)
| - Reservations
| - Reorder Points
| - Purchase Requisitions
|
*/

Route::group(['prefix' => 'v1/inventory', 'middleware' => ['api', 'api-throttle:api']], function () {

    /*
    |--------------------------------------------------------------------------
    | Stock Thresholds
    |--------------------------------------------------------------------------
    */
    Route::group(['prefix' => 'stock-thresholds'], function () {
        Route::get('/', [Inventory\StockThresholdController::class, 'index'])->name('api.inventory.stock-thresholds.index');
        Route::post('/', [Inventory\StockThresholdController::class, 'store'])->name('api.inventory.stock-thresholds.store');
        Route::get('{id}', [Inventory\StockThresholdController::class, 'show'])->name('api.inventory.stock-thresholds.show');
        Route::put('{id}', [Inventory\StockThresholdController::class, 'update'])->name('api.inventory.stock-thresholds.update');
        Route::delete('{id}', [Inventory\StockThresholdController::class, 'destroy'])->name('api.inventory.stock-thresholds.destroy');
        Route::get('check/{itemType}/{itemId}', [Inventory\StockThresholdController::class, 'checkThreshold'])->name('api.inventory.stock-thresholds.check');
    });

    /*
    |--------------------------------------------------------------------------
    | Goods Received Notes
    |--------------------------------------------------------------------------
    */
    Route::group(['prefix' => 'grn'], function () {
        Route::get('/', [Inventory\GrnController::class, 'index'])->name('api.inventory.grn.index');
        Route::post('/', [Inventory\GrnController::class, 'store'])->name('api.inventory.grn.store');
        Route::get('{id}', [Inventory\GrnController::class, 'show'])->name('api.inventory.grn.show');
        Route::put('{id}', [Inventory\GrnController::class, 'update'])->name('api.inventory.grn.update');
        Route::delete('{id}', [Inventory\GrnController::class, 'destroy'])->name('api.inventory.grn.destroy');
        Route::post('{id}/post', [Inventory\GrnController::class, 'post'])->name('api.inventory.grn.post');
        Route::post('{id}/lock', [Inventory\GrnController::class, 'lock'])->name('api.inventory.grn.lock');
        Route::post('{id}/approve', [Inventory\GrnController::class, 'approve'])->name('api.inventory.grn.approve');

        Route::post('{id}/items', [Inventory\GrnItemController::class, 'store'])->name('api.inventory.grn.items.store');
        Route::put('{id}/items/{itemId}', [Inventory\GrnItemController::class, 'update'])->name('api.inventory.grn.items.update');
        Route::delete('{id}/items/{itemId}', [Inventory\GrnItemController::class, 'destroy'])->name('api.inventory.grn.items.destroy');

        Route::post('{id}/items/{itemId}/inspect', [Inventory\GrnInspectionController::class, 'inspect'])->name('api.inventory.grn.items.inspect');
    });

    /*
    |--------------------------------------------------------------------------
    | Reservations
    |--------------------------------------------------------------------------
    */
    Route::group(['prefix' => 'reservations'], function () {
        Route::get('/', [Inventory\ReservationController::class, 'index'])->name('api.inventory.reservations.index');
        Route::post('/', [Inventory\ReservationController::class, 'store'])->name('api.inventory.reservations.store');
        Route::get('{id}', [Inventory\ReservationController::class, 'show'])->name('api.inventory.reservations.show');
        Route::put('{id}', [Inventory\ReservationController::class, 'update'])->name('api.inventory.reservations.update');
        Route::delete('{id}', [Inventory\ReservationController::class, 'destroy'])->name('api.inventory.reservations.destroy');
        Route::post('{id}/fulfill', [Inventory\ReservationController::class, 'fulfill'])->name('api.inventory.reservations.fulfill');
        Route::post('{id}/cancel', [Inventory\ReservationController::class, 'cancel'])->name('api.inventory.reservations.cancel');

        Route::get('item/{itemType}/{itemId}/availability', [Inventory\ReservationController::class, 'availability'])->name('api.inventory.reservations.availability');
        Route::get('item/{itemType}/{itemId}/active', [Inventory\ReservationController::class, 'activeReservations'])->name('api.inventory.reservations.active');
    });

    /*
    |--------------------------------------------------------------------------
    | Reorder Points
    |--------------------------------------------------------------------------
    */
    Route::group(['prefix' => 'reorder-points'], function () {
        Route::get('/', [Inventory\ReorderPointController::class, 'index'])->name('api.inventory.reorder-points.index');
        Route::post('/', [Inventory\ReorderPointController::class, 'store'])->name('api.inventory.reorder-points.store');
        Route::get('{id}', [Inventory\ReorderPointController::class, 'show'])->name('api.inventory.reorder-points.show');
        Route::put('{id}', [Inventory\ReorderPointController::class, 'update'])->name('api.inventory.reorder-points.update');
        Route::delete('{id}', [Inventory\ReorderPointController::class, 'destroy'])->name('api.inventory.reorder-points.destroy');
        Route::get('check', [Inventory\ReorderPointController::class, 'checkReorderPoints'])->name('api.inventory.reorder-points.check');
        Route::get('report', [Inventory\ReorderPointController::class, 'report'])->name('api.inventory.reorder-points.report');
    });

    /*
    |--------------------------------------------------------------------------
    | Purchase Requisitions
    |--------------------------------------------------------------------------
    */
    Route::group(['prefix' => 'requisitions'], function () {
        Route::get('/', [Inventory\PurchaseRequisitionController::class, 'index'])->name('api.inventory.requisitions.index');
        Route::post('/', [Inventory\PurchaseRequisitionController::class, 'store'])->name('api.inventory.requisitions.store');
        Route::get('{id}', [Inventory\PurchaseRequisitionController::class, 'show'])->name('api.inventory.requisitions.show');
        Route::put('{id}', [Inventory\PurchaseRequisitionController::class, 'update'])->name('api.inventory.requisitions.update');
        Route::delete('{id}', [Inventory\PurchaseRequisitionController::class, 'destroy'])->name('api.inventory.requisitions.destroy');
        Route::post('{id}/submit', [Inventory\PurchaseRequisitionController::class, 'submit'])->name('api.inventory.requisitions.submit');
        Route::post('{id}/approve', [Inventory\PurchaseRequisitionController::class, 'approve'])->name('api.inventory.requisitions.approve');
        Route::post('{id}/reject', [Inventory\PurchaseRequisitionController::class, 'reject'])->name('api.inventory.requisitions.reject');
        Route::post('{id}/fulfill', [Inventory\PurchaseRequisitionController::class, 'fulfill'])->name('api.inventory.requisitions.fulfill');

        Route::post('{id}/items', [Inventory\PurchaseRequisitionItemController::class, 'store'])->name('api.inventory.requisitions.items.store');
        Route::put('{id}/items/{itemId}', [Inventory\PurchaseRequisitionItemController::class, 'update'])->name('api.inventory.requisitions.items.update');
        Route::delete('{id}/items/{itemId}', [Inventory\PurchaseRequisitionItemController::class, 'destroy'])->name('api.inventory.requisitions.items.destroy');
    });

});
