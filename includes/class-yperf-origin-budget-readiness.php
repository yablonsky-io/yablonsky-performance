<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/** Read-only site-specific PHP-FPM capacity evidence. */
final class OriginBudgetReadiness
{
    /** @param array<string,mixed> $host @param array<string,mixed> $runtime @return array<string,mixed> */
    public static function analyze(array $host, array $runtime): array
    {
        $fpm = is_array($host['php_fpm'] ?? null) ? $host['php_fpm'] : array();
        $hostInfo = is_array($host['host'] ?? null) ? $host['host'] : array();
        $schema = (int) ($host['schema'] ?? 0);
        $pool = sanitize_text_field((string) ($fpm['site_pool_name'] ?? ''));
        $maxChildren = max(0, (int) ($fpm['site_pool_max_children'] ?? 0));
        $currentWorkers = max(0, (int) ($fpm['site_pool_current_processes'] ?? 0));
        $avgRss = max(0.0, (float) ($fpm['site_pool_avg_rss_mb'] ?? 0));
        $maxRss = max(0.0, (float) ($fpm['site_pool_max_rss_mb'] ?? 0));
        $memTotal = max(0.0, (float) ($hostInfo['memory_total_mb'] ?? 0));
        $memAvail = max(0.0, (float) ($hostInfo['memory_available_mb'] ?? 0));
        $cpu = max(1, (int) ($hostInfo['cpu_threads'] ?? 0));
        $load1 = max(0.0, (float) ($hostInfo['load_1'] ?? 0));
        $projected = $avgRss > 0 && $maxChildren > 0 ? $avgRss * $maxChildren : 0.0;
        $projectedPct = $memTotal > 0 ? ($projected / $memTotal) * 100 : 0.0;
        $workerCpuRatio = $maxChildren > 0 ? $maxChildren / $cpu : 0.0;

        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        $runtimeCurrent = $runtime !== array() && (string) ($runtime['version'] ?? '') === YPERF_VERSION;
        $runtimeClean = $runtimeCurrent && (int) ($summary['fail'] ?? 0) === 0;

        $gates = array(
            'host_schema_current' => $schema >= 5,
            'site_pool_mapped' => $pool !== '',
            'max_children_visible' => $maxChildren > 0,
            'worker_rss_sampled' => $currentWorkers > 0 && $avgRss > 0,
            'host_memory_visible' => $memTotal > 0 && $memAvail > 0,
            'runtime_clean' => $runtimeClean,
        );
        $passed = 0;
        foreach ($gates as $ok) {
            if ($ok) {
                $passed++;
            }
        }
        $progress = (int) round(($passed / max(1, count($gates))) * 100);

        $state = 'host_proof_required';
        $label = __('REFRESH HOST PROOF', 'yablonsky-performance');
        $reason = __('Host Inspector 1.5.0+ is required to sample the mapped PHP-FPM pool and worker memory footprint.', 'yablonsky-performance');
        if ($schema >= 5 && $pool === '') {
            $state = 'pool_mapping_required';
            $label = __('SITE POOL MAPPING REQUIRED', 'yablonsky-performance');
            $reason = __('PHP-FPM is visible, but YPerf cannot bind this WordPress site to one exact pool yet.', 'yablonsky-performance');
        } elseif ($pool !== '' && ($currentWorkers <= 0 || $avgRss <= 0)) {
            $state = 'worker_sample_required';
            $label = __('WORKER SAMPLE REQUIRED', 'yablonsky-performance');
            $reason = __('The site pool is mapped, but no live worker RSS sample is available. Do not derive an Origin Budget from configuration alone.', 'yablonsky-performance');
        } elseif (!$runtimeClean) {
            $state = 'runtime_gate_required';
            $label = __('RUNTIME GATE REQUIRED', 'yablonsky-performance');
            $reason = __('Current-version Runtime Acceptance must be clean before an Origin Budget operation can be reviewed.', 'yablonsky-performance');
        } elseif ($schema >= 5 && $pool !== '' && $avgRss > 0 && $maxChildren > 0 && $memTotal > 0) {
            $state = 'capacity_review_ready';
            $label = __('ORIGIN CAPACITY REVIEW READY', 'yablonsky-performance');
            $reason = __('YPerf has an exact site pool, live worker-memory evidence and current Runtime Acceptance. This is enough to review a bounded Origin Budget, but not enough to change FPM automatically.', 'yablonsky-performance');
        }

        $pressure = 'unknown';
        if ($state === 'capacity_review_ready') {
            if ($projectedPct >= 60.0 || $workerCpuRatio >= 4.0 || ($load1 / $cpu) >= 1.0) {
                $pressure = 'attention';
            } else {
                $pressure = 'review';
            }
        }

        return array(
            'schema' => 1,
            'generated_at' => time(),
            'state' => $state,
            'label' => $label,
            'reason' => $reason,
            'progress_percent' => $progress,
            'gates' => $gates,
            'capacity' => array(
                'site_pool' => $pool,
                'pm' => sanitize_key((string) ($fpm['site_pool_pm'] ?? '')),
                'max_children' => $maxChildren,
                'max_requests' => max(0, (int) ($fpm['site_pool_max_requests'] ?? 0)),
                'current_processes' => $currentWorkers,
                'avg_worker_rss_mb' => round($avgRss, 2),
                'max_worker_rss_mb' => round($maxRss, 2),
                'projected_pool_memory_at_max_children_mb' => round($projected, 2),
                'projected_pool_memory_pct_host' => round($projectedPct, 1),
                'max_children_per_cpu' => round($workerCpuRatio, 2),
                'host_memory_total_mb' => round($memTotal, 1),
                'host_memory_available_mb' => round($memAvail, 1),
                'load_1_per_cpu' => round($load1 / $cpu, 2),
                'pressure_class' => $pressure,
            ),
            'next_operation' => array(
                'fpm_write_contract' => $state === 'capacity_review_ready' ? 'ready_for_explicit_review' : 'blocked',
                'configuration_write' => 'locked',
                'service_reload' => 'locked',
            ),
            'safety' => array(
                'fpm_changed' => false,
                'service_reloaded' => false,
                'recommendation_is_capacity_evidence_not_autotuning' => true,
            ),
        );
    }
}
