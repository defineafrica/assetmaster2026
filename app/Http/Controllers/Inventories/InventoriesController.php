<?php

namespace App\Http\Controllers\Inventories;

use App\Events\CheckoutableCheckedIn;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImageUploadRequest;
use App\Http\Requests\CreateMultipleInventoryRequest;
use App\Http\Requests\UpdateInventoryRequest;
use App\Models\Actionlog;
use App\Http\Requests\UploadFileRequest;
use Illuminate\Support\Facades\Log;
use App\Models\Inventory;
use App\Models\AssetModel;
use App\Models\CheckoutRequest;
use App\Models\Company;
use App\Models\Location;
use App\Models\Setting;
use App\Models\Statuslabel;
use App\Models\User;
use App\View\Label;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;
use Illuminate\Http\Response;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use TypeError;

class InventoriesController extends Controller
{
    protected $qrCodeDimensions = ['height' => 3.5, 'width' => 3.5];
    protected $barCodeDimensions = ['height' => 2, 'width' => 22];

    public function __construct()
    {
        $this->middleware('auth');
        parent::__construct();
    }

    public function index(Request $request) : View
    {
        $this->authorize('index', Inventory::class);
        $company = Company::find($request->input('company_id'));

        return view('inventory/index')->with('company', $company);
    }

    public function create(Request $request) : View
    {
        $this->authorize('create', Inventory::class);
        $view = view('inventory/edit')
            ->with('statuslabel_list', Helper::statusLabelList())
            ->with('item', new Inventory)
            ->with('statuslabel_types', Helper::statusTypeList());

        if ($request->filled('model_id')) {
            $selected_model = AssetModel::find($request->input('model_id'));
            $view->with('selected_model', $selected_model);
        }

        return $view;
    }

