@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{ trans('general.reports') }}
    @parent
@stop

{{-- Page content --}}
@section('content')

    {{-- Row: Report Links --}}
    <div class="row">

        <div class="col-md-3 col-sm-6">
            <span href="{{ route('reports.activity') }}" class="btn btn-theme btn-block" style="margin-bottom: 10px; white-space: normal;">
                <x-icon type="reports"/> {{ trans('general.activity_report') }}
            </span>
        </div>

        <div class="col-md-3 col-sm-6">
            <a href="{{ url('reports/custom') }}" class="btn btn-theme btn-block" style="margin-bottom: 10px; white-space: normal;">
                <x-icon type="reports"/> {{ trans('general.custom_report') }}
            </a>
        </div>

        <div class="col-md-3 col-sm-6">
            <a href="{{ route('reports.audit') }}" class="btn btn-theme btn-block" style="margin-bottom: 10px; white-space: normal;">
                <x-icon type="audit"/> {{ trans('general.audit_report') }}
            </a>
        </div>

        <div class="col-md-3 col-sm-6">
            <a href="{{ url('reports/depreciation') }}" class="btn btn-theme btn-block" style="margin-bottom: 10px; white-space: normal;">
                <x-icon type="reports"/> {{ trans('general.depreciation_report') }}
            </a>
        </div>

        <div class="col-md-3 col-sm-6">
            <a href="{{ url('reports/licenses') }}" class="btn btn-theme btn-block" style="margin-bottom: 10px; white-space: normal;">
                <x-icon type="licenses"/> {{ trans('general.license_report') }}
            </a>
        </div>

        <div class="col-md-3 col-sm-6">
            <a href="{{ route('ui.reports.maintenances') }}" class="btn btn-theme btn-block" style="margin-bottom: 10px; white-space: normal;">
                <x-icon type="maintenances"/> {{ trans('general.asset_maintenance_report') }}
            </a>
        </div>

        <div class="col-md-3 col-sm-6">
            <a href="{{ url('reports/unaccepted_assets') }}" class="btn btn-theme btn-block" style="margin-bottom: 10px; white-space: normal;">
                <x-icon type="assets"/> {{ trans('general.unaccepted_asset_report') }}
            </a>
        </div>

        <div class="col-md-3 col-sm-6">
            <a href="{{ url('reports/accessories') }}" class="btn btn-theme btn-block" style="margin-bottom: 10px; white-space: normal;">
                <x-icon type="accessories"/> {{ trans('general.accessory_report') }}
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
                        <div class="progress" style="background-color: rgba(128,128,128,0.3); height:6px;">
                            <div class="progress-bar" id="progress-audit" style="width: 0%"></div>
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
                        <div class="progress" style="background-color: rgba(128,128,128,0.3); height:6px;">
                            <div class="progress-bar" id="progress-checkin" style="width: 0%"></div>
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
                        <div class="progress" style="background-color: rgba(128,128,128,0.3); height:6px;">
                            <div class="progress-bar" id="progress-acceptance" style="width: 0%"></div>
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
                        <div class="progress" style="background-color: rgba(128,128,128,0.3); height:6px;">
                            <div class="progress-bar" id="progress-licenses" style="width: 0%"></div>
                        </div>
                        <span class="info-box-more" id="progress-licenses-label">&nbsp;</span>
                    </div>
                </div>
            </a>
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
                            <div class="chart-title-row">
                                <h4 class="chart-title">{!! trans('general.checkouts_checkins') !!}</h4>
                                <span class="chart-tools">
                                    <button type="button" class="btn btn-box-tool chart-dl-btn" data-target="chart-asset-checkouts" title="{{ trans('general.download_chart') }}"><i class="fa fa-download fa-fw"></i></button>
                                    <button type="button" class="btn btn-box-tool chart-fs-btn" data-target="chart-asset-checkouts" title="{{ trans('general.fullscreen') }}"><i class="fa fa-expand fa-fw"></i></button>
                                </span>
                            </div>
                            <div class="chart-scroll">
                                <div class="chart-inner" id="wrap-chart-asset-checkouts" style="position:relative; height:160px; min-width:100%;">
                                    <canvas id="chart-asset-checkouts"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-title-row">
                                <h4 class="chart-title">{!! trans('general.new_assets_created') !!}</h4>
                                <span class="chart-tools">
                                    <button type="button" class="btn btn-box-tool chart-dl-btn" data-target="chart-assets" title="{{ trans('general.download_chart') }}"><i class="fa fa-download fa-fw"></i></button>
                                    <button type="button" class="btn btn-box-tool chart-fs-btn" data-target="chart-assets" title="{{ trans('general.fullscreen') }}"><i class="fa fa-expand fa-fw"></i></button>
                                </span>
                            </div>
                            <div class="chart-scroll">
                                <div class="chart-inner" id="wrap-chart-assets" style="position:relative; height:160px; min-width:100%;">
                                    <canvas id="chart-assets"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row" style="margin-top:20px;">
                        <div class="col-md-6">
                            <div class="chart-title-row">
                                <h4 class="chart-title">{!! trans('general.new_maintenances_created') !!}</h4>
                                <span class="chart-tools">
                                    <button type="button" class="btn btn-box-tool chart-dl-btn" data-target="chart-maintenances" title="{{ trans('general.download_chart') }}"><i class="fa fa-download fa-fw"></i></button>
                                    <button type="button" class="btn btn-box-tool chart-fs-btn" data-target="chart-maintenances" title="{{ trans('general.fullscreen') }}"><i class="fa fa-expand fa-fw"></i></button>
                                </span>
                            </div>
                            <div class="chart-scroll">
                                <div class="chart-inner" id="wrap-chart-maintenances" style="position:relative; height:160px; min-width:100%;">
                                    <canvas id="chart-maintenances"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-title-row">
                                <h4 class="chart-title">{!! trans('general.new_audits_created') !!}</h4>
                                <span class="chart-tools">
                                    <button type="button" class="btn btn-box-tool chart-dl-btn" data-target="chart-audits" title="{{ trans('general.download_chart') }}"><i class="fa fa-download fa-fw"></i></button>
                                    <button type="button" class="btn btn-box-tool chart-fs-btn" data-target="chart-audits" title="{{ trans('general.fullscreen') }}"><i class="fa fa-expand fa-fw"></i></button>
                                </span>
                            </div>
                            <div class="chart-scroll">
                                <div class="chart-inner" id="wrap-chart-audits" style="position:relative; height:160px; min-width:100%;">
                                    <canvas id="chart-audits"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Components --}}
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('general.components') }}</h2>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse" aria-hidden="true"><x-icon type="minus" /></button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-title-row">
                                <h4 class="chart-title">{!! trans('general.checkouts_checkins') !!}</h4>
                                <span class="chart-tools">
                                    <button type="button" class="btn btn-box-tool chart-dl-btn" data-target="chart-component-checkouts" title="{{ trans('general.download_chart') }}"><i class="fa fa-download fa-fw"></i></button>
                                    <button type="button" class="btn btn-box-tool chart-fs-btn" data-target="chart-component-checkouts" title="{{ trans('general.fullscreen') }}"><i class="fa fa-expand fa-fw"></i></button>
                                </span>
                            </div>
                            <div class="chart-scroll">
                                <div class="chart-inner" id="wrap-chart-component-checkouts" style="position:relative; height:160px; min-width:100%;">
                                    <canvas id="chart-component-checkouts"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-title-row">
                                <h4 class="chart-title">{!! trans('general.new_components_created') !!}</h4>
                                <span class="chart-tools">
                                    <button type="button" class="btn btn-box-tool chart-dl-btn" data-target="chart-components" title="{{ trans('general.download_chart') }}"><i class="fa fa-download fa-fw"></i></button>
                                    <button type="button" class="btn btn-box-tool chart-fs-btn" data-target="chart-components" title="{{ trans('general.fullscreen') }}"><i class="fa fa-expand fa-fw"></i></button>
                                </span>
                            </div>
                            <div class="chart-scroll">
                                <div class="chart-inner" id="wrap-chart-components" style="position:relative; height:160px; min-width:100%;">
                                    <canvas id="chart-components"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Consumables --}}
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('general.consumables') }}</h2>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse" aria-hidden="true"><x-icon type="minus" /></button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-title-row">
                                <h4 class="chart-title">{!! trans('general.checkouts_checkins') !!}</h4>
                                <span class="chart-tools">
                                    <button type="button" class="btn btn-box-tool chart-dl-btn" data-target="chart-consumable-checkouts" title="{{ trans('general.download_chart') }}"><i class="fa fa-download fa-fw"></i></button>
                                    <button type="button" class="btn btn-box-tool chart-fs-btn" data-target="chart-consumable-checkouts" title="{{ trans('general.fullscreen') }}"><i class="fa fa-expand fa-fw"></i></button>
                                </span>
                            </div>
                            <div class="chart-scroll">
                                <div class="chart-inner" id="wrap-chart-consumable-checkouts" style="position:relative; height:160px; min-width:100%;">
                                    <canvas id="chart-consumable-checkouts"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-title-row">
                                <h4 class="chart-title">{!! trans('general.new_consumables_created') !!}</h4>
                                <span class="chart-tools">
                                    <button type="button" class="btn btn-box-tool chart-dl-btn" data-target="chart-consumables" title="{{ trans('general.download_chart') }}"><i class="fa fa-download fa-fw"></i></button>
                                    <button type="button" class="btn btn-box-tool chart-fs-btn" data-target="chart-consumables" title="{{ trans('general.fullscreen') }}"><i class="fa fa-expand fa-fw"></i></button>
                                </span>
                            </div>
                            <div class="chart-scroll">
                                <div class="chart-inner" id="wrap-chart-consumables" style="position:relative; height:160px; min-width:100%;">
                                    <canvas id="chart-consumables"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Licenses --}}
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('general.licenses') }}</h2>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse" aria-hidden="true"><x-icon type="minus" /></button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-title-row">
                                <h4 class="chart-title">{!! trans('general.checkouts_checkins') !!}</h4>
                                <span class="chart-tools">
                                    <button type="button" class="btn btn-box-tool chart-dl-btn" data-target="chart-license-checkouts" title="{{ trans('general.download_chart') }}"><i class="fa fa-download fa-fw"></i></button>
                                    <button type="button" class="btn btn-box-tool chart-fs-btn" data-target="chart-license-checkouts" title="{{ trans('general.fullscreen') }}"><i class="fa fa-expand fa-fw"></i></button>
                                </span>
                            </div>
                            <div class="chart-scroll">
                                <div class="chart-inner" id="wrap-chart-license-checkouts" style="position:relative; height:160px; min-width:100%;">
                                    <canvas id="chart-license-checkouts"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-title-row">
                                <h4 class="chart-title">{!! trans('general.new_licenses_created') !!}</h4>
                                <span class="chart-tools">
                                    <button type="button" class="btn btn-box-tool chart-dl-btn" data-target="chart-licenses" title="{{ trans('general.download_chart') }}"><i class="fa fa-download fa-fw"></i></button>
                                    <button type="button" class="btn btn-box-tool chart-fs-btn" data-target="chart-licenses" title="{{ trans('general.fullscreen') }}"><i class="fa fa-expand fa-fw"></i></button>
                                </span>
                            </div>
                            <div class="chart-scroll">
                                <div class="chart-inner" id="wrap-chart-licenses" style="position:relative; height:160px; min-width:100%;">
                                    <canvas id="chart-licenses"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Accessories --}}
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('general.accessories') }}</h2>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse" aria-hidden="true"><x-icon type="minus" /></button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-title-row">
                                <h4 class="chart-title">{!! trans('general.checkouts_checkins') !!}</h4>
                                <span class="chart-tools">
                                    <button type="button" class="btn btn-box-tool chart-dl-btn" data-target="chart-accessory-checkouts" title="{{ trans('general.download_chart') }}"><i class="fa fa-download fa-fw"></i></button>
                                    <button type="button" class="btn btn-box-tool chart-fs-btn" data-target="chart-accessory-checkouts" title="{{ trans('general.fullscreen') }}"><i class="fa fa-expand fa-fw"></i></button>
                                </span>
                            </div>
                            <div class="chart-scroll">
                                <div class="chart-inner" id="wrap-chart-accessory-checkouts" style="position:relative; height:160px; min-width:100%;">
                                    <canvas id="chart-accessory-checkouts"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-title-row">
                                <h4 class="chart-title">{!! trans('general.new_accessories_created') !!}</h4>
                                <span class="chart-tools">
                                    <button type="button" class="btn btn-box-tool chart-dl-btn" data-target="chart-accessories" title="{{ trans('general.download_chart') }}"><i class="fa fa-download fa-fw"></i></button>
                                    <button type="button" class="btn btn-box-tool chart-fs-btn" data-target="chart-accessories" title="{{ trans('general.fullscreen') }}"><i class="fa fa-expand fa-fw"></i></button>
                                </span>
                            </div>
                            <div class="chart-scroll">
                                <div class="chart-inner" id="wrap-chart-accessories" style="position:relative; height:160px; min-width:100%;">
                                    <canvas id="chart-accessories"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@stop


