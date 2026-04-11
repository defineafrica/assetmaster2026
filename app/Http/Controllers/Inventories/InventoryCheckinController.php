<?php

namespace App\Http\Controllers\Inventories;

use App\Events\CheckoutableCheckedIn;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryCheckinRequest;
use App\Http\Traits\MigratesLegacyAssetLocations;
use App\Models\Inventory;
use App\Models\CheckoutAcceptance;
use App\Models\LicenseSeat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use \Illuminate\Contracts\View\View;
use \Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;

class InventoryCheckinController extends Controller
{
    use MigratesLegacyAssetLocations;

    public function create(Inventory $inventory, $backto = null) : View | RedirectResponse
    {
        $this->authorize('checkin', $inventory);

        if (is_null($inventory->assignedTo)) {
            return redirect()->route('inventories.index')->with('error', trans('admin/hardware/message.checkin.already_checked_in'));
        }

        if (!$inventory->model) {
            return redirect()->route('inventories.show', $inventory->id)->with('error', trans('admin/hardware/general.model_invalid_fix'));
        }

        $inventory->setRules($inventory->getRules() + $inventory->customFieldValidationRules());

        if ($inventory->isInvalid()) {
            return redirect()->route('inventories.edit', $inventory)->withErrors($inventory->getErrors());
        }

        $target_option = match ($inventory->assigned_type) {
            'App\Models\Inventory' => trans('admin/hardware/form.redirect_to_type', ['type' => trans('general.inventory_previous')]),
            'App\Models\Location' => trans('admin/hardware/form.redirect_to_type', ['type' => trans('general.location')]),
            default => trans('admin/hardware/form.redirect_to_type', ['type' => trans('general.user')]),
        };
        return view('hardware/inventory/checkin', compact('inventory', 'target_option'))
            ->with('item', $inventory)
            ->with('statusLabel_list', Helper::statusLabelList())
            ->with('backto', $backto)
            ->with('table_name', 'Inventories');
    }

    public function store(InventoryCheckinRequest $request, $inventoryId = null, $backto = null) : RedirectResponse
    {
        if (is_null($inventory = Inventory::find($inventoryId))) {
            return redirect()->route('inventories.index')->with('error', trans('admin/hardware/message.does_not_exist'));
        }

        if (is_null($target = $inventory->assignedTo)) {
            return redirect()->route('inventories.index')->with('error', trans('admin/hardware/message.checkin.already_checked_in'));
        }

        if (!$inventory->model) {
            return redirect()->route('inventories.show', $inventory->id)->with('error', trans('admin/hardware/general.model_invalid_fix'));
        }

        $this->authorize('checkin', $inventory);

        session()->put('checkedInFrom', $inventory->assignedTo->id);
        session()->put('checkout_to_type', match ($inventory->assigned_type) {
            'App\Models\User' => 'user',
            'App\Models\Location' => 'location',
            'App\Models\Inventory' => 'inventory',
        });

        $inventory->expected_checkin = null;
        $inventory->assignedTo()->disassociate($inventory);
        $inventory->accepted = null;
        $inventory->name = $request->input('name');

        if ($request->filled('status_id')) {
            $inventory->status_id = e($request->input('status_id'));
        }

        $inventory->customFieldsForCheckinCheckout('display_checkin');

        $this->migrateLegacyLocations($inventory);

        $inventory->location_id = $inventory->rtd_location_id;

        if ($request->filled('location_id')) {
            Log::debug('NEW Location ID: '.$request->input('location_id'));
            $inventory->location_id = $request->input('location_id');

            if ($request->input('update_default_location') == 0){
                $inventory->rtd_location_id = $request->input('location_id');
            }
        }

        $originalValues = $inventory->getRawOriginal();

        $checkin_at = date('Y-m-d H:i:s');
        if (($request->filled('checkin_at')) && ($request->input('checkin_at') != date('Y-m-d'))) {
            $originalValues['action_date'] = $checkin_at;
            $checkin_at = $request->input('checkin_at');
        }
        $inventory->last_checkin = $checkin_at;

        $inventory->licenseseats->each(function (LicenseSeat $seat) {
            $seat->update(['assigned_to' => null]);
        });

        $acceptances = CheckoutAcceptance::pending()->whereHasMorph('checkoutable',
            [Inventory::class],
            function (Builder $query) use ($inventory) {
                $query->where('id', $inventory->id);
            })->get();
        $acceptances->map(function($acceptance) {
            $acceptance->delete();
        });

        session()->put('redirect_option', $request->input('redirect_option'));

        $inventory->customFieldsForCheckinCheckout('display_checkin');

        if ($inventory->save()) {
            event(new CheckoutableCheckedIn($inventory, $target, auth()->user(), $request->input('note'), $checkin_at, $originalValues));
            return Helper::getRedirectOption($request, $inventory->id, 'Inventories')
                ->with('success', trans('admin/hardware/message.checkin.success'));
        }

        return redirect()->route('inventories.index')->with('error', trans('admin/hardware/message.checkin.error').$inventory->getErrors());
    }
}
