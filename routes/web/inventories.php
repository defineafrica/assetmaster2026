<?php

use App\Http\Controllers\Inventories\InventoriesController;
use App\Http\Controllers\Inventories\InventoryCheckoutController;
use App\Http\Controllers\Inventories\InventoryCheckinController;
use App\Http\Controllers\Inventories\BulkInventoriesController;
use App\Models\Inventory;
use App\Models\Setting;
use Tabuna\Breadcrumbs\Trail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Inventory Routes
|--------------------------------------------------------------------------
|
| Register all the inventory routes.
|
*/
Route::group(
    [
        'prefix' => 'inventories',
        'middleware' => ['auth'], 
    ],
    
    function () {
        
        Route::get('bulkaudit', [InventoriesController::class, 'quickScan'])
            ->name('inventories.bulkaudit')
            ->breadcrumbs(fn (Trail $trail) =>
            $trail->parent('inventories.index')
                ->push(trans('general.bulkaudit'), route('inventories.bulkaudit'))
            );

        Route::get('quickscancheckin', [InventoriesController::class, 'quickScanCheckin'])
            ->name('inventories.quickscancheckin')
            ->breadcrumbs(fn (Trail $trail) =>
            $trail->parent('inventories.index')
                ->push('Quickscan Checkin', route('inventories.quickscancheckin'))
            );

        Route::get('requested', [InventoriesController::class, 'getRequestedIndex'])
            ->name('inventories.requested')
            ->breadcrumbs(fn (Trail $trail) =>
            $trail->parent('inventories.index')
                ->push(trans('admin/hardware/general.requested'), route('inventories.requested'))
            );

        Route::get('audit/due', [InventoriesController::class, 'dueForAudit'])
            ->name('inventories.audit.due')
            ->breadcrumbs(fn (Trail $trail) =>
            $trail->parent('inventories.index')
                ->push(trans_choice('general.audit_due_days', Setting::getSettings()->audit_warning_days, ['days' => Setting::getSettings()->audit_warning_days]), route('inventories.audit.due'))
            );

        Route::get('checkins/due',
            [InventoriesController::class, 'dueForCheckin']
        )->name('inventories.checkins.due')
            ->breadcrumbs(fn (Trail $trail) =>
            $trail->parent('inventories.index')
                ->push(trans_choice('general.checkin_due_days', Setting::getSettings()->due_checkin_days, ['days' => Setting::getSettings()->due_checkin_days]), route('inventories.checkins.due'))
            );
        
        Route::get('{inventory}/audit', [InventoriesController::class, 'audit'])
            ->name('inventory.audit.create')
            ->breadcrumbs(fn (Trail $trail, Inventory $inventory) =>
            $trail->parent('inventories.show', $inventory)
                ->push(trans('general.audit'))
            );

        Route::post('{inventory}/audit',
            [InventoriesController::class, 'auditStore']
        )->name('inventory.audit.store');

        Route::get('history', [InventoriesController::class, 'getImportHistory'])
            ->name('inventories.import-history')
            ->breadcrumbs(fn (Trail $trail) =>
                $trail->parent('inventories.index')
                ->push(trans('general.import-history'), route('inventories.import-history'))
            );

        Route::post('history',
            [InventoriesController::class, 'postImportHistory']
        )->name('inventories.process-import-history');

        Route::get('bytag/{any?}',
            [InventoriesController::class, 'getInventoryByTag']
        )->where('any', '.*')->name('findbytag/inventories');

        Route::get('byserial/{any?}',
            [InventoriesController::class, 'getInventoryBySerial']
        )->where('any', '.*')->name('findbyserial/inventories');

        Route::get('{inventory}/clone',
            [InventoriesController::class, 'getClone']
        )->name('clone/inventories')->withTrashed();

        Route::get('{inventoryId}/label',
            [InventoriesController::class, 'getLabel']
        )->name('label/inventories');

        Route::get('{inventory}/checkout', [InventoryCheckoutController::class, 'create'])
            ->name('inventories.checkout.create')
            ->breadcrumbs(fn (Trail $trail, Inventory $inventory) =>
            $trail->parent('inventories.show', $inventory)
                ->push(trans('admin/hardware/general.checkout'), route('inventories.index'))
            );

        Route::post('{inventoryId}/checkout',
            [InventoryCheckoutController::class, 'store']
        )->name('inventories.checkout.store');

        Route::get('{inventory}/checkin/{backto?}',
            [InventoryCheckinController::class, 'create']
        )->name('inventories.checkin.create')
        ->breadcrumbs(fn (Trail $trail, Inventory $inventory) =>
        $trail->parent('inventories.show', $inventory)
            ->push(trans('admin/hardware/general.checkin'), route('inventories.index'))
        );

        Route::post('{inventoryId}/checkin/{backto?}',
            [InventoryCheckinController::class, 'store']
        )->name('inventories.checkin.store');

        Route::get('{inventoryId}/view', function ($inventoryId) {
            return redirect()->route('inventories.show', $inventoryId);
        });

        Route::get('{inventory}/qr_code',
            [InventoriesController::class, 'getQrCode']
        )->name('qr_code/inventories')->withTrashed();

        Route::get('{inventory}/barcode',
            [InventoriesController::class, 'getBarCode']
        )->name('barcode/inventories')->withTrashed();

        Route::post('{inventory}/restore',
            [InventoriesController::class, 'getRestore']
        )->name('restore/inventories')->withTrashed();

        Route::post(
            'bulkedit',
            [BulkInventoriesController::class, 'edit']
        )->name('inventories.bulkedit.show')
        ->breadcrumbs(fn (Trail $trail) =>
        $trail->parent('inventories.index')
            ->push(trans('general.bulk_delete'), route('inventories.index')));

        Route::post(
            'bulkdelete',
            [BulkInventoriesController::class, 'destroy']
        )->name('inventories.bulkdelete.store');

        Route::post(
            'bulkrestore',
            [BulkInventoriesController::class, 'restore']
        )->name('inventories.bulkrestore');

        Route::post(
            'bulksave',
            [BulkInventoriesController::class, 'update']
        )->name('inventories.bulksave');

        Route::get('bulkcheckout', [BulkInventoriesController::class, 'showCheckout'])
            ->name('inventories.bulkcheckout.show')
            ->breadcrumbs(fn (Trail $trail) =>
            $trail->parent('inventories.index')
                ->push(trans('admin/hardware/general.bulk_checkout'), route('inventories.index'))
            );

        Route::post('bulkcheckout',
            [BulkInventoriesController::class, 'storeCheckout']
        )->name('inventories.bulkcheckout.store');

    });

Route::resource('inventories',
        InventoriesController::class,
        ['middleware' => ['auth']
])->parameters(['inventories' => 'inventory'])->withTrashed();
