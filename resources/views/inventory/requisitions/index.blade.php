@extends('layouts/default')

@section('title')
{{ trans('admin/inventory/requisitions/title.purchase_requisitions') }}
@parent
@stop

@section('content')
<x-container>
    <x-box>
        <x-slot:header>
            <div class="pull-left">
                <h3>{{ trans('admin/inventory/requisitions/title.purchase_requisitions') }}</h3>
            </div>
            <div class="pull-right">
                <a href="{{ route('inventory.requisitions.create') }}" class="btn btn-sm btn-warning">
                    <x-icon type="plus" />
                    {{ trans('general.create') }}
                </a>
            </div>
        </x-slot:header>

        <table class="table table-striped snipe-table" id="requisition-table"
               data-cookie-id-table="requisitionTable"
               data-height="550"
               data-search="true"
               data-show-columns="true"
               data-show-fullscreen="true"
               data-show-toggle="true"
               data-pagination="true"
               data-side-pagination="server"
               data-show-refresh="true"
               data-smart-display="true"
               data-export-options='{"fileName": "requisitions-{{ date("Y-m-d") }}"}'
               data-buttons-class="primary"
               data-show-export="true"
               data-url="{{ route('api.inventory.requisitions.index') }}">
            <thead>
                <tr>
                    <th data-sortable="true" data-field="pr_number">
                        {{ trans('admin/inventory/requisitions/table.pr_number') }}
                    </th>
                    <th data-sortable="true" data-field="requesting_user.name">
                        {{ trans('admin/inventory/requisitions/table.requested_by') }}
                    </th>
                    <th data-sortable="true" data-field="department.name">
                        {{ trans('admin/inventory/requisitions/table.department') }}
                    </th>
                    <th data-sortable="true" data-field="status">
                        {{ trans('admin/inventory/requisitions/table.status') }}
                    </th>
                    <th data-sortable="true" data-field="estimated_total">
                        {{ trans('admin/inventory/requisitions/table.total') }}
                    </th>
                    <th data-sortable="true" data-field="created_at">
                        {{ trans('admin/inventory/requisitions/table.created_at') }}
                    </th>
                    <th data-formatter="requisitionActionsFormatter" data-field="actions">
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
    'exportFile' => 'requisitions-export',
    'search' => true
])

<script nonce="{{ csrf_token() }}">
    function requisitionStatusFormatter(value) {
        var statusLabels = {
            'draft': 'label-default',
            'submitted': 'label-info',
            'approved': 'label-success',
            'rejected': 'label-danger',
            'fulfilled': 'label-primary'
        };
        var statusClass = statusLabels[value] || 'label-default';
        return '<span class="label ' + statusClass + '">' + value + '</span>';
    }

    function requisitionActionsFormatter(value, row) {
        var actions = '<nobr>';

        actions += '<a href="{{ config('app.url') }}/inventory/requisitions/' + row.id + '" class="btn btn-sm btn-default" data-tooltip="true" title="{{ trans('general.view') }}"><x-icon type="view" class="fa-fw" /></a>&nbsp;';

        if (row.status === 'draft' && row.available_actions && row.available_actions.update === true) {
            actions += '<a href="{{ config('app.url') }}/inventory/requisitions/' + row.id + '/edit" class="btn btn-sm btn-warning" data-tooltip="true" title="{{ trans('general.edit') }}"><x-icon type="edit" class="fa-fw" /></a>&nbsp;';
        }

        if (row.status === 'draft' && row.available_actions && row.available_actions.delete === true) {
            actions += '<a href="{{ config('app.url') }}/inventory/requisitions/' + row.id + '" class="btn btn-sm btn-danger delete-asset" data-toggle="modal" data-tooltip="true" title="{{ trans('general.delete') }}"><x-icon type="delete" class="fa-fw" /></a>&nbsp;';
        }

        actions += '</nobr>';
        return actions;
    }
</script>
@stop
