<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

if (!defined('ABSPATH')) {
    return;
}

if (!defined('YPERF_EARLY_RUNTIME_SOURCE_VERSION')) {
    define('YPERF_EARLY_RUNTIME_SOURCE_VERSION', '2.56.0');
}

/**
 * Early, request-cost focused runtime loaded by the single managed MU bootstrap.
 *
 * It never owns security classification. It keeps useful public content
 * crawlable while bounding avoidable WordPress/WooCommerce origin work.
 */
final class EarlyRuntime
{
    /** @var bool */
    private static $booted = false;

    /** @var array<int,array<string,mixed>> */
    private static $state = array();

    /** @var int */
    private static $suppressedWidgets = 0;

    /** @var string */
    private static $originAdmissionState = 'standby';

    /** @var int */
    private static $originAdmissionSlot = -1;

    /** @var string */
    private static $originAdmissionToken = '';

    /** @var string */
    private static $originAdmissionBackend = 'standby';

    private const ORIGIN_ADMISSION_DEFAULT_SLOTS = 2;
    private const ORIGIN_ADMISSION_RETRY_AFTER = 5;
    private const ORIGIN_ADMISSION_LEASE_SECONDS = 120;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        if (!defined('YPERF_EARLY_RUNTIME_ACTIVE')) {
            define('YPERF_EARLY_RUNTIME_ACTIVE', true);
        }
        if (!defined('YPERF_EARLY_RUNTIME_VERSION')) {
            define('YPERF_EARLY_RUNTIME_VERSION', YPERF_EARLY_RUNTIME_SOURCE_VERSION);
        }

        // Portable origin survival: cached requests finish before this MU layer.
        // A content crawler that reaches this point is consuming a live PHP
        // worker, so bound concurrent crawler origin work before normal plugins,
        // WooCommerce, WPML and theme execution begin.
        self::admitCrawlerOrigin();

        add_action('init', array(__CLASS__, 'removeOembedDiscovery'), PHP_INT_MAX);
        add_action('wp_loaded', array(__CLASS__, 'normalizeCrawlerStateAction'), -9999);

