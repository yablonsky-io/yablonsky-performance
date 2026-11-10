<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Read-only Redis evidence resolver for Object Cache R1/R2.
 *
 * Prefer a current Host Inspector snapshot when one exists. Otherwise collect
 * the small Redis-only evidence set that WordPress can prove safely through a
 * local PhpRedis connection. The local probe never scans keys, writes keys,
 * changes CONFIG, flushes a database or restarts a service.
 */
final class RedisEvidence
{
    private const MAX_HOST_AGE = 86400;
    private const HOST = '127.0.0.1';
    private const PORT = 6379;
    private const TIMEOUT = 0.25;

    /** @var array<string,mixed>|null */
    private static $localMemo = null;

    /** @param array<string,mixed> $host @return array<string,mixed> */
    public static function resolve(array $host): array
    {
        if (self::hostSnapshotUsable($host)) {
            $host['evidence_source'] = 'host_snapshot';
            return $host;
        }

        $local = self::inspectLocal();
        $redis = is_array($local['redis'] ?? null) ? $local['redis'] : array();
        if (!empty($redis['reachable'])) {
            return $local;
        }

        // Preserve stale/imported evidence for diagnosis when the local probe
        // cannot prove anything. Adoption gates will still fail closed on age.
        if ($host !== array()) {
            $host['evidence_source'] = 'host_snapshot_stale_or_partial';
            return $host;
        }

        return $local;
    }

    /** @return array<string,mixed> */
    public static function inspectLocal(): array
    {
        if (self::$localMemo !== null) {
            return self::$localMemo;
        }

        $base = array(
            'schema' => 7,
            'helper' => 'yablonsky-performance-local-redis-inspector',
            'helper_version' => YPERF_VERSION,
            'generated_at' => time(),
            'read_only' => true,
            'evidence_source' => 'local_php_redis',
            'site_runtime' => array(
                'target_host' => sanitize_text_field((string) wp_parse_url(home_url('/'), PHP_URL_HOST)),
            ),
            'redis' => array(
                'installed' => class_exists('Redis'),
                'reachable' => false,
                'version' => '',
                'role' => '',
                'uptime_seconds' => 0,
                'connected_clients' => 0,
                'used_memory_mb' => 0.0,
                'maxmemory_mb' => 0.0,
                'maxmemory_policy' => 'unknown',
                'evicted_keys' => 0,
                'keyspace_hits' => 0,
                'keyspace_misses' => 0,
                'appendonly' => null,
                'used_memory_rss_mb' => 0.0,
                'mem_fragmentation_ratio' => 0.0,
                'instantaneous_ops_per_sec' => 0,
                'rejected_connections' => 0,
                'logical_databases' => 0,
                'keyspace_nonempty_databases' => 0,
                'keyspace_total_keys' => 0,
                'first_empty_database' => -1,
                'database_sizes' => array(),
                'local_probe' => true,
                'config_readable' => false,
                'dbsize_readable' => false,
            ),
        );

        if (!class_exists('Redis')) {
            self::$localMemo = $base;
            return $base;
        }

        $redisClient = null;
        try {
            $redisClient = new \Redis();
            if (!$redisClient->connect(self::HOST, self::PORT, self::TIMEOUT)) {
                self::$localMemo = $base;
                return $base;
            }

            $info = $redisClient->info();
            if (!is_array($info)) {
                $info = array();
            }

            $maxmemory = self::numericValue($info, 'maxmemory');
            $policy = self::stringValue($info, 'maxmemory_policy');
            $appendonly = self::stringValue($info, 'aof_enabled');
            $databases = 0;
            $configReadable = false;

            $configMaxmemory = self::configGet($redisClient, 'maxmemory');
            if ($configMaxmemory !== null) {
                $configReadable = true;
                $maxmemory = max(0.0, (float) $configMaxmemory);
            }
            $configPolicy = self::configGet($redisClient, 'maxmemory-policy');
            if ($configPolicy !== null) {
                $configReadable = true;
                $policy = $configPolicy;
            }
            $configDatabases = self::configGet($redisClient, 'databases');
            if ($configDatabases !== null && is_numeric($configDatabases)) {
                $configReadable = true;
                $databases = max(0, min(256, (int) $configDatabases));
            }
            $configAppendonly = self::configGet($redisClient, 'appendonly');
            if ($configAppendonly !== null) {
                $configReadable = true;
                $appendonly = $configAppendonly;
            }

            $totalKeys = 0;
            $nonempty = 0;
            $firstEmpty = -1;
            $dbsizeReadable = $databases > 0;
            $databaseSizes = array();
            if ($databases > 0) {
                for ($db = 0; $db < $databases; $db++) {
                    if (!$redisClient->select($db)) {
                        $dbsizeReadable = false;
                        $databaseSizes = array();
                        $firstEmpty = -1;
                        break;
                    }
                    $size = $redisClient->dbSize();
                    if (!is_int($size)) {
                        $dbsizeReadable = false;
                        $databaseSizes = array();
                        $firstEmpty = -1;
                        break;
                    }
                    $size = max(0, $size);
                    $databaseSizes[(string) $db] = $size;
                    $totalKeys += $size;
                    if ($size > 0) {
                        ++$nonempty;
                    } elseif ($firstEmpty < 0) {
                        $firstEmpty = $db;
                    }
                }
            }

            if (!$dbsizeReadable) {
                $totalKeys = self::keyspaceTotalFromInfo($info);
                $nonempty = self::keyspaceNonemptyFromInfo($info);
            }

            $base['redis'] = array(
                'installed' => true,
                'reachable' => true,
                'version' => sanitize_text_field(self::stringValue($info, 'redis_version')),
                'role' => sanitize_key(self::stringValue($info, 'role')),
                'uptime_seconds' => max(0, (int) self::numericValue($info, 'uptime_in_seconds')),
                'connected_clients' => max(0, (int) self::numericValue($info, 'connected_clients')),
                'used_memory_mb' => round(self::numericValue($info, 'used_memory') / 1048576, 3),
                'maxmemory_mb' => round(max(0.0, $maxmemory) / 1048576, 3),
                'maxmemory_policy' => sanitize_key($policy !== '' ? $policy : 'unknown'),
                'evicted_keys' => max(0, (int) self::numericValue($info, 'evicted_keys')),
                'keyspace_hits' => max(0, (int) self::numericValue($info, 'keyspace_hits')),
                'keyspace_misses' => max(0, (int) self::numericValue($info, 'keyspace_misses')),
                'appendonly' => self::toBoolOrNull($appendonly),
                'used_memory_rss_mb' => round(self::numericValue($info, 'used_memory_rss') / 1048576, 3),
                'mem_fragmentation_ratio' => max(0.0, self::numericValue($info, 'mem_fragmentation_ratio')),
                'instantaneous_ops_per_sec' => max(0, (int) self::numericValue($info, 'instantaneous_ops_per_sec')),
                'rejected_connections' => max(0, (int) self::numericValue($info, 'rejected_connections')),
                'logical_databases' => $databases,
                'keyspace_nonempty_databases' => $nonempty,
                'keyspace_total_keys' => $totalKeys,
                'first_empty_database' => $dbsizeReadable ? $firstEmpty : -1,
                'database_sizes' => $dbsizeReadable ? $databaseSizes : array(),
                'local_probe' => true,
                'config_readable' => $configReadable,
                'dbsize_readable' => $dbsizeReadable,
            );
        } catch (\Throwable $throwable) {
            // Fail closed. The object-cache adoption layer will surface the
            // missing evidence instead of treating a probe error as readiness.
        } finally {
            if ($redisClient instanceof \Redis) {
                try {
                    $redisClient->close();
                } catch (\Throwable $throwable) {
                }
            }
        }

        self::$localMemo = $base;
        return $base;
    }

