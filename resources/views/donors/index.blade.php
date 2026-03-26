@extends('layouts/default')

@section('title')
  {{ trans('general.donors') }}
  @parent
@stop

@section('content')
    <x-container columns="2">

        <x-page-column class="col-md-9">
            <x-box>
                <table
                  data-columns="{{ \App\Presenters\DonorPresenter::dataTableLayout() }}"
                  data-cookie-id-table="donorsTable"
                  data-id-table="donorsTable"
                  data-side-pagination="server"
                  data-sort-order="asc"
                  data-advanced-search="false"
                  id="donorsTable"
                  data-buttons="donorButtons"
                  class="table table-striped snipe-table"
                  data-url="{{ route('api.donors.index') }}"
                  data-export-options='{
                            "fileName": "export-donors-{{ date('Y-m-d') }}",
                            "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                            }'>
                </table>
            </x-box>
        </x-page-column>


        <!-- side address column -->
        <x-page-column class="col-md-3">
          <h2>{{ trans('admin/donors/general.about_donors') }}</h2>
          <p>{{ trans('admin/donors/general.about_donors_description') }}</p>
        </x-page-column>
    </x-container>
@stop

@section('moar_scripts')
  @include ('partials.bootstrap-table')
@stop