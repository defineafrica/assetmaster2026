<?php

namespace App\Http\Requests;

class InventoryCheckoutRequest extends Request
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $settings = \App\Models\Setting::getSettings();

        $rules = [
            'assigned_user' => 'numeric|nullable|required_without_all:assigned_asset,assigned_location',
            'assigned_asset' => 'numeric|nullable|required_without_all:assigned_user,assigned_location',
            'assigned_location' => 'numeric|nullable|required_without_all:assigned_user,assigned_asset',
            'status_id'             => 'exists:status_labels,id,deployable,1',
            'checkout_to_type'      => 'required|in:asset,location,user',
            'checkout_at' => [
                'nullable',
                'date',
            ],
            'expected_checkin' => [
                'nullable',
                'date'
            ],
            ];

            if($settings->require_checkinout_notes) {
                $rules['note'] = 'required|string';
            }

        return $rules;
    }
}
