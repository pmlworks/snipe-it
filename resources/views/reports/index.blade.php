@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{ trans('general.reports') }}
    @parent
@stop

{{-- Page content --}}
@section('content')

    {{-- Row 1: Stat Alert Cards --}}
    <div class="row">

        <div class="col-lg-3 col-sm-6">
            <a href="{{ route('reports.audit') }}">
                <div class="info-box">
                    <span class="info-box-icon {{ $audit_alert_count > 0 ? 'bg-red' : 'bg-green' }}" aria-hidden="true">
                        <x-icon type="audit" />
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ trans('general.audit_due') }} / {{ trans('general.audit_overdue') }}</span>
                        <span class="info-box-number">{{ number_format($audit_alert_count) }}</span>
                        <div class="progress">
                            <div class="progress-bar progress-bar-info" id="progress-audit" style="width: 0%"></div>
                        </div>
                        <span class="info-box-more" id="progress-audit-label">&nbsp;</span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-sm-6">
            <a href="{{ route('hardware.index') }}">
                <div class="info-box">
                    <span class="info-box-icon {{ $checkin_alert_count > 0 ? 'bg-red' : 'bg-green' }}" aria-hidden="true">
                        <x-icon type="assets" />
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ trans('general.checkin_due') }} / {{ trans('general.checkin_overdue') }}</span>
                        <span class="info-box-number">{{ number_format($checkin_alert_count) }}</span>
                        <div class="progress">
                            <div class="progress-bar progress-bar-info" id="progress-checkin" style="width: 0%"></div>
                        </div>
                        <span class="info-box-more" id="progress-checkin-label">&nbsp;</span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-sm-6">
            <a href="{{ route('reports/unaccepted_assets') }}">
                <div class="info-box">
                    <span class="info-box-icon {{ $pending_acceptance_count > 0 ? 'bg-yellow' : 'bg-green' }}" aria-hidden="true">
                        <x-icon type="assets" />
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ trans('general.unaccepted_asset_report') }}</span>
                        <span class="info-box-number">{{ number_format($pending_acceptance_count) }}</span>
                        <div class="progress">
                            <div class="progress-bar progress-bar-info" id="progress-acceptance" style="width: 0%"></div>
                        </div>
                        <span class="info-box-more" id="progress-acceptance-label">&nbsp;</span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-sm-6">
            <a href="{{ url('reports/licenses') }}">
                <div class="info-box">
                    <span class="info-box-icon {{ $licenses_low_count > 0 ? 'bg-red' : 'bg-green' }}" aria-hidden="true">
                        <x-icon type="licenses" />
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ trans('general.licenses_with_no_seats') }}</span>
                        <span class="info-box-number">{{ number_format($licenses_low_count) }}</span>
                        <div class="progress">
                            <div class="progress-bar progress-bar-info" id="progress-licenses" style="width: 0%"></div>
                        </div>
                        <span class="info-box-more" id="progress-licenses-label">&nbsp;</span>
                    </div>
                </div>
            </a>
        </div>

    </div>


    {{-- Date Range Control --}}
    <div class="row">
        <div class="col-md-12">
            <div class="well well-sm" style="display:flex; align-items:center; gap:8px; margin-bottom:15px;">
                <label style="margin:0; font-weight:bold; white-space:nowrap;">{{ trans('general.time_range') }}:</label>
                <select id="chartTimeRange" class="form-control input-sm" style="width:auto;">
                    <option value="7">{{ trans('general.last_7_days') }}</option>
                    <option value="14">{{ trans('general.last_14_days') }}</option>
                    <option value="30" selected>{{ trans('general.last_30_days') }}</option>
                    <option value="60">{{ trans('general.last_60_days') }}</option>
                    <option value="90">{{ trans('general.last_90_days') }}</option>
                    <option value="180">{{ trans('general.last_180_days') }}</option>
                    <option value="365">{{ trans('general.last_365_days') }}</option>
                    <option value="custom">{{ trans('general.custom_range') }}…</option>
                </select>
                <div id="customRangePicker" class="input-daterange input-group" style="display:none; width:auto;">
                    <input type="text" id="chartStartDate" class="form-control input-sm" placeholder="{{ trans('general.select_date') }}" style="width:110px;" autocomplete="off">
                    <span class="input-group-addon">–</span>
                    <input type="text" id="chartEndDate" class="form-control input-sm" placeholder="{{ trans('general.select_date') }}" style="width:110px;" autocomplete="off">
                </div>
            </div>
        </div>
    </div>

    {{-- Assets Box --}}
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('general.assets') }}</h2>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse" aria-hidden="true"><x-icon type="minus" /></button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="well">
                                <h4>{!! trans('general.checkouts_checkins') !!}</h4>
                                <div style="position:relative; height:160px;">
                                    <canvas id="chart-asset-checkouts"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="well">
                                <h4>{!! trans('general.new_assets_created') !!}</h4>
                                <div style="position:relative; height:160px;">
                                    <canvas id="chart-assets"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="well">
                                <h4>{!! trans('general.new_maintenances_created') !!}</h4>
                                <div style="position:relative; height:160px;">
                                    <canvas id="chart-maintenances"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="well">
                                <h4>{!! trans('general.new_audits_created') !!}</h4>
                                <div style="position:relative; height:160px;">
                                    <canvas id="chart-audits"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Components & Consumables --}}
    <div class="row">
        <div class="col-md-6">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('general.components') }}</h2>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse" aria-hidden="true"><x-icon type="minus" /></button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="well">
                        <h4>{!! trans('general.checkouts_checkins') !!}</h4>
                        <div style="position:relative; height:160px;">
                            <canvas id="chart-component-checkouts"></canvas>
                        </div>
                    </div>
                    <div class="well">
                        <h4>{!! trans('general.new_components_created') !!}</h4>
                        <div style="position:relative; height:160px;">
                            <canvas id="chart-components"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('general.consumables') }}</h2>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse" aria-hidden="true"><x-icon type="minus" /></button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="well">
                        <h4>{!! trans('general.checkouts_checkins') !!}</h4>
                        <div style="position:relative; height:160px;">
                            <canvas id="chart-consumable-checkouts"></canvas>
                        </div>
                    </div>
                    <div class="well">
                        <h4>{!! trans('general.new_consumables_created') !!}</h4>
                        <div style="position:relative; height:160px;">
                            <canvas id="chart-consumables"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Licenses & Accessories --}}
    <div class="row">
        <div class="col-md-6">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('general.licenses') }}</h2>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse" aria-hidden="true"><x-icon type="minus" /></button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="well">
                        <h4>{!! trans('general.checkouts_checkins') !!}</h4>
                        <div style="position:relative; height:160px;">
                            <canvas id="chart-license-checkouts"></canvas>
                        </div>
                    </div>
                    <div class="well">
                        <h4>{!! trans('general.new_licenses_created') !!}</h4>
                        <div style="position:relative; height:160px;">
                            <canvas id="chart-licenses"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('general.accessories') }}</h2>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse" aria-hidden="true"><x-icon type="minus" /></button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="well">
                        <h4>{!! trans('general.checkouts_checkins') !!}</h4>
                        <div style="position:relative; height:160px;">
                            <canvas id="chart-accessory-checkouts"></canvas>
                        </div>
                    </div>
                    <div class="well">
                        <h4>{!! trans('general.new_accessories_created') !!}</h4>
                        <div style="position:relative; height:160px;">
                            <canvas id="chart-accessories"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- Row: Report Links --}}
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('general.reports') }}</h2>
                </div>
                <div class="box-body">
                    <div class="row">

                        <div class="col-md-3 col-sm-6">
                            <a href="{{ route('reports.activity') }}" class="btn btn-default btn-block" style="margin-bottom: 10px; white-space: normal;">
                                <x-icon type="reports" /> {{ trans('general.activity_report') }}
                            </a>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <a href="{{ url('reports/custom') }}" class="btn btn-default btn-block" style="margin-bottom: 10px; white-space: normal;">
                                <x-icon type="reports" /> {{ trans('general.custom_report') }}
                            </a>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <a href="{{ route('reports.audit') }}" class="btn btn-default btn-block" style="margin-bottom: 10px; white-space: normal;">
                                <x-icon type="audit" /> {{ trans('general.audit_report') }}
                            </a>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <a href="{{ url('reports/depreciation') }}" class="btn btn-default btn-block" style="margin-bottom: 10px; white-space: normal;">
                                <x-icon type="reports" /> {{ trans('general.depreciation_report') }}
                            </a>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <a href="{{ url('reports/licenses') }}" class="btn btn-default btn-block" style="margin-bottom: 10px; white-space: normal;">
                                <x-icon type="licenses" /> {{ trans('general.license_report') }}
                            </a>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <a href="{{ route('ui.reports.maintenances') }}" class="btn btn-default btn-block" style="margin-bottom: 10px; white-space: normal;">
                                <x-icon type="maintenances" /> {{ trans('general.asset_maintenance_report') }}
                            </a>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <a href="{{ url('reports/unaccepted_assets') }}" class="btn btn-default btn-block" style="margin-bottom: 10px; white-space: normal;">
                                <x-icon type="assets" /> {{ trans('general.unaccepted_asset_report') }}
                            </a>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <a href="{{ url('reports/accessories') }}" class="btn btn-default btn-block" style="margin-bottom: 10px; white-space: normal;">
                                <x-icon type="accessories" /> {{ trans('general.accessory_report') }}
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

