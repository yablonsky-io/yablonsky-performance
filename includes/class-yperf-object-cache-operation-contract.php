<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Deterministic R2 contract consumed by the explicit R3 YPerf Redis executor.
 * Building the contract is read-only. A write is possible only through the
 * separate operator-approved executor, which rechecks this exact fingerprint.
 */
final class ObjectCacheOperationContract
{
    /** @param array<string,mixed> $adoption @param array<string,mixed> $host @param array<string,mixed> $dropins @param array<string,mixed> $runtime @return array<string,mixed> */
    public static function build(array $adoption, array $host, array $dropins, array $runtime): array
    {
        $record = is_array($adoption['record'] ?? null) ? $adoption['record'] : array();
        $redis = is_array($host['redis'] ?? null) ? $host['redis'] : array();
        $objectDropin = is_array($dropins['object_cache'] ?? null) ? $dropins['object_cache'] : array();
        $runtimeDropin = is_array($dropins['runtime'] ?? null) ? $dropins['runtime'] : array();
        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();

        $db = (int) ($record['database'] ?? -1);
        $hostSchema = (int) ($host['schema'] ?? 0);
        $hostGenerated = max(0, (int) ($host['generated_at'] ?? 0));
        $hostAge = $hostGenerated > 0 ? max(0, time() - $hostGenerated) : 0;
        $hostFresh = $hostGenerated > 0 && $hostAge <= 86400;
        $runtimeClean = $runtime !== array()
            && (string) ($runtime['version'] ?? '') === YPERF_VERSION
            && (int) ($summary['fail'] ?? 0) === 0;

        $reserved = !empty($adoption['reserved']) && $record !== array() && $db >= 0;
        $firstEmpty = (int) ($redis['first_empty_database'] ?? -1);
        $databaseSizes = is_array($redis['database_sizes'] ?? null) ? $redis['database_sizes'] : array();
        $dbStillObservedEmpty = $db >= 0 && (array_key_exists((string) $db, $databaseSizes)
            ? (int) $databaseSizes[(string) $db] === 0
            : $firstEmpty === $db);
        $dropinAbsent = empty($objectDropin['present']);
        $externalInactive = empty($runtimeDropin['external_object_cache']);
        $redisReachable = !empty($redis['reachable']);
        $redisBounded = (float) ($redis['maxmemory_mb'] ?? 0) > 0;

        $prefix = self::prefix();
        $fingerprint = self::fingerprint($db, $prefix, $host, $record);

        $gates = array(
            'reservation_current' => $reserved,
            'host_schema_current' => $hostSchema >= 5,
            'host_proof_fresh' => $hostFresh,
            'redis_reachable' => $redisReachable,
            'redis_bounded' => $redisBounded,
            'reserved_database_still_observed_empty' => $dbStillObservedEmpty,
            'object_cache_dropin_absent' => $dropinAbsent,
            'external_object_cache_inactive' => $externalInactive,
            'runtime_current_clean' => $runtimeClean,
        );

        $passed = 0;
        foreach ($gates as $ok) {
            if ($ok) {
                $passed++;
            }
        }
        $progress = (int) round(($passed / max(1, count($gates))) * 100);

        $state = 'reservation_required';
        $label = __('RESERVE REDIS TARGET FIRST', 'yablonsky-performance');
        $reason = __('Object Cache R1 must reserve an isolated Redis target before YPerf can prepare a deterministic activation and rollback contract.', 'yablonsky-performance');
        if ($reserved && !$hostFresh) {
            $state = 'redis_evidence_refresh_required';
            $label = __('REFRESH REDIS EVIDENCE', 'yablonsky-performance');
            $reason = __('Refresh the read-only Redis evidence before treating the reserved database as still empty and safe for an activation plan.', 'yablonsky-performance');
        } elseif ($reserved && !$dbStillObservedEmpty) {
            $state = 'reservation_drift';
            $label = __('REDIS RESERVATION DRIFT · BLOCKED', 'yablonsky-performance');
            $reason = __('The reserved logical database is no longer the empty database proven by current host evidence. Release the reservation and re-evaluate instead of reusing stale isolation evidence.', 'yablonsky-performance');
        } elseif (!$dropinAbsent || !$externalInactive) {
            $state = 'dropin_conflict';
            $label = __('OBJECT CACHE OWNERSHIP CONFLICT', 'yablonsky-performance');
            $reason = __('WordPress already exposes an object-cache drop-in or external object-cache runtime. YPerf will not prepare a competing activation contract.', 'yablonsky-performance');
        } elseif (!$runtimeClean) {
            $state = 'runtime_gate_required';
            $label = __('RUNTIME GATE REQUIRED', 'yablonsky-performance');
            $reason = __('A clean current-version Runtime Acceptance is required before the R3 object-cache activation can be approved.', 'yablonsky-performance');
        } elseif ($passed === count($gates)) {
            $state = 'operation_contract_ready';
            $label = __('OBJECT CACHE CONTRACT READY', 'yablonsky-performance');
            $reason = __('The Redis target remains isolated, WordPress has no competing object-cache runtime and current acceptance is clean. The exact contract can now be passed to the explicit R3 activation, verification and rollback workflow.', 'yablonsky-performance');
        }

        return array(
            'schema' => 1,
            'generated_at' => time(),
            'state' => $state,
            'label' => $label,
            'reason' => $reason,
            'progress_percent' => $progress,
            'gates' => $gates,
            'evidence_source' => sanitize_key((string) ($host['evidence_source'] ?? (($host['helper'] ?? '') === 'yablonsky-performance-local-redis-inspector' ? 'local_php_redis' : 'host_snapshot'))),
            'contract' => array(
                'fingerprint' => $fingerprint,
                'redis_host' => '127.0.0.1',
                'redis_port' => 6379,
                'database' => $db,
                'key_prefix' => $prefix,
                'dropin_target' => 'wp-content/object-cache.php',
                'owner' => 'yablonsky-performance',
            ),
            'planned_apply' => array(
                'backup_existing_dropin' => false,
                'write_yperf_dropin' => true,
                'verify_wp_using_ext_object_cache' => true,
                'verify_redis_roundtrip' => true,
                'run_runtime_acceptance' => true,
                'rollback_on_failure' => true,
            ),
            'rollback' => array(
                'remove_yperf_dropin_only' => true,
                'restore_previous_dropin_if_any' => false,
                'flush_reserved_database' => false,
                'redis_configuration_write' => false,
                'service_restart' => false,
            ),
            'safety' => array(
                'contract_build_is_read_only' => true,
                'r3_requires_explicit_operator_approval' => true,
                'r3_refuses_existing_dropin' => true,
                'r3_never_flushes_reserved_database' => true,
                'host_configuration_changed_by_contract' => false,
            ),
        );
    }

    private static function prefix(): string
    {
        $host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        $path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
        $seed = trim($host . $path, '/');
        return 'yperf:' . substr(hash('sha256', $seed), 0, 12) . ':';
    }

    /** @param array<string,mixed> $host @param array<string,mixed> $record */
    private static function fingerprint(int $db, string $prefix, array $host, array $record): string
    {
        $redis = is_array($host['redis'] ?? null) ? $host['redis'] : array();
        $payload = array(
            'target_host' => sanitize_text_field((string) ($host['site_runtime']['target_host'] ?? '')),
            'redis_version' => sanitize_text_field((string) ($redis['version'] ?? '')),
            'maxmemory_mb' => (float) ($redis['maxmemory_mb'] ?? 0),
            'policy' => sanitize_key((string) ($redis['maxmemory_policy'] ?? '')),
            'database' => $db,
            'prefix' => $prefix,
            'reservation_version' => sanitize_text_field((string) ($record['yperf_version'] ?? '')),
            'yperf_version' => YPERF_VERSION,
        );
        return hash('sha256', (string) wp_json_encode($payload));
    }
}
