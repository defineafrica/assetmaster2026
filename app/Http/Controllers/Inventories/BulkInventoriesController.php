<?php

namespace App\Http\Controllers\Inventories;

use App\Helpers\Helper;
use App\Http\Controllers\CheckInOutRequest;
use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\AssetModel;
use App\Models\Statuslabel;
use App\Models\Setting;
use App\View\Label;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\InventoryCheckoutRequest;
use App\Models\CustomField;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BulkInventoriesController extends Controller
{
    use CheckInOutRequest;

    public function edit(Request $request) : View | RedirectResponse
    {
        $this->authorize('view', Inventory::class);

        if (! $request->filled('ids')) {
            return redirect()->back()->with('error', trans('admin/hardware/message.update.no_inventories_selected'));
        }

        $inventory_ids = $request->input('ids');

        if ($request->input('bulk_actions') === 'checkout') {
            $status_check = $this->hasUndeployableStatus($inventory_ids);
            if($status_check && $status_check['status'] === true){
                $inventory_tags = implode(', ', array_column($status_check['tags'], 'asset_tag'));
                $inventory_ids = $status_check['inventory_ids'];

                session()->flash('warning', trans('admin/hardware/message.undeployable', ['asset_tags' => $inventory_tags]));
            }

            session()->flashInput(['selected_inventories' => $inventory_ids]);
            return redirect()->route('inventories.bulkcheckout.show');
        }

        if ($request->input('bulk_actions') === 'maintenance') {
            session()->flashInput(['selected_inventories' => $inventory_ids]);
            return redirect()->route('maintenances.create');
        }

        $bulk_back_url = request()->headers->get('referer');
        session(['bulk_back_url' => $bulk_back_url]);

        $allowed_columns = [
            'id',
            'name',
            'asset_tag',
            'serial',
            'model_number',
            'last_checkout',
            'notes',
            'expected_checkin',
            'order_number',
            'image',
            'assigned_to',
            'created_at',
            'updated_at',
            'purchase_date',
            'purchase_cost',
            'last_audit_date',
            'next_audit_date',
            'warranty_months',
            'checkout_counter',
            'checkin_counter',
            'requests_counter',
            'byod',
            'asset_eol_date',
        ];

        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort_override = str_replace('custom_fields.', '', $request->input('sort'));

        $column_sort = in_array($sort_override, $allowed_columns) ? $sort_override : 'inventories.id';

        $query = Inventory::with('assignedTo', 'location', 'model')
                ->whereIn('inventories.id', $inventory_ids)
                ->withTrashed();

        switch ($sort_override) {
            case 'model':
                $query->OrderModels($order);
                break;
            case 'model_number':
                $query->OrderModelNumber($order);
                break;
            case 'category':
                $query->OrderCategory($order);
                break;
            case 'manufacturer':
                $query->OrderManufacturer($order);
                break;
            case 'company':
                $query->OrderCompany($order);
                break;
            case 'location':
                $query->OrderLocation($order);
                break;
            case 'rtd_location':
                $query->OrderRtdLocation($order);
                break;
            case 'status_label':
                $query->OrderStatus($order);
                break;
            case 'supplier':
                $query->OrderSupplier($order);
                break;
            case 'assigned_to':
                $query->OrderAssigned($order);
                break;
            default:
                $query->orderBy($column_sort, $order);
                break;
        }
        $inventories = $query->get();

        if ($inventories->isEmpty()) {
            Log::debug('No inventories were found for the provided IDs', ['ids' => $inventory_ids]);
            return redirect()->back()->with('error', trans('admin/hardware/message.update.inventories_do_not_exist_or_are_invalid'));
        }

        $models = $inventories->unique('model_id');
        $modelNames = [];

        foreach($models as $model) {
            $modelNames[] = $model->model?->name;
        }

        if ($request->filled('bulk_actions')) {
            switch ($request->input('bulk_actions')) {
                case 'labels':
                    $this->authorize('view', Inventory::class);

                    return (new Label)
                        ->with('inventories', $inventories)
                        ->with('settings', Setting::getSettings())
                        ->with('bulkedit', true)
                        ->with('count', 0);

                case 'delete':
                    $this->authorize('delete', Inventory::class);
                    $inventories->each(function ($inventory) {
                        $this->authorize('delete', $inventory);
                    });

                    return view('hardware/inventory/bulk-delete')->with('inventories', $inventories);

                case 'restore':
                    $this->authorize('update', Inventory::class);
                    $inventories = Inventory::withTrashed()->find($inventory_ids);
                    $inventories->each(function ($inventory) {
                        $this->authorize('delete', $inventory);
                    });
                    return view('hardware/inventory/bulk-restore')->with('inventories', $inventories);

                case 'edit':
                    $this->authorize('update', Inventory::class);
                    return view('hardware/inventory/bulk')
                        ->with('inventories', $inventory_ids)
                        ->with('statuslabel_list', Helper::statusLabelList())
                        ->with('models', $models->pluck(['model']))
                        ->with('modelNames', $modelNames);
            }
        }

        return redirect()->back()->with('error', 'No action selected');
    }

    public function update(Request $request) : RedirectResponse
    {
        $this->authorize('update', Inventory::class);
        $has_errors = 0;
        $error_array = array();

        $bulk_back_url = $request->session()->pull('bulk_back_url', url()->previous());

        $custom_field_columns = CustomField::all()->pluck('db_column')->toArray();

        $null_custom_fields_inputs = array_filter($request->all(), function ($key) {
            return (strpos($key, 'null_') === 0);
        }, ARRAY_FILTER_USE_KEY);;

        $custom_fields_to_null = [];
        foreach ($null_custom_fields_inputs as $key => $value) {
            $custom_fields_to_null[str_replace('null', '', $key)] = $value;
        }

        if (! $request->filled('ids') || count($request->input('ids')) == 0) {
            return redirect($bulk_back_url)->with('error', trans('admin/hardware/message.update.no_inventories_selected'));
        }

        $inventories = Inventory::whereIn('id', $request->input('ids'))->get();

        if (($request->filled('name'))
            || ($request->filled('purchase_date'))
            || ($request->filled('expected_checkin'))
            || ($request->filled('purchase_cost'))
            || ($request->filled('supplier_id'))
            || ($request->filled('donor_id'))
            || ($request->filled('order_number'))
            || ($request->filled('warranty_months'))
            || ($request->filled('rtd_location_id'))
            || ($request->filled('requestable'))
            || ($request->filled('company_id'))
            || ($request->filled('status_id'))
            || ($request->filled('model_id'))
            || ($request->filled('notes'))
            || ($request->filled('next_audit_date'))
            || ($request->filled('asset_eol_date'))
            || ($request->filled('null_name'))
            || ($request->filled('null_purchase_date'))
            || ($request->filled('null_expected_checkin_date'))
            || ($request->filled('null_next_audit_date'))
            || ($request->filled('null_asset_eol_date'))
            || ($request->filled('null_notes'))
            || ($request->anyFilled($custom_field_columns))
            || ($request->anyFilled(array_keys($null_custom_fields_inputs)))
        ) {
            foreach ($inventories as $inventory) {

                $this->update_array = [];

                $this->conditionallyAddItem('name')
                    ->conditionallyAddItem('purchase_date')
                    ->conditionallyAddItem('expected_checkin')
                    ->conditionallyAddItem('order_number')
                    ->conditionallyAddItem('requestable')
                    ->conditionallyAddItem('supplier_id')
                    ->conditionallyAddItem('donor_id')
                    ->conditionallyAddItem('warranty_months')
                    ->conditionallyAddItem('next_audit_date')
                    ->conditionallyAddItem('asset_eol_date')
                    ->conditionallyAddItem('notes');
                    foreach ($custom_field_columns as $key => $custom_field_column) {
                        $this->conditionallyAddItem($custom_field_column); 
                   }
                foreach ($custom_fields_to_null as $key => $custom_field_to_null) {
                    $this->conditionallyAddItem($key);
                }

                if (!($inventory->eol_explicit)) {
                    if ($request->filled('model_id')) {
                        $model = AssetModel::find($request->input('model_id'));
                        if ($model->eol > 0) {
                            if ($request->filled('purchase_date')) {
                                $this->update_array['asset_eol_date'] = Carbon::parse($request->input('purchase_date'))->addMonths($model->eol)->format('Y-m-d');
                            } else {
                                $this->update_array['asset_eol_date'] = Carbon::parse($inventory->purchase_date)->addMonths($model->eol)->format('Y-m-d');
                            }
                        } else {
                            $this->update_array['asset_eol_date'] = null;
                        }
                    } elseif (($request->filled('purchase_date')) && ($inventory->model->eol > 0)) {
                        $this->update_array['asset_eol_date'] = Carbon::parse($request->input('purchase_date'))->addMonths($inventory->model->eol)->format('Y-m-d');
                    }
                }                

                if ($request->input('null_name')=='1') {
                    $this->update_array['name'] = null;
                }

                if ($request->input('null_purchase_date')=='1') {
                    $this->update_array['purchase_date'] = null;
                    if (!($inventory->eol_explicit)) {
                        $this->update_array['asset_eol_date'] = null;
                    }
                }

                if ($request->input('null_expected_checkin_date')=='1') {
                    $this->update_array['expected_checkin'] = null;
                }

                if ($request->input('null_next_audit_date')=='1') {
                    $this->update_array['next_audit_date'] = null;
                }

                if ($request->input('null_asset_eol_date')=='1') {
                    $this->update_array['asset_eol_date'] = null;
                    if ($request->input('calc_eol')=='1') {
                        $this->update_array['eol_explicit'] = 0;
                    }
                }

                if ($request->input('null_notes')=='1') {
                    $this->update_array['notes'] = null;
                }

                if ($request->filled('purchase_cost')) {
                    $this->update_array['purchase_cost'] =  $request->input('purchase_cost');
                }

                if ($request->filled('company_id')) {
                    $this->update_array['company_id'] = $request->input('company_id');
                    if ($request->input('company_id') == 'clear') {
                        $this->update_array['company_id'] = null;
                    }
                }

                if ($request->filled('model_id')) {
                    $this->update_array['model_id'] = AssetModel::find($request->input('model_id'))->id;
                }

                if ($request->filled('status_id')) {
                    try {
                        $updated_status = Statuslabel::findOrFail($request->input('status_id'));
                    } catch (ModelNotFoundException $e) {
                        return redirect($bulk_back_url)->with('error', trans('admin/statuslabels/message.does_not_exist'));
                    }

                    $unassigned = $inventory->assigned_to == '';
                    $deployable = $updated_status->deployable == '1' && $inventory->assetstatus?->deployable == '1';
                    $pending =  $updated_status->pending === 1;

                    if ($unassigned || $deployable || $pending) {
                        $this->update_array['status_id'] = $updated_status->id;
                    }
                }

                if ($request->filled('rtd_location_id')) {
                    if (($request->filled('update_real_loc')) && (($request->input('update_real_loc')) == '0')) {
                        $this->update_array['rtd_location_id'] = $request->input('rtd_location_id');
                    }

                    if (($request->filled('update_real_loc')) && (($request->input('update_real_loc')) == '1')) {
                        $this->update_array['location_id'] = $request->input('rtd_location_id');
                        $this->update_array['rtd_location_id'] = $request->input('rtd_location_id');
                    }

                    if (($request->filled('update_real_loc')) && (($request->input('update_real_loc')) == '2')) {
                        $this->update_array['location_id'] = $request->input('rtd_location_id');
                    }
                }

                $changed = [];

                foreach ($this->update_array as $key => $value) {
                    if ($this->update_array[$key] != $inventory->{$key}) {
                        $changed[$key]['old'] = $inventory->{$key};
                        $changed[$key]['new'] = $this->update_array[$key];
                    }
                }

                if ($inventory->model?->fieldset) {
                    foreach ($inventory->model->fieldset->fields as $field) {
                        if ($custom_fields_to_null) {
                            foreach ($custom_fields_to_null as $key => $custom_field_to_null) {
                                if ($field->db_column == $key) {
                                    $this->update_array[$field->db_column] = null;
                                }
                            }
                        }

                        if ((array_key_exists($field->db_column, $this->update_array)) && ($field->field_encrypted == '1')) {
                            if (Gate::allows('admin')) {
                                $decrypted_old = Helper::gracefulDecrypt($field, $inventory->{$field->db_column});

                                if ($decrypted_old != $this->update_array[$field->db_column]) {
                                    $inventory->{$field->db_column} = Crypt::encrypt($this->update_array[$field->db_column]);
                                } else {
                                    unset($this->update_array[$field->db_column]);
                                    unset($inventory->{$field->db_column});
                                }
                            }
                        } else {
                            if ((array_key_exists($field->db_column, $this->update_array)) && ($inventory->{$field->db_column} != $this->update_array[$field->db_column])) {
                                if (is_array($this->update_array[$field->db_column])) {
                                    $inventory->{$field->db_column} = implode(', ', $this->update_array[$field->db_column]);
                                } else {
                                    $inventory->{$field->db_column} = $this->update_array[$field->db_column];
                                }
                            }
                        }
                    }
                }

                if (!$inventory->update($this->update_array)) {
                    foreach ($inventory->getErrors()->toArray() as $key => $message) {
                        for ($x = 0; $x < count($message); $x++) {
                            $error_array[$key][] = trans('general.inventory') . ' ' . $inventory->id . ': ' . $message[$x];
                            $has_errors++;
                        }
                    }
                }
            }

            if ($has_errors > 0) {
                session()->put('bulkedit_ids', $request->input('ids'));
                session()->put('bulk_inventory_errors', $error_array);

                return redirect()
                    ->route('inventories.index')
                    ->with('bulk_inventory_errors', $error_array)
                    ->withInput();
            }

            return redirect($bulk_back_url)->with('success', trans('admin/hardware/message.update.success'));
        }

        return redirect($bulk_back_url)->with('warning', trans('admin/hardware/message.update.nothing_updated'));
    }

    private $update_array;

    protected function conditionallyAddItem($field) : BulkInventoriesController
    {
        if (request()->filled($field)) {
            $this->update_array[$field] = request()->input($field);
        }

        return $this;
    }

    public function destroy(Request $request) : RedirectResponse
    {
        $this->authorize('delete', Inventory::class);

        $bulk_back_url = route('inventories.index');

        if ($request->session()->has('bulk_back_url')) {
            $bulk_back_url = $request->session()->pull('bulk_back_url');
        }
        $inventoryIds = $request->input('ids');

        if(empty($inventoryIds)) {
            return redirect($bulk_back_url)->with('error', trans('admin/hardware/message.delete.nothing_updated'));
        }

        $assignedInventories = Inventory::whereIn('id', $inventoryIds)->whereNotNull('assigned_to')->get();
        if($assignedInventories->isNotEmpty()) {
            $inventoryTags = $assignedInventories->pluck('asset_tag')->implode(', ');
            return redirect($bulk_back_url)->with('error', trans_choice('admin/hardware/message.delete.assigned_to_error', $assignedInventories->count(), ['asset_tag' => $inventoryTags] ));
        }

        foreach (Inventory::wherein('id', $inventoryIds)->get() as $inventory) {
            $inventory->delete();
        }

        return redirect($bulk_back_url)->with('success', trans('admin/hardware/message.delete.success'));
    }

    public function showCheckout() : View
    {
        $this->authorize('checkout', Inventory::class);

        $alreadyAssigned = collect();

        if (old('selected_inventories') && is_array(old('selected_inventories'))) {
            $inventories = Inventory::findMany(old('selected_inventories'));

            [$assignable, $alreadyAssigned] = $inventories->partition(function (Inventory $inventory) {
                return !$inventory->assigned_to;
            });

            session()->flashInput(['selected_inventories' => $assignable->pluck('id')->values()->toArray()]);
        }

        $do_not_change = ['' => trans('general.do_not_change')];
        $status_label_list = $do_not_change + Helper::deployableStatusLabelList();

        return view('hardware/inventory/bulk-checkout', [
            'statusLabel_list' => $status_label_list,
            'removed_inventories' => $alreadyAssigned,
        ]);
    }

    public function storeCheckout(InventoryCheckoutRequest $request) : RedirectResponse | ModelNotFoundException
    {
        $this->authorize('checkout', Inventory::class);

        try {
            $admin = auth()->user();

            $target = $this->determineCheckoutTarget();
            session()->put(['checkout_to_type' => $target]);

            if (! is_array($request->input('selected_inventories'))) {
                return redirect()->route('inventories.bulkcheckout.show')->withInput()->with('error', trans('admin/hardware/message.checkout.no_inventories_selected'));
            }

            $inventory_ids = array_filter($request->input('selected_inventories'));

            $inventories = Inventory::findOrFail($inventory_ids);

            if ($inventories->pluck('assigned_to')->unique()->filter()->isNotEmpty()) {
                session()->flashInput(['selected_inventories' => $inventory_ids]);

                return redirect(route('inventories.bulkcheckout.show'))
                    ->with('error', trans('general.error_inventories_already_checked_out'));
            }

            if (Setting::getSettings()->full_multiple_companies_support && $target->company_id) {
                $company_ids = $inventories->pluck('company_id')->unique();

                if ($company_ids->count() > 1 || $company_ids->first() != $target->company_id) {
                    session()->flashInput(['selected_inventories' => $inventory_ids]);

                    return redirect(route('inventories.bulkcheckout.show'))
                        ->with('error', trans('general.error_user_company_multiple'));
                }
            }

            if (request('checkout_to_type') == 'inventory') {
                foreach ($inventory_ids as $inventory_id) {
                    if ($target->id == $inventory_id) {
                        return redirect()->back()->with('error', 'You cannot check an inventory out to itself.');
                    }
                }
            }
            $checkout_at = date('Y-m-d H:i:s');
            if (($request->filled('checkout_at')) && ($request->input('checkout_at') != date('Y-m-d'))) {
                $checkout_at = $request->input('checkout_at');
            }

            $expected_checkin = '';

            if ($request->filled('expected_checkin')) {
                $expected_checkin = $request->input('expected_checkin');
            }

            $errors = [];
            DB::transaction(function () use ($target, $admin, $checkout_at, $expected_checkin, &$errors, $inventories, $request) {
                foreach ($inventories as $inventory) {
                    $this->authorize('checkout', $inventory);

                    if ($request->filled('status_id')) {
                        $inventory->status_id = $request->input('status_id');
                    }

                    $checkout_success = $inventory->checkOut($target, $admin, $checkout_at, $expected_checkin, e($request->input('note')), $inventory->name, null);

                    if ($target->location_id != '') {
                        $inventory->location_id = $target->location_id;
                        $inventory::withoutEvents(function () use ($inventory) {
                            $inventory->save();
                        });
                    }

                    if (!$checkout_success) {
                        $errors = array_merge_recursive($errors, $inventory->getErrors()->toArray());
                    }
                }
            });

            if (! $errors) {
                return redirect()->to('inventories')->with('success', trans_choice('admin/hardware/message.multi-checkout.success', $inventory_ids));
            }

            return redirect()->route('inventories.bulkcheckout.show')->withInput()->with('error', trans_choice('admin/hardware/message.multi-checkout.error', $inventory_ids))->withErrors($errors);
        } catch (ModelNotFoundException $e) {
            return redirect()->route('inventories.bulkcheckout.show')->withInput()->with('error', trans_choice('admin/hardware/message.multi-checkout.error', $request->input('selected_inventories')));
        }
    }

    public function restore(Request $request) : RedirectResponse
    {
        $this->authorize('update', Inventory::class);
        $inventoryIds = $request->input('ids');

        if (empty($inventoryIds)) {
            return redirect()->route('inventories.index')->with('error', trans('admin/hardware/message.restore.nothing_updated'));
        } else {
            foreach ($inventoryIds as $key => $inventoryId) {
                $inventory = Inventory::withTrashed()->find($inventoryId);
                $inventory->restore();
            } 
            return redirect()->route('inventories.index')->with('success', trans('admin/hardware/message.restore.success'));
        }
    }

    public function hasUndeployableStatus (array $inventory_ids)
    {
        $undeployable = Inventory::whereIn('id', $inventory_ids)
            ->undeployable()
            ->get();

        $undeployableTags = $undeployable->map(function ($inventory) {
            return [
                'id' => $inventory->id,
                'asset_tag' => $inventory->asset_tag,
            ];
        })->toArray();

        $undeployableIds = array_column($undeployableTags, 'id');
        $filtered_ids = array_diff($inventory_ids, $undeployableIds);

         if($undeployable->isNotEmpty()) {
             return ['status' => true, 'tags' => $undeployableTags, 'inventory_ids' => $filtered_ids];
         }
        return false;
    }

    public function bulkEditForm(): View|RedirectResponse
    {
        $this->authorize('update', Inventory::class);

        $inventory_ids = session()->pull('bulkedit_ids', []);

        if (empty($inventory_ids)) {
            return redirect()->route('inventories.index')->with('error', trans('admin/hardware/message.update.no_inventories_selected'));
        }

        $inventories = Inventory::with('model')->withTrashed()->whereIn('id', $inventory_ids)->get();

        if ($inventories->isEmpty()) {
            return redirect()->route('inventories.index')->with('error', trans('admin/hardware/message.update.inventories_do_not_exist_or_are_invalid'));
        }

        $models = $inventories->unique('model_id');
        $modelNames = [];
        foreach ($models as $model) {
            $modelNames[] = $model->model->name;
        }

        return view('hardware/inventory/bulk')
            ->with('inventories', $inventory_ids)
            ->with('statuslabel_list', Helper::statusLabelList())
            ->with('models', $models->pluck(['model']))
            ->with('modelNames', $modelNames);
    }
}
