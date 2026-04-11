@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{ trans('general.quickscan_checkin') }}
    @parent
@stop

{{-- Page content --}}
@section('content')

    <style>

        .input-group {
            padding-left: 0px !important;
        }
    </style>



    <div class="row">
    <form method="POST" action="{{ route('inventories/quickscancheckin') }}" accept-charset="UTF-8" class="form-horizontal" role="form" id="checkin-form">
        <!-- left column -->
        <div class="col-md-6">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title"> {{ trans('admin/hardware/general.bulk_checkin') }} </h2>
                </div>
                <div class="box-body">
                    {{csrf_field()}}

                    <!-- Inventory Tag -->
                    <div class="form-group {{ $errors->has('inventory_tag') ? 'error' : '' }}">
                        <label for="inventory_tag" class="col-md-3 control-label" id="checkin_tag">{{ trans('general.inventory_tag') }}</label>
                        <div class="col-md-9">
                            <div class="input-group col-md-11 required">
                                <input type="text" class="form-control" name="inventory_tag" id="inventory_tag" value="{{ old('inventory_tag') }}" required>

                            </div>
                            {!! $errors->first('inventory_tag', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="form-group {{ $errors->has('status_id') ? 'error' : '' }}">
                        <label for="status_id" class="col-md-3 control-label">
                            {{ trans('admin/hardware/form.status') }}
                        </label>
                        <div class="col-md-7">
                            <x-input.select
                                name="status_id"
                                id="status_id"
                                :options="$statusLabel_list"
                                style="width:100%"
                                aria-label="status_id"
                            />
                            {!! $errors->first('status_id', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                        </div>
                    </div>

                    <!-- Locations -->
                    @include ('partials.forms.edit.location-select', ['translated_name' => trans('general.location'), 'fieldname' => 'location_id'])

                    <!-- Note -->
                        <div class="form-group {{ $errors->has('note') ? 'error' : '' }}">
                            <label for="note" class="col-md-3 control-label">{{ trans('admin/hardware/form.notes') }}</label>
                            <div class="col-md-8">
                                <textarea class="col-md-6 form-control" id="note" name="note">{{ old('note') }}</textarea>
                                {!! $errors->first('note', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                        </div>



                </div> <!--/.box-body-->
                <div class="box-footer">
                    <a class="btn btn-link" href="{{ route('inventories.index') }}"> {{ trans('button.cancel') }}</a>
                    <button type="submit" id="checkin_button" class="btn btn-success pull-right"><x-icon type="checkmark" /> {{ trans('general.checkin') }}</button>
                </div>



            </div>



            </form>
        </div> <!--/.col-md-6-->

        <div class="col-md-6">
            <div class="box box-default" id="checkedin-div" style="display: none">
                <div class="box-header with-border">
                    <h2 class="box-title"> {{ trans('general.quickscan_checkin_status') }} (<span id="checkin-counter">0</span> {{ trans('general.inventories_checked_in_count') }}) </h2>
                </div>
                <div class="box-body">

                    <table id="checkedin" class="table table-striped snipe-table">
                        <thead>
                        <tr>
                            <th>{{ trans('general.inventory_tag') }}</th>
                            <th>{{ trans('general.inventory_model') }}</th>
                            <th>{{ trans('general.model_no') }}</th>
                            <th>{{ trans('general.quickscan_checkin_status') }}</th>
                            <th></th>
                        </tr>
                        <tr id="checkin-loader" style="display: none;">
                            <td colspan="3">
                                <x-icon type="spinner" />  {{ trans('general.processing') }}...
                            </td>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>


@stop


@section('moar_scripts')
    <script nonce="{{ csrf_token() }}">

        $("#checkin-form").submit(function (event) {
            $('#checkedin-div').show();
            $('#checkin-loader').show();

            event.preventDefault();

            var form = $("#checkin-form").get(0);
            var formData = $('#checkin-form').serializeArray();

            $.ajax({
                url: "{{ route('api.inventory.checkinbytag') }}",
                type : 'POST',
                headers: {
                    "X-Requested-With": 'XMLHttpRequest',
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content')
                },
                dataType : 'json',
                data : formData,
                success : function (data) {
                    if (data.status == 'success') {
                        $('#checkedin tbody').prepend("<tr class='success'><td>" + data.payload.inventory_tag + "</td><td>" + data.payload.model + "</td><td>" + data.payload.model_number + "</td><td>" + data.messages + "</td><td><i class='fas fa-check text-success'></i></td></tr>");

                        @if ($user?->enable_sounds)
                        var audio = new Audio('{{ config('app.url') }}/sounds/success.mp3');
                        audio.play()
                        @endif

                        incrementOnSuccess();
                    } else {
                        handlecheckinFail(data);
                    }
                    $('input#inventory_tag').val('');
                },
                error: function (data) {
                    handlecheckinFail(data);
                },
                complete: function() {
                    $('#checkin-loader').hide();
                }

            });

            return false;
        });

        function handlecheckinFail (data) {

            @if ($user?->enable_sounds)
            var audio = new Audio('{{ config('app.url') }}/sounds/error.mp3');
            audio.play()
            @endif

            if (data.payload.inventory_tag) {
                var inventory_tag = data.payload.inventory_tag;
                var model = data.payload.model;
                var model_number = data.payload.model_number;
            } else {
                var inventory_tag = '';
                var model = '';
                var model_number = '';
            }
            if (data.messages) {
                var messages = data.messages;
            } else {
                var messages = '';
            }
            $('#checkedin tbody').prepend("<tr class='danger'><td>" + inventory_tag + "</td><td>" + model + "</td><td>" + model_number + "</td><td>" + messages + "</td><td><i class='fas fa-times text-danger'></i></td></tr>");
        }

        function incrementOnSuccess() {
            var x = parseInt($('#checkin-counter').html());
            y = x + 1;
            $('#checkin-counter').html(y);
        }

        $("#checkin_tag").focus();

    </script>
@stop