@push('css')
<style>
.info-box .info-box-text { text-transform: none; }
[data-theme="dark"] .info-box { background: var(--box-bg); color: #d2d6de; }
[data-theme="dark"] .info-box .info-box-number,
[data-theme="dark"] .info-box .info-box-text,
[data-theme="dark"] .info-box .info-box-more { color: #d2d6de; }
.chart-title-row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    margin-bottom: 4px;
}
.chart-title { margin: 0; }
.chart-tools { white-space: nowrap; }
.chart-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }

/* Fullscreen: expand the chart-inner div to fill the screen */
.chart-inner:fullscreen          { height: 100vh !important; width: 100vw !important; }
.chart-inner:-webkit-full-screen { height: 100vh !important; width: 100vw !important; }
.chart-inner:-moz-full-screen    { height: 100vh !important; width: 100vw !important; }
</style>
@endpush


@push('js')
<script src="{{ url(mix('js/dist/Chart.min.js')) }}"></script>
<script nonce="{{ csrf_token() }}">

// Chart instances keyed by canvas ID for clean destroy/recreate
var charts = {};
var lastParams = { days: 30 };

function isDark() {
    return document.documentElement.getAttribute('data-theme') === 'dark';
}

function applyChartTheme() {
    Chart.defaults.global.defaultFontColor = isDark() ? '#cccccc' : '#666666';
}

