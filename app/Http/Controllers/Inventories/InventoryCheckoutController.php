<?php

namespace App\Http\Controllers\Inventories;

use App\Exceptions\CheckoutNotAllowed;
use App\Helpers\Helper;
use App\Http\Controllers\CheckInOutRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryCheckoutRequest;
use App\Models\Inventory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Session;
use \Illuminate\Contracts\View\View;
use \Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;

class InventoryCheckoutController extends Controller
{
    use CheckInOutRequest;

    public function create(Inventory $inventory) : View | RedirectResponse
    {
        $this->authorize('checkout', $inventory);

        if (!$inventory->model) {
            return redirect()->route('inventories.show', $inventory)
                ->with('error', trans('admin/hardware/general.model_invalid_fix'));
        }

        $inventory->setRules($inventory->getRules() + $inventory->customFieldValidationRules());

        if ($inventory->isInvalid()) {
            return redirect()->route('inventories.edit', $inventory)->withErrors($inventory->getErrors());
        }

        if ($inventory->availableForCheckout()) {
            return view('hardware/inventory/checkout', compact('inventory'))
                ->with('statusLabel_list', Helper::deployableStatusLabelList())
                ->with('table_name', 'Inventories')
                ->with('item', $inventory);
        }

        return redirect()->route('inventories.index')
            ->with('error', trans('admin/hardware/message.checkout.not_available'));
    }

    public function store(InventoryCheckoutRequest $request, $inventoryId) : RedirectResponse
    {
        try {
            if (! $inventory = Inventory::find($inventoryId)) {
                return redirect()->route('inventories.index')->with('error', trans('admin/hardware/message.does_not_exist'));
            } elseif (! $inventory->availableForCheckout()) {
                return redirect()->route('inventories.index')->with('error', trans('admin/hardware/message.checkout.not_available'));
            }
            $this->authorize('checkout', $inventory);

            if (!$inventory->model) {
                return redirect()->route('inventories.show', $inventory)->with('error', trans('admin/hardware/general.model_invalid_fix'));
            }

            $admin = auth()->user();

            $target = $this->determineCheckoutTarget();
            session()->put(['checkout_to_type' => $target]);

            $inventory = $this->updateAssetLocation($inventory, $target);

            $checkout_at = date('Y-m-d H:i:s');
            if (($request->filled('checkout_at')) && ($request->input('checkout_at') != date('Y-m-d'))) {
                $checkout_at = $request->input('checkout_at');
            }

            $expected_checkin = '';
            if ($request->filled('expected_checkin')) {
                $expected_checkin = $request->input('expected_checkin');
            }

            if ($request->filled('status_id')) {
                $inventory->status_id = $request->input('status_id');
            }

            if(!empty($inventory->licenseseats->all())){
                if(request('checkout_to_type') == 'user') {
                    foreach ($inventory->licenseseats as $seat){
                        $seat->assigned_to = $target->id;
                        $seat->save();
                    }
                }
            }

            $inventory->customFieldsForCheckinCheckout('display_checkout');

            $settings = \App\Models\Setting::getSettings();

            if (($settings->full_multiple_companies_support) && ((!is_null($target->company_id)) &&  (!is_null($inventory->company_id)))) {
                if ($target->company_id != $inventory->company_id){
                    return redirect()->route('inventories.checkout.create', $inventory)->with('error', trans('general.error_user_company'));
                }
            }

            session()->put(['redirect_option' => $request->input('redirect_option'), 'checkout_to_type' => $request->input('checkout_to_type')]);

            if ($inventory->checkOut($target, $admin, $checkout_at, $expected_checkin, $request->input('note'), $request->input('name'))) {
                return Helper::getRedirectOption($request, $inventory->id, 'Inventories')
                    ->with('success', trans('admin/hardware/message.checkout.success'));
            }

            return redirect()->route("inventories.checkout.create", $inventory)->with('error', trans('admin/hardware/message.checkout.error').$inventory->getErrors());
        } catch (ModelNotFoundException $e) {
            return redirect()->back()->with('error', trans('admin/hardware/message.checkout.error'))->withErrors($inventory->getErrors());
        } catch (CheckoutNotAllowed $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
