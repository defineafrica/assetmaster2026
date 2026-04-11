@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{ trans('admin/hardware/general.view') }} {{ $inventory->inventory_tag }}
    @parent
@stop

{{-- Page content --}}
@section('content')


    <div class="row">

        @if (!$inventory->model)
            <div class="col-md-12">
                <div class="callout callout-danger">
                    <p><strong>{{ trans('admin/models/message.no_association') }}</strong> {{ trans('admin/models/message.no_association_fix') }}</p>
                </div>
            </div>
        @endif

        @if ($inventory->checkInvalidNextAuditDate())
            <div class="col-md-12">
                <div class="callout callout-warning">
                    <p><strong>{{ trans('general.warning',
                        [
                            'warning' => trans('admin/hardware/message.warning_audit_date_mismatch',
                                    [
                                        'last_audit_date' => Helper::getFormattedDateObject($inventory->last_audit_date, 'datetime', false),
                                        'next_audit_date' => Helper::getFormattedDateObject($inventory->next_audit_date, 'date', false)
                                    ]
                                    )
                        ]
                        ) }}</strong></p>
                </div>
            </div>
        @endif

        @if ($inventory->deleted_at!='')
            <div class="col-md-12">
                <div class="callout callout-warning">
                    <x-icon type="warning" />
                    {{ trans('general.inventory_deleted_warning') }}
                </div>
            </div>
        @endif

        <div class="col-md-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs hidden-print">

                    <li class="active">
                        <a href="#details" data-toggle="tab">
                          <span class="hidden-lg hidden-md">
                            <x-icon type="info-circle" class="fa-2x" />
                          </span>
                            <span class="hidden-xs hidden-sm">
                                {{ trans('admin/users/general.info') }}
                            </span>
                        </a>
                    </li>

                    <li>
                        <a href="#software" data-toggle="tab">
                          <span class="hidden-lg hidden-md">
                           <x-icon type="licenses" class="fa-2x" />
                          </span>
                            <span class="hidden-xs hidden-sm">
                                {{ trans('general.licenses') }}
                            </span>
                            {!! ($inventory->licenses->count() > 0 ) ? '<span class="badge badge-secondary">'.number_format($inventory->licenses->count()).'</span>' : '' !!}

                        </a>
                    </li>

                    <li>
                        <a href="#components" data-toggle="tab">
                          <span class="hidden-lg hidden-md">
                            <x-icon type="components" class="fa-2x" />
                          </span>
                            <span class="hidden-xs hidden-sm">
                                {{ trans('general.components') }}
                            </span>
                            {!! ($inventory->components->count() > 0 ) ? '<span class="badge badge-secondary">'.number_format($inventory->components->count()).'</span>' : '' !!}

                        </a>
                    </li>

                    <li>
                        <a href="#assets" data-toggle="tab">
                          <span class="hidden-lg hidden-md">
                            <x-icon type="assets" class="fa-2x" />
                          </span>
                            <span class="hidden-xs hidden-sm">
                                {{ trans('general.assets') }}
                            </span>
                            {!! ($inventory->assignedAssets()->count() > 0 ) ? '<span class="badge badge-secondary">'.number_format($inventory->assignedAssets()->count()).'</span>' : '' !!}

                        </a>
                    </li>

                    @if ($inventory->assignedAccessories->count() > 0)
                        <li>
                            <a href="#accessories_assigned" data-toggle="tab" data-tooltip="true">
                                <span class="hidden-lg hidden-md">
                                    <i class="fas fa-keyboard fa-2x"></i>
                                </span>
                                <span class="hidden-xs hidden-sm">
                                    {{ trans('general.accessories_assigned') }}
                                </span>
                                {!! ($inventory->assignedAccessories()->count() > 0 ) ? '<span class="badge badge-secondary">'.number_format($inventory->assignedAccessories()->count()).'</span>' : '' !!}
                            </a>
                        </li>
                    @endif


                    @if ($inventory->audits->count() > 0)
                    <li>
                        <a href="#audits" data-toggle="tab" data-tooltip="true">
                            <span class="hidden-lg hidden-md">
                                <i class="fas fa-clipboard-check fa-2x"></i>
                            </span>
                            <span class="hidden-xs hidden-sm">
                                {{ trans('general.audits') }}
                            </span>
                            {!! ($inventory->audits()->count() > 0 ) ? '<span class="badge badge-secondary">'.number_format($inventory->audits()->count()).'</span>' : '' !!}
                        </a>
                    </li>
                    @endif

                    <li>
                        <a href="#history" data-toggle="tab">
                          <span class="hidden-lg hidden-md">
                              <x-icon type="history" class="fa-2x "/>
                          </span>
                            <span class="hidden-xs hidden-sm">{{ trans('general.history') }}
                          </span>
                        </a>
                    </li>

                    <li>
                        <a href="#maintenances" data-toggle="tab">
                          <span class="hidden-lg hidden-md">
                              <x-icon type="maintenances" class="fa-2x" />
                          </span>
                            <span class="hidden-xs hidden-sm">
                                {{ trans('general.maintenances') }}
                            </span>
                            {!! ($inventory->maintenances()->count() > 0 ) ? '<span class="badge badge-secondary">'.number_format($inventory->maintenances()->count()).'</span>' : '' !!}
                        </a>
                    </li>

                    <li>
                        <a href="#files" data-toggle="tab">
                          <span class="hidden-lg hidden-md">
                            <x-icon type="files" class="fa-2x" />
                          </span>
                            <span class="hidden-xs hidden-sm">
                            {{ trans('general.files') }}
                            </span>
                            {!! ($inventory->uploads->count() > 0 ) ? '<span class="badge badge-secondary">'.number_format($inventory->uploads->count()).'</span>' : '' !!}
                        </a>
                    </li>

                    @can('view', $inventory->model)
                    <li>
                        <a href="#modelfiles" data-toggle="tab">
                          <span class="hidden-lg hidden-md">
                              <x-icon type="more-files" class="fa-2x" />
                          </span>
                            <span class="hidden-xs hidden-sm">
                            {{ trans('general.additional_files') }}
                            </span>
                            {!! ($inventory->model) && ($inventory->model->uploads->count() > 0 ) ? '<span class="badge badge-secondary">'.number_format($inventory->model->uploads->count()).'</span>' : '' !!}
                        </a>
                    </li>
                    @endcan


                    @can('update', \App\Models\Inventory::class)
                        <li class="pull-right">
                            <a href="#" data-toggle="modal" data-target="#uploadFileModal">
                                <span class="hidden-lg hidden-xl hidden-md">
                                    <x-icon type="paperclip" class="fa-2x" />
                                </span>
                                <span class="hidden-xs hidden-sm">
                                    <x-icon type="paperclip" />
                                    {{ trans('button.upload') }}
                                </span>
                            </a>
                        </li>
                    @endcan

                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade in active" id="details">
                    <div class="row">

                        <div class="info-stack-container">
                            <!-- Start button column -->
                            <div class="col-md-3 col-xs-12 col-sm-push-9 info-stack">

                                <div class="col-md-12 text-center">
                                    @if (($inventory->image) || (($inventory->model) && ($inventory->model->image!='')))
                                        <div class="text-center col-md-12" style="padding-bottom: 15px;">
                                            <a href="{{ ($inventory->getImageUrl()) ? $inventory->getImageUrl() : null }}" data-toggle="lightbox" data-type="image">
                                                <img src="{{ ($inventory->getImageUrl()) ? $inventory->getImageUrl() : null }}" class="assetimg img-responsive" alt="{{ $inventory->getDisplayNameAttribute() }}">
                                            </a>
                                        </div>
                                    @else
                                        <!-- generic image goes here -->
                                    @endif
                                </div>


                                @if ($inventory->deleted_at=='')
                                    @can('update', $inventory)
                                        <div class="col-md-12 hidden-print" style="padding-top: 5px;">
                                            <a href="{{ route('inventories.edit', $inventory) }}" class="btn btn-sm btn-warning btn-social btn-block hidden-print">
                                                <x-icon type="edit" />
                                                {{ trans('admin/hardware/general.edit') }}
                                            </a>
                                        </div>
                                    @endcan


                                @if (($inventory->assetstatus) && ($inventory->assetstatus->deployable=='1'))
                                    @if (($inventory->assigned_to != '') && ($inventory->deleted_at==''))
                                        @can('checkin', $inventory)
                                            <div class="col-md-12 hidden-print" style="padding-top: 5px;">
                                                    <span class="tooltip-wrapper"{!! (!$inventory->model ? ' data-tooltip="true" title="'.trans('admin/hardware/general.model_invalid_fix').'"' : '') !!}>
                                                        <a role="button" href="{{ route('inventories.checkin.create', $inventory->id) }}" class="btn btn-sm btn-theme bg-purple btn-social btn-block hidden-print{{ (!$inventory->model ? ' disabled' : '') }}">
                                                            <x-icon type="checkin" />
                                                            {{ trans('admin/hardware/general.checkin') }}
                                                        </a>
                                                    </span>
                                            </div>
                                        @endcan
                                    @elseif (($inventory->assigned_to == '') && ($inventory->deleted_at==''))
                                        @can('checkout', $inventory)
                                            <div class="col-md-12 hidden-print" style="padding-top: 5px;">
                                                    <span class="tooltip-wrapper"{!! (!$inventory->model ? ' data-tooltip="true" title="'.trans('admin/hardware/general.model_invalid_fix').'"' : '') !!}>
                                                        <a href="{{ route('inventories.checkout.create', $inventory->id)  }}" class="btn btn-sm bg-maroon btn-social btn-block hidden-print{{ (!$inventory->model ? ' disabled' : '') }}">
                                                             <x-icon type="checkout" />
                                                            {{ trans('admin/hardware/general.checkout') }}
                                                    </a>
                                                    </span>
                                            </div>
                                        @endcan
                                    @endif
                                @endif

                                        <!-- Add notes -->
                                        @can('update', \App\Models\Inventory::class)
                                            <div class="col-md-12 hidden-print" style="padding-top: 5px;">
                                                <a href="#" style="width: 100%" data-toggle="modal" data-target="#createNoteModal" class="btn btn-sm btn-theme btn-block btn-social hidden-print">
                                                    <x-icon type="note" />
                                                    {{ trans('general.add_note') }}
                                                </a>
                                                @include ('modals.add-note', ['type' => 'inventory', 'id' => $inventory->id])
                                            </div>
                                        @endcan




                                    @can('audit', \App\Models\Inventory::class)
                                        <div class="col-md-12 hidden-print" style="padding-top: 5px;">
                                        <span class="tooltip-wrapper"{!! (!$inventory->model ? ' data-tooltip="true" title="'.trans('admin/hardware/general.model_invalid_fix').'"' : '') !!}>
                                            <a href="{{ route('inventory.audit.create', $inventory->id)  }}" class="btn btn-sm btn-primary btn-block btn-social hidden-print{{ (!$inventory->model ? ' disabled' : '') }}">
                                                 <x-icon type="audit" />
                                             {{ trans('general.audit') }}
                                            </a>
                                        </span>
                                        </div>
                                    @endcan
                                @endif

                                @can('create', $inventory)
                                    <div class="col-md-12 hidden-print" style="padding-top: 5px;">
                                        <a href="{{ route('clone/inventories', $inventory->id) }}" class="btn btn-sm btn-info btn-block btn-social hidden-print">
                                            <x-icon type="clone" />
                                            {{ trans('admin/hardware/general.clone') }}
                                        </a>
                                    </div>
                                @endcan

                                <div class="col-md-12 hidden-print" style="padding-top: 5px;">
                                    <form
                                        method="POST"
                                        action="{{ route('inventories.bulkedit.show') }}"
                                        accept-charset="UTF-8"
                                        class="form-inline"
                                        target="_blank"
                                        id="bulkForm"
                                    >
                                    @csrf
                                    <input type="hidden" name="bulk_actions" value="labels" />
                                    <input type="hidden" name="ids[{{$inventory->id}}]" value="{{ $inventory->id }}" />
                                    <button class="btn btn-block btn-social btn-sm btn-default" id="bulkEdit"{{ (!$inventory->model ? ' disabled' : '') }}{!! (!$inventory->model ? ' data-tooltip="true" title="'.trans('admin/hardware/general.model_invalid').'"' : '') !!}>
                                        <x-icon type="assets" />
                                        {{ trans_choice('button.generate_labels', 1) }}</button>
                                    </form>
                                </div>

                                @can('delete', $inventory)
                                    <div class="col-md-12 hidden-print" style="padding-top: 30px; padding-bottom: 30px;">

                                        @if ($inventory->deleted_at=='')
                                            <button class="btn btn-sm btn-block btn-danger btn-social delete-asset" data-toggle="modal" data-title="{{ trans('general.delete') }}" data-content="{{ trans('general.sure_to_delete_var', ['item' => $inventory->inventory_tag]) }}" data-target="#dataConfirmModal">

                                                <x-icon type="delete" />
                                                @if ($inventory->assignedTo)
                                                    {{ trans('general.checkin_and_delete') }}
                                                @else
                                                    {{ trans('general.delete') }}
                                                @endif
                                            </button>
                                            <span class="sr-only">{{ trans('general.delete') }}</span>
                                        @else
                                            <form method="POST" action="{{ route('restore/inventories', [$inventory]) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-block btn-warning btn-social">
                                                    <x-icon type="restore" />
                                                    {{ trans('general.restore') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endcan

                                @if (($inventory->assignedTo) && ($inventory->deleted_at==''))
                                    <div class="col-md-12" style="text-align: left">
                                        <h2>
                                            {{ trans('admin/hardware/form.checkedout_to') }}
                                            <x-icon type="long-arrow-right" />
                                        </h2>

                                        <ul class="list-unstyled" style="line-height: 25px; font-size: 14px">

                                            @if (($inventory->checkedOutToUser()) && ($inventory->assignedTo->present()->gravatar()))
                                                <li>
                                                    <img src="{{ $inventory->assignedTo->present()->gravatar() }}" class="user-image-inline hidden-print" alt="{{ $inventory->assignedTo->display_name }}">
                                                    {!! $inventory->assignedTo->present()->nameUrl() !!}
                                                </li>
                                            @else
                                                <li>
                                                    <x-icon type="{{ $inventory->assignedType() }}" class="fa-fw" />
                                                    {!! $inventory->assignedTo->present()->nameUrl() !!}
                                                </li>
                                            @endif


                                            @if ((isset($inventory->assignedTo->employee_num)) && ($inventory->assignedTo->employee_num!=''))
                                                <li>
                                                    <x-icon type="employee_num" class="fa-fw"/>
                                                    {{ $inventory->assignedTo->employee_num }}
                                                </li>
                                            @endif
                                            @if ((isset($inventory->assignedTo->email)) && ($inventory->assignedTo->email!=''))
                                                <li>
                                                    <x-icon type="email" class="fa-fw" />
                                                    <a href="mailto:{{ $inventory->assignedTo->email }}">{{ $inventory->assignedTo->email }}</a>
                                                </li>
                                            @endif

                                            @if ((isset($inventory->assignedTo)) && ($inventory->assignedTo->phone!=''))
                                                <li>
                                                    <x-icon type="phone" class="fa-fw" />
                                                    <a href="tel:{{ $inventory->assignedTo->phone }}">{{ $inventory->assignedTo->phone }}</a>
                                                </li>
                                            @endif

                                            @if((isset($inventory->assignedTo)) && ($inventory->assignedTo->department))
                                                <li>
                                                    <x-icon type="department" class="fa-fw" />
                                                    {{ $inventory->assignedTo->department->name}}</li>
                                            @endif

                                            @if (isset($inventory->location))
                                                <li>
                                                    <x-icon type="locations" class="fa-fw" />
                                                     {{ $inventory->location->parent?->name }}
                                                        @if ($inventory->location->parent)
                                                            <i class="fas fa-long-arrow-alt-right" aria-hidden="true"></i>
                                                        @endif
                                                    {!!  $inventory->location->present()->formattedNameLink !!}
                                                </li>
                                                <li>{{ $inventory->location->address }}
                                                    @if ($inventory->location->address2!='')
                                                        {{ $inventory->location->address2 }}
                                                    @endif
                                                </li>

                                                <li>{{ $inventory->location->city }}
                                                    @if (($inventory->location->city!='') && ($inventory->location->state!=''))
                                                        ,
                                                    @endif
                                                    {{ $inventory->location->state }} {{ $inventory->location->zip }}
                                                </li>
                                            @endif
                                            <li>
                                                <x-icon type="calendar" class="fa-fw" />
                                                {{ trans('admin/hardware/form.checkout_date') }}: {{ Helper::getFormattedDateObject($inventory->last_checkout, 'date', false) }}
                                            </li>
                                            @if (isset($inventory->expected_checkin))
                                                <li>
                                                    <x-icon type="calendar" class="fa-fw" />
                                                    {{ trans('general.expected_checkin') }}: {{ Helper::getFormattedDateObject($inventory->expected_checkin, 'date', false) }}
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                @endif
                                @if (($snipeSettings->qr_code=='1') || $snipeSettings->label2_2d_type!='none')
                                    <div class="col-md-12 text-center asset-qr-img" style="padding-top: 15px;">
                                        <img src="{{ config('app.url') }}/inventories/{{ $inventory->id }}/qr_code" class="img-thumbnail" style="height: 150px; width: 150px; margin-right: 10px;" alt="QR code for {{ $inventory->getDisplayNameAttribute() }}">
                                    </div>
                                @endif
                                <br><br>
                            </div>




                            <!-- End button column -->

                            <div class="col-md-9 col-xs-12 col-sm-pull-3 info-stack">

                                <div class="row-new-striped">

                                    @if ($inventory->inventory_tag)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>{{ trans('admin/hardware/form.tag') }}</strong>
                                            </div>
                                            <div class="col-md-9">
                                                <x-copy-to-clipboard copy_what="inventorytag">{{ $inventory->inventory_tag  }}</x-copy-to-clipboard>
                                            </div>
                                        </div>
                                    @endif


                                    @if ($inventory->deleted_at!='')
                                        <div class="row">
                                            <div class="col-md-3">
                                                <span class="text-danger"><strong>{{ trans('general.deleted') }}</strong></span>
                                            </div>
                                            <div class="col-md-9">
                                                {{ \App\Helpers\Helper::getFormattedDateObject($inventory->deleted_at, 'date', false) }}

                                            </div>
                                        </div>
                                    @endif



                                    @if ($inventory->assetstatus)

                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>{{ trans('general.status') }}</strong>
                                            </div>
                                            <div class="col-md-9">
                                                @if (($inventory->assignedTo) && ($inventory->deleted_at==''))
                                                    <x-icon type="circle-solid" class="text-blue" />
                                                    {{ $inventory->assetstatus->name }}
                                                    <label class="label label-default">{{ trans('general.deployed') }}</label>


                                                    <x-icon type="long-arrow-right" />
                                                    <x-icon type="{{ $inventory->assignedType() }}" class="fa-fw" />
                                                    {!!  $inventory->assignedTo->present()->nameUrl() !!}
                                                @else
                                                    @if (($inventory->assetstatus) && ($inventory->assetstatus->deployable=='1'))
                                                        <x-icon type="circle-solid" class="text-green" />
                                                    @elseif (($inventory->assetstatus) && ($inventory->assetstatus->pending=='1'))
                                                        <x-icon type="circle-solid" class="text-orange" />
                                                    @else
                                                        <x-icon type="x" class="text-red" />
                                                    @endif
                                                    <a href="{{ route('statuslabels.show', $inventory->assetstatus->id) }}">
                                                        {{ $inventory->assetstatus->name }}</a>
                                                    <label class="label label-default">{{ $inventory->present()->statusMeta }}</label>

                                                @endif
                                            </div>
                                        </div>
                                    @endif


                                    @if ($inventory->company)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>{{ trans('general.company') }}</strong>
                                            </div>
                                            <div class="col-md-9">
                                                {!!  $inventory->company->present()->formattedNameLink !!}
                                            </div>
                                        </div>
                                    @endif

                                    @if ($inventory->name)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>{{ trans('admin/hardware/form.name') }}</strong>
                                            </div>
                                            <div class="col-md-9">
                                                <x-copy-to-clipboard copy_what="inventoryname">
                                                    {{ $inventory->name }}
                                                </x-copy-to-clipboard>

                                            </div>
                                        </div>
                                    @endif

                                    @if ($inventory->serial)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>{{ trans('admin/hardware/form.serial') }}</strong>
                                            </div>
                                            <div class="col-md-9">
                                                <x-copy-to-clipboard copy_what="serial">
                                                    {{ $inventory->serial  }}
                                                </x-copy-to-clipboard>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($inventory->last_checkout!='')
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/hardware/table.checkout_date') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {{ Helper::getFormattedDateObject($inventory->last_checkout, 'datetime', false) }}
                                            </div>
                                        </div>
                                    @endif

                                    @if ((isset($audit_log)) && ($audit_log->created_at))
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('general.last_audit') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {!! $inventory->checkInvalidNextAuditDate() ? '<i class="fas fa-exclamation-triangle text-orange" aria-hidden="true"></i>' : '' !!}
                                                {{ Helper::getFormattedDateObject($audit_log->created_at, 'datetime', false) }}
                                                @if ($audit_log->user)
                                                    ({{ link_to_route('users.show', $audit_log->user->display_name, [$audit_log->user->id]) }})
                                                @endif

                                            </div>
                                        </div>
                                    @endif

                                    @if ($inventory->next_audit_date)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('general.next_audit_date') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {!! $inventory->checkInvalidNextAuditDate() ? '<i class="fas fa-exclamation-triangle text-orange" aria-hidden="true"></i>' : '' !!}
                                                {{ Helper::getFormattedDateObject($inventory->next_audit_date, 'date', false) }}
                                            </div>
                                        </div>
                                    @endif

                                    @if (($inventory->model) && ($inventory->model->manufacturer))
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/hardware/form.manufacturer') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                <ul class="list-unstyled">

                                                    <li>
                                                        <x-copy-to-clipboard copy_what="manufacturer">{!!  $inventory->model->manufacturer->present()->formattedNameLink !!}</x-copy-to-clipboard>
                                                    </li>

                                                    @if (($inventory->model) && ($inventory->model->manufacturer) &&  ($inventory->model->manufacturer->url!=''))
                                                        <li>
                                                            <x-icon type="globe-us" />
                                                            <a href="{{ $inventory->present()->dynamicUrl($inventory->model->manufacturer->url) }}" target="_blank">
                                                                {{ $inventory->present()->dynamicUrl($inventory->model->manufacturer->url) }}
                                                                <x-icon type="external-link" />
                                                            </a>
                                                        </li>
                                                    @endif

                                                    @if (($inventory->model) && ($inventory->model->manufacturer) &&  ($inventory->model->manufacturer->support_url!=''))
                                                        <li>
                                                            <x-icon type="more-info" />
                                                            <a href="{{ $inventory->present()->dynamicUrl($inventory->model->manufacturer->support_url) }}" target="_blank">
                                                                {{ $inventory->present()->dynamicUrl($inventory->model->manufacturer->support_url) }}
                                                                <x-icon type="external-link" />
                                                            </a>
                                                        </li>
                                                    @endif

                                                    @if (($inventory->model) && ($inventory->model->manufacturer) &&  ($inventory->model->manufacturer->warranty_lookup_url!=''))
                                                        <li>
                                                            <x-icon type="maintenances" />
                                                            <a href="{{ $inventory->present()->dynamicUrl($inventory->model->manufacturer->warranty_lookup_url) }}" target="_blank">
                                                                {{ $inventory->present()->dynamicUrl($inventory->model->manufacturer->warranty_lookup_url) }}

                                                                <x-icon type="external-link" />
                                                                    <span class="sr-only">{{ trans('admin/hardware/general.mfg_warranty_lookup', ['manufacturer' => $inventory->model->manufacturer->name]) }}</span></i>
                                                            </a>
                                                        </li>
                                                    @endif

                                                    @if (($inventory->model) && ($inventory->model->manufacturer->support_phone))
                                                        <li>
                                                            <x-icon type="phone" />
                                                            <a href="tel:{{ $inventory->model->manufacturer->support_phone }}">
                                                                {{ $inventory->model->manufacturer->support_phone }}
                                                            </a>
                                                        </li>
                                                    @endif

                                                    @if (($inventory->model) && ($inventory->model->manufacturer->support_email))
                                                        <li>
                                                            <x-icon type="email" />
                                                            <a href="mailto:{{ $inventory->model->manufacturer->support_email }}">
                                                                {{ $inventory->model->manufacturer->support_email }}
                                                            </a>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>
                                                {{ trans('general.category') }}
                                            </strong>
                                        </div>
                                        <div class="col-md-9">
                                            @if (($inventory->model) && ($inventory->model->category))
                                                <x-copy-to-clipboard copy_what="category">{!!  $inventory->model->category->present()->formattedNameLink !!}</x-copy-to-clipboard>
                                            @else
                                                Invalid category
                                            @endif
                                        </div>
                                    </div>

                                    @if ($inventory->model)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/hardware/form.model') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                @if ($inventory->model)
                                                    <x-copy-to-clipboard copy_what="model">{!!  $inventory->model->present()->formattedNameLink !!}</x-copy-to-clipboard>

                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>
                                                {{ trans('admin/models/table.modelnumber') }}
                                            </strong>
                                        </div>
                                        <div class="col-md-9">
                                            @if (($inventory->model) && ($inventory->model->model_number!=''))
                                                <x-copy-to-clipboard copy_what="model_number">{{ ($inventory->model) ? $inventory->model->model_number : ''}}</x-copy-to-clipboard>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- byod -->
                                    <div class="row byod">
                                        <div class="col-md-3">
                                            <strong>{{ trans('general.byod') }}</strong>
                                        </div>
                                        <div class="col-md-9">
                                            {!! ($inventory->byod=='1') ? '<i class="fas fa-check text-success" aria-hidden="true"></i> '.trans('general.yes') : '<i class="fas fa-times text-danger" aria-hidden="true"></i> '.trans('general.no')  !!}
                                        </div>
                                    </div>

                                    <!-- requestable -->
                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>{{ trans('admin/hardware/general.requestable') }}</strong>
                                        </div>
                                        <div class="col-md-9">
                                            {!! ($inventory->requestable=='1') ? '<i class="fas fa-check text-success" aria-hidden="true"></i> '.trans('general.yes') : '<i class="fas fa-times text-danger" aria-hidden="true"></i> '.trans('general.no')  !!}
                                        </div>
                                    </div>

                                    @if (($inventory->model) && ($inventory->model->fieldset))
                                        @foreach($inventory->model->fieldset->fields as $field)
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ $field->name }}
                                                    </strong>
                                                </div>
                                                <div class="col-md-9{{ (($field->format=='URL') && ($inventory->{$field->db_column_name()}!='')) ? ' ellipsis': '' }}">

                                                    @if (!empty($inventory->{$field->db_column_name()}))
                                                        <x-copy-to-clipboard copy_what="{{ $field->id }}">
                                                        </x-copy-to-clipboard>
                                                        {{-- Hidden span used as copy target --}}
                                                        {{-- It's tempting to break out the HTML into separate lines for this, but it results in extra spaces being added onto the end of the copied value --}}
                                                        @if (($field->field_encrypted=='1') && (Gate::allows('inventories.view.encrypted_custom_fields')))
                                                            <span class="js-copy-{{ $field->id }} visually-hidden hidden-print" style="font-size: 0px;">{{ ($field->isFieldDecryptable($inventory->{$field->db_column_name()}) ? Helper::gracefulDecrypt($field, $inventory->{$field->db_column_name()}) : $inventory->{$field->db_column_name()}) }}</span>
                                                        @elseif (($field->field_encrypted=='1') && (Gate::denies('inventories.view.encrypted_custom_fields')))
                                                            <span class="js-copy-{{ $field->id }} visually-hidden hidden-print" style="font-size: 0px;">{{ strtoupper(trans('admin/custom_fields/general.encrypted')) }}</span>
                                                        @else
                                                            <span class="js-copy-{{ $field->id }} visually-hidden hidden-print" style="font-size: 0px;">{{ $inventory->{$field->db_column_name()} }}</span>
                                                        @endif


                                                        @endif
                                                        @if (($field->field_encrypted=='1') && ($inventory->{$field->db_column_name()}!='') && (Gate::allows('inventories.view.encrypted_custom_fields')))
                                                            <i class="fas fa-lock" data-tooltip="true" data-placement="top" title="{{ trans('admin/custom_fields/general.value_encrypted') }}" onclick="showHideEncValue(this)" id="text-{{ $field->id }}"></i>
                                                        @endif

                                                        @if ($field->isFieldDecryptable($inventory->{$field->db_column_name()} ))
                                                            @can('inventories.view.encrypted_custom_fields')
                                                                @php
                                                                    $fieldSize = strlen(Helper::gracefulDecrypt($field, $inventory->{$field->db_column_name()}))
                                                                @endphp
                                                                @if ($fieldSize > 0)
                                                                    <span id="text-{{ $field->id }}-to-hide">***********</span>
                                                                        @if (($field->format=='URL') && ($inventory->{$field->db_column_name()}!=''))
                                                                            <span class="js-copy-{{ $field->id }} hidden-print"
                                                                                  id="text-{{ $field->id }}-to-show"
                                                                                  style="font-size: 0px;">
                                                                                <a href="{{ Helper::gracefulDecrypt($field, $inventory->{$field->db_column_name()}) }}"
                                                                                        target="_new">{{ Helper::gracefulDecrypt($field, $inventory->{$field->db_column_name()}) }}</a>
                                                                            </span>
                                                                        @elseif (($field->format=='DATE') && ($inventory->{$field->db_column_name()}!=''))
                                                                            <span class="js-copy-{{ $field->id }} hidden-print"
                                                                                  id="text-{{ $field->id }}-to-show"
                                                                                  style="font-size: 0px;">{{ \App\Helpers\Helper::gracefulDecrypt($field, \App\Helpers\Helper::getFormattedDateObject($inventory->{$field->db_column_name()}, 'date', false)) }}</span>
                                                                        @else
                                                                            <span class="js-copy-{{ $field->id }} hidden-print"
                                                                                  id="text-{{ $field->id }}-to-show"
                                                                                  style="font-size: 0px;">{{ Helper::gracefulDecrypt($field, $inventory->{$field->db_column_name()}) }}</span>
                                                                        @endif
                                                                @endif
                                                            @else
                                                                {{ strtoupper(trans('admin/custom_fields/general.encrypted')) }}
                                                            @endcan

                                                        @else
                                                            @if (($field->format=='BOOLEAN') && ($inventory->{$field->db_column_name()}!=''))
                                                                {!! ($inventory->{$field->db_column_name()} == 1) ? "<span class='fas fa-check-circle' style='color:green' />" : "<span class='fas fa-times-circle' style='color:red' />" !!}
                                                            @elseif (($field->format=='URL') && ($inventory->{$field->db_column_name()}!=''))
                                                                <a href="{{ $inventory->{$field->db_column_name()} }}" target="_new">{{ $inventory->{$field->db_column_name()} }}</a>
                                                            @elseif (($field->format=='DATE') && ($inventory->{$field->db_column_name()}!=''))
                                                                {{ \App\Helpers\Helper::getFormattedDateObject($inventory->{$field->db_column_name()}, 'date', false) }}
                                                            @else
                                                                {!! nl2br(e($inventory->{$field->db_column_name()})) !!}
                                                            @endif

                                                        @endif

                                                        @if ($inventory->{$field->db_column_name()}=='')
                                                            &nbsp;
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif


                                        @if ($inventory->purchase_date)
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ trans('admin/hardware/form.date') }}
                                                    </strong>
                                                </div>
                                                <div class="col-md-9">
                                                    {{ Helper::getFormattedDateObject($inventory->purchase_date, 'date', false) }}
                                                    -
                                                    {{ Carbon::parse($inventory->purchase_date)->diffForHumans(['parts' => 3]) }}

                                                </div>
                                            </div>
                                        @endif

                                        @if ($inventory->purchase_cost)
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ trans('admin/hardware/form.cost') }}
                                                    </strong>
                                                </div>
                                                <div class="col-md-9">
                                                    <x-copy-to-clipboard copy_what="purchase_cost">
                                                        @if (($inventory->id) && ($inventory->location))
                                                            {{ $inventory->location->currency }}
                                                        @elseif (($inventory->id) && ($inventory->location))
                                                            {{ $inventory->location->currency }}
                                                        @else
                                                            {{ $snipeSettings->default_currency }}
                                                        @endif
                                                        {{ Helper::formatCurrencyOutput($inventory->purchase_cost)}}
                                                    </x-copy-to-clipboard>

                                                </div>
                                            </div>
                                        @endif
                                        @if(($inventory->components->count() > 0) && ($inventory->purchase_cost))
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ trans('admin/hardware/table.components_cost') }}
                                                    </strong>
                                                </div>
                                                <div class="col-md-9">
                                                    <x-copy-to-clipboard copy_what="component_cost">
                                                        @if (($inventory->id) && ($inventory->location))
                                                            {{ $inventory->location->currency }}
                                                        @elseif (($inventory->id) && ($inventory->location))
                                                            {{ $inventory->location->currency }}
                                                        @else
                                                            {{ $snipeSettings->default_currency }}
                                                        @endif
                                                        {{Helper::formatCurrencyOutput($inventory->getComponentCost())}}
                                                    </x-copy-to-clipboard>
                                                </div>
                                            </div>
                                        @endif
                                        @if (($inventory->model) && ($inventory->depreciation) && ($inventory->purchase_date))
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ trans('admin/hardware/table.current_value') }}
                                                    </strong>
                                                </div>
                                                <div class="col-md-9">
                                                    <x-copy-to-clipboard copy_what="current_value">
                                                        @if (($inventory->id) && ($inventory->location))
                                                            {{ $inventory->location->currency }}
                                                        @elseif (($inventory->id) && ($inventory->location))
                                                            {{ $inventory->location->currency }}
                                                        @else
                                                            {{ $snipeSettings->default_currency }}
                                                        @endif
                                                    {{ Helper::formatCurrencyOutput($inventory->getDepreciatedValue() )}}
                                                    </x-copy-to-clipboard>


                                                </div>
                                            </div>
                                        @endif
                                        @if ($inventory->order_number)
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ trans('general.order_number') }}
                                                    </strong>
                                                </div>
                                                <div class="col-md-9">
                                                    <x-copy-to-clipboard copy_what="order_number"><a href="{{ route('inventories.index', ['order_number' => $inventory->order_number]) }}">{{ $inventory->order_number }}</a></x-copy-to-clipboard>
                                                </div>
                                            </div>
                                        @endif

                                        @if ($inventory->supplier)
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ trans('general.supplier') }}
                                                    </strong>
                                                </div>
                                                <div class="col-md-9">
                                                    <x-copy-to-clipboard copy_what="supplier">{!!  $inventory->supplier->present()->formattedNameLink !!}</x-copy-to-clipboard>
                                                </div>
                                            </div>
                                        @endif

                                        @if ($inventory->donor)
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ trans('general.donor') }}
                                                    </strong>
                                                </div>
                                                <div class="col-md-9">
                                                    <x-copy-to-clipboard copy_what="donor">{!!  $inventory->donor->present()->formattedNameLink !!}</x-copy-to-clipboard>
                                                </div>
                                            </div>
                                        @endif


                                        @if ($inventory->warranty_months)
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ trans('admin/hardware/form.warranty') }}
                                                    </strong>
                                                </div>
                                                <div class="col-md-9">
                                                    {{ trans_choice('general.months_plural', $inventory->warranty_months) }}
                                                    @if (($inventory->model) && ($inventory->model->manufacturer) && ($inventory->model->manufacturer->warranty_lookup_url!=''))
                                                        <a href="{{ $inventory->present()->dynamicUrl($inventory->model->manufacturer->warranty_lookup_url) }}" target="_blank">
                                                            <x-icon type="external-link" />
                                                            <span class="sr-only">{{ trans('admin/hardware/general.mfg_warranty_lookup', ['manufacturer' => $inventory->model->manufacturer->name]) }}</span></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ trans('admin/hardware/form.warranty_expires') }}


                                                    </strong>
                                                </div>
                                                <div class="col-md-9">
                                                    @if ($inventory->purchase_date)
                                                        {{ Helper::getFormattedDateObject($inventory->present()->warranty_expires(), 'date', false) }}
                                                        -
                                                        {{ Carbon::parse($inventory->present()->warranty_expires())->diffForHumans(['parts' => 3]) }}

                                                        @if ($inventory->purchase_date)
                                                            {!! $inventory->present()->warranty_expires() < date("Y-m-d") ? '<i class="fas fa-exclamation-triangle text-orange" aria-hidden="true"></i>' : '' !!}
                                                        @endif
                                                    @else
                                                        {{ trans('general.na_no_purchase_date') }}
                                                    @endif
                                                </div>
                                            </div>

                                        @endif

                                        @if (($inventory->model) && ($inventory->depreciation))
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ trans('general.depreciation') }}
                                                    </strong>
                                                </div>
                                                <div class="col-md-9">
                                                    {{ $inventory->depreciation->name }}
                                                    ({{ trans_choice('general.months_plural', $inventory->depreciation->months) }})
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ trans('admin/hardware/form.fully_depreciated') }}
                                                    </strong>
                                                </div>
                                                <div class="col-md-9">
                                                    @if ($inventory->purchase_date)
                                                        {{ Helper::getFormattedDateObject($inventory->depreciated_date()->format('Y-m-d'), 'date', false) }}
                                                        -
                                                        {{ Carbon::parse($inventory->depreciated_date())->diffForHumans(['parts' => 3]) }}
                                                    @else
                                                        {{ trans('general.na_no_purchase_date') }}
                                                    @endif

                                                </div>
                                            </div>
                                        @endif

                                        @if (($inventory->asset_eol_date) && ($inventory->purchase_date))
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ trans('admin/hardware/form.eol_rate') }}
                                                    </strong>
                                                </div>
                                                <div class="col-md-9">
                                                    {{ (int) Carbon::parse($inventory->asset_eol_date)->diffInMonths($inventory->purchase_date, true) }}
                                                    {{ trans('admin/hardware/form.months') }}

                                                </div>
                                            </div>
                                        @endif
                                        @if ($inventory->asset_eol_date)
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ trans('admin/hardware/form.eol_date') }}
                                                        @if ($inventory->purchase_date)
                                                            {!! $inventory->asset_eol_date < date("Y-m-d") ? '<i class="fas fa-exclamation-triangle text-orange" aria-hidden="true"></i>' : '' !!}
                                                        @endif
                                                    </strong>
                                                </div>
                                                <div class="col-md-9">
                                                    @if ($inventory->asset_eol_date)
                                                        {{ Helper::getFormattedDateObject($inventory->asset_eol_date, 'date', false) }}
                                                        -
                                                        {{ Carbon::parse($inventory->asset_eol_date)->locale(app()->getLocale())->diffForHumans(['parts' => 3]) }}
                                                    @else
                                                        {{ trans('general.na_no_purchase_date') }}
                                                    @endif
                                                    @if ($inventory->eol_explicit =='1')
                                                            <span data-tooltip="true"
                                                                    data-placement="top"
                                                                    data-title="Explicit EOL"
                                                                    title="Explicit EOL">
                                                                    <x-icon type="warning" class="text-primary" />
                                                            </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif


                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/hardware/form.notes') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {!! nl2br(Helper::parseEscapedMarkedownInline($inventory->notes)) !!}
                                            </div>
                                        </div>

                                        @if ($inventory->location)
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ trans('general.location') }}
                                                    </strong>
                                                </div>
                                                <div class="col-md-9">
                                                    <x-copy-to-clipboard copy_what="location">
                                                        @can('superuser')
                                                            <a href="{{ route('locations.show', ['location' => $inventory->location->id]) }}">
                                                                {{ $inventory->location->name }}
                                                            </a>
                                                        @else
                                                            {{ $inventory->location->name }}
                                                        @endcan
                                                    </x-copy-to-clipboard>
                                                </div>
                                            </div>
                                        @endif

                                        @if ($inventory->defaultLoc)
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ trans('admin/hardware/form.default_location') }}
                                                    </strong>
                                                </div>
                                                <div class="col-md-9">
                                                    <x-copy-to-clipboard copy_what="default_location">
                                                        @can('superuser')
                                                            <a href="{{ route('locations.show', ['location' => $inventory->defaultLoc->id]) }}">
                                                                {{ $inventory->defaultLoc->name }}
                                                            </a>
                                                        @else
                                                            {{ $inventory->defaultLoc->name }}
                                                        @endcan
                                                    </x-copy-to-clipboard>
                                                </div>
                                            </div>
                                        @endif

                                        @if ($inventory->created_at!='')
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ trans('general.created_at') }}
                                                    </strong>
                                                </div>
                                                <div class="col-md-9">
                                                    {{ Helper::getFormattedDateObject($inventory->created_at, 'datetime', false) }}
                                                </div>
                                            </div>
                                        @endif

                                        @if ($inventory->updated_at!='')
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ trans('general.updated_at') }}
                                                    </strong>
                                                </div>
                                                <div class="col-md-9">
                                                    {{ Helper::getFormattedDateObject($inventory->updated_at, 'datetime', false) }}
                                                </div>
                                            </div>
                                        @endif

                                        @if ($inventory->expected_checkin!='')
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ trans('general.expected_checkin') }}
                                                    </strong>
                                                </div>
                                                <div class="col-md-9">
                                                    {{ Helper::getFormattedDateObject($inventory->expected_checkin, 'date', false) }}
                                                </div>
                                            </div>
                                        @endif

