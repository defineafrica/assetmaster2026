@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{ trans('general.bulkaudit') }}
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
    <form method="POST" accept-charset="UTF-8" class="form-horizontal" role="form" id="audit-form">
        <!-- left column -->
        <div class="col-md-6">
            <div class="box box-default">

                    <div class="box-body">
                    {{csrf_field()}}

                    <!-- Next Audit -->
                        <div class="form-group {{ $errors->has('inventory_tag') ? 'error' : '' }}">
                            <label for="inventory_tag" class="col-md-3 control-label" id="audit_tag">{{ trans('general.inventory_tag') }}</label>
                            <div class="col-md-9">
                                <div class="input-group date col-md-11 required" data-date-format="yyyy-mm-dd">
                                    <input type="text" class="form-control" name="inventory_tag" id="inventory_tag" required value="{{ old('inventory_tag') }}">

                                </div>
                                {!! $errors->first('inventory_tag', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                        </div>



                        <!-- Locations -->
                    @include ('partials.forms.edit.location-select', ['translated_name' => trans('general.location'), 'fieldname' => 'location_id'])


                    <!-- Update location -->
                        <div class="form-group">
                            <div class="col-sm-offset-3 col-md-9">
                                <label class="form-control">
                                    <input type="checkbox" value="1" name="update_location" {{ old('update_location') == '1' ? ' checked="checked"' : '' }}>
                                    <span>{{ trans('admin/hardware/form.asset_location') }}
                                    <a href="#" class="text-dark-gray" tabindex="0" role="button" data-toggle="popover" data-trigger="focus" title="<i class='far fa-life-ring'></i> {{ trans('general.more_info') }}" data-html="true" data-content="{{ trans('general.quickscan_bulk_help') }}">
                                        <x-icon type="more-info" /></a></span>
                                </label>
                            </div>
                        </div>


                        <!-- Next Audit -->
                        <div class="form-group {{ $errors->has('next_audit_date') ? 'error' : '' }}">
                            <label for="next_audit_date" class="col-md-3 control-label">{{ trans('general.next_audit_date') }}</label>
                            <div class="col-md-9">
                                <div class="input-group date col-md-5" data-provide="datepicker" data-date-format="yyyy-mm-dd" data-date-clear-btn="true">
                                    <input type="text" class="form-control" placeholder="{{ trans('general.next_audit_date') }}" name="next_audit_date" id="next_audit_date" value="{{ old('next_audit_date', $next_audit_date) }}">
                                    <span class="input-group-addon"><x-icon type="calendar" /></span>
                                </div>
                                {!! $errors->first('next_audit_date', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                        </div>


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
                        <button type="submit" id="audit_button" class="btn btn-success pull-right">
                            <x-icon type="checkmark" />
                            {{ trans('general.audit') }}
                        </button>
                    </div>
            </div>



            </form>
        </div> <!--/.col-md-6-->

        <div class="col-md-6">
            <div class="box box-default" id="audited-div" style="display: none">
                <div class="box-header with-border">
                    <h2 class="box-title"> {{ trans('general.bulkaudit_status') }} (<span id="audit-counter">0</span> {{ trans('general.inventories_audited') }}) </h2>
                </div>
                <div class="box-body">

                    <table id="audited" class="table table-striped snipe-table">
                        <thead>
                        <tr>
                            <th>{{ trans('general.inventory_tag') }}</th>
                            <th>{{ trans('general.bulkaudit_status') }}</th>
                            <th>{{ trans('general.status') }}</th>
                            <th>{{ trans('general.notes') }}</th>
                            <th></th>
                        </tr>
                        <tr id="audit-loader" style="display: none;">
                            <td colspan="3">
                                <x-icon type="spinner" />
                                {{ trans('admin/hardware/form.processing') }}
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

        $("#audit-form").submit(function (event) {
            $('#audited-div').show();
            $('#audit-loader').show();

            event.preventDefault();

            var form = $("#audit-form").get(0);
            var formData = $('#audit-form').serializeArray();
            var inventory_tag = $('#inventory_tag').val();

            $.ajax({
                url: "{{ route('api.inventory.audit.legacy') }}",
                type : 'POST',
                headers: {
                    "X-Requested-With": 'XMLHttpRequest',
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content')
                },
                dataType : 'json',
                data : formData,
                success : function (data) {

                    if (data.status == 'success') {
                        $('#audited tbody').prepend("<tr class='success'><td>" + data.payload.inventory_tag + "</td><td>" + data.messages + "</td><td>" + data.payload.status_label + " (" + data.payload.status_type + ")</td><td>" + data.payload.note + "</td><td><i class='fas fa-check text-success' style='font-size:18px;'></i></td></tr>");

                        @if ($user->enable_sounds)
                        var audio = new Audio('{{ config('app.url') }}/sounds/success.mp3');
                        audio.play()
                        @endif

                        incrementOnSuccess();
                    } else {
                        handleAuditFail(data, inventory_tag);
                    }
                    $('input#inventory_tag').val('');
                },
                error: function (data) {
                    handleAuditFail(data, inventory_tag);
                },
                complete: function() {
                    $('#audit-loader').hide();
                }

            });

            return false;
        });

        function handleAuditFail (data, inventory_tag) {
            @if ($user->enable_sounds)
            var audio = new Audio('{{ config('app.url') }}/sounds/error.mp3');
            audio.play()
            @endif


            if ((!inventory_tag) && (data.payload)  && (data.payload.inventory_tag)) {
                inventory_tag = data.payload.inventory_tag;
            }

            inventory_tag = jQuery('<span>' + inventory_tag + '</span>').text();

            let messages = "";

            // Loop through the error messages
            if ((data.messages)  && (data.messages)) {
                for (let x in data.messages) {
                    messages += data.messages[x];
                }
            }

            $('#audited tbody').prepend("<tr class='danger'><td>" + inventory_tag + "</td><td>" + messages + "</td><td></td><td></td><td><i class='fas fa-times text-danger' style='font-size:18px;'></i></td></tr>");
        }

        function incrementOnSuccess() {
            var x = parseInt($('#audit-counter').html());
            y = x + 1;
            $('#audit-counter').html(y);
        }

        $("#audit_tag").focus();

    </script>
@stop
