@extends('layouts/default')

@section('title')
{{ trans('admin/inventory/reorder-points/title.reorder_points') }}
@parent
@stop

@section('content')
<x-container>
    <x-box>
        <x-slot:header>
            <div class="pull-left">
                <h3>{{ trans('admin/inventory/reorder-points/title.reorder_points') }}</h3>
            </div>
            <div class="pull-right">
                <a href="{{ route('inventory.reorder-points.report') }}" class="btn btn-sm btn-info">
                    <x-icon type="report" />
                    {{ trans('admin/inventory/reorder-points/button.view_report') }}
                </a>
                <a href="{{ route('inventory.reorder-points.create') }}" class="btn btn-sm btn-warning">
                    <x-icon type="plus" />
                    {{ trans('general.create') }}
                </a>
            </div>
        </x-slot:header>

        <table class="table table-striped snipe-table" id="reorder-point-table"
               data-cookie-id-table="reorderPointTable"
               data-height="550"
               data-search="true"
               data-show-columns="true"
               data-show-fullscreen="true"
               data-show-toggle="true"
               data-pagination="true"
               data-side-pagination="server"
               data-show-refresh="true"
               data-smart-display="true"
               data-export-options='{"fileName": "reorder-points-{{ date("Y-m-d") }}"}'
               data-buttons-class="primary"
               data-show-export="true"
               data-url="{{ route('api.inventory.reorder-points.index') }}">
            <thead>
                <tr>
                    <th data-sortable="true" data-field="item.name">
                        {{ trans('admin/inventory/reorder-points/table.item') }}
                    </th>
                    <th data-sortable="true" data-field="item_type">
                        {{ trans('admin/inventory/reorder-points/table.item_type') }}
                    </th>
                    <th data-sortable="true" data-field="reorder_point">
                        {{ trans('admin/inventory/reorder-points/table.reorder_point') }}
                    </th>
                    <th data-sortable="true" data-field="safety_stock">
                        {{ trans('admin/inventory/reorder-points/table.safety_stock') }}
                    </th>
                    <th data-sortable="true" data-field="reorder_quantity">
                        {{ trans('admin/inventory/reorder-points/table.reorder_quantity') }}
                    </th>
                    <th data-sortable="true" data-field="is_active">
                        {{ trans('admin/inventory/reorder-points/table.status') }}
                    </th>
                    <th data-formatter="reorderPointActionsFormatter" data-field="actions">
                        {{ trans('general.actions') }}
                    </th>
                </tr>
            </thead>
        </table>
    </x-box>
</x-container>
@stop

@section('moar_scripts')
@include('partials.bootstrap-table', [
    'exportFile' => 'reorder-points-export',
    'search' => true
])

<script nonce="{{ csrf_token() }}">
    function reorderPointStatusFormatter(value) {
        if (value === true || value === 1) {
            return '<span class="label label-success">{{ trans('general.active') }}</span>';
        }
        return '<span class="label label-default">{{ trans('general.inactive') }}</span>';
    }

    function reorderPointActionsFormatter(value, row) {
        var actions = '<nobr>';

        actions += '<a href="{{ config('app.url') }}/inventory/reorder-points/' + row.id + '" class="btn btn-sm btn-default" data-tooltip="true" title="{{ trans('general.view') }}"><x-icon type="view" class="fa-fw" /></a>&nbsp;';

        if (row.available_actions && row.available_actions.update === true) {
            actions += '<a href="{{ config('app.url') }}/inventory/reorder-points/' + row.id + '/edit" class="btn btn-sm btn-warning" data-tooltip="true" title="{{ trans('general.edit') }}"><x-icon type="edit" class="fa-fw" /></a>&nbsp;';
        }

        if (row.available_actions && row.available_actions.delete === true) {
            actions += '<a href="{{ config('app.url') }}/inventory/reorder-points/' + row.id + '" class="btn btn-sm btn-danger delete-asset" data-toggle="modal" data-tooltip="true" title="{{ trans('general.delete') }}"><x-icon type="delete" class="fa-fw" /></a>&nbsp;';
        }

        actions += '</nobr>';
        return actions;
    }
</script>
@stop
