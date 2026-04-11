@extends('layouts/default')

@section('title')
{{ trans('admin/inventory/reorder-points/title.reorder_report') }}
@parent
@stop

@section('content')
<x-container>
    <x-box>
        <x-slot:header>
            <div class="pull-left">
                <h3>{{ trans('admin/inventory/reorder-points/title.reorder_report') }}</h3>
            </div>
            <div class="pull-right">
                <a href="{{ route('inventory.reorder-points.index') }}" class="btn btn-sm btn-default">
                    {{ trans('general.back') }}
                </a>
            </div>
        </x-slot:header>

        @if(isset($report['items_below_reorder']) && count($report['items_below_reorder']) > 0)
        <h4>{{ trans('admin/inventory/reorder-points/report.items_needing_reorder') }}</h4>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>{{ trans('admin/inventory/reorder-points/table.item') }}</th>
                    <th>{{ trans('admin/inventory/reorder-points/table.item_type') }}</th>
                    <th>{{ trans('admin/inventory/reorder-points/table.current_qty') }}</th>
                    <th>{{ trans('admin/inventory/reorder-points/table.reorder_point') }}</th>
                    <th>{{ trans('admin/inventory/reorder-points/table.safety_stock') }}</th>
                    <th>{{ trans('admin/inventory/reorder-points/table.reorder_qty') }}</th>
                    <th>{{ trans('admin/inventory/reorder-points/table.preferred_supplier') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['items_below_reorder'] as $item)
                <tr>
                    <td>
                        <a href="{{ route($item['item_type'].'.show', $item['item_id']) }}">
                            {{ $item['item_name'] }}
                        </a>
                    </td>
                    <td>{{ ucfirst($item['item_type']) }}</td>
                    <td class="text-danger"><strong>{{ $item['current_qty'] }}</strong></td>
                    <td>{{ $item['reorder_point'] }}</td>
                    <td>{{ $item['safety_stock'] ?? 0 }}</td>
                    <td>{{ $item['reorder_quantity'] }}</td>
                    <td>{{ $item['preferred_supplier'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="text-success">{{ trans('admin/inventory/reorder-points/report.all_items_above_reorder') }}</p>
        @endif
    </x-box>

    <x-box>
        <x-slot:header>
            <h4>{{ trans('admin/inventory/reorder-points/report.summary') }}</h4>
        </x-slot:header>

        <table class="table">
            <tbody>
                <tr>
                    <td width="50%"><strong>{{ trans('admin/inventory/reorder-points/report.total_items_tracked') }}</strong></td>
                    <td>{{ $report['total_items_tracked'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td><strong>{{ trans('admin/inventory/reorder-points/report.items_below_reorder') }}</strong></td>
                    <td class="{{ ($report['items_needing_reorder_count'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                        <strong>{{ $report['items_needing_reorder_count'] ?? 0 }}</strong>
                    </td>
                </tr>
                <tr>
                    <td><strong>{{ trans('admin/inventory/reorder-points/report.total_reorder_value') }}</strong></td>
                    <td>{{ number_format($report['total_reorder_value'] ?? 0, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </x-box>
</x-container>
@stop