// Fill canvas with the box-body background before each chart draw so lines
// render against a solid surface rather than a transparent canvas in dark mode.
Chart.pluginService.register({
    beforeDraw: function (chart) {
        var el = chart.canvas.parentElement;
        while (el && !(el.classList && el.classList.contains('box-body'))) {
            el = el.parentElement;
        }
        var bg = el ? window.getComputedStyle(el).backgroundColor : null;
        if (!bg || bg === 'rgba(0, 0, 0, 0)' || bg === 'transparent') return;
        var ctx = chart.chart.ctx;
        ctx.save();
        ctx.fillStyle = bg;
        ctx.fillRect(0, 0, chart.chart.width, chart.chart.height);
        ctx.restore();
    },
});

function getLineOptions() {
    var dark = isDark();
    var fontColor = dark ? '#cccccc' : '#666666';
    var gridColor = dark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';
    return {
        responsive: true,
        maintainAspectRatio: false,
        legend: { position: 'bottom', labels: { boxWidth: 12, fontColor: fontColor } },
        scales: {
            xAxes: [{ gridLines: { display: false }, ticks: { maxTicksLimit: 10, fontColor: fontColor } }],
            yAxes: [{ gridLines: { color: gridColor }, ticks: { beginAtZero: true, precision: 0, fontColor: fontColor } }]
        }
    };
}

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

