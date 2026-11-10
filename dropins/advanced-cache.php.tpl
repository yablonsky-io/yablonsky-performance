<?php
/**
 * Yablonsky Performance Page Cache — generated drop-in.
 * Version: __YPERF_VERSION__
 *
 * Loaded by WordPress before plugins when WP_CACHE is true. The drop-in is
 * deliberately self-contained so a cache HIT avoids booting WordPress.
 */

if (!defined('ABSPATH') || !defined('WP_CONTENT_DIR')) {
    return;
}

$yperf_method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
$yperf_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
$yperf_host = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
$yperf_path = parse_url($yperf_uri, PHP_URL_PATH);
$yperf_path = is_string($yperf_path) && $yperf_path !== '' ? $yperf_path : '/';
$yperf_query = parse_url($yperf_uri, PHP_URL_QUERY);
$yperf_bypass = $yperf_method !== 'GET' || $yperf_host === '' || (is_string($yperf_query) && $yperf_query !== '');
// Never share HTTP-authenticated representations, even when no WordPress login
// cookie is present. These fields are untrusted request context, not identity.
if (!empty($_SERVER['HTTP_AUTHORIZATION']) || !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])
    || !empty($_SERVER['PHP_AUTH_USER']) || !empty($_SERVER['HTTP_PROXY_AUTHORIZATION'])
) {
    $yperf_bypass = true;
}


// Never cache crawler/service/non-HTML endpoints. This check runs before
// WordPress/plugins so a previously cached HTML error can never become the
// authority for a sitemap/XML URL.
$yperf_service_path = '/' . ltrim(strtolower(rawurldecode($yperf_path)), '/');
if (preg_match('~\\.xml(?:\.gz)?$~', $yperf_service_path) === 1
    || (in_array(rtrim($yperf_service_path, '/'), array('/robots.txt', '/xmlrpc.php', '/wp-links-opml.php'), true)
        || preg_match('~(?:^|/)robots\.txt/?$~', $yperf_service_path) === 1)
    || preg_match('~/(?:feed|comments/feed)(?:/|$)~', $yperf_service_path) === 1
) {
    $yperf_bypass = true;
}

if (!$yperf_bypass) {
    $yperf_lower_path = strtolower($yperf_path);
    foreach (array('/wp-admin', '/wp-login.php', '/wp-cron.php', '/wp-comments-post.php', '/wp-json/') as $yperf_private_path) {
        if (strpos($yperf_lower_path, $yperf_private_path) !== false) {
            $yperf_bypass = true;
            break;
        }
    }
    if (preg_match('~/(cart|checkout|my-account)(?:/|$)~', $yperf_lower_path) === 1) {
        $yperf_bypass = true;
    }
}

if (!$yperf_bypass && !empty($_COOKIE)) {
    foreach (array_keys($_COOKIE) as $yperf_cookie_name) {
        $yperf_cookie_name = (string) $yperf_cookie_name;
        foreach (array('wordpress_logged_in_', 'wordpress_sec_', 'wp_woocommerce_session_', 'woocommerce_items_in_cart', 'woocommerce_cart_hash', 'comment_author_', 'wp-postpass_', 'edd_items_in_cart', 'edd_session_', 'PHPSESSID', 'wp-wpml_current_language', 'wpml_current_language', 'pll_language', 'googtrans', 'woocommerce_recently_viewed', 'woocommerce_currency', 'yith_wcwl_session_') as $yperf_cookie_prefix) {
            if (strpos($yperf_cookie_name, $yperf_cookie_prefix) === 0) {
                $yperf_bypass = true;
                break 2;
            }
        }
    }
}

if ($yperf_bypass) {
    if (!headers_sent()) {
        header('X-YPerf-Cache: BYPASS');
        header('X-YPerf-Cache-Version: __YPERF_VERSION__');
    }
    return;
}

$yperf_scheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
$yperf_key = hash('sha256', $yperf_scheme . '://' . $yperf_host . $yperf_path);
$yperf_root = rtrim((string) WP_CONTENT_DIR, '/\\') . '/cache/yablonsky-performance/page';
$yperf_dir = $yperf_root . '/' . substr($yperf_key, 0, 2);
$yperf_html = $yperf_dir . '/' . $yperf_key . '.html';
$yperf_meta = $yperf_dir . '/' . $yperf_key . '.json';
$yperf_ttl = __YPERF_TTL__;
$yperf_browser_max_age = __YPERF_BROWSER_MAX_AGE__;
$yperf_now = time();
$yperf_lock = $yperf_dir . '/' . $yperf_key . '.lock';

