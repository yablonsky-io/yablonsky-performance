<?php
/**
 * The credentialed Yablonsky AI transport may only use canonical HTTPS.
 * Run: php tests/ai-origin-regression.php
 */
declare(strict_types=1);

namespace {
    function wp_parse_url(string $url, int $component = -1) { return parse_url($url, $component); }
}
namespace Yablonsky\Performance {
    require_once dirname(__DIR__) . '/includes/class-yperf-yablonsky-ai-client.php';
    $method = new \ReflectionMethod(YablonskyAIClient::class, 'urlIsYablonsky');
    $method->setAccessible(true);
    $cases = array(
        array('https://ai.yablonsky.io/api/account', true),
        array('https://ai.yablonsky.io:443/api/account', true),
        array('http://ai.yablonsky.io/api/account', false),
        array('https://ai.yablonsky.io:8443/api/account', false),
        array('https://other.yablonsky.io/api/account', false),
        array('https://ai.yablonsky.io.attacker.test/api/account', false),
        array('https://user@ai.yablonsky.io/api/account', false),
    );
    foreach ($cases as $case) {
        if ($method->invoke(null, $case[0]) !== $case[1]) {
            throw new \RuntimeException('AI credential origin policy failed for ' . $case[0]);
        }
    }
    echo "Yablonsky AI credential origin: PASS\n";
}
