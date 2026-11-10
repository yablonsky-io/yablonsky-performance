<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class PurgeController
{
    private const LAST_OPTION = 'yperf_last_purge_result_v1';

    public static function register(): void
    {
        if (get_option('yperf_external_purge_bridge', '0') === '1') {
            add_action('yablonsky_performance_adapter_purge_urls', array(__CLASS__, 'bridgeUrls'), 20, 2);
            add_action('yablonsky_performance_adapter_purge_all', array(__CLASS__, 'bridgeAll'), 20, 1);
        }
    }

    /** @param array<int,string> $urls */
    public static function bridgeUrls(array $urls, string $context = 'semantic'): void
    {
        $clean = array();
        foreach ($urls as $url) {
            $url = esc_url_raw((string) $url);
            if ($url === '') {
                continue;
            }
            $clean[] = $url;
            self::purge('url', $url, 'bridge:' . sanitize_key($context));
        }
        if ($clean !== array()) {
            CloudflareEdge::purgeUrls(array_values(array_unique($clean)));
        }
    }

    public static function bridgeAll(string $context = 'semantic'): void
    {
        self::purge('all', '', 'bridge:' . sanitize_key($context));
        CloudflareEdge::purgeHost();
    }

    /** @return array<string,mixed> */
    public static function purge(string $mode, string $url = '', string $context = 'manual'): array
    {
        $started = microtime(true);
        $mode = sanitize_key($mode);
        $allowed = array('smart', 'url', 'page', 'object', 'database', 'minify', 'all');
        if (!in_array($mode, $allowed, true)) {
            $mode = 'smart';
        }
        $url = esc_url_raw(trim($url));
        if ($url !== '' && !self::sameOrigin($url)) {
            return self::saveResult(array(
                'mode' => $mode,
                'status' => 'blocked',
                'provider' => '',
                'count' => 0,
                'url' => $url,
                'context' => $context,
                'details' => 'Only same-origin URLs can be purged.',
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'confirmed' => false,
                'affected' => 0,
            ));
        }

        $provider = '';
        $status = 'signaled';
        $count = 0;
        $affected = 0;
        $details = '';
        $confirmed = false;
        $edgeStatus = 'not_applicable';
        $edgeDetails = '';

        try {
            if ($mode === 'smart' || $mode === 'url') {
                if ($url === '') {
                    $url = home_url('/');
                }
                if (PageCacheExecutor::enabled()) {
                    $execution = PageCacheExecutor::purgeUrls(array($url), $context);
                    $provider = 'Yablonsky Performance';
                    $status = empty($execution['delete_failures']) ? 'pass' : 'fail';
                    $count = 1;
                    $affected = max(0, (int) ($execution['deleted_files'] ?? 0));
                    $confirmed = $status === 'pass';
                    $details = $status === 'pass' ? 'Selected URL purge was executed by the YPerf Page Cache.' : 'YPerf Page Cache reported a file deletion failure.';
                } elseif (function_exists('w3tc_flush_url')) {
                    \w3tc_flush_url($url);
                    $provider = 'W3 Total Cache';
                    $status = 'pass';
                    $count = 1;
                    $confirmed = true;
                    $details = 'Selected URL was purged through the active provider API.';
                } else {
                    do_action('yablonsky_performance_purge_url', $url, $context);
                    $count = 1;
                    $details = 'Purge intent was emitted; no provider-specific executor confirmed completion.';
                }
            } elseif ($mode === 'page') {
                if (PageCacheExecutor::enabled()) {
                    $execution = PageCacheExecutor::purgeAll($context);
                    $provider = 'Yablonsky Performance';
                    $status = empty($execution['delete_failures']) ? 'pass' : 'fail';
                    $affected = max(0, (int) ($execution['deleted_files'] ?? 0));
                    $confirmed = $status === 'pass';
                    $details = $status === 'pass' ? 'The YPerf Page Cache purge completed.' : 'YPerf Page Cache reported a file deletion failure.';
                } elseif (function_exists('w3tc_flush_posts')) {
                    \w3tc_flush_posts();
                    $provider = 'W3 Total Cache';
                    $status = 'pass';
                    $confirmed = true;
                    $details = 'Page cache was purged through the active provider API.';
                } else {
                    do_action('yablonsky_performance_purge_page_cache', $context);
                    $details = 'Page-cache purge intent was emitted.';
                }
            } elseif ($mode === 'object') {
                if (function_exists('w3tc_objectcache_flush')) {
                    \w3tc_objectcache_flush();
                    $provider = 'W3 Total Cache';
                    $status = 'pass';
                    $confirmed = true;
                    $details = 'Object cache was purged through the active provider API.';
                } elseif (function_exists('wp_cache_flush') && wp_using_ext_object_cache()) {
                    wp_cache_flush();
                    $provider = 'WordPress external object cache';
                    $status = 'pass';
                    $confirmed = true;
                    $details = 'WordPress external object cache was flushed.';
                } else {
                    $status = 'skip';
                    $details = 'No active persistent object-cache executor is available.';
                }
            } elseif ($mode === 'database') {
                if (function_exists('w3tc_dbcache_flush')) {
                    \w3tc_dbcache_flush();
                    $provider = 'W3 Total Cache';
                    $status = 'pass';
                    $confirmed = true;
                    $details = 'Database cache was purged through the active provider API.';
                } else {
                    $status = 'skip';
                    $details = 'No database-cache executor is available.';
                }
            } elseif ($mode === 'minify') {
                if (function_exists('w3tc_minify_flush')) {
                    \w3tc_minify_flush();
                    $provider = 'W3 Total Cache';
                    $status = 'pass';
                    $confirmed = true;
                    $details = 'Minify cache was purged through the active provider API.';
                } else {
                    $status = 'skip';
                    $details = 'No minify-cache executor is available.';
                }
            } elseif ($mode === 'all') {
                if (PageCacheExecutor::enabled()) {
                    $execution = PageCacheExecutor::purgeAll($context);
                    $provider = 'Yablonsky Performance';
                    $status = empty($execution['delete_failures']) ? 'pass' : 'fail';
                    $affected = max(0, (int) ($execution['deleted_files'] ?? 0));
                    $confirmed = $status === 'pass';
                    $details = $status === 'pass' ? 'YPerf-owned page cache purge completed; semantic listeners were also notified.' : 'YPerf Page Cache reported a file deletion failure during the global purge.';
                    do_action('yablonsky_performance_purge_all', $context);
                } elseif (function_exists('w3tc_flush_all')) {
                    \w3tc_flush_all();
                    $provider = 'W3 Total Cache';
                    $status = 'pass';
                    $confirmed = true;
                    $details = 'All caches exposed by the active provider were purged.';
                } else {
                    do_action('yablonsky_performance_purge_all', $context);
                    $details = 'Global purge intent was emitted; no provider-specific executor confirmed completion.';
                }
            }
        } catch (\Throwable $throwable) {
            $status = 'fail';
            $confirmed = false;
            $details = 'Purge failed: ' . sanitize_text_field($throwable->getMessage());
        }

        $edgeEligible = !in_array($status, array('fail', 'blocked'), true);
        if ($edgeEligible && strpos((string) $context, 'bridge:') !== 0 && CloudflareEdge::adoption() !== array()) {
            if (($mode === 'smart' || $mode === 'url') && $url !== '') {
                $edge = CloudflareEdge::purgeUrls(array($url));
                $edgeStatus = sanitize_key((string) ($edge['state'] ?? 'unknown'));
                $edgeDetails = sanitize_text_field((string) ($edge['message'] ?? ''));
            } elseif ($mode === 'page' || $mode === 'all') {
                $edge = CloudflareEdge::purgeHost();
                $edgeStatus = sanitize_key((string) ($edge['state'] ?? 'unknown'));
                $edgeDetails = sanitize_text_field((string) ($edge['message'] ?? ''));
            }
        }

        return self::saveResult(array(
            'mode'     => $mode,
            'status'   => $status,
            'provider' => $provider,
            'count'    => $count,
            'url'      => $url,
            'context'  => sanitize_key($context),
            'details'  => $details,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'confirmed' => $confirmed,
            'affected' => $affected,
            'edge_status' => $edgeStatus,
            'edge_details' => $edgeDetails,
        ));
    }

    /** @return array<string,mixed> */
    public static function lastResult(): array
    {
        $value = get_option(self::LAST_OPTION, array());
        return is_array($value) ? $value : array();
    }

    /** @param array<string,mixed> $result @return array<string,mixed> */
    private static function saveResult(array $result): array
    {
        $clean = array(
            'time'     => time(),
            'mode'     => sanitize_key((string) ($result['mode'] ?? 'unknown')),
            'status'   => sanitize_key((string) ($result['status'] ?? 'unknown')),
            'provider' => sanitize_text_field((string) ($result['provider'] ?? '')),
            'count'    => max(0, (int) ($result['count'] ?? 0)),
            'url'      => esc_url_raw((string) ($result['url'] ?? '')),
            'context'  => sanitize_key((string) ($result['context'] ?? 'manual')),
            'details'  => sanitize_text_field((string) ($result['details'] ?? '')),
            'duration_ms' => max(0, (int) ($result['duration_ms'] ?? 0)),
            'confirmed' => !empty($result['confirmed']),
            'affected' => max(0, (int) ($result['affected'] ?? 0)),
            'edge_status' => sanitize_key((string) ($result['edge_status'] ?? 'not_applicable')),
            'edge_details' => sanitize_text_field((string) ($result['edge_details'] ?? '')),
        );
        update_option(self::LAST_OPTION, $clean, false);
        Telemetry::recordPurge($clean);
        return $clean;
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
}
