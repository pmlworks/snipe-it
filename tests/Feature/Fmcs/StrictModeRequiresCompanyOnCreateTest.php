<?php

namespace Tests\Feature\Fmcs;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\Department;
use App\Models\License;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Regression coverage for GitHub issue #19192. When FMCS is enabled and
 * "treat items and users without company associations as floaters" is
 * OFF (strict mode), a non-superuser could save a create-form with the
 * Company field left blank; the row landed with company_id=NULL and
 * was instantly filtered out of that user's own view. The fmcs_company
 * validator + strict-mode branch in SaveUserRequest close both paths.
 *
 * Per Snipe's FMCS adversarial-tests memory: cover strict + floater +
 * non-FMCS with superuser and non-superuser actors. The validator itself
 * is exercised in isolation (not against each model's full $rules) so
 * unrelated pseudo-rules like License::limit_change don't crash the
 * runner — a separate sanity test then asserts every affected model's
 * $rules['company_id'] carries the fmcs_company rule.
 */
class StrictModeRequiresCompanyOnCreateTest extends TestCase
{
    // ------------------------------------------------------------------
    // fmcs_company validator behavior — in isolation
    // ------------------------------------------------------------------

    public function test_rule_rejects_null_in_strict_fmcs_for_non_superuser()
    {
        $this->settings->enableMultipleFullCompanySupport();
        $this->settings->disableFloaterMode();
        auth()->login(User::factory()->create());

        $validator = Validator::make(['company_id' => null], ['company_id' => 'fmcs_company']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('company_id', $validator->errors()->toArray());
    }

    public function test_rule_accepts_null_in_strict_fmcs_for_superuser()
    {
        $this->settings->enableMultipleFullCompanySupport();
        $this->settings->disableFloaterMode();
        auth()->login(User::factory()->superuser()->create());

        $validator = Validator::make(['company_id' => null], ['company_id' => 'fmcs_company']);

        $this->assertFalse($validator->fails());
    }

    public function test_rule_accepts_null_when_floater_mode_enabled()
    {
        $this->settings->enableFloaterMode();
        auth()->login(User::factory()->create());

        $validator = Validator::make(['company_id' => null], ['company_id' => 'fmcs_company']);

        $this->assertFalse($validator->fails());
    }

    public function test_rule_accepts_null_when_fmcs_off()
    {
        $this->settings->disableMultipleFullCompanySupport();
        auth()->login(User::factory()->create());

        $validator = Validator::make(['company_id' => null], ['company_id' => 'fmcs_company']);

        $this->assertFalse($validator->fails());
    }

    public function test_rule_accepts_non_null_in_strict_fmcs_for_non_superuser()
    {
        $this->settings->enableMultipleFullCompanySupport();
        $this->settings->disableFloaterMode();
        auth()->login(User::factory()->create());
        $company = Company::factory()->create();

        $validator = Validator::make(['company_id' => $company->id], ['company_id' => 'fmcs_company']);

        $this->assertFalse($validator->fails());
    }

    public function test_rule_accepts_null_when_no_auth_context()
    {
        // CLI / seeders / importers deliberately bypass — same posture
        // as the SaveUserRequest cannot_make_floater gate.
        $this->settings->enableMultipleFullCompanySupport();
        $this->settings->disableFloaterMode();
        auth()->logout();

        $validator = Validator::make(['company_id' => null], ['company_id' => 'fmcs_company']);

        $this->assertFalse($validator->fails());
    }

    public function test_rule_accepts_null_for_uncompanied_non_superuser_in_strict_mode()
    {
        // Regression guard for the pseudo-company workflow. Under
        // Company::scopeCompanyablesDirectly in strict mode, actors
        // with no company memberships are scoped to null-company rows
        // (whereNull($column)). Null IS a valid company id for them —
        // forcing them to pick a non-null company would both lock them
        // out of their normal workflow AND produce a row they wouldn't
        // be able to see afterward.
        $this->settings->enableMultipleFullCompanySupport();
        $this->settings->disableFloaterMode();

        $actor = User::factory()->withoutCompany()->create();
        $this->assertFalse($actor->companies()->exists(), 'test precondition: actor is uncompanied');
        auth()->login($actor);

        $validator = Validator::make(['company_id' => null], ['company_id' => 'fmcs_company']);

        $this->assertFalse($validator->fails());
    }

    public function test_rule_still_rejects_null_for_companied_non_superuser_in_strict_mode()
    {
        // Reporter's #19192 case: a non-superuser WITH company
        // memberships submitting a null company_id would land an
        // invisible row. That must still fail.
        $this->settings->enableMultipleFullCompanySupport();
        $this->settings->disableFloaterMode();

        $company = Company::factory()->create();
        $actor = $company->users()->save(User::factory()->create());
        $this->assertTrue($actor->companies()->exists(), 'test precondition: actor has memberships');
        auth()->login($actor);

        $validator = Validator::make(['company_id' => null], ['company_id' => 'fmcs_company']);

        $this->assertTrue($validator->fails());
    }

    // ------------------------------------------------------------------
    // Sanity: every model the reporter listed has the rule wired
    // ------------------------------------------------------------------

    /**
     * @dataProvider companyableModelProvider
     */
    public function test_model_rules_include_fmcs_company_for_company_id(string $modelClass)
    {
        $rules = $modelClass::rules();
        $this->assertArrayHasKey('company_id', $rules, $modelClass.' should declare a company_id rule');

        $companyRule = $rules['company_id'];
        $ruleString = is_array($companyRule) ? implode('|', $companyRule) : $companyRule;

        $this->assertStringContainsString(
            'fmcs_company',
            $ruleString,
            $modelClass.'::rules()[company_id] must include the fmcs_company validator so strict-FMCS mode rejects blank submissions',
        );
    }

    public static function companyableModelProvider(): array
    {
        return [
            'Asset' => [Asset::class],
            'License' => [License::class],
            'Accessory' => [Accessory::class],
            'Consumable' => [Consumable::class],
            'Component' => [Component::class],
            'Department' => [Department::class],
            'Location' => [Location::class],
        ];
    }

    // ------------------------------------------------------------------
    // Users: gate lives in SaveUserRequest, not model $rules
    // ------------------------------------------------------------------

    public function test_users_strict_fmcs_rejects_empty_company_ids_for_non_superuser()
    {
        $this->settings->enableMultipleFullCompanySupport();
        $this->settings->disableFloaterMode();

        $actor = User::factory()->create();
        $username = 'strict-null-target-'.uniqid();

        $this->actingAs($actor)
            ->post(route('users.store'), [
                'first_name' => 'Test',
                'last_name' => 'User',
                'username' => $username,
                'email' => $username.'@example.com',
                'password' => 'SomeGreatPassword-123',
                'password_confirmation' => 'SomeGreatPassword-123',
                // No company_ids submitted.
            ])
            ->assertSessionHasErrors('company_ids');

        $this->assertDatabaseMissing('users', ['username' => $username]);
    }

    // ------------------------------------------------------------------
    // Bulk asset edit: same gate reaches through ValidatingTrait
    // ------------------------------------------------------------------

    public function test_bulk_asset_edit_clear_company_is_rejected_for_companied_non_superuser()
    {
        // BulkAssetsController::update() calls $asset->update($updateArray)
        // per row; the model-level fmcs_company rule fires when
        // company_id gets filled to null via the 'clear' bulk option.
        // Locked in here so a future refactor of the bulk controller
        // can't quietly bypass the gate.
        $this->settings->enableMultipleFullCompanySupport();
        $this->settings->disableFloaterMode();

        $company = \App\Models\Company::factory()->create();
        $actor = $company->users()->save(User::factory()->editAssets()->create());
        $target = \App\Models\Asset::factory()->create(['company_id' => $company->id]);

        $this->actingAs($actor)
            ->post(route('hardware/bulksave'), [
                'ids' => [$target->id => '1'],
                'company_id' => 'clear',
                'bulk_actions' => 'edit',
            ]);

        // Row's company should NOT have been cleared.
        $this->assertEquals($company->id, $target->fresh()->company_id);
    }

    public function test_bulk_asset_edit_clear_company_is_allowed_for_uncompanied_non_superuser()
    {
        // Uncompanied non-superusers work in the null pseudo-company
        // namespace under strict mode. Bulk-clearing Company on rows
        // they own is a legitimate operation for them and the gate
        // steps aside.
        $this->settings->enableMultipleFullCompanySupport();
        $this->settings->disableFloaterMode();

        $actor = User::factory()->withoutCompany()->editAssets()->create();
        $target = \App\Models\Asset::factory()->create(['company_id' => null]);

        $this->actingAs($actor)
            ->post(route('hardware/bulksave'), [
                'ids' => [$target->id => '1'],
                'company_id' => 'clear',
                'bulk_actions' => 'edit',
            ]);

        $this->assertNull($target->fresh()->company_id);
    }

    public function test_bulk_asset_edit_unrelated_field_still_works_for_companied_non_superuser()
    {
        // If the bulk edit doesn't touch Company at all, ValidatingTrait
        // sees the existing non-null company_id on each row and passes.
        // This is the "make sure we haven't broken ordinary bulk edits"
        // sanity guard.
        //
        // Defensive Setting cache flush. Some upstream tests in the full
        // MySQL sweep can leave the memoized Setting instance in a state
        // where full_multiple_companies_support looks enabled at the
        // moment auth loads but disabled by the time the fmcs_company
        // validator reads it, or vice versa. The Support helper's update()
        // clears the cache too, but only after both writes have landed.
        // Clearing here first pins the pre-state so the two writes below
        // are the only source of truth for this test's fmcs_company check.
        \App\Models\Setting::$_cache = null;
        $this->settings->enableMultipleFullCompanySupport();
        $this->settings->disableFloaterMode();

        $company = \App\Models\Company::factory()->create();
        $actor = $company->users()->save(User::factory()->editAssets()->create());
        $target = \App\Models\Asset::factory()->create([
            'company_id' => $company->id,
            'notes' => 'before',
        ]);

        $response = $this->actingAs($actor)
            ->post(route('hardware/bulksave'), [
                'ids' => [$target->id => '1'],
                'notes' => 'after',
                'bulk_actions' => 'edit',
            ]);

        // Fail loudly if the controller redirected back with a flash
        // error (the notes assertion below is a downstream symptom and
        // hides the real cause). Seen on MySQL CI as a full-suite flake
        // — pinning the response state up front turns any recurrence
        // into an actionable diagnostic.
        $response->assertSessionMissing('error');

        $this->assertEquals('after', $target->fresh()->notes);
        $this->assertEquals($company->id, $target->fresh()->company_id);
    }

    public function test_users_strict_fmcs_allows_empty_company_ids_for_superuser()
    {
        $this->settings->enableMultipleFullCompanySupport();
        $this->settings->disableFloaterMode();

        $actor = User::factory()->superuser()->create();
        $username = 'super-null-'.uniqid();

        $this->actingAs($actor)
            ->post(route('users.store'), [
                'first_name' => 'Superuser-Created',
                'last_name' => 'User',
                'username' => $username,
                'email' => $username.'@example.com',
                'password' => 'SomeGreatPassword-123',
                'password_confirmation' => 'SomeGreatPassword-123',
            ])
            ->assertSessionHasNoErrors('company_ids');
    }
}
