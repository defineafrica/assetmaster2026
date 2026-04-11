@extends('layouts/edit-form', [
    'createText' => trans('admin/inventory/stock-thresholds/form.create'),
    'updateText' => trans('admin/inventory/stock-thresholds/form.update'),
    'topSubmit' => true,
    'helpPosition' => 'right',
    'helpText' => trans('help.stock_thresholds'),
    'formAction' => ($item->id) ? route('inventory.stock-thresholds.update', $item) : route('inventory.stock-thresholds.store'),
    'index_route' => 'inventory.stock-thresholds.index',
    'options' => [
        'back' => trans('general.back'),
        'index' => trans('general.list_all', ['type' => trans('admin/inventory/stock-thresholds/title.stock_thresholds')]),
    ]
])

@section('inputFields')
<div class="form-group {{ $errors->has('item_type') ? ' has-error' : '' }}">
    <label for="item_type" class="col-md-3 control-label">
        {{ trans('admin/inventory/stock-thresholds/form.item_type') }}
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
            <option value="component" {{ old('item_type', $item->item_type) == 'component' ? 'selected' : '' }}>
                {{ trans('general.component') }}
            </option>
        </select>
        {!! $errors->first('item_type', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('item_id') ? ' has-error' : '' }}">
    <label for="item_id" class="col-md-3 control-label">
        {{ trans('admin/inventory/stock-thresholds/form.item') }}
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

<div class="form-group {{ $errors->has('min_quantity') ? ' has-error' : '' }}">
    <label for="min_quantity" class="col-md-3 control-label">
        {{ trans('admin/inventory/stock-thresholds/form.min_quantity') }}
        <span class="text-danger">*</span>
    </label>
    <div class="col-md-7">
        <input class="form-control" type="number" name="min_quantity" id="min_quantity"
               value="{{ old('min_quantity', $item->min_quantity) }}" min="0" required />
        {!! $errors->first('min_quantity', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('reorder_quantity') ? ' has-error' : '' }}">
    <label for="reorder_quantity" class="col-md-3 control-label">
        {{ trans('admin/inventory/stock-thresholds/form.reorder_quantity') }}
        <span class="text-danger">*</span>
    </label>
    <div class="col-md-7">
        <input class="form-control" type="number" name="reorder_quantity" id="reorder_quantity"
               value="{{ old('reorder_quantity', $item->reorder_quantity) }}" min="1" required />
        {!! $errors->first('reorder_quantity', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('alert_email') ? ' has-error' : '' }}">
    <label for="alert_email" class="col-md-3 control-label">
        {{ trans('admin/inventory/stock-thresholds/form.alert_email') }}
    </label>
    <div class="col-md-7">
        <input class="form-control" type="email" name="alert_email" id="alert_email"
               value="{{ old('alert_email', $item->alert_email) }}"
               placeholder="{{ trans('admin/inventory/stock-thresholds/form.alert_email_placeholder') }}" />
        {!! $errors->first('alert_email', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group">
    <div class="col-md-7 col-md-offset-3">
        <label>
            <input type="checkbox" value="1" name="send_sms" id="send_sms"
                   {{ old('send_sms', $item->send_sms) == '1' ? 'checked="checked"' : '' }} />
            {{ trans('admin/inventory/stock-thresholds/form.send_sms') }}
        </label>
    </div>
</div>

<div class="form-group">
    <div class="col-md-7 col-md-offset-3">
        <label>
            <input type="checkbox" value="1" name="is_active" id="is_active"
                   {{ old('is_active', $item->is_active ?? true) == '1' ? 'checked="checked"' : '' }} />
            {{ trans('admin/inventory/stock-thresholds/form.is_active') }}
        </label>
    </div>
</div>
@stop

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    var itemTypeSelect = document.getElementById('item_type');
    var itemSelect = document.getElementById('item_id');

    itemTypeSelect.addEventListener('change', function() {
        var selectedType = this.value;
        itemSelect.dataset.endpoint = selectedType;
        itemSelect.value = '';
    });
</script>
@stop
