@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{ $donor->name }}
    @parent
@stop

@section('header_right')
    <i class="fa-regular fa-2x fa-square-caret-right pull-right" id="expand-info-panel-button" data-tooltip="true" title="{{ trans('button.show_hide_info') }}"></i>
@endsection

{{-- Page content --}}
@section('content')
    <x-container columns="2">
        <x-page-column class="col-md-9 main-panel">
            <x-tabs>
                <x-slot:tabnav>

                    @can('view', \App\Models\Asset::class)
                        <x-tabs.nav-item
                                class="active"
                                name="assets"
                                icon_type="asset"
                                label="{{ trans('general.assets') }}"
                                count="{{ $donor->assets()->AssetsForShow()->count() }}"
                                tooltip="{{ trans('general.assets') }}"
                        />
                    @endcan

                    @can('view', \App\Models\License::class)
                        <x-tabs.nav-item
                                name="licenses"
                                icon_type="licenses"
                                label="{{ trans('general.licenses') }}"
                                count="{{ $donor->licenses()->count() }}"
                                tooltip="{{ trans('general.licenses') }}"
                        />
                    @endcan

                    @can('view', \App\Models\Accessory::class)
                        <x-tabs.nav-item
                                name="accessories"
                                icon_type="accessories"
                                label="{{ trans('general.accessories') }}"
                                count="{{ $donor->accessories()->count() }}"
                                tooltip="{{ trans('general.accessories') }}"
                        />
                    @endcan

                    @can('view', \App\Models\Consumable::class)
                        <x-tabs.nav-item
                                name="consumables"
                                icon_type="consumables"
                                label="{{ trans('general.consumables') }}"
                                count="{{ $donor->consumables()->count() }}"
                                tooltip="{{ trans('general.consumables') }}"
                        />
                    @endcan

                    @can('view', \App\Models\Component::class)
                        <x-tabs.nav-item
                                name="components"
                                icon_type="components"
                                label="{{ trans('general.components') }}"
                                count="{{ $donor->components()->count() }}"
                                tooltip="{{ trans('general.components') }}"
                        />
                    @endcan

                    @can('update', $donor)
                        <x-tabs.nav-item-upload />
                    @endcan


                </x-slot:tabnav>

                <x-slot:tabpanes>

                    <!-- start assets tab pane -->
                    @can('view', \App\Models\Asset::class)
                        <x-tabs.pane name="assets" class="in active">
                            <x-slot:header>
                                {{ trans('general.assets') }}
                            </x-slot:header>

                            <x-slot:bulkactions>
                                <x-table.bulk-assets />
                            </x-slot:bulkactions>

                            <x-slot:content>
                                <x-table
                                        show_column_search="true"
                                        show_advanced_search="true"
                                        buttons="assetButtons"
                                        api_url="{{ route('api.assets.index', ['donor_id' => $donor->id]) }}"
                                        :presenter="\App\Presenters\AssetPresenter::dataTableLayout()"
                                        export_filename="export-donor-{{ str_slug($donor->name) }}-assets-{{ date('Y-m-d') }}"
                                />
                            </x-slot:content>
                        </x-tabs.pane>
                        <!-- end assets tab pane -->
                    @endcan


                    <!-- start licenses tab pane -->
                    @can('view', \App\Models\License::class)
                        <x-tabs.pane name="licenses">
                            <x-slot:header>
                                {{ trans('general.licenses') }}
                            </x-slot:header>
                            <x-slot:content>
                                <x-table
                                        name="licenses"
                                        buttons="licenseButtons"
                                        api_url="{{ route('api.licenses.index', ['donor_id' => $donor->id]) }}"
                                        :presenter="\App\Presenters\LicensePresenter::dataTableLayout()"
                                        export_filename="export-donor-{{ str_slug($donor->name) }}-licences-{{ date('Y-m-d') }}"
                                />
                            </x-slot:content>
                        </x-tabs.pane>
                    @endcan
                    <!-- end licenses tab pane -->


                    <!-- start accessory tab pane -->
                    @can('view', \App\Models\Accessory::class)
                        <x-tabs.pane name="accessories">
                            <x-slot:header>
                                {{ trans('general.accessories') }}
                            </x-slot:header>
                            <x-slot:content>
                                <x-table
                                        name="accessories"
                                        buttons="accessoryButtons"
                                        api_url="{{ route('api.accessories.index', ['donor_id' => $donor->id]) }}"
                                        :presenter="\App\Presenters\AccessoryPresenter::dataTableLayout()"
                                        export_filename="export-donor-{{ str_slug($donor->name) }}-accessories-{{ date('Y-m-d') }}"
                                />
                            </x-slot:content>
                        </x-tabs.pane>
                    @endcan
                    <!-- end accessory tab pane -->


                    <!-- start consumables tab pane -->
                    @can('view', \App\Models\Consumable::class)
                        <x-tabs.pane name="consumables">
                            <x-slot:header>
                                {{ trans('general.consumables') }}
                            </x-slot:header>
                            <x-slot:content>
                                <x-table
                                        name="consumables"
                                        buttons="consumableButtons"
                                        api_url="{{ route('api.consumables.index', ['donor_id' => $donor->id]) }}"
                                        :presenter="\App\Presenters\ConsumablePresenter::dataTableLayout()"
                                        export_filename="export-donor-{{ str_slug($donor->name) }}-consumables-{{ date('Y-m-d') }}"
                                />
                            </x-slot:content>
                        </x-tabs.pane>
                    @endcan
                    <!-- end components tab pane -->


                    <!-- start components tab pane -->
                    @can('view', \App\Models\Component::class)
                        <x-tabs.pane name="components">
                            <x-slot:header>
                                {{ trans('general.components') }}
                            </x-slot:header>
                            <x-slot:content>
                                <x-table
                                        name="components"
                                        buttons="componentButtons"
                                        api_url="{{ route('api.components.index', ['donor_id' => $donor->id]) }}"
                                        :presenter="\App\Presenters\ComponentPresenter::dataTableLayout()"
                                        export_filename="export-donor-{{ str_slug($donor->name) }}-components-{{ date('Y-m-d') }}"
                                />
                            </x-slot:content>
                        </x-tabs.pane>
                    @endcan
                    <!-- end components tab pane -->


                </x-slot:tabpanes>

            </x-tabs>

        </x-page-column>
        <x-page-column class="col-md-3">
            <x-box>
                <x-box.info-panel :infoPanelObj="$donor" img_path="{{ app('donors_upload_url') }}">

                    <x-slot:before_list>

                        <x-button.wide-edit :item="$donor" :route="route('donors.edit', $donor->id)" />
                        <x-button.wide-delete :item="$donor" />

                    </x-slot:before_list>


                </x-box.info-panel>
            </x-box>
        </x-page-column>
    </x-container>



@stop
@section('moar_scripts')
    @include ('partials.bootstrap-table')

@stop