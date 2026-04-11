<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\MayContainCustomFields;
use App\Models\Inventory;
use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\Helper;
use App\Models\Setting;
use App\Models\AssetModel;
use App\Rules\UniqueUndeleted;
use Illuminate\Support\Str;

class CreateMultipleInventoryRequest extends ImageUploadRequest
{
    use MayContainCustomFields;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $modelRules = (new Inventory)->getRules();
        unset($modelRules['serial']);

        $inventory_tag_rules = $modelRules['inventory_tag'];
        unset($modelRules['inventory_tag']);
        array_splice($inventory_tag_rules, array_search('not_array', $inventory_tag_rules), 1, 'distinct');
        foreach ($inventory_tag_rules as $i => $inventory_tag_rule) {
            if (Str::startsWith($inventory_tag_rule, 'unique_undeleted')) {
                $inventory_tag_rules[$i] = new UniqueUndeleted('inventories', 'inventory_tag');
            }
        }

        $serials_unique = Setting::getSettings()['unique_serial'];
        $serials_required = AssetModel::find($this?->model_id)?->require_serial;

        $serial_rules = ['string'];
        if ($serials_unique) {
            $serial_rules[] = new UniqueUndeleted('inventories', 'serial');
            $serial_rules[] = 'distinct';
        }
        if ($serials_required) {
            $serial_rules[] = 'required';
        } else {
            $serial_rules[] = 'nullable';
        }

        return array_merge($modelRules, [
            'serials.*' => $serial_rules,
            'inventory_tags.*' => $inventory_tag_rules,
        ]);
    }
}