    /** @param array<string,mixed> $host */
    private static function hostSnapshotUsable(array $host): bool
    {
        $schema = (int) ($host['schema'] ?? 0);
        $generatedAt = max(0, (int) ($host['generated_at'] ?? 0));
        $redis = is_array($host['redis'] ?? null) ? $host['redis'] : array();
        return $schema >= 5
            && $generatedAt > 0
            && (time() - $generatedAt) <= self::MAX_HOST_AGE
            && $redis !== array();
    }

    /** @param array<string,mixed> $info */
    private static function numericValue(array $info, string $key): float
    {
        $value = $info[$key] ?? 0;
        return is_numeric($value) ? (float) $value : 0.0;
    }

    /** @param array<string,mixed> $info */
    private static function stringValue(array $info, string $key): string
    {
        $value = $info[$key] ?? '';
        return is_scalar($value) ? trim((string) $value) : '';
    }

    /** @return string|null */
    private static function configGet(\Redis $redis, string $key): ?string
    {
        try {
            $result = $redis->config('GET', $key);
        } catch (\Throwable $throwable) {
            return null;
        }
        if (!is_array($result)) {
            return null;
        }
        if (array_key_exists($key, $result) && is_scalar($result[$key])) {
            return trim((string) $result[$key]);
        }
        $normalized = str_replace('-', '_', $key);
        if (array_key_exists($normalized, $result) && is_scalar($result[$normalized])) {
            return trim((string) $result[$normalized]);
        }
        $values = array_values($result);
        if (count($values) >= 2 && is_scalar($values[1])) {
            return trim((string) $values[1]);
        }
        if (count($values) === 1 && is_scalar($values[0])) {
            return trim((string) $values[0]);
        }
        return null;
    }

    /** @param array<string,mixed> $info */
    private static function keyspaceTotalFromInfo(array $info): int
    {
        $total = 0;
        foreach ($info as $key => $value) {
            if (preg_match('/^db\d+$/', (string) $key) !== 1) {
                continue;
            }
            if (is_array($value)) {
                $total += max(0, (int) ($value['keys'] ?? 0));
                continue;
            }
            if (is_string($value) && preg_match('/(?:^|,)keys=(\d+)/', $value, $match) === 1) {
                $total += (int) $match[1];
            }
        }
        return $total;
    }

    /** @param array<string,mixed> $info */
    private static function keyspaceNonemptyFromInfo(array $info): int
    {
        $count = 0;
        foreach ($info as $key => $value) {
            if (preg_match('/^db\d+$/', (string) $key) !== 1) {
                continue;
            }
            if (is_array($value) && (int) ($value['keys'] ?? 0) > 0) {
                ++$count;
            } elseif (is_string($value) && preg_match('/(?:^|,)keys=(\d+)/', $value, $match) === 1 && (int) $match[1] > 0) {
                ++$count;
            }
        }
        return $count;
    }

    /** @return bool|null */
    private static function toBoolOrNull(string $value)
    {
        $value = strtolower(trim($value));
        if (in_array($value, array('1', 'yes', 'on', 'true'), true)) {
            return true;
        }
        if (in_array($value, array('0', 'no', 'off', 'false'), true)) {
            return false;
        }
        return null;
    }
}
