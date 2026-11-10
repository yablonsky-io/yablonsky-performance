<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class HostSnapshot
{
    private const OPTION = 'yperf_host_snapshot_v1';
    private const MAX_JSON_BYTES = 524288;

    /** @return array<string,mixed> */
    public static function importJson(string $json): array
    {
        $json = trim($json);
        if ($json === '') {
            return array('ok' => false, 'message' => __('Host snapshot JSON is empty.', 'yablonsky-performance'));
        }
        if (strlen($json) > self::MAX_JSON_BYTES) {
            return array('ok' => false, 'message' => __('Host snapshot is larger than the 512 KB safety limit.', 'yablonsky-performance'));
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return array('ok' => false, 'message' => __('Host snapshot is not valid JSON.', 'yablonsky-performance'));
        }
        $validated = self::validate($decoded);
        if (empty($validated['ok'])) {
            return $validated;
        }
        $snapshot = is_array($validated['snapshot'] ?? null) ? $validated['snapshot'] : array();
        update_option(self::OPTION, $snapshot, false);
        return array('ok' => true, 'message' => __('Host Intelligence snapshot imported.', 'yablonsky-performance'), 'snapshot' => $snapshot);
    }

    /** @return array<string,mixed> */
    public static function last(): array
    {
        $value = get_option(self::OPTION, array());
        return is_array($value) ? $value : array();
    }

    public static function clear(): void
    {
        delete_option(self::OPTION);
    }

    /** @param array<string,mixed> $input @return array<string,mixed> */
    private static function validate(array $input): array
    {
        $schema = (int) ($input['schema'] ?? 0);
        if (!in_array($schema, array(1, 2, 3, 4, 5, 6), true)) {
            return array('ok' => false, 'message' => __('Unsupported Host Intelligence schema.', 'yablonsky-performance'));
        }
        $helper = sanitize_key((string) ($input['helper'] ?? ''));
        if ($helper !== 'yablonsky-performance-host-inspector') {
            return array('ok' => false, 'message' => __('Snapshot was not produced by the Yablonsky Performance host inspector.', 'yablonsky-performance'));
        }

        $snapshot = array(
            'schema' => $schema,
            'helper' => $helper,
            'helper_version' => sanitize_text_field((string) ($input['helper_version'] ?? 'unknown')),
            'generated_at' => max(0, (int) ($input['generated_at'] ?? 0)),
            'read_only' => !empty($input['read_only']),
            'host' => self::section($input, 'host', array('os', 'kernel', 'cpu_threads', 'memory_total_mb', 'memory_available_mb', 'load_1', 'load_5', 'load_15', 'disk_total_mb', 'disk_available_mb')),
            'nginx' => self::section($input, 'nginx', array('installed', 'version', 'service_active', 'syntax_ok', 'fastcgi_cache_paths', 'fastcgi_cache_uses', 'proxy_cache_paths', 'gzip_enabled', 'brotli_enabled')),
            'php_fpm' => self::section($input, 'php_fpm', array('installed', 'service_active', 'pool_count', 'dynamic_pools', 'ondemand_pools', 'static_pools', 'max_children_total', 'max_children_min', 'max_children_max', 'max_requests_max', 'site_pool_name', 'site_pool_pm', 'site_pool_max_children', 'site_pool_max_requests', 'site_pool_memory_limit', 'site_pool_current_processes', 'site_pool_avg_rss_mb', 'site_pool_max_rss_mb')),
            'redis' => self::section($input, 'redis', array('installed', 'reachable', 'version', 'role', 'uptime_seconds', 'connected_clients', 'used_memory_mb', 'maxmemory_mb', 'maxmemory_policy', 'evicted_keys', 'keyspace_hits', 'keyspace_misses', 'appendonly', 'used_memory_rss_mb', 'mem_fragmentation_ratio', 'instantaneous_ops_per_sec', 'rejected_connections', 'logical_databases', 'keyspace_nonempty_databases', 'keyspace_total_keys', 'first_empty_database')),
            'memcached' => self::section($input, 'memcached', array('installed', 'service_active', 'reachable', 'version')),
            'database' => self::section($input, 'database', array('mariadb_service_active', 'mysql_service_active')),
        );
        if ($schema >= 2) {
            $snapshot['site_runtime'] = self::section($input, 'site_runtime', array(
                'target_host', 'nginx_matching_server_blocks', 'fastcgi_cache_directives', 'fastcgi_cache_bypass_directives', 'fastcgi_no_cache_directives',
                'fastcgi_cache_purge_directives', 'fastcgi_cache_zone', 'cache_status_header_name', 'cache_status_header_exposed',
                'cache_zone_owner_blocks', 'cache_zone_owner_signature', 'cache_zone_target_owner', 'cache_zone_definition_count', 'cache_zone_path_hash',
                'cache_zone_path_basename', 'fastcgi_cache_key_expression', 'fastcgi_cache_key_host_scoped',
                'fastcgi_pass_kind', 'fastcgi_pass_target', 'matched_fpm_pool', 'probe_performed', 'probe_https_reachable',
                'probe1_http_code', 'probe1_ms', 'probe1_cache_header', 'probe1_cache_value', 'probe1_set_cookie', 'probe1_cache_control',
                'probe2_http_code', 'probe2_ms', 'probe2_cache_header', 'probe2_cache_value', 'probe2_set_cookie', 'probe2_cache_control'
            ));
        }

        if (!$snapshot['read_only']) {
            return array('ok' => false, 'message' => __('Host snapshot must declare read_only=true.', 'yablonsky-performance'));
        }
        return array('ok' => true, 'snapshot' => $snapshot);
    }

    /** @param array<string,mixed> $input @param array<int,string> $allowed @return array<string,mixed> */
    private static function section(array $input, string $name, array $allowed): array
    {
        $source = is_array($input[$name] ?? null) ? $input[$name] : array();
        $out = array();
        foreach ($allowed as $key) {
            if (!array_key_exists($key, $source)) {
                continue;
            }
            $value = $source[$key];
            if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
                $out[$key] = $value;
            } else {
                $out[$key] = sanitize_text_field((string) $value);
            }
        }
        return $out;
    }
}