    public function store(CreateMultipleInventoryRequest $request): RedirectResponse
    {
        $this->authorize(Inventory::class);

        $this->validate($request, ['inventory_tags' => ['required', 'array']]);

        $inventory_tags = $request->input('inventory_tags');
        $model = AssetModel::find($request->input('model_id'));
        $serial_errors = [];
        $serials = $request->input('serials');

        $settings = Setting::getSettings();

        for ($a = 1, $aMax = count($inventory_tags); $a <= $aMax; $a++) {
            if ($model && $model->require_serial === 1 && empty($serials[$a])) {
                $serial_errors["serials.$a"] = trans('admin/hardware/form.serial_required', ['number' => $a]);
            }
        }

        if (!empty($serial_errors)) {
            return redirect()->back()
                ->withInput()
                ->withErrors($serial_errors);
        }

        $inventory = null;
        $companyId = Company::getIdForCurrentUser($request->input('company_id'));
        $successes = [];
        $failures = [];

        try {
            DB::beginTransaction();
            for ($a = 1, $aMax = count($inventory_tags); $a <= $aMax; $a++) {
                $inventory = new Inventory();

                $inventory->model()->associate($model);
                $inventory->name = $request->input('name');

                if (($serials) && (array_key_exists($a, $serials))) {
                    $inventory->serial = $serials[$a];
                }

                if (($inventory_tags) && (array_key_exists($a, $inventory_tags))) {
                    $inventory->inventory_tag = $inventory_tags[$a];
                }

                $inventory->company_id = $companyId;
                $inventory->model_id = $request->input('model_id');
                $inventory->order_number = $request->input('order_number');
                $inventory->notes = $request->input('notes');
                $inventory->created_by = auth()->id();
                $inventory->status_id = request('status_id');
                $inventory->warranty_months = request('warranty_months', null);
                $inventory->purchase_cost = request('purchase_cost');
                $inventory->purchase_date = request('purchase_date', null);
                $inventory->asset_eol_date = request('asset_eol_date', null);
                $inventory->assigned_to = request('assigned_to', null);
                $inventory->supplier_id = request('supplier_id', null);
                $inventory->donor_id = request('donor_id', null);
                $inventory->requestable = request('requestable', 0);
                $inventory->rtd_location_id = request('rtd_location_id', null);
                $inventory->byod = request('byod', 0);

                if (!empty($settings->audit_interval)) {
                    $inventory->next_audit_date = Carbon::now()->addMonths((int)$settings->audit_interval)->toDateString();
                }

                if (!request('assigned_user') && !request('assigned_inventory') && !request('assigned_location')) {
                    $inventory->location_id = $request->input('rtd_location_id', null);
                }

                if ($request->has('use_cloned_image')) {
                    $cloned_model_img = Inventory::select('image')->find($request->input('clone_image_from_id'));
                    if ($cloned_model_img) {
                        $new_image_name = 'clone-' . date('U') . '-' . $cloned_model_img->image;
                        $new_image = 'inventories/' . $new_image_name;
                        Storage::disk('public')->copy('inventories/' . $cloned_model_img->image, $new_image);
                        $inventory->image = $new_image_name;
                    }
                } else {
                    $inventory = $request->handleImages($inventory);
                }

                if (($model) && ($model->fieldset)) {
                    foreach ($model->fieldset->fields as $field) {
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
                    }
                }

                if ($inventory->isValid() && $inventory->save()) {
                    $target = null;
                    $location = null;

                    if ($userId = request('assigned_user')) {
                        $target = User::find($userId);

                        if (!$target) {
                            return redirect()->back()->withInput()->with('error', trans('admin/hardware/message.create.target_not_found.user'));
                        }
                        $location = $target->location_id;
                    } elseif ($inventoryId = request('assigned_inventory')) {
                        $target = Inventory::find($inventoryId);

                        if (!$target) {
                            return redirect()->back()->withInput()->with('error', trans('admin/hardware/message.create.target_not_found.inventory'));
                        }
                        $location = $target->location_id;
                    } elseif ($locationId = request('assigned_location')) {
                        $target = Location::find($locationId);

                        if (!$target) {
                            return redirect()->back()->withInput()->with('error', trans('admin/hardware/message.create.target_not_found.location'));
                        }
                        $location = $target->id;
                    }

                    if (isset($target)) {
                        $inventory->checkOut($target, auth()->user(), date('Y-m-d H:i:s'), $request->input('expected_checkin', null), 'Checked out on inventory creation', $request->input('name'), $location);
                    }

                    $successes[] = "<a href='" . route('inventories.show', $inventory) . "' style='color: white;'>" . e($inventory->inventory_tag) . "</a>";
                } else {
                    $inventory->throwValidationException();
                    $failures[] = join(",", $inventory->getErrors()->all());
                }
            }
        } catch (\Throwable $e) {
            \Log::debug("Caught exception in multi-create - rolling back: " . $e->getMessage());
            DB::rollBack();
            throw $e;
        }
        DB::commit();

        if($request->input('redirect_option') === 'back'){
            session()->put(['redirect_option' => 'index']);
        } else {
            session()->put(['redirect_option' => $request->input('redirect_option')]);
        }

        session()->put(['checkout_to_type' => $request->input('checkout_to_type'),
                       'other_redirect' =>  'model' ]);

        if ($successes) {
            if ($failures) {
                return Helper::getRedirectOption($request, $inventory->id, 'Inventories')
                ->with('success-unescaped', trans_choice('admin/hardware/message.create.multi_success_linked', $successes, ['links' => join(", ", $successes)]))
                    ->with('warning', trans_choice('admin/hardware/message.create.partial_failure', $failures, ['failures' => join("; ", $failures)]));
            } else {
                if (count($successes) == 1) {
                    return Helper::getRedirectOption($request, $inventory->id, 'Inventories')
                        ->with('success-unescaped', trans('admin/hardware/message.create.success_linked', ['link' => route('inventories.show', $inventory), 'id', 'tag' => e($inventory->inventory_tag)]));
                } else {
                    return Helper::getRedirectOption($request, $inventory->id, 'Inventories')
                        ->with('success-unescaped', trans_choice('admin/hardware/message.create.multi_success_linked', $successes, ['links' => join(", ", $successes)]));
                }
            }
        }

        return redirect()->back()->withInput()->withErrors($inventory->getErrors());
    }

