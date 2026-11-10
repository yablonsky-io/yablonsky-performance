<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * One-time state-key migration from the historical YPC namespace to YPerf.
 *
 * This does not load, activate, delete or otherwise interact with a historical
 * plugin package. It only preserves local WordPress option state created by
 * earlier canonical Yablonsky Performance releases.
 */
final class StateMigration
{
    private const MARKER = 'yperf_state_namespace_migration_v1';
    private const RECOVERY_MARKER = 'yperf_state_namespace_recovery_v2';
    private const PATCH_EVIDENCE_MARKER_1931 = 'yperf_patch_evidence_carry_1_93_1';
    private const PATCH_EVIDENCE_MARKER_1932 = 'yperf_patch_evidence_carry_1_93_2';
    private const PATCH_EVIDENCE_MARKER_1933 = 'yperf_patch_evidence_carry_1_93_3';
    private const PATCH_EVIDENCE_MARKER_1940 = 'yperf_patch_evidence_carry_1_94_0';
    private const PATCH_EVIDENCE_MARKER_1950 = 'yperf_patch_evidence_carry_1_95_0';
    private const PATCH_EVIDENCE_MARKER_1960 = 'yperf_patch_evidence_carry_1_96_0';
    private const PATCH_EVIDENCE_MARKER_1970 = 'yperf_patch_evidence_carry_1_97_0';
    private const PATCH_EVIDENCE_MARKER_1990 = 'yperf_patch_evidence_carry_1_99_0';
    private const MAJOR_EVIDENCE_MARKER_2000 = 'yperf_major_evidence_carry_2_0_0';
    private const PATCH_EVIDENCE_MARKER_2100 = 'yperf_patch_evidence_carry_2_1_0';

    /** @var array<string,string> */
    private const OPTION_MAP = array(
        'ypc_telemetry_v1'                    => 'yperf_telemetry_v1',
        'ypc_cacheability_inspection_v1'      => 'yperf_cacheability_inspection_v1',
        'ypc_benchmark_report_v1'             => 'yperf_benchmark_report_v1',
        'ypc_runtime_acceptance_v1'           => 'yperf_runtime_acceptance_v1',
        'ypc_performance_profile'             => 'yperf_performance_profile',
        'ypc_performance_plan_last_apply_v1'  => 'yperf_performance_plan_last_apply_v1',
        'ypc_frontend_optimizer'              => 'yperf_frontend_optimizer',
        'ypc_deep_archive_guard'              => 'yperf_deep_archive_guard',
        'ypc_external_purge_bridge'           => 'yperf_external_purge_bridge',
        'ypc_prewarm_report_v1'               => 'yperf_prewarm_report_v1',
        'ypc_host_snapshot_v1'                => 'yperf_host_snapshot_v1',
        'ypc_last_purge_result_v1'            => 'yperf_last_purge_result_v1',
        'ypc_dismissed_recommendations_v1'    => 'yperf_dismissed_recommendations_v1',
        'ypc_last_invalidation'               => 'yperf_last_invalidation',
        'ypc_invalidation_log_v1'             => 'yperf_invalidation_log_v1',
        'ypc_optimization_outcome_v1'         => 'yperf_optimization_outcome_v1',
        'ypc_environment_check_v1'            => 'yperf_environment_check_v1',
        'ypc_adoption_snapshot_v1'            => 'yperf_adoption_snapshot_v1',
    );

    public static function migrate(): void
    {
        if ((string) get_option(self::MARKER, '') !== '1') {
            self::migratePrimary();
        }
        self::recoverEmptyCanonicalEvidence();
        self::carryForwardCompatiblePatchEvidence1931();
        self::carryForwardCompatiblePatchEvidence1932();
        self::carryForwardCompatiblePatchEvidence1933();
        self::carryForwardCompatiblePatchEvidence1940();
        self::carryForwardCompatiblePatchEvidence1950();
        self::carryForwardCompatiblePatchEvidence1960();
        self::carryForwardCompatiblePatchEvidence1970();
        self::carryForwardCompatiblePatchEvidence1990();
        self::carryForwardCompatibleMajorEvidence2000();
        self::carryForwardCompatiblePatchEvidence2100();
    }

