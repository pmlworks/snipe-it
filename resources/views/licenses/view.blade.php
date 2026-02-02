@extends('layouts/default')

{{-- Page title --}}
@section('title')
  {{ trans('admin/licenses/general.view') }}
  - {{ $license->name }}
  @parent
@stop

{{-- Page content --}}
@section('content')
    <x-container columns="2">
        <x-page-column class="col-md-9">
            <x-tabs>
                <x-slot:tabnav>

                    <x-tabs.nav-item
                            name="info"
                            class="active"
                            icon="fas fa-info-circle"
                            label="{{ trans('admin/users/general.info') }}"
                    />

                    <x-tabs.nav-item
                            name="seats"
                            icon="far fa-list-alt"
                            label="{{ trans('general.assigned') }}"
                            count="{{ $license->assignedCount()->count() }}"
                    />

                    <x-tabs.nav-item
                            name="available"
                            icon="far fa-list"
                            label="{{ trans('general.available') }}"
                            count="{{ $license->availCount()->count() }}"
                    />

                <x-tabs.nav-item
                        name="files"
                        icon="fa-solid fa-file-contract fa-fw"
                        label="{{ trans('general.files') }}"
                        count="{{ $license->uploads()->count() }}"
                />


                <x-tabs.nav-item
                        name="history"
                        icon="fa-solid fa-clock-rotate-left fa-fw"
                        label="{{ trans('general.history') }}"
                        tooltip="{{ trans('general.history') }}"
                />


                @can('update', $license)
                    <x-tabs.nav-item-upload />
                @endcan
                </x-slot:tabnav>

                <x-slot:tabpanes>
                    <!-- start info tab pane -->
                        <x-tabs.pane name="info" class="in active">

                            <x-slot:content>

                                <div class="container row-new-striped">

                                    @if (!is_null($license->serial))
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>{{ trans('admin/licenses/form.license_key') }}</strong>
                                            </div>
                                            <div class="col-md-9">
                                                @can('viewKeys', $license)

                                                    <code>
                                                        <x-copy-to-clipboard copy_what="license_key">
                                                            {!! nl2br(e($license->serial)) !!}
                                                        </x-copy-to-clipboard>
                                                    </code>
                                                @else
                                                    ------------
                                                @endcan
                                            </div>
                                        </div>
                                    @endif


                                    @if ($license->license_name!='')
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>{{ trans('admin/licenses/form.to_name') }}</strong>
                                            </div>
                                            <div class="col-md-9">
                                                {{ $license->license_name }}
                                            </div>
                                        </div>
                                    @endif

                                    @if ($license->license_email!='')
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/licenses/form.to_email') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {{ $license->license_email }}
                                            </div>
                                        </div>
                                    @endif



                                @if (isset($license->expiration_date))
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/licenses/form.expiration') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                @if ($license->isExpired())
                                                    <span class="text-danger">
                                                       <x-icon type="warning" class="text-warning" />
                                                      </span>
                                                @endif
                                                {{ Helper::getFormattedDateObject($license->expiration_date, 'date', false) }}
                                            </div>
                                        </div>
                                    @endif

                                        @if ($license->termination_date)
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>
                                                        {{ trans('admin/licenses/form.termination_date') }}
                                                    </strong>
                                                </div>
                                                <div class="col-md-9">
                                                    @if ($license->isTerminated())
                                                        <span class="text-danger">
                           <x-icon type="warning" class="text-warning" />
                          </span>
                                                    @endif

                                                    {{ Helper::getFormattedDateObject($license->termination_date, 'date', false) }}
                                                </div>
                                            </div>
                                        @endif



                                    @if ($license->purchase_cost > 0)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('general.purchase_cost') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {{ $snipeSettings->default_currency }}
                                                {{ Helper::formatCurrencyOutput($license->purchase_cost) }}
                                            </div>
                                        </div>
                                    @endif


                                    @if ($license->purchase_order)
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/licenses/form.purchase_order') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {{ $license->purchase_order }}
                                            </div>
                                        </div>
                                    @endif

                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>
                                                {{ trans('admin/licenses/form.maintained') }}
                                            </strong>
                                        </div>
                                        <div class="col-md-9">
                                            {!! $license->maintained ? '<i class="fas fa-check fa-fw text-success" aria-hidden="true"></i> '.trans('general.yes') : '<i class="fas fa-times fa-fw text-danger" aria-hidden="true"></i> '.trans('general.no') !!}
                                        </div>
                                    </div>

                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>
                                                    {{ trans('admin/licenses/form.reassignable') }}
                                                </strong>
                                            </div>
                                            <div class="col-md-9">
                                                {!! $license->reassignable ? '<i class="fas fa-check fa-fw text-success" aria-hidden="true"></i> '.trans('general.yes') : '<i class="fas fa-times fa-fw text-danger" aria-hidden="true"></i> '.trans('general.no') !!}
                                            </div>
                                        </div>





                                </div>

                            </x-slot:content>
                        </x-tabs.pane>
                    <!-- end info tab pane -->

                    <x-tabs.pane name="seats">
                        <x-slot:header>
                            {{ trans('general.assigned') }}
                        </x-slot:header>
                        <x-slot:content>

                            <table
                                    data-columns="{{ \App\Presenters\LicensePresenter::dataTableLayoutSeats() }}"
                                    data-cookie-id-table="seatsTable"
                                    data-id-table="seatsTable"
                                    id="seatsTable"
                                    data-search="false"
                                    data-side-pagination="server"
                                    data-sort-order="asc"
                                    data-sort-name="name"
                                    class="table table-striped snipe-table"
                                    data-url="{{ route('api.licenses.seats.index', [$license->id, 'status' => 'assigned']) }}"
                                    data-export-options='{
                        "fileName": "export-seats-{{ str_slug($license->name) }}-{{ date('Y-m-d') }}",
                        "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                        }'>
                            </table>

                        </x-slot:content>
                    </x-tabs.pane>


                    <x-tabs.pane name="available">
                        <x-slot:header>
                            {{ trans('general.available') }}
                        </x-slot:header>
                        <x-slot:content>
                            <table
                                    data-columns="{{ \App\Presenters\LicensePresenter::dataTableLayoutSeats() }}"
                                    data-cookie-id-table="availableSeatsTable"
                                    data-id-table="availableSeatsTable"
                                    id="availableSeatsTable"
                                    data-search="false"
                                    data-side-pagination="server"
                                    data-sort-order="asc"
                                    data-sort-name="name"
                                    class="table table-striped snipe-table"
                                    data-url="{{ route('api.licenses.seats.index', [$license->id, 'status' => 'available']) }}"
                                    data-export-options='{
                        "fileName": "export-available-seats-{{ str_slug($license->name) }}-{{ date('Y-m-d') }}",
                        "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                        }'>
                            </table>

                        </x-slot:content>
                    </x-tabs.pane>


                    <!-- start history tab pane -->
                    <x-tabs.pane name="history">
                        <x-slot:header>
                            {{ trans('general.history') }}
                        </x-slot:header>
                        <x-slot:content>
                            <x-table
                                    name="locationHistory"
                                    api_url="{{ route('api.activity.index', ['target_id' => $license->id, 'target_type' => 'license']) }}"
                                    :presenter="\App\Presenters\HistoryPresenter::dataTableLayout()"
                                    export_filename="export-licenses-{{ str_slug($license->name) }}-{{ date('Y-m-d') }}"
                            />
                        </x-slot:content>
                    </x-tabs.pane>
                    <!-- end history tab pane -->


                    <!-- start files tab pane -->
                    @can('licenses.files', $license)
                    <x-tabs.pane name="files">
                        <x-slot:header>
                            {{ trans('general.files') }}
                        </x-slot:header>
                        <x-slot:content>
                            <x-filestable object_type="licenses" :object="$license" />
                        </x-slot:content>
                    </x-tabs.pane>
                    @endcan
                    <!-- end files tab pane -->

                </x-slot:tabpanes>
            </x-tabs>
        </x-page-column>

        <x-page-column class="col-md-3">
            <x-box>
                <x-box.contact :contact="$license" img_path="{{ app('licenses_upload_url') }}">

                    <x-slot:before_list>

                        @can('update', $license)
                            <a href="{{ route('licenses.edit', $license->id) }}" class="btn btn-warning btn-sm btn-social btn-block hidden-print" style="margin-bottom: 5px;">
                                <x-icon type="edit" />
                                {{ trans('admin/licenses/general.edit') }}
                            </a>
                            <a href="{{ route('clone/license', $license->id) }}" class="btn btn-info btn-block btn-sm btn-social hidden-print" style="margin-bottom: 5px;">
                                <x-icon type="clone" />
                                {{ trans('admin/licenses/general.clone') }}</a>
                        @endcan

                        @can('checkout', $license)

                            @if (($license->availCount()->count() > 0) && (!$license->isInactive()))

                                <a href="{{ route('licenses.checkout', $license->id) }}" class="btn bg-maroon btn-sm btn-social btn-block hidden-print" style="margin-bottom: 5px;">
                                    <x-icon type="checkout" />
                                    {{ trans('general.checkout') }}
                                </a>

                                <a href="#" class="btn bg-maroon btn-sm btn-social btn-block hidden-print" style="margin-bottom: 5px;" data-toggle="modal" data-tooltip="true" title="{{ trans('admin/licenses/general.bulk.checkout_all.enabled_tooltip') }}" data-target="#checkoutFromAllModal">
                                    <x-icon type="checkout" />
                                    {{ trans('admin/licenses/general.bulk.checkout_all.button') }}
                                </a>

                            @else
                                <span data-tooltip="true" title="{{ ($license->availCount()->count() == 0) ? trans('admin/licenses/general.bulk.checkout_all.disabled_tooltip') : trans('admin/licenses/message.checkout.license_is_inactive') }}" class="btn bg-maroon btn-sm btn-social btn-block hidden-print disabled" style="margin-bottom: 5px;" data-tooltip="true" title="{{ trans('general.checkout') }}">
                                    <x-icon type="checkout" />
                                    {{ trans('general.checkout') }}
                                  </span>
                                                        <span data-tooltip="true" title="{{ ($license->availCount()->count() == 0) ? trans('admin/licenses/general.bulk.checkout_all.disabled_tooltip') : trans('admin/licenses/message.checkout.license_is_inactive') }}" class="btn bg-maroon btn-sm btn-social btn-block hidden-print disabled" style="margin-bottom: 5px;" data-tooltip="true" title="{{ trans('general.checkout') }}">
                                      <x-icon type="checkout" />
                                      {{ trans('admin/licenses/general.bulk.checkout_all.button') }}
                                  </span>
                            @endif
                        @endcan

                            @can('checkin', $license)

                                @if (($license->seats - $license->availCount()->count()) <= 0 )
                                    <span data-tooltip="true" title=" {{ trans('admin/licenses/general.bulk.checkin_all.disabled_tooltip') }}">
                                        <a href="#"  class="btn btn-primary bg-purple btn-sm btn-social btn-block hidden-print disabled"  style="margin-bottom: 25px;">
                                          <x-icon type="checkin" />
                                         {{ trans('admin/licenses/general.bulk.checkin_all.button') }}
                                        </a>
                                    </span>
                                @else
                                    <a href="#"  class="btn btn-primary bg-purple btn-sm btn-social btn-block hidden-print" style="margin-bottom: 25px;" data-toggle="modal" data-tooltip="true"  data-target="#checkinFromAllModal" data-content="{{ trans('general.sure_to_delete') }} data-title="{{  trans('general.delete') }}" onClick="return false;">
                                    <x-icon type="checkin" />
                                    {{ trans('admin/licenses/general.bulk.checkin_all.button') }}
                                    </a>
                                @endif
                            @endcan

                            @can('delete', $license)

                                @if ($license->availCount()->count() == $license->seats)
                                    <a class="btn btn-block btn-danger btn-sm btn-social delete-asset" data-icon="fa fa trash" data-toggle="modal" data-title="{{ trans('general.delete') }}" data-content="{{ trans('general.delete_confirm', ['item' => $license->name]) }}" data-target="#dataConfirmModal" onClick="return false;">
                                        <x-icon type="delete" />
                                        {{ trans('general.delete') }}
                                    </a>
                                @else
                                    <span data-tooltip="true" title=" {{ trans('admin/licenses/general.delete_disabled') }}">
            <a href="#" class="btn btn-block btn-danger btn-sm btn-social delete-asset disabled" onClick="return false;">
              <x-icon type="delete" />
              {{ trans('general.delete') }}
            </a>
          </span>
                                @endif
                            @endcan




                    </x-slot:before_list>
                </x-box.contact>
            </x-box>

        </x-page-column>
    </x-container>



  @can('update', \App\Models\License::class)
    @include ('modals.upload-file', ['item_type' => 'license', 'item_id' => $license->id])
  @endcan

@stop


@section('moar_scripts')
  @include ('partials.bootstrap-table')
@stop
