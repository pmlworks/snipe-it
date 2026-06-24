@extends('layouts/default')

{{-- Page title --}}
@section('title')
  {{ trans('general.companies') }}
  @parent
@stop

{{-- Page content --}}
@section('content')
    <x-container>
            <x-box>
                <table
                  data-columns="{{ \App\Presenters\CompanyPresenter::dataTableLayout() }}"
                  data-cookie-id-table="companiesTable"
                  data-id-table="companiesTable"
                  data-side-pagination="server"
                  data-sort-order="asc"
                  data-advanced-search="false"
                  id="companiesTable"
                  data-buttons="companyButtons"
                  class="table table-striped snipe-table"
                  data-url="{{ route('api.companies.index') }}"
                  data-export-options='{
                            "fileName": "export-companies-{{ date('Y-m-d') }}",
                            "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                            }'>
                </table>
            </x-box>
    </x-container>
@stop

@section('moar_scripts')
  @include ('partials.bootstrap-table')
@stop
