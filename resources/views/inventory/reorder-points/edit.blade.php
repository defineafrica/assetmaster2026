@extends('layouts/edit-form', [
    'createText' => trans('admin/inventory/reorder-points/form.create'),
    'updateText' => trans('admin/inventory/reorder-points/form.update'),
    'topSubmit' => true,
    'helpPosition' => 'right',
    'helpText' => trans('help.reorder_points'),
    'formAction' => ($item->id) ? route('inventory.reorder-points.update', $item) : route('inventory.reorder-points.store'),
    'index_route' => 'inventory.reorder-points.index',
    'options' => [
        'back' => trans('general.back'),
        'index' => trans('general.list_all', ['type' => trans('admin/inventory/reorder-points/title.reorder_points')]),
    ]
])

@section('inputFields')
<div class="form-group {{ $errors->has('item_type') ? ' has-error' : '' }}">
    <label for="item_type" class="col-md-3 control-label">
        {{ trans('admin/inventory/reorder-points/form.item_type') }}
        <span class="text-danger">*</span>
    </label>
    <div class="col-md-7">
        <select class="form-control" name="item_type" id="item_type" required>
            <option value="consumable" {{ old('item_type', $item->item_type) == 'consumable' ? 'selected' : '' }}>
                {{ trans('general.consumable') }}
            </option>
            <option value="accessory" {{ old('item_type', $item->item_type) == 'accessory' ? 'selected' : '' }}>
                {{ trans('general.accessory') }}
            </option>
        </select>
        {!! $errors->first('item_type', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('item_id') ? ' has-error' : '' }}">
    <label for="item_id" class="col-md-3 control-label">
        {{ trans('admin/inventory/reorder-points/form.item') }}
        <span class="text-danger">*</span>
    </label>
    <div class="col-md-7">
        <select class="js-data-ajax" name="item_id" id="item_id" style="width: 100%" required
                data-placeholder="{{ trans('general.select_item') }}"
                data-endpoint="{{ old('item_type', $item->item_type) ?? 'consumable' }}"
                data-allow-clear="true">
            @if ($item->item_id && $item->item)
                <option value="{{ $item->item_id }}" selected>{{ $item->item->name }}</option>
            @endif
        </select>
        {!! $errors->first('item_id', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('reorder_point') ? ' has-error' : '' }}">
    <label for="reorder_point" class="col-md-3 control-label">
        {{ trans('admin/inventory/reorder-points/form.reorder_point') }}
        <span class="text-danger">*</span>
    </label>
    <div class="col-md-7">
        <input class="form-control" type="number" name="reorder_point" id="reorder_point"
               value="{{ old('reorder_point', $item->reorder_point) }}" min="0" required />
        {!! $errors->first('reorder_point', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('safety_stock') ? ' has-error' : '' }}">
    <label for="safety_stock" class="col-md-3 control-label">
        {{ trans('admin/inventory/reorder-points/form.safety_stock') }}
    </label>
    <div class="col-md-7">
        <input class="form-control" type="number" name="safety_stock" id="safety_stock"
               value="{{ old('safety_stock', $item->safety_stock ?? 0) }}" min="0" />
        {!! $errors->first('safety_stock', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('reorder_quantity') ? ' has-error' : '' }}">
    <label for="reorder_quantity" class="col-md-3 control-label">
        {{ trans('admin/inventory/reorder-points/form.reorder_quantity') }}
        <span class="text-danger">*</span>
    </label>
    <div class="col-md-7">
        <input class="form-control" type="number" name="reorder_quantity" id="reorder_quantity"
               value="{{ old('reorder_quantity', $item->reorder_quantity) }}" min="1" required />
        {!! $errors->first('reorder_quantity', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('preferred_supplier_id') ? ' has-error' : '' }}">
    <label for="preferred_supplier_id" class="col-md-3 control-label">
        {{ trans('admin/inventory/reorder-points/form.preferred_supplier') }}
    </label>
    <div class="col-md-7">
        <select class="js-data-ajax" name="preferred_supplier_id" id="preferred_supplier_id" style="width: 100%"
                data-placeholder="{{ trans('general.select_supplier') }}"
                data-endpoint="suppliers"
                data-allow-clear="true">
            @if ($item->preferred_supplier_id && $item->preferredSupplier)
                <option value="{{ $item->preferred_supplier_id }}" selected>{{ $item->preferredSupplier->name }}</option>
            @endif
        </select>
        {!! $errors->first('preferred_supplier_id', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('lead_time_days') ? ' has-error' : '' }}">
    <label for="lead_time_days" class="col-md-3 control-label">
        {{ trans('admin/inventory/reorder-points/form.lead_time_days') }}
    </label>
    <div class="col-md-7">
        <input class="form-control" type="number" name="lead_time_days" id="lead_time_days"
               value="{{ old('lead_time_days', $item->lead_time_days ?? 7) }}" min="0" />
        {!! $errors->first('lead_time_days', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('alert_threshold_days') ? ' has-error' : '' }}">
    <label for="alert_threshold_days" class="col-md-3 control-label">
        {{ trans('admin/inventory/reorder-points/form.alert_threshold_days') }}
    </label>
    <div class="col-md-7">
        <input class="form-control" type="number" name="alert_threshold_days" id="alert_threshold_days"
               value="{{ old('alert_threshold_days', $item->alert_threshold_days ?? 3) }}" min="0" />
        {!! $errors->first('alert_threshold_days', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group">
    <div class="col-md-7 col-md-offset-3">
        <label>
            <input type="checkbox" value="1" name="auto_replenish" id="auto_replenish"
                   {{ old('auto_replenish', $item->auto_replenish ?? false) == '1' ? 'checked="checked"' : '' }} />
            {{ trans('admin/inventory/reorder-points/form.auto_replenish') }}
        </label>
    </div>
</div>

<div class="form-group">
    <div class="col-md-7 col-md-offset-3">
        <label>
            <input type="checkbox" value="1" name="is_active" id="is_active"
                   {{ old('is_active', $item->is_active ?? true) == '1' ? 'checked="checked"' : '' }} />
            {{ trans('admin/inventory/reorder-points/form.is_active') }}
        </label>
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
