@extends('layouts/default')

@section('title')
{{ trans('admin/inventory/reorder-points/title.reorder_point_details') }}
@parent
@stop

@section('content')
<x-container>
    <div class="row">
        <div class="col-md-8">
            <x-box>
                <x-slot:header>
                    <div class="pull-left">
                        <h3>{{ trans('admin/inventory/reorder-points/title.reorder_point_details') }}</h3>
                    </div>
                    <div class="pull-right">
                        <a href="{{ route('inventory.reorder-points.edit', $reorderPoint) }}" class="btn btn-sm btn-warning">
                            <x-icon type="edit" />
                            {{ trans('general.edit') }}
                        </a>
                        <a href="{{ route('inventory.reorder-points.index') }}" class="btn btn-sm btn-default">
                            {{ trans('general.back') }}
                        </a>
                    </div>
                </x-slot:header>

                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <td width="30%"><strong>{{ trans('admin/inventory/reorder-points/table.item_type') }}</strong></td>
                            <td>{{ ucfirst($reorderPoint->item_type) }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reorder-points/table.item') }}</strong></td>
                            <td>{{ $reorderPoint->item->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reorder-points/table.reorder_point') }}</strong></td>
                            <td>{{ $reorderPoint->reorder_point }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reorder-points/table.safety_stock') }}</strong></td>
                            <td>{{ $reorderPoint->safety_stock ?? 0 }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reorder-points/table.reorder_quantity') }}</strong></td>
                            <td>{{ $reorderPoint->reorder_quantity }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reorder-points/table.preferred_supplier') }}</strong></td>
                            <td>{{ $reorderPoint->preferredSupplier->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reorder-points/table.lead_time_days') }}</strong></td>
                            <td>{{ $reorderPoint->lead_time_days ?? 7 }} days</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reorder-points/table.alert_threshold_days') }}</strong></td>
                            <td>{{ $reorderPoint->alert_threshold_days ?? 3 }} days</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reorder-points/table.auto_replenish') }}</strong></td>
                            <td>{{ $reorderPoint->auto_replenish ? 'Yes' : 'No' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reorder-points/table.status') }}</strong></td>
                            <td>
                                @if($reorderPoint->is_active)
                                    <span class="label label-success">{{ trans('general.active') }}</span>
                                @else
                                    <span class="label label-default">{{ trans('general.inactive') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('general.created_at') }}</strong></td>
                            <td>{{ $reorderPoint->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('general.updated_at') }}</strong></td>
                            <td>{{ $reorderPoint->updated_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    </tbody>
                </table>
            </x-box>
        </div>
    </div>
</x-container>
@stop