    private static function migratePrimary(): void
    {

        foreach (self::OPTION_MAP as $legacy => $canonical) {
            $canonicalValue = get_option($canonical, null);
            if ($canonicalValue !== null) {
                continue;
            }

            $legacyValue = get_option($legacy, null);
            if ($legacyValue === null) {
                continue;
            }

            update_option($canonical, self::normalizeLegacyValue($legacyValue), false);
        }

        update_option(self::MARKER, '1', false);
    }

    private static function recoverEmptyCanonicalEvidence(): void
    {
        if ((string) get_option(self::RECOVERY_MARKER, '') === '1') {
            return;
        }
        $recoverable = array(
            'ypc_runtime_acceptance_v1' => 'yperf_runtime_acceptance_v1',
            'ypc_benchmark_report_v1' => 'yperf_benchmark_report_v1',
            'ypc_telemetry_v1' => 'yperf_telemetry_v1',
            'ypc_host_snapshot_v1' => 'yperf_host_snapshot_v1',
            'ypc_environment_check_v1' => 'yperf_environment_check_v1',
            'ypc_adoption_snapshot_v1' => 'yperf_adoption_snapshot_v1',
            'ypc_optimization_outcome_v1' => 'yperf_optimization_outcome_v1',
        );
        foreach ($recoverable as $legacy => $canonical) {
            $canonicalValue = get_option($canonical, null);
            $legacyValue = get_option($legacy, null);
            $canonicalEmpty = $canonicalValue === null || $canonicalValue === '' || $canonicalValue === array();
            $legacyMeaningful = !($legacyValue === null || $legacyValue === '' || $legacyValue === array());
            if ($canonicalEmpty && $legacyMeaningful) {
                update_option($canonical, self::normalizeLegacyValue($legacyValue), false);
            }
        }
        update_option(self::RECOVERY_MARKER, '1', false);
    }



    /** Preserve clean 1.93.0 read-only evidence across the 1.93.1 patch. */
    private static function carryForwardCompatiblePatchEvidence1931(): void
    {
        if (YPERF_VERSION !== '1.93.1') {
            return;
        }
        if ((string) get_option(self::PATCH_EVIDENCE_MARKER_1931, '') === '1') {
            return;
        }
        foreach (array('yperf_runtime_acceptance_v1', 'yperf_benchmark_report_v1') as $option) {
            $report = get_option($option, array());
            if (!is_array($report) || (string) ($report['version'] ?? '') !== '1.93.0') {
                continue;
            }
            if ($option === 'yperf_runtime_acceptance_v1') {
                $summary = is_array($report['summary'] ?? null) ? $report['summary'] : array();
                if ((int) ($summary['fail'] ?? 0) > 0) {
                    continue;
                }
            }
            $report['carried_forward_from_version'] = '1.93.0';
            $report['version'] = YPERF_VERSION;
            update_option($option, $report, false);
        }
        update_option(self::PATCH_EVIDENCE_MARKER_1931, '1', false);
    }

    /** Preserve only clean 1.93.1 read-only evidence across the 1.93.2 patch. */
    private static function carryForwardCompatiblePatchEvidence1932(): void
    {
        if (YPERF_VERSION !== '1.93.2') {
            return;
        }
        if ((string) get_option(self::PATCH_EVIDENCE_MARKER_1932, '') === '1') {
            return;
        }
        foreach (array('yperf_runtime_acceptance_v1', 'yperf_benchmark_report_v1') as $option) {
            $report = get_option($option, array());
            if (!is_array($report) || (string) ($report['version'] ?? '') !== '1.93.1') {
                continue;
            }
            if ($option === 'yperf_runtime_acceptance_v1') {
                $summary = is_array($report['summary'] ?? null) ? $report['summary'] : array();
                if ((int) ($summary['fail'] ?? 0) > 0) {
                    continue;
                }
            }
            $report['carried_forward_from_version'] = '1.93.1';
            $report['version'] = YPERF_VERSION;
            update_option($option, $report, false);
        }
        update_option(self::PATCH_EVIDENCE_MARKER_1932, '1', false);
    }

