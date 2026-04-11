@extends('layouts/edit-form', [
    'createText' => trans('admin/inventory/reservations/form.create'),
    'updateText' => trans('admin/inventory/reservations/form.update'),
    'topSubmit' => true,
    'helpPosition' => 'right',
    'helpText' => trans('help.reservations'),
    'formAction' => ($item->id) ? route('inventory.reservations.update', $item) : route('inventory.reservations.store'),
    'index_route' => 'inventory.reservations.index',
    'options' => [
        'back' => trans('general.back'),
        'index' => trans('general.list_all', ['type' => trans('admin/inventory/reservations/title.reservations')]),
    ]
])

@section('inputFields')
<div class="form-group {{ $errors->has('item_type') ? ' has-error' : '' }}">
    <label for="item_type" class="col-md-3 control-label">
        {{ trans('admin/inventory/reservations/form.item_type') }}
        <span class="text-danger">*</span>
    </label>
    <div class="col-md-7">
        <select class="form-control" name="item_type" id="item_type" required>
            <option value="consumable" {{ old('item_type', $item->item_type ?? $preset_item_type ?? '') == 'consumable' ? 'selected' : '' }}>
                {{ trans('general.consumable') }}
            </option>
            <option value="accessory" {{ old('item_type', $item->item_type ?? $preset_item_type ?? '') == 'accessory' ? 'selected' : '' }}>
                {{ trans('general.accessory') }}
            </option>
            <option value="component" {{ old('item_type', $item->item_type ?? $preset_item_type ?? '') == 'component' ? 'selected' : '' }}>
                {{ trans('general.component') }}
            </option>
        </select>
        {!! $errors->first('item_type', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('item_id') ? ' has-error' : '' }}">
    <label for="item_id" class="col-md-3 control-label">
        {{ trans('admin/inventory/reservations/form.item') }}
        <span class="text-danger">*</span>
    </label>
    <div class="col-md-7">
        <select class="js-data-ajax" name="item_id" id="item_id" style="width: 100%" required
                data-placeholder="{{ trans('general.select_item') }}"
                data-endpoint="{{ old('item_type', $item->item_type ?? $preset_item_type ?? 'consumable') }}"
                data-allow-clear="true">
            @if ($item->item_id && $item->item)
                <option value="{{ $item->item_id }}" selected>{{ $item->item->name }}</option>
            @elseif($preset_item_id)
                <option value="{{ $preset_item_id }}" selected>{{ trans('general.item_selected') }}</option>
            @endif
        </select>
        {!! $errors->first('item_id', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('quantity') ? ' has-error' : '' }}">
    <label for="quantity" class="col-md-3 control-label">
        {{ trans('admin/inventory/reservations/form.quantity') }}
        <span class="text-danger">*</span>
    </label>
    <div class="col-md-7">
        <input class="form-control" type="number" name="quantity" id="quantity"
               value="{{ old('quantity', $item->quantity) }}" min="1" required />
        {!! $errors->first('quantity', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('department_id') ? ' has-error' : '' }}">
    <label for="department_id" class="col-md-3 control-label">
        {{ trans('admin/inventory/reservations/form.department') }}
    </label>
    <div class="col-md-7">
        <select class="js-data-ajax" name="department_id" id="department_id" style="width: 100%"
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

<div class="form-group {{ $errors->has('project_code') ? ' has-error' : '' }}">
    <label for="project_code" class="col-md-3 control-label">
        {{ trans('admin/inventory/reservations/form.project_code') }}
    </label>
    <div class="col-md-7">
        <input class="form-control" type="text" name="project_code" id="project_code"
               value="{{ old('project_code', $item->project_code) }}" />
        {!! $errors->first('project_code', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('expires_at') ? ' has-error' : '' }}">
    <label for="expires_at" class="col-md-3 control-label">
        {{ trans('admin/inventory/reservations/form.expires_at') }}
    </label>
    <div class="col-md-7">
        <input class="form-control" type="datetime-local" name="expires_at" id="expires_at"
               value="{{ old('expires_at', $item->expires_at?->format('Y-m-d\TH:i')) }}" />
        {!! $errors->first('expires_at', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('notes') ? ' has-error' : '' }}">
    <label for="notes" class="col-md-3 control-label">
        {{ trans('admin/inventory/reservations/form.notes') }}
    </label>
    <div class="col-md-7">
        <textarea class="form-control" name="notes" id="notes" rows="4">{{ old('notes', $item->notes) }}</textarea>
        {!! $errors->first('notes', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>
@stop

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    var itemTypeSelect = document.getElementById('item_type');
    var itemSelect = document.getElementById('item_id');

    if (itemTypeSelect && itemSelect) {
        itemTypeSelect.addEventListener('change', function() {
            var selectedType = this.value;
            itemSelect.dataset.endpoint = selectedType;
            itemSelect.value = '';
        });
    }
</script>
@stop
