<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Actionlog;
use App\Models\Component;
use App\Models\ReportTemplate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use League\Csv\EscapeFormula;
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

        return new StreamedResponse(function () use ($request) {
            Log::debug('Starting streamed response for custom component report');
            Log::debug('CSV escaping is set to: '.config('app.escape_formulas'));

            // Open output stream
            $handle = fopen('php://output', 'w');
            stream_set_timeout($handle, 2000);

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            $headerRow = $this->generateHeaders($request);

            Log::debug('Adding headers: '.$this->getExecutionTime());
            fputcsv($handle, $headerRow);
            Log::debug('Added headers: '.$this->getExecutionTime());

            $this->buildQuery($request)->orderBy('components.id', 'ASC')->chunk(500, function ($components) use ($handle, $request) {
                Log::debug('Walking results: '.$this->getExecutionTime());

                $count = 0;

                $formatter = new EscapeFormula('`');

                /** @var Component $component */
                foreach ($components as $component) {
                    $rowsToWrite = $component->qty;

                    for ($i = 0; $i < $rowsToWrite; $i++) {
                        $count++;
                        $row = [];

                        if ($request->filled('id')) {
                            $row[] = $component->id;
                        }

                        if ($request->filled('company')) {
                            $row[] = $component->company->name ?? '';
                        }

                        if ($request->filled('category')) {
                            $row[] = $component?->category->name ?? '';
                        }

                        if ($request->filled('component_name')) {
                            $row[] = $component->name;
                        }

                        if ($request->filled('manufacturer')) {
                            $row[] = $component?->manufacturer->name ?? '';
                        }

                        if ($request->filled('model')) {
                            $row[] = $component->model_number;
                        }

                        if ($request->filled('serial')) {
                            $row[] = $component->serial;
                        }

                        if ($request->filled('include_assignments')) {
                            if (isset($component->assets[$i])) {
                                $row[] = $component->assets[$i]->name ?? '';
                                $row[] = $component->assets[$i]->asset_tag ?? '';
                                $row[] = $component->assets[$i]->company->name ?? '';
                                $row[] = $component->assets[$i]->serial;
                            } else {
                                $row[] = '';
                                $row[] = '';
                                $row[] = '';
                                $row[] = '';
                            }
                        }

                        if ($request->filled('purchase_date')) {
                            $row[] = $component->purchase_date ? Carbon::make($component->purchase_date)->format('Y-m-d') : '';
                        }

                        if ($request->filled('quantity')) {
                            $row[] = $component->qty;
                        }

                        if ($request->filled('min_amount')) {
                            $row[] = $component->min_amt;
                        }

                        if ($request->filled('unit_cost')) {
                            $row[] = $component->purchase_cost;
                        }

                        if ($request->filled('order')) {
                            $row[] = $component->order_number;
                        }

                        if ($request->filled('supplier')) {
                            $row[] = $component?->supplier?->name;
                        }

                        if ($request->filled('location')) {
                            $row[] = $component->location->name ?? '';

                            if ($request->filled('location_address')) {
                                $row[] = $component->location->address ?? '';
                                $row[] = $component->location->address2 ?? '';
                                $row[] = $component->location->city ?? '';
                                $row[] = $component->location->state ?? '';
                                $row[] = $component->location->country ?? '';
                                $row[] = $component->location->zip ?? '';
                            }
                        }

                        // if ($request->filled('checkout_date')) {
                        //     // todo: checkout date
                        //     $row[] = '';
                        // }

                        if ($request->filled('created_at')) {
                            $row[] = $component->created_at;
                        }

                        if ($request->filled('updated_at')) {
                            $row[] = $component->updated_at;
                        }

                        if ($request->filled('deleted_at')) {
                            $row[] = $component->deleted_at ?? '';
                        }

                        if ($request->filled('notes')) {
                            $row[] = $component->notes;
                        }

                        // CSV_ESCAPE_FORMULAS is set to false in the .env
                        if (config('app.escape_formulas') === false) {
                            fputcsv($handle, $row);

                            // CSV_ESCAPE_FORMULAS is set to true or is not set in the .env
                        } else {
                            fputcsv($handle, $formatter->escapeRecord($row));
                        }

                        Log::debug('-- Record '.$count.' Component ID:'.$component->id.' in '.$this->getExecutionTime());
                    }
                }
            });

            // Close the output stream
            fclose($handle);
            $executionTime = $this->getExecutionTime();
            Log::debug('-- SCRIPT COMPLETED IN '.$executionTime);

        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="custom-components-report-'.date('Y-m-d-his').'.csv"',
        ]);
    }

    private function getExecutionTime(): mixed
    {
        return microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
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

        if ($request->filled('include_assignments')) {
            $header[] = trans('admin/hardware/form.name');
            $header[] = trans('admin/hardware/form.tag');
            $header[] = trans('admin/reports/general.custom_export.asset_company');
            $header[] = trans('admin/reports/general.custom_export.asset_serial');
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
            $header[] = trans('general.supplier');
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

        // todo: has to have include_assignments enabled
        // if ($request->filled('checkout_date')) {
        //     $header[] = trans('admin/hardware/table.checkout_date');
        // }

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

        return $header;
    }

    private function buildQuery(Request $request): Builder
    {
        $query = Component::select('components.*')
            ->with([
                'category',
                'company',
                'location',
                'manufacturer',
                'supplier',
            ]);

        $request->whenFilled('include_assignments', fn () => $query->with('assets.company'));

        $query = $this->appendLocalConstraints($query, $request, [
            'by_model_number' => 'components.model_number',
            'by_name' => 'components.name',
            'by_order_number' => 'components.order_number',
        ]);

        $query = $this->appendForeignConstraints($query, $request, [
            'by_category_id' => 'components.category_id',
            'by_company_id' => 'components.company_id',
            'by_location_id' => 'components.location_id',
            'by_manufacturer_id' => 'components.manufacturer_id',
            'by_supplier_id' => 'components.supplier_id',
        ]);

        $query = $this->appendNumericalBoundaries($query, $request, [
            // formKey => column
            // _start and _end will be appended to the key
            // ie: quantity_start|quantity_end => qty
            'quantity' => 'qty',
            'min_quantity' => 'min_amt',
            'unit_cost' => 'purchase_cost',
        ]);

        $query = $this->appendDateWindowBoundaries($query, $request, [
            // formKey => column
            // _start and _end will be appended to the key
            // ie: purchase_start|purchase_end => purchase_date
            'purchase' => 'purchase_date',
            'created' => 'created_at',
            'last_updated' => 'updated_at',
        ]);

        $query = $this->appendBeforeDateBoundaries($query, $request, [
            // formKey => column
            'last_updated_before' => 'updated_at',
        ]);

        if ($request->filled('checkout_date_start') && $request->filled('checkout_date_end')) {
            $checkout_start = Carbon::parse($request->input('checkout_date_start'))->startOfDay();
            $checkout_end = Carbon::parse($request->input('checkout_date_end', now()))->endOfDay();

            $componentIdsWithinCheckoutRange = Actionlog::where('action_type', '=', 'checkout')
                ->where('item_type', Component::class)
                ->whereBetween('action_date', [$checkout_start, $checkout_end])
                ->pluck('item_id');

            $query->whereIn('components.id', $componentIdsWithinCheckoutRange);
        }

        if ($request->input('deleted_components') === 'include_deleted') {
            $query->withTrashed();
        }

        if ($request->input('deleted_components') === 'only_deleted') {
            $query->onlyTrashed();
        }

        return $query;
    }

    private function appendLocalConstraints(Builder $query, Request $request, array $constraints): Builder
    {
        foreach ($constraints as $formKey => $column) {
            if ($request->filled($formKey)) {
                $query->where($column, $request->input($formKey));
            }
        }

        return $query;
    }

    private function appendForeignConstraints(Builder $query, Request $request, array $constraints): Builder
    {
        foreach ($constraints as $formKey => $column) {
            if ($request->filled($formKey)) {
                $query->whereIn($column, $request->input($formKey));
            }
        }

        return $query;
    }

    private function appendNumericalBoundaries(Builder $query, Request $request, array $mapping): Builder
    {
        foreach ($mapping as $formKey => $column) {
            if ($request->filled(["{$formKey}_start", "{$formKey}_end"])) {
                $query->whereBetween("components.{$column}", [
                    $request->input("{$formKey}_start"),
                    $request->input("{$formKey}_end"),
                ]);
            }
        }

        return $query;
    }

    private function appendDateWindowBoundaries(Builder $query, Request $request, array $mapping): Builder
    {
        foreach ($mapping as $formKey => $column) {
            if (($request->filled("{$formKey}_start")) && ($request->filled("{$formKey}_end"))) {
                $start = Carbon::parse($request->input("{$formKey}_start"))->startOfDay();
                $end = Carbon::parse($request->input("{$formKey}_end"))->endOfDay();

                $query->whereBetween("components.{$column}", [$start, $end]);
            }
        }

        return $query;
    }

    private function appendBeforeDateBoundaries(Builder $query, Request $request, array $mapping): Builder
    {
        foreach ($mapping as $formKey => $column) {
            if ($request->filled($formKey)) {
                $date = Carbon::parse(today()->subDays($request->input($formKey)));
                $query->where('components.updated_at', '<', $date);
            }
        }

        return $query;
    }
}
