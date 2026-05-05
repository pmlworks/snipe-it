<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ReportTemplate;
use Illuminate\Http\Request;

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
    }
}