@stop


@push('js')
<script src="{{ url(mix('js/dist/Chart.min.js')) }}"></script>
<script nonce="{{ csrf_token() }}">

// Chart instances keyed by canvas ID for clean destroy/recreate
var charts = {};

var lineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    legend: { position: 'bottom', labels: { boxWidth: 12 } },
    scales: {
        xAxes: [{ gridLines: { display: false }, ticks: { maxTicksLimit: 10 } }],
        yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }]
    }
};

function ds(label, data, color, isPrev) {
    return {
        label:            label,
        data:             data,
        borderColor:      color,
        backgroundColor:  color,
        borderWidth:      isPrev ? 1.5 : 2,
        borderDash:       isPrev ? [5, 4] : [],
        pointRadius:      isPrev ? 0 : 3,
        pointHoverRadius: isPrev ? 3 : 5,
        fill:             false,
        tension:          0.3,
    };
}

function makeChart(id, labels, current, previous, label, prevPeriod, color) {
    if (charts[id]) { charts[id].destroy(); }
    charts[id] = new Chart(document.getElementById(id), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                ds(label,                        current,  color,                    false),
                ds(label + ' (' + prevPeriod + ')', previous, hexToRgba(color, 0.5), true),
            ]
        },
        options: lineOptions
    });
}

function makeChart2(id, labels, d1, d2, prev1, prev2, label1, label2, color1, color2, prevPeriod) {
    if (charts[id]) { charts[id].destroy(); }
    charts[id] = new Chart(document.getElementById(id), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                ds(label1,                           d1,   color1,                    false),
                ds(label2,                           d2,   color2,                    false),
                ds(label1 + ' (' + prevPeriod + ')', prev1, hexToRgba(color1, 0.5), true),
                ds(label2 + ' (' + prevPeriod + ')', prev2, hexToRgba(color2, 0.5), true),
            ]
        },
        options: lineOptions
    });
}

