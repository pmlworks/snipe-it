<?php

namespace Tests\Feature\Companies\Ui;

use App\Models\Company;
use App\Models\User;
use Tests\TestCase;

class ShowCompanyTest extends TestCase
{
    public function test_page_renders()
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('companies.show', Company::factory()->create()))
            ->assertOk();
    }

    public function test_show_page_includes_parent_company_breadcrumb_hierarchy()
    {
        $parent = Company::factory()->create(['name' => 'Parent Breadcrumb Company']);
        $child = Company::factory()->childOf($parent)->create(['name' => 'Child Breadcrumb Company']);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('companies.show', $child))
            ->assertOk()
            ->assertSeeInOrder([
                route('companies.show', $parent),
                route('companies.show', $child),
            ]);
    }

    public function test_sidebar_shows_children_for_parent_company()
    {
        $parent = Company::factory()->create(['name' => 'Parent Sidebar Co']);
        $childA = Company::factory()->childOf($parent)->create(['name' => 'Aardvark Subsidiary']);
        $childB = Company::factory()->childOf($parent)->create(['name' => 'Zebra Subsidiary']);
        $unrelated = Company::factory()->create(['name' => 'Unrelated Co']);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('companies.show', $parent))
            ->assertOk()
            ->assertSee(trans('admin/companies/table.hierarchy'))
            ->assertSee($childA->name)
            ->assertSee($childB->name)
            ->assertDontSee($unrelated->name);
    }

    public function test_sidebar_shows_parent_for_child_company()
    {
        $parent = Company::factory()->create(['name' => 'Top Level Sidebar Co']);
        $child = Company::factory()->childOf($parent)->create(['name' => 'Subsidiary Sidebar Co']);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('companies.show', $child))
            ->assertOk()
            ->assertSee(trans('admin/companies/table.hierarchy'))
            ->assertSee($parent->name);
    }

    public function test_sidebar_hierarchy_box_hidden_when_company_is_standalone()
    {
        $solo = Company::factory()->create(['name' => 'Standalone Sidebar Co']);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('companies.show', $solo))
            ->assertOk()
            ->assertDontSee(trans('admin/companies/table.hierarchy'));
    }
}
