<?php

namespace App\Http\Requests;

class InventoryCheckinRequest extends Request
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $settings = \App\Models\Setting::getSettings();

        $rules = [];

            if($settings->require_checkinout_notes) {
            $rules['note'] = 'string|required';
        }
        return $rules;
    }

    public function response(array $errors)
    {
        return $this->redirector->back()->withInput()->withErrors($errors, $this->errorBag);
    }
}
