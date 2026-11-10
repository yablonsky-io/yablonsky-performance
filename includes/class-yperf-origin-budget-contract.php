<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * R2 reviewed Origin Budget contract. It freezes exact evidence and a
 * conservative review envelope, but performs no PHP-FPM write/reload.
 */
final class OriginBudgetContract
{
    /** @param array<string,mixed> $readiness @param array<string,mixed> $controlPlane @param array<string,mixed> $host @return array<string,mixed> */
    public static function build(array $readiness, array $controlPlane, array $host): array
    {
        $capacity = is_array($readiness['capacity'] ?? null) ? $readiness['capacity'] : array();
        $fpm = is_array($host['php_fpm'] ?? null) ? $host['php_fpm'] : array();
        $hostInfo = is_array($host['host'] ?? null) ? $host['host'] : array();
        $cpState = sanitize_key((string) ($controlPlane['state'] ?? 'evidence_required'));

        $pool = sanitize_text_field((string) ($capacity['site_pool'] ?? ''));
        $currentMaxChildren = max(0, (int) ($capacity['max_children'] ?? 0));
        $currentMaxRequests = max(0, (int) ($capacity['max_requests'] ?? 0));
        $avgRss = max(0.0, (float) ($capacity['avg_worker_rss_mb'] ?? 0));
        $maxRss = max(0.0, (float) ($capacity['max_worker_rss_mb'] ?? 0));
        $cpu = max(1, (int) ($hostInfo['cpu_threads'] ?? 0));
        $memTotal = max(0.0, (float) ($hostInfo['memory_total_mb'] ?? 0));
        $memAvail = max(0.0, (float) ($hostInfo['memory_available_mb'] ?? 0));
        $reserveWorkers = max(1, (int) ceil(max(1, $currentMaxChildren) * 0.20));

        $effectiveWorkerRss = max($avgRss, $maxRss > 0 ? $maxRss * 0.75 : 0.0);
        $memoryEnvelope = 0;
        if ($effectiveWorkerRss > 0 && $memAvail > 0) {
            // Use at most half of currently available host RAM for any proposed
            // extra concurrency. This is a review envelope, never autotuning.
            $memoryEnvelope = max(0, (int) floor(($memAvail * 0.50) / $effectiveWorkerRss));
        }
        $cpuEnvelope = max(1, (int) floor($cpu * 1.50));
        $candidateCeiling = $currentMaxChildren > 0
            ? max(1, min($cpuEnvelope, $currentMaxChildren + $memoryEnvelope))
            : 0;
        $existingLimitWithinReviewCeiling = $currentMaxChildren > 0
            && $candidateCeiling > 0
            && $currentMaxChildren <= $candidateCeiling;

        $readinessReady = (string) ($readiness['state'] ?? '') === 'capacity_review_ready';
        $controlPlaneReady = in_array($cpState, array('reserve_ready', 'capacity_attention'), true);
        $blockedByIncident = in_array($cpState, array('memory_blocked', 'incident_attention', 'runtime_blocked'), true);

        $gates = array(
            'origin_capacity_evidence_ready' => $readinessReady,
            'control_plane_not_blocked' => !$blockedByIncident,
            'control_plane_evidence_available' => $controlPlaneReady,
            'site_pool_mapped' => $pool !== '',
            'worker_memory_sampled' => $avgRss > 0,
            'current_max_children_visible' => $currentMaxChildren > 0,
            'host_memory_visible' => $memTotal > 0 && $memAvail > 0,
            'existing_limit_within_review_ceiling' => $existingLimitWithinReviewCeiling,
        );
        $passed = 0;
        foreach ($gates as $ok) {
            if ($ok) {
                $passed++;
            }
        }
        $progress = (int) round(($passed / max(1, count($gates))) * 100);

        $state = 'evidence_required';
        $label = __('ORIGIN BUDGET EVIDENCE REQUIRED', 'yablonsky-performance');
        $reason = __('Map the exact site PHP-FPM pool, sample worker memory and keep Runtime Acceptance clean before freezing an Origin Budget operation contract.', 'yablonsky-performance');
        if ($blockedByIncident) {
            $state = 'control_plane_blocked';
            $label = __('CONTROL PLANE BLOCKS FPM TUNING', 'yablonsky-performance');
            $reason = __('Recent admin/resource incident evidence blocks FPM tuning. Preserve WordPress control-plane access before changing frontend concurrency.', 'yablonsky-performance');
        } elseif ($readinessReady && $controlPlaneReady && $pool !== '' && $currentMaxChildren > 0 && $existingLimitWithinReviewCeiling) {
            $state = 'operation_contract_ready';
            $label = __('ORIGIN BUDGET CONTRACT READY', 'yablonsky-performance');
            $reason = __('YPerf has exact pool identity, live worker memory evidence and a control-plane reserve model. R2 freezes the reviewed before-state and rollback authority without editing PHP-FPM.', 'yablonsky-performance');
        }

        $enforcementFingerprint = hash('sha256', (string) wp_json_encode(array(
            'pool' => $pool,
            'pm' => sanitize_key((string) ($fpm['site_pool_pm'] ?? '')),
            'max_children' => $currentMaxChildren,
            'max_requests' => $currentMaxRequests,
            'reserve_workers' => $reserveWorkers,
        )));

        $fingerprint = hash('sha256', (string) wp_json_encode(array(
            'pool' => $pool,
            'pm' => sanitize_key((string) ($fpm['site_pool_pm'] ?? '')),
            'max_children' => $currentMaxChildren,
            'max_requests' => $currentMaxRequests,
            'avg_rss' => round($avgRss, 2),
            'max_rss' => round($maxRss, 2),
            'cpu' => $cpu,
            'mem_total' => round($memTotal, 1),
            'mem_available' => round($memAvail, 1),
            'reserve_workers' => $reserveWorkers,
            'yperf_version' => YPERF_VERSION,
        )));

        return array(
            'schema' => 2,
            'generated_at' => time(),
            'state' => $state,
            'label' => $label,
            'reason' => $reason,
            'progress_percent' => $progress,
            'gates' => $gates,
            'contract' => array(
                'fingerprint' => $fingerprint,
                'enforcement_fingerprint' => $enforcementFingerprint,
                'site_pool' => $pool,
                'current_pm' => sanitize_key((string) ($fpm['site_pool_pm'] ?? '')),
                'current_max_children' => $currentMaxChildren,
                'current_max_requests' => $currentMaxRequests,
                'current_avg_worker_rss_mb' => round($avgRss, 2),
                'current_max_worker_rss_mb' => round($maxRss, 2),
                'control_plane_reserve_workers' => $reserveWorkers,
                'review_cpu_envelope_workers' => $cpuEnvelope,
                'review_memory_extra_workers' => $memoryEnvelope,
                'candidate_max_children_ceiling' => $candidateCeiling,
            ),
            'planned_apply' => array(
                'exact_pool_file_required' => true,
                'before_state_backup_required' => true,
                'php_fpm_config_test_required' => true,
                'graceful_reload_required' => true,
                'readback_required' => true,
                'runtime_acceptance_required' => true,
                'rollback_required' => true,
            ),
            'safety' => array(
                'fpm_changed_in_this_release' => false,
                'service_reloaded_in_this_release' => false,
                'candidate_is_review_envelope_not_autotuning' => true,
                'preserve_control_plane_reserve' => true,
            ),
        );
    }
}
