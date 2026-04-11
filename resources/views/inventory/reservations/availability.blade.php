@extends('layouts/default')

@section('title')
{{ $item_type|capitalize }} Availability - {{ trans('admin/inventory/reservations/title.availability') }}
@parent
@stop

@section('content')
<x-container>
    <div class="row">
        <div class="col-md-8">
            <x-box>
                <x-slot:header>
                    <div class="pull-left">
                        <h3>{{ trans('admin/inventory/reservations/title.availability') }}</h3>
                    </div>
                    <div class="pull-right">
                        <a href="{{ route('inventory.reservations.create', ['item_type' => $item_type, 'item_id' => $item_id]) }}" class="btn btn-sm btn-warning">
                            <x-icon type="plus" />
                            {{ trans('admin/inventory/reservations/button.new_reservation') }}
                        </a>
                    </div>
                </x-slot:header>

                <table class="table">
                    <tbody>
                        <tr>
                            <td width="40%"><strong>{{ trans('admin/inventory/reservations/form.item_type') }}</strong></td>
                            <td>{{ ucfirst($item_type) }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reservations/form.available_quantity') }}</strong></td>
                            <td><span class="text-success">{{ $available_quantity }}</span></td>
                        </tr>
                        <tr>
                            <td><strong>{{ trans('admin/inventory/reservations/form.reserved_quantity') }}</strong></td>
                            <td><span class="text-warning">{{ $reserved_quantity }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </x-box>

            <x-box>
                <x-slot:header>
                    <h4>{{ trans('admin/inventory/reservations/title.active_reservations') }}</h4>
                </x-slot:header>

                @if($active_reservations && $active_reservations->count() > 0)
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ trans('admin/inventory/reservations/table.reservation_number') }}</th>
                            <th>{{ trans('admin/inventory/reservations/table.quantity') }}</th>
                            <th>{{ trans('admin/inventory/reservations/table.expires_at') }}</th>
                            <th>{{ trans('admin/inventory/reservations/table.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($active_reservations as $reservation)
                        <tr>
                            <td>
                                <a href="{{ route('inventory.reservations.show', $reservation) }}">
                                    {{ $reservation->reservation_number }}
                                </a>
                            </td>
                            <td>{{ $reservation->quantity }}</td>
                            <td>{{ $reservation->expires_at?->format('Y-m-d H:i') ?? 'No expiration' }}</td>
                            <td><span class="label label-success">{{ $reservation->status }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-muted">{{ trans('admin/inventory/reservations/message.no_active_reservations') }}</p>
                @endif
            </x-box>
        </div>
    </div>
</x-container>
@stop
