@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{ trans('admin/hardware/general.view') }} {{ $asset->asset_tag }}
    @parent
@stop

@section('header_right')
    <x-button.info-panel-toggle/>
@endsection

{{-- Page content --}}
@section('content')

    <style>
        .main-panel-content {
            line-height: 20px;
            border-bottom: var(--tab-bottom-border);
            padding: 10px 15px;
        }




        /* table */

        dl.table-display {
            float: left;
            width: 100%;
            margin: 1em 0;
            padding: 0;
        }

        .table-display dt {
            line-height: 25px;
            clear: left;
            float: left;
            text-align: right;
            width: 20%;
            margin: 0;
            padding: 10px;
            /*border-top: var(--tab-bottom-border);*/
            font-weight: bold;
        }

        .table-display dd {
            line-height: 25px;
            float: left;
            width: 80%;
            margin: 0;
            padding: 10px;
            /*border-top: var(--tab-bottom-border);*/
        }


        .table-display dd:first-of-type, .table-display dt:first-of-type {
            border-top: 0 !important;
        }

    </style>
    <x-container columns="2">
        <x-page-column class="col-md-9 main-panel">

            <x-tabs>
                <x-slot:tabnav>
                    <x-tabs.details-tab/>
                    <x-tabs.license-tab count="{{ $asset->licenses->count() }}"/>
                    <x-tabs.component-tab count="{{ $asset->components->count() }}"/>
                    <x-tabs.asset-tab count="{{ $asset->assignedAssets()->AssetsForShow()->count() }}"/>
                    <x-tabs.accessory-tab count="{{ $asset->assignedAccessories()->count() }}"/>
                    <x-tabs.maintenance-tab count="{{ $asset->maintenances->count() }}"/>
                    <x-tabs.files-tab count="{{ $asset->uploads()->count() }}"/>
                    <x-tabs.model-files-tab count="{{ $asset->model?->uploads()->count() }}"/>
                    <x-tabs.history-tab count="{{ $asset->assetlog()->count() }}" model=" \App\Models\Asset::class"/>
                    <x-tabs.upload-tab :item="$asset"/>
                </x-slot:tabnav>

                <x-slot:tabpanes>
                    <x-tabs.pane name="details">
                        <x-page-data>

                            <div class="row">

                                <x-progressbar text="Device EOL" :percent="Carbon::parse($asset->asset_eol_date)->diffInMonths($asset->purchase_date, true)">
                                    <strong>{{ (int) Carbon::now()->diffInMonths($asset->asset_eol_date, true) }}</strong>/{{ $asset->model->eol }} {{ trans('general.months') }}
                                </x-progressbar>

                                <x-progressbar :text="trans('admin/hardware/form.fully_depreciated')" :percent="Carbon::now()->diffInMonths($asset->depreciated_date()->format('Y-m-d'), true)">
                                    {{ Helper::getFormattedDateObject($asset->depreciated_date()->format('Y-m-d'), 'date', false) }}
                                </x-progressbar>

                                <x-progressbar :text="trans('admin/hardware/form.warranty_expires')" :percent="Carbon::now()->diffInMonths($asset->warranty_expires, true)">
                                    {{ Helper::getFormattedDateObject($asset->warranty_expires, 'date', false) }}
                                </x-progressbar>


                                <div class="col-md-4">
                                    <div class="col-md-12 well">
                                        <x-info-element.status :infoObject="$asset"/>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="col-md-12 well">
                                        <x-icon type="cost" class="fa-fw"/>
                                        <strong>{{ trans('admin/hardware/table.current_value') }}</strong>
                                            @if (($asset->id) && ($asset->location))
                                                {{ $asset->location->currency }}
                                            @elseif (($asset->id) && ($asset->location))
                                                {{ $asset->location->currency }}
                                            @else
                                                {{ $snipeSettings->default_currency }}
                                            @endif
                                            {{ Helper::formatCurrencyOutput($asset->getDepreciatedValue() )}}
                                    </div>
                                </div>


                                <div class="col-md-4">
                                    <div class="col-md-12 well">
                                        <x-icon type="calendar" class="fa-fw"/>
                                        <strong>{{ trans('general.expected_checkin') }}</strong>
                                        @if ($asset->expected_checkin!='')
                                            {{ Helper::getFormattedDateObject($asset->expected_checkin, 'date', false) }}
                                            <span class="text-muted">{{ Carbon::parse($asset->expected_checkin)->diffForHumans(['parts' => 2]) }}</span>
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                </div>


                                <div class="col-md-3 col-sm-6">
                                    <div class="col-md-12 well well-sm">
                                        <x-icon type="maintenances" class="fa-fw"/>
                                        <strong>Active Maintenances</strong>
                                        {{ $asset->maintenances->whereNull('completion_date')->count() }}
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <div class="col-md-12 well well-sm">
                                        <x-icon type="checkout" class="fa-fw"/>
                                        <strong>{{ trans('general.checkouts_count') }}</strong>
                                        {{ ($asset->checkouts) ? (int) $asset->checkouts->count() : '0' }}
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <div class="col-md-12 well well-sm">
                                        <x-icon type="checkin" class="fa-fw"/>
                                        <strong>{{ trans('general.checkins_count') }}</strong>
                                        {{ ($asset->checkins) ? (int) $asset->checkins->count() : '0' }}
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <div class="col-md-12 well well-sm">
                                        <x-icon type="request" class="fa-fw"/>
                                        <strong> {{ trans('general.user_requests_count') }}</strong>
                                        {{ ($asset->userRequests) ? (int) $asset->userRequests->count() : '0' }}
                                    </div>
                                </div>
                            </div>


                            <x-data-row :label="trans('admin/hardware/form.tag')" copy_what="asset_tag">
                                {{ $asset->asset_tag }}
                            </x-data-row>


                            <x-data-row :label="trans('admin/hardware/form.name')" copy_what="asset_name">
                                {{ $asset->name }}
                            </x-data-row>

                            <x-data-row :label="trans('general.last_audit')" copy_what="audit_date">
                                @if ((isset($audit_log)) && ($audit_log->created_at))
                                    {!! $asset->checkInvalidNextAuditDate() ? '<i class="fas fa-exclamation-triangle text-orange" aria-hidden="true"></i>' : '' !!}
                                    {{ Helper::getFormattedDateObject($audit_log->created_at, 'datetime', false) }}
                                    <span class="text-muted">{{ Carbon::parse($audit_log->created_at)->diffForHumans(['parts' => 2]) }}</span>
                                    @if ($audit_log->user)
                                        -
                                        <a href="{{ route('users.show', $audit_log->user->id) }}">{{ $audit_log->user->display_name }}</a>
                                    @endif
                                @endif
                            </x-data-row>

                            <x-data-row :label="trans('general.next_audit_date')" copy_what="next_audit_date">
                                {!! $asset->checkInvalidNextAuditDate() ? '<i class="fas fa-exclamation-triangle text-orange" aria-hidden="true"></i>' : '' !!}
                                {{ Helper::getFormattedDateObject($asset->next_audit_date, 'date', false) }}
                                <span class="text-muted">{{ Carbon::parse($asset->next_audit_date)->diffForHumans(['parts' => 2]) }}</span>
                            </x-data-row>


                            @if (($asset->model) && ($asset->model->fieldset))
                                @foreach($asset->model->fieldset->fields as $field)

                                    <x-data-row :label="$field->name">
                                        <x-info-element.customfield :item="$asset" :field="$field"/>
                                    </x-data-row>

                                @endforeach
                            @endif

                            @if ($asset->defaultLoc)
                                <x-data-row :label="trans('admin/hardware/form.default_location')" copy_what="default_location">
                                    {!!  $asset->defaultLoc->present()->formattedNameLink !!}
                                </x-data-row>
                            @endif

                            @if($asset->expected_checkin!='')
                            <x-data-row :label="trans('general.expected_checkin')" copy_what="expected_checkin">
                                {{ Helper::getFormattedDateObject($asset->expected_checkin, 'date', false) }}
                                <span class="text-muted">{{ Carbon::parse($asset->expected_checkin)->diffForHumans(['parts' => 2]) }}</span>
                            </x-data-row>
                            @endif

                            @if($asset->assetlog->where('action_type', 'note added')->last()->note!='')
                                <x-data-row :label="trans('general.last_note')" copy_what="last_note">
                                    {{ $asset->assetlog->where('action_type', 'note added')->last()->note }}
                                </x-data-row>
                            @endif

                            @if (($asset->asset_eol_date) && ($asset->purchase_date))
                                <x-data-row :label="trans('admin/hardware/form.eol_rate')" copy_what="eol_rate">
                                    {{ (int) Carbon::parse($asset->asset_eol_date)->diffInMonths($asset->purchase_date, true) }}
                                    {{ trans('admin/hardware/form.months') }}
                                </x-data-row>
                            @endif

                            @if ($asset->asset_eol_date)
                                <x-data-row :label="trans('admin/hardware/form.eol_date')" copy_what="eol_date">
                                    @if ($asset->asset_eol_date)
                                        {{ Helper::getFormattedDateObject($asset->asset_eol_date, 'date', false) }}
                                        -
                                        <span class="text-muted">{{ Carbon::parse($asset->asset_eol_date)->locale(app()->getLocale())->diffForHumans(['parts' => 3]) }}</span>
                                    @else
                                        {{ trans('general.na_no_purchase_date') }}
                                    @endif
                                    @if ($asset->eol_explicit =='1')
                                            <span data-tooltip="true" data-placement="top" data-title="Explicit EOL" title="Explicit EOL">
                                            <x-icon type="warning" class="text-primary"/>
                                        </span>
                                    @endif
                                </x-data-row>
                            @endif




                        </x-page-data>
                    </x-tabs.pane>

                    <x-tabs.pane name="licenses">
                        Licenses
                    </x-tabs.pane>
                    <x-tabs.pane name="components">
                        Components
                    </x-tabs.pane>
                    <x-tabs.pane name="assets">
                        Assets
                    </x-tabs.pane>

                    <x-tabs.pane name="accessories">
                        <x-slot:table_header>
                            {{ trans('general.accessories_assigned') }}
                        </x-slot:table_header>

                        <x-table
                            name="assetAccessories_{{ $asset->id }}"
                            api_url="{{ route('api.assets.assigned_accessories', ['asset' => $asset]) }}"
                            :presenter="\App\Presenters\AssetPresenter::assignedAccessoriesDataTableLayout()"
                            export_filename="export-maintenances-{{ str_slug($asset->name) }}-{{ date('Y-m-d') }}"
                        />
                    </x-tabs.pane>
                    <x-tabs.pane name="audits">
                        Audits
                    </x-tabs.pane>

                    <!-- start history tab pane -->
                    <x-tabs.pane name="history">

                        <x-slot:table_header>
                            {{ trans('general.history') }}
                        </x-slot:table_header>

                        <x-table
                            name="assetHistory_{{ $asset->id }}"
                            api_url="{{ route('api.activity.index', ['item_id' => $asset->id, 'item_type' => 'asset']) }}"
                            :presenter="\App\Presenters\HistoryPresenter::dataTableLayout()"
                            export_filename="export-history-{{ str_slug($asset->name) }}-{{ date('Y-m-d') }}"
                        />

                    </x-tabs.pane>
                    <!-- end history tab pane -->

                    <!-- start maintenances tab pane -->
                    <x-tabs.pane name="maintenances">

                        <x-slot:table_header>
                            {{ trans('general.maintenances') }}
                        </x-slot:table_header>

                        <x-table
                            name="assetMaintenances_{{ $asset->id }}"
                            api_url="{{ route('api.maintenances.index', array('asset_id' => $asset->id)) }}"
                            :presenter="\App\Presenters\MaintenancesPresenter::dataTableLayout()"
                            export_filename="export-maintenances-{{ str_slug($asset->name) }}-{{ date('Y-m-d') }}"
                        />
                    </x-tabs.pane>
                    <!-- end maintenances tab pane -->

                    <x-tabs.pane name="files">
                        <x-table.files object_type="assets" :object="$asset"/>
                    </x-tabs.pane>

                    <x-tabs.pane name="model-files">
                        Model Files
                    </x-tabs.pane>


                </x-slot:tabpanes>

            </x-tabs>

        </x-page-column>

        <x-page-column class="col-md-3">
            <x-box class="side-box expanded">
                <x-box.info-panel :infoPanelObj="$asset" img_path="{{ app('assets_upload_url') }}">
                    <x-slot:buttons>
                        <x-button.checkout permission="checkout" :item="$asset" :route="route('hardware.checkout.create', $asset->id)"/>
                        <x-button.edit :item="$asset" :route="route('hardware.edit', $asset->id)"/>
                        <x-button.clone :item="$asset" :route="route('clone/hardware', $asset->id)"/>
                        <x-button.note :item="$asset" :route="route('clone/hardware', $asset->id)"/>
                        <x-button.audit :item="$asset" :route="route('asset.audit.create', $asset->id)"/>
                        <x-button.label :item="$asset" :route="route('restore/hardware', ['asset' => $asset->id])"/>
                        <x-button.delete :item="$asset"/>
                        <x-button.restore :item="$asset" :route="route('restore/hardware', ['asset' => $asset->id])"/>
                    </x-slot:buttons>
                </x-box.info-panel>
            </x-box>

        </x-page-column>

    </x-container>



    @can('update', \App\Models\Asset::class)
        @include ('modals.add-note', ['type' => 'asset', 'id' => $asset->id])
        @include ('modals.upload-file', ['item_type' => 'asset', 'item_id' => $asset->id])
    @endcan
    @stop
                @section('moar_scripts')
        @include ('partials.bootstrap-table')

    @stop
