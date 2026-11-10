<?php
/**
 * Standalone regression: cacheability inspection must not follow redirects
 * or label a redirect response as cache ready.
 * Run: php tests/cacheability-redirect-regression.php
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
        return array('response' => array('code' => 302), 'headers' => array('location' => 'https://elsewhere.test/'));
    }
    function is_wp_error($value): bool { return false; }
    function wp_remote_retrieve_response_code($response): int { return (int) $response['response']['code']; }
    function wp_remote_retrieve_headers($response): array { return $response['headers']; }
    function sanitize_key(string $value): string { return strtolower(preg_replace('/[^a-z0-9_-]/i', '', $value)); }
    function sanitize_text_field(string $value): string { return $value; }
    function update_option(string $key, $value, bool $autoload = false): bool { return true; }
}

namespace Yablonsky\Performance {
    final class RequestPolicy {
        public static function classify(array $context): array {
            return array('reason' => 'public', 'cacheable' => true);
        }
    }
    require_once dirname(__DIR__) . '/includes/class-yperf-cacheability-inspector.php';
    $report = CacheabilityInspector::inspect('https://example.test/path');
    if (($GLOBALS['requestOptions']['redirection'] ?? null) !== 0) {
        throw new \RuntimeException('Cacheability inspection must not follow redirects.');
    }
    if (($report['status'] ?? '') !== 'attention' || ($report['http_code'] ?? 0) !== 302) {
        throw new \RuntimeException('Redirect must be flagged, not reported as cache-ready.');
    }
    echo "Cacheability redirect regression: PASS\n";
}
