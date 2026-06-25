@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{ $company->name }}
    @parent
@stop

@section('header_right')
    <x-button.info-panel-toggle/>
@endsection

{{-- Page content --}}
@section('content')
    <x-container columns="2">
        <x-page-column class="col-md-9 main-panel">
            <x-tabs>
                <x-slot:tabnav>
                    <x-tabs.user-tab count="{{ $tabCounts['users'] }}"/>
                    <x-tabs.asset-tab count="{{ $tabCounts['assets'] }}"/>
                    <x-tabs.license-tab count="{{ $tabCounts['licenses'] }}"/>
                    <x-tabs.accessory-tab count="{{ $tabCounts['accessories'] }}"/>
                    <x-tabs.consumable-tab count="{{ $tabCounts['consumables'] }}"/>
                    <x-tabs.component-tab count="{{ $tabCounts['components'] }}"/>
                    <x-tabs.files-tab :item="$company" count="{{ $company->uploads()->count() }}"/>
                    <x-tabs.upload-tab :item="$company"/>
                </x-slot:tabnav>

                <x-slot:tabpanes>
                    <!-- start users tab pane -->
                    <x-tabs.pane name="users">
                        <x-table.users name="users" :route="route('api.users.index', ['company_id' => $company->id, 'expand_company_hierarchy' => 1])"/>
                    </x-tabs.pane>
                    <!-- end users tab pane -->

                    <!-- start assets tab pane -->
                    <x-tabs.pane name="assets">
                        <x-table.assets name="assets" :route="route('api.assets.index', ['company_id' => $company->id, 'expand_company_hierarchy' => 1])"/>
                    </x-tabs.pane>
                    <!-- end assets tab pane -->

                    <!-- start licenses tab pane -->
                    <x-tabs.pane name="licenses">
                        <x-table.licenses name="licenses" :route="route('api.licenses.index', ['company_id' => $company->id, 'expand_company_hierarchy' => 1])"/>
                    </x-tabs.pane>
                    <!-- end licenses tab pane -->

                    <!-- start accessory tab pane -->
                    <x-tabs.pane name="accessories">
                        <x-table.accessories name="accessories" :route="route('api.accessories.index', ['company_id' => $company->id, 'expand_company_hierarchy' => 1])"/>
                    </x-tabs.pane>
                    <!-- end accessory tab pane -->

                    <!-- start consumables tab pane -->
                    <x-tabs.pane name="consumables">
                        <x-table.consumables name="consumables" :route="route('api.consumables.index', ['company_id' => $company->id, 'expand_company_hierarchy' => 1])"/>
                    </x-tabs.pane>
                    <!-- end components tab pane -->

                    <!-- start components tab pane -->
                    <x-tabs.pane name="components">
                        <x-table.components name="components" :route="route('api.components.index', ['company_id' => $company->id, 'expand_company_hierarchy' => 1])"/>
                    </x-tabs.pane>

                    <!-- start files tab pane -->
                    <x-tabs.pane name="files">
                        <x-table.files object_type="companies" :object="$company"/>
                    </x-tabs.pane>
                    <!-- end files tab pane -->

                </x-slot:tabpanes>

            </x-tabs>

        </x-page-column>
        <x-page-column class="col-md-3">
            <x-box class="side-box expanded">
                <x-info-panel :infoPanelObj="$company" img_path="{{ app('companies_upload_url') }}" :qr_code_url="route('qr_code/common', ['object_type' => 'companies', 'id' => $company->id])">

                    <x-slot:buttons>
                        <x-button.edit :item="$company" :route="route('companies.edit', $company->id)" />
                        <x-button.delete :item="$company" />
                    </x-slot:buttons>

                </x-info-panel>
            </x-box>

            @if ($company->parent || $company->children->isNotEmpty())
                <x-box>
                    <h2 class="box-title" style="font-size: 16px; margin-bottom: 12px;">{{ trans('admin/companies/table.hierarchy') }}</h2>

                    @if ($company->parent)
                        <p style="margin-bottom: 12px;">
                            <strong>{{ trans('admin/companies/table.parent') }}:</strong><br>
                            <a href="{{ route('companies.show', $company->parent) }}">{{ $company->parent->name }}</a>
                        </p>
                    @endif

                    @if ($company->children->isNotEmpty())
                        <p style="margin-bottom: 4px;"><strong>{{ trans('admin/companies/table.children') }}:</strong></p>
                        <ul style="padding-left: 20px; margin-bottom: 0;">
                            @foreach ($company->children->sortBy('name') as $child)
                                <li><a href="{{ route('companies.show', $child) }}">{{ $child->name }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </x-box>
            @endif
        </x-page-column>
    </x-container>

@endsection

@section('moar_scripts')
    @can('files', $company)
        @include ('modals.upload-file', ['item_type' => 'companies', 'item_id' => $company->id])
    @endcan
    <script>
        // Bootstrap-table formatters read this to decorate rows whose company
        // doesn't match the company being viewed (i.e. inherited from parent
        // or child via the FMCS hierarchy expansion). See companiesLinkObj
        // Formatter and companiesArrayLinkFormatter in partials/bootstrap-table.
        window.viewingCompanyId = {{ (int) $company->id }};
    </script>
    @include ('partials.bootstrap-table')
@endsection

