@extends('layouts/default')

@section('title')
Reservation {{ $reservation->reservation_number }} - {{ trans('admin/inventory/reservations/title.reservation_details') }}
@parent
@stop

@section('content')
<x-container>
    <div class="row">
        <div class="col-md-8">
            <x-box>
                <x-slot:header>
                    <div class="pull-left">
                        <h3>{{ trans('admin/inventory/reservations/title.reservation_details') }}: {{ $reservation->reservation_number }}</h3>
                    </div>
                    <div class="pull-right">
                        @if($reservation->status === 'active')
                            <a href="{{ route('inventory.reservations.edit', $reservation) }}" class="btn btn-sm btn-warning">
                                <x-icon type="edit" />
                                {{ trans('general.edit') }}
                            </a>
                        @endif
                        <a href="{{ route('inventory.reservations.index') }}" class="btn btn-sm btn-default">
                            {{ trans('general.back') }}
                        </a>
                    </div>
                </x-slot:header>

                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <td width="30%"><strong>{{ trans('admin/inventory/reservations/table.reservation_number') }}</strong></td>
                            <td>{{ $reservation->reservation_number }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reservations/table.item_type') }}</strong></td>
                            <td>{{ ucfirst($reservation->item_type) }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reservations/table.item') }}</strong></td>
                            <td>{{ $reservation->item->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reservations/table.quantity') }}</strong></td>
                            <td>{{ $reservation->quantity }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reservations/table.status') }}</strong></td>
                            <td>
                                @switch($reservation->status)
                                    @case('active')
                                        <span class="label label-success">{{ $reservation->status }}</span>
                                        @break
                                    @case('fulfilled')
                                        <span class="label label-info">{{ $reservation->status }}</span>
                                        @break
                                    @case('cancelled')
                                        <span class="label label-default">{{ $reservation->status }}</span>
                                        @break
                                    @case('expired')
                                        <span class="label label-warning">{{ $reservation->status }}</span>
                                        @break
                                @endswitch
                            </td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reservations/table.department') }}</strong></td>
                            <td>{{ $reservation->department->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reservations/table.project_code') }}</strong></td>
                            <td>{{ $reservation->project_code ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reservations/table.expires_at') }}</strong></td>
                            <td>{{ $reservation->expires_at?->format('Y-m-d H:i') ?? 'No expiration' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reservations/table.fulfilled_at') }}</strong></td>
                            <td>{{ $reservation->fulfilled_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reservations/table.cancelled_at') }}</strong></td>
                            <td>{{ $reservation->cancelled_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reservations/table.notes') }}</strong></td>
                            <td>{{ $reservation->notes ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reservations/table.reserved_by') }}</strong></td>
                            <td>{{ $reservation->reserver->display_name ?? 'N/A' }}</td>
                        </tr>
                    </tbody>
                </table>

                @if($reservation->status === 'active')
                <div class="row">
                    <div class="col-md-12 text-center">
                        <form method="POST" action="{{ route('inventory.reservations.fulfill', $reservation) }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <x-icon type="checkmark" />
                                {{ trans('admin/inventory/reservations/button.fulfill') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('inventory.reservations.cancel', $reservation) }}" style="display: inline; margin-left: 10px;">
                            @csrf
                            <button type="submit" class="btn btn-danger">
                                <x-icon type="delete" />
                                {{ trans('admin/inventory/reservations/button.cancel') }}
                            </button>
                        </form>
                    </div>
                </div>
                @endif
            </x-box>
        </div>

        <div class="col-md-4">
            <x-box>
                <x-slot:header>
                    <h4>{{ trans('admin/inventory/reservations/title.activity') }}</h4>
                </x-slot:header>

                <ul class="list-unstyled">
                    <li>
                        <small class="text-muted">{{ trans('general.created_at') }}</small><br>
                        {{ $reservation->created_at->format('Y-m-d H:i:s') }}
                    </li>
                    <li>
                        <small class="text-muted">{{ trans('general.updated_at') }}</small><br>
                        {{ $reservation->updated_at->format('Y-m-d H:i:s') }}
                    </li>
                </ul>
            </x-box>
        </div>
    </div>
</x-container>
@stop
