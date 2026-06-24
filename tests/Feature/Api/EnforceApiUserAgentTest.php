<?php

namespace Tests\Feature\Api;

use App\Models\Setting;
use App\Models\User;
use Laravel\Passport\Passport;
use Tests\TestCase;

class EnforceApiUserAgentTest extends TestCase
{
    public function test_master_off_bails_out_entirely_so_nothing_is_blocked()
    {
        // Master off — even a curl UA AND a blank UA pass through, regardless of
        // any other setting or per-route parameter.
        $this->settings->set([
            'block_api_user_agents' => '0',
            'blocked_api_user_agents' => "curl/\nPostmanRuntime/",
            'block_blank_api_user_agents' => '1',
        ]);

        Passport::actingAs(User::factory()->superuser()->create());

        $this->withHeader('User-Agent', 'curl/8.5.0')
            ->getJson(route('api.users.selectlist'))
            ->assertOk();

        $this->withHeader('User-Agent', '')
            ->getJson(route('api.users.selectlist'))
            ->assertOk();
    }

    public function test_master_on_blocks_matching_pattern_and_echoes_user_agent()
    {
        $this->settings->set([
            'block_api_user_agents' => '1',
            'blocked_api_user_agents' => "curl/\nPostmanRuntime/",
            'block_blank_api_user_agents' => '0',
        ]);

        Passport::actingAs(User::factory()->superuser()->create());

        $this->withHeader('User-Agent', 'curl/8.5.0')
            ->getJson(route('api.users.selectlist'))
            ->assertForbidden()
            ->assertJson([
                'status' => 'error',
                'payload' => ['user_agent' => 'curl/8.5.0'],
            ]);
    }

    public function test_master_on_pattern_match_is_case_insensitive()
    {
        $this->settings->set([
            'block_api_user_agents' => '1',
            'blocked_api_user_agents' => 'curl/',
            'block_blank_api_user_agents' => '0',
        ]);

        Passport::actingAs(User::factory()->superuser()->create());

        $this->withHeader('User-Agent', 'CURL/8.5.0')
            ->getJson(route('api.users.selectlist'))
            ->assertForbidden();
    }

    public function test_master_on_unmatched_user_agent_passes()
    {
        $this->settings->set([
            'block_api_user_agents' => '1',
            'blocked_api_user_agents' => "curl/\nPostmanRuntime/",
            'block_blank_api_user_agents' => '0',
        ]);

        Passport::actingAs(User::factory()->superuser()->create());

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120.0.0.0')
            ->getJson(route('api.users.selectlist'))
            ->assertOk();
    }

    public function test_master_on_blank_blocking_off_lets_blank_user_agent_through_on_api()
    {
        // Pattern blocking on but blank blocking off: an admin who has webhooks/other
        // integrations that send blanks can keep them working.
        $this->settings->set([
            'block_api_user_agents' => '1',
            'blocked_api_user_agents' => 'curl/',
            'block_blank_api_user_agents' => '0',
        ]);

        Passport::actingAs(User::factory()->superuser()->create());

        $this->withHeader('User-Agent', '')
            ->getJson(route('api.users.selectlist'))
            ->assertOk();
    }

    public function test_master_on_blank_blocking_on_rejects_blank_user_agent_on_api()
    {
        $this->settings->set([
            'block_api_user_agents' => '1',
            'blocked_api_user_agents' => null,
            'block_blank_api_user_agents' => '1',
        ]);

        Passport::actingAs(User::factory()->superuser()->create());

        $this->withHeader('User-Agent', '')
            ->getJson(route('api.users.selectlist'))
            ->assertForbidden()
            ->assertJson([
                'payload' => ['user_agent' => ''],
            ]);
    }

    public function test_pattern_is_matched_only_at_start_of_user_agent()
    {
        $this->settings->set([
            'block_api_user_agents' => '1',
            'blocked_api_user_agents' => 'curl/',
            'block_blank_api_user_agents' => '0',
        ]);

        Passport::actingAs(User::factory()->superuser()->create());

        $this->withHeader('User-Agent', 'MyWrapper/1.0 (uses curl/8.5.0 internally)')
            ->getJson(route('api.users.selectlist'))
            ->assertOk();

        $this->withHeader('User-Agent', 'curl/8.5.0')
            ->getJson(route('api.users.selectlist'))
            ->assertForbidden();
    }

    public function test_master_on_empty_pattern_lines_do_not_match_every_request()
    {
        // Without filtering, a blank line would be a zero-length substring that matches every UA.
        $this->settings->set([
            'block_api_user_agents' => '1',
            'blocked_api_user_agents' => "curl/\n\n   \n",
            'block_blank_api_user_agents' => '0',
        ]);

        Passport::actingAs(User::factory()->superuser()->create());

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0) Chrome/120.0.0.0')
            ->getJson(route('api.users.selectlist'))
            ->assertOk();
    }

    public function test_scim_routes_block_pattern_matches()
    {
        $this->settings->set([
            'block_api_user_agents' => '1',
            'blocked_api_user_agents' => 'curl/',
            'block_blank_api_user_agents' => '0',
        ]);

        Passport::actingAs(User::factory()->superuser()->create());

        $this->withHeader('User-Agent', 'curl/8.5.0')
            ->getJson('/scim/v2/Users')
            ->assertForbidden()
            ->assertJson([
                'payload' => ['user_agent' => 'curl/8.5.0'],
            ]);
    }

    public function test_scim_routes_allow_blank_user_agent_even_when_admin_blocks_blanks()
    {
        // Hard override: even with the admin toggle set to block blanks, SCIM routes
        // pass through because Entra ID SCIM provisioning sends a blank User-Agent.
        $this->settings->set([
            'block_api_user_agents' => '1',
            'blocked_api_user_agents' => null,
            'block_blank_api_user_agents' => '1',
        ]);

        Passport::actingAs(User::factory()->superuser()->create());

        $response = $this->withHeader('User-Agent', '')
            ->getJson('/scim/v2/Users');

        $this->assertNotEquals(403, $response->status());
    }

    public function test_default_patterns_constant_is_non_empty()
    {
        $this->assertNotEmpty(Setting::DEFAULT_BLOCKED_API_USER_AGENTS);
        $this->assertContains('curl/', Setting::DEFAULT_BLOCKED_API_USER_AGENTS);
    }
}
