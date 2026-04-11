<?php

namespace App\Http\Controllers\Api;

use App\Events\CheckoutableCheckedIn;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterRequest;
use App\Http\Transformers\InventoriesTransformer;
use App\Models\CustomField;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

class InventoriesController extends Controller
{
    public function index(FilterRequest $request, $action = null, $upcoming_status = null) : JsonResponse | array
    {
        $this->authorize('index', Inventory::class);

        $settings = Setting::getSettings();

        $allowed_columns = [
            'id',
            'name',
            'inventory_tag',
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

        $settings = Setting::getSettings();
        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($request->input('sort'), $allowed_columns) ? $request->input('sort') : 'created_at';

        $inventories = Inventory::withTrashed()
            ->select('inventories.*')
            ->with('model', 'model.category', 'model.manufacturer', 'assetstatus', 'location', 'defaultLoc', 'supplier', 'company', 'assignedTo');

        if ($request->input('status_id')) {
            $inventories->where('status_id', '=', $request->input('status_id'));
        }

        if ($request->input('status')) {
            switch ($request->input('status')) {
                case 'Pending':
                    $inventories->whereHas('assetstatus', function ($query) {
                        $query->where('pending', '=', 1)
                            ->where('archived', '=', 0)
                            ->where('deployable', '=', 0);
                    });
                    break;
                case 'RTD':
                    $inventories->where('assigned_to', '=', null)
                        ->where('status_id', '!=', '0');
                    break;
                case 'Deployed':
                    $inventories->where('assigned_to', '>', '0')
                        ->whereNotNull('assigned_to');
                    break;
                case 'Undeployable':
                    $inventories->whereHas('assetstatus', function ($query) {
                        $query->where('deployable', '!=', 1)
                            ->where('pending', '!=', 1)
                            ->where('archived', '!=', 1);
                    });
                    break;
                case 'Requestable':
                    $inventories->where('requestable', '=', 1)
                        ->whereNull('assigned_to');
                    break;
                case 'Archived':
                    $inventories->where('archived', '=', 1);
                    break;
                case 'Deleted':
                    $inventories->onlyTrashed();
                    break;
                case 'byod':
                    $inventories->where('byod', '=', 1);
                    break;
            }
        }

        if ($request->input('company_id')) {
            $inventories->where('company_id', '=', $request->input('company_id'));
        }

        if ($request->input('location_id')) {
            $inventories->where(function ($query) use ($request) {
                $query->where('location_id', '=', $request->input('location_id'))
                    ->orWhere('rtd_location_id', '=', $request->input('location_id'));
            });
        }

        if ($request->input('rtd_location_id')) {
            $inventories->where('rtd_location_id', '=', $request->input('rtd_location_id'));
        }

        if ($request->input('model_id')) {
            $inventories->where('model_id', '=', $request->input('model_id'));
        }

        if ($request->input('manufacturer_id')) {
            $inventories->whereHas('model', function ($query) use ($request) {
                $query->where('manufacturer_id', '=', $request->input('manufacturer_id'));
            });
        }

        if ($request->input('category_id')) {
            $inventories->whereHas('model', function ($query) use ($request) {
                $query->where('category_id', '=', $request->input('category_id'));
            });
        }

        if ($request->input('supplier_id')) {
            $inventories->where('supplier_id', '=', $request->input('supplier_id'));
        }

        if ($request->input('license_id')) {
            $inventories->whereHas('licenses', function ($query) use ($request) {
                $query->where('licenses.id', '=', $request->input('license_id'));
            });
        }

        if ($request->input('search')) {
            $inventories = $inventories->HardwareSearch($request->input('search'));
        }

        if ($request->input('asset_tag')) {
            $inventories->where('inventory_tag', '=', $request->input('asset_tag'));
        }

        if ($request->input('serial')) {
            $inventories->where('serial', '=', $request->input('serial'));
        }

        if ($request->input('model_number')) {
            $inventories->whereHas('model', function ($query) use ($request) {
                $query->where('model_number', '=', $request->input('model_number'));
            });
        }

        if ($request->input('cancellation')) {
            $inventories->CancellationOrders();
        }

        if ($request->input('depreciation_id')) {
            $inventories->whereHas('model', function ($query) use ($request) {
                $query->where('depreciation_id', '=', $request->input('depreciation_id'));
            });
        }

        if ($request->input('user_id')) {
            $inventories->where('assigned_to', '=', $request->input('user_id'))
                ->where('assigned_type', '=', User::class);
        }

        if ($request->input('requestable')) {
            $inventories->where('requestable', '=', 1);
        }

        if ($request->input('order_number')) {
            $inventories->where('order_number', '=', $request->input('order_number'));
        }

        if ($request->input('manufacturer_id')) {
            $inventories->whereHas('model', function ($query) use ($request) {
                $query->where('manufacturer_id', '=', $request->input('manufacturer_id'));
            });
        }

        if ($request->input('byod')) {
            $inventories->where('byod', '=', '1');
        }

        if (($request->input('last_checkout')) || ($request->input('last_checkin'))) {
            if ($request->input('last_checkout')) {
                $inventories->where('last_checkout', '>=', $request->input('last_checkout'));
            }
            if ($request->input('last_checkin')) {
                $inventories->where('last_checkin', '>=', $request->input('last_checkin'));
            }
        }

        if ($request->input('created_by')) {
            $inventories->where('created_by', '=', $request->input('created_by'));
        }

        if ($request->input('next_audit_date')) {
            $inventories->where('next_audit_date', '>=', $request->input('next_audit_date'));
        }

        if ($request->input('previous_checkout')) {
            $inventories->where('last_checkout', '>=', $request->input('previous_checkout'));
        }

        if ($request->input('components_id')) {
            $inventories->whereHas('components', function ($query) use ($request) {
                $query->where('components.id', '=', $request->input('components_id'));
            });
        }

        if ($request->input('accessory_id')) {
            $inventories->whereHas('accessories', function ($query) use ($request) {
                $query->where('accessories.id', '=', $request->input('accessory_id'));
            });
        }

        if (request('manufacturers_id')) {
            $inventories->whereHas('model', function ($query) {
                $query->where('manufacturer_id', '=', request('manufacturers_id'));
            });
        }

        if (request('suppliers_id')) {
            $inventories->where('supplier_id', '=', request('suppliers_id'));
        }

        if (request('serial')) {
            $inventories->where('serial', '=', request('serial'));
        }

        if (request('order_number')) {
            $inventories->where('order_number', '=', request('order_number'));
        }

        if (request('category_id')) {
            $inventories->whereHas('model', function ($query) {
                $query->where('category_id', '=', request('category_id'));
            });
        }

        if (request('model_id')) {
            $inventories->where('model_id', '=', request('model_id'));
        }

        if (request('model_status')) {
            $inventories->whereHas('model', function ($query) {
                $query->where('model_statuses_id', '=', request('model_status'));
            });
        }

        if ($request->user() && $request->user()->cannot('view-requestable', Inventory::class)) {
            $inventories->where('requestable', '=', 0);
        }

        if ($request->input('offset')) {
            $offset = e($request->input('offset'));
        } else {
            $offset = 0;
        }

        if ($request->input('limit')) {
            $limit = e($request->input('limit'));
        } else {
            $limit = 50;
        }

        $total = $inventories->count();
        $inventories = $inventories->orderBy($sort, $order)->skip($offset)->take($limit)->get();

        return (new InventoriesTransformer)->transformDatatable($inventories, $total, $request);
    }

    public function show(Inventory $inventory) : JsonResponse
    {
        $this->authorize('view', $inventory);

        return (new InventoriesTransformer)->transformInventory($inventory);
    }

    public function store(Request $request) : JsonResponse
    {
        $this->authorize('create', Inventory::class);

        $inventory = new Inventory;
        $inventory->fill($request->all());

        if ($inventory->save()) {
            return response()->json(['status' => 'success', 'inventory' => $inventory], 201);
        }

        return response()->json(['status' => 'error', 'message' => 'Failed to create inventory'], 500);
    }

    public function update(Request $request, Inventory $inventory) : JsonResponse
    {
        $this->authorize('update', $inventory);

        $inventory->fill($request->all());

        if ($inventory->save()) {
            return response()->json(['status' => 'success', 'inventory' => $inventory]);
        }

        return response()->json(['status' => 'error', 'message' => 'Failed to update inventory'], 500);
    }

    public function destroy(Inventory $inventory) : JsonResponse
    {
        $this->authorize('delete', $inventory);

        if ($inventory->delete()) {
            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'error', 'message' => 'Failed to delete inventory'], 500);
    }