// A hard-killed PHP worker can leave an orphan lock. Fresh locks remain
// authoritative; only a lock older than two minutes is eligible for cleanup.
if (is_file($yperf_lock)) {
    $yperf_lock_mtime = @filemtime($yperf_lock); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_filemtime -- pre-bootstrap lock hygiene.
    if (is_int($yperf_lock_mtime) && $yperf_lock_mtime > 0 && (time() - $yperf_lock_mtime) >= 120) {
        @unlink($yperf_lock); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- bounded stale pre-bootstrap lock cleanup.
    }
}

if (is_file($yperf_html) && is_file($yperf_meta)) {
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- drop-in runs before WP Filesystem is available.
    $yperf_meta_raw = file_get_contents($yperf_meta);
    $yperf_data = is_string($yperf_meta_raw) ? json_decode($yperf_meta_raw, true) : null;
    if (is_array($yperf_data) && (int) ($yperf_data['expires_at'] ?? 0) >= $yperf_now) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- hot cache path intentionally avoids WordPress bootstrap.
        $yperf_body = file_get_contents($yperf_html);
        if (is_string($yperf_body)) {
            // Weak validator identifies the canonical uncompressed HTML bytes.
            // Weakness is intentional: gzip/Brotli/CDN may transform the wire
            // representation while preserving its meaning.
            $yperf_etag = 'W/"yperf-' . hash('sha256', $yperf_body) . '"';
            $yperf_conditional = isset($_SERVER['HTTP_IF_NONE_MATCH']) && is_string($_SERVER['HTTP_IF_NONE_MATCH'])
                ? (string) $_SERVER['HTTP_IF_NONE_MATCH'] : '';
            $yperf_not_modified = false;
            if ($yperf_conditional !== '' && strlen($yperf_conditional) <= 4096) {
                foreach (explode(',', $yperf_conditional) as $yperf_candidate) {
                    $yperf_candidate = trim($yperf_candidate);
                    // Weak comparison is correct for If-None-Match on GET.
                    if ($yperf_candidate === '*' || $yperf_candidate === $yperf_etag
                        || $yperf_candidate === substr($yperf_etag, 2)) {
                        $yperf_not_modified = true;
                        break;
                    }
                }
            }
            if (!headers_sent()) {
                if ($yperf_not_modified) {
                    http_response_code(304);
                } else {
                    header('Content-Type: ' . (string) ($yperf_data['content_type'] ?? 'text/html; charset=UTF-8'));
                }
                header($yperf_browser_max_age > 0 ? 'Cache-Control: public, max-age=' . min($yperf_browser_max_age, max(0, (int) ($yperf_data['expires_at'] ?? $yperf_now) - $yperf_now)) : 'Cache-Control: no-cache, must-revalidate, max-age=0');
                header('Age: ' . max(0, $yperf_now - (int) ($yperf_data['created_at'] ?? $yperf_now)));
                header('ETag: ' . $yperf_etag);
                header('Vary: Accept-Encoding');
                // Exact public metadata copied from the originating 200 response;
                // never replay arbitrary request headers or private response data.
                foreach ((array) ($yperf_data['public_headers'] ?? array()) as $yperf_name => $yperf_values) {
                    if (!in_array(strtolower((string) $yperf_name), array('x-robots-tag', 'content-language', 'link'), true)) {
                        continue;
                    }
                    foreach (array_slice((array) $yperf_values, 0, 16) as $yperf_value) {
                        if (is_string($yperf_value) && strlen($yperf_value) <= 2048
                            && strpos($yperf_value, "\r") === false && strpos($yperf_value, "\n") === false) {
                            header($yperf_name . ': ' . $yperf_value, false);
                        }
                    }
                }
                header('X-YPerf-Cache: ' . ($yperf_not_modified ? 'REVALIDATED' : 'HIT'));
                header('X-YPerf-Cache-Version: __YPERF_VERSION__');
            }
            if ($yperf_not_modified) {
                // Fresh cached representation only: never 304 for expired/stale.
                exit;
            }
            echo $yperf_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- cached HTML is the previously generated same-origin response.
            exit;
        }
    }
}

