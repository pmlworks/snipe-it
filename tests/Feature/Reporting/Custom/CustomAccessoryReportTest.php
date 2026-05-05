<?php

namespace Tests\Feature\Reporting\Custom;

use App\Models\Accessory;
use App\Models\Company;
use App\Models\ReportTemplate;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('custom-reporting')]
class CustomAccessoryReportTest extends TestCase
{
    public function test_requires_permission_to_view_page()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reports.custom.accessory'))
            ->assertForbidden();
    }

    public function test_requires_permission_to_run_report()
    {
        $this->actingAs(User::factory()->create())
            ->post(route('reports.custom.accessory.run'), [
                //
            ])
            ->assertForbidden();
    }

    public function test_can_load_custom_report_page()
    {
        $this->actingAs(User::factory()->canViewReports()->create())
            ->get(route('reports.custom.accessory'))
            ->assertOk();
    }

    public function test_saved_templates_on_page_are_scoped_to_the_user_and_type()
    {
        // Given there are saved templates for one user
        ReportTemplate::factory()->create(['type' => 'asset', 'name' => 'Another User: Asset']);
        ReportTemplate::factory()->create(['type' => 'accessory', 'name' => 'Another User: Accessory']);

        // When loading reports.custom.accessory while acting as another user that also has saved templates
        $user = User::factory()->canViewReports()
            ->has(ReportTemplate::factory(['type' => 'asset', 'name' => 'User: Asset']))
            ->has(ReportTemplate::factory(['type' => 'accessory', 'name' => 'User: Accessory']))
            ->create();

        $response = $this->actingAs($user)->get(route('reports.custom.accessory'));

        $viewTemplateNames = $response->viewData('report_templates')->pluck('name');

        // The user should only see their accessory template
        $this->assertTrue($viewTemplateNames->contains('User: Accessory'));
        $this->assertTrue($viewTemplateNames->doesntContain('User: Asset'));
        $this->assertTrue($viewTemplateNames->doesntContain('Another User: Asset'));
        $this->assertTrue($viewTemplateNames->doesntContain('Another User: Accessory'));
    }

    public function test_custom_accessory_report()
    {
        $this->markTestIncomplete();

        Accessory::factory()->create(['name' => 'Accessory A']);
        Accessory::factory()->create(['name' => 'Accessory B']);

        $this->actingAs(User::factory()->canViewReports()->create())
            ->post(route('reports.custom.accessory.run'), [
                // todo:
            ])->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=utf-8')
            ->assertSeeTextInStreamedResponse('Accessory A')
            ->assertSeeTextInStreamedResponse('Accessory B');
    }

    public function test_custom_accessory_report_headers()
    {
        $this->markTestIncomplete();

        $this->actingAs(User::factory()->canViewReports()->create())
            ->post(route('reports.custom.accessory.run'), [
                'id' => '1',
                'company' => '1',
                'accessory_name' => '1',
                'manufacturer' => '1',
                'model' => '1',
                'category' => '1',
                'purchase_date' => '1',
                'purchase_cost' => '1',
                'order' => '1',
                'quantity' => '1',
                'supplier' => '1',
                'location' => '1',
                'location_address' => '1',
                'min_amount' => '1',
                'checkout_date' => '1',
                'created_at' => '1',
                'updated_at' => '1',
                'deleted_at' => '1',
                'notes' => '1',
                'assigned_to' => '1',
                'username' => '1',
                'user_company' => '1',
                'email' => '1',
                'employee_num' => '1',
                'manager' => '1',
                'department' => '1',
                'title' => '1',
                'phone' => '1',
                'user_address' => '1',
                'user_city' => '1',
                'user_state' => '1',
                'user_country' => '1',
                'user_zip' => '1',
                'target_notes' => '1',

            ])->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=utf-8')
            ->assertSeeTextInStreamedResponse(trans('general.id'))
            ->assertSeeTextInStreamedResponse(trans('general.company'))
            ->assertSeeTextInStreamedResponse(trans('admin/accessories/general.accessory_name'))
            ->assertSeeTextInStreamedResponse(trans('general.manufacturer'))
            ->assertSeeTextInStreamedResponse(trans('general.model_no'))
            ->assertSeeTextInStreamedResponse(trans('general.category'))
            ->assertSeeTextInStreamedResponse(trans('admin/licenses/table.purchase_date'))
            ->assertSeeTextInStreamedResponse(trans('admin/hardware/form.cost'))
            ->assertSeeTextInStreamedResponse(trans('admin/hardware/form.order'))
            ->assertSeeTextInStreamedResponse(trans('general.quantity'))
            ->assertSeeTextInStreamedResponse(trans('general.suppliers'))
            ->assertSeeTextInStreamedResponse(trans('general.location'))
            ->assertSeeTextInStreamedResponse(trans('general.address'))
            ->assertSeeTextInStreamedResponse(trans('general.min_amt'))
            ->assertSeeTextInStreamedResponse(trans('admin/hardware/table.checkout_date'))
            ->assertSeeTextInStreamedResponse(trans('general.created_at'))
            ->assertSeeTextInStreamedResponse(trans('general.updated_at'))
            ->assertSeeTextInStreamedResponse(trans('general.deleted'))
            ->assertSeeTextInStreamedResponse(trans('general.notes'))
            ->assertSeeTextInStreamedResponse(trans('general.checked_out_to_fields'))
            ->assertSeeTextInStreamedResponse(trans('admin/licenses/table.assigned_to'))
            ->assertSeeTextInStreamedResponse(trans('admin/users/table.username'))
            ->assertSeeTextInStreamedResponse(trans('admin/reports/general.custom_export.user_company'))
            ->assertSeeTextInStreamedResponse(trans('admin/users/table.email'))
            ->assertSeeTextInStreamedResponse(trans('general.employee_number'))
            ->assertSeeTextInStreamedResponse(trans('admin/users/table.manager'))
            ->assertSeeTextInStreamedResponse(trans('general.department'))
            ->assertSeeTextInStreamedResponse(trans('admin/users/table.title'))
            ->assertSeeTextInStreamedResponse(trans('admin/users/table.phone'))
            ->assertSeeTextInStreamedResponse(trans('general.address'))
            ->assertSeeTextInStreamedResponse(trans('general.city'))
            ->assertSeeTextInStreamedResponse(trans('general.state'))
            ->assertSeeTextInStreamedResponse(trans('general.country'))
            ->assertSeeTextInStreamedResponse(trans('general.zip'))
            ->assertSeeTextInStreamedResponse(trans('admin/reports/general.custom_export.target_notes'));
    }

    public function test_custom_accessory_report_content()
    {
        $this->markTestIncomplete();
        // todo: might be merged with the test above
    }

    public function test_custom_accessory_report_exclude_deleted()
    {
        $this->markTestIncomplete();
    }

    public function test_custom_accessory_report_adheres_to_company_scoping()
    {
        $this->markTestIncomplete();

        [$companyA, $companyB] = Company::factory()->count(2)->create()->all();

        Accessory::factory()->for($companyA)->create(['name' => 'Accessory A']);
        Accessory::factory()->for($companyB)->create(['name' => 'Accessory B']);

        $superUser = $companyA->users()->save(User::factory()->superuser()->make());
        $userInCompanyA = $companyA->users()->save(User::factory()->canViewReports()->make());
        $userInCompanyB = $companyB->users()->save(User::factory()->canViewReports()->make());

        $this->settings->disableMultipleFullCompanySupport();

        $this->actingAs($superUser)
            ->post(route('reports.custom.accessory.run'), ['asset_name' => '1'])
            ->assertSeeTextInStreamedResponse('Accessory A')
            ->assertSeeTextInStreamedResponse('Accessory B');

        $this->actingAs($userInCompanyA)
            ->post(route('reports.custom.accessory.run'), ['asset_name' => '1'])
            ->assertSeeTextInStreamedResponse('Accessory A')
            ->assertSeeTextInStreamedResponse('Accessory B');

        $this->actingAs($userInCompanyB)
            ->post(route('reports.custom.accessory.run'), ['asset_name' => '1'])
            ->assertSeeTextInStreamedResponse('Accessory A')
            ->assertSeeTextInStreamedResponse('Accessory B');

        $this->settings->enableMultipleFullCompanySupport();

        $this->actingAs($superUser)
            ->post(route('reports.custom.accessory.run'), ['asset_name' => '1'])
            ->assertSeeTextInStreamedResponse('Accessory A')
            ->assertSeeTextInStreamedResponse('Accessory B');

        $this->actingAs($userInCompanyA)
            ->post(route('reports.custom.accessory.run'), ['asset_name' => '1'])
            ->assertSeeTextInStreamedResponse('Accessory A')
            ->assertDontSeeTextInStreamedResponse('Accessory B');

        $this->actingAs($userInCompanyB)
            ->post(route('reports.custom.accessory.run'), ['asset_name' => '1'])
            ->assertDontSeeTextInStreamedResponse('Accessory A')
            ->assertSeeTextInStreamedResponse('Accessory B');
    }
}
