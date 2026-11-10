<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class InvalidationBus
{
    private const MAX_URLS = 200;
    public static function register(): void
    {
        add_action('save_post', array(__CLASS__, 'onSavePost'), 20, 3);
        add_action('before_delete_post', array(__CLASS__, 'onBeforeDeletePost'), 20, 2);
        add_action('edited_term', array(__CLASS__, 'onEditedTerm'), 20, 3);
        add_action('deleted_term', array(__CLASS__, 'invalidateAll'), 20, 0);
        add_action('update_option_blog_public', array(__CLASS__, 'invalidateAll'), 20, 0);
        add_action('update_option_permalink_structure', array(__CLASS__, 'invalidateAll'), 20, 0);
        add_action('switch_theme', array(__CLASS__, 'invalidateAll'), 20, 0);

        // Cross-plugin semantic contract: YPM/YPS/future plugins emit intent; YPerf owns execution.
        add_action('yablonsky_performance_invalidate_urls', array(__CLASS__, 'invalidateUrls'), 10, 2);
        add_action('yablonsky_performance_invalidate_all', array(__CLASS__, 'invalidateAll'), 10, 1);
        // Compatibility aliases for YMulti/YSEO builds that still emit the historical contract.
        add_action('yablonsky_performance_cache_invalidate_urls', array(__CLASS__, 'invalidateUrls'), 10, 2);
        add_action('yablonsky_performance_cache_invalidate_all', array(__CLASS__, 'invalidateAll'), 10, 1);
    }

    public static function onSavePost(int $postId, \WP_Post $post, bool $update): void
    {
        unset($update);
        if (wp_is_post_revision($postId) || wp_is_post_autosave($postId)) {
            return;
        }
        if ($post->post_status === 'auto-draft') {
            return;
        }

        $urls = array_filter(array(
            get_permalink($postId),
            home_url('/'),
            get_post_type_archive_link($post->post_type),
        ));

        // Category/product archives may show this post even when their own
        // URLs are unchanged. Purge bounded public term pages, not all URLs.
        $taxonomies = get_object_taxonomies($post->post_type);
        foreach ((array) $taxonomies as $taxonomy) {
            $terms = get_the_terms($postId, (string) $taxonomy);
            if (!is_array($terms)) {
                continue;
            }
            foreach ($terms as $term) {
                $termUrl = get_term_link($term);
                if (!is_wp_error($termUrl)) {
                    $urls[] = $termUrl;
                }
                if (count($urls) > self::MAX_URLS) {
                    self::invalidateAll('core:save_post_many_terms');
                    return;
                }
            }
        }
        self::invalidateUrls($urls, 'core:save_post');
    }

    public static function onBeforeDeletePost(int $postId, \WP_Post $post): void
    {
        $urls = array_filter(array(
            get_permalink($postId),
            home_url('/'),
            get_post_type_archive_link($post->post_type),
        ));
        self::invalidateUrls($urls, 'core:before_delete_post');
    }

    public static function onEditedTerm(int $termId, int $ttId, string $taxonomy): void
    {
        unset($ttId);
        $url = get_term_link($termId, $taxonomy);
        if (!is_wp_error($url)) {
            self::invalidateUrls(array($url, home_url('/')), 'core:edited_term');
            return;
        }
        self::invalidateAll('core:edited_term_fallback');
    }

    /**
     * @param mixed $urls
     * @param mixed $context
     */
    public static function invalidateUrls($urls, $context = 'external'): void
    {
        $clean = array();
        foreach ((array) $urls as $url) {
            $url = esc_url_raw((string) $url);
            if ($url === '' || !self::sameOrigin($url)) {
                continue;
            }
            $clean[$url] = $url;
            if (count($clean) >= self::MAX_URLS) {
                break;
            }
        }

        if ($clean === array()) {
            return;
        }

        $payload = array_values($clean);
        $context = sanitize_key((string) $context);
        self::record('urls', array('urls' => $payload, 'context' => $context));

        /**
         * Native/portable page-cache adapters subscribe here.
         *
         * @param array<int,string> $urls
         * @param string            $context
         */
        do_action('yablonsky_performance_adapter_purge_urls', $payload, $context);
    }

    /** @param mixed $context */
    public static function invalidateAll($context = 'external'): void
    {
        $context = sanitize_key((string) $context);
        self::record('all', array('context' => $context));

        /** @param string $context */
        do_action('yablonsky_performance_adapter_purge_all', $context);
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

    /** @param array<string,mixed> $payload */
    private static function record(string $type, array $payload): void
    {
        $event = array(
            'time'    => time(),
            'type'    => $type,
            'payload' => $payload,
        );

        update_option('yperf_last_invalidation', $event, false);

        $history = get_option('yperf_invalidation_log_v1', array());
        if (!is_array($history)) {
            $history = array();
        }
        array_unshift($history, $event);
        if (count($history) > 50) {
            $history = array_slice($history, 0, 50);
        }
        update_option('yperf_invalidation_log_v1', $history, false);
    }
}