// Widen the canvas wrapper for long ranges so labels aren't squashed.
// Each label gets at least 8px; below 61 labels the chart fills naturally.
function applyScrollWidth(id, labelCount) {
    var wrap = document.getElementById('wrap-' + id);
    if (!wrap) return;
    wrap.style.width = labelCount > 60 ? (labelCount * 8) + 'px' : '';
}

function makeChart(id, labels, current, previous, label, prevPeriod, color) {
    if (charts[id]) { charts[id].destroy(); }
    applyScrollWidth(id, labels.length);
    charts[id] = new Chart(document.getElementById(id), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                ds(label,                           current,  color,                    false),
                ds(label + ' (' + prevPeriod + ')', previous, hexToRgba(color, 0.5),   true),
            ]
        },
        options: getLineOptions()
    });
}

function makeChart2(id, labels, d1, d2, prev1, prev2, label1, label2, color1, color2, prevPeriod) {
    if (charts[id]) { charts[id].destroy(); }
    applyScrollWidth(id, labels.length);
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
        options: getLineOptions()
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
    $('#progress-' + id).css('width', pct + '%').css('background-color', isDark() ? '#60b0e0' : '#3c8dbc');
    $('#progress-' + id + '-label').text(pct + '% {!! trans('general.vs_prior_period') !!} (' + prevLabel + ')');
}