    public function edit(Inventory $inventory) : View | RedirectResponse
    {
        $this->authorize($inventory);
        session()->put('back_url', url()->previous());
        return view('hardware/inventory/edit')
            ->with('item', $inventory)
            ->with('statuslabel_list', Helper::statusLabelList())
            ->with('statuslabel_types', Helper::statusTypeList());
    }

    public function show(Inventory $inventory) : View | RedirectResponse
    {
        $this->authorize('view', $inventory);
        $settings = Setting::getSettings();

        if (isset($inventory)) {
            $audit_log = Actionlog::where('action_type', '=', 'audit')
                ->where('item_id', '=', $inventory->id)
                ->where('item_type', '=', Inventory::class)
                ->orderBy('created_at', 'DESC')->first();

            if ($inventory->location) {
                $use_currency = $inventory->location->currency;
            } else {
                if ($settings->default_currency != '') {
                    $use_currency = $settings->default_currency;
                } else {
                    $use_currency = trans('general.currency');
                }
            }

            $qr_code = (object) [
                'display' => $settings->qr_code == '1',
                'url' => route('qr_code/inventories', $inventory),
            ];

            return view('hardware/inventory/view', compact('inventory', 'qr_code', 'settings'))
                ->with('use_currency', $use_currency)->with('audit_log', $audit_log);
        }

        return redirect()->route('inventories.index')->with('error', trans('admin/hardware/message.does_not_exist'));
    }

    public function update(ImageUploadRequest $request, Inventory $inventory) : RedirectResponse
    {
        $this->authorize($inventory);

        $inventory->status_id = $request->input('status_id', null);
        $inventory->warranty_months = $request->input('warranty_months', null);
        $inventory->purchase_cost = $request->input('purchase_cost', null);
        $inventory->purchase_date = $request->input('purchase_date', null);
        $inventory->next_audit_date = $request->input('next_audit_date', null);
        if ($request->filled('purchase_date') && !$request->filled('asset_eol_date') && ($inventory->model?->eol > 0)) {
            $inventory->purchase_date = $request->input('purchase_date', null); 
            $inventory->asset_eol_date = Carbon::parse($request->input('purchase_date'))->addMonths($inventory->model->eol)->format('Y-m-d');
            $inventory->eol_explicit = false;
        } elseif ($request->filled('asset_eol_date')) {
           $inventory->asset_eol_date = $request->input('asset_eol_date', null);
            $months = (int) Carbon::parse($inventory->asset_eol_date)->diffInMonths($inventory->purchase_date, true);
           if($inventory->model->eol) {
               if($months != $inventory->model->eol > 0) {
                   $inventory->eol_explicit = true;
               } else {
                   $inventory->eol_explicit = false;
               }
           } else {
               $inventory->eol_explicit = true;
           }
        } elseif (!$request->filled('asset_eol_date') && (($inventory->model?->eol) == 0)) {
           $inventory->asset_eol_date = null;
		   $inventory->eol_explicit = false;
        }
        $inventory->supplier_id = $request->input('supplier_id', null);
        $inventory->donor_id = $request->input('donor_id', null);
        $inventory->expected_checkin = $request->input('expected_checkin', null);
        $inventory->requestable = $request->input('requestable', 0);
        $inventory->rtd_location_id = $request->input('rtd_location_id', null);
        $inventory->byod = $request->input('byod', 0);

        $status = Statuslabel::find($request->input('status_id'));

        if (($status) && (($status->getStatuslabelType() != 'pending') && ($status->getStatuslabelType() != 'deployable')) && ($target = $inventory->assignedTo)) {
            $originalValues = $inventory->getRawOriginal();
            $inventory->assigned_to = null;
            $inventory->assigned_type = null;
            $inventory->accepted = null;
            $inventory->last_checkin = now();
            event(new CheckoutableCheckedIn($inventory, $target, auth()->user(), 'Checkin on inventory update with '.$status->getStatuslabelType().' status', date('Y-m-d H:i:s'), $originalValues));
        }

        if ($request->filled('image_delete')) {
            try {
                unlink(public_path().'/uploads/inventories/'.$inventory->image);
                $inventory->image = '';
            } catch (\Exception $e) {
                Log::info($e);
            }
        }

        $serial = $request->input('serials');
        $inventory->serial = $request->input('serials');

        if (is_array($request->input('serials'))) {
            $inventory->serial = $serial[1];
        }

        $inventory->name = $request->input('name');
        $inventory->company_id = Company::getIdForCurrentUser($request->input('company_id'));
        $inventory->model_id = $request->input('model_id');
        $inventory->order_number = $request->input('order_number');

        $inventory_tags = $request->input('inventory_tags');
        $inventory->inventory_tag = $request->input('inventory_tags');

        if (is_array($request->input('inventory_tags'))) {
            $inventory->inventory_tag = $inventory_tags[1];
        }

        $inventory->notes = $request->input('notes');

        $inventory = $request->handleImages($inventory);

        $model = AssetModel::find($request->input('model_id'));
        if (($model) && ($model->fieldset)) {
            foreach ($model->fieldset->fields as $field) {
                if ($field->element == 'checkbox' && !$request->has($field->db_column)) {
                    $inventory->{$field->db_column} = null;
                }
                if ($request->has($field->db_column)) {
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
                }
            }
        }
        session()->put([
            'redirect_option' => $request->input('redirect_option'),
            'checkout_to_type' => $request->input('checkout_to_type'),
            'other_redirect' => $request->input('redirect_option') === 'other_redirect' ? 'model' : null,
        ]);

        if ($model && $model->require_serial === 1 && empty($serial[1])) {
            return redirect()->to(Helper::getRedirectOption($request, $inventory->id, 'Inventories'))
                ->with('warning', trans('admin/hardware/form.serial_required_post_model_update', [
                    'inventory_model' => $model->name
                ]));
        }
        if ($inventory->save()) {
            return Helper::getRedirectOption($request, $inventory->id, 'Inventories')
                ->with('success', trans('admin/hardware/message.update.success'));
        }

        return redirect()->back()->withInput()->withErrors($inventory->getErrors());
    }

