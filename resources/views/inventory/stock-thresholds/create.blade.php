@extends('layouts/default')

@section('title')
Create Stock Threshold
@parent
@stop

@section('content')
<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Add Stock Threshold</h3>
            </div>
            <form action="{{ route('inventory.stock-thresholds.store') }}" method="POST">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label for="item_type">Item Type</label>
                        <select name="item_type" id="item_type" class="form-control" required>
                            <option value="consumables">Consumable</option>
                            <option value="accessories">Accessory</option>
                        </select>
                        @if($errors->has('item_type'))
                        <span class="text-danger">{{ $errors->first('item_type') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="item_id">Item</label>
                        <select name="item_id" id="item_id" class="form-control select2" required>
                            <option value="">Select an item...</option>
                        </select>
                        @if($errors->has('item_id'))
                        <span class="text-danger">{{ $errors->first('item_id') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="min_quantity">Minimum Quantity</label>
                        <input type="number" name="min_quantity" id="min_quantity" class="form-control" value="{{ old('min_quantity', 0) }}" min="0" required>
                        @if($errors->has('min_quantity'))
                        <span class="text-danger">{{ $errors->first('min_quantity') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="reorder_quantity">Reorder Quantity</label>
                        <input type="number" name="reorder_quantity" id="reorder_quantity" class="form-control" value="{{ old('reorder_quantity', 0) }}" min="0">
                        @if($errors->has('reorder_quantity'))
                        <span class="text-danger">{{ $errors->first('reorder_quantity') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="alert_email">Alert Email</label>
                        <input type="email" name="alert_email" id="alert_email" class="form-control" value="{{ old('alert_email') }}" placeholder="email@example.com">
                        @if($errors->has('alert_email'))
                        <span class="text-danger">{{ $errors->first('alert_email') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="send_sms">
                            <input type="checkbox" name="send_sms" id="send_sms" value="1" {{ old('send_sms') ? 'checked' : '' }}>
                            Send SMS Alert
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="is_active">
                            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            Active
                        </label>
                    </div>
                </div>

                <div class="box-footer">
                    <a href="{{ route('inventory.stock-thresholds.index') }}" class="btn btn-default">Cancel</a>
                    <button type="submit" class="btn btn-primary pull-right">Create Threshold</button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('moar_scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({
        ajax: {
            url: function() {
                var itemType = $('#item_type').val();
                return '/api/v1/' + itemType;
            },
            data: function(params) {
                return {
                    search: params.term,
                    page: params.page || 1
                };
            },
            processResults: function(data) {
                return {
                    results: data.rows.map(function(item) {
                        return {
                            id: item.id,
                            text: item.name
                        };
                    })
                };
            }
        }
    });

    $('#item_type').change(function() {
        $('#item_id').val(null).trigger('change');
    });
});
</script>
@stop