        add_action('pre_get_posts', array(__CLASS__, 'prepareArchive'), PHP_INT_MAX);
        add_filter('post_limits', array(__CLASS__, 'extendLimit'), PHP_INT_MAX, 2);
        add_filter('posts_request', array(__CLASS__, 'observeSql'), PHP_INT_MAX, 2);
        add_filter('the_posts', array(__CLASS__, 'trimSentinel'), PHP_INT_MAX, 2);
        add_filter('sidebars_widgets', array(__CLASS__, 'suppressExpensiveWidgets'), PHP_INT_MAX);
        add_action('wp', array(__CLASS__, 'replacePagination'), PHP_INT_MAX);
        add_action('template_redirect', array(__CLASS__, 'emitProbeHeaders'), PHP_INT_MAX);
    }

    /**
     * Bound concurrent cold-origin work from known content crawlers.
     *
     * Page-cache HITs complete before MU plugins load, so this gate targets the
     * expensive path that actually reaches WordPress. Human traffic, private
     * routes, signed YPerf probes and normal non-crawler traffic keep their
     * existing execution path. When the crawler-origin budget is full, the
     * crawler receives a standards-based Retry-After response and can continue
     * shortly while the remaining PHP worker reserve serves interactive work.
     */
    private static function admitCrawlerOrigin(): void
    {
        if (!self::deepGuardEnabled() || !self::isGetOrHead() || self::isYperfProbe() || !self::isContentCrawler()) {
            return;
        }
        if (self::hasPrivateRuntimeCookie() || self::isPrivateRuntimePath()) {
            return;
        }

        self::$originAdmissionState = 'eligible';
        self::$originAdmissionToken = self::originAdmissionToken();
        self::$originAdmissionBackend = 'wordpress_options';
        $slots = self::originAdmissionSlots();

        // Portable WordPress semaphore. add_option() is atomic because
        // option_name is unique, and autoload stays disabled so the two small
        // leases remain outside the ordinary frontend option payload. Existing
        // persistent object caches accelerate get_option() reads automatically
        // while database uniqueness remains the concurrency authority.
        $now = time();
        for ($slot = 0; $slot < $slots; ++$slot) {
            $key = self::originAdmissionOptionKey($slot);
            $current = get_option($key, '');
            if (is_string($current) && $current !== '') {
                $parts = explode('|', $current, 2);
                $expires = isset($parts[1]) ? (int) $parts[1] : 0;
                if ($expires > 0 && $expires <= $now) {
                    delete_option($key);
                    $current = '';
                }
            }
            if ($current !== '') {
                continue;
            }

            $value = self::$originAdmissionToken . '|' . ($now + self::ORIGIN_ADMISSION_LEASE_SECONDS);
            if (add_option($key, $value, '', false)) {
                self::$originAdmissionSlot = $slot;
                self::$originAdmissionState = 'admitted';
                register_shutdown_function(array(__CLASS__, 'releaseCrawlerOriginAdmission'));
                return;
            }
        }

        self::$originAdmissionState = 'retry';
        self::sendCrawlerRetryResponse();
    }

    public static function releaseCrawlerOriginAdmission(): void
    {
        if (self::$originAdmissionSlot < 0 || self::$originAdmissionToken === '') {
            return;
        }

        if (self::$originAdmissionBackend === 'wordpress_options') {
            $key = self::originAdmissionOptionKey(self::$originAdmissionSlot);
            $current = get_option($key, '');
            if (is_string($current) && strpos($current, self::$originAdmissionToken . '|') === 0) {
                delete_option($key);
            }
        }

        self::$originAdmissionSlot = -1;
        self::$originAdmissionToken = '';
    }

    /** @return array<string,mixed> */
    public static function originAdmissionStatus(): array
    {
        return array(
            'available' => true,
            'configured' => self::deepGuardEnabled(),
            'mode' => 'crawler_cold_origin',
            'slots' => self::originAdmissionSlots(),
            'retry_after_seconds' => self::ORIGIN_ADMISSION_RETRY_AFTER,
            'lease_seconds' => self::ORIGIN_ADMISSION_LEASE_SECONDS,
            'current_request_state' => self::$originAdmissionState,
            'current_request_slot' => self::$originAdmissionSlot,
            'current_request_backend' => self::$originAdmissionBackend,
            'third_party_service_required' => false,
            'execution_hook' => defined('YPERF_EARLY_RUNTIME_PHASE_HOOK') ? (string) YPERF_EARLY_RUNTIME_PHASE_HOOK : 'direct',
            'execution_priority' => defined('YPERF_EARLY_RUNTIME_PHASE_PRIORITY') ? (int) YPERF_EARLY_RUNTIME_PHASE_PRIORITY : 0,
            'security_precedes_capacity_priority' => -1000,
            'coordination_contract' => 'security_then_capacity',
        );
    }

    private static function originAdmissionSlots(): int
    {
        $slots = self::ORIGIN_ADMISSION_DEFAULT_SLOTS;
        if (defined('YPERF_ORIGIN_CRAWLER_SLOTS')) {
            $slots = (int) constant('YPERF_ORIGIN_CRAWLER_SLOTS');
        }
        return max(1, min(8, $slots));
    }

    private static function originAdmissionToken(): string
    {
        try {
            return bin2hex(random_bytes(12));
        } catch (\Throwable $error) {
            $entropy = microtime(true) . '|' . getmypid();
            if (function_exists('wp_rand')) {
                $entropy .= '|' . wp_rand();
            }
            return hash('sha256', $entropy);
        }
    }

    private static function originAdmissionOptionKey(int $slot): string
    {
        return 'yperf_origin_admission_slot_' . max(0, $slot);
    }

    private static function hasPrivateRuntimeCookie(): bool
    {
        $cookie = isset($_SERVER['HTTP_COOKIE']) ? sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_COOKIE'])) : '';
        if ($cookie === '') {
            return false;
        }
        return preg_match('/(?:wordpress_logged_in_|wp_woocommerce_session_|woocommerce_items_in_cart|woocommerce_cart_hash)/i', $cookie) === 1;
    }

    private static function isPrivateRuntimePath(): bool
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_URI'])) : '/';
        $path = wp_parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = '/';
        }
        return preg_match('~^/(?:[a-z]{2}(?:-[A-Za-z]{2})?/)?(?:wp-admin|wp-json|wc-api|cart|checkout|my-account)(?:/|$)|^/(?:xmlrpc|wp-cron|wp-login)\\.php(?:/|$)~i', $path) === 1;
    }

    private static function sendCrawlerRetryResponse(): void
    {
        if (!headers_sent()) {
            header('HTTP/1.1 503 Service Unavailable', true, 503);
            header('Retry-After: ' . self::ORIGIN_ADMISSION_RETRY_AFTER);
            header('Cache-Control: no-store, private, max-age=0');
            header('Content-Type: text/plain; charset=UTF-8');
            header('X-YPerf-Origin-Admission: retry');
            header('X-YPerf-Early-Runtime: ' . YPERF_EARLY_RUNTIME_SOURCE_VERSION);
        }
        echo "Origin capacity is serving current requests. Please retry shortly.\n";
        exit;
    }

    public static function removeOembedDiscovery(): void
    {
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
    }

    /**
     * Useful public content stays available. Only crawler requests that encode a
     * state-changing WooCommerce/YITH action in a GET URL are normalized to the
     * same clean public path before those actions execute.
     */
    public static function normalizeCrawlerStateAction(): void
    {
        if (!self::isGetOrHead() || is_admin() || is_user_logged_in() || !self::isContentCrawler()) {
            return;
        }
        if (!self::hasStateActionQuery()) {
            return;
        }

        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_URI'])) : '/';
        $path = wp_parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = '/';
        }

        $target = home_url($path);
        if (!headers_sent()) {
            header('X-YPerf-State-Action: normalized');
            header('X-YPerf-Early-Runtime: ' . YPERF_EARLY_RUNTIME_SOURCE_VERSION);
        }

        wp_safe_redirect($target, 301, 'Yablonsky Performance');
        exit;
    }

    public static function prepareArchive(\WP_Query $query): void
    {
        if (!self::eligibleArchive($query)) {
            return;
        }

        $id = spl_object_id($query);
        $perPage = max(1, (int) $query->get('posts_per_page'));
        self::$state[$id] = array(
            'page' => self::page($query),
            'per_page' => $perPage,
            'has_next' => false,
            'sql_clean' => null,
            'limit_extended' => false,
            'query_bearing' => self::hasQueryString(),
        );

        $query->set('no_found_rows', true);
    }

    public static function extendLimit(string $limits, \WP_Query $query): string
    {
        $id = spl_object_id($query);
        if (!isset(self::$state[$id])) {
            return $limits;
        }

        $perPage = (int) self::$state[$id]['per_page'];
        $pattern = '/\bLIMIT\s+([0-9]+)\s*,\s*' . preg_quote((string) $perPage, '/') . '\s*$/i';
        $replacement = 'LIMIT $1, ' . ($perPage + 1);
        $newLimits = preg_replace($pattern, $replacement, $limits, 1, $count);
        if ($count === 1 && is_string($newLimits)) {
            self::$state[$id]['limit_extended'] = true;
            return $newLimits;
        }
        return $limits;
    }

    public static function observeSql(string $sql, \WP_Query $query): string
    {
        $id = spl_object_id($query);
        if (isset(self::$state[$id])) {
            self::$state[$id]['sql_clean'] = stripos($sql, 'SQL_CALC_FOUND_ROWS') === false;
        }
        return $sql;
    }

    /** @param array<int,mixed> $posts @return array<int,mixed> */
    public static function trimSentinel(array $posts, \WP_Query $query): array
    {
        $id = spl_object_id($query);
        if (!isset(self::$state[$id])) {
            return $posts;
        }

        $perPage = (int) self::$state[$id]['per_page'];
        $page = (int) self::$state[$id]['page'];
        $count = count($posts);

        if ($count === 0) {
            self::$state[$id]['has_next'] = false;
            $query->found_posts = 0;
            $query->max_num_pages = 0;
            return $posts;
        }

        $hasNext = $count > $perPage;
        if ($hasNext) {
            $posts = array_slice($posts, 0, $perPage);
        }
        $visible = count($posts);
        self::$state[$id]['has_next'] = $hasNext;
        if ($hasNext) {
            $query->found_posts = ($page * $perPage) + 1;
            $query->max_num_pages = $page + 1;
        } else {
            $query->found_posts = (($page - 1) * $perPage) + $visible;
            $query->max_num_pages = $page;
        }
        return $posts;
    }

    /** @param array<string,array<int,string>> $sidebars @return array<string,array<int,string>> */
    public static function suppressExpensiveWidgets(array $sidebars): array
    {
        global $wp_query;
        if (!($wp_query instanceof \WP_Query) || !self::eligibleArchive($wp_query)) {
            return $sidebars;
        }

        $patterns = array(
            '/^woocommerce_price_filter-/i',
            '/^woocommerce_layered_nav-/i',
            '/^woocommerce_product_categories-/i',
            '/_wc_attribute_filter-/i',
            '/_wc_categories-/i',
            '/_attribute_filter-/i',
        );
        $patterns = apply_filters('yablonsky_performance_early_runtime_expensive_widget_patterns', $patterns, $wp_query);
        if (!is_array($patterns)) {
            return $sidebars;
        }

        foreach ($sidebars as $sidebar => $widgets) {
            if (!is_array($widgets) || $widgets === array()) {
                continue;
            }
            $kept = array();
            foreach ($widgets as $widget) {
                $expensive = false;
                foreach ($patterns as $pattern) {
                    if (is_string($pattern) && $pattern !== '' && preg_match($pattern, (string) $widget) === 1) {
                        $expensive = true;
                        break;
                    }
                }
                if ($expensive) {
                    ++self::$suppressedWidgets;
                    continue;
                }
                $kept[] = $widget;
            }
            $sidebars[$sidebar] = $kept;
        }

        return $sidebars;
    }

    public static function replacePagination(): void
    {
        global $wp_query;
        if (!($wp_query instanceof \WP_Query)) {
            return;
        }
        $id = spl_object_id($wp_query);
        if (!isset(self::$state[$id])) {
            return;
        }

        remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
        remove_action('woocommerce_after_shop_loop', 'woocommerce_pagination', 10);
        add_action('woocommerce_after_shop_loop', array(__CLASS__, 'renderPagination'), 10);
    }

    public static function renderPagination(): void
    {
        global $wp_query;
        if (!($wp_query instanceof \WP_Query)) {
            return;
        }
        $id = spl_object_id($wp_query);
        if (!isset(self::$state[$id])) {
            return;
        }

        $page = (int) self::$state[$id]['page'];
        $hasNext = !empty(self::$state[$id]['has_next']);
        $previous = $page > 1 ? get_pagenum_link($page - 1) : '';
        $next = $hasNext ? get_pagenum_link($page + 1) : '';

        echo '<nav class="woocommerce-pagination yperf-seek-pagination" aria-label="' . esc_attr__('Product pagination', 'yablonsky-performance') . '"><ul class="page-numbers">';
        if ($previous !== '') {
            echo '<li><a class="prev page-numbers" rel="prev" href="' . esc_url($previous) . '">&larr; ' . esc_html__('Previous', 'yablonsky-performance') . '</a></li>';
        }
        echo '<li><span aria-current="page" class="page-numbers current">' . esc_html((string) $page) . '</span></li>';
        if ($next !== '') {
            echo '<li><a class="next page-numbers" rel="next" href="' . esc_url($next) . '">' . esc_html__('Next', 'yablonsky-performance') . ' &rarr;</a></li>';
        }
        echo '</ul></nav>';
    }

    public static function emitProbeHeaders(): void
    {
        if (headers_sent() || !self::isYperfProbe()) {
            return;
        }
        header('X-YPerf-Early-Runtime: ' . YPERF_EARLY_RUNTIME_SOURCE_VERSION);

        global $wp_query;
        if (!($wp_query instanceof \WP_Query)) {
            return;
        }
        $id = spl_object_id($wp_query);
        if (!isset(self::$state[$id])) {
            return;
        }
        $state = self::$state[$id];
        header('X-YPerf-Query-Cost-Guard: ' . (!empty($state['query_bearing']) ? 'deep_filtered_archive' : 'deep_archive'));
        header('X-YPerf-Deep-Page: ' . (int) ($state['page'] ?? 1));
        header('X-YPerf-Found-Rows-Suppressed: ' . (!empty($state['sql_clean']) ? '1' : '0'));
        header('X-YPerf-Limit-Plus-One: ' . (!empty($state['limit_extended']) ? '1' : '0'));
        header('X-YPerf-Facet-Widgets-Suppressed: ' . self::$suppressedWidgets);
    }

    private static function eligibleArchive(\WP_Query $query): bool
    {
        if (!self::deepGuardEnabled()) {
            return false;
        }
        if (is_admin() || is_user_logged_in() || !$query->is_main_query() || !self::isGetOrHead()) {
            return false;
        }
        if (self::page($query) < 2) {
            return false;
        }

        $eligible = $query->is_post_type_archive('product');
        if (!$eligible && post_type_exists('product')) {
            $taxonomies = get_object_taxonomies('product');
            $eligible = !empty($taxonomies) && $query->is_tax($taxonomies);
        }
        $eligible = (bool) apply_filters('yablonsky_performance_deep_archive_guard_eligible', $eligible, $query);
        return (bool) apply_filters('yablonsky_performance_cache_deep_archive_guard_eligible', $eligible, $query);
    }

    private static function deepGuardEnabled(): bool
    {
        $enabled = get_option('yperf_deep_archive_guard', '0') === '1';
        $enabled = (bool) apply_filters('yablonsky_performance_deep_archive_guard_enabled', $enabled);
        return (bool) apply_filters('yablonsky_performance_cache_deep_archive_guard_enabled', $enabled);
    }

    private static function page(\WP_Query $query): int
    {
        return max(1, (int) $query->get('paged'), (int) $query->get('page'));
    }

    private static function hasQueryString(): bool
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_URI'])) : '';
        $query = wp_parse_url($uri, PHP_URL_QUERY);
        return is_string($query) && $query !== '';
    }

    private static function hasStateActionQuery(): bool
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_URI'])) : '';
        $rawQuery = wp_parse_url($uri, PHP_URL_QUERY);
        if (!is_string($rawQuery) || $rawQuery === '') {
            return false;
        }

        $query = array();
        wp_parse_str($rawQuery, $query);
        if (!is_array($query)) {
            return false;
        }

        $keys = array('add_to_wishlist', 'add-to-cart', 'add_to_cart', 'remove_item');
        foreach ($keys as $key) {
            if (array_key_exists($key, $query)) {
                return true;
            }
        }

        $action = isset($query['action']) ? sanitize_key((string) $query['action']) : '';
        if ($action === 'yith-woocompare-add-product') {
            return true;
        }

        $wcAjax = isset($query['wc-ajax']) ? sanitize_key((string) $query['wc-ajax']) : '';
        return $wcAjax === 'get_refreshed_fragments';
    }

    private static function isContentCrawler(): bool
    {
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_USER_AGENT'])) : '';
        if ($agent === '') {
            return false;
        }
        // User-triggered fetchers are not background crawlers and should not
        // inherit the crawler admission budget. Google-Extended and
        // Applebot-Extended are robots policy tokens, not HTTP agents.
        if (preg_match('/(?:ChatGPT-User|Claude-User|Perplexity-User|Google-Extended|Applebot-Extended)/i', $agent) === 1) {
            return false;
        }
        // UA is merely a workload hint, NOT verified provider identity.
        return preg_match('/(?:bot|crawler|spider|meta-externalagent)/i', $agent) === 1;
    }

    private static function isGetOrHead(): bool
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper(sanitize_key(wp_unslash((string) $_SERVER['REQUEST_METHOD']))) : 'GET';
        return in_array($method, array('GET', 'HEAD'), true);
    }

    private static function isYperfProbe(): bool
    {
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_USER_AGENT'])) : '';
        return strpos($agent, 'Yablonsky-Performance/') === 0 || strpos($agent, 'Yablonsky-Performance-Origin-Probe/') === 0;
    }
}
