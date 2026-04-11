@extends('layouts/default')

@section('title')
PR {{ $pr->pr_number }} - {{ trans('admin/inventory/requisitions/title.pr_details') }}
@parent
@stop

@section('content')
<x-container>
    <div class="row">
        <div class="col-md-8">
            <x-box>
                <x-slot:header>
                    <div class="pull-left">
                        <h3>{{ trans('admin/inventory/requisitions/title.pr_details') }}: {{ $pr->pr_number }}</h3>
                    </div>
                    <div class="pull-right">
                        @if($pr->status === 'draft')
                            <a href="{{ route('inventory.requisitions.edit', $pr) }}" class="btn btn-sm btn-warning">
                                <x-icon type="edit" />
                                {{ trans('general.edit') }}
                            </a>
                        @endif
                        <a href="{{ route('inventory.requisitions.index') }}" class="btn btn-sm btn-default">
                            {{ trans('general.back') }}
                        </a>
                    </div>
                </x-slot:header>

                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <td width="30%"><strong>{{ trans('admin/inventory/requisitions/table.pr_number') }}</strong></td>
                            <td>{{ $pr->pr_number }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/requisitions/table.requested_by') }}</strong></td>
                            <td>{{ $pr->requestingUser->display_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/requisitions/table.department') }}</strong></td>
                            <td>{{ $pr->department->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/requisitions/table.status') }}</strong></td>
                            <td>
                                @switch($pr->status)
                                    @case('draft')
                                        <span class="label label-default">{{ $pr->status }}</span>
                                        @break
                                    @case('submitted')
                                        <span class="label label-info">{{ $pr->status }}</span>
                                        @break
                                    @case('approved')
                                        <span class="label label-success">{{ $pr->status }}</span>
                                        @break
                                    @case('rejected')
                                        <span class="label label-danger">{{ $pr->status }}</span>
                                        @break
                                    @case('fulfilled')
                                        <span class="label label-primary">{{ $pr->status }}</span>
                                        @break
                                @endswitch
                            </td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/requisitions/table.justification') }}</strong></td>
                            <td>{{ $pr->justification ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/requisitions/table.notes') }}</strong></td>
                            <td>{{ $pr->notes ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/requisitions/table.estimated_total') }}</strong></td>
                            <td>{{ number_format($pr->estimated_total ?? 0, 2) }}</td>
                        </tr>
                        @if($pr->approved_by)
                        <tr>
                            <td><strong>{{ trans('admin/inventory/requisitions/table.approved_by') }}</strong></td>
                            <td>{{ $pr->approver->display_name ?? 'N/A' }} ({{ $pr->approved_at?->format('Y-m-d H:i') }})</td>
                        </tr>
                        @endif
                        @if($pr->rejected_by)
                        <tr>
                            <td><strong>{{ trans('admin/inventory/requisitions/table.rejected_by') }}</strong></td>
                            <td>{{ $pr->rejector->display_name ?? 'N/A' }} ({{ $pr->rejected_at?->format('Y-m-d H:i') }})</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/requisitions/table.rejection_reason') }}</strong></td>
                            <td>{{ $pr->rejection_reason ?? '-' }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>

                @if($pr->status === 'draft')
                <div class="row">
                    <div class="col-md-12 text-center">
                        <form method="POST" action="{{ route('inventory.requisitions.submit', $pr) }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <x-icon type="send" />
                                {{ trans('admin/inventory/requisitions/button.submit') }}
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                @if($pr->status === 'submitted')
                <div class="row">
                    <div class="col-md-12 text-center">
                        <form method="POST" action="{{ route('inventory.requisitions.approve', $pr) }}" style="display: inline;">
                            @csrf
                            <input type="hidden" name="comments" value="" />
                            <button type="submit" class="btn btn-success">
                                <x-icon type="checkmark" />
                                {{ trans('admin/inventory/requisitions/button.approve') }}
                            </button>
                        </form>
                        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectModal" style="margin-left: 10px;">
                            <x-icon type="delete" />
                            {{ trans('admin/inventory/requisitions/button.reject') }}
                        </button>
                    </div>
                </div>

                <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('inventory.requisitions.reject', $pr) }}">
                                @csrf
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h4 class="modal-title">{{ trans('admin/inventory/requisitions/button.reject') }}</h4>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="rejection_reason">{{ trans('admin/inventory/requisitions/form.rejection_reason') }}</label>
                                        <textarea class="form-control" name="rejection_reason" id="rejection_reason" rows="3" required></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('button.cancel') }}</button>
                                    <button type="submit" class="btn btn-danger">{{ trans('admin/inventory/requisitions/button.reject') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif

                @if($pr->status === 'approved')
                <div class="row">
                    <div class="col-md-12 text-center">
                        <form method="POST" action="{{ route('inventory.requisitions.fulfill', $pr) }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <x-icon type="checkmark" />
                                {{ trans('admin/inventory/requisitions/button.fulfill') }}
                            </button>
                        </form>
                    </div>
                </div>
                @endif
            </x-box>

            @if($pr->items && $pr->items->count() > 0)
            <x-box>
                <x-slot:header>
                    <h4>{{ trans('admin/inventory/requisitions/title.items') }}</h4>
                </x-slot:header>

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ trans('admin/inventory/requisitions/form.item_name') }}</th>
                            <th>{{ trans('admin/inventory/requisitions/form.quantity') }}</th>
                            <th>{{ trans('admin/inventory/requisitions/form.unit_cost') }}</th>
                            <th>{{ trans('admin/inventory/requisitions/form.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pr->items as $prItem)
                        <tr>
                            <td>{{ $prItem->item_name }}</td>
                            <td>{{ $prItem->quantity }}</td>
                            <td>{{ $prItem->unit_cost ? number_format($prItem->unit_cost, 2) : '-' }}</td>
                            <td>{{ $prItem->total_cost ? number_format($prItem->total_cost, 2) : '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right"><strong>{{ trans('admin/inventory/requisitions/form.total') }}:</strong></td>
                            <td><strong>{{ number_format($pr->items->sum('total_cost'), 2) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </x-box>
            @endif
        </div>

        <div class="col-md-4">
            <x-box>
                <x-slot:header>
                    <h4>{{ trans('admin/inventory/requisitions/title.activity') }}</h4>
                </x-slot:header>

                <ul class="list-unstyled">
                    <li>
                        <small class="text-muted">{{ trans('general.created_at') }}</small><br>
                        {{ $pr->created_at->format('Y-m-d H:i:s') }}
                    </li>
                    <li>
                        <small class="text-muted">{{ trans('general.updated_at') }}</small><br>
                        {{ $pr->updated_at->format('Y-m-d H:i:s') }}
                    </li>
                </ul>
            </x-box>
        </div>
    </div>
</x-container>
@stop
