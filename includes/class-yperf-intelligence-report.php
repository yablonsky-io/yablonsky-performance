<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class IntelligenceReport
{
    /**
     * @param array<string,mixed> $caps
     * @param array<string,mixed> $runtime
     * @param array<string,array<string,mixed>> $features
     * @param array<int,mixed> $purgeLog
     * @param array<string,mixed> $environmentCheck
     * @param array<string,mixed> $benchmark
     * @param array<string,mixed> $telemetry
     * @param array<string,mixed> $migration
     * @param array<string,mixed> $inspection
     * @param array<string,mixed> $prewarm
     * @param array<string,mixed> $hostSnapshot
     * @param array<string,mixed> $nativeAdoptionPlan
     * @param array<string,mixed> $dropinOwnership
     * @param array<string,mixed> $evidenceReconciliation
     * @param array<string,mixed> $nativeRuntimeProof
     * @param array<string,mixed> $performancePlan
     * @param array<string,mixed> $effectiveness
     * @param array<string,mixed> $optimizationOutcome
     * @param array<string,mixed> $runtimeHistory
     * @param array<string,mixed> $autopilot
     * @param array<string,mixed> $campaignReport
     * @param array<string,mixed> $purgeResult
     * @return array<string,mixed>
     */
    public static function build(array $caps, array $runtime, array $features, array $purgeLog, array $environmentCheck = array(), array $benchmark = array(), array $telemetry = array(), array $migration = array(), array $inspection = array(), array $prewarm = array(), array $hostSnapshot = array(), array $nativeAdoptionPlan = array(), array $dropinOwnership = array(), array $evidenceReconciliation = array(), array $nativeRuntimeProof = array(), array $performancePlan = array(), array $effectiveness = array(), array $optimizationOutcome = array(), array $runtimeHistory = array(), array $autopilot = array(), array $campaignReport = array(), array $upgradeHealth = array(), array $nativePageCacheAdoption = array(), array $nativePageCacheOperations = array(), array $objectCacheAdoption = array(), array $originBudgetReadiness = array(), array $controlPlaneReadiness = array(), array $objectCacheOperationContract = array(), array $originBudgetContract = array(), array $releaseReadiness = array(), array $objectCacheExecution = array(), array $originBudgetAdoption = array(), array $purgeResult = array()): array
    {
        global $wpdb;

        $environment = array(
            'wordpress_version' => get_bloginfo('version'),
            'php_version'       => PHP_VERSION,
            'php_sapi'          => PHP_SAPI,
            'database_server'   => is_object($wpdb) && method_exists($wpdb, 'db_version') ? (string) $wpdb->db_version() : 'unknown',
            'multisite'         => is_multisite(),
            'permalink_structure' => (string) get_option('permalink_structure', ''),
            'home_scheme'       => (string) wp_parse_url(home_url('/'), PHP_URL_SCHEME),
            'extensions'        => self::extensions(),
            'php_runtime'       => array(
                'open_basedir'            => (string) ini_get('open_basedir') !== '',
                'zlib_output_compression' => self::iniEnabled('zlib.output_compression'),
                'set_time_limit_available'=> function_exists('set_time_limit'),
                'memory_limit'            => (string) ini_get('memory_limit'),
                'max_execution_time'      => (string) ini_get('max_execution_time'),
            ),
        );

        $featureStates = array();
        foreach ($features as $id => $feature) {
            $featureStates[(string) $id] = array(
                'name'     => (string) ($feature['name'] ?? ''),
                'group'    => (string) ($feature['group'] ?? ''),
                'state'    => (string) ($feature['state'] ?? ''),
                'status'   => (string) ($feature['status'] ?? ''),
                'provider' => (string) ($feature['provider'] ?? ''),
            );
        }

        return array(
            'schema'       => 20,
            'generated_at' => time(),
            'plugin'       => 'Yablonsky Performance',
            'version'      => YPERF_VERSION,
            'site'         => home_url('/'),
            'privacy'      => array(
                'remote_sent'      => false,
                'secrets_included' => false,
                'note'             => 'Generated locally. Secret option values, API keys, passwords and tokens are intentionally excluded.',
            ),
            'environment'  => $environment,
            'capabilities' => self::sanitizeCapabilities($caps),
            'runtime_acceptance' => self::runtimeSummary($runtime),
            'origin_cost' => self::sanitizeArray(is_array($runtime['origin_cost_summary'] ?? null) ? $runtime['origin_cost_summary'] : array()),
            'environment_check' => self::sanitizeArray($environmentCheck),
            'benchmark'     => self::sanitizeArray($benchmark),
            'telemetry'     => self::sanitizeArray($telemetry),
            'migration'     => self::sanitizeArray($migration),
            'cacheability_inspection' => self::sanitizeArray($inspection),
            'prewarm'       => self::sanitizeArray($prewarm),
            'purge_last_result' => self::sanitizeArray($purgeResult),
            'host_intelligence' => self::sanitizeArray($hostSnapshot),
            'native_adoption_plan' => self::sanitizeArray($nativeAdoptionPlan),
            'native_page_cache_adoption' => self::sanitizeArray($nativePageCacheAdoption),
            'native_page_cache_operations' => self::sanitizeArray($nativePageCacheOperations),
            'persistent_object_cache_adoption' => self::sanitizeArray($objectCacheAdoption),
            'origin_budget_readiness' => self::sanitizeArray($originBudgetReadiness),
            'control_plane_readiness' => self::sanitizeArray($controlPlaneReadiness),
            'persistent_object_cache_operation_contract' => self::sanitizeArray($objectCacheOperationContract),
            'persistent_object_cache_execution' => self::sanitizeArray($objectCacheExecution),
            'origin_budget_operation_contract' => self::sanitizeArray($originBudgetContract),
            'origin_budget_adoption' => self::sanitizeArray($originBudgetAdoption),
            'release_readiness' => self::sanitizeArray($releaseReadiness),
            'dropin_ownership' => self::sanitizeArray($dropinOwnership),
            'evidence_reconciliation' => self::sanitizeArray($evidenceReconciliation),
            'native_runtime_proof' => self::sanitizeArray($nativeRuntimeProof),
            'performance_plan' => self::sanitizeArray($performancePlan),
            'performance_effectiveness' => self::sanitizeArray($effectiveness),
            'optimization_outcome' => self::sanitizeArray($optimizationOutcome),
            'runtime_history' => self::sanitizeArray($runtimeHistory),
            'performance_autopilot' => self::sanitizeArray($autopilot),
            'optimization_campaigns' => self::sanitizeArray($campaignReport),
            'admin_upgrade_health' => self::sanitizeArray($upgradeHealth),
            'technical_identity' => array(
                'plugin_basename' => sanitize_text_field((string) YPERF_BASENAME),
                'canonical_directory' => strpos((string) YPERF_BASENAME, 'yablonsky-performance/') === 0,
                'runtime_identity' => 'canonical_only',
                'technical_prefix' => 'YPerf',
                'state_prefix' => 'yperf_',
                'artifact_prefix' => 'yperf-',
            ),
            'features'     => $featureStates,
            'purge_audit'  => array(
                'events_retained' => min(50, count($purgeLog)),
                'latest_type'     => isset($purgeLog[0]['type']) ? sanitize_key((string) $purgeLog[0]['type']) : '',
            ),
            'recommendations' => Recommendations::build($caps, $runtime, $nativeRuntimeProof),
        );
    }

    /** @return array<string,bool> */
    private static function extensions(): array
    {
        return array(
            'curl'       => extension_loaded('curl'),
            'zlib'       => extension_loaded('zlib'),
            'brotli'     => extension_loaded('brotli'),
            'mbstring'   => extension_loaded('mbstring'),
            'redis'      => extension_loaded('redis'),
            'memcached'  => extension_loaded('memcached'),
            'memcache'   => extension_loaded('memcache'),
            'fileinfo'   => extension_loaded('fileinfo'),
            'hash'       => extension_loaded('hash'),
            'tidy'       => extension_loaded('tidy'),
            'ssh2'       => extension_loaded('ssh2'),
            'opcache'    => extension_loaded('Zend OPcache'),
        );
    }

    private static function iniEnabled(string $key): bool
    {
        $value = strtolower(trim((string) ini_get($key)));
        return in_array($value, array('1', 'on', 'yes', 'true'), true);
    }

    /** @param array<string,mixed> $caps @return array<string,mixed> */
    private static function sanitizeCapabilities(array $caps): array
    {
        $allowed = array('web_server', 'page_cache', 'object_cache', 'opcode_cache', 'origin', 'providers', 'application');
        $clean = array();
        foreach ($allowed as $key) {
            if (isset($caps[$key]) && is_array($caps[$key])) {
                $clean[$key] = self::sanitizeArray($caps[$key]);
            }
        }
        return $clean;
    }

    /** @param array<mixed> $values @return array<mixed> */
    private static function sanitizeArray(array $values): array
    {
        $clean = array();
        foreach ($values as $key => $value) {
            $safeKey = is_int($key) ? $key : sanitize_key((string) $key);
            if (is_array($value)) {
                $clean[$safeKey] = self::sanitizeArray($value);
            } elseif (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
                $clean[$safeKey] = $value;
            } else {
                $clean[$safeKey] = sanitize_text_field((string) $value);
            }
        }
        return $clean;
    }

    /** @param array<string,mixed> $runtime @return array<string,mixed> */
    private static function runtimeSummary(array $runtime): array
    {
        if ($runtime === array()) {
            return array('available' => false);
        }
        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        $timings = array();
        foreach ((array) ($runtime['tests'] ?? array()) as $test) {
            if (!is_array($test) || !isset($test['ms'])) {
                continue;
            }
            $timings[] = array(
                'name' => sanitize_text_field((string) ($test['name'] ?? '')),
                'ms'   => (int) $test['ms'],
                'http' => isset($test['http_code']) ? (int) $test['http_code'] : null,
            );
        }
        return array(
            'available' => true,
            'version' => sanitize_text_field((string) ($runtime['version'] ?? '')),
            'version_current' => (string) ($runtime['version'] ?? '') === YPERF_VERSION,
            'summary'   => array(
                'pass'      => (int) ($summary['pass'] ?? 0),
                'fail'      => (int) ($summary['fail'] ?? 0),
                'skip'      => (int) ($summary['skip'] ?? 0),
                'attention' => (int) ($summary['attention'] ?? 0),
            ),
            'frontend_timings' => $timings,
        );
    }
}
