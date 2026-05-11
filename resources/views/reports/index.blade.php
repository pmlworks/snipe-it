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
                <div class="small-box {{ $audit_alert_count > 0 ? 'bg-red' : 'bg-green' }}">
                    <div class="inner">
                        <h3>{{ number_format($audit_alert_count) }}</h3>
                        <p>{{ trans('general.audit_due') }} / {{ trans('general.audit_overdue') }}</p>
                    </div>
                    <div class="icon" aria-hidden="true"><x-icon type="audit" /></div>
                    <span class="small-box-footer">
                        {{ trans('general.viewall') }} <x-icon type="arrow-circle-right" />
                    </span>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-sm-6">
            <a href="{{ route('hardware.index') }}">
                <div class="small-box {{ $checkin_alert_count > 0 ? 'bg-red' : 'bg-green' }}">
                    <div class="inner">
                        <h3>{{ number_format($checkin_alert_count) }}</h3>
                        <p>{{ trans('general.checkin_due') }} / {{ trans('general.checkin_overdue') }}</p>
                    </div>
                    <div class="icon" aria-hidden="true"><x-icon type="assets" /></div>
                    <span class="small-box-footer">
                        {{ trans('general.viewall') }} <x-icon type="arrow-circle-right" />
                    </span>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-sm-6">
            <a href="{{ route('reports/unaccepted_assets') }}">
                <div class="small-box {{ $pending_acceptance_count > 0 ? 'bg-yellow' : 'bg-green' }}">
                    <div class="inner">
                        <h3>{{ number_format($pending_acceptance_count) }}</h3>
                        <p>{{ trans('general.unaccepted_asset_report') }}</p>
                    </div>
                    <div class="icon" aria-hidden="true"><x-icon type="assets" /></div>
                    <span class="small-box-footer">
                        {{ trans('general.viewall') }} <x-icon type="arrow-circle-right" />
                    </span>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-sm-6">
            <a href="{{ url('reports/licenses') }}">
                <div class="small-box {{ $licenses_low_count > 0 ? 'bg-red' : 'bg-green' }}">
                    <div class="inner">
                        <h3>{{ number_format($licenses_low_count) }}</h3>
                        <p>{{ trans('general.licenses_with_no_seats') }}</p>
                    </div>
                    <div class="icon" aria-hidden="true"><x-icon type="licenses" /></div>
                    <span class="small-box-footer">
                        {{ trans('general.viewall') }} <x-icon type="arrow-circle-right" />
                    </span>
                </div>
            </a>
        </div>

    </div>


    {{-- Charts: all inside one box with the date-range control in the header --}}
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">

                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('general.activity_overview') }}</h2>
                    <div class="box-tools pull-right" style="display:flex; align-items:center; gap:8px;">
                        <label style="margin:0; font-weight:normal; white-space:nowrap;">{{ trans('general.time_range') }}:</label>
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
                        <button type="button" class="btn btn-box-tool" data-widget="collapse" aria-hidden="true"><x-icon type="minus" /></button>
                    </div>
                </div>

                <div class="box-body">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="well">
                                <h4>{!! trans('general.checkouts_checkins') !!}</h4>
                                <div style="position:relative; height:160px;">
                                    <canvas id="chart-checkouts"></canvas>
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
                                <h4>{!! trans('general.new_users_created') !!}</h4>
                                <div style="position:relative; height:160px;">
                                    <canvas id="chart-users"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="well">
                                <h4>{!! trans('general.new_accessories_created') !!}</h4>
                                <div style="position:relative; height:160px;">
                                    <canvas id="chart-accessories"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="well">
                                <h4>{!! trans('general.new_components_created') !!}</h4>
                                <div style="position:relative; height:160px;">
                                    <canvas id="chart-components"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="well">
                                <h4>{!! trans('general.new_consumables_created') !!}</h4>
                                <div style="position:relative; height:160px;">
                                    <canvas id="chart-consumables"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="well">
                                <h4>{!! trans('general.new_licenses_created') !!}</h4>
                                <div style="position:relative; height:160px;">
                                    <canvas id="chart-licenses"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>{{-- /.box-body --}}
            </div>{{-- /.box --}}
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

function loadCharts(params) {
    $.ajax({
        type: 'GET',
        url: '{{ route('api.reports.activity.chart') }}',
        data: params,
        headers: { "X-Requested-With": 'XMLHttpRequest', "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content') },
        dataType: 'json',
        success: function(d) {
            var p = d.prev_label;

            makeChart2('chart-checkouts',
                d.labels,
                d.checkouts, d.checkins,
                d.prev_checkouts, d.prev_checkins,
                '{!! trans('general.checkouts') !!}', '{!! trans('general.checkins') !!}',
                '#3c8dbc', '#00a65a', p
            );

            makeChart('chart-assets',
                d.labels, d.new_assets, d.prev_new_assets,
                '{!! trans('general.assets') !!}', p, '#f39c12'
            );

            makeChart('chart-maintenances',
                d.labels, d.new_maintenances, d.prev_new_maintenances,
                '{!! trans('general.maintenances') !!}', p, '#dd4b39'
            );

            makeChart('chart-users',
                d.labels, d.new_users, d.prev_new_users,
                '{!! trans('general.users') !!}', p, '#605ca8'
            );

            makeChart('chart-accessories',
                d.labels, d.new_accessories, d.prev_new_accessories,
                '{!! trans('general.accessories') !!}', p, '#00c0ef'
            );

            makeChart('chart-components',
                d.labels, d.new_components, d.prev_new_components,
                '{!! trans('general.components') !!}', p, '#39cccc'
            );

            makeChart('chart-consumables',
                d.labels, d.new_consumables, d.prev_new_consumables,
                '{!! trans('general.consumables') !!}', p, '#ff851b'
            );

            makeChart('chart-licenses',
                d.labels, d.new_licenses, d.prev_new_licenses,
                '{!! trans('general.licenses') !!}', p, '#d81b60'
            );
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