    public function destroy(Request $request, $inventoryId) : RedirectResponse
    {
        if (is_null($inventory = Inventory::find($inventoryId))) {
            return redirect()->route('inventories.index')->with('error', trans('admin/hardware/message.does_not_exist'));
        }

        $this->authorize('delete', $inventory);

        if ($inventory->assignedTo) {
            $target = $inventory->assignedTo;
            $checkin_at = date('Y-m-d H:i:s');
            $originalValues = $inventory->getRawOriginal();
            event(new CheckoutableCheckedIn($inventory, $target, auth()->user(), 'Checkin on delete', $checkin_at, $originalValues));
            DB::table('inventories')
                ->where('id', $inventory->id)
                ->update(['assigned_to' => null, 'assigned_type' => null]);
        }

        if ($inventory->image) {
            try {
                Storage::disk('public')->delete('inventories'.'/'.$inventory->image);
            } catch (\Exception $e) {
                Log::debug($e);
            }
        }

        $inventory->delete();

        return redirect()->route('inventories.index')->with('success', trans('admin/hardware/message.delete.success'));
    }

    public function getInventoryBySerial(Request $request) : RedirectResponse
    {
        $topsearch = ($request->input('topsearch')=="true");

        if (!$inventory = Inventory::where('serial', '=', $request->input('serial'))->first()) {
            return redirect()->route('inventories.index')->with('error', trans('admin/hardware/message.does_not_exist'));
        }
        $this->authorize('view', $inventory);
        return redirect()->route('inventories.show', $inventory->id)->with('topsearch', $topsearch);
    }