// One writer per public key. A competing request waits for the first writer
// before booting WordPress. Bounded waiting avoids a burst of duplicate costly
// WP/WooCommerce renders when a cold page takes longer than 200 milliseconds.
// This lock lives inside the owned cache tree and requires no host configuration.
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- pre-bootstrap cache lock.
$yperf_lock_handle = @fopen($yperf_lock, 'x');
if ($yperf_lock_handle === false) {
    // Only a previously cached public 200 HTML response may be served stale,
    // and only while another worker owns the refresh lock. Purge removes both
    // files, so an invalidated page is never served through the stale path.
    // Expired content is capped at 90 seconds; no stale response is browser-
    // cacheable, so the next visitor can receive the refreshed authority.
    $yperf_stale_grace = 90;
    $yperf_wait_deadline = microtime(true) + 1.5;
    do {
        if (is_file($yperf_html) && is_file($yperf_meta)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- pre-bootstrap cache read.
            $yperf_wait_meta_raw = @file_get_contents($yperf_meta);
            $yperf_wait_data = is_string($yperf_wait_meta_raw) ? json_decode($yperf_wait_meta_raw, true) : null;
            $yperf_wait_now = time();
            $yperf_wait_expires = is_array($yperf_wait_data) ? (int) ($yperf_wait_data['expires_at'] ?? 0) : 0;
            $yperf_is_fresh = $yperf_wait_expires >= $yperf_wait_now;
            $yperf_is_stale = $yperf_wait_expires > 0
                && $yperf_wait_expires < $yperf_wait_now
                && ($yperf_wait_now - $yperf_wait_expires) <= $yperf_stale_grace;
            if (($yperf_is_fresh || $yperf_is_stale)
                && is_array($yperf_wait_data)
                && (int) ($yperf_wait_data['schema'] ?? 0) === 1
                && strpos(strtolower((string) ($yperf_wait_data['content_type'] ?? '')), 'text/html') === 0
            ) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- pre-bootstrap cache read.
                $yperf_wait_body = @file_get_contents($yperf_html);
                if (is_string($yperf_wait_body) && $yperf_wait_body !== '') {
                    if (!headers_sent()) {
                        header('Content-Type: ' . (string) $yperf_wait_data['content_type']);
                        header($yperf_is_stale ? 'Cache-Control: no-store, max-age=0'
                            : ($yperf_browser_max_age > 0 ? 'Cache-Control: public, max-age=' . min($yperf_browser_max_age, max(0, $yperf_wait_expires - $yperf_wait_now)) : 'Cache-Control: no-cache, must-revalidate, max-age=0'));
                        header('Vary: Accept-Encoding');
                        foreach ((array) ($yperf_wait_data['public_headers'] ?? array()) as $yperf_name => $yperf_values) {
                            if (!in_array(strtolower((string) $yperf_name), array('x-robots-tag', 'content-language', 'link'), true)) {
                                continue;
                            }
                            foreach (array_slice((array) $yperf_values, 0, 16) as $yperf_value) {
                                if (is_string($yperf_value) && strlen($yperf_value) <= 2048
                                    && strpos($yperf_value, "\r") === false && strpos($yperf_value, "\n") === false) {
                                    header($yperf_name . ': ' . $yperf_value, false);
                                }
                            }
                        }
                        if (!$yperf_is_stale) {
                            header('ETag: W/"yperf-' . hash('sha256', $yperf_wait_body) . '"');
                        }
                        header('X-YPerf-Cache: ' . ($yperf_is_stale ? 'STALE-LOCK' : 'HIT-WAIT'));
                        header('X-YPerf-Cache-Version: __YPERF_VERSION__');
                    }
                    echo $yperf_wait_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- cached anonymous public HTML.
                    exit;
                }
            }
        }
        if (microtime(true) >= $yperf_wait_deadline) {
            break;
        }
        usleep(50000);
    } while (true);
    if (!headers_sent()) {
        header('X-YPerf-Cache: BYPASS-LOCK');
        header('X-YPerf-Cache-Version: __YPERF_VERSION__');
    }
    return;
}

register_shutdown_function(static function () use ($yperf_lock_handle, $yperf_lock): void {
    if (is_resource($yperf_lock_handle)) {
        fclose($yperf_lock_handle);
    }
    if (is_file($yperf_lock)) {
        @unlink($yperf_lock); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- pre-bootstrap cache lock cleanup.
    }
});

if (!headers_sent()) {
    header('X-YPerf-Cache: MISS');
    header('X-YPerf-Cache-Version: __YPERF_VERSION__');
}

