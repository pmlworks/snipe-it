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
}