    /** Preserve compatible 1.93.2 evidence across the 1.93.3 admin-UX patch. */
    private static function carryForwardCompatiblePatchEvidence1933(): void
    {
        if (YPERF_VERSION !== '1.93.3') {
            return;
        }
        if ((string) get_option(self::PATCH_EVIDENCE_MARKER_1933, '') === '1') {
            return;
        }

        foreach (array('yperf_runtime_acceptance_v1', 'yperf_benchmark_report_v1') as $option) {
            $report = get_option($option, array());
            if (!is_array($report) || (string) ($report['version'] ?? '') !== '1.93.2') {
                continue;
            }
            if ($option === 'yperf_runtime_acceptance_v1') {
                $summary = is_array($report['summary'] ?? null) ? $report['summary'] : array();
                if ((int) ($summary['fail'] ?? 0) > 0) {
                    continue;
                }
            }
            $report['carried_forward_from_version'] = '1.93.2';
            $report['version'] = YPERF_VERSION;
            update_option($option, $report, false);
        }

        $execution = get_option('yperf_page_cache_execution_v1', array());
        if (is_array($execution) && (string) ($execution['state'] ?? '') === 'active') {
            $proof = is_array($execution['last_proof'] ?? null) ? $execution['last_proof'] : array();
            if (!empty($proof['ok']) && (string) ($proof['version'] ?? '') === '1.93.2') {
                $proof['carried_forward_from_version'] = '1.93.2';
                $proof['version'] = YPERF_VERSION;
                $execution['last_proof'] = $proof;
                $execution['carried_forward_from_version'] = '1.93.2';
                $execution['version'] = YPERF_VERSION;
                update_option('yperf_page_cache_execution_v1', $execution, false);
            }
        }

        foreach (array('yperf_page_cache_campaign_state_v1', 'yperf_page_cache_campaign_report_v1') as $option) {
            $value = get_option($option, array());
            if (!is_array($value) || (string) ($value['version'] ?? '') !== '1.93.2') {
                continue;
            }
            $status = (string) ($value['status'] ?? '');
            if (!in_array($status, array('complete', 'proven'), true)) {
                continue;
            }
            $value['carried_forward_from_version'] = '1.93.2';
            $value['version'] = YPERF_VERSION;
            update_option($option, $value, false);
        }

        update_option(self::PATCH_EVIDENCE_MARKER_1933, '1', false);
    }

    /** Preserve compatible 1.93.3 evidence across the 1.94.0 truth/stability release. */
    private static function carryForwardCompatiblePatchEvidence1940(): void
    {
        if (YPERF_VERSION !== '1.94.0') {
            return;
        }
        if ((string) get_option(self::PATCH_EVIDENCE_MARKER_1940, '') === '1') {
            return;
        }

        foreach (array('yperf_runtime_acceptance_v1', 'yperf_benchmark_report_v1') as $option) {
            $report = get_option($option, array());
            if (!is_array($report) || !in_array((string) ($report['version'] ?? ''), array('1.93.2', '1.93.3'), true)) {
                continue;
            }
            if ($option === 'yperf_runtime_acceptance_v1') {
                $summary = is_array($report['summary'] ?? null) ? $report['summary'] : array();
                if ((int) ($summary['fail'] ?? 0) > 0) {
                    continue;
                }
            }
            $fromVersion = (string) ($report['version'] ?? '');
            $report['carried_forward_from_version'] = $fromVersion;
            $report['version'] = YPERF_VERSION;
            update_option($option, $report, false);
        }

        $execution = get_option('yperf_page_cache_execution_v1', array());
        if (is_array($execution) && (string) ($execution['state'] ?? '') === 'active') {
            $proof = is_array($execution['last_proof'] ?? null) ? $execution['last_proof'] : array();
            if (!empty($proof['ok']) && in_array((string) ($proof['version'] ?? ''), array('1.93.2', '1.93.3'), true)) {
                $fromVersion = (string) ($proof['version'] ?? '');
                $proof['carried_forward_from_version'] = $fromVersion;
                $proof['version'] = YPERF_VERSION;
                $execution['last_proof'] = $proof;
                $execution['carried_forward_from_version'] = $fromVersion;
                $execution['version'] = YPERF_VERSION;
                update_option('yperf_page_cache_execution_v1', $execution, false);
            }
        }

        foreach (array('yperf_page_cache_campaign_state_v1', 'yperf_page_cache_campaign_report_v1') as $option) {
            $value = get_option($option, array());
            if (!is_array($value) || !in_array((string) ($value['version'] ?? ''), array('1.93.2', '1.93.3'), true)) {
                continue;
            }
            $status = (string) ($value['status'] ?? '');
            if (!in_array($status, array('complete', 'proven'), true)) {
                continue;
            }
            $fromVersion = (string) ($value['version'] ?? '');
            $value['carried_forward_from_version'] = $fromVersion;
            $value['version'] = YPERF_VERSION;
            if ($option === 'yperf_page_cache_campaign_report_v1') {
                $value['stability_method'] = 'legacy_peak_to_peak_v1';
                $value['stability_reprove_recommended'] = true;
            }
            update_option($option, $value, false);
        }

        update_option(self::PATCH_EVIDENCE_MARKER_1940, '1', false);
    }


