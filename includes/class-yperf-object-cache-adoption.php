<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Read-only Redis object-cache adoption readiness.
 *
 * R1 proves whether an existing local Redis service has enough isolation and
 * headroom to become a WordPress object-cache executor. R2 freezes an exact
 * contract and R3 performs the separate operator-approved write/proof/rollback.
 */
final class ObjectCacheAdoption
{
    private const OPTION = 'yperf_object_cache_adoption_v1';
    private const MAX_PROOF_AGE = 86400;

    /** @param array<string,mixed> $host @param array<string,mixed> $dropins @param array<string,mixed> $runtime @param array<string,mixed> $benchmark @return array<string,mixed> */
    public static function analyze(array $host, array $dropins, array $runtime, array $benchmark): array
    {
        $redis = is_array($host['redis'] ?? null) ? $host['redis'] : array();
        $objectDropin = is_array($dropins['object_cache'] ?? null) ? $dropins['object_cache'] : array();
        $runtimeDropin = is_array($dropins['runtime'] ?? null) ? $dropins['runtime'] : array();
        $hostSchema = (int) ($host['schema'] ?? 0);
        $generatedAt = max(0, (int) ($host['generated_at'] ?? 0));
        $age = $generatedAt > 0 ? max(0, time() - $generatedAt) : 0;
        $fresh = $generatedAt > 0 && $age <= self::MAX_PROOF_AGE;

        $used = (float) ($redis['used_memory_mb'] ?? 0);
        $max = (float) ($redis['maxmemory_mb'] ?? 0);
        $usagePct = $max > 0 ? ($used / $max) * 100 : 0.0;
        $uptime = max(1.0, (float) ($redis['uptime_seconds'] ?? 0));
        $evicted = max(0.0, (float) ($redis['evicted_keys'] ?? 0));
        $evictionsPerSecond = $evicted / $uptime;
        $policy = sanitize_key((string) ($redis['maxmemory_policy'] ?? 'unknown'));
        $safePolicy = in_array($policy, array('allkeys-lru', 'allkeys-lfu'), true);
        $firstEmptyDb = (int) ($redis['first_empty_database'] ?? -1);
        $totalKeys = max(0, (int) ($redis['keyspace_total_keys'] ?? 0));
        $nonemptyDbs = max(0, (int) ($redis['keyspace_nonempty_databases'] ?? 0));
        $logicalDbs = max(0, (int) ($redis['logical_databases'] ?? 0));
        $fragmentation = max(0.0, (float) ($redis['mem_fragmentation_ratio'] ?? 0));
        $rejected = max(0, (int) ($redis['rejected_connections'] ?? 0));
        $databaseSizes = is_array($redis['database_sizes'] ?? null) ? $redis['database_sizes'] : array();

        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        $runtimeCurrent = $runtime !== array() && (string) ($runtime['version'] ?? '') === YPERF_VERSION;
        $runtimeClean = $runtimeCurrent && (int) ($summary['fail'] ?? 0) === 0;
        $dropinPresent = !empty($objectDropin['present']);
        $externalActive = !empty($runtimeDropin['external_object_cache']);

        $redisBenchmark = self::benchmarkResult($benchmark, 'redis');
        $benchmarkCurrent = $benchmark !== array() && (string) ($benchmark['version'] ?? '') === YPERF_VERSION;
        $benchmarkPass = $benchmarkCurrent && (string) ($redisBenchmark['status'] ?? '') === 'pass';

        $gates = array(
            'host_schema_current' => $hostSchema >= 5,
            'host_proof_fresh' => $fresh,
            'redis_reachable' => !empty($redis['reachable']),
            'memory_bounded' => $max > 0,
            'eviction_policy_safe' => $safePolicy,
            'memory_headroom' => $max > 0 && $usagePct < 80.0,
            'eviction_pressure_low' => $evictionsPerSecond < 0.20,
            'isolated_database_available' => $firstEmptyDb >= 0,
            'object_cache_dropin_absent' => !$dropinPresent,
            'external_object_cache_inactive' => !$externalActive,
            'runtime_clean' => $runtimeClean,
            'redis_benchmark_current' => $benchmarkPass,
        );
        $passed = 0;
        foreach ($gates as $ok) {
            if ($ok) {
                $passed++;
            }
        }
        $progress = (int) round(($passed / max(1, count($gates))) * 100);

        $state = 'redis_evidence_required';
        $label = __('REDIS EVIDENCE REQUIRED', 'yablonsky-performance');
        $reason = __('YPerf needs current read-only Redis evidence. It can use a current Host Inspector snapshot or inspect a local PhpRedis service directly without writing keys or changing Redis configuration.', 'yablonsky-performance');

        if ($externalActive) {
            $state = 'already_active';
            $label = __('OBJECT CACHE ACTIVE', 'yablonsky-performance');
            $reason = __('WordPress already reports an external object cache. YPerf will not install or reserve a competing Redis executor.', 'yablonsky-performance');
        } elseif ($dropinPresent) {
            $state = 'dropin_owned_elsewhere';
            $label = __('DROP-IN OWNERSHIP REVIEW', 'yablonsky-performance');
            $reason = __('object-cache.php already exists. Resolve ownership before any Redis adoption path is prepared.', 'yablonsky-performance');
        } elseif ($hostSchema >= 5 && empty($redis['reachable'])) {
            $state = 'redis_unreachable';
            $label = __('REDIS UNREACHABLE', 'yablonsky-performance');
            $reason = __('Current Redis evidence does not confirm a reachable local Redis service.', 'yablonsky-performance');
        } elseif ($hostSchema >= 5 && $max <= 0) {
            $state = 'redis_unbounded';
            $label = __('REDIS UNBOUNDED · BLOCKED', 'yablonsky-performance');
            $reason = __('Redis maxmemory is not bounded. YPerf will not prepare WordPress object-cache adoption until a reviewed memory boundary exists.', 'yablonsky-performance');
        } elseif ($hostSchema >= 5 && !$safePolicy) {
            $state = 'eviction_policy_review';
            $label = __('EVICTION POLICY REVIEW', 'yablonsky-performance');
            $reason = __('The current Redis eviction policy is not an allkeys LRU/LFU policy. Adoption remains blocked pending explicit review.', 'yablonsky-performance');
        } elseif ($hostSchema >= 5 && ($usagePct >= 80.0 || $evictionsPerSecond >= 0.20)) {
            $state = 'redis_pressure_blocked';
            $label = __('REDIS PRESSURE · BLOCKED', 'yablonsky-performance');
            $reason = __('Current Redis memory or eviction evidence shows insufficient headroom for adding WordPress as a new persistent consumer.', 'yablonsky-performance');
        } elseif ($hostSchema >= 5 && $firstEmptyDb < 0) {
            $state = 'isolation_required';
            $label = __('ISOLATION REQUIRED', 'yablonsky-performance');
            $reason = __('No empty logical Redis database was proven. A dedicated database or reviewed key-prefix isolation is required before adoption.', 'yablonsky-performance');
        } elseif (!$runtimeClean) {
            $state = 'runtime_gate_required';
            $label = __('RUNTIME GATE REQUIRED', 'yablonsky-performance');
            $reason = __('Current-version Runtime Acceptance must be clean before preparing a persistent object-cache adoption.', 'yablonsky-performance');
        } elseif (!$benchmarkPass) {
            $state = 'benchmark_required';
            $label = __('REDIS BENCHMARK REQUIRED', 'yablonsky-performance');
            $reason = __('Run the current-version Redis benchmark before reserving a local object-cache target.', 'yablonsky-performance');
        } elseif ($hostSchema >= 5 && $fresh) {
            $state = 'reservation_ready';
            $label = __('REDIS ISOLATION READY', 'yablonsky-performance');
            $reason = __('Redis is reachable, bounded, below the adoption pressure threshold and an empty logical database is available. R1 can reserve the target locally; R2/R3 then require a fresh contract and explicit activation approval.', 'yablonsky-performance');
        }

        $record = self::last();
        $recordDb = (int) ($record['database'] ?? -1);
        $recordDbStillEmpty = $recordDb >= 0
            && (array_key_exists((string) $recordDb, $databaseSizes)
                ? (int) $databaseSizes[(string) $recordDb] === 0
                : $recordDb === $firstEmptyDb);
        $recordCurrent = $record !== array()
            && $recordDbStillEmpty
            && (string) ($record['host_fingerprint'] ?? '') === self::hostFingerprint($host)
            && $fresh;
        if ($recordCurrent && !$externalActive && !$dropinPresent) {
            $state = 'reserved_read_only';
            $label = __('REDIS TARGET RESERVED · READ-ONLY', 'yablonsky-performance');
            $reason = __('YPerf has a current local reservation for the isolated Redis target. No Redis key, drop-in or server configuration has been created.', 'yablonsky-performance');
        }

        return array(
            'schema' => 1,
            'generated_at' => time(),
            'state' => $state,
            'label' => $label,
            'reason' => $reason,
            'progress_percent' => $progress,
            'eligible_to_reserve' => $state === 'reservation_ready',
            'reserved' => $state === 'reserved_read_only',
            'gates' => $gates,
            'host_schema' => $hostSchema,
            'host_proof_age_seconds' => $age,
            'evidence_source' => sanitize_key((string) ($host['evidence_source'] ?? (($host['helper'] ?? '') === 'yablonsky-performance-local-redis-inspector' ? 'local_php_redis' : 'host_snapshot'))),
            'redis' => array(
                'used_memory_mb' => round($used, 2),
                'maxmemory_mb' => round($max, 2),
                'memory_usage_pct' => round($usagePct, 1),
                'maxmemory_policy' => $policy,
                'evictions_per_second_since_start' => round($evictionsPerSecond, 4),
                'logical_databases' => $logicalDbs,
                'nonempty_databases' => $nonemptyDbs,
                'total_keys' => $totalKeys,
                'first_empty_database' => $firstEmptyDb,
                'database_sizes' => $databaseSizes,
                'mem_fragmentation_ratio' => round($fragmentation, 2),
                'rejected_connections' => $rejected,
            ),
            'dropin' => array(
                'present' => $dropinPresent,
                'owner' => sanitize_key((string) ($objectDropin['owner'] ?? 'none')),
                'external_runtime_active' => $externalActive,
            ),
            'benchmark' => array(
                'current' => $benchmarkCurrent,
                'status' => sanitize_key((string) ($redisBenchmark['status'] ?? 'missing')),
                'median_ms' => isset($redisBenchmark['median_ms']) ? (float) $redisBenchmark['median_ms'] : null,
            ),
            'record' => self::publicRecord($record),
            'next_operation' => array(
                'install_dropin' => $recordCurrent ? 'ready_for_r2_contract_then_r3_explicit_activation' : 'blocked',
                'redis_configuration_write' => 'locked',
                'redis_flushdb' => 'forbidden',
                'service_restart' => 'locked',
            ),
            'safety' => array(
                'redis_changed' => false,
                'keys_created' => false,
                'keys_deleted' => false,
                'dropin_written' => false,
                'server_restart' => false,
            ),
        );
    }

