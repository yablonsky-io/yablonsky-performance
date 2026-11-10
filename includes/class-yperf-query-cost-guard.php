<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class QueryCostGuard
{
    /** @var array<int,array<string,mixed>> */
    private static $state = array();

    public static function register(): void
    {
        add_action('pre_get_posts', array(__CLASS__, 'prepare'), PHP_INT_MAX);
        add_filter('post_limits', array(__CLASS__, 'extendLimit'), PHP_INT_MAX, 2);
        add_filter('posts_request', array(__CLASS__, 'observeSql'), PHP_INT_MAX, 2);
        add_filter('the_posts', array(__CLASS__, 'trimSentinel'), PHP_INT_MAX, 2);
        add_action('wp', array(__CLASS__, 'replacePagination'), PHP_INT_MAX);
        add_action('template_redirect', array(__CLASS__, 'emitDiagnosticHeader'), PHP_INT_MAX);
    }

    public static function prepare(\WP_Query $query): void
    {
        if (!self::eligible($query)) {
            return;
        }

        $id = spl_object_id($query);
        $perPage = max(1, (int) $query->get('posts_per_page'));
        self::$state[$id] = array(
            'page'           => self::page($query),
            'per_page'       => $perPage,
            'has_next'       => false,
            'sql_clean'      => null,
            'limit_extended' => false,
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

    /**
     * @param array<int,mixed> $posts
     * @return array<int,mixed>
     */
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

        $visibleCount = count($posts);
        self::$state[$id]['has_next'] = $hasNext;
        if ($hasNext) {
            $query->found_posts = ($page * $perPage) + 1;
            $query->max_num_pages = $page + 1;
        } else {
            $query->found_posts = (($page - 1) * $perPage) + $visibleCount;
            $query->max_num_pages = $page;
        }

        return $posts;
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
        $hasNext = (bool) self::$state[$id]['has_next'];
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


    public static function emitDiagnosticHeader(): void
    {
        if (self::$state === array() || headers_sent() || !self::isYperfProbe()) {
            return;
        }
        header('X-YPerf-First-Hit: protected');
    }

    private static function isYperfProbe(): bool
    {
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_USER_AGENT'])) : '';
        return strpos($agent, 'Yablonsky-Performance/') === 0;
    }

    private static function eligible(\WP_Query $query): bool
    {
        if (!self::enabled()) {
            return false;
        }
        if (is_admin() || is_user_logged_in() || !$query->is_main_query()) {
            return false;
        }
        if (isset($_SERVER['REQUEST_METHOD']) && !in_array(strtoupper(sanitize_key(wp_unslash((string) $_SERVER['REQUEST_METHOD']))), array('GET', 'HEAD'), true)) {
            return false;
        }
        if (self::hasQueryString()) {
            return false;
        }
        if (self::page($query) < 2) {
            return false;
        }

        $isProductArchive = $query->is_post_type_archive('product');
        $taxonomies = get_object_taxonomies('product');
        $isProductTaxonomy = !empty($taxonomies) && $query->is_tax($taxonomies);
        $eligible = $isProductArchive || $isProductTaxonomy;

        /**
         * Last-mile compatibility gate for themes/catalog plugins.
         * Defaults to the conservative automatic Woo conditions above.
         */
        $eligible = (bool) apply_filters('yablonsky_performance_deep_archive_guard_eligible', $eligible, $query);
        return (bool) apply_filters('yablonsky_performance_cache_deep_archive_guard_eligible', $eligible, $query);
    }

    private static function hasQueryString(): bool
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_URI'])) : '';
        $query = wp_parse_url($uri, PHP_URL_QUERY);
        return is_string($query) && $query !== '';
    }

    private static function enabled(): bool
    {
        $enabled = get_option('yperf_deep_archive_guard', '0') === '1';
        $enabled = (bool) apply_filters('yablonsky_performance_deep_archive_guard_enabled', $enabled);
        return (bool) apply_filters('yablonsky_performance_cache_deep_archive_guard_enabled', $enabled);
    }

    private static function page(\WP_Query $query): int
    {
        return max(1, (int) $query->get('paged'), (int) $query->get('page'));
    }
}