function hexToRgba(hex, alpha) {
    var r = parseInt(hex.slice(1,3), 16);
    var g = parseInt(hex.slice(3,5), 16);
    var b = parseInt(hex.slice(5,7), 16);
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
}

function arrSum(arr) {
    return arr.reduce(function(a, b) { return a + b; }, 0);
}

function trendPct(cur, prev) {
    var c = arrSum(cur), p = arrSum(prev);
    return (c + p) === 0 ? 0 : Math.round(c / (c + p) * 100);
}

function setInfoBar(id, pct, prevLabel) {
    $('#progress-' + id).css('width', pct + '%');
    $('#progress-' + id + '-label').text(pct + '% {!! trans('general.vs_prior_period') !!} (' + prevLabel + ')');
}

function loadCharts(params) {
    $.ajax({
        type: 'GET',
        url: '{{ route('api.reports.activity.chart') }}',
        data: params,
        headers: { "X-Requested-With": 'XMLHttpRequest', "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content') },
        dataType: 'json',
        success: function(d) {
            var p = d.prev_label;

            // Info-box progress bars
            setInfoBar('audit',      trendPct(d.asset_checkouts,   d.prev_asset_checkouts),   p);
            setInfoBar('checkin',    trendPct(d.asset_checkins,    d.prev_asset_checkins),    p);
            setInfoBar('acceptance', trendPct(d.asset_checkouts,   d.prev_asset_checkouts),   p);
            setInfoBar('licenses',   trendPct(d.license_checkouts, d.prev_license_checkouts), p);

            // Assets
            makeChart2('chart-asset-checkouts',
                d.labels, d.asset_checkouts, d.asset_checkins, d.prev_asset_checkouts, d.prev_asset_checkins,
                '{!! trans('general.checkouts') !!}', '{!! trans('general.checkins') !!}',
                '#3c8dbc', '#00a65a', p
            );
            makeChart('chart-assets',       d.labels, d.new_assets,       d.prev_new_assets,       '{!! trans('general.assets') !!}',       p, '#f39c12');
            makeChart('chart-maintenances', d.labels, d.new_maintenances, d.prev_new_maintenances, '{!! trans('general.maintenances') !!}', p, '#dd4b39');
            makeChart('chart-audits',       d.labels, d.new_audits,       d.prev_new_audits,       '{!! trans('general.audits') !!}',       p, '#605ca8');

            // Components
            makeChart2('chart-component-checkouts',
                d.labels, d.component_checkouts, d.component_checkins, d.prev_component_checkouts, d.prev_component_checkins,
                '{!! trans('general.checkouts') !!}', '{!! trans('general.checkins') !!}',
                '#3c8dbc', '#00a65a', p
            );
            makeChart('chart-components', d.labels, d.new_components, d.prev_new_components, '{!! trans('general.components') !!}', p, '#39cccc');

            // Consumables
            makeChart2('chart-consumable-checkouts',
                d.labels, d.consumable_checkouts, d.consumable_checkins, d.prev_consumable_checkouts, d.prev_consumable_checkins,
                '{!! trans('general.checkouts') !!}', '{!! trans('general.checkins') !!}',
                '#3c8dbc', '#00a65a', p
            );
            makeChart('chart-consumables', d.labels, d.new_consumables, d.prev_new_consumables, '{!! trans('general.consumables') !!}', p, '#ff851b');

            // Licenses
            makeChart2('chart-license-checkouts',
                d.labels, d.license_checkouts, d.license_checkins, d.prev_license_checkouts, d.prev_license_checkins,
                '{!! trans('general.checkouts') !!}', '{!! trans('general.checkins') !!}',
                '#3c8dbc', '#00a65a', p
            );
            makeChart('chart-licenses', d.labels, d.new_licenses, d.prev_new_licenses, '{!! trans('general.licenses') !!}', p, '#d81b60');

            // Accessories
            makeChart2('chart-accessory-checkouts',
                d.labels, d.accessory_checkouts, d.accessory_checkins, d.prev_accessory_checkouts, d.prev_accessory_checkins,
                '{!! trans('general.checkouts') !!}', '{!! trans('general.checkins') !!}',
                '#3c8dbc', '#00a65a', p
            );
            makeChart('chart-accessories', d.labels, d.new_accessories, d.prev_new_accessories, '{!! trans('general.accessories') !!}', p, '#00c0ef');
        }
    });
}

// Preset dropdown
$('#chartTimeRange').on('change', function() {
    if ($(this).val() === 'custom') {
        $('#customRangePicker').css('display', 'flex');
    } else {
        $('#customRangePicker').hide();
        loadCharts({ days: $(this).val() });
    }
});

// Bootstrap datepicker — same options as reports/custom.blade.php
$('#customRangePicker').datepicker({
    clearBtn: true,
    todayHighlight: true,
    endDate: '0d',
    format: 'yyyy-mm-dd',
    keepEmptyValues: true,
});

$('#customRangePicker').on('changeDate', function() {
    var start = $('#chartStartDate').val();
    var end   = $('#chartEndDate').val();
    if (start && end && start <= end) {
        loadCharts({ start_date: start, end_date: end });
    }
});

loadCharts({ days: 30 });

</script>
@endpush
