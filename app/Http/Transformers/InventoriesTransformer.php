<?php

namespace App\Http\Transformers;

use App\Helpers\Helper;
use App\Models\Inventory;
use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class InventoriesTransformer
{
    public function transformInventories(Collection $inventories, $total)
    {
        $array = [];
        foreach ($inventories as $inventory) {
            $array[] = self::transformInventory($inventory);
        }

        return (new DatatablesTransformer)->transformDatatables($array, $total);
    }

    public function transformInventory(Inventory $inventory)
    {
        $setting = Setting::getSettings();

        $array = [
            'id' => (int) $inventory->id,
            'name' => e($inventory->name),
            'inventory_tag' => e($inventory->inventory_tag),
            'serial' => e($inventory->serial),
            'model' => ($inventory->model) ? [
                'id' => (int) $inventory->model->id,
                'name'=> e($inventory->model->name),
            ] : null,
            'byod' => ($inventory->byod ? true : false),
            'requestable' => ($inventory->requestable ? true : false),
            'model_number' => (($inventory->model) && ($inventory->model->model_number)) ? e($inventory->model->model_number) : null,
            'eol' => (($inventory->asset_eol_date != '') && ($inventory->purchase_date != '')) ? (int) Carbon::parse($inventory->asset_eol_date)->diffInMonths($inventory->purchase_date, true) . ' months' : null,
            'asset_eol_date' => ($inventory->asset_eol_date != '') ? Helper::getFormattedDateObject($inventory->asset_eol_date, 'date') : null,
            'status_label' => ($inventory->assetstatus) ? [
                'id' => (int) $inventory->assetstatus->id,
                'name'=> e($inventory->assetstatus->name),
                'status_type'=> e($inventory->assetstatus->getStatuslabelType()),
                'status_meta' => e($inventory->present()->statusMeta),
            ] : null,
            'category' => (($inventory->model) && ($inventory->model->category)) ? [
                'id' => (int) $inventory->model->category->id,
                'name'=> e($inventory->model->category->name),
                'tag_color'=> ($inventory->model->category->tag_color) ? e($inventory->model->category->tag_color) : null,
            ] : null,
            'manufacturer' => (($inventory->model) && ($inventory->model->manufacturer)) ? [
                'id' => (int) $inventory->model->manufacturer->id,
                'name'=> e($inventory->model->manufacturer->name),
                'tag_color'=> ($inventory->model->manufacturer->tag_color) ? e($inventory->model->manufacturer->tag_color) : null,
            ] : null,
            'depreciation' => (($inventory->model) && ($inventory->model->depreciation)) ? [
                'id' => (int) $inventory->model->depreciation->id,
                'name'=> e($inventory->model->depreciation->name),
                'months'=> (int) $inventory->model->depreciation->months,
                'type'=>  e($inventory->model->depreciation->depreciation_type),
                'minimum'=> ($inventory->model->depreciation->depreciation_min) ? (int) $inventory->model->depreciation->depreciation_min : null,
            ] : null,
            'supplier' => ($inventory->supplier) ? [
                'id' => (int) $inventory->supplier->id,
                'name'=> e($inventory->supplier->name),
                'tag_color'=> ($inventory->supplier->tag_color) ? e($inventory->supplier->tag_color) : null,
            ] : null,
            'notes' => ($inventory->notes) ? Helper::parseEscapedMarkedownInline($inventory->notes) : null,
            'order_number' => ($inventory->order_number) ? e($inventory->order_number) : null,
            'company' => ($inventory->company) ? [
                'id' => (int) $inventory->company->id,
                'name'=> e($inventory->company->name),
                'tag_color'=> ($inventory->company->tag_color) ? e($inventory->company->tag_color) : null,
            ] : null,
            'location' => ($inventory->location) ? [
                'id' => (int) $inventory->location->id,
                'name'=> e($inventory->location->name),
                'tag_color'=> ($inventory->location->tag_color) ? e($inventory->location->tag_color) : null,
            ] : null,
            'rtd_location' => ($inventory->defaultLoc) ? [
                'id' => (int) $inventory->defaultLoc->id,
                'name'=> e($inventory->defaultLoc->name),
                'tag_color'=> ($inventory->defaultLoc->tag_color) ? e($inventory->defaultLoc->tag_color) : null,
            ] : null,
            'image' => ($inventory->getImageUrl()) ? $inventory->getImageUrl() : null,
            'qr' => ($setting->qr_code=='1') ? config('app.url').'/uploads/barcodes/qr-'.str_slug($inventory->inventory_tag).'-'.str_slug($inventory->id).'.png' : null,
            'alt_barcode' => ($setting->alt_barcode_enabled=='1') ? config('app.url').'/uploads/barcodes/'.str_slug($setting->alt_barcode).'-'.str_slug($inventory->inventory_tag).'.png' : null,
            'assigned_to' => $this->transformAssignedTo($inventory),
            'warranty_months' =>  ($inventory->warranty_months > 0) ? e($inventory->warranty_months.' '.trans('admin/hardware/form.months')) : null,
            'warranty_expires' => ($inventory->warranty_months > 0) ? Helper::getFormattedDateObject($inventory->warranty_expires, 'date') : null,
            'created_by' => ($inventory->adminuser) ? [
                'id' => (int) $inventory->adminuser->id,
                'name'=> e($inventory->adminuser->display_name),
            ] : null,
            'created_at' => Helper::getFormattedDateObject($inventory->created_at, 'datetime'),
            'updated_at' => Helper::getFormattedDateObject($inventory->updated_at, 'datetime'),
            'last_audit_date' => Helper::getFormattedDateObject($inventory->last_audit_date, 'datetime'),
            'next_audit_date' => Helper::getFormattedDateObject($inventory->next_audit_date, 'date'),
            'deleted_at' => Helper::getFormattedDateObject($inventory->deleted_at, 'datetime'),
            'purchase_date' => Helper::getFormattedDateObject($inventory->purchase_date, 'date'),
            'age' => $inventory->purchase_date ? $inventory->purchase_date->locale(app()->getLocale())->diffForHumans() : '',
            'last_checkout' => Helper::getFormattedDateObject($inventory->last_checkout, 'datetime'),
            'last_checkin' => Helper::getFormattedDateObject($inventory->last_checkin, 'datetime'),
            'expected_checkin' => Helper::getFormattedDateObject($inventory->expected_checkin, 'date'),
            'purchase_cost' => Helper::formatCurrencyOutput($inventory->purchase_cost),
            'checkin_counter' => (int) $inventory->checkin_counter,
            'checkout_counter' => (int) $inventory->checkout_counter,
            'requests_counter' => (int) $inventory->requests_counter,
            'user_can_checkout' => (bool) $inventory->availableForCheckout(),
            'book_value' => Helper::formatCurrencyOutput($inventory->getDepreciatedValue()),
        ];

        if (($inventory->model) && ($inventory->model->fieldset) && ($inventory->model->fieldset->fields->count() > 0)) {
            $fields_array = [];

            foreach ($inventory->model->fieldset->fields as $field) {
                if ($field->isFieldDecryptable($inventory->{$field->db_column})) {
                    $decrypted = Helper::gracefulDecrypt($field, $inventory->{$field->db_column});
                    $value = (Gate::allows('inventories.view.encrypted_custom_fields')) ? $decrypted : strtoupper(trans('admin/custom_fields/general.encrypted'));

                    if ($field->format == 'DATE'){
                        if (Gate::allows('inventories.view.encrypted_custom_fields')){
                            $value = Helper::getFormattedDateObject($value, 'date', false);
                        } else {
                           $value = strtoupper(trans('admin/custom_fields/general.encrypted'));
                        }
                    }

                    $fields_array[$field->name] = [
                            'field' => e($field->db_column),
                            'value' => e($value),
                            'field_format' => $field->format,
                            'element' => $field->element,
                        ];

                } else {
                    $value = $inventory->{$field->db_column};

                    if (($field->format == 'DATE') && (!is_null($value)) && ($value!='')){
                        $value = Helper::getFormattedDateObject($value, 'date', false);
                    }
                    
                    $fields_array[$field->name] = [
                        'field' => e($field->db_column),
                        'value' => e($value),
                        'field_format' => $field->format,
                        'element' => $field->element,
                    ];
                }

                $array['custom_fields'] = $fields_array;
            }
        } else {
            $array['custom_fields'] = new \stdClass;
        }

        $permissions_array['available_actions'] = [
            'checkout'      => ($inventory->deleted_at=='' && Gate::allows('checkout', Inventory::class)) ? true : false,
            'checkin'       => ($inventory->deleted_at=='' && Gate::allows('checkin', Inventory::class)) ? true : false,
            'clone'         => Gate::allows('create', Inventory::class) ? true : false,
            'restore'       => ($inventory->deleted_at!='' && Gate::allows('create', Inventory::class)) ? true : false,
            'update'        => ($inventory->deleted_at=='' && Gate::allows('update', Inventory::class)) ? true : false,
            'audit'        => Gate::allows('audit', Inventory::class) ? true : false,
            'delete'        => ($inventory->deleted_at=='' && $inventory->assigned_to =='' && Gate::allows('delete', Inventory::class) && ($inventory->deleted_at == '')) ? true : false,
        ];      

        $array += $permissions_array;

        return $array;
    }

    public function transformAssignedTo(Inventory $inventory)
    {
        if ($inventory->checkedOutToUser()) {
            return $inventory->assigned ? [
                    'id' => (int) $inventory->assigned->id,
                    'username' => e($inventory->assigned->username),
                    'name' => e($inventory->assigned->getFullNameAttribute()),
                    'first_name'=> e($inventory->assigned->first_name),
                    'last_name'=> ($inventory->assigned->last_name) ? e($inventory->assigned->last_name) : null,
                    'email'=> ($inventory->assigned->email) ? e($inventory->assigned->email) : null,
                    'employee_number' =>  ($inventory->assigned->employee_num) ? e($inventory->assigned->employee_num) : null,
                    'jobtitle' => $inventory->assigned->jobtitle ? e($inventory->assigned->jobtitle) : null,
                    'type' => 'user',
                ] : null;
        }

        return $inventory->assigned ? [
            'id' => $inventory->assigned->id,
            'name' => e($inventory->assigned->display_name),
            'type' => $inventory->assignedType()
        ] : null;
    }

    public function transformDatatable($inventories, $total, $request)
    {
        $array = [];
        foreach ($inventories as $inventory) {
            $array[] = self::transformInventory($inventory);
        }

        return (new DatatablesTransformer)->transformDatatables($array, $total);
    }

    public function transformSelectList($inventories)
    {
        $array = [];

        foreach ($inventories as $inventory) {
            $array[] = [
                'id' => $inventory->id,
                'inventory_tag' => $inventory->inventory_tag,
                'text' => $inventory->text,
                'serial' => $inventory->serial,
                'name' => $inventory->name,
                'status_label' => ($inventory->assetstatus) ? [
                    'id' => (int) $inventory->assetstatus->id,
                    'name' => e($inventory->assetstatus->name),
                    'status_type' => e($inventory->assetstatus->getStatuslabelType()),
                ] : null,
            ];
        }

        return $array;
    }
}
