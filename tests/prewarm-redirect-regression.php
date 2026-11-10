<?php
/**
 * Standalone regression: a same-origin prewarm must not follow a redirect,
 * and a redirect response must not be counted as a successful warm.
 * Run: php tests/prewarm-redirect-regression.php
 */
declare(strict_types=1);

namespace {
    define('YPERF_VERSION', '2.56.0');
    $requestOptions = array();
    function home_url(string $path = '/'): string { return 'https://example.test' . $path; }
    function esc_url_raw(string $url): string { return $url; }
    function wp_parse_url(string $url, int $component = -1) { return parse_url($url, $component); }
    function wp_safe_remote_get(string $url, array $options) {
        $GLOBALS['requestOptions'] = $options;
        return array('response' => array('code' => 302), 'headers' => array('x-yperf-cache' => 'MISS'));
    }
    function is_wp_error($value): bool { return false; }
    function wp_remote_retrieve_response_code($response): int { return (int) $response['response']['code']; }
    function wp_remote_retrieve_header($response, string $name): string {
        return (string) ($response['headers'][$name] ?? '');
    }
    function sanitize_key(string $value): string { return strtolower(preg_replace('/[^a-z0-9_-]/i', '', $value)); }
}

namespace Yablonsky\Performance {
    require_once dirname(__DIR__) . '/includes/class-yperf-prewarm.php';

    $result = Prewarm::runUrl('https://example.test/redirect');
    $options = $GLOBALS['requestOptions'];
    if (($options['redirection'] ?? null) !== 0) {
        throw new \RuntimeException('Prewarm must not follow redirects.');
    }
    if (($result['status'] ?? null) !== 'fail' || ($result['http_code'] ?? null) !== 302) {
        throw new \RuntimeException('HTTP redirect cannot count as successful prewarm.');
    }
    echo "Prewarm redirect regression: PASS\n";
}
