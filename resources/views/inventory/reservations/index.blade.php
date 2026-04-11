@extends('layouts/default')

@section('title')
{{ trans('admin/inventory/reservations/title.reservations') }}
@parent
@stop

@section('content')
<x-container>
    <x-box>
        <x-slot:header>
            <div class="pull-left">
                <h3>{{ trans('admin/inventory/reservations/title.reservations') }}</h3>
            </div>
            <div class="pull-right">
                <a href="{{ route('inventory.reservations.create') }}" class="btn btn-sm btn-warning">
                    <x-icon type="plus" />
                    {{ trans('general.create') }}
                </a>
            </div>
        </x-slot:header>

        <table class="table table-striped snipe-table" id="reservation-table"
               data-cookie-id-table="reservationTable"
               data-height="550"
               data-search="true"
               data-show-columns="true"
               data-show-fullscreen="true"
               data-show-toggle="true"
               data-pagination="true"
               data-side-pagination="server"
               data-show-refresh="true"
               data-smart-display="true"
               data-export-options='{"fileName": "reservations-{{ date("Y-m-d") }}"}'
               data-buttons-class="primary"
               data-show-export="true"
               data-url="{{ route('api.inventory.reservations.index') }}">
            <thead>
                <tr>
                    <th data-sortable="true" data-field="reservation_number">
                        {{ trans('admin/inventory/reservations/table.reservation_number') }}
                    </th>
                    <th data-sortable="true" data-field="item.name">
                        {{ trans('admin/inventory/reservations/table.item') }}
                    </th>
                    <th data-sortable="true" data-field="item_type">
                        {{ trans('admin/inventory/reservations/table.item_type') }}
                    </th>
                    <th data-sortable="true" data-field="quantity">
                        {{ trans('admin/inventory/reservations/table.quantity') }}
                    </th>
                    <th data-sortable="true" data-field="status">
                        {{ trans('admin/inventory/reservations/table.status') }}
                    </th>
                    <th data-sortable="true" data-field="expires_at">
                        {{ trans('admin/inventory/reservations/table.expires_at') }}
                    </th>
                    <th data-formatter="reservationActionsFormatter" data-field="actions">
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
    'exportFile' => 'reservations-export',
    'search' => true
])

<script nonce="{{ csrf_token() }}">
    function reservationStatusFormatter(value) {
        var statusLabels = {
            'active': 'label-success',
            'fulfilled': 'label-info',
            'cancelled': 'label-default',
            'expired': 'label-warning'
        };
        var statusClass = statusLabels[value] || 'label-default';
        return '<span class="label ' + statusClass + '">' + value + '</span>';
    }

    function reservationActionsFormatter(value, row) {
        var actions = '<nobr>';

        actions += '<a href="{{ config('app.url') }}/inventory/reservations/' + row.id + '" class="btn btn-sm btn-default" data-tooltip="true" title="{{ trans('general.view') }}"><x-icon type="view" class="fa-fw" /></a>&nbsp;';

        if (row.status === 'active') {
            if (row.available_actions && row.available_actions.update === true) {
                actions += '<a href="{{ config('app.url') }}/inventory/reservations/' + row.id + '/edit" class="btn btn-sm btn-warning" data-tooltip="true" title="{{ trans('general.edit') }}"><x-icon type="edit" class="fa-fw" /></a>&nbsp;';
            }
            actions += '<a href="{{ config('app.url') }}/inventory/reservations/' + row.id + '/fulfill" class="btn btn-sm btn-success" data-tooltip="true" title="{{ trans('admin/inventory/reservations/button.fulfill') }}"><x-icon type="checkmark" class="fa-fw" /></a>&nbsp;';
            actions += '<a href="{{ config('app.url') }}/inventory/reservations/' + row.id + '/cancel" class="btn btn-sm btn-danger" data-tooltip="true" title="{{ trans('admin/inventory/reservations/button.cancel') }}"><x-icon type="delete" class="fa-fw" /></a>&nbsp;';
        }

        actions += '</nobr>';
        return actions;
    }
</script>
@stop