var palette = {
    light: {
        checkout: '#3c8dbc', checkin: '#00a65a', asset: '#f39c12',
        maintenance: '#dd4b39', audit: '#605ca8', component: '#39cccc',
        consumable: '#ff851b', license: '#d81b60', accessory: '#00c0ef',
    },
    dark: {
        checkout: '#60b0e0', checkin: '#2ecc71', asset: '#f5b942',
        maintenance: '#e74c3c', audit: '#9b97e0', component: '#4de8e8',
        consumable: '#ffa03a', license: '#f06292', accessory: '#29d8ff',
    },
};

function c(key) {
    return isDark() ? palette.dark[key] : palette.light[key];
}

function loadCharts(params) {
    lastParams = params;
    applyChartTheme();
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
                c('checkout'), c('checkin'), p
            );
            makeChart('chart-assets',       d.labels, d.new_assets,       d.prev_new_assets,       '{!! trans('general.assets') !!}',       p, c('asset'));
            makeChart('chart-maintenances', d.labels, d.new_maintenances, d.prev_new_maintenances, '{!! trans('general.maintenances') !!}', p, c('maintenance'));
            makeChart('chart-audits',       d.labels, d.new_audits,       d.prev_new_audits,       '{!! trans('general.audits') !!}',       p, c('audit'));

            // Components
            makeChart2('chart-component-checkouts',
                d.labels, d.component_checkouts, d.component_checkins, d.prev_component_checkouts, d.prev_component_checkins,
                '{!! trans('general.checkouts') !!}', '{!! trans('general.checkins') !!}',
                c('checkout'), c('checkin'), p
            );
            makeChart('chart-components', d.labels, d.new_components, d.prev_new_components, '{!! trans('general.components') !!}', p, c('component'));

            // Consumables
            makeChart2('chart-consumable-checkouts',
                d.labels, d.consumable_checkouts, d.consumable_checkins, d.prev_consumable_checkouts, d.prev_consumable_checkins,
                '{!! trans('general.checkouts') !!}', '{!! trans('general.checkins') !!}',
                c('checkout'), c('checkin'), p
            );
            makeChart('chart-consumables', d.labels, d.new_consumables, d.prev_new_consumables, '{!! trans('general.consumables') !!}', p, c('consumable'));

            // Licenses
            makeChart2('chart-license-checkouts',
                d.labels, d.license_checkouts, d.license_checkins, d.prev_license_checkouts, d.prev_license_checkins,
                '{!! trans('general.checkouts') !!}', '{!! trans('general.checkins') !!}',
                c('checkout'), c('checkin'), p
            );
            makeChart('chart-licenses', d.labels, d.new_licenses, d.prev_new_licenses, '{!! trans('general.licenses') !!}', p, c('license'));

            // Accessories
            makeChart2('chart-accessory-checkouts',
                d.labels, d.accessory_checkouts, d.accessory_checkins, d.prev_accessory_checkouts, d.prev_accessory_checkins,
                '{!! trans('general.checkouts') !!}', '{!! trans('general.checkins') !!}',
                c('checkout'), c('checkin'), p
            );
            makeChart('chart-accessories', d.labels, d.new_accessories, d.prev_new_accessories, '{!! trans('general.accessories') !!}', p, c('accessory'));
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

// Bootstrap datepicker
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

// Download chart as PNG
$(document).on('click', '.chart-dl-btn', function() {
    var id    = $(this).data('target');
    var chart = charts[id];
    if (!chart) return;
    var a = document.createElement('a');
    a.href     = chart.toBase64Image();
    a.download = id + '.png';
    a.click();
});

// Fullscreen chart
$(document).on('click', '.chart-fs-btn', function() {
    var wrap = document.getElementById('wrap-' + $(this).data('target'));
    if (!wrap) return;
    var req = wrap.requestFullscreen || wrap.webkitRequestFullscreen || wrap.mozRequestFullScreen;
    if (req) req.call(wrap);
});

// After exiting fullscreen, resize all charts back to their containers
document.addEventListener('fullscreenchange', function() {
    if (!document.fullscreenElement) {
        $.each(charts, function(id, chart) { if (chart) chart.resize(); });
    }
});
document.addEventListener('webkitfullscreenchange', function() {
    if (!document.webkitFullscreenElement) {
        $.each(charts, function(id, chart) { if (chart) chart.resize(); });
    }
});

loadCharts({ days: 30 });

// Reload charts when data-theme attribute changes on <html> (the app's dark mode mechanism)
new MutationObserver(function () {
    loadCharts(lastParams);
}).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

</script>
@endpush