    public function getInventoryByTag(Request $request, $tag=null) : RedirectResponse
    {
        $tag = $tag ? $tag : $request->input('inventoryTag');
        $topsearch = ($request->input('topsearch') == 'true');

        $inventories = Inventory::where('inventory_tag', '=', $tag);

        if ($inventories->count() != 1) {
            return redirect()->route('inventories.index')
                ->with('search', $tag)
                ->with('warning', trans('admin/hardware/message.does_not_exist_var', [ 'inventory_tag' => $tag ]));
        }
        $inventory = $inventories->first();
        $this->authorize('view', $inventory);

        return redirect()->route('inventories.show', $inventory->id)->with('topsearch', $topsearch);
    }

    public function getQrCode(Inventory $inventory) : Response | BinaryFileResponse | string | bool
    {
        $settings = Setting::getSettings();

        if ($settings->label2_2d_type !== 'none') {
            if ($inventory) {
                $size = Helper::barcodeDimensions($settings->label2_2d_type);
                $qr_file = public_path().'/uploads/barcodes/qr-'.str_slug($inventory->inventory_tag).'-'.str_slug($inventory->id).'.png';

                if (isset($inventory->id, $inventory->inventory_tag)) {
                    if (file_exists($qr_file)) {
                        $header = ['Content-type' => 'image/png'];
                        return response()->file($qr_file, $header);
                    } else {
                        $barcode = new \Com\Tecnick\Barcode\Barcode();
                        $barcode_obj = $barcode->getBarcodeObj($settings->label2_2d_type, route('inventories.show', $inventory->id), $size['height'], $size['width'], 'black', [-2, -2, -2, -2]);
                        file_put_contents($qr_file, $barcode_obj->getPngData());
                        return response($barcode_obj->getPngData())->header('Content-type', 'image/png');
                    }
                }
            }
            return 'That inventory is invalid';
        }
        return false;
    }

    public function getBarCode($inventoryId = null)
    {
        $settings = Setting::getSettings();
        if ($inventory = Inventory::withTrashed()->find($inventoryId)) {
            $barcode_file = public_path().'/uploads/barcodes/'.str_slug($settings->label2_1d_type).'-'.str_slug($inventory->inventory_tag).'.png';

            if (isset($inventory->id, $inventory->inventory_tag)) {
                if (file_exists($barcode_file)) {
                    $header = ['Content-type' => 'image/png'];
                    return response()->file($barcode_file, $header);
                } else {
                    $barcode_width = ($settings->labels_width - $settings->labels_display_sgutter) * 200.000000000001;
                    $barcode = new \Com\Tecnick\Barcode\Barcode();
                    try {
                        $barcode_obj = $barcode->getBarcodeObj($settings->label2_1d_type, $inventory->inventory_tag, ($barcode_width < 300 ? $barcode_width : 300), 50);
                        file_put_contents($barcode_file, $barcode_obj->getPngData());
                        return response($barcode_obj->getPngData())->header('Content-type', 'image/png');
                    } catch (\Exception|TypeError $e) {
                        Log::debug('The barcode format is invalid.');
                        return response(file_get_contents(public_path('uploads/barcodes/invalid_barcode.gif')))->header('Content-type', 'image/gif');
                    }
                }
            }
        }
        return null;
    }

    public function getLabel($inventoryId = null)
    {
        if (isset($inventoryId)) {
            $inventory = Inventory::find($inventoryId);
            $this->authorize('view', $inventory);

            return (new Label())
                ->with('inventories', collect([ $inventory ]))
                ->with('settings', Setting::getSettings())
                ->with('template', request()->input('template'))
                ->with('offset', request()->input('offset'))
                ->with('bulkedit', false)
                ->with('count', 0);
        }
    }

    public function getClone(Inventory $inventory)
    {
        $this->authorize('create', Inventory::class);
        $cloned = clone $inventory;
        $cloned_model = $inventory;
        $cloned->id = null;
        $cloned->inventory_tag = '';
        $cloned->serial = '';
        $cloned->assigned_to = '';
        $cloned->deleted_at = '';

        return view('hardware/inventory/edit')
            ->with('statuslabel_list', Helper::statusLabelList())
            ->with('statuslabel_types', Helper::statusTypeList())
            ->with('cloned_model', $cloned_model)
            ->with('item', $cloned);
    }

