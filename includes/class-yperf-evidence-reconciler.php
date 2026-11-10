<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class EvidenceReconciler
{
    /** @param array<string,mixed> $caps @param array<string,mixed> $environment @param array<string,mixed> $host @param array<string,mixed> $dropins @param array<string,mixed> $runtime @param array<string,mixed> $benchmark @param array<string,mixed> $nativeProof @return array<string,mixed> */
    public static function build(array $caps, array $environment, array $host, array $dropins, array $runtime, array $benchmark, array $nativeProof): array
    {
        $now = time();
        $sources = array(
            'wordpress_live' => self::source($now, $now, 'live'),
            'environment_check' => self::source((int) ($environment['generated_at'] ?? 0), $now, 'stored'),
            'host_snapshot' => self::source((int) ($host['generated_at'] ?? 0), $now, 'imported'),
            'runtime_acceptance' => self::reportSource($runtime, $now, 'stored'),
            'benchmark' => self::reportSource($benchmark, $now, 'stored'),
            'dropin_ownership' => self::source((int) ($dropins['generated_at'] ?? 0), $now, 'live'),
        );

        $conflicts = array();
        $liveObjectPresent = !empty($caps['object_cache']['dropin_present']);
        $envObjectPresent = self::environmentPresent($environment, 'object_cache_dropin');
        if ($envObjectPresent !== null && $envObjectPresent !== $liveObjectPresent) {
            $conflicts[] = self::conflict('object_cache_dropin_changed', 'Object cache drop-in state differs between the stored Environment Check and the live WordPress filesystem.', 'wordpress_live', 'environment_check');
        }

        $liveYmulti = !empty($caps['application']['ypm']);
        $envYmulti = self::environmentMultilingual($environment);
        if ($envYmulti !== null && $envYmulti !== $liveYmulti) {
            $conflicts[] = self::conflict('multilingual_detection_changed', 'Multilingual authority differs between stored Environment Check evidence and live capability detection.', 'wordpress_live', 'environment_check');
        }

        if ($host !== array() && self::environmentHostUnknown($environment)) {
            $conflicts[] = self::conflict('host_snapshot_newer_than_environment', 'A Host Intelligence snapshot is present while the older Environment Check still reports the host helper as unknown.', 'host_snapshot', 'environment_check');
        }

        if (!empty($dropins['object_cache']['present']) && empty($dropins['runtime']['external_object_cache'])) {
            $conflicts[] = self::conflict('object_cache_artifact_not_runtime_active', 'object-cache.php exists, but WordPress does not currently report an external object cache as active.', 'dropin_ownership', 'wordpress_live');
        }

        $providers = is_array($caps['providers'] ?? null) ? $caps['providers'] : array();
        $w3tc = is_array($providers['w3-total-cache'] ?? null) ? $providers['w3-total-cache'] : array();
        $layers = is_array($w3tc['layers'] ?? null) ? $w3tc['layers'] : array();
        $page = is_array($layers['page_cache'] ?? null) ? $layers['page_cache'] : array();
        if (!empty($dropins['advanced_cache']['present']) && !empty($w3tc['config_readable']) && empty($page['enabled'])) {
            $conflicts[] = self::conflict('advanced_cache_artifact_provider_off', 'advanced-cache.php exists while W3 Total Cache reports Page Cache configured but off. Treat the file as an ownership artifact, not proof of execution.', 'dropin_ownership', 'wordpress_live');
        }

        $pageProof = is_array($nativeProof['page_cache'] ?? null) ? $nativeProof['page_cache'] : array();
        if (($pageProof['state'] ?? '') === 'native_configured_status_header_not_exposed') {
            $conflicts[] = self::conflict('native_cache_configured_execution_unproven', 'The site-specific Nginx configuration references FastCGI cache, but no cache-status header proves HIT/MISS/BYPASS execution yet.', 'host_snapshot', 'native_runtime_proof');
        }

        if (($sources['runtime_acceptance']['version_relation'] ?? '') === 'historical') {
            $conflicts[] = self::conflict('runtime_from_previous_yperf_version', 'Stored Runtime Acceptance belongs to an earlier YPerf version. Re-run it before treating the runtime gate as current.', 'wordpress_live', 'runtime_acceptance');
        }
        if (($sources['benchmark']['quality'] ?? '') === 'failed') {
            $conflicts[] = self::conflict('benchmark_is_failed_incident_evidence', 'The stored benchmark contains functional HTTP failures and is retained as incident evidence, not as a current performance baseline.', 'wordpress_live', 'benchmark');
        } elseif (($sources['benchmark']['version_relation'] ?? '') === 'historical') {
            $conflicts[] = self::conflict('benchmark_from_previous_yperf_version', 'The stored benchmark belongs to an earlier YPerf version and should be re-run before comparison.', 'wordpress_live', 'benchmark');
        }

        return array(
            'schema' => 2,
            'generated_at' => $now,
            'sources' => $sources,
            'conflict_count' => count($conflicts),
            'conflicts' => $conflicts,
            'policy' => array(
                'live_wordpress_wins_for_current_filesystem_state' => true,
                'stored_evidence_is_not_silently_overwritten' => true,
                'host_snapshot_is_authoritative_only_for_host_fields_it_observes' => true,
                'current_yperf_version_required_for_runtime_gate' => true,
                'failed_benchmark_is_incident_evidence_not_baseline' => true,
            ),
        );
    }

    /** @return array<string,mixed> */
    private static function source(int $generatedAt, int $now, string $kind): array
    {
        if ($generatedAt <= 0) {
            return array('kind' => $kind, 'available' => false, 'freshness' => 'unknown', 'age_seconds' => null);
        }
        $age = max(0, $now - $generatedAt);
        $freshness = $age <= 900 ? 'fresh' : ($age <= 86400 ? 'recent' : 'stale');
        return array('kind' => $kind, 'available' => true, 'generated_at' => $generatedAt, 'age_seconds' => $age, 'freshness' => $freshness);
    }

    /** @param array<string,mixed> $report @return array<string,mixed> */
    private static function reportSource(array $report, int $now, string $kind): array
    {
        $source = self::source((int) ($report['generated_at'] ?? 0), $now, $kind);
        if (empty($source['available'])) {
            $source['version'] = '';
            $source['version_relation'] = 'unknown';
            $source['quality'] = 'unknown';
            $source['current_usable'] = false;
            return $source;
        }

        $version = sanitize_text_field((string) ($report['version'] ?? ''));
        $source['version'] = $version;
        $source['version_relation'] = $version === '' ? 'unknown' : ($version === YPERF_VERSION ? 'current' : 'historical');

        $summary = is_array($report['summary'] ?? null) ? $report['summary'] : array();
        $failed = (int) ($summary['fail'] ?? 0) > 0 || self::containsServerFailure($report);
        $source['quality'] = $failed ? 'failed' : 'usable';
        if ($failed) {
            $source['freshness'] = 'incident';
        }
        $source['current_usable'] = !$failed && $source['version_relation'] === 'current';
        return $source;
    }

    /** @param array<string,mixed> $report */
    private static function containsServerFailure(array $report): bool
    {
        foreach (array('tests', 'results') as $key) {
            foreach ((array) ($report[$key] ?? array()) as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $http = (int) ($row['http_code'] ?? 0);
                if ($http >= 500) {
                    return true;
                }
            }
        }
        return false;
    }

    /** @return array<string,string> */
    private static function conflict(string $id, string $message, string $preferred, string $older): array
    {
        return array('id' => $id, 'message' => $message, 'current_source' => $preferred, 'other_source' => $older);
    }

    /** @param array<string,mixed> $environment */
    private static function environmentPresent(array $environment, string $id): ?bool
    {
        foreach ((array) ($environment['checks'] ?? array()) as $check) {
            if (!is_array($check) || (string) ($check['id'] ?? '') !== $id) {
                continue;
            }
            $value = strtolower((string) ($check['value'] ?? ''));
            if (strpos($value, 'absent') !== false) {
                return false;
            }
            if (strpos($value, 'present') !== false) {
                return true;
            }
        }
        return null;
    }

    /** @param array<string,mixed> $environment */
    private static function environmentMultilingual(array $environment): ?bool
    {
        foreach ((array) ($environment['checks'] ?? array()) as $check) {
            if (!is_array($check) || (string) ($check['id'] ?? '') !== 'multilingual') {
                continue;
            }
            $value = strtolower((string) ($check['value'] ?? ''));
            if ($value === '' || strpos($value, 'not detected') !== false) {
                return false;
            }
            return true;
        }
        return null;
    }

    /** @param array<string,mixed> $environment */
    private static function environmentHostUnknown(array $environment): bool
    {
        foreach ((array) ($environment['checks'] ?? array()) as $check) {
            if (is_array($check) && (string) ($check['id'] ?? '') === 'host_helper') {
                return (string) ($check['state'] ?? '') === 'unknown';
            }
        }
        return false;
    }
}