    /** Preserve compatible 1.94.0 evidence across the 1.95.0 Redis-evidence release. */
    private static function carryForwardCompatiblePatchEvidence1950(): void
    {
        if (YPERF_VERSION !== '1.95.0') {
            return;
        }
        if ((string) get_option(self::PATCH_EVIDENCE_MARKER_1950, '') === '1') {
            return;
        }

        foreach (array('yperf_runtime_acceptance_v1', 'yperf_benchmark_report_v1') as $option) {
            $report = get_option($option, array());
            if (!is_array($report) || (string) ($report['version'] ?? '') !== '1.94.0') {
                continue;
            }
            if ($option === 'yperf_runtime_acceptance_v1') {
                $summary = is_array($report['summary'] ?? null) ? $report['summary'] : array();
                if ((int) ($summary['fail'] ?? 0) > 0) {
                    continue;
                }
            }
            $report['carried_forward_from_version'] = '1.94.0';
            $report['version'] = YPERF_VERSION;
            update_option($option, $report, false);
        }

        $execution = get_option('yperf_page_cache_execution_v1', array());
        if (is_array($execution) && (string) ($execution['state'] ?? '') === 'active') {
            $proof = is_array($execution['last_proof'] ?? null) ? $execution['last_proof'] : array();
            if (!empty($proof['ok']) && (string) ($proof['version'] ?? '') === '1.94.0') {
                $proof['carried_forward_from_version'] = '1.94.0';
                $proof['version'] = YPERF_VERSION;
                $execution['last_proof'] = $proof;
                $execution['carried_forward_from_version'] = '1.94.0';
                $execution['version'] = YPERF_VERSION;
                update_option('yperf_page_cache_execution_v1', $execution, false);
            }
        }

        foreach (array('yperf_page_cache_campaign_state_v1', 'yperf_page_cache_campaign_report_v1') as $option) {
            $value = get_option($option, array());
            if (!is_array($value) || (string) ($value['version'] ?? '') !== '1.94.0') {
                continue;
            }
            $status = (string) ($value['status'] ?? '');
            if (!in_array($status, array('complete', 'proven'), true)) {
                continue;
            }
            $value['carried_forward_from_version'] = '1.94.0';
            $value['version'] = YPERF_VERSION;
            update_option($option, $value, false);
        }

        update_option(self::PATCH_EVIDENCE_MARKER_1950, '1', false);
    }


    /** Preserve compatible Page Cache evidence across the 1.96.0 execution/security closeout. */
    private static function carryForwardCompatiblePatchEvidence1960(): void
    {
        if (YPERF_VERSION !== '1.96.0') {
            return;
        }
        if ((string) get_option(self::PATCH_EVIDENCE_MARKER_1960, '') === '1') {
            return;
        }

        $compatible = array('1.94.0', '1.95.0');
        $objectExecution = get_option('yperf_object_cache_execution_v1', array());
        $objectWasActive = is_array($objectExecution) && (string) ($objectExecution['state'] ?? '') === 'active';

        if (!$objectWasActive) {
            foreach (array('yperf_runtime_acceptance_v1', 'yperf_benchmark_report_v1') as $option) {
                $report = get_option($option, array());
                $sourceVersion = is_array($report) ? (string) ($report['version'] ?? '') : '';
                if (!is_array($report) || !in_array($sourceVersion, $compatible, true)) {
                    continue;
                }
                if ($option === 'yperf_runtime_acceptance_v1') {
                    $summary = is_array($report['summary'] ?? null) ? $report['summary'] : array();
                    if ((int) ($summary['fail'] ?? 0) > 0) {
                        continue;
                    }
                }
                $report['carried_forward_from_version'] = $sourceVersion;
                $report['version'] = YPERF_VERSION;
                update_option($option, $report, false);
            }
        }

        $execution = get_option('yperf_page_cache_execution_v1', array());
        if (is_array($execution) && (string) ($execution['state'] ?? '') === 'active') {
            $proof = is_array($execution['last_proof'] ?? null) ? $execution['last_proof'] : array();
            $sourceVersion = (string) ($proof['version'] ?? '');
            if (!empty($proof['ok']) && in_array($sourceVersion, $compatible, true)) {
                $proof['carried_forward_from_version'] = $sourceVersion;
                $proof['version'] = YPERF_VERSION;
                $execution['last_proof'] = $proof;
                $execution['carried_forward_from_version'] = $sourceVersion;
                $execution['version'] = YPERF_VERSION;
                update_option('yperf_page_cache_execution_v1', $execution, false);
            }
        }

        if (!$objectWasActive) {
            foreach (array('yperf_page_cache_campaign_state_v1', 'yperf_page_cache_campaign_report_v1') as $option) {
                $value = get_option($option, array());
                $sourceVersion = is_array($value) ? (string) ($value['version'] ?? '') : '';
                if (!is_array($value) || !in_array($sourceVersion, $compatible, true)) {
                    continue;
                }
                $status = (string) ($value['status'] ?? '');
                if (!in_array($status, array('complete', 'proven'), true)) {
                    continue;
                }
                $value['carried_forward_from_version'] = $sourceVersion;
                $value['version'] = YPERF_VERSION;
                update_option($option, $value, false);
            }
        }

        update_option(self::PATCH_EVIDENCE_MARKER_1960, '1', false);
    }


