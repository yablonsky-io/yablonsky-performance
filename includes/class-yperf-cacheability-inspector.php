<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class CacheabilityInspector
{
    private const OPTION = 'yperf_cacheability_inspection_v1';

    /** @return array<string,mixed> */
    public static function inspect(string $url): array
    {
        $url = esc_url_raw(trim($url));
        if ($url === '') {
            $url = home_url('/');
        }

        if (!self::sameOrigin($url)) {
            $report = self::report($url, 'blocked', false, 'cross_origin', 0, 0, array(), 'Only same-origin URLs can be inspected.');
            update_option(self::OPTION, $report, false);
            return $report;
        }

        $path = wp_parse_url($url, PHP_URL_PATH);
        $path = is_string($path) ? '/' . ltrim($path, '/') : '/';
        $query = array();
        $rawQuery = wp_parse_url($url, PHP_URL_QUERY);
        if (is_string($rawQuery) && $rawQuery !== '') {
            parse_str($rawQuery, $query);
        }
        $context = array(
            'method'           => 'GET',
            'is_admin'         => false,
            'doing_ajax'       => false,
            'doing_cron'       => false,
            'is_rest'          => strpos($path, '/wp-json/') !== false || substr($path, -8) === '/wp-json',
            'logged_in'        => false,
            'private_route'    => self::privatePath($path),
            'sensitive_cookie' => false,
            'query'            => is_array($query) ? $query : array(),
            'path'             => $path,
        );
        $decision = RequestPolicy::classify($context);

        if (in_array((string) $decision['reason'], array('private_route', 'rest', 'logged_in', 'private_cookie', 'method'), true)) {
            $report = self::report(
                $url,
                'ready',
                false,
                (string) $decision['reason'],
                0,
                0,
                array(),
                'Policy-only inspection completed. No live request was sent to this private or sensitive route.'
            );
            update_option(self::OPTION, $report, false);
            return $report;
        }

        $start = microtime(true);
        $response = wp_safe_remote_get(
            $url,
            array(
                'timeout'             => 8,
                'redirection'         => 3,
                'sslverify'           => true,
                'limit_response_size' => 65536,
                'user-agent'          => 'Yablonsky-Performance/' . YPERF_VERSION . ' CacheabilityInspector',
                'headers'             => array('Accept' => 'text/html,application/xhtml+xml'),
            )
        );
        $elapsed = (int) round((microtime(true) - $start) * 1000);

        if (is_wp_error($response)) {
            $report = self::report($url, 'error', (bool) $decision['cacheable'], (string) $decision['reason'], 0, $elapsed, array(), 'HTTP probe failed: ' . $response->get_error_message());
            update_option(self::OPTION, $report, false);
            return $report;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $signals = self::signals(wp_remote_retrieve_headers($response));
        $attention = isset($signals['set-cookie']);
        $status = ($code >= 200 && $code < 400) ? ($attention ? 'attention' : 'ready') : 'attention';
        $details = $attention
            ? 'Policy allows analysis, but the origin sets a cookie. Review before page-cache adoption.'
            : 'Policy and live response were inspected without changing cache configuration.';

        $report = self::report($url, $status, (bool) $decision['cacheable'], (string) $decision['reason'], $code, $elapsed, $signals, $details);
        update_option(self::OPTION, $report, false);
        return $report;
    }

    /** @return array<string,mixed> */
    public static function lastReport(): array
    {
        $value = get_option(self::OPTION, array());
        return is_array($value) ? $value : array();
    }

    /** @param array<string,string> $signals @return array<string,mixed> */
    private static function report(string $url, string $status, bool $cacheable, string $reason, int $code, int $ms, array $signals, string $details): array
    {
        return array(
            'schema'       => 1,
            'generated_at' => time(),
            'url'          => $url,
            'status'       => sanitize_key($status),
            'decision'     => $cacheable ? 'cache' : 'bypass',
            'cacheable'    => $cacheable,
            'reason'       => sanitize_key($reason),
            'http_code'    => max(0, $code),
            'ms'           => max(0, $ms),
            'signals'      => $signals,
            'details'      => sanitize_text_field($details),
        );
    }

    /** @param mixed $headers @return array<string,string> */
    private static function signals($headers): array
    {
        $names = array('cache-control', 'age', 'etag', 'last-modified', 'x-cache', 'x-cache-status', 'x-fastcgi-cache', 'cf-cache-status', 'vary', 'server');
        $signals = array();
        foreach ($names as $name) {
            $value = '';
            if (is_object($headers) && method_exists($headers, 'offsetGet')) {
                $value = (string) $headers->offsetGet($name);
            } elseif (is_array($headers) && isset($headers[$name])) {
                $value = (string) $headers[$name];
            }
            if ($value !== '') {
                $signals[$name] = sanitize_text_field($value);
            }
        }
        $cookie = '';
        if (is_object($headers) && method_exists($headers, 'offsetGet')) {
            $cookie = (string) $headers->offsetGet('set-cookie');
        } elseif (is_array($headers) && isset($headers['set-cookie'])) {
            $cookie = is_array($headers['set-cookie']) ? implode(';', $headers['set-cookie']) : (string) $headers['set-cookie'];
        }
        if ($cookie !== '') {
            $signals['set-cookie'] = 'present';
        }
        return $signals;
    }

    private static function privatePath(string $path): bool
    {
        $path = '/' . ltrim(strtolower($path), '/');
        foreach (array('/wp-admin', '/wp-login.php', '/wp-cron.php', '/wp-comments-post.php') as $needle) {
            if (strpos($path, $needle) !== false) {
                return true;
            }
        }
        return preg_match('~/(cart|checkout|my-account)(?:/|$)~', $path) === 1;
    }

    private static function sameOrigin(string $url): bool
    {
        $target = wp_parse_url($url);
        $home = wp_parse_url(home_url('/'));
        if (!is_array($target) || !is_array($home)) {
            return false;
        }
        $targetHost = strtolower((string) ($target['host'] ?? ''));
        $homeHost = strtolower((string) ($home['host'] ?? ''));
        $targetScheme = strtolower((string) ($target['scheme'] ?? ''));
        $homeScheme = strtolower((string) ($home['scheme'] ?? ''));
        if ($targetHost === '' || $homeHost === '' || $targetHost !== $homeHost || $targetScheme !== $homeScheme) {
            return false;
        }
        $defaultPort = static function (string $scheme): int {
            return $scheme === 'https' ? 443 : 80;
        };
        $targetPort = isset($target['port']) ? (int) $target['port'] : $defaultPort($targetScheme);
        $homePort = isset($home['port']) ? (int) $home['port'] : $defaultPort($homeScheme);
        return $targetPort === $homePort;
    }
}
