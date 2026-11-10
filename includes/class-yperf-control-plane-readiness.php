<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Read-only admin/control-plane reliability gate.
 *
 * The gate converts transient wp-admin incident evidence, PHP memory headroom,
 * current Runtime Acceptance and host/FPM headroom into one conservative
 * operator state. It never raises limits or changes FPM/Nginx/WordPress.
 */
final class ControlPlaneReadiness
{
    private const HOST_MAX_AGE = 86400;
    private const INCIDENT_LOOKBACK = 21600; // 6 hours.

    /** @param array<string,mixed> $upgradeHealth @param array<string,mixed> $host @param array<string,mixed> $runtime @return array<string,mixed> */
    public static function analyze(array $upgradeHealth, array $host, array $runtime): array
    {
        $resource = is_array($upgradeHealth['resource_headroom'] ?? null) ? $upgradeHealth['resource_headroom'] : array();
        $events = is_array($upgradeHealth['events'] ?? null) ? $upgradeHealth['events'] : array();
        $hostInfo = is_array($host['host'] ?? null) ? $host['host'] : array();
        $fpm = is_array($host['php_fpm'] ?? null) ? $host['php_fpm'] : array();

        $runtimeSummary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        $runtimeCurrent = $runtime !== array() && (string) ($runtime['version'] ?? '') === YPERF_VERSION;
        $runtimeClean = $runtimeCurrent && (int) ($runtimeSummary['fail'] ?? 0) === 0;

        $hostGeneratedAt = max(0, (int) ($host['generated_at'] ?? 0));
        $hostAge = $hostGeneratedAt > 0 ? max(0, time() - $hostGeneratedAt) : 0;
        $hostFresh = $hostGeneratedAt > 0 && $hostAge <= self::HOST_MAX_AGE;
        $hostSchema = (int) ($host['schema'] ?? 0);

        $memoryState = sanitize_key((string) ($resource['state'] ?? 'unknown'));
        $memoryLimit = (float) ($resource['memory_limit_mb'] ?? -1);
        $requestHeadroom = (float) ($resource['headroom_mb'] ?? -1);

        $cpu = max(1, (int) ($hostInfo['cpu_threads'] ?? 0));
        $load1 = max(0.0, (float) ($hostInfo['load_1'] ?? 0));
        $loadPerCpu = $cpu > 0 ? $load1 / $cpu : 0.0;
        $memoryTotal = max(0.0, (float) ($hostInfo['memory_total_mb'] ?? 0));
        $memoryAvailable = max(0.0, (float) ($hostInfo['memory_available_mb'] ?? 0));
        $memoryAvailablePct = $memoryTotal > 0 ? ($memoryAvailable / $memoryTotal) * 100 : 0.0;

        $pool = sanitize_text_field((string) ($fpm['site_pool_name'] ?? ''));
        $maxChildren = max(0, (int) ($fpm['site_pool_max_children'] ?? 0));
        $currentProcesses = max(0, (int) ($fpm['site_pool_current_processes'] ?? 0));
        $poolMemoryLimit = sanitize_text_field((string) ($fpm['site_pool_memory_limit'] ?? ''));
        $reserveWorkers = $maxChildren > 0 ? max(1, (int) ceil($maxChildren * 0.20)) : 0;
        $operationalCeiling = $maxChildren > 0 ? max(0, $maxChildren - $reserveWorkers) : 0;
        $workerReserveAvailable = $maxChildren > 0 && $currentProcesses <= $operationalCeiling;

        $recent = self::recentIncidents($events);
        $recentFatal = (int) ($recent['fatal_count'] ?? 0) > 0;
        $recentMemoryFatal = (int) ($recent['memory_fatal_count'] ?? 0) > 0;
        $recent5xx = (int) ($recent['response_5xx_count'] ?? 0) > 0;

        $gates = array(
            'runtime_current_clean' => $runtimeClean,
            'admin_memory_not_constrained' => $memoryState !== 'constrained',
            'no_recent_php_fatal' => !$recentFatal,
            'no_recent_memory_fatal' => !$recentMemoryFatal,
            'host_proof_current' => $hostSchema >= 5 && $hostFresh,
            'host_memory_headroom' => $memoryTotal > 0 && $memoryAvailablePct >= 15.0,
            'host_load_below_saturation' => $loadPerCpu < 1.0,
            'site_fpm_pool_mapped' => $pool !== '' && $maxChildren > 0,
            'admin_worker_reserve_visible' => $workerReserveAvailable,
        );

        $passed = 0;
        foreach ($gates as $ok) {
            if ($ok) {
                $passed++;
            }
        }
        $progress = (int) round(($passed / max(1, count($gates))) * 100);

        $state = 'evidence_required';
        $label = __('CONTROL-PLANE EVIDENCE REQUIRED', 'yablonsky-performance');
        $reason = __('Run current Runtime Acceptance and import a fresh Host Intelligence snapshot before YPerf treats wp-admin headroom as proven.', 'yablonsky-performance');

        if (!$runtimeClean) {
            $state = 'runtime_blocked';
            $label = __('RUNTIME BLOCKED', 'yablonsky-performance');
            $reason = __('The current YPerf Runtime Acceptance is missing, stale or contains a functional failure. Control-plane capacity is not considered releasable until the site baseline is clean.', 'yablonsky-performance');
        } elseif ($recentMemoryFatal || $memoryState === 'constrained') {
            $state = 'memory_blocked';
            $label = __('ADMIN MEMORY · BLOCKED', 'yablonsky-performance');
            $reason = __('Recent memory-exhaustion evidence or constrained PHP admin headroom exists. Heavy plugin checks, upgrades and control-plane workflows should retain explicit memory reserve before host tuning is approved.', 'yablonsky-performance');
        } elseif ($recentFatal || $recent5xx) {
            $state = 'incident_attention';
            $label = __('RECENT ADMIN INCIDENT · REVIEW', 'yablonsky-performance');
            $reason = __('Recent wp-admin fatal/5xx lifecycle evidence exists. Keep the control plane observable and avoid aggressive host changes until the incident window stays clean.', 'yablonsky-performance');
        } elseif ($hostSchema < 5 || !$hostFresh) {
            // Host Intelligence is an optional deeper-evidence lane. A clean
            // WordPress control plane must not be blocked from Production only
            // because the optional native inspector is not connected. Host
            // proof remains mandatory for host/FPM write-boundary decisions.
            $state = 'local_ready_host_optional';
            $label = __('LOCAL CONTROL PLANE READY', 'yablonsky-performance');
            $reason = __('WordPress-side evidence is clean: Runtime Acceptance is current, admin memory is not constrained, and no recent fatal/5xx incident is retained. Host Intelligence remains optional here and becomes required only for native host/FPM operation contracts.', 'yablonsky-performance');
        } elseif ($memoryAvailablePct < 15.0 || $loadPerCpu >= 1.0 || !$workerReserveAvailable) {
            $state = 'capacity_attention';
            $label = __('CONTROL-PLANE RESERVE · ATTENTION', 'yablonsky-performance');
            $reason = __('The site is functional, but current host/FPM headroom does not prove a comfortable reserve for admin work during frontend pressure. Origin Budget review should preserve an explicit control-plane reserve.', 'yablonsky-performance');
        } else {
            $state = 'reserve_ready';
            $label = __('CONTROL-PLANE RESERVE READY', 'yablonsky-performance');
            $reason = __('Current Runtime Acceptance is clean, no recent fatal evidence is retained, host memory/load are below the conservative attention thresholds and the mapped FPM pool currently leaves worker reserve.', 'yablonsky-performance');
        }

        return array(
            'schema' => 2,
            'generated_at' => time(),
            'state' => $state,
            'label' => $label,
            'reason' => $reason,
            'progress_percent' => $progress,
            'gates' => $gates,
            'runtime' => array(
                'current' => $runtimeCurrent,
                'clean' => $runtimeClean,
                'failures' => (int) ($runtimeSummary['fail'] ?? 0),
            ),
            'admin_memory' => array(
                'state' => $memoryState,
                'memory_limit_mb' => $memoryLimit,
                'request_headroom_mb' => $requestHeadroom,
            ),
            'host' => array(
                'schema' => $hostSchema,
                'proof_age_seconds' => $hostAge,
                'cpu_threads' => $cpu,
                'load_1_per_cpu' => round($loadPerCpu, 2),
                'memory_available_mb' => round($memoryAvailable, 1),
                'memory_available_pct' => round($memoryAvailablePct, 1),
            ),
            'fpm_reserve' => array(
                'site_pool' => $pool,
                'max_children' => $maxChildren,
                'current_processes' => $currentProcesses,
                'pool_memory_limit' => $poolMemoryLimit,
                'reserve_workers' => $reserveWorkers,
                'operational_ceiling_before_reserve' => $operationalCeiling,
                'reserve_visible_now' => $workerReserveAvailable,
            ),
            'recent_incidents' => $recent,
            'local_core_ready' => in_array($state, array('local_ready_host_optional', 'reserve_ready', 'capacity_attention'), true),
            'host_extended_ready' => $state === 'reserve_ready',
            'policy' => array(
                'lookback_seconds' => self::INCIDENT_LOOKBACK,
                'memory_available_attention_below_pct' => 15,
                'load_attention_at_per_cpu' => 1.0,
                'fpm_worker_reserve_pct' => 20,
                'automatic_host_write' => false,
            ),
        );
    }

    /** @param array<int,mixed> $events @return array<string,int> */
    private static function recentIncidents(array $events): array
    {
        $cutoff = time() - self::INCIDENT_LOOKBACK;
        $fatal = 0;
        $memoryFatal = 0;
        $responses5xx = 0;
        $observed = 0;
        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }
            $timestamp = (int) ($event['timestamp'] ?? 0);
            if ($timestamp <= 0 || $timestamp < $cutoff) {
                continue;
            }
            $observed++;
            if (!empty($event['fatal'])) {
                $fatal++;
                if ((string) ($event['fatal_class'] ?? '') === 'memory_exhaustion') {
                    $memoryFatal++;
                }
            }
            $code = (int) ($event['response_code'] ?? 0);
            if ($code >= 500 && $code <= 599) {
                $responses5xx++;
            }
        }
        return array(
            'events_in_window' => $observed,
            'fatal_count' => $fatal,
            'memory_fatal_count' => $memoryFatal,
            'response_5xx_count' => $responses5xx,
        );
    }
}