    /** @param array<string,mixed> $host @param array<string,mixed> $dropins @param array<string,mixed> $runtime @param array<string,mixed> $benchmark @return array<string,mixed> */
    public static function reserve(array $host, array $dropins, array $runtime, array $benchmark): array
    {
        $analysis = self::analyze($host, $dropins, $runtime, $benchmark);
        if (empty($analysis['eligible_to_reserve'])) {
            $dropin = is_array($analysis['dropin'] ?? null) ? $analysis['dropin'] : array();
            return array(
                'ok' => false,
                'state' => sanitize_key((string) ($analysis['state'] ?? 'blocked')),
                'message' => sanitize_text_field((string) ($analysis['reason'] ?? __('Redis target reservation is blocked.', 'yablonsky-performance'))),
                'dropin_owner' => sanitize_key((string) ($dropin['owner'] ?? 'none')),
            );
        }
        $redis = is_array($analysis['redis'] ?? null) ? $analysis['redis'] : array();
        $db = (int) ($redis['first_empty_database'] ?? -1);
        if ($db < 0) {
            return array('ok' => false, 'message' => __('No empty Redis logical database is available.', 'yablonsky-performance'));
        }
        $record = array(
            'schema' => 1,
            'reserved_at' => time(),
            'database' => $db,
            'host_fingerprint' => self::hostFingerprint($host),
            'yperf_version' => YPERF_VERSION,
            'redis_changed' => false,
            'dropin_written' => false,
        );
        update_option(self::OPTION, $record, false);
        return array('ok' => true, 'message' => __('Redis logical database reserved locally. No Redis or filesystem change was made; R3 activation still requires a current contract and explicit approval.', 'yablonsky-performance'), 'record' => self::publicRecord($record));
    }

