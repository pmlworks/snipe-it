<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ReportTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomComponentReportController extends Controller
{
    public function show(Request $request)
    {
        $this->authorize('reports.view');

        $report_templates = ReportTemplate::where('type', 'component')->orderBy('name')->get();

        // The view needs a template to render correctly, even if it is empty...
        $template = new ReportTemplate;

        // Set the report's input values in the cases we were redirected back
        // with validation errors so the report is populated as expected.
        if ($request->old()) {
            $template->name = $request->old('name');
            $template->options = $request->old();
        }

        return view('reports.custom.component', [
            'report_templates' => $report_templates,
            'template' => $template,
        ]);
    }

    public function run(Request $request)
    {
        $this->authorize('reports.view');

        ini_set('max_execution_time', env('REPORT_TIME_LIMIT', 12000)); // 12000 seconds = 200 minutes

        $this->disableDebugbar();

        $response = new StreamedResponse(function () use ($request) {
            Log::debug('Starting streamed response for custom component report');
            Log::debug('CSV escaping is set to: '.config('app.escape_formulas'));

            // Open output stream
            $handle = fopen('php://output', 'w');
            stream_set_timeout($handle, 2000);

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            $headerRow = $this->generateHeaders($request);

            $executionTime = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
            Log::debug('Starting headers: '.$executionTime);
            fputcsv($handle, $headerRow);
            $executionTime = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
            Log::debug('Added headers: '.$executionTime);

            // Close the output stream
            fclose($handle);
            $executionTime = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
            Log::debug('-- SCRIPT COMPLETED IN '.$executionTime);

        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="custom-accessories-report-'.date('Y-m-d-his').'.csv"',
        ]);

        return $response;
    }

    private function generateHeaders(Request $request): array
    {
        $header = [];

        if ($request->filled('id')) {
            $header[] = trans('general.id');
        }

        if ($request->filled('company')) {
            $header[] = trans('general.company');
        }

        if ($request->filled('category')) {
            $header[] = trans('general.category');
        }

        if ($request->filled('component_name')) {
            $header[] = trans('admin/components/general.component_name');
        }

        if ($request->filled('manufacturer')) {
            $header[] = trans('general.manufacturer');
        }

        if ($request->filled('model')) {
            $header[] = trans('general.model_no');
        }

        if ($request->filled('serial')) {
            $header[] = trans('general.serial_number');
        }

        if ($request->filled('purchase_date')) {
            $header[] = trans('general.purchase_date');
        }

        if ($request->filled('quantity')) {
            $header[] = trans('general.quantity');
        }

        if ($request->filled('min_amount')) {
            $header[] = trans('general.min_amt');
        }

        if ($request->filled('unit_cost')) {
            $header[] = trans('general.unit_cost');
        }

        if ($request->filled('order')) {
            $header[] = trans('admin/hardware/form.order');
        }

        if ($request->filled('supplier')) {
            $header[] = trans('general.suppliers');
        }

        if ($request->filled('location')) {
            $header[] = trans('general.location');
        }

        if ($request->filled('location_address')) {
            $header[] = trans('general.address');
            $header[] = trans('general.address');
            $header[] = trans('general.city');
            $header[] = trans('general.state');
            $header[] = trans('general.country');
            $header[] = trans('general.zip');
        }

        if ($request->filled('checkout_date')) {
            $header[] = trans('admin/hardware/table.checkout_date');
        }

        if ($request->filled('created_at')) {
            $header[] = trans('general.created_at');
        }

        if ($request->filled('updated_at')) {
            $header[] = trans('general.updated_at');
        }

        if ($request->filled('deleted_at')) {
            $header[] = trans('general.deleted');
        }

        if ($request->filled('notes')) {
            $header[] = trans('general.notes');
        }

        if ($request->filled('asset_name')) {
            $header[] = trans('admin/hardware/form.name');
        }

        if ($request->filled('asset_tag')) {
            $header[] = trans('admin/hardware/form.tag');
        }

        if ($request->filled('asset_company')) {
            $header[] = trans('admin/reports/general.custom_export.asset_company');
        }

        if ($request->filled('asset_serial')) {
            $header[] = trans('admin/reports/general.custom_export.asset_serial');
        }

        return $header;
    }
}
