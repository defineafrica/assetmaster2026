@extends('layouts/default')

@section('title')
GRN {{ $grn->grn_number }} - {{ trans('admin/inventory/grn/title.grn_details') }}
@parent
@stop

@section('content')
<x-container>
    <div class="row">
        <div class="col-md-8">
            <x-box>
                <x-slot:header>
                    <div class="pull-left">
                        <h3>{{ trans('admin/inventory/grn/title.grn_details') }}: {{ $grn->grn_number }}</h3>
                    </div>
                    <div class="pull-right">
                        @if($grn->status === 'draft')
                            <a href="{{ route('inventory.grn.edit', $grn) }}" class="btn btn-sm btn-warning">
                                <x-icon type="edit" />
                                {{ trans('general.edit') }}
                            </a>
                        @endif
                        <a href="{{ route('inventory.grn.index') }}" class="btn btn-sm btn-default">
                            {{ trans('general.back') }}
                        </a>
                    </div>
                </x-slot:header>

                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <td width="30%"><strong>{{ trans('admin/inventory/grn/table.grn_number') }}</strong></td>
                            <td>{{ $grn->grn_number }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/grn/table.supplier') }}</strong></td>
                            <td>{{ $grn->supplier->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/grn/table.received_date') }}</strong></td>
                            <td>{{ $grn->received_date?->format('Y-m-d') }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/grn/table.purchase_order') }}</strong></td>
                            <td>{{ $grn->purchase_order_number ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/grn/table.status') }}</strong></td>
                            <td>
                                @switch($grn->status)
                                    @case('draft')
                                        <span class="label label-default">{{ $grn->status }}</span>
                                        @break
                                    @case('posted')
                                        <span class="label label-info">{{ $grn->status }}</span>
                                        @break
                                    @case('locked')
                                        <span class="label label-success">{{ $grn->status }}</span>
                                        @break
                                    @case('cancelled')
                                        <span class="label label-danger">{{ $grn->status }}</span>
                                        @break
                                @endswitch
                            </td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/grn/table.inspection_status') }}</strong></td>
                            <td>
                                @if($grn->inspection_status)
                                    @switch($grn->inspection_status)
                                        @case('pending')
                                            <span class="label label-warning">{{ $grn->inspection_status }}</span>
                                            @break
                                        @case('approved')
                                            <span class="label label-success">{{ $grn->inspection_status }}</span>
                                            @break
                                        @case('rejected')
                                            <span class="label label-danger">{{ $grn->inspection_status }}</span>
                                            @break
                                        @default
                                            <span class="label label-default">{{ $grn->inspection_status }}</span>
                                    @endswitch
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/grn/table.notes') }}</strong></td>
                            <td>{{ $grn->notes ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/grn/table.received_by') }}</strong></td>
                            <td>{{ $grn->receiver->display_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/grn/table.approved_by') }}</strong></td>
                            <td>{{ $grn->approver->display_name ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>

                @if($grn->status === 'draft')
                <div class="row">
                    <div class="col-md-12 text-center">
                        <form method="POST" action="{{ route('inventory.grn.post', $grn) }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <x-icon type="checkmark" />
                                {{ trans('admin/inventory/grn/button.post') }}
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                @if($grn->status === 'posted')
                <div class="row">
                    <div class="col-md-12 text-center">
                        <form method="POST" action="{{ route('inventory.grn.lock', $grn) }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <x-icon type="lock" />
                                {{ trans('admin/inventory/grn/button.lock') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('inventory.grn.approve', $grn) }}" style="display: inline; margin-left: 10px;">
                            @csrf
                            <input type="hidden" name="inspection_status" value="approved" />
                            <button type="submit" class="btn btn-success">
                                <x-icon type="checkmark" />
                                {{ trans('admin/inventory/grn/button.approve') }}
                            </button>
                        </form>
                    </div>
                </div>
                @endif
            </x-box>

            @if($grn->items && $grn->items->count() > 0)
            <x-box>
                <x-slot:header>
                    <h4>{{ trans('admin/inventory/grn/title.items') }}</h4>
                </x-slot:header>

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ trans('admin/inventory/grn/form.item_name') }}</th>
                            <th>{{ trans('admin/inventory/grn/form.quantity') }}</th>
                            <th>{{ trans('admin/inventory/grn/form.unit_cost') }}</th>
                            <th>{{ trans('admin/inventory/grn/form.total') }}</th>
                            <th>{{ trans('admin/inventory/grn/form.inspection_result') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($grn->items as $grnItem)
                        <tr>
                            <td>{{ $grnItem->item_name }}</td>
                            <td>{{ $grnItem->quantity }}</td>
                            <td>{{ $grnItem->unit_cost ? number_format($grnItem->unit_cost, 2) : '-' }}</td>
                            <td>{{ $grnItem->total_cost ? number_format($grnItem->total_cost, 2) : '-' }}</td>
                            <td>
                                @if($grnItem->inspection_result)
                                    <span class="label label-{{ $grnItem->inspection_result === 'pass' ? 'success' : 'danger' }}">
                                        {{ $grnItem->inspection_result }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right"><strong>{{ trans('admin/inventory/grn/form.total_value') }}:</strong></td>
                            <td><strong>{{ number_format($grn->items->sum('total_cost'), 2) }}</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </x-box>
            @endif
        </div>

        <div class="col-md-4">
            <x-box>
                <x-slot:header>
                    <h4>{{ trans('admin/inventory/grn/title.activity') }}</h4>
                </x-slot:header>

                <ul class="list-unstyled">
                    <li>
                        <small class="text-muted">{{ trans('general.created_at') }}</small><br>
                        {{ $grn->created_at->format('Y-m-d H:i:s') }}
                    </li>
                    <li>
                        <small class="text-muted">{{ trans('general.updated_at') }}</small><br>
                        {{ $grn->updated_at->format('Y-m-d H:i:s') }}
                    </li>
                </ul>
            </x-box>
        </div>
    </div>
</x-container>
@stop