    public function getImportHistory()
    {
        $this->authorize('admin');

        return view('hardware/inventory/history');
    }

    public function postImportHistory(Request $request)
    {
        if (! $request->hasFile('user_import_csv')) {
            return back()->with('error', 'No file provided. Please select a file for import and try again. ');
        }

        if (! ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }
        $csv = Reader::createFromPath($request->file('user_import_csv'));
        $csv->setHeaderOffset(0);
        $header = $csv->getHeader();
        $isCheckinHeaderExplicit = in_array('checkin date', (array_map('strtolower', $header)));
        try {
            $results = $csv->getRecords();
        } catch (\Exception $e) {
            return back()->with('error', trans('general.error_in_import_file', ['error' => $e->getMessage()]));
        } 
        $item = [];
        $status = [];
        $status['error'] = [];
        $status['success'] = [];
        foreach ($results as $row) {
            if (is_array($row)) {
                $row = array_change_key_case($row, CASE_LOWER);
                $inventory_tag = Helper::array_smart_fetch($row, 'inventory tag');
                if (! array_key_exists($inventory_tag, $item)) {
                    $item[$inventory_tag] = [];
                }
                $batch_counter = count($item[$inventory_tag]);
                $item[$inventory_tag][$batch_counter]['checkout_date'] = Carbon::parse(Helper::array_smart_fetch($row, 'checkout date'))->format('Y-m-d H:i:s');

                if ($isCheckinHeaderExplicit) {
                    if (! empty(Helper::array_smart_fetch($row, 'checkin date'))) {
                        $item[$inventory_tag][$batch_counter]['checkin_date'] = Carbon::parse(Helper::array_smart_fetch($row, 'checkin date'))->format('Y-m-d H:i:s');
                    } else {
                        $item[$inventory_tag][$batch_counter]['checkin_date'] = '';
                    }
                } else {
                    $item[$inventory_tag][$batch_counter]['checkin_date'] = Carbon::parse(now())->format('Y-m-d H:i:s');
                }

                $item[$inventory_tag][$batch_counter]['inventory_tag'] = Helper::array_smart_fetch($row, 'inventory tag');
                $item[$inventory_tag][$batch_counter]['name'] = Helper::array_smart_fetch($row, 'name');
                $item[$inventory_tag][$batch_counter]['email'] = Helper::array_smart_fetch($row, 'email');
                if ($inventory = Inventory::where('inventory_tag', '=', $inventory_tag)->first()) {
                    $item[$inventory_tag][$batch_counter]['inventory_id'] = $inventory->id;
                    $base_username = User::generateFormattedNameFromFullName(Setting::getSettings()->username_format, $item[$inventory_tag][$batch_counter]['name']);
                    $user = User::where('username', '=', $base_username['username']);
                    $user_query = ' on username '.$base_username['username'];
                    if ($request->input('match_firstnamelastname') == '1') {
                        $firstnamedotlastname = User::generateFormattedNameFromFullName('firstname.lastname', $item[$inventory_tag][$batch_counter]['name']);
                        $item[$inventory_tag][$batch_counter]['username'][] = $firstnamedotlastname['username'];
                        $user->orWhere('username', '=', $firstnamedotlastname['username']);
                        $user_query .= ', or on username '.$firstnamedotlastname['username'];
                    }
                    if ($request->input('match_flastname') == '1') {
                        $flastname = User::generateFormattedNameFromFullName('filastname', $item[$inventory_tag][$batch_counter]['name']);
                        $item[$inventory_tag][$batch_counter]['username'][] = $flastname['username'];
                        $user->orWhere('username', '=', $flastname['username']);
                        $user_query .= ', or on username '.$flastname['username'];
                    }
                    if ($request->input('match_firstname') == '1') {
                        $firstname = User::generateFormattedNameFromFullName('firstname', $item[$inventory_tag][$batch_counter]['name']);
                        $item[$inventory_tag][$batch_counter]['username'][] = $firstname['username'];
                        $user->orWhere('username', '=', $firstname['username']);
                        $user_query .= ', or on username '.$firstname['username'];
                    }
                    if ($request->input('match_email') == '1') {
                        if ($item[$inventory_tag][$batch_counter]['name'] == '') {
                            $item[$inventory_tag][$batch_counter]['username'][] = $user_email = User::generateEmailFromFullName($item[$inventory_tag][$batch_counter]['name']);
                            $user->orWhere('username', '=', $user_email);
                            $user_query .= ', or on username '.$user_email;
                        }
                    }
                    if ($request->input('match_username') == '1') {
                        $raw_username = $item[$inventory_tag][$batch_counter]['name'];
                        $user->orWhere('username', '=', $raw_username);
                        $user_query .= ', or on username '.$raw_username;
                    }

                    if ($user = $user->first()) {
                        $item[$inventory_tag][$batch_counter]['user_id'] = $user->id;

                        Actionlog::firstOrCreate([
                            'item_id' => $inventory->id,
                            'item_type' => Inventory::class,
                            'created_by' =>  auth()->id(),
                            'note' => 'Checkout imported by '.auth()->user()->display_name.' from history importer',
                            'target_id' => $item[$inventory_tag][$batch_counter]['user_id'],
                            'target_type' => User::class,
                            'created_at' =>  $item[$inventory_tag][$batch_counter]['checkout_date'],
                            'action_type'   => 'checkout',
                        ]);

                        $checkin_date = $item[$inventory_tag][$batch_counter]['checkin_date'];

                        if ($isCheckinHeaderExplicit) {
                            if ((strtotime($checkin_date) > strtotime(Carbon::now())) || (empty($checkin_date)))
                            {
                                $inventory->assigned_to = $user->id;
                                $inventory->assigned_type = User::class;
                            }
                        }

                        if (! empty($checkin_date)) {
                            Actionlog::firstOrCreate([
                                'item_id' => $item[$inventory_tag][$batch_counter]['inventory_id'],
                                'item_type' => Inventory::class,
                                'created_by' => auth()->id(),
                                'note' => 'Checkin imported by '.auth()->user()->display_name.' from history importer',
                                'target_id' => null,
                                'created_at' => $checkin_date,
                                'action_type' => 'checkin',
                            ]);
                        }

                        if ($inventory->save()) {
                            $status['success'][]['inventory'][$inventory_tag]['msg'] = 'Inventory successfully matched for '.Helper::array_smart_fetch($row, 'name').$user_query.' on '.$item[$inventory_tag][$batch_counter]['checkout_date'];
                        } else {
                            $status['error'][]['inventory'][$inventory_tag]['msg'] = 'Inventory and user was matched but could not be saved.';
                        }
                    } else {
                        $item[$inventory_tag][$batch_counter]['user_id'] = null;
                        $status['error'][]['user'][Helper::array_smart_fetch($row, 'name')]['msg'] = 'User does not exist so no checkin log was created.';
                    }
                } else {
                    $item[$inventory_tag][$batch_counter]['inventory_id'] = null;
                    $status['error'][]['inventory'][$inventory_tag]['msg'] = 'Inventory does not exist so no match was attempted.';
                }
            }
        }

        return view('hardware/inventory/history')->with('status', $status);
    }

