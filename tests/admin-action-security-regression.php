<?php
/**
 * Source-level guard: all registered YPerf admin-post and AJAX actions
 * must start with administrator capability and nonce verification.
 * Run: php tests/admin-action-security-regression.php
 */
declare(strict_types=1);

$source = file_get_contents(dirname(__DIR__) . '/includes/class-yperf-admin.php');
if (!is_string($source) || $source === '') {
    throw new RuntimeException('YPerf Admin source is not readable.');
}

preg_match_all(
    "~add_action\\('(admin_post_yperf_[^']+|wp_ajax_yperf_[^']+)', array\\(__CLASS__, '([^']+)'\\)\\)~",
    $source,
    $matches,
    PREG_SET_ORDER
);
if (count($matches) < 59) {
    throw new RuntimeException('YPerf registered admin action count unexpectedly decreased.');
}
if (strpos($source, 'wp_ajax_nopriv_yperf_') !== false) {
    throw new RuntimeException('Unexpected unauthenticated YPerf admin AJAX action.');
}

$verified = array();
foreach ($matches as $match) {
    $method = $match[2];
    $needle = 'public static function ' . $method . '(';
    $start = strpos($source, $needle);
    if ($start === false) {
        throw new RuntimeException('Registered handler not found: ' . $method);
    }
    $body = substr($source, $start, 750);
    $capability = strpos($body, 'self::requireCapability(') !== false
        || strpos($body, 'self::requireOperator(') !== false
        || strpos($body, "current_user_can('manage_options')") !== false;
    $nonce = strpos($body, 'self::requireOperator(') !== false
        || strpos($body, 'check_ajax_referer(') !== false
        || strpos($body, 'check_admin_referer(') !== false;
    if (!$capability || !$nonce) {
        throw new RuntimeException('Missing capability or nonce guard: ' . $method);
    }
    $verified[$match[1]] = true;
}
if (count($verified) !== count($matches)) {
    throw new RuntimeException('Duplicate YPerf admin action registration detected.');
}
echo 'YPerf admin action security: PASS (' . count($verified) . " handlers)\n";
