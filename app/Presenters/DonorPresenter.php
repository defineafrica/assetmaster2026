<?php

namespace App\Presenters;

class DonorPresenter extends Presenter
{
    public static function dataTableLayout()
    {
        $layout = [
            [
                'field' => 'id',
                'searchable' => false,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.id'),
                'visible' => false,
            ], [
                'field' => 'name',
                'searchable' => true,
                'sortable' => true,
                'switchable' => false,
                'title' => trans('admin/donors/table.name'),
                'visible' => true,
                'formatter' => 'donorsLinkFormatter',
            ], [
                'field' => 'phone',
                'searchable' => true,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('admin/users/table.phone'),
                'visible' => false,
                'formatter'    => 'phoneFormatter',
            ], [
                'field' => 'fax',
                'searchable' => true,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('admin/suppliers/table.fax'),
                'visible' => false,
                'formatter'    => 'phoneFormatter',
            ], [
                'field' => 'email',
                'searchable' => true,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('admin/suppliers/table.email'),
                'visible' => true,
				'formatter' => 'emailFormatter',
            ], [
                'field' => 'image',
                'searchable' => false,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.image'),
                'visible' => true,
                'formatter' => 'imageFormatter',
            ], [
                'field' => 'assets_count',
                'searchable' => false,
                'sortable' => true,
                'title' => trans('general.assets'),
                'visible' => true,
                'class' => 'css-barcode',
            ], [
                'field' => 'licenses_count',
                'searchable' => false,
                'sortable' => true,
                'title' => trans('general.licenses'),
                'visible' => true,
                'class' => 'css-license',
            ], [
                'field' => 'accessories_count',
                'searchable' => false,
                'sortable' => true,
                'title' => trans('general.accessories'),
                'visible' => true,
                'class' => 'css-accessory',
            ], [
                'field' => 'consumables_count',
                'searchable' => false,
                'sortable' => true,
                'title' => trans('general.consumables'),
                'visible' => true,
                'class' => 'css-consumable',
            ], [
                'field' => 'components_count',
                'searchable' => false,
                'sortable' => true,
                'title' => trans('general.components'),
                'visible' => true,
                'class' => 'css-component',
            ], [
                'field' => 'tag_color',
                'searchable' => true,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.tag_color'),
                'visible' => false,
                'formatter' => 'colorTagFormatter',
            ],
            [
                'field' => 'notes',
                'searchable' => true,
                'sortable' => true,
                'visible' => false,
                'title' => trans('general.notes'),
            ], [
                'field' => 'created_by',
                'searchable' => false,
                'sortable' => true,
                'title' => trans('general.created_by'),
                'visible' => false,
                'formatter' => 'usersLinkObjFormatter',
            ], [
                'field' => 'created_at',
                'searchable' => true,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.created_at'),
                'visible' => false,
                'formatter' => 'dateDisplayFormatter',
            ], [
                'field' => 'updated_at',
                'searchable' => true,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.updated_at'),
                'visible' => false,
                'formatter' => 'dateDisplayFormatter',
            ], [
                'field' => 'actions',
                'searchable' => false,
                'sortable' => false,
                'switchable' => false,
                'title' => trans('table.actions'),
                'visible' => true,
                'formatter' => 'donorsActionsFormatter',
                'printIgnore' => true,
            ],
        ];

        return json_encode($layout);
    }

    public function nameUrl()
    {
        if (auth()->user()->can('view', ['\App\Models\Donor', $this])) {
            return (string)link_to_route('donors.show', e($this->display_name), $this->id);
        } else {
            return e($this->display_name);
        }
    }

    public function viewUrl()
    {
        return route('donors.show', $this->id);
    }

    public function formattedNameLink() {

        if (auth()->user()->can('view', ['\App\Models\Donor', $this])) {
            return ($this->tag_color ? "<i class='fa-solid fa-fw fa-square' style='color: ".e($this->tag_color)."' aria-hidden='true'></i>" : '').'<a href="'.route('donors.show', e($this->id)).'">'.e($this->display_name).'</a>';
        }

        return ($this->tag_color ? "<i class='fa-solid fa-fw fa-square' style='color: ".e($this->tag_color)."' aria-hidden='true'></i>" : '').e($this->display_name);
    }
}