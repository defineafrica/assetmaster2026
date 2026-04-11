<?php

use App\Http\Controllers\Inventory\GrnController;
use App\Http\Controllers\Inventory\GrnItemController;
use App\Http\Controllers\Inventory\PurchaseRequisitionController;
use App\Http\Controllers\Inventory\ReorderPointController;
use App\Http\Controllers\Inventory\ReservationController;
use App\Http\Controllers\Inventory\StockThresholdController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'web'])->prefix('inventory')->name('inventory.')->group(function () {

    Route::prefix('stock-thresholds')->name('stock-thresholds.')->group(function () {
        Route::get('/', [StockThresholdController::class, 'index'])->name('index');
        Route::get('/create', [StockThresholdController::class, 'create'])->name('create');
        Route::post('/', [StockThresholdController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [StockThresholdController::class, 'edit'])->name('edit');
        Route::put('/{id}', [StockThresholdController::class, 'update'])->name('update');
        Route::delete('/{id}', [StockThresholdController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('grn')->name('grn.')->group(function () {
        Route::get('/', [GrnController::class, 'index'])->name('index');
        Route::get('/create', [GrnController::class, 'create'])->name('create');
        Route::post('/', [GrnController::class, 'store'])->name('store');
        Route::get('/{id}', [GrnController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [GrnController::class, 'edit'])->name('edit');
        Route::put('/{id}', [GrnController::class, 'update'])->name('update');
        Route::delete('/{id}', [GrnController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/post', [GrnController::class, 'post'])->name('post');
        Route::post('/{id}/lock', [GrnController::class, 'lock'])->name('lock');

        Route::post('/{id}/items', [GrnItemController::class, 'store'])->name('items.store');
        Route::put('/{id}/items/{itemId}', [GrnItemController::class, 'update'])->name('items.update');
        Route::delete('/{id}/items/{itemId}', [GrnItemController::class, 'destroy'])->name('items.destroy');
        Route::post('/{id}/items/{itemId}/inspect', [GrnItemController::class, 'inspect'])->name('items.inspect');
    });

    Route::prefix('reservations')->name('reservations.')->group(function () {
        Route::get('/', [ReservationController::class, 'index'])->name('index');
        Route::get('/create', [ReservationController::class, 'create'])->name('create');
        Route::post('/', [ReservationController::class, 'store'])->name('store');
        Route::get('/{id}', [ReservationController::class, 'show'])->name('show');
        Route::post('/{id}/cancel', [ReservationController::class, 'cancel'])->name('cancel');
        Route::post('/{id}/fulfill', [ReservationController::class, 'fulfill'])->name('fulfill');
        Route::delete('/{id}', [ReservationController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('reorder-points')->name('reorder-points.')->group(function () {
        Route::get('/', [ReorderPointController::class, 'index'])->name('index');
        Route::get('/create', [ReorderPointController::class, 'create'])->name('create');
        Route::post('/', [ReorderPointController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [ReorderPointController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ReorderPointController::class, 'update'])->name('update');
        Route::delete('/{id}', [ReorderPointController::class, 'destroy'])->name('destroy');
        Route::get('/report', [ReorderPointController::class, 'report'])->name('report');
    });

    Route::prefix('requisitions')->name('requisitions.')->group(function () {
        Route::get('/', [PurchaseRequisitionController::class, 'index'])->name('index');
        Route::get('/create', [PurchaseRequisitionController::class, 'create'])->name('create');
        Route::post('/', [PurchaseRequisitionController::class, 'store'])->name('store');
        Route::get('/{id}', [PurchaseRequisitionController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [PurchaseRequisitionController::class, 'edit'])->name('edit');
        Route::put('/{id}', [PurchaseRequisitionController::class, 'update'])->name('update');
        Route::delete('/{id}', [PurchaseRequisitionController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/submit', [PurchaseRequisitionController::class, 'submit'])->name('submit');
        Route::post('/{id}/approve', [PurchaseRequisitionController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [PurchaseRequisitionController::class, 'reject'])->name('reject');
        Route::post('/{id}/fulfill', [PurchaseRequisitionController::class, 'fulfill'])->name('fulfill');
    });

});
