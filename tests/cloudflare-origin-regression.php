<?php
/**
 * Standalone origin policy regression for Cloudflare URL purge scope.
 * Run: php tests/cloudflare-origin-regression.php
 */
declare(strict_types=1);

namespace {
    function home_url(string $path = '/'): string { return 'https://example.test' . $path; }
    function wp_parse_url(string $url, int $component = -1) { return parse_url($url, $component); }
}
namespace Yablonsky\Performance {
    require_once dirname(__DIR__) . '/includes/class-yperf-cloudflare-client.php';
    $method = new \ReflectionMethod(CloudflareClient::class, 'sameOrigin');
    $method->setAccessible(true);
    $cases = array(
        'same origin' => array('https://example.test/product', true),
        'explicit default port' => array('https://example.test:443/product', true),
        'other HTTPS port' => array('https://example.test:8443/product', false),
        'foreign host' => array('https://other.test/product', false),
        'foreign scheme' => array('http://example.test/product', false),
        'userinfo' => array('https://username@example.test/product', false),
    );
    foreach ($cases as $label => $case) {
        $actual = $method->invoke(null, $case[0]);
        if ($actual !== $case[1]) {
            throw new \RuntimeException('Cloudflare purge origin regression: ' . $label);
        }
    }
    echo "Cloudflare purge origin regression: PASS\n";
}
