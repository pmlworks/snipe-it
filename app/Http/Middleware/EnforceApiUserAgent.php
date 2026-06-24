<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceApiUserAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $setting = Setting::getSettings();
        $rawUserAgent = $request->header('User-Agent');
        $userAgent = trim((string) $rawUserAgent);

        // Handle the blank-UA case up front. Blank blocking is independent of the
        // pattern master so an admin can leave it off for integrations that legitimately
        // send a blank User-Agent (e.g. Entra SCIM provisioning).
        if ($userAgent === '') {
            if ($setting?->block_blank_api_user_agents) {
                return $this->reject($rawUserAgent);
            }

            return $next($request);
        }

        // From here on we know the UA is non-blank.
        // Pattern-based blocking is gated behind block_api_user_agents so the textarea
        // can be pre-populated with sensible defaults without auto-enabling blocking.
        if (! $setting?->block_api_user_agents) {
            return $next($request);
        }

        // Prefix match (=== 0) rather than substring: scripted clients identify
        // themselves at the start of the User-Agent (curl/x, PostmanRuntime/x,
        // python-requests/x, etc.), and matching only the prefix prevents a pattern
        // from accidentally hitting an unrelated UA that mentions it later in the
        // string.
        foreach ($this->blockedPatterns($setting->blocked_api_user_agents) as $pattern) {
            if (stripos($userAgent, $pattern) === 0) {
                return $this->reject($rawUserAgent);
            }
        }

        return $next($request);
    }

    /**
     * Split the textarea contents into a clean list of patterns. Lines are
     * trimmed and blanks are dropped so the textarea ergonomics (trailing
     * newlines, accidental whitespace) don't accidentally match every UA.
     *
     * @return array<int, string>
     */
    private function blockedPatterns(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            'trim',
            preg_split('/\r\n|\r|\n/', $raw) ?: [],
        ), fn (string $line) => $line !== ''));
    }

    /**
     * Echo the original, untrimmed User-Agent header back to the caller so they
     * can see exactly what the server saw. Null means the header was absent.
     */
    private function reject(?string $userAgent): JsonResponse
    {
        return new JsonResponse([
            'status' => 'error',
            'messages' => trans('admin/settings/general.blocked_api_user_agent_rejected'),
            'payload' => [
                'user_agent' => $userAgent,
            ],
        ], Response::HTTP_FORBIDDEN);
    }
}
