@extends('layouts/edit-form', [
    'createText' => trans('admin/inventory/grn/form.create'),
    'updateText' => trans('admin/inventory/grn/form.update'),
    'topSubmit' => true,
    'helpPosition' => 'right',
    'helpText' => trans('help.grn'),
    'formAction' => ($item->id) ? route('inventory.grn.update', $item) : route('inventory.grn.store'),
    'index_route' => 'inventory.grn.index',
    'options' => [
        'back' => trans('general.back'),
        'index' => trans('general.list_all', ['type' => trans('admin/inventory/grn/title.goods_received_notes')]),
    ]
])

@section('inputFields')
<div class="form-group {{ $errors->has('grn_number') ? ' has-error' : '' }}">
    <label for="grn_number" class="col-md-3 control-label">
        {{ trans('admin/inventory/grn/form.grn_number') }}
        <span class="text-danger">*</span>
    </label>
    <div class="col-md-7">
        <input class="form-control" type="text" name="grn_number" id="grn_number"
               value="{{ old('grn_number', $item->grn_number) }}" required />
        {!! $errors->first('grn_number', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('supplier_id') ? ' has-error' : '' }}">
    <label for="supplier_id" class="col-md-3 control-label">
        {{ trans('admin/inventory/grn/form.supplier') }}
        <span class="text-danger">*</span>
    </label>
    <div class="col-md-7">
        <select class="js-data-ajax" name="supplier_id" id="supplier_id" style="width: 100%" required
                data-placeholder="{{ trans('general.select_supplier') }}"
                data-endpoint="suppliers"
                data-allow-clear="true">
            @if ($item->supplier_id && $item->supplier)
                <option value="{{ $item->supplier_id }}" selected>{{ $item->supplier->name }}</option>
            @endif
        </select>
        {!! $errors->first('supplier_id', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('received_date') ? ' has-error' : '' }}">
    <label for="received_date" class="col-md-3 control-label">
        {{ trans('admin/inventory/grn/form.received_date') }}
        <span class="text-danger">*</span>
    </label>
    <div class="col-md-7">
        <input class="form-control" type="date" name="received_date" id="received_date"
               value="{{ old('received_date', $item->received_date?->format('Y-m-d')) }}" required />
        {!! $errors->first('received_date', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('purchase_order_number') ? ' has-error' : '' }}">
    <label for="purchase_order_number" class="col-md-3 control-label">
        {{ trans('admin/inventory/grn/form.purchase_order') }}
    </label>
    <div class="col-md-7">
        <input class="form-control" type="text" name="purchase_order_number" id="purchase_order_number"
               value="{{ old('purchase_order_number', $item->purchase_order_number) }}" />
        {!! $errors->first('purchase_order_number', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('notes') ? ' has-error' : '' }}">
    <label for="notes" class="col-md-3 control-label">
        {{ trans('admin/inventory/grn/form.notes') }}
    </label>
    <div class="col-md-7">
        <textarea class="form-control" name="notes" id="notes" rows="4">{{ old('notes', $item->notes) }}</textarea>
        {!! $errors->first('notes', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

@if($item->id && $item->status === 'draft')
<div class="row">
    <div class="col-md-12">
        <x-box>
            <x-slot:header>
                <h4>{{ trans('admin/inventory/grn/title.add_items') }}</h4>
            </x-slot:header>

            <form method="POST" action="{{ route('inventory.grn.items.store', $item) }}">
                @csrf
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="item_name">{{ trans('admin/inventory/grn/form.item_name') }}</label>
                            <input class="form-control" type="text" name="item_name" id="item_name" required />
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="quantity">{{ trans('admin/inventory/grn/form.quantity') }}</label>
                            <input class="form-control" type="number" name="quantity" id="quantity" min="1" required />
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="unit_cost">{{ trans('admin/inventory/grn/form.unit_cost') }}</label>
                            <input class="form-control" type="number" name="unit_cost" id="unit_cost" step="0.01" min="0" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="description">{{ trans('admin/inventory/grn/form.description') }}</label>
                            <input class="form-control" type="text" name="description" id="description" />
                        </div>
                    </div>
                    <div class="col-md-2" style="padding-top: 25px;">
                        <button type="submit" class="btn btn-sm btn-success">
                            <x-icon type="plus" />
                            {{ trans('general.add') }}
                        </button>
                    </div>
                </div>
            </form>

            @if($item->items && $item->items->count() > 0)
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{ trans('admin/inventory/grn/form.item_name') }}</th>
                        <th>{{ trans('admin/inventory/grn/form.quantity') }}</th>
                        <th>{{ trans('admin/inventory/grn/form.unit_cost') }}</th>
                        <th>{{ trans('admin/inventory/grn/form.total') }}</th>
                        <th>{{ trans('general.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($item->items as $grnItem)
                    <tr>
                        <td>{{ $grnItem->item_name }}</td>
                        <td>{{ $grnItem->quantity }}</td>
                        <td>{{ $grnItem->unit_cost ? number_format($grnItem->unit_cost, 2) : '-' }}</td>
                        <td>{{ $grnItem->total_cost ? number_format($grnItem->total_cost, 2) : '-' }}</td>
                        <td>
                            <form method="POST" action="{{ route('inventory.grn.items.destroy', [$item, $grnItem]) }}" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <x-icon type="delete" />
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </x-box>
    </div>
</div>
@endif
@stop
