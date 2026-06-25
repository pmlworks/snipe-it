<?php

namespace Tests\Feature\Users\Ui;

use App\Models\Company;
use App\Models\User;
use Tests\TestCase;

/**
 * #19200: with floater mode on, a non-superuser with users.edit could blank a
 * user's company assignments and promote that user (often themselves) to a
 * system-wide floater. SaveUserRequest::withValidator and BulkUsersController
 * both refuse the operation; only superusers can intentionally make a floater.
 */
class FloaterModeGateTest extends TestCase
{
    public function test_non_superuser_cannot_blank_their_own_companies_in_floater_mode()
    {
        $this->settings->enableFloaterMode();

        $company = Company::factory()->create();
        $actor = $company->users()->save(User::factory()->editUsers()->create());

        $this->actingAs($actor)
            ->put(route('users.update', $actor), [
                'first_name' => $actor->first_name,
                'username' => $actor->username,
                'company_ids' => [],
            ])
            ->assertSessionHasErrors('company_ids');

        $this->assertContains($company->id, $actor->fresh()->companies->pluck('id')->all(), 'Actor should still belong to original company');
        $this->assertNotEmpty($actor->fresh()->companies, 'Actor pivot should not be wiped');
    }

    public function test_non_superuser_cannot_blank_another_users_companies_in_floater_mode()
    {
        $this->settings->enableFloaterMode();

        $company = Company::factory()->create();
        $actor = $company->users()->save(User::factory()->editUsers()->create());
        $victim = $company->users()->save(User::factory()->create());

        $this->actingAs($actor)
            ->put(route('users.update', $victim), [
                'first_name' => $victim->first_name,
                'username' => $victim->username,
                'company_ids' => [],
            ])
            ->assertSessionHasErrors('company_ids');

        $this->assertContains($company->id, $victim->fresh()->companies->pluck('id')->all());
        $this->assertNotEmpty($victim->fresh()->companies, 'Victim pivot should not be wiped');
    }

    public function test_superuser_can_save_a_user_with_no_companies_in_floater_mode()
    {
        $this->settings->enableFloaterMode();

        $company = Company::factory()->create();
        $superuser = User::factory()->superuser()->create();
        $target = $company->users()->save(User::factory()->create());

        $this->actingAs($superuser)
            ->put(route('users.update', $target), [
                'first_name' => $target->first_name,
                'username' => $target->username,
                'company_ids' => [],
            ])
            ->assertSessionDoesntHaveErrors('company_ids');

        $this->assertEmpty($target->fresh()->companies->pluck('id')->all(), 'Superuser is trusted to grant floater status');
    }

    public function test_guard_does_not_apply_when_floater_mode_is_off()
    {
        $this->settings->enableMultipleFullCompanySupport();
        $this->settings->disableFloaterMode();

        $company = Company::factory()->create();
        $actor = $company->users()->save(User::factory()->editUsers()->create());

        $this->actingAs($actor)
            ->put(route('users.update', $actor), [
                'first_name' => $actor->first_name,
                'username' => $actor->username,
                'company_ids' => [],
            ])
            ->assertSessionDoesntHaveErrors('company_ids');
    }

    public function test_non_superuser_cannot_bulk_clear_companies_in_floater_mode()
    {
        $this->settings->enableFloaterMode();

        $company = Company::factory()->create();
        $actor = $company->users()->save(User::factory()->editUsers()->create());
        $victim = $company->users()->save(User::factory()->create());

        $this->actingAs($actor)
            ->post(route('users/bulkeditsave'), [
                'ids' => [$victim->id => '1'],
                'null_company_ids' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertContains($company->id, $victim->fresh()->companies->pluck('id')->all(), 'Bulk clear should be refused');
        $this->assertNotEmpty($victim->fresh()->companies);
    }

    public function test_uncompanied_non_superuser_cannot_save_user_with_no_companies()
    {
        // Per spec: under floater mode, ONLY superusers may grant floater
        // status. An uncompanied non-superuser (themselves a floater) was
        // previously trusted to manage other floaters — that's now closed off
        // because it lets the chain "clear pivot → reset target's password
        // (via canEditAuthFields gaps) → log in as a new floater" complete.
        $this->settings->enableFloaterMode();

        $actor = User::factory()->editUsers()->create();
        $actor->companies()->sync([]);
        $target = User::factory()->create();
        $target->companies()->sync([]);

        $this->actingAs($actor)
            ->put(route('users.update', $target), [
                'first_name' => $target->first_name,
                'username' => $target->username,
                'company_ids' => [],
            ])
            ->assertSessionHasErrors('company_ids');
    }

    public function test_non_superuser_can_change_their_companies_to_a_non_empty_set_in_floater_mode()
    {
        $this->settings->enableFloaterMode();

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $actor = User::factory()->editUsers()->create();
        $actor->companies()->sync([$companyA->id, $companyB->id]);

        // Drop B, keep A — still has memberships, so not becoming a floater.
        $this->actingAs($actor)
            ->put(route('users.update', $actor), [
                'first_name' => $actor->first_name,
                'username' => $actor->username,
                'company_ids' => [$companyA->id],
            ])
            ->assertSessionDoesntHaveErrors('company_ids');

        $freshIds = $actor->fresh()->companies->pluck('id')->all();
        $this->assertContains($companyA->id, $freshIds);
        $this->assertNotContains($companyB->id, $freshIds);
    }
}
