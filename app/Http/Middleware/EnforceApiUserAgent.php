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

        // Blank-UA blocking is independent of the pattern-based master toggle: an admin
        // can enable it on its own (or leave it off if they have an integration like Entra
        // SCIM that legitimately sends a blank User-Agent).
        if ($setting?->block_blank_api_user_agents && $userAgent === '') {
            return $this->reject($rawUserAgent);
        }

        // Pattern-based blocking is gated behind block_api_user_agents so the textarea
        // can be pre-populated with sensible defaults without auto-enabling blocking.
        if (! $setting?->block_api_user_agents) {
            return $next($request);
        }

        $patterns = $this->blockedPatterns($setting->blocked_api_user_agents);

        if ($patterns === [] || $userAgent === '') {
            return $next($request);
        }

        foreach ($patterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
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
