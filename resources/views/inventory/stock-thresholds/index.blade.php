@extends('layouts/default')

@section('title')
{{ trans('admin/inventory/stock-thresholds/title.stock_thresholds') }}
@parent
@stop

@section('content')
<x-container>
    <x-box>
        <x-slot:header>
            <div class="pull-left">
                <h3>{{ trans('admin/inventory/stock-thresholds/title.stock_thresholds') }}</h3>
            </div>
            <div class="pull-right">
                <a href="{{ route('inventory.stock-thresholds.create') }}" class="btn btn-sm btn-warning">
                    <x-icon type="plus" />
                    {{ trans('general.create') }}
                </a>
            </div>
        </x-slot:header>

        <table class="table table-striped snipe-table" id="stock-threshold-table"
               data-cookie-id-table="stockThresholds"
               data-height="550"
               data-search="true"
               data-show-columns="true"
               data-show-fullscreen="true"
               data-show-toggle="true"
               data-pagination="true"
               data-side-pagination="server"
               data-show-refresh="true"
               data-smart-display="true"
               data-export-options='{"fileName": "stock-thresholds-{{ date("Y-m-d") }}"}'
               data-buttons-class="primary"
               data-show-export="true"
               data-url="{{ route('api.inventory.stock-thresholds.index') }}">
            <thead>
                <tr>
                    <th data-sortable="true" data-field="item_type" data-visible="true">
                        {{ trans('admin/inventory/stock-thresholds/table.item_type') }}
                    </th>
                    <th data-sortable="true" data-field="item.name" data-visible="true">
                        {{ trans('admin/inventory/stock-thresholds/table.item_name') }}
                    </th>
                    <th data-sortable="true" data-field="min_quantity" data-visible="true">
                        {{ trans('admin/inventory/stock-thresholds/table.min_quantity') }}
                    </th>
                    <th data-sortable="true" data-field="reorder_quantity" data-visible="true">
                        {{ trans('admin/inventory/stock-thresholds/table.reorder_quantity') }}
                    </th>
                    <th data-sortable="true" data-field="current_quantity" data-visible="true">
                        {{ trans('admin/inventory/stock-thresholds/table.current_qty') }}
                    </th>
                    <th data-sortable="true" data-field="is_active" data-visible="true">
                        {{ trans('admin/inventory/stock-thresholds/table.status') }}
                    </th>
                    <th data-formatter="stockThresholdActionsFormatter" data-field="actions">
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
    'exportFile' => 'stock-thresholds-export',
    'search' => true,
    'showFooter' => true
])

<script nonce="{{ csrf_token() }}">
    function stockThresholdActionsFormatter(value, row) {
        var actions = '<nobr>';

        if (row.available_actions && row.available_actions.update === true) {
            actions += '<a href="{{ config('app.url') }}/inventory/stock-thresholds/' + row.id + '/edit" class="btn btn-sm btn-warning" data-tooltip="true" title="{{ trans('general.update') }}"><x-icon type="edit" class="fa-fw" /></a>&nbsp;';
        }

        if (row.available_actions && row.available_actions.delete === true) {
            actions += '<a href="{{ config('app.url') }}/inventory/stock-thresholds/' + row.id + '" class="btn btn-sm btn-danger delete-asset" data-toggle="modal" data-tooltip="true" title="{{ trans('general.delete') }}"><x-icon type="delete" class="fa-fw" /></a>&nbsp;';
        }

        actions += '</nobr>';
        return actions;
    }
</script>
@stop
