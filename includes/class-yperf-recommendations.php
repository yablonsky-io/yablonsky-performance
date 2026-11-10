<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class Recommendations
{
    /** @param array<string,mixed> $caps @param array<string,mixed> $runtime @param array<string,mixed> $nativeProof @return array<int,array<string,mixed>> */
    public static function build(array $caps, array $runtime, array $nativeProof = array()): array
    {
        $items = array();
        $app = is_array($caps['application'] ?? null) ? $caps['application'] : array();
        $object = is_array($caps['object_cache'] ?? null) ? $caps['object_cache'] : array();
        $redisProof = is_array($nativeProof['object_cache']['redis'] ?? null) ? $nativeProof['object_cache']['redis'] : array();

        if (!empty($object['redis_extension']) && empty($object['external_enabled'])) {
            $redisState = (string) ($redisProof['state'] ?? '');
            if (in_array($redisState, array('high_memory_pressure', 'active_eviction_pressure'), true)) {
                $items[] = self::item(
                    'performance',
                    __('Redis pressure before object-cache adoption', 'yablonsky-performance'),
                    __('Redis is reachable and fast, but host evidence shows meaningful eviction pressure. Verify existing consumers, database/prefix isolation and memory budget before adding WordPress object-cache load.', 'yablonsky-performance'),
                    __('Review Redis ownership first', 'yablonsky-performance'),
                    'attention'
                );
            } else {
                $items[] = self::item(
                    'performance',
                    __('Persistent object cache opportunity', 'yablonsky-performance'),
                    __('Redis support is visible to PHP, but WordPress is not currently using a confirmed persistent object cache. Benchmark it and verify current Redis consumers before enabling.', 'yablonsky-performance'),
                    __('Benchmark / setup candidate', 'yablonsky-performance'),
                    'attention'
                );
            }
        }

        $originCost = is_array($runtime['origin_cost_summary'] ?? null) ? $runtime['origin_cost_summary'] : array();
        if ((int) ($originCost['measured_routes'] ?? 0) > 0) {
            $maxDb = (float) ($originCost['max_db_time_ms'] ?? 0);
            $slow100 = (int) ($originCost['slow_queries_100ms_total'] ?? 0);
            $foundRows = (int) ($originCost['sql_calc_found_rows_total'] ?? 0);
            if ($slow100 > 0 || $maxDb >= 250.0 || $foundRows > 0) {
                $items[] = self::item(
                    'performance',
                    __('Origin query cost needs attention', 'yablonsky-performance'),
                    sprintf(
                        /* translators: 1: max DB ms, 2: slow query count, 3: SQL_CALC_FOUND_ROWS count */
                        __('Signed Runtime probes measured up to %1$.1f ms of database time, %2$d queries at or above 100 ms, and %3$d SQL_CALC_FOUND_ROWS occurrences. Fix origin cost before relying on cache alone.', 'yablonsky-performance'),
                        $maxDb,
                        $slow100,
                        $foundRows
                    ),
                    __('Review Origin Cost evidence', 'yablonsky-performance'),
                    'attention'
                );
            }
        }

        if (empty($app['ypm']) && empty($app['wpml']) && empty($app['polylang'])) {
            $items[] = self::item(
                'ecosystem',
                __('Multilingual growth', 'yablonsky-performance'),
                __('No multilingual authority was detected. If this site needs multiple languages, Yablonsky Multilingual can provide language and URL semantics that YPerf can invalidate precisely.', 'yablonsky-performance'),
                __('Explore YMulti when multilingual is needed', 'yablonsky-performance'),
                'optional'
            );
        }

        if (empty($app['yps'])) {
            $items[] = self::item(
                'ecosystem',
                __('Search visibility', 'yablonsky-performance'),
                __('Yablonsky SEO was not detected. YPerf can expose performance evidence while YSEO owns SEO output and can use the same site context without duplicating performance authority.', 'yablonsky-performance'),
                __('Explore YSEO', 'yablonsky-performance'),
                'optional'
            );
        }

        if (self::contentScaleSignal()) {
            $items[] = self::item(
                'ecosystem',
                __('Publishing workflow opportunity', 'yablonsky-performance'),
                __('This site has enough published content for publishing workflow automation to become relevant. A future Yablonsky Publisher integration can use YPerf performance evidence without turning the cache dashboard into an advertising surface.', 'yablonsky-performance'),
                __('Publisher integration path', 'yablonsky-performance'),
                'optional'
            );
        }

        $attention = (int) (($runtime['summary']['attention'] ?? 0));
        if ($attention > 0) {
            $items[] = self::item(
                'analysis',
                __('Deep analysis candidate', 'yablonsky-performance'),
                __('Runtime Acceptance contains an attention signal. The local report is ready to be inspected manually now and can later be sent explicitly to Yablonsky AI for deeper reasoning.', 'yablonsky-performance'),
                __('AI-ready evidence', 'yablonsky-performance'),
                'attention'
            );
        }

        return array_slice($items, 0, 5);
    }

    /** @return array<string,mixed> */
    private static function item(string $type, string $title, string $reason, string $action, string $state): array
    {
        return array(
            'id'     => sanitize_key($type . '-' . $title),
            'type'   => $type,
            'title'  => $title,
            'reason' => $reason,
            'action' => $action,
            'state'  => $state,
        );
    }

    private static function contentScaleSignal(): bool
    {
        $posts = wp_count_posts('post');
        $publishedPosts = is_object($posts) && isset($posts->publish) ? (int) $posts->publish : 0;
        $products = post_type_exists('product') ? wp_count_posts('product') : null;
        $publishedProducts = is_object($products) && isset($products->publish) ? (int) $products->publish : 0;
        return ($publishedPosts + $publishedProducts) >= 20;
    }
}