    public static function release(): void
    {
        delete_option(self::OPTION);
    }

    /** @return array<string,mixed> */
    public static function last(): array
    {
        $value = get_option(self::OPTION, array());
        return is_array($value) ? $value : array();
    }

    /** @param array<string,mixed> $benchmark @return array<string,mixed> */
    private static function benchmarkResult(array $benchmark, string $id): array
    {
        foreach ((array) ($benchmark['results'] ?? array()) as $row) {
            if (is_array($row) && (string) ($row['id'] ?? '') === $id) {
                return $row;
            }
        }
        return array();
    }

    /** @param array<string,mixed> $host */
    private static function hostFingerprint(array $host): string
    {
        $redis = is_array($host['redis'] ?? null) ? $host['redis'] : array();
        $payload = array(
            'helper' => sanitize_key((string) ($host['helper'] ?? '')),
            'schema' => (int) ($host['schema'] ?? 0),
            'version' => sanitize_text_field((string) ($redis['version'] ?? '')),
            'maxmemory_mb' => (float) ($redis['maxmemory_mb'] ?? 0),
            'policy' => sanitize_key((string) ($redis['maxmemory_policy'] ?? '')),
            'logical_databases' => (int) ($redis['logical_databases'] ?? 0),
            'evidence_source' => sanitize_key((string) ($host['evidence_source'] ?? '')),
            'target_host' => sanitize_text_field((string) (($host['site_runtime']['target_host'] ?? ''))),
        );
        return hash('sha256', (string) wp_json_encode($payload));
    }

    /** @param array<string,mixed> $record @return array<string,mixed> */
    private static function publicRecord(array $record): array
    {
        if ($record === array()) {
            return array();
        }
        return array(
            'schema' => (int) ($record['schema'] ?? 1),
            'reserved_at' => (int) ($record['reserved_at'] ?? 0),
            'database' => (int) ($record['database'] ?? -1),
            'yperf_version' => sanitize_text_field((string) ($record['yperf_version'] ?? '')),
            'redis_changed' => !empty($record['redis_changed']),
            'dropin_written' => !empty($record['dropin_written']),
        );
    }
}
