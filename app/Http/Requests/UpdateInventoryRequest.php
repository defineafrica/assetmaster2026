<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\MayContainCustomFields;
use App\Models\Inventory;
use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateInventoryRequest extends ImageUploadRequest
{
    use MayContainCustomFields;

    public function authorize()
    {
        return Gate::allows('update', $this->inventory);
    }

    public function rules()
    {
        $setting = Setting::getSettings();

        $rules = array_merge(
            parent::rules(),
            (new Inventory)->getRules(),
            [
                'model_id'  => ['integer', 'exists:models,id,deleted_at,NULL', 'not_array'],
                'status_id' => ['integer', 'exists:status_labels,id'],
                'inventory_tag' => [
                    'min:1', 'max:255', 'not_array',
                    Rule::unique('inventories', 'inventory_tag')->ignore($this->inventory)->withoutTrashed(),
                ],
                'serial' => [
                    'string', 'max:255', 'not_array',
                    $setting->unique_serial=='1' ? Rule::unique('inventories', 'serial')->ignore($this->inventory)->withoutTrashed() : 'nullable',
                ],
            ],
        );

        if ($setting->digit_separator === '1.234,56' && is_string($this->input('purchase_cost'))) {
            $rules['purchase_cost'] = ['nullable', 'string'];
        }

        return $rules;
    }
}