    public function sortByName(array $recordA, array $recordB): int
    {
        return strcmp($recordB['Full Name'], $recordA['Full Name']);
    }

    public function getRestore($inventoryId = null)
    {
        if ($inventory = Inventory::withTrashed()->find($inventoryId)) {
            $this->authorize('delete', $inventory);

            if ($inventory->deleted_at == '') {
                return redirect()->back()->with('error', trans('general.not_deleted', ['item_type' => trans('general.inventory')]));
            }

            if ($inventory->restore()) {
                $deleted_inventories = Inventory::onlyTrashed()->count();
                if ($deleted_inventories > 0) {
                    return redirect()->back()->with('success', trans('admin/hardware/message.restore.success'));
                }
                return redirect()->route('inventories.index')->with('success', trans('admin/hardware/message.restore.success'));
            }

            return redirect()->back()->with('error', trans('general.could_not_restore', ['item_type' => trans('general.inventory'), 'error' => $inventory->getErrors()->first()]));
        }

        return redirect()->route('inventories.index')->with('error', trans('admin/hardware/message.does_not_exist'));
    }

    public function quickScan()
    {
        $this->authorize('audit', Inventory::class);
        $settings = Setting::getSettings();
        $dt = Carbon::now()->addMonths($settings->audit_interval)->toDateString();
        return view('hardware/inventory/quickscan')->with('next_audit_date', $dt);
    }

