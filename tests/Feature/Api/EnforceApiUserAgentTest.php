<?php

namespace Tests\Feature\Api;

use App\Models\Setting;
use App\Models\User;
use Laravel\Passport\Passport;
use Tests\TestCase;

class EnforceApiUserAgentTest extends TestCase
{
    public function test_master_off_allows_matching_user_agents_to_pass()
    {
        // Master gate off — even with a populated pattern list, nothing should be blocked.
        $this->settings->set([
            'block_api_user_agents' => '0',
            'blocked_api_user_agents' => "curl/\nPostmanRuntime/",
            'block_blank_api_user_agents' => '0',
        ]);

        Passport::actingAs(User::factory()->superuser()->create());

        $this->withHeader('User-Agent', 'curl/8.5.0')
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

    public function test_master_on_blank_user_agent_passes_when_blank_blocking_is_off()
    {
        // The Entra SCIM scenario: pattern blocking is on, blanks are explicitly allowed.
        $this->settings->set([
            'block_api_user_agents' => '1',
            'blocked_api_user_agents' => "curl/\nPostmanRuntime/",
            'block_blank_api_user_agents' => '0',
        ]);

        Passport::actingAs(User::factory()->superuser()->create());

        $this->withHeader('User-Agent', '')
            ->getJson(route('api.users.selectlist'))
            ->assertOk();
    }

    public function test_blank_user_agent_blocked_when_blank_blocking_on_even_if_master_is_off()
    {
        // Blank blocking is independent of the master pattern gate.
        $this->settings->set([
            'block_api_user_agents' => '0',
            'blocked_api_user_agents' => null,
            'block_blank_api_user_agents' => '1',
        ]);

        Passport::actingAs(User::factory()->superuser()->create());

        $this->withHeader('User-Agent', '')
            ->getJson(route('api.users.selectlist'))
            ->assertForbidden()
            ->assertJson([
                'status' => 'error',
                'payload' => ['user_agent' => ''],
            ]);
    }

    public function test_whitespace_only_user_agent_treated_as_blank_and_echoed_verbatim()
    {
        $this->settings->set([
            'block_api_user_agents' => '0',
            'blocked_api_user_agents' => null,
            'block_blank_api_user_agents' => '1',
        ]);

        Passport::actingAs(User::factory()->superuser()->create());

        $this->withHeader('User-Agent', '   ')
            ->getJson(route('api.users.selectlist'))
            ->assertForbidden()
            ->assertJson([
                'payload' => ['user_agent' => '   '],
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

        // Mid-string mention of "curl/" should NOT be blocked under prefix matching.
        $this->withHeader('User-Agent', 'MyWrapper/1.0 (uses curl/8.5.0 internally)')
            ->getJson(route('api.users.selectlist'))
            ->assertOk();

        // Pattern at position 0 still blocks.
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

    public function test_middleware_is_applied_to_scim_routes()
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
                'status' => 'error',
                'payload' => ['user_agent' => 'curl/8.5.0'],
            ]);
    }

    public function test_blank_blocking_applies_to_scim_routes()
    {
        $this->settings->set([
            'block_api_user_agents' => '0',
            'blocked_api_user_agents' => null,
            'block_blank_api_user_agents' => '1',
        ]);

        Passport::actingAs(User::factory()->superuser()->create());

        $this->withHeader('User-Agent', '')
            ->getJson('/scim/v2/Users')
            ->assertForbidden();
    }

    public function test_default_patterns_constant_is_non_empty()
    {
        $this->assertNotEmpty(Setting::DEFAULT_BLOCKED_API_USER_AGENTS);
        $this->assertContains('curl/', Setting::DEFAULT_BLOCKED_API_USER_AGENTS);
    }
}
