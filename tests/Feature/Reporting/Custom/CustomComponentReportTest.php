<?php

namespace Tests\Feature\Reporting\Custom;

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

    public function test_custom_component_report()
    {
        $this->markTestIncomplete();
    }

    public function test_custom_component_report_headers()
    {
        $this->markTestIncomplete();
    }

    public function test_custom_component_report_content()
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