    public function selectlist(Request $request) : array
    {
        $settings = Setting::getSettings();

        if ($settings->ls_enabled === '1') {
            $inventories = Inventory::where('archived', '0')
                ->where('status_id', '!=', '0')
                ->with('locales', 'category', 'assetstatus', 'model', 'model.category')
                ->whereHas('locales', function ($query) use ($settings) {
                    $query->where('departments.id', $settings->ls_default_location);
                })
                ->select(['inventories.*', 'inventory_tag as text', 'serial']);
        } else {
            $inventories = Inventory::where('archived', '0')
                ->where('status_id', '!=', '0')
                ->with('locales', 'category', 'assetstatus', 'model', 'model.category')
                ->select(['inventories.*', 'inventory_tag as text', 'serial']);
        }

        if ($request->input('search')) {
            $inventories = $inventories->where('inventory_tag', 'LIKE', '%' . $request->input('search') . '%')
                ->orWhere('serial', 'LIKE', '%' . $request->input('search') . '%')
                ->orWhere('name', 'LIKE', '%' . $request->input('search') . '%');
        }

        $limit = $request->input('limit', 50);
        $inventories = $inventories->orderBy('inventory_tag', 'asc')->take($limit)->get();

        return (new InventoriesTransformer)->transformSelectList($inventories);
    }

