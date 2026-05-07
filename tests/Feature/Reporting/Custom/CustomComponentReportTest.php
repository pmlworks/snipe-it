<?php

namespace Tests\Feature\Reporting\Custom;

use App\Models\Category;
use App\Models\Company;
use App\Models\Component;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\ReportTemplate;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('custom-reporting')]
class CustomComponentReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // todo: remove
        Model::preventLazyLoading();
    }

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

    public function test_custom_component_report_validation()
    {
        $this->markTestIncomplete();

        // todo: purchase_start and purchase_end
    }

    public function test_custom_component_report_headers()
    {
        $this->sendRequest([
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
            ->assertCsvHeader()
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
        $this->sendRequest([
            'id' => '1',
            'component_name' => '1',
            // company and category intentionally omitted
        ])
            ->assertOk()
            ->assertCsvHeader()
            ->assertDontSeeTextInStreamedResponse([
                trans('general.company'),
                trans('general.category'),
            ]);
    }

    public function test_custom_component_report_contents()
    {
        $this->markTestIncomplete();

        // todo: ensure only the items checked are included in the output
    }

    public function test_limiting_by_company()
    {
        [$companyA, $companyB] = Company::factory()->count(2)->create()->all();

        Component::factory()
            ->count(2)
            ->sequence(
                ['company_id' => $companyA->id, 'name' => 'Component for Company A'],
                ['company_id' => $companyB->id, 'name' => 'Component for Company B'],
            )
            ->create();

        $this->sendRequest([
            'component_name' => '1',
            'company' => '1',
            'by_company_id' => [
                $companyA->id,
            ],
        ])
            ->assertOk()
            ->assertCsvHeader()
            ->assertSeeTextInStreamedResponse('Component for Company A')
            ->assertDontSeeTextInStreamedResponse('Component for Company B');
    }

    public function test_limiting_by_category()
    {
        [$categoryA, $categoryB] = Category::factory()->count(2)->create()->all();

        Component::factory()
            ->count(2)
            ->sequence(
                ['category_id' => $categoryA->id, 'name' => 'Component for Category A'],
                ['category_id' => $categoryB->id, 'name' => 'Component for Category B'],
            )
            ->create();

        $this->sendRequest([
            'component_name' => '1',
            'category' => '1',
            'by_category_id' => [
                $categoryA->id,
            ],
        ])
            ->assertOk()
            ->assertCsvHeader()
            ->assertSeeTextInStreamedResponse('Component for Category A')
            ->assertDontSeeTextInStreamedResponse('Component for Category B');
    }

    public function test_limiting_by_manufacturer()
    {
        [$manufacturerA, $manufacturerB] = Manufacturer::factory()->count(2)->create()->all();

        Component::factory()
            ->count(2)
            ->sequence(
                ['manufacturer_id' => $manufacturerA->id, 'name' => 'Component for Manufacturer A'],
                ['manufacturer_id' => $manufacturerB->id, 'name' => 'Component for Manufacturer B'],
            )
            ->create();

        $this->sendRequest([
            'component_name' => '1',
            'manufacturer' => '1',
            'by_manufacturer_id' => [
                $manufacturerA->id,
            ],
        ])
            ->assertOk()
            ->assertCsvHeader()
            ->assertSeeTextInStreamedResponse('Component for Manufacturer A')
            ->assertDontSeeTextInStreamedResponse('Component for Manufacturer B');
    }

    public function test_limiting_by_supplier()
    {
        [$supplierA, $supplierB] = Supplier::factory()->count(2)->create()->all();

        Component::factory()
            ->count(2)
            ->sequence(
                ['supplier_id' => $supplierA->id, 'name' => 'Component for Supplier A'],
                ['supplier_id' => $supplierB->id, 'name' => 'Component for Supplier B'],
            )
            ->create();

        $this->sendRequest([
            'component_name' => '1',
            'supplier' => '1',
            'by_supplier_id' => [
                $supplierA->id,
            ],
        ])
            ->assertOk()
            ->assertCsvHeader()
            ->assertSeeTextInStreamedResponse('Component for Supplier A')
            ->assertDontSeeTextInStreamedResponse('Component for Supplier B');
    }

    public function test_limiting_by_location()
    {
        [$locationA, $locationB] = Location::factory()->count(2)->create()->all();

        Component::factory()
            ->count(2)
            ->sequence(
                ['location_id' => $locationA->id, 'name' => 'Component for Location A'],
                ['location_id' => $locationB->id, 'name' => 'Component for Location B'],
            )
            ->create();

        $this->sendRequest([
            'component_name' => '1',
            'location' => '1',
            'by_location_id' => [
                $locationA->id,
            ],
        ])
            ->assertOk()
            ->assertCsvHeader()
            ->assertSeeTextInStreamedResponse('Component for Location A')
            ->assertDontSeeTextInStreamedResponse('Component for Location B');
    }

    public function test_limiting_by_name()
    {
        Component::factory()->create(['name' => 'RAM']);
        Component::factory()->create(['name' => 'Hard Drive']);

        $this->sendRequest([
            'component_name' => '1',
            'by_name' => 'RAM',
        ])
            ->assertOk()
            ->assertCsvHeader()
            ->assertSeeTextInStreamedResponse('RAM')
            ->assertDontSeeTextInStreamedResponse('Hard Drive');
    }

    public function test_limiting_by_model_number()
    {
        Component::factory()->create(['model_number' => 'MODEL-001']);
        Component::factory()->create(['model_number' => 'MODEL-002']);

        $this->sendRequest([
            'component_name' => '1',
            'model' => '1',
            'by_model_number' => 'MODEL-001',
        ])
            ->assertOk()
            ->assertCsvHeader()
            ->assertSeeTextInStreamedResponse('MODEL-001')
            ->assertDontSeeTextInStreamedResponse('MODEL-002');
    }

    public function test_limiting_by_order_number()
    {
        Component::factory()->create(['order_number' => 'ORD-001']);
        Component::factory()->create(['order_number' => 'ORD-002']);

        $this->sendRequest([
            'component_name' => '1',
            'order' => '1',
            'by_order_number' => 'ORD-001',
        ])
            ->assertOk()
            ->assertCsvHeader()
            ->assertSeeTextInStreamedResponse('ORD-001')
            ->assertDontSeeTextInStreamedResponse('ORD-002');
    }

    public function test_limiting_by_purchase_date()
    {
        Component::factory()->create(['name' => 'Component A', 'purchase_date' => '2024-01-15']);
        Component::factory()->create(['name' => 'Component B', 'purchase_date' => '2024-06-15']);

        $this->sendRequest([
            'component_name' => '1',
            'purchase_start' => '2024-01-01',
            'purchase_end' => '2024-03-31',
        ])
            ->assertOk()
            ->assertCsvHeader()
            ->assertSeeTextInStreamedResponse('Component A')
            ->assertDontSeeTextInStreamedResponse('Component B');
    }

    public function test_limiting_by_quantity()
    {
        Component::factory()->create(['name' => 'Component A', 'qty' => 5]);
        Component::factory()->create(['name' => 'Component B', 'qty' => 50]);

        $this->sendRequest([
            'component_name' => '1',
            'quantity_start' => 1,
            'quantity_end' => 10,
        ])
            ->assertOk()
            ->assertCsvHeader()
            ->assertSeeTextInStreamedResponse('Component A')
            ->assertDontSeeTextInStreamedResponse('Component B');
    }

    public function test_limiting_by_minimum_quantity()
    {
        Component::factory()->create(['name' => 'Component A', 'min_amt' => 2]);
        Component::factory()->create(['name' => 'Component B', 'min_amt' => 20]);

        $this->sendRequest([
            'component_name' => '1',
            'min_quantity_start' => 1,
            'min_quantity_end' => 5,
        ])
            ->assertOk()
            ->assertCsvHeader()
            ->assertSeeTextInStreamedResponse('Component A')
            ->assertDontSeeTextInStreamedResponse('Component B');
    }

    public function test_limiting_by_unit_cost()
    {
        $this->markTestIncomplete();

        Component::factory()->create(['name' => 'Component A', 'purchase_cost' => 10.00]);
        Component::factory()->create(['name' => 'Component B', 'purchase_cost' => 500.00]);

        $this->sendRequest([
            'component_name' => '1',
            'unit_cost_start' => 1,
            'unit_cost_end' => 50,
        ])
            ->assertOk()
            ->assertCsvHeader()
            ->assertSeeTextInStreamedResponse('Component A')
            ->assertDontSeeTextInStreamedResponse('Component B');
    }

    public function test_limiting_by_checkout_date()
    {
        $this->markTestIncomplete();
    }

    public function test_limiting_by_created_at()
    {
        $this->markTestIncomplete();

        $this->travel(-60)->days(function () {
            Component::factory()->create(['name' => 'Component A']);
        });

        Component::factory()->create(['name' => 'Component B']);

        $this->sendRequest([
            'component_name' => '1',
            'created_start' => now()->subDays(90)->toDateString(),
            'created_end' => now()->subDays(30)->toDateString(),
        ])
            ->assertOk()
            ->assertCsvHeader()
            ->assertSeeTextInStreamedResponse('Component A')
            ->assertDontSeeTextInStreamedResponse('Component B');
    }

    public function test_limiting_by_updated_at()
    {
        $this->markTestIncomplete();

        $this->travel(-60)->days(function () {
            Component::factory()->create(['name' => 'Component A']);
        });

        Component::factory()->create(['name' => 'Component B']);

        $this->sendRequest([
            'component_name' => '1',
            'last_updated_start' => now()->subDays(90)->toDateString(),
            'last_updated_end' => now()->subDays(30)->toDateString(),
        ])
            ->assertOk()
            ->assertCsvHeader()
            ->assertSeeTextInStreamedResponse('Component A')
            ->assertDontSeeTextInStreamedResponse('Component B');
    }

    public function test_limiting_by_updated_before()
    {
        $this->markTestIncomplete();

        $this->travel(-60)->days(function () {
            Component::factory()->create(['name' => 'Component A']);
        });

        Component::factory()->create(['name' => 'Component B']);

        $this->sendRequest([
            'component_name' => '1',
            'last_updated_before' => 30,
        ])
            ->assertOk()
            ->assertCsvHeader()
            ->assertSeeTextInStreamedResponse('Component A')
            ->assertDontSeeTextInStreamedResponse('Component B');
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

    private function sendRequest(array $data): TestResponse
    {
        return $this->actingAs(User::factory()->canViewReports()->create())
            ->post(route('reports.custom.component.run'), $data);
    }
}
