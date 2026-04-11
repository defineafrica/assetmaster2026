@extends('layouts/edit-form', [
    'createText' => trans('admin/inventory/requisitions/form.create'),
    'updateText' => trans('admin/inventory/requisitions/form.update'),
    'topSubmit' => true,
    'helpPosition' => 'right',
    'helpText' => trans('help.requisitions'),
    'formAction' => ($item->id) ? route('inventory.requisitions.update', $item) : route('inventory.requisitions.store'),
    'index_route' => 'inventory.requisitions.index',
    'options' => [
        'back' => trans('general.back'),
        'index' => trans('general.list_all', ['type' => trans('admin/inventory/requisitions/title.purchase_requisitions')]),
    ]
])

@section('inputFields')
<div class="form-group {{ $errors->has('pr_number') ? ' has-error' : '' }}">
    <label for="pr_number" class="col-md-3 control-label">
        {{ trans('admin/inventory/requisitions/form.pr_number') }}
        <span class="text-danger">*</span>
    </label>
    <div class="col-md-7">
        <input class="form-control" type="text" name="pr_number" id="pr_number"
               value="{{ old('pr_number', $item->pr_number) }}" required />
        {!! $errors->first('pr_number', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('department_id') ? ' has-error' : '' }}">
    <label for="department_id" class="col-md-3 control-label">
        {{ trans('admin/inventory/requisitions/form.department') }}
        <span class="text-danger">*</span>
    </label>
    <div class="col-md-7">
        <select class="js-data-ajax" name="department_id" id="department_id" style="width: 100%" required
                data-placeholder="{{ trans('general.select_department') }}"
                data-endpoint="departments"
                data-allow-clear="true">
            @if ($item->department_id && $item->department)
                <option value="{{ $item->department_id }}" selected>{{ $item->department->name }}</option>
            @endif
        </select>
        {!! $errors->first('department_id', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('justification') ? ' has-error' : '' }}">
    <label for="justification" class="col-md-3 control-label">
        {{ trans('admin/inventory/requisitions/form.justification') }}
    </label>
    <div class="col-md-7">
        <textarea class="form-control" name="justification" id="justification" rows="3">{{ old('justification', $item->justification) }}</textarea>
        {!! $errors->first('justification', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('notes') ? ' has-error' : '' }}">
    <label for="notes" class="col-md-3 control-label">
        {{ trans('admin/inventory/requisitions/form.notes') }}
    </label>
    <div class="col-md-7">
        <textarea class="form-control" name="notes" id="notes" rows="3">{{ old('notes', $item->notes) }}</textarea>
        {!! $errors->first('notes', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

@if($item->id && $item->status === 'draft')
<div class="row">
    <div class="col-md-12">
        <x-box>
            <x-slot:header>
                <h4>{{ trans('admin/inventory/requisitions/title.add_items') }}</h4>
            </x-slot:header>

            <form method="POST" action="{{ route('inventory.requisitions.items.store', $item) }}">
                @csrf
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="item_name">{{ trans('admin/inventory/requisitions/form.item_name') }}</label>
                            <input class="form-control" type="text" name="item_name" id="item_name" required />
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="quantity">{{ trans('admin/inventory/requisitions/form.quantity') }}</label>
                            <input class="form-control" type="number" name="quantity" id="quantity" min="1" required />
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="unit_cost">{{ trans('admin/inventory/requisitions/form.unit_cost') }}</label>
                            <input class="form-control" type="number" name="unit_cost" id="unit_cost" step="0.01" min="0" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="description">{{ trans('admin/inventory/requisitions/form.description') }}</label>
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
                        <th>{{ trans('admin/inventory/requisitions/form.item_name') }}</th>
                        <th>{{ trans('admin/inventory/requisitions/form.quantity') }}</th>
                        <th>{{ trans('admin/inventory/requisitions/form.unit_cost') }}</th>
                        <th>{{ trans('admin/inventory/requisitions/form.total') }}</th>
                        <th>{{ trans('general.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($item->items as $prItem)
                    <tr>
                        <td>{{ $prItem->item_name }}</td>
                        <td>{{ $prItem->quantity }}</td>
                        <td>{{ $prItem->unit_cost ? number_format($prItem->unit_cost, 2) : '-' }}</td>
                        <td>{{ $prItem->total_cost ? number_format($prItem->total_cost, 2) : '-' }}</td>
                        <td>
                            <form method="POST" action="{{ route('inventory.requisitions.items.destroy', [$item, $prItem]) }}" style="display: inline;">
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
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right"><strong>{{ trans('admin/inventory/requisitions/form.total') }}:</strong></td>
                        <td><strong>{{ number_format($item->items->sum('total_cost'), 2) }}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            @endif
        </x-box>
    </div>
</div>
@endif
@stop
