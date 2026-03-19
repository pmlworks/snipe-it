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
            clear: left;
            float: left;
            text-align: right;
            width: 20%;
            margin: 0;
            padding: 10px;
            border-top: var(--tab-bottom-border);
            font-weight: bold;
        }

        .table-display dd {
            float: left;
            width: 80%;
            margin: 0;
            padding: 10px;
            border-top: var(--tab-bottom-border);
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
                    <x-tabs.maintenance-tab count="{{ $asset->maintenances->count() }}"/>
                    <x-tabs.files-tab count="{{ $asset->uploads()->count() }}"/>
                    <x-tabs.model-files-tab count="{{ $asset->model->uploads()->count() }}"/>
                    <x-tabs.history-tab model="\App\Models\Asset::class"/>
                    <x-tabs.upload-tab :item="$asset"/>
                </x-slot:tabnav>

                <x-slot:tabpanes>
                    <x-tabs.pane name="details">

                        <x-page-data>
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
                                    @if ($audit_log->user)
                                        -
                                        <a href="{{ route('users.show', $audit_log->user->id) }}">{{ $audit_log->user->display_name }}</a>
                                    @endif
                                @endif
                            </x-data-row>

                            <x-data-row :label="trans('general.next_audit_date')" copy_what="next_audit_date">
                                {!! $asset->checkInvalidNextAuditDate() ? '<i class="fas fa-exclamation-triangle text-orange" aria-hidden="true"></i>' : '' !!}
                                {{ Helper::getFormattedDateObject($asset->next_audit_date, 'date', false) }}
                            </x-data-row>
                        </x-page-data>
                    </x-tabs.pane>

                    <x-tabs.pane name="licenses">
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
            @include ('modals.upload-file', ['item_type' => 'asset', 'item_id' => $asset->id])
        @endcan
    @stop
                @section('moar_scripts')
        @include ('partials.bootstrap-table')

    @stop
