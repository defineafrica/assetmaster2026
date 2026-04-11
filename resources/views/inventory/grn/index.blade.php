@extends('layouts/default')

@section('title')
{{ trans('admin/inventory/grn/title.goods_received_notes') }}
@parent
@stop

@section('content')
<x-container>
    <x-box>
        <x-slot:header>
            <div class="pull-left">
                <h3>{{ trans('admin/inventory/grn/title.goods_received_notes') }}</h3>
            </div>
            <div class="pull-right">
                <a href="{{ route('inventory.grn.create') }}" class="btn btn-sm btn-warning">
                    <x-icon type="plus" />
                    {{ trans('general.create') }}
                </a>
            </div>
        </x-slot:header>

        <table class="table table-striped snipe-table" id="grn-table"
               data-cookie-id-table="grnTable"
               data-height="550"
               data-search="true"
               data-show-columns="true"
               data-show-fullscreen="true"
               data-show-toggle="true"
               data-pagination="true"
               data-side-pagination="server"
               data-show-refresh="true"
               data-smart-display="true"
               data-export-options='{"fileName": "grn-{{ date("Y-m-d") }}"}'
               data-buttons-class="primary"
               data-show-export="true"
               data-url="{{ route('api.inventory.grn.index') }}">
            <thead>
                <tr>
                    <th data-sortable="true" data-field="grn_number">
                        {{ trans('admin/inventory/grn/table.grn_number') }}
                    </th>
                    <th data-sortable="true" data-field="supplier.name">
                        {{ trans('admin/inventory/grn/table.supplier') }}
                    </th>
                    <th data-sortable="true" data-field="received_date">
                        {{ trans('admin/inventory/grn/table.received_date') }}
                    </th>
                    <th data-sortable="true" data-field="status">
                        {{ trans('admin/inventory/grn/table.status') }}
                    </th>
                    <th data-sortable="true" data-field="inspection_status">
                        {{ trans('admin/inventory/grn/table.inspection_status') }}
                    </th>
                    <th data-sortable="true" data-field="items_count">
                        {{ trans('admin/inventory/grn/table.items') }}
                    </th>
                    <th data-formatter="grnActionsFormatter" data-field="actions">
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
    'exportFile' => 'grn-export',
    'search' => true
])

<script nonce="{{ csrf_token() }}">
    function grnStatusFormatter(value) {
        var statusLabels = {
            'draft': 'label-default',
            'posted': 'label-info',
            'locked': 'label-success',
            'cancelled': 'label-danger'
        };
        var statusClass = statusLabels[value] || 'label-default';
        return '<span class="label ' + statusClass + '">' + value + '</span>';
    }

    function grnInspectionStatusFormatter(value) {
        if (!value) return '-';
        var statusLabels = {
            'pending': 'label-warning',
            'approved': 'label-success',
            'rejected': 'label-danger'
        };
        var statusClass = statusLabels[value] || 'label-default';
        return '<span class="label ' + statusClass + '">' + value + '</span>';
    }

    function grnActionsFormatter(value, row) {
        var actions = '<nobr>';

        actions += '<a href="{{ config('app.url') }}/inventory/grn/' + row.id + '" class="btn btn-sm btn-default" data-tooltip="true" title="{{ trans('general.view') }}"><x-icon type="view" class="fa-fw" /></a>&nbsp;';

        if (row.available_actions && row.available_actions.update === true) {
            actions += '<a href="{{ config('app.url') }}/inventory/grn/' + row.id + '/edit" class="btn btn-sm btn-warning" data-tooltip="true" title="{{ trans('general.edit') }}"><x-icon type="edit" class="fa-fw" /></a>&nbsp;';
        }

        if (row.status === 'draft' && row.available_actions && row.available_actions.delete === true) {
            actions += '<a href="{{ config('app.url') }}/inventory/grn/' + row.id + '" class="btn btn-sm btn-danger delete-asset" data-toggle="modal" data-tooltip="true" title="{{ trans('general.delete') }}"><x-icon type="delete" class="fa-fw" /></a>&nbsp;';
        }

        actions += '</nobr>';
        return actions;
    }
</script>
@stop