    public function quickScanCheckin()
    {
        $this->authorize('checkin', Inventory::class);

        return view('hardware/inventory/quickscan-checkin')->with('statusLabel_list', Helper::statusLabelList());
    }

    public function dueForAudit()
    {
        $this->authorize('audit', Inventory::class);

        return view('hardware/inventory/audit-due');
    }

    public function dueForCheckin()
    {
        $this->authorize('checkin', Inventory::class);

        return view('hardware/inventory/checkin-due');
    }

    public function audit(Inventory $inventory): View | RedirectResponse
    {
        $this->authorize('audit', Inventory::class);
        $settings = Setting::getSettings();

        $inventory->setRules($inventory->getRules() + $inventory->customFieldValidationRules());

        if ($inventory->isInvalid()) {
            return redirect()->route('inventories.edit', $inventory)->withErrors($inventory->getErrors());
        }

        $dt = Carbon::now()->addMonths( (int) $settings->audit_interval)->toDateString();
        return view('hardware/inventory/audit')->with('inventory', $inventory)->with('item', $inventory)->with('next_audit_date', $dt)->with('locations_list');
    }

    public function auditStore(UploadFileRequest $request, Inventory $inventory)
    {
        $this->authorize('audit', Inventory::class);

        session()->put('redirect_option', $request->input('redirect_option'));
        session()->put('other_redirect', 'audit');

        $originalValues = $inventory->getRawOriginal();

        $inventory->next_audit_date = $request->input('next_audit_date');
        $inventory->last_audit_date = date('Y-m-d H:i:s');

        if ($request->input('update_location') == '1') {
            $inventory->location_id = $request->input('location_id');
        }

        if (($inventory->model) && ($inventory->model->fieldset)) {
            foreach ($inventory->model->fieldset->fields as $field) {
                if (($field->display_audit=='1') && ($request->has($field->db_column))) {
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
                }
            }
        }

        $inventory->setRules($inventory->getRules() + $inventory->customFieldValidationRules());

        if ($inventory->isInvalid()) {
            return redirect()->back()->withInput()->withErrors($inventory->getErrors());
        }

        $inventory->unsetEventDispatcher();

        if ($inventory->isValid() && $inventory->save()) {
            $file_name = null;
            if ($request->hasFile('image')) {
                $file_name = $request->handleFile('private_uploads/audits/', 'audit-'.$inventory->id, $request->file('image'));
            }

            $inventory->logAudit($request->input('note'), $request->input('location_id'), $file_name, $originalValues);
            return Helper::getRedirectOption($request, $inventory->id, 'Inventories')->with('success', trans('admin/hardware/message.audit.success'));
        }

        return redirect()->back()->withInput()->withErrors($inventory->getErrors());
    }

    public function getRequestedIndex($user_id = null)
    {
        $this->authorize('index', Inventory::class);
        $requestedItems = CheckoutRequest::with('user', 'requestedItem')->whereNull('canceled_at')->with('user', 'requestedItem');

        if ($user_id) {
            $requestedItems->where('user_id', $user_id)->get();
        }

        $requestedItems = $requestedItems->orderBy('created_at', 'desc')->get();

        return view('hardware/inventory/requested', compact('requestedItems'));
    }
}
