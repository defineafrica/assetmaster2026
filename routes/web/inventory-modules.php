<?php

use App\Http\Controllers\Inventory\StockThresholdController;
use App\Http\Controllers\Inventory\GrnController;
use App\Http\Controllers\Inventory\ReservationController;
use App\Http\Controllers\Inventory\ReorderPointController;
use App\Http\Controllers\Inventory\PurchaseRequisitionController;
use App\Models\Inventory\StockThreshold\StockThreshold;
use App\Models\Inventory\GoodsReceivedNote\GoodsReceivedNote;
use App\Models\Inventory\GoodsReceivedNote\GrnItem;
use App\Models\Inventory\Reservation\Reservation;
use App\Models\Inventory\ReorderPoint\ReorderPoint;
use App\Models\Inventory\PurchaseRequisition\PurchaseRequisition;
use App\Models\Inventory\PurchaseRequisition\PurchaseRequisitionItem;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'inventory', 'middleware' => ['auth']], function () {

    Route::group(['prefix' => 'stock-thresholds'], function () {
        Route::get('/', [StockThresholdController::class, 'index'])->name('inventory.stock-thresholds.index');
        Route::get('create', [StockThresholdController::class, 'create'])->name('inventory.stock-thresholds.create');
        Route::post('/', [StockThresholdController::class, 'store'])->name('inventory.stock-thresholds.store');
        Route::get('{stockThreshold}', [StockThresholdController::class, 'show'])->name('inventory.stock-thresholds.show');
        Route::get('{stockThreshold}/edit', [StockThresholdController::class, 'edit'])->name('inventory.stock-thresholds.edit');
        Route::put('{stockThreshold}', [StockThresholdController::class, 'update'])->name('inventory.stock-thresholds.update');
        Route::delete('{stockThreshold}', [StockThresholdController::class, 'destroy'])->name('inventory.stock-thresholds.destroy');
    });

    Route::group(['prefix' => 'grn'], function () {
        Route::get('/', [GrnController::class, 'index'])->name('inventory.grn.index');
        Route::get('create', [GrnController::class, 'create'])->name('inventory.grn.create');
        Route::post('/', [GrnController::class, 'store'])->name('inventory.grn.store');
        Route::get('{grn}', [GrnController::class, 'show'])->name('inventory.grn.show');
        Route::get('{grn}/edit', [GrnController::class, 'edit'])->name('inventory.grn.edit');
        Route::put('{grn}', [GrnController::class, 'update'])->name('inventory.grn.update');
        Route::delete('{grn}', [GrnController::class, 'destroy'])->name('inventory.grn.destroy');
        Route::post('{grn}/post', [GrnController::class, 'post'])->name('inventory.grn.post');
        Route::post('{grn}/lock', [GrnController::class, 'lock'])->name('inventory.grn.lock');
        Route::post('{grn}/approve', [GrnController::class, 'approve'])->name('inventory.grn.approve');

        Route::post('{grn}/items', [GrnController::class, 'addItem'])->name('inventory.grn.items.store');
        Route::put('{grn}/items/{item}', [GrnController::class, 'updateItem'])->name('inventory.grn.items.update');
        Route::delete('{grn}/items/{item}', [GrnController::class, 'destroyItem'])->name('inventory.grn.items.destroy');
        Route::post('{grn}/items/{item}/inspect', [GrnController::class, 'inspect'])->name('inventory.grn.items.inspect');
    });

    Route::group(['prefix' => 'reservations'], function () {
        Route::get('/', [ReservationController::class, 'index'])->name('inventory.reservations.index');
        Route::get('create', [ReservationController::class, 'create'])->name('inventory.reservations.create');
        Route::post('/', [ReservationController::class, 'store'])->name('inventory.reservations.store');
        Route::get('{reservation}', [ReservationController::class, 'show'])->name('inventory.reservations.show');
        Route::get('{reservation}/edit', [ReservationController::class, 'edit'])->name('inventory.reservations.edit');
        Route::put('{reservation}', [ReservationController::class, 'update'])->name('inventory.reservations.update');
        Route::delete('{reservation}', [ReservationController::class, 'destroy'])->name('inventory.reservations.destroy');
        Route::post('{reservation}/fulfill', [ReservationController::class, 'fulfill'])->name('inventory.reservations.fulfill');
        Route::post('{reservation}/cancel', [ReservationController::class, 'cancel'])->name('inventory.reservations.cancel');
        Route::get('availability/{itemType}/{itemId}', [ReservationController::class, 'availability'])->name('inventory.reservations.availability');
    });

    Route::group(['prefix' => 'reorder-points'], function () {
        Route::get('/', [ReorderPointController::class, 'index'])->name('inventory.reorder-points.index');
        Route::get('report', [ReorderPointController::class, 'report'])->name('inventory.reorder-points.report');
        Route::get('create', [ReorderPointController::class, 'create'])->name('inventory.reorder-points.create');
        Route::post('/', [ReorderPointController::class, 'store'])->name('inventory.reorder-points.store');
        Route::get('{reorderPoint}', [ReorderPointController::class, 'show'])->name('inventory.reorder-points.show');
        Route::get('{reorderPoint}/edit', [ReorderPointController::class, 'edit'])->name('inventory.reorder-points.edit');
        Route::put('{reorderPoint}', [ReorderPointController::class, 'update'])->name('inventory.reorder-points.update');
        Route::delete('{reorderPoint}', [ReorderPointController::class, 'destroy'])->name('inventory.reorder-points.destroy');
    });

    Route::group(['prefix' => 'requisitions'], function () {
        Route::get('/', [PurchaseRequisitionController::class, 'index'])->name('inventory.requisitions.index');
        Route::get('create', [PurchaseRequisitionController::class, 'create'])->name('inventory.requisitions.create');
        Route::post('/', [PurchaseRequisitionController::class, 'store'])->name('inventory.requisitions.store');
        Route::get('{purchaseRequisition}', [PurchaseRequisitionController::class, 'show'])->name('inventory.requisitions.show');
        Route::get('{purchaseRequisition}/edit', [PurchaseRequisitionController::class, 'edit'])->name('inventory.requisitions.edit');
        Route::put('{purchaseRequisition}', [PurchaseRequisitionController::class, 'update'])->name('inventory.requisitions.update');
        Route::delete('{purchaseRequisition}', [PurchaseRequisitionController::class, 'destroy'])->name('inventory.requisitions.destroy');
        Route::post('{purchaseRequisition}/submit', [PurchaseRequisitionController::class, 'submit'])->name('inventory.requisitions.submit');
        Route::post('{purchaseRequisition}/approve', [PurchaseRequisitionController::class, 'approve'])->name('inventory.requisitions.approve');
        Route::post('{purchaseRequisition}/reject', [PurchaseRequisitionController::class, 'reject'])->name('inventory.requisitions.reject');
        Route::post('{purchaseRequisition}/fulfill', [PurchaseRequisitionController::class, 'fulfill'])->name('inventory.requisitions.fulfill');

        Route::post('{purchaseRequisition}/items', [PurchaseRequisitionController::class, 'addItem'])->name('inventory.requisitions.items.store');
        Route::put('{purchaseRequisition}/items/{item}', [PurchaseRequisitionController::class, 'updateItem'])->name('inventory.requisitions.items.update');
        Route::delete('{purchaseRequisition}/items/{item}', [PurchaseRequisitionController::class, 'destroyItem'])->name('inventory.requisitions.items.destroy');
    });

});
