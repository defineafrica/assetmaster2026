<?php

namespace App\Http\Transformers;

use App\Helpers\Helper;
use App\Models\Donor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class DonorsTransformer
{
    public function transformDonors(Collection $donors, $total)
    {
        $array = [];
        foreach ($donors as $donor) {
            $array[] = self::transformDonor($donor);
        }

        return (new DatatablesTransformer)->transformDatatables($array, $total);
    }

    public function transformDonor(Donor $donor = null)
    {
        if ($donor) {
            $array = [
                'id' => (int) $donor->id,
                'name' => e($donor->name),
                'phone' => ($donor->phone!='') ? e($donor->phone): null,
                'fax' => ($donor->fax!='') ? e($donor->fax): null,
                'email' => ($donor->email!='') ? e($donor->email): null,
                'image' =>   ($donor->image) ? Storage::disk('public')->url('donors/'.e($donor->image)) : null,
                'assets_count' => (int) $donor->assets_count,
                'licenses_count' => (int) $donor->licenses_count,
                'accessories_count' => (int) $donor->accessories_count,
                'consumables_count' => (int) $donor->consumables_count,
                'components_count' => (int) $donor->components_count,
                'created_by' => ($donor->adminuser) ? [
                    'id' => (int) $donor->adminuser->id,
                    'name'=> e($donor->adminuser->display_name),
                ] : null,
                'tag_color' => ($donor->tag_color!='') ? e($donor->tag_color): null,
                'notes' => Helper::parseEscapedMarkedownInline($donor->notes),
                'created_at' => Helper::getFormattedDateObject($donor->created_at, 'datetime'),
                'updated_at' => Helper::getFormattedDateObject($donor->updated_at, 'datetime'),
            ];

            $permissions_array['available_actions'] = [
                'update' => Gate::allows('update', Donor::class),
                'delete' => $donor->isDeletable(),
            ];

            $array += $permissions_array;

            return $array;
        }
    }
}