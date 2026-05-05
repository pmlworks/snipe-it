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