    public function showByTag(Request $request, $tag) : JsonResponse
    {
        $settings = Setting::getSettings();
        $inventories = Inventory::where($settings->tag_id, '=', $tag)->first();

        if (!$inventories) {
            return response()->json(['status' => 'error', 'message' => 'Inventory not found'], 404);
        }

        return (new InventoriesTransformer)->transformInventory($inventories);
    }

    public function showBySerial(Request $request, $serial) : JsonResponse
    {
        $settings = Setting::getSettings();
        $inventories = Inventory::where('serial', '=', $serial)->first();

        if (!$inventories) {
            return response()->json(['status' => 'error', 'message' => 'Inventory not found'], 404);
        }

        return (new InventoriesTransformer)->transformInventory($inventories);
    }

    /**
     * Checkout an inventory item
     *
     * @param Request $request
     * @param int $inventory_id
     * @return JsonResponse
     */
    public function checkout(Request $request, $inventory_id): JsonResponse
    {
        $this->authorize('checkout', Inventory::class);
        $inventory = Inventory::findOrFail($inventory_id);

        if (!$inventory->availableForCheckout()) {
            return response()->json(Helper::formatStandardApiResponse('error', ['inventory' => e($inventory->inventory_tag)], trans('admin/hardware/message.checkout.not_available')));
        }

        $this->authorize('checkout', $inventory);

        $error_payload = [];
        $error_payload['inventory'] = [
            'id' => $inventory->id,
            'inventory_tag' => $inventory->inventory_tag,
        ];

        $target = null;
        if (request('checkout_to_type') == 'location') {
            $target = Location::find(request('assigned_location'));
            $inventory->location_id = ($target) ? $target->id : '';
            $error_payload['target_id'] = $request->input('assigned_location');
            $error_payload['target_type'] = 'location';
        } elseif (request('checkout_to_type') == 'inventory') {
            $target = Inventory::where('id', '!=', $inventory_id)->find(request('assigned_inventory'));
            $inventory->location_id = (($target) && (isset($target->location_id))) ? $target->location_id : '';
            $error_payload['target_id'] = $request->input('assigned_inventory');
            $error_payload['target_type'] = 'inventory';
        } elseif (request('checkout_to_type') == 'user') {
            $target = User::find(request('assigned_user'));
            $inventory->location_id = (($target) && (isset($target->location_id))) ? $target->location_id : '';
            $error_payload['target_id'] = $request->input('assigned_user');
            $error_payload['target_type'] = 'user';
        }

        if ($request->filled('status_id')) {
            $inventory->status_id = $request->input('status_id');
        }

        if (!isset($target)) {
            return response()->json(Helper::formatStandardApiResponse('error', $error_payload, 'Checkout target for inventory ' . e($inventory->inventory_tag) . ' is invalid - ' . $error_payload['target_type'] . ' does not exist.'));
        }

        $checkout_at = request('checkout_at', date('Y-m-d H:i:s'));
        $expected_checkin = request('expected_checkin', null);
        $note = request('note', null);
        $inventory_name = request()->has('name') ? request('name') : $inventory->name;

        if ($inventory->checkOut($target, auth()->user(), $checkout_at, $expected_checkin, $note, $inventory_name, $inventory->location_id)) {
            return response()->json(Helper::formatStandardApiResponse('success', ['inventory' => e($inventory->inventory_tag)], trans('admin/hardware/message.checkout.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', ['inventory' => e($inventory->inventory_tag)], trans('admin/hardware/message.checkout.error')));
    }

    /**
     * Checkin an inventory item
     *
     * @param Request $request
     * @param int $inventory_id
     * @return JsonResponse
     */
    public function checkin(Request $request, $inventory_id): JsonResponse
    {
        $inventory = Inventory::with('model')->findOrFail($inventory_id);
        $this->authorize('checkin', $inventory);

        $target = $inventory->assignedTo;
        if (is_null($target)) {
            return response()->json(Helper::formatStandardApiResponse('error', [
                'inventory_tag' => e($inventory->inventory_tag),
                'model' => e($inventory->model->name),
                'model_number' => e($inventory->model->model_number)
            ], trans('admin/hardware/message.checkin.already_checked_in')));
        }

        $inventory->expected_checkin = null;
        $inventory->last_checkin = now();
        $inventory->assignedTo()->disassociate($inventory);
        $inventory->accepted = null;

        if ($request->has('name')) {
            $inventory->name = $request->input('name');
        }

        $inventory->location_id = $inventory->rtd_location_id;

        if ($request->filled('location_id')) {
            $inventory->location_id = $request->input('location_id');

            if ($request->input('update_default_location')) {
                $inventory->rtd_location_id = $request->input('location_id');
            }
        }

        if ($request->filled('status_id')) {
            $inventory->status_id = $request->input('status_id');
        }

        $checkin_at = $request->filled('checkin_at') ? $request->input('checkin_at') . ' ' . date('H:i:s') : date('Y-m-d H:i:s');
        $originalValues = $inventory->getRawOriginal();

        if (($request->filled('checkin_at')) && ($request->input('checkin_at') != date('Y-m-d'))) {
            $originalValues['action_date'] = $checkin_at;
        }

        if ($inventory->save()) {
            event(new CheckoutableCheckedIn($inventory, $target, auth()->user(), $request->input('note'), $checkin_at, $originalValues));

            return response()->json(Helper::formatStandardApiResponse('success', [
                'inventory_tag' => e($inventory->inventory_tag),
                'model' => e($inventory->model->name),
                'model_number' => e($inventory->model->model_number)
            ], trans('admin/hardware/message.checkin.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', ['inventory' => e($inventory->inventory_tag)], trans('admin/hardware/message.checkin.error')));
    }

    /**
     * Checkin an inventory item by inventory tag
     *
     * @param Request $request
     * @param string|null $tag
     * @return JsonResponse
     */
    public function checkinByTag(Request $request, $tag = null): JsonResponse
    {
        $this->authorize('checkin', Inventory::class);
        if (null == $tag && null !== ($request->input('inventory_tag'))) {
            $tag = $request->input('inventory_tag');
        }
        $inventory = Inventory::where('inventory_tag', $tag)->first();

        if ($inventory) {
            return $this->checkin($request, $inventory->id);
        }

        return response()->json(Helper::formatStandardApiResponse('error', [
            'inventory' => e($tag)
        ], 'Inventory with tag ' . e($tag) . ' not found'));
    }

    /**
     * Mark an inventory item as audited
     *
     * @param Request $request
     * @param Inventory $inventory
     * @return JsonResponse
     */
    public function audit(Request $request, Inventory $inventory): JsonResponse
    {
        $this->authorize('audit', Inventory::class);

        $settings = Setting::getSettings();
        $dt = Carbon::now()->addMonths($settings->audit_interval)->toDateString();

        if ($inventory) {
            $originalValues = $inventory->getRawOriginal();

            $inventory->next_audit_date = $dt;

            if ($request->filled('next_audit_date')) {
                $inventory->next_audit_date = $request->input('next_audit_date');
            }

            if ($request->input('update_location') == '1') {
                $inventory->location_id = $request->input('location_id');
            }

            $inventory->last_audit_date = date('Y-m-d H:i:s');

            $payload = [
                'id' => $inventory->id,
                'inventory_tag' => $inventory->inventory_tag,
                'note' => e($request->input('note')),
                'status_label' => e($inventory->assetstatus->display_name),
                'status_type' => $inventory->assetstatus->getStatuslabelType(),
                'next_audit_date' => Helper::getFormattedDateObject($inventory->next_audit_date),
            ];

            if (($inventory->model) && ($inventory->model->fieldset)) {
                $payload['custom_fields'] = [];
                foreach ($inventory->model->fieldset->fields as $field) {
                    if (($field->display_audit == '1') && ($request->has($field->db_column))) {
                        if ($field->field_encrypted == '1') {
                            if (Gate::allows('inventories.view.encrypted_custom_fields')) {
                                if (is_array($request->input($field->db_column))) {
                                    $inventory->{$field->db_column} = Crypt::encrypt(implode(', ', $request->input($field->db_column)));
                                } else {
                                    $inventory->{$field->db_column} = Crypt::encrypt($request->input($field->db_column));
                                }
                            }
                        } else {
                            if (is_array($request->input($field->db_column))) {
                                $inventory->{$field->db_column} = implode(', ', $request->input($field->db_column));
                            } else {
                                $inventory->{$field->db_column} = $request->input($field->db_column);
                            }
                        }
                        $payload['custom_fields'][$field->db_column] = $request->input($field->db_column);
                    }
                }
            }

            $inventory->setRules($inventory->getRules() + $inventory->customFieldValidationRules());

            if ($inventory->isInvalid()) {
                return response()->json(Helper::formatStandardApiResponse('error', ['inventory_tag' => $inventory->inventory_tag], $inventory->getErrors()));
            }

            $inventory->unsetEventDispatcher();

            if ($inventory->isValid() && $inventory->save()) {
                $inventory->logAudit(request('note'), request('location_id'), null, $originalValues);
                return response()->json(Helper::formatStandardApiResponse('success', $payload, trans('admin/hardware/message.audit.success')));
            }
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, trans('admin/hardware/message.does_not_exist')), 200);
    }

    /**
     * Returns JSON listing of all requestable inventories
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     */
    public function requestable(Request $request): JsonResponse | array
    {
        $this->authorize('viewRequestable', Inventory::class);

        $allowed_columns = [
            'name',
            'inventory_tag',
            'serial',
            'model_number',
            'image',
            'purchase_cost',
            'expected_checkin',
        ];

        $all_custom_fields = CustomField::all();

        foreach ($all_custom_fields as $field) {
            $allowed_columns[] = $field->db_column_name();
        }

        $inventories = Inventory::select('inventories.*')
            ->with(
                'location',
                'assetstatus',
                'assetlog',
                'company',
                'assignedTo',
                'model.category',
                'model.manufacturer',
                'model.fieldset',
                'supplier',
                'requests'
            );

        if ($request->filled('search')) {
            $inventories->TextSearch($request->input('search'));
        }

        foreach ($all_custom_fields as $field) {
            if ($request->filled($field->db_column_name())) {
                $inventories->where($field->db_column_name(), '=', $request->input($field->db_column_name()));
            }
        }

        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort_override = str_replace('custom_fields.', '', $request->input('sort'));

        $column_sort = in_array($sort_override, $allowed_columns) ? $sort_override : 'inventories.created_at';

        switch ($request->input('sort')) {
            case 'model':
                $inventories->OrderModels($order);
                break;
            case 'model_number':
                $inventories->OrderModelNumber($order);
                break;
            case 'location':
                $inventories->OrderLocation($order);
                break;
            default:
                $inventories->orderBy($column_sort, $order);
                break;
        }

        $inventories->requestableAssets();

        $offset = ($request->input('offset') > $inventories->count()) ? $inventories->count() : app('api_offset_value');
        $limit = app('api_limit_value');

        $total = $inventories->count();
        $inventories = $inventories->skip($offset)->take($limit)->get();

        return (new InventoriesTransformer)->transformRequestedInventories($inventories, $total);
    }
}