    /** Preserve compatible execution evidence across the 1.97.0 field/lifecycle closeout. */
    private static function carryForwardCompatiblePatchEvidence1970(): void
    {
        if (YPERF_VERSION !== '1.97.0') {
            return;
        }
        if ((string) get_option(self::PATCH_EVIDENCE_MARKER_1970, '') === '1') {
            return;
        }

        foreach (array('yperf_runtime_acceptance_v1', 'yperf_benchmark_report_v1') as $option) {
            $report = get_option($option, array());
            if (!is_array($report) || (string) ($report['version'] ?? '') !== '1.96.0') {
                continue;
            }
            if ($option === 'yperf_runtime_acceptance_v1') {
                $summary = is_array($report['summary'] ?? null) ? $report['summary'] : array();
                if ((int) ($summary['fail'] ?? 0) > 0) {
                    continue;
                }
            }
            $report['carried_forward_from_version'] = '1.96.0';
            $report['version'] = YPERF_VERSION;
            update_option($option, $report, false);
        }

        $execution = get_option('yperf_page_cache_execution_v1', array());
        if (is_array($execution) && (string) ($execution['state'] ?? '') === 'active') {
            $proof = is_array($execution['last_proof'] ?? null) ? $execution['last_proof'] : array();
            if (!empty($proof['ok']) && (string) ($proof['version'] ?? '') === '1.96.0') {
                $proof['carried_forward_from_version'] = '1.96.0';
                $proof['version'] = YPERF_VERSION;
                $execution['last_proof'] = $proof;
                $execution['carried_forward_from_version'] = '1.96.0';
                $execution['version'] = YPERF_VERSION;
                update_option('yperf_page_cache_execution_v1', $execution, false);
            }
        }

        foreach (array('yperf_page_cache_campaign_state_v1', 'yperf_page_cache_campaign_report_v1') as $option) {
            $value = get_option($option, array());
            if (!is_array($value) || (string) ($value['version'] ?? '') !== '1.96.0') {
                continue;
            }
            $status = (string) ($value['status'] ?? '');
            if (!in_array($status, array('complete', 'proven'), true)) {
                continue;
            }
            $value['carried_forward_from_version'] = '1.96.0';
            $value['version'] = YPERF_VERSION;
            update_option($option, $value, false);
        }

        update_option(self::PATCH_EVIDENCE_MARKER_1970, '1', false);
    }

