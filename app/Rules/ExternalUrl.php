<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Reject URLs that point at loopback, link-local, private-network, or
 * otherwise-non-public addresses so a super-admin can't be tricked (or
 * accidentally) turn the outbound webhook path into an SSRF primitive
 * against 169.254.169.254, 127.0.0.1, RFC-1918, [::1], and friends.
 *
 * The rule is intentionally scheme-strict (http/https only): the previous
 * webhook validator accepted ftp:// and irc://, which have no legitimate
 * webhook use and expand the attack surface.
 *
 * Between this check and the outbound request there is still a DNS
 * rebinding window; that is acceptable given the super-admin threat
 * model. If we ever need to close it, pin the resolved IP into Guzzle
 * via CURLOPT_RESOLVE on the actual request.
 */
class ExternalUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail(trans('validation.external_url'));

            return;
        }

        $parts = parse_url($value);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            $fail(trans('validation.external_url'));

            return;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            $fail(trans('validation.external_url'));

            return;
        }

        $host = $parts['host'];

        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : $this->resolveHost($host);

        if ($ips === []) {
            $fail(trans('validation.external_url'));

            return;
        }

        foreach ($ips as $ip) {
            if (! $this->isPublicIp($ip)) {
                $fail(trans('validation.external_url'));

                return;
            }
        }
    }

    private function isPublicIp(string $ip): bool
    {
        // Unwrap IPv4-mapped IPv6 so ::ffff:127.0.0.1 doesn't sneak past
        // the IPv4 private/reserved checks.
        if (stripos($ip, '::ffff:') === 0) {
            $ipv4 = substr($ip, 7);
            if (filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ip = $ipv4;
            }
        }

        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    private function resolveHost(string $host): array
    {
        $ips = [];

        $records = @dns_get_record($host, DNS_A + DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $r) {
                if (! empty($r['ip'])) {
                    $ips[] = $r['ip'];
                }
                if (! empty($r['ipv6'])) {
                    $ips[] = $r['ipv6'];
                }
            }
        }

        if ($ips === []) {
            $resolved = @gethostbyname($host);
            if ($resolved !== $host && filter_var($resolved, FILTER_VALIDATE_IP)) {
                $ips[] = $resolved;
            }
        }

        return $ips;
    }
}
