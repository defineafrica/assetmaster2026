@extends('layouts/default')

@section('title')
{{ $threshold->item->name ?? 'N/A' }} - {{ trans('admin/inventory/stock-thresholds/title.stock_threshold_details') }}
@parent
@stop

@section('content')
<x-container>
    <div class="row">
        <div class="col-md-12">
            <x-box>
                <x-slot:header>
                    <div class="pull-left">
                        <h3>{{ trans('admin/inventory/stock-thresholds/title.stock_threshold_details') }}</h3>
                    </div>
                    <div class="pull-right">
                        <a href="{{ route('inventory.stock-thresholds.edit', $threshold) }}" class="btn btn-sm btn-warning">
                            <x-icon type="edit" />
                            {{ trans('general.edit') }}
                        </a>
                        <a href="{{ route('inventory.stock-thresholds.index') }}" class="btn btn-sm btn-default">
                            {{ trans('general.back') }}
                        </a>
                    </div>
                </x-slot:header>

                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <td width="30%"><strong>{{ trans('admin/inventory/stock-thresholds/table.item_type') }}</strong></td>
                            <td>{{ ucfirst($threshold->item_type) }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/stock-thresholds/table.item_name') }}</strong></td>
                            <td>{{ $threshold->item->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/stock-thresholds/table.min_quantity') }}</strong></td>
                            <td>{{ $threshold->min_quantity }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/stock-thresholds/table.reorder_quantity') }}</strong></td>
                            <td>{{ $threshold->reorder_quantity }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/stock-thresholds/table.current_qty') }}</strong></td>
                            <td>
                                @if($threshold->item)
                                    @php
                                        $currentQty = method_exists($threshold->item, 'qty') ? $threshold->item->qty : 0;
                                        $isLow = $currentQty <= $threshold->min_quantity;
                                    @endphp
                                    <span class="{{ $isLow ? 'text-danger' : 'text-success' }}">
                                        {{ $currentQty }}
                                    </span>
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/stock-thresholds/table.alert_email') }}</strong></td>
                            <td>{{ $threshold->alert_email ?? 'Not configured' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/stock-thresholds/table.send_sms') }}</strong></td>
                            <td>{{ $threshold->send_sms ? 'Yes' : 'No' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/stock-thresholds/table.status') }}</strong></td>
                            <td>
                                @if($threshold->is_active)
                                    <span class="label label-success">{{ trans('general.active') }}</span>
                                @else
                                    <span class="label label-default">{{ trans('general.inactive') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('general.created_at') }}</strong></td>
                            <td>{{ $threshold->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('general.updated_at') }}</strong></td>
                            <td>{{ $threshold->updated_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    </tbody>
                </table>
            </x-box>
        </div>
    </div>
</x-container>
@stop