    /** Preserve compatible 1.97.0 execution evidence across the 1.99.0 market-RC closeout. */
    private static function carryForwardCompatiblePatchEvidence1990(): void
    {
        if (YPERF_VERSION !== '1.99.0') {
            return;
        }
        if ((string) get_option(self::PATCH_EVIDENCE_MARKER_1990, '') === '1') {
            return;
        }

        $objectExecution = get_option('yperf_object_cache_execution_v1', array());
        $objectWasActive = is_array($objectExecution) && (string) ($objectExecution['state'] ?? '') === 'active';

        if (!$objectWasActive) {
            foreach (array('yperf_runtime_acceptance_v1', 'yperf_benchmark_report_v1') as $option) {
                $report = get_option($option, array());
                if (!is_array($report) || (string) ($report['version'] ?? '') !== '1.97.0') {
                    continue;
                }
                if ($option === 'yperf_runtime_acceptance_v1') {
                    $summary = is_array($report['summary'] ?? null) ? $report['summary'] : array();
                    if ((int) ($summary['fail'] ?? 0) > 0) {
                        continue;
                    }
                }
                $report['carried_forward_from_version'] = '1.97.0';
                $report['version'] = YPERF_VERSION;
                update_option($option, $report, false);
            }
        }

        $execution = get_option('yperf_page_cache_execution_v1', array());
        if (is_array($execution) && (string) ($execution['state'] ?? '') === 'active') {
            $proof = is_array($execution['last_proof'] ?? null) ? $execution['last_proof'] : array();
            if (!empty($proof['ok']) && (string) ($proof['version'] ?? '') === '1.97.0') {
                $proof['carried_forward_from_version'] = '1.97.0';
                $proof['version'] = YPERF_VERSION;
                $execution['last_proof'] = $proof;
                $execution['carried_forward_from_version'] = '1.97.0';
                $execution['version'] = YPERF_VERSION;
                update_option('yperf_page_cache_execution_v1', $execution, false);
            }
        }

        if (!$objectWasActive) {
            foreach (array('yperf_page_cache_campaign_state_v1', 'yperf_page_cache_campaign_report_v1') as $option) {
                $value = get_option($option, array());
                if (!is_array($value) || (string) ($value['version'] ?? '') !== '1.97.0') {
                    continue;
                }
                $status = (string) ($value['status'] ?? '');
                if (!in_array($status, array('complete', 'proven'), true)) {
                    continue;
                }
                $value['carried_forward_from_version'] = '1.97.0';
                $value['version'] = YPERF_VERSION;
                update_option($option, $value, false);
            }
        }

        update_option(self::PATCH_EVIDENCE_MARKER_1990, '1', false);
    }

    /** Preserve only boundaries proven compatible from the accepted 1.99.0 Market RC. Runtime Acceptance is intentionally re-run because 2.0.0 changes current-run proof reconciliation. */
    private static function carryForwardCompatibleMajorEvidence2000(): void
    {
        if (YPERF_VERSION !== '2.0.0') {
            return;
        }
        if ((string) get_option(self::MAJOR_EVIDENCE_MARKER_2000, '') === '1') {
            return;
        }

        $objectExecution = get_option('yperf_object_cache_execution_v1', array());
        $objectWasActive = is_array($objectExecution) && (string) ($objectExecution['state'] ?? '') === 'active';

        if (!$objectWasActive) {
            $benchmark = get_option('yperf_benchmark_report_v1', array());
            if (is_array($benchmark) && (string) ($benchmark['version'] ?? '') === '1.99.0') {
                $benchmark['carried_forward_from_version'] = '1.99.0';
                $benchmark['version'] = YPERF_VERSION;
                update_option('yperf_benchmark_report_v1', $benchmark, false);
            }
        }

        $execution = get_option('yperf_page_cache_execution_v1', array());
        if (is_array($execution) && (string) ($execution['state'] ?? '') === 'active') {
            $proof = is_array($execution['last_proof'] ?? null) ? $execution['last_proof'] : array();
            if (!empty($proof['ok']) && (string) ($proof['version'] ?? '') === '1.99.0') {
                $proof['carried_forward_from_version'] = '1.99.0';
                $proof['version'] = YPERF_VERSION;
                $execution['last_proof'] = $proof;
                $execution['carried_forward_from_version'] = '1.99.0';
                $execution['version'] = YPERF_VERSION;
                update_option('yperf_page_cache_execution_v1', $execution, false);
            }
        }

        if (!$objectWasActive) {
            foreach (array('yperf_page_cache_campaign_state_v1', 'yperf_page_cache_campaign_report_v1') as $option) {
                $value = get_option($option, array());
                if (!is_array($value) || (string) ($value['version'] ?? '') !== '1.99.0') {
                    continue;
                }
                $status = (string) ($value['status'] ?? '');
                if (!in_array($status, array('complete', 'proven'), true)) {
                    continue;
                }
                $value['carried_forward_from_version'] = '1.99.0';
                $value['version'] = YPERF_VERSION;
                update_option($option, $value, false);
            }
        }

        update_option(self::MAJOR_EVIDENCE_MARKER_2000, '1', false);
    }

