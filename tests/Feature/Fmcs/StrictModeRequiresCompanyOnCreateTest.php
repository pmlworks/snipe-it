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