ob_start(static function ($yperf_buffer) use ($yperf_html, $yperf_meta, $yperf_ttl) {
    if (!is_string($yperf_buffer) || $yperf_buffer === '' || strlen($yperf_buffer) > 2097152 || (defined('DONOTCACHEPAGE') && DONOTCACHEPAGE)) {
        return $yperf_buffer;
    }
    $yperf_code = function_exists('http_response_code') ? (int) http_response_code() : 200;
    if ($yperf_code !== 200) {
        return $yperf_buffer;
    }
    $yperf_content_type = 'text/html; charset=UTF-8';
    $yperf_public_headers = array();
    $yperf_existing_etag = false;
    foreach (headers_list() as $yperf_header) {
        $yperf_header_lower = strtolower((string) $yperf_header);
        if (strpos($yperf_header_lower, 'set-cookie:') === 0
            || (strpos($yperf_header_lower, 'cache-control:') === 0
                && preg_match('~\b(?:private|no-store|no-cache)\b~i', $yperf_header_lower) === 1)
            || (strpos($yperf_header_lower, 'vary:') === 0
                && preg_match('~^vary:\s*accept-encoding\s*$~i', $yperf_header_lower) !== 1)
            || strpos($yperf_header_lower, 'content-encoding:') === 0
            || strpos($yperf_header_lower, 'content-range:') === 0
            || strpos($yperf_header_lower, 'www-authenticate:') === 0
        ) {
            // Any personalized/variant/partial response must never enter the
            // shared anonymous cache. Existing cache entries remain unchanged.
            return $yperf_buffer;
        }
        if (strpos($yperf_header_lower, 'etag:') === 0) {
            $yperf_existing_etag = true;
        }
        if (strpos($yperf_header_lower, 'content-type:') === 0) {
            $yperf_content_type = trim(substr((string) $yperf_header, strlen('content-type:')));
        }
        foreach (array('x-robots-tag', 'content-language', 'link') as $yperf_allowed_header) {
            if (strpos($yperf_header_lower, $yperf_allowed_header . ':') === 0) {
                $yperf_value = trim(substr((string) $yperf_header, strlen($yperf_allowed_header) + 1));
                if ($yperf_value !== '' && strlen($yperf_value) <= 2048
                    && strpos($yperf_value, "\r") === false && strpos($yperf_value, "\n") === false) {
                    if (!isset($yperf_public_headers[$yperf_allowed_header])) {
                        $yperf_public_headers[$yperf_allowed_header] = array();
                    }
                    if (count($yperf_public_headers[$yperf_allowed_header]) < 16) {
                        $yperf_public_headers[$yperf_allowed_header][] = $yperf_value;
                    }
                }
            }
        }
    }
    if (stripos($yperf_content_type, 'text/html') === false || $yperf_existing_etag) {
        // Another component authored its own validator: preserve that authority
        // rather than replacing it on cache HIT with a different validator.
        return $yperf_buffer;
    }
    $yperf_created = time();
    $yperf_metadata = json_encode(array(
        'schema' => 1,
        'created_at' => $yperf_created,
        'expires_at' => $yperf_created + $yperf_ttl,
        'content_type' => $yperf_content_type,
        'public_headers' => $yperf_public_headers,
    ));
    if (!is_string($yperf_metadata)) {
        return $yperf_buffer;
    }
    // Respect a header already authored by WordPress/SEO; never become its
    // competing writer. Otherwise issue the same validator a future HIT uses.
    if (!$yperf_existing_etag && !headers_sent()) {
        header('ETag: W/"yperf-' . hash('sha256', $yperf_buffer) . '"');
    }
    $yperf_html_tmp = $yperf_html . '.tmp-' . getmypid();
    $yperf_meta_tmp = $yperf_meta . '.tmp-' . getmypid();
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- advanced-cache drop-in must write before WP Filesystem exists.
    $yperf_html_written = file_put_contents($yperf_html_tmp, $yperf_buffer, LOCK_EX);
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- advanced-cache drop-in must write before WP Filesystem exists.
    $yperf_meta_written = file_put_contents($yperf_meta_tmp, $yperf_metadata, LOCK_EX);
    if ($yperf_html_written !== false && $yperf_meta_written !== false) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- atomic cache promotion on pre-bootstrap hot path.
        rename($yperf_html_tmp, $yperf_html);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- atomic cache promotion on pre-bootstrap hot path.
        rename($yperf_meta_tmp, $yperf_meta);
    } else {
        if (is_file($yperf_html_tmp)) { @unlink($yperf_html_tmp); } // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
        if (is_file($yperf_meta_tmp)) { @unlink($yperf_meta_tmp); } // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
    }
    return $yperf_buffer;
});