    /**
     * Preserve accepted evidence across the 2.1.0 market-UX closeout.
     * 2.1.0 changes presentation and workflow only; cache/runtime execution
     * boundaries are unchanged. Clean accepted 1.99.0/2.0.0 evidence can
     * therefore remain authoritative without making the user repeat setup.
     */
    private static function carryForwardCompatiblePatchEvidence2100(): void
    {
        if (YPERF_VERSION !== '2.1.0') {
            return;
        }
        if ((string) get_option(self::PATCH_EVIDENCE_MARKER_2100, '') === '1') {
            return;
        }

        $compatible = array('1.99.0', '2.0.0');
        $objectExecution = get_option('yperf_object_cache_execution_v1', array());
        $objectWasActive = is_array($objectExecution) && (string) ($objectExecution['state'] ?? '') === 'active';

        $runtime = get_option('yperf_runtime_acceptance_v1', array());
        if (is_array($runtime) && in_array((string) ($runtime['version'] ?? ''), $compatible, true)) {
            $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
            if ((int) ($summary['fail'] ?? 0) === 0) {
                $from = (string) ($runtime['version'] ?? '');
                $runtime['carried_forward_from_version'] = $from;
                $runtime['compatibility_basis'] = '2.1.0-market-ux-only';
                $runtime['version'] = YPERF_VERSION;
                update_option('yperf_runtime_acceptance_v1', $runtime, false);
            }
        }

        if (!$objectWasActive) {
            $benchmark = get_option('yperf_benchmark_report_v1', array());
            if (is_array($benchmark) && in_array((string) ($benchmark['version'] ?? ''), $compatible, true)) {
                $from = (string) ($benchmark['version'] ?? '');
                $benchmark['carried_forward_from_version'] = $from;
                $benchmark['compatibility_basis'] = '2.1.0-market-ux-only';
                $benchmark['version'] = YPERF_VERSION;
                update_option('yperf_benchmark_report_v1', $benchmark, false);
            }
        }

        $execution = get_option('yperf_page_cache_execution_v1', array());
        if (is_array($execution) && (string) ($execution['state'] ?? '') === 'active') {
            $proof = is_array($execution['last_proof'] ?? null) ? $execution['last_proof'] : array();
            if (!empty($proof['ok']) && in_array((string) ($proof['version'] ?? ''), $compatible, true)) {
                $from = (string) ($proof['version'] ?? '');
                $proof['carried_forward_from_version'] = $from;
                $proof['compatibility_basis'] = '2.1.0-market-ux-only';
                $proof['version'] = YPERF_VERSION;
                $execution['last_proof'] = $proof;
                $execution['carried_forward_from_version'] = $from;
                $execution['version'] = YPERF_VERSION;
                update_option('yperf_page_cache_execution_v1', $execution, false);
            }
        }

        if (!$objectWasActive) {
            foreach (array('yperf_page_cache_campaign_state_v1', 'yperf_page_cache_campaign_report_v1') as $option) {
                $value = get_option($option, array());
                if (!is_array($value) || !in_array((string) ($value['version'] ?? ''), $compatible, true)) {
                    continue;
                }
                $status = (string) ($value['status'] ?? '');
                if (!in_array($status, array('complete', 'proven'), true)) {
                    continue;
                }
                $from = (string) ($value['version'] ?? '');
                $value['carried_forward_from_version'] = $from;
                $value['compatibility_basis'] = '2.1.0-market-ux-only';
                $value['version'] = YPERF_VERSION;
                update_option($option, $value, false);
            }
        }

        update_option(self::PATCH_EVIDENCE_MARKER_2100, '1', false);
    }

    /** @param mixed $value @return mixed */
    private static function normalizeLegacyValue($value)
    {
        if (is_array($value)) {
            $normalized = array();
            foreach ($value as $key => $item) {
                $normalized[$key] = self::normalizeLegacyValue($item);
            }
            return $normalized;
        }
        if (is_string($value)) {
            return str_replace('YPC', 'YPerf', $value);
        }
        return $value;
    }

    /** @return array<string,string> */
    public static function legacyOptionMap(): array
    {
        return self::OPTION_MAP;
    }
}
