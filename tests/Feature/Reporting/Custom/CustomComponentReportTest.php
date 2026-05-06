<?php

namespace Tests\Feature\Reporting\Custom;

use App\Models\Category;
use App\Models\Company;
use App\Models\Component;
use App\Models\ReportTemplate;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('custom-reporting')]
class CustomComponentReportTest extends TestCase
{
    public function test_requires_permission_to_view_page()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reports.custom.component'))
            ->assertForbidden();
    }

    public function test_requires_permission_to_run_report()
    {
        $this->actingAs(User::factory()->create())
            ->post(route('reports.custom.component.run'), [
                //
            ])
            ->assertForbidden();
    }

    public function test_can_load_custom_report_page()
    {
        $this->actingAs(User::factory()->canViewReports()->create())
            ->get(route('reports.custom.component'))
            ->assertOk();
    }

    public function test_saved_templates_on_page_are_scoped_to_the_user_and_type()
    {
        // Given there are saved templates for one user
        ReportTemplate::factory()->create(['type' => 'asset', 'name' => 'Another User: Asset']);
        ReportTemplate::factory()->create(['type' => 'accessory', 'name' => 'Another User: Accessory']);
        ReportTemplate::factory()->create(['type' => 'component', 'name' => 'Another User: Component']);

        // When loading reports.custom.component while acting as another user that also has saved templates
        $user = User::factory()->canViewReports()
            ->has(ReportTemplate::factory(['type' => 'asset', 'name' => 'User: Asset']))
            ->has(ReportTemplate::factory(['type' => 'accessory', 'name' => 'User: Accessory']))
            ->has(ReportTemplate::factory(['type' => 'component', 'name' => 'User: Component']))
            ->create();

        $response = $this->actingAs($user)->get(route('reports.custom.component'));

        $viewTemplateNames = $response->viewData('report_templates')->pluck('name');

        // The user should only see their component template
        $this->assertTrue($viewTemplateNames->contains('User: Component'));
        $this->assertTrue($viewTemplateNames->doesntContain('User: Accessory'));
        $this->assertTrue($viewTemplateNames->doesntContain('User: Asset'));
        $this->assertTrue($viewTemplateNames->doesntContain('Another User: Asset'));
        $this->assertTrue($viewTemplateNames->doesntContain('Another User: Accessory'));
        $this->assertTrue($viewTemplateNames->doesntContain('Another User: Component'));
    }

    public function test_custom_component_report_headers()
    {
        $this->actingAs(User::factory()->canViewReports()->create())
            ->post(route('reports.custom.component.run'), [
                'id' => '1',
                'company' => '1',
                'category' => '1',
                'component_name' => '1',
                'manufacturer' => '1',
                'model' => '1',
                'serial' => '1',
                'purchase_date' => '1',
                'quantity' => '1',
                'min_amount' => '1',
                'unit_cost' => '1',
                'order' => '1',
                'supplier' => '1',
                'location' => '1',
                'location_address' => '1',
                'checkout_date' => '1',
                'created_at' => '1',
                'updated_at' => '1',
                'deleted_at' => '1',
                'notes' => '1',
                'asset_name' => '1',
                'asset_tag' => '1',
                'asset_company' => '1',
                'asset_serial' => '1',
            ])
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=utf-8')
            ->assertSeeTextInStreamedResponse([
                trans('general.id'),
                trans('general.company'),
                trans('general.category'),
                trans('admin/components/general.component_name'),
                trans('general.manufacturer'),
                trans('general.model_no'),
                trans('general.serial_number'),
                trans('general.purchase_date'),
                trans('general.quantity'),
                trans('general.min_amt'),
                trans('general.unit_cost'),
                trans('admin/hardware/form.order'),
                trans('general.suppliers'),
                trans('general.location'),
                trans('general.address'),
                trans('general.city'),
                trans('general.state'),
                trans('general.country'),
                trans('general.zip'),
                trans('admin/hardware/table.checkout_date'),
                trans('general.created_at'),
                trans('general.updated_at'),
                trans('general.deleted'),
                trans('general.notes'),
                trans('admin/hardware/form.name'),
                trans('admin/hardware/form.tag'),
                trans('admin/reports/general.custom_export.asset_company'),
                trans('admin/reports/general.custom_export.asset_serial'),
            ]);
    }

    public function test_omitted_columns_are_excluded_from_report_headers()
    {
        $this->actingAs(User::factory()->canViewReports()->create())
            ->post(route('reports.custom.component.run'), [
                'id' => '1',
                'component_name' => '1',
                // company and category intentionally omitted
            ])
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=utf-8')
            ->assertDontSeeTextInStreamedResponse([
                trans('general.company'),
                trans('general.category'),
            ]);
    }

    public function test_custom_limiting_by_company()
    {
        $this->markTestIncomplete();

        [$companyA, $companyB] = Company::factory()->count(2)->create()->all();

        Component::factory()
            ->count(2)
            ->sequence(
                ['company_id' => $companyA->id, 'name' => 'Component for Company A'],
                ['company_id' => $companyB->id, 'name' => 'Component for Company B'],
            )
            ->create();

        $this->actingAs(User::factory()->canViewReports()->create())
            ->post(route('reports.custom.component.run'), [
                'company' => '1',
                'by_company_id' => [
                    $companyA->id,
                ],
            ])
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=utf-8')
            ->assertSeeTextInStreamedResponse('Component for Company A')
            ->assertDontSeeTextInStreamedResponse('Component for Company B');
    }

    public function test_limiting_by_category()
    {
        $this->markTestIncomplete();

        [$categoryA, $categoryB] = Category::factory()->count(2)->create()->all();

        Component::factory()
            ->count(2)
            ->sequence(
                ['category_type' => $categoryA->id, 'name' => 'Component for Category A'],
                ['category_type' => $categoryB->id, 'name' => 'Component for Category B'],
            )
            ->create();

        $data = [
            'category' => '1',
            'by_category_id' => [
                $categoryA->id,
            ],
        ];

        $this->actingAs(User::factory()->canViewReports()->create())
            ->post(route('reports.custom.component.run'), $data)
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=utf-8')
            ->assertSeeTextInStreamedResponse('Category for Company A')
            ->assertDontSeeTextInStreamedResponse('Category for Company B');
    }

    public function test_limiting_by_manufacturer()
    {
        $this->markTestIncomplete();
    }

    public function test_limiting_by_supplier()
    {
        $this->markTestIncomplete();
    }

    public function test_limiting_by_location()
    {
        $this->markTestIncomplete();
    }

    public function test_limiting_by_name()
    {
        $this->markTestIncomplete();
    }

    public function test_limiting_by_model_number()
    {
        $this->markTestIncomplete();
    }

    public function test_limiting_by_order_number()
    {
        $this->markTestIncomplete();
    }

    public function test_limiting_by_purchase_date()
    {
        $this->markTestIncomplete();
    }

    public function test_limiting_by_quantity()
    {
        $this->markTestIncomplete();
    }

    public function test_limiting_by_minimum_quantity()
    {
        $this->markTestIncomplete();
    }

    public function test_limiting_by_unit_cost()
    {
        $this->markTestIncomplete();
    }

    public function test_limiting_by_checkout_date()
    {
        $this->markTestIncomplete();
    }

    public function test_limiting_by_created_at()
    {
        $this->markTestIncomplete();
    }

    public function test_limiting_by_updated_at()
    {
        $this->markTestIncomplete();
    }

    public function test_limiting_by_updated_before()
    {
        $this->markTestIncomplete();
    }

    public function test_limiting_by_excluding_deleted_components()
    {
        $this->markTestIncomplete();
    }

    public function test_limiting_by_including_deleted_components()
    {
        $this->markTestIncomplete();
    }

    public function test_limiting_by_only_deleted_components()
    {
        $this->markTestIncomplete();
    }

    public function test_custom_component_report_exclude_deleted()
    {
        $this->markTestIncomplete();
    }

    public function test_custom_component_report_adheres_to_company_scoping()
    {
        $this->markTestIncomplete();
    }
}