{{--                                        <div class="row">--}}
{{--                                            <div class="col-md-3">--}}
{{--                                                <strong>--}}
{{--                                                    {!! trans('general.first_checkout') !!}--}}
{{--                                                </strong>--}}
{{--                                            </div>--}}
{{--                                            <div class="col-md-9">--}}
{{--                                                {{ Helper::getFormattedDateObject($inventory->first_checkout_at, 'datetime')['formatted'] ?? '' }}--}}
{{--                                            </div>--}}
{{--                                        </div>--}}


                                        @if ($inventory->last_checkin!='')
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ trans('admin/hardware/table.last_checkin_date') }}
                                                    </strong>
                                                </div>
                                                <div class="col-md-9">
                                                    {{ Helper::getFormattedDateObject($inventory->last_checkin, 'datetime', false) }}
                                                </div>
                                            </div>
                                        @endif



                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('general.checkouts_count') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {{ ($inventory->checkouts) ? (int) $inventory->checkouts->count() : '0' }}
                                            </div>
                                        </div>


                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('general.checkins_count') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {{ ($inventory->checkins) ? (int) $inventory->checkins->count() : '0' }}
                                            </div>
                                        </div>


                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('general.user_requests_count') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {{ ($inventory->userRequests) ? (int) $inventory->userRequests->count() : '0' }}
                                            </div>
                                        </div>

                                    </div> <!--/end striped container-->
                                </div> <!-- end col-md-9 -->
                            </div><!-- end info-stack-container -->
                            </div> <!--/.row-->
                        </div><!-- /.tab-pane -->

                        <div class="tab-pane fade" id="software">
                            <div class="row{{($inventory->licenses->count() > 0 ) ? '' : ' hidden-print'}}">
                                <div class="col-md-12">
                                    <!-- Licenses assets table -->
                                        <table class="table">
                                            <thead>
                                            <tr>
                                                <th>{{ trans('general.name') }}</th>
                                                <th><span class="line"></span>{{ trans('admin/licenses/form.license_key') }}</th>
                                                <th><span class="line"></span>{{ trans('admin/licenses/form.expiration') }}</th>
                                                <th><span class="line"></span>{{ trans('table.actions') }}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach ($inventory->licenseseats as $seat)
                                                @if ($seat->license)
                                                    <tr>
                                                        <td><a href="{{ route('licenses.show', $seat->license->id) }}">{{ $seat->license->name }}</a></td>
                                                        <td>
                                                            @can('viewKeys', $seat->license)
                                                                <code class="single-line"><span class="js-copy-link" data-clipboard-target=".js-copy-key-{{ $seat->id }}" aria-hidden="true" data-tooltip="true" data-placement="top" title="{{ trans('general.copy_to_clipboard') }}"><span class="js-copy-key-{{ $seat->id }}">{{ $seat->license->serial }}</span></span></code>
                                                            @else
                                                                ------------
                                                            @endcan
                                                        </td>
                                                        <td>
                                                            {{ Helper::getFormattedDateObject($seat->license->expiration_date, 'date', false) }}
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('licenses.checkin', $seat->id) }}" class="btn btn-sm bg-purple hidden-print" data-tooltip="true">{{ trans('general.checkin') }}</a>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                            </tbody>
                                        </table>
                                </div><!-- /col -->
                            </div> <!-- row -->
                        </div> <!-- /.tab-pane software -->

                        <div class="tab-pane fade" id="components">
                            <!-- checked out assets table -->
                            <div class="row{{($inventory->components->count() > 0 ) ? '' : ' hidden-print'}}">
                                <div class="col-md-12">

                                        <table class="table table-striped">
                                            <thead>
                                            <th>{{ trans('general.name') }}</th>
                                            <th>{{ trans('general.qty') }}</th>
                                            <th>{{ trans('general.purchase_cost') }}</th>
                                            <th>{{trans('admin/hardware/form.serial')}}</th>
                                            <th>{{trans('general.checkin')}}</th>
                                            <th></th>
                                            </thead>
                                            <tbody>
                                                <?php $totalCost = 0; ?>
                                            @foreach ($inventory->components as $component)


                                                @if (is_null($component->deleted_at))
                                                    <tr>
                                                        <td>
                                                            <a href="{{ route('components.show', $component->id) }}">{{ $component->name }}</a>
                                                        </td>
                                                        <td>{{ $component->pivot->assigned_qty }}</td>
                                                        <td>
                                                            @if ($component->purchase_cost!='')
                                                                {{ trans('general.cost_each', ['amount' => Helper::formatCurrencyOutput($component->purchase_cost)])  }}
                                                            @endif
                                                        </td>
                                                        <td>{{ $component->serial }}</td>
                                                        <td>
                                                            <a href="{{ route('components.checkin.show', $component->pivot->id) }}" class="btn btn-sm bg-purple hidden-print" data-tooltip="true">{{ trans('general.checkin') }}</a>
                                                        </td>

                                                            <?php $totalCost = $totalCost + ($component->purchase_cost *$component->pivot->assigned_qty) ?>
                                                    </tr>
                                                @endif
                                            @endforeach
                                            </tbody>

                                            <tfoot>
                                            <tr>
                                                <td colspan="2">
                                                </td>
                                                <td>{{ $totalCost }}</td>
                                            </tr>
                                            </tfoot>
                                        </table>
                                </div>
                            </div>
                        </div> <!-- /.tab-pane components -->


                        <div class="tab-pane fade" id="assets">
                            <div class="row{{($inventory->assignedAssets->count() > 0 ) ? '' : ' hidden-print'}}">
                                <div class="col-md-12">

                                    @include('partials.asset-bulk-actions')

                                        <!-- checked out assets table -->
                                            <table
                                                data-columns="{{ \App\Presenters\AssetPresenter::dataTableLayout() }}"
                                                data-show-columns-search="true"
                                                data-cookie-id-table="assetsTable"
                                                data-id-table="assetsTable"
                                                data-side-pagination="server"
                                                data-sort-order="asc"
                                                data-toolbar="#assetsBulkEditToolbar"
                                                data-bulk-button-id="#bulkAssetEditButton"
                                                data-bulk-form-id="#assetsBulkForm"
                                                id="assetsListingTable"
                                                class="table table-striped snipe-table"
                                                data-url="{{route('api.assets.index',['assigned_to' => $inventory->id, 'assigned_type' => 'App\Models\Inventory']) }}"
                                                data-export-options='{
                                                  "fileName": "export-inventories-{{ str_slug($inventory->name) }}-assets-{{ date('Y-m-d') }}",
                                                  "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                                                  }'>
                                            </table>
                                </div><!-- /col -->
                            </div> <!-- row -->
                        </div> <!-- /.tab-pane software -->


                    <div class="tab-pane" id="accessories_assigned">

                            <h2 class="box-title" style="float:left">
                                {{ trans('general.accessories_assigned') }}
                            </h2>

                            <table
                                    data-columns="{{ \App\Presenters\AssetPresenter::assignedAccessoriesDataTableLayout() }}"
                                    data-cookie-id-table="accessoriesAssignedListingTable"
                                    data-id-table="accessoriesAssignedListingTable"
                                    data-side-pagination="server"
                                    data-sort-order="asc"
                                    id="accessoriesAssignedListingTable"
                                    class="table table-striped snipe-table"
                                    data-url="{{ route('api.assets.assigned_accessories', ['asset' => $inventory]) }}"
                                    data-export-options='{
                                  "fileName": "export-locations-{{ str_slug($inventory->name) }}-accessories-{{ date('Y-m-d') }}",
                                  "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                                  }'>
                            </table>

                    </div><!-- /.tab-pane -->


                        <div class="tab-pane fade" id="maintenances">
                            <div class="row{{($inventory->maintenances->count() > 0 ) ? '' : ' hidden-print'}}">
                                <div class="col-md-12">

                                    <!-- Asset Maintenance table -->
                                    <table
                                            data-columns="{{ \App\Presenters\MaintenancesPresenter::dataTableLayout() }}"
                                            class="table table-striped snipe-table"
                                            id="MaintenancesTable"
                                            data-buttons="maintenanceButtons"
                                            data-id-table="MaintenancesTable"
                                            data-side-pagination="server"
                                            data-toolbar="#maintenance-toolbar"
                                            data-export-options='{
                                               "fileName": "export-{{ $inventory->inventory_tag }}-maintenances",
                                               "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                                             }'
                                            data-url="{{ route('api.maintenances.index', array('asset_id' => $inventory->id)) }}"
                                            data-cookie-id-table="MaintenancesTable"
                                            data-cookie="true">
                                    </table>
                                </div> <!-- /.col-md-12 -->
                            </div> <!-- /.row -->
                        </div> <!-- /.tab-pane maintenances -->


                    <div class="tab-pane fade" id="audits">
                        <!-- checked out assets table -->
                        <div class="row">
                            <div class="col-md-12">
                                <table
                                        class="table table-striped snipe-table"
                                        id="assetAuditHistory"
                                        data-id-table="assetAuditHistory"
                                        data-side-pagination="server"
                                        data-sort-order="desc"
                                        data-sort-name="created_at"
                                        data-export-options='{
                                             "fileName": "export-inventory-{{  $inventory->id }}-audits",
                                             "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                                           }'
                                        data-url="{{ route('api.activity.index', ['item_id' => $inventory->id, 'item_type' => 'inventory', 'action_type' => 'audit']) }}"
                                        data-cookie-id-table="assetHistory"
                                        data-cookie="true">
                                    <thead>
                                    <tr>
                                        <th data-visible="true" data-field="icon" style="width: 40px;" class="hidden-xs" data-formatter="iconFormatter">{{ trans('admin/hardware/table.icon') }}</th>
                                        <th data-visible="true" data-field="created_at" data-sortable="true" data-formatter="dateDisplayFormatter">{{ trans('general.date') }}</th>
                                        <th data-visible="true" data-field="admin" data-formatter="usersLinkObjFormatter">{{ trans('general.created_by') }}</th>
                                        <th data-visible="true" data-field="image" data-formatter="auditImageFormatter">{{ trans('general.image') }}</th>
                                        <th class="col-sm-2" data-field="file" data-sortable="true" data-visible="false" data-formatter="fileNameFormatter">{{ trans('general.file_name') }}</th>
                                        <th data-field="note">{{ trans('general.notes') }}</th>
                                        <th data-visible="false" data-field="file" data-visible="false"  data-formatter="fileDownloadButtonsFormatter">{{ trans('general.download') }}</th>
                                        <th data-field="log_meta" data-visible="true" data-formatter="changeLogFormatter">{{ trans('admin/hardware/table.changed')}}</th>
                                        <th data-field="remote_ip" data-visible="false" data-sortable="true">{{ trans('admin/settings/general.login_ip') }}</th>
                                        <th data-field="user_agent" data-visible="false" data-sortable="true">{{ trans('admin/settings/general.login_user_agent') }}</th>
                                        <th data-field="action_source" data-visible="false" data-sortable="true">{{ trans('general.action_source') }}</th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                        </div> <!-- /.row -->
                    </div> <!-- /.tab-pane history -->


                    <div class="tab-pane fade" id="history">
                            <!-- checked out assets table -->
                            <div class="row">
                                <div class="col-md-12">
                                    <table
                                            data-columns="{{ \App\Presenters\HistoryPresenter::dataTableLayout() }}"
                                            class="table table-striped snipe-table"
                                            id="assetHistory"
                                            data-id-table="assetHistory"
                                            data-side-pagination="server"
                                            data-sort-order="desc"
                                            data-sort-name="created_at"
                                            data-export-options='{
                                                 "fileName": "export-inventory-{{  $inventory->id }}-history",
                                                 "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                                               }'
                                            data-url="{{ route('api.activity.index', ['item_id' => $inventory->id, 'item_type' => 'inventory']) }}"
                                            data-cookie-id-table="assetHistory"
                                            data-cookie="true">
                                    </table>
                                </div>
                            </div> <!-- /.row -->
                        </div> <!-- /.tab-pane history -->

                        <div class="tab-pane fade" id="files">
                            <div class="row{{ ($inventory->uploads->count() > 0 ) ? '' : ' hidden-print' }}">
                                <div class="col-md-12">
                                    <x-filestable object_type="inventories" :object="$inventory" />
                                </div> <!-- /.col-md-12 -->
                            </div> <!-- /.row -->
                        </div> <!-- /.tab-pane files -->

                        @if ($inventory->model)
                            @can('view', $inventory->model)
                                <div class="tab-pane fade" id="modelfiles">
                                    <div class="row{{ (($inventory->model) && ($inventory->model->uploads->count() > 0)) ? '' : ' hidden-print' }}">
                                        <div class="col-md-12">
                                            <x-filestable object_type="models" :object="$inventory->model" />
                                        </div> <!-- /.col-md-12 -->
                                    </div> <!-- /.row -->
                                </div> <!-- /.tab-pane files -->
                            @endcan
                        @endif
                </div><!-- /.tab-content -->
            </div><!-- nav-tabs-custom -->
        </div>

        @can('update', \App\Models\Inventory::class)
            @include ('modals.upload-file', ['item_type' => 'inventory', 'item_id' => $inventory->id])
        @endcan
    @stop
                @section('moar_scripts')
        @include ('partials.bootstrap-table')

    @stop
