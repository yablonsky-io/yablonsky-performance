<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class NativeRuntimeProof
{
    /** @param array<string,mixed> $host @param array<string,mixed> $dropins @return array<string,mixed> */
    public static function build(array $host, array $dropins): array
    {
        $site = is_array($host['site_runtime'] ?? null) ? $host['site_runtime'] : array();
        $redis = is_array($host['redis'] ?? null) ? $host['redis'] : array();
        $fpm = is_array($host['php_fpm'] ?? null) ? $host['php_fpm'] : array();
        $hostInfo = is_array($host['host'] ?? null) ? $host['host'] : array();

        $pageProof = self::pageCacheProof($site, $host);
        $redisProof = self::redisProof($redis);
        $originProof = self::originProof($fpm, $hostInfo);

        return array(
            'schema' => 1,
            'generated_at' => time(),
            'page_cache' => $pageProof,
            'object_cache' => array(
                'dropin' => is_array($dropins['object_cache'] ?? null) ? $dropins['object_cache'] : array(),
                'runtime_external' => !empty($dropins['runtime']['external_object_cache']),
                'redis' => $redisProof,
            ),
            'origin' => $originProof,
            'safety' => array(
                'configuration_changed' => false,
                'bounded_request_probe_only' => !empty($site['probe_performed']),
            ),
        );
    }

    /** @param array<string,mixed> $site @param array<string,mixed> $host @return array<string,mixed> */
    private static function pageCacheProof(array $site, array $host): array
    {
        if ($site === array()) {
            $nginx = is_array($host['nginx'] ?? null) ? $host['nginx'] : array();
            return array(
                'state' => !empty($nginx['fastcgi_cache_uses']) ? 'global_native_config_visible_site_unproven' : 'not_proven',
                'target_host' => '',
                'reason' => 'site_specific_host_probe_not_available',
            );
        }

        $first = strtoupper((string) ($site['probe1_cache_value'] ?? ''));
        $second = strtoupper((string) ($site['probe2_cache_value'] ?? ''));
        $header = (string) ($site['probe2_cache_header'] ?? $site['probe1_cache_header'] ?? '');
        $state = 'execution_unproven';
        if ($first === 'MISS' && $second === 'HIT') {
            $state = 'miss_then_hit_confirmed';
        } elseif ($second === 'HIT') {
            $state = 'hit_confirmed';
        } elseif ($second === 'BYPASS' || $first === 'BYPASS') {
            $state = 'bypass_confirmed';
        } elseif (!empty($site['fastcgi_cache_directives']) && empty($header)) {
            $state = 'native_configured_status_header_not_exposed';
        } elseif (!empty($site['fastcgi_cache_directives'])) {
            $state = 'native_configured_execution_unproven';
        }

        return array(
            'state' => $state,
            'target_host' => sanitize_text_field((string) ($site['target_host'] ?? '')),
            'matching_server_blocks' => (int) ($site['nginx_matching_server_blocks'] ?? 0),
            'fastcgi_cache_directives' => (int) ($site['fastcgi_cache_directives'] ?? 0),
            'fastcgi_cache_purge_directives' => (int) ($site['fastcgi_cache_purge_directives'] ?? 0),
            'cache_zone' => sanitize_text_field((string) ($site['fastcgi_cache_zone'] ?? '')),
            'cache_status_header_name' => sanitize_text_field((string) ($site['cache_status_header_name'] ?? '')),
            'cache_status_header_exposed' => !empty($site['cache_status_header_exposed']),
            'cache_header' => sanitize_text_field($header),
            'probe1_http_code' => (int) ($site['probe1_http_code'] ?? 0),
            'probe1_ms' => (float) ($site['probe1_ms'] ?? 0),
            'probe1_cache_value' => sanitize_text_field((string) ($site['probe1_cache_value'] ?? '')),
            'probe2_http_code' => (int) ($site['probe2_http_code'] ?? 0),
            'probe2_ms' => (float) ($site['probe2_ms'] ?? 0),
            'probe2_cache_value' => sanitize_text_field((string) ($site['probe2_cache_value'] ?? '')),
            'set_cookie_seen' => !empty($site['probe1_set_cookie']) || !empty($site['probe2_set_cookie']),
        );
    }

    /** @param array<string,mixed> $redis @return array<string,mixed> */
    private static function redisProof(array $redis): array
    {
        if ($redis === array()) {
            return array('state' => 'host_snapshot_required');
        }
        if (empty($redis['reachable'])) {
            return array('state' => 'not_reachable');
        }
        $used = (float) ($redis['used_memory_mb'] ?? 0);
        $max = (float) ($redis['maxmemory_mb'] ?? 0);
        $hits = (float) ($redis['keyspace_hits'] ?? 0);
        $misses = (float) ($redis['keyspace_misses'] ?? 0);
        $evicted = (float) ($redis['evicted_keys'] ?? 0);
        $uptime = max(1.0, (float) ($redis['uptime_seconds'] ?? 0));
        $usagePct = $max > 0 ? ($used / $max) * 100 : 0.0;
        $hitPct = ($hits + $misses) > 0 ? ($hits / ($hits + $misses)) * 100 : 0.0;
        $evictionsPerSecond = $evicted / $uptime;
        $state = 'healthy_candidate';
        if ($max <= 0) {
            $state = 'unbounded_attention';
        } elseif ($usagePct >= 85.0 && $evictionsPerSecond >= 1.0) {
            $state = 'high_memory_pressure';
        } elseif ($evictionsPerSecond >= 1.0) {
            $state = 'active_eviction_pressure';
        }
        return array(
            'state' => $state,
            'used_memory_mb' => round($used, 2),
            'maxmemory_mb' => round($max, 2),
            'memory_usage_pct' => round($usagePct, 1),
            'hit_ratio_pct' => round($hitPct, 1),
            'evictions_per_second_since_start' => round($evictionsPerSecond, 2),
            'maxmemory_policy' => sanitize_text_field((string) ($redis['maxmemory_policy'] ?? 'unknown')),
        );
    }

    /** @param array<string,mixed> $fpm @param array<string,mixed> $hostInfo @return array<string,mixed> */
    private static function originProof(array $fpm, array $hostInfo): array
    {
        $cpu = max(1, (int) ($hostInfo['cpu_threads'] ?? 0));
        $load1 = (float) ($hostInfo['load_1'] ?? 0);
        $pool = sanitize_text_field((string) ($fpm['site_pool_name'] ?? ''));
        return array(
            'state' => $pool !== '' ? 'site_pool_mapped' : (!empty($fpm['installed']) ? 'fpm_global_only' : 'not_available'),
            'site_pool_name' => $pool,
            'site_pool_pm' => sanitize_text_field((string) ($fpm['site_pool_pm'] ?? '')),
            'site_pool_max_children' => (int) ($fpm['site_pool_max_children'] ?? 0),
            'site_pool_max_requests' => (int) ($fpm['site_pool_max_requests'] ?? 0),
            'global_max_children_total' => (int) ($fpm['max_children_total'] ?? 0),
            'cpu_threads' => $cpu,
            'load_1_per_cpu' => round($load1 / $cpu, 2),
        );
    }
}
