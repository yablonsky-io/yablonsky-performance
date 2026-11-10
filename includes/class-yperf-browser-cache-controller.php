<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/** Conservative browser-cache policy for public HTML owned by YPerf. */
final class BrowserCacheController
{
    private const OPTION = 'yperf_browser_cache_policy';
    private const MAX_AGE = 60;
    private const STALE_WHILE_REVALIDATE = 30;

    public static function register(): void
    {
        if (!self::enabled()) {
            return;
        }
        add_filter('wp_headers', array(self::class, 'filterHeaders'), PHP_INT_MAX, 2);
    }

    public static function enabled(): bool
    {
        return get_option(self::OPTION, '0') === '1';
    }

    public static function setEnabled(bool $enabled): bool
    {
        $before = get_option(self::OPTION, '0') === '1';
        update_option(self::OPTION, $enabled ? '1' : '0', false);
        if (PageCacheExecutor::enabled() && !PageCacheExecutor::refreshDropinPolicy()) {
            update_option(self::OPTION, $before ? '1' : '0', false);
            PageCacheExecutor::refreshDropinPolicy();
            return false;
        }
        return true;
    }

    /** @param array<string,string> $headers @param mixed $wp */
    public static function filterHeaders(array $headers, $wp): array
    {
        unset($wp);
        if (!PageCacheExecutor::enabled()) {
            return $headers;
        }
        $decision = RequestPolicy::currentDecision();
        if (empty($decision['cacheable'])) {
            return $headers;
        }
        foreach ($headers as $name => $value) {
            if (strtolower((string) $name) === 'set-cookie' || stripos((string) $value, 'private') !== false || stripos((string) $value, 'no-store') !== false) {
                return $headers;
            }
        }
        $headers['Cache-Control'] = 'public, max-age=' . self::MAX_AGE . ', stale-while-revalidate=' . self::STALE_WHILE_REVALIDATE;
        if (isset($headers['Vary']) && trim((string) $headers['Vary']) !== '') {
            if (stripos((string) $headers['Vary'], 'Accept-Encoding') === false) {
                $headers['Vary'] .= ', Accept-Encoding';
            }
        } else {
            $headers['Vary'] = 'Accept-Encoding';
        }
        $headers['X-YPerf-Browser-Cache'] = 'active';
        return $headers;
    }

    /** @return array<string,mixed> */
    public static function prove(): array
    {
        if (!self::enabled() || !PageCacheExecutor::enabled()) {
            return array('ok' => false, 'message' => __('Browser Cache requires the YPerf Page Cache to be active first.', 'yablonsky-performance'));
        }
        PageCacheExecutor::purgeUrls(array(home_url('/')), 'browser_cache_proof');
        $response = wp_safe_remote_get(home_url('/'), array(
            'timeout' => 12,
            'redirection' => 2,
            'sslverify' => true,
            'limit_response_size' => 32768,
            'user-agent' => 'Yablonsky-Performance/' . YPERF_VERSION . ' BrowserCacheProof',
            'headers' => array('X-YPerf-Probe' => 'browser-cache'),
        ));
        if (is_wp_error($response)) {
            return array('ok' => false, 'message' => sanitize_text_field($response->get_error_message()));
        }
        $control = strtolower((string) wp_remote_retrieve_header($response, 'cache-control'));
        $marker = strtolower((string) wp_remote_retrieve_header($response, 'x-yperf-browser-cache'));
        $ok = strpos($control, 'public') !== false && strpos($control, 'max-age=') !== false && $marker === 'active';
        return array(
            'ok' => $ok,
            'http_code' => (int) wp_remote_retrieve_response_code($response),
            'cache_control' => sanitize_text_field($control),
            'marker' => sanitize_text_field($marker),
        );
    }
}
