<?php

namespace Tests\Feature\Companies\Api;

use App\Models\Asset;
use App\Models\Company;
use App\Models\User;
use Tests\TestCase;

class CompanyHierarchyTest extends TestCase
{
    public function test_can_create_company_with_parent_id()
    {
        $parent = Company::factory()->create();

        $this->actingAsForApi(User::factory()->createCompanies()->create())
            ->postJson(route('api.companies.store'), [
                'name' => 'Subsidiary',
                'parent_id' => $parent->id,
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('success');

        $this->assertDatabaseHas('companies', [
            'name' => 'Subsidiary',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_can_update_company_to_set_parent_id()
    {
        $parent = Company::factory()->create();
        $orphan = Company::factory()->create();

        $this->actingAsForApi(User::factory()->editCompanies()->create())
            ->patchJson(route('api.companies.update', ['company' => $orphan->id]), [
                'name' => $orphan->name,
                'parent_id' => $parent->id,
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('success');

        $this->assertEquals($parent->id, $orphan->fresh()->parent_id);
    }

    public function test_company_cannot_be_its_own_parent()
    {
        $company = Company::factory()->create();

        $this->actingAsForApi(User::factory()->editCompanies()->create())
            ->patchJson(route('api.companies.update', ['company' => $company->id]), [
                'name' => $company->name,
                'parent_id' => $company->id,
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('error')
            ->assertJsonStructure(['messages' => ['parent_id']]);

        $this->assertNull($company->fresh()->parent_id);
    }

    public function test_cannot_create_grandchild_company()
    {
        // Grandparent → Parent already exists; trying to make Child the parent of New should fail.
        $grandparent = Company::factory()->create();
        $parent = Company::factory()->childOf($grandparent)->create();

        $this->actingAsForApi(User::factory()->createCompanies()->create())
            ->postJson(route('api.companies.store'), [
                'name' => 'GreatGrandchild',
                'parent_id' => $parent->id,
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('error')
            ->assertJsonStructure(['messages' => ['parent_id']]);

        $this->assertDatabaseMissing('companies', ['name' => 'GreatGrandchild']);
    }

    public function test_cannot_assign_parent_to_company_that_has_children()
    {
        $grandparentCandidate = Company::factory()->create();
        $parent = Company::factory()->create();
        Company::factory()->childOf($parent)->create();

        $this->actingAsForApi(User::factory()->editCompanies()->create())
            ->patchJson(route('api.companies.update', ['company' => $parent->id]), [
                'name' => $parent->name,
                'parent_id' => $grandparentCandidate->id,
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('error')
            ->assertJsonStructure(['messages' => ['parent_id']]);

        $this->assertNull($parent->fresh()->parent_id);
    }

    public function test_can_clear_parent_id_by_passing_null()
    {
        $parent = Company::factory()->create();
        $child = Company::factory()->childOf($parent)->create();

        $this->actingAsForApi(User::factory()->editCompanies()->create())
            ->patchJson(route('api.companies.update', ['company' => $child->id]), [
                'name' => $child->name,
                'parent_id' => null,
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('success');

        $this->assertNull($child->fresh()->parent_id);
    }

    public function test_parent_id_must_reference_existing_company()
    {
        $this->actingAsForApi(User::factory()->createCompanies()->create())
            ->postJson(route('api.companies.store'), [
                'name' => 'Orphaned',
                'parent_id' => 999999,
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('error')
            ->assertJsonStructure(['messages' => ['parent_id']]);
    }

    public function test_cannot_delete_company_that_has_children()
    {
        $parent = Company::factory()->create();
        Company::factory()->childOf($parent)->create();

        $this->actingAsForApi(User::factory()->deleteCompanies()->create())
            ->deleteJson(route('api.companies.destroy', $parent))
            ->assertStatusMessageIs('error');

        $this->assertDatabaseHas('companies', ['id' => $parent->id, 'deleted_at' => null]);
    }

    public function test_index_exposes_parent_and_children_count()
    {
        $parent = Company::factory()->create();
        Company::factory()->count(2)->childOf($parent)->create();

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(route('api.companies.index', ['search' => $parent->name]))
            ->assertOk()
            ->json();

        $parentRow = collect($response['rows'])->firstWhere('id', $parent->id);

        $this->assertNotNull($parentRow);
        $this->assertNull($parentRow['parent']);
        $this->assertEquals(2, $parentRow['children_count']);
    }

    public function test_show_exposes_parent_block_for_child_company()
    {
        $parent = Company::factory()->create();
        $child = Company::factory()->childOf($parent)->create();

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(route('api.companies.show', $child))
            ->assertOk()
            ->json();

        $this->assertEquals($parent->id, $response['parent']['id']);
        $this->assertEquals($parent->name, $response['parent']['name']);
    }

    public function test_user_in_parent_company_has_fmcs_access_to_child_company_assets()
    {
        $this->settings->enableMultipleFullCompanySupport();

        $parent = Company::factory()->create();
        $child = Company::factory()->childOf($parent)->create();

        // Asset belongs to the child company.
        $assetInChild = Asset::factory()->create(['company_id' => $child->id]);

        // User is a member of the parent only.
        $userInParent = $parent->users()->save(User::factory()->viewAssets()->create());

        $response = $this->actingAsForApi($userInParent)
            ->getJson(route('api.assets.index'))
            ->assertOk()
            ->json();

        $foundIds = collect($response['rows'])->pluck('id')->all();

        $this->assertContains($assetInChild->id, $foundIds, 'User in parent should see child-company assets');
    }

    public function test_user_in_child_company_does_not_see_parent_company_assets()
    {
        $this->settings->enableMultipleFullCompanySupport();

        $parent = Company::factory()->create();
        $child = Company::factory()->childOf($parent)->create();

        $assetInParent = Asset::factory()->create(['company_id' => $parent->id]);

        $userInChild = $child->users()->save(User::factory()->viewAssets()->create());

        $response = $this->actingAsForApi($userInChild)
            ->getJson(route('api.assets.index'))
            ->assertOk()
            ->json();

        $foundIds = collect($response['rows'])->pluck('id')->all();

        $this->assertNotContains($assetInParent->id, $foundIds, 'User in child should not see parent-company assets');
    }

    public function test_user_in_parent_can_filter_companies_selectlist_and_see_children()
    {
        $this->settings->enableMultipleFullCompanySupport();

        $parent = Company::factory()->create();
        $child = Company::factory()->childOf($parent)->create();
        $unrelated = Company::factory()->create();

        // view.selectlists is gated on having create/update on at least one resource.
        $userInParent = $parent->users()->save(User::factory()->createAssets()->create());

        $response = $this->actingAsForApi($userInParent)
            ->getJson(route('api.companies.selectlist'))
            ->assertOk()
            ->json();

        $ids = collect($response['results'])->pluck('id')->all();

        $this->assertContains($parent->id, $ids);
        $this->assertContains($child->id, $ids);
        $this->assertNotContains($unrelated->id, $ids);
    }

    public function test_fmcs_floater_mode_still_works_with_hierarchy()
    {
        // Floater mode: items with NULL company_id are visible to everyone.
        // A user in a parent company should still see floater items in addition
        // to their parent's items AND their child-company items.
        $this->settings->enableFloaterMode();

        $parent = Company::factory()->create();
        $child = Company::factory()->childOf($parent)->create();

        $assetInParent = Asset::factory()->create(['company_id' => $parent->id]);
        $assetInChild = Asset::factory()->create(['company_id' => $child->id]);
        $floaterAsset = Asset::factory()->create(['company_id' => null]);

        $userInParent = $parent->users()->save(User::factory()->viewAssets()->create());

        $response = $this->actingAsForApi($userInParent)
            ->getJson(route('api.assets.index'))
            ->assertOk()
            ->json();

        $foundIds = collect($response['rows'])->pluck('id')->all();

        $this->assertContains($assetInParent->id, $foundIds, 'Parent asset should be visible');
        $this->assertContains($assetInChild->id, $foundIds, 'Child asset should be visible via hierarchy');
        $this->assertContains($floaterAsset->id, $foundIds, 'Floater asset should be visible via null_company_is_floater');
    }
}
