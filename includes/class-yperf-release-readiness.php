<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/** Aggregates current evidence into core release gates and optional site execution coverage. */
final class ReleaseReadiness
{
    /**
     * @param array<string,mixed> $runtime
     * @param array<string,mixed> $controlPlane
     * @param array<string,mixed> $pageOps
     * @param array<string,mixed> $objectContract
     * @param array<string,mixed> $originContract
     * @param array<string,mixed> $objectExecution
     * @param array<string,mixed> $originAdoption
     * @param array<string,mixed> $providerExitGate
     * @return array<string,mixed>
     */
    public static function build(array $runtime, array $controlPlane, array $pageOps, array $objectContract, array $originContract, array $objectExecution = array(), array $originAdoption = array(), array $providerExitGate = array(), array $portablePageCache = array(), array $pageCacheCampaign = array()): array
    {
        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        $runtimeCurrent = $runtime !== array() && (string) ($runtime['version'] ?? '') === YPERF_VERSION;
        $runtimeClean = $runtimeCurrent && (int) ($summary['fail'] ?? 0) === 0;
        $controlState = sanitize_key((string) ($controlPlane['state'] ?? 'evidence_required'));
        $originCost = is_array($runtime['origin_cost_summary'] ?? null) ? $runtime['origin_cost_summary'] : array();
        $originCostMeasured = $runtimeClean && (int) ($originCost['measured_routes'] ?? 0) > 0;
        $controlAcceptable = in_array($controlState, array('local_ready_host_optional', 'reserve_ready', 'capacity_attention'), true);
        $pageState = sanitize_key((string) ($pageOps['state'] ?? 'adoption_required'));
        $objectState = !empty($objectExecution['active']) ? 'active' : sanitize_key((string) ($objectContract['state'] ?? 'reservation_required'));
        $originState = !empty($originAdoption['active']) ? 'active' : sanitize_key((string) ($originContract['state'] ?? 'evidence_required'));
        $providerApplicable = !empty($providerExitGate['applicable']);
        $providerSafe = !$providerApplicable || !empty($providerExitGate['safe_to_remove']);

        // Core release gates remain environment-neutral. Optional execution
        // lanes are reported separately so a host without Redis or Nginx purge
        // support does not make the plugin itself unhealthy.
        $coreGates = array(
            'canonical_runtime_identity' => defined('YPERF_BASENAME') && strpos((string) YPERF_BASENAME, 'yablonsky-performance/') === 0,
            'runtime_current_clean' => $runtimeClean,
            'control_plane_not_blocked' => $controlAcceptable,
            'origin_cost_evidence' => $originCostMeasured,
            'plugin_check_manual_gate' => false,
        );
        $knownCore = 4;
        $passedCore = 0;
        foreach (array('canonical_runtime_identity', 'runtime_current_clean', 'control_plane_not_blocked', 'origin_cost_evidence') as $key) {
            if (!empty($coreGates[$key])) {
                $passedCore++;
            }
        }
        $coreProgress = (int) round(($passedCore / max(1, $knownCore)) * 100);

        $pageReviewReady = $pageState === 'operation_review_ready';
        $portablePageActive = !empty($portablePageCache['enabled'])
            && (string) ($portablePageCache['state'] ?? '') === 'active'
            && !empty($portablePageCache['dropin_version_compatible']);
        $pageCampaignProven = (string) ($pageCacheCampaign['status'] ?? '') === 'proven'
            && !empty($pageCacheCampaign['page_cache_active']);
        $pageAuthorityProven = $portablePageActive && $pageCampaignProven;
        $objectActive = $objectState === 'active';
        $originActive = $originState === 'active';

        $lanes = array(
            'page_cache_authority' => array(
                'state' => $pageAuthorityProven ? 'portable_proven' : ($pageReviewReady ? 'native_review_ready' : 'not_proven'),
                'portable_active' => $portablePageActive,
                'portable_artifact_version' => sanitize_text_field((string) ($portablePageCache['dropin_version'] ?? '')),
                'portable_artifact_current' => !empty($portablePageCache['dropin_version_current']),
                'native_optional' => $portablePageActive,
                'closeout_ready' => $pageAuthorityProven || $pageReviewReady,
            ),
            'native_page_cache' => array(
                'state' => $portablePageActive ? 'gated_by_portable_authority' : $pageState,
                'ready_for_write_review' => !$portablePageActive && $pageReviewReady,
                'closeout_ready' => $portablePageActive || $pageReviewReady,
            ),
            'persistent_object_cache' => array(
                'state' => $objectState,
                'ready_for_write_review' => $objectState === 'operation_contract_ready',
                'active' => $objectActive,
                'closeout_ready' => $objectActive,
            ),
            'origin_budget' => array(
                'state' => $originState,
                'ready_for_write_review' => $originState === 'operation_contract_ready',
                'active' => $originActive,
                'closeout_ready' => $originActive,
            ),
        );

        // Site execution coverage is deliberately stricter than the environment-neutral
        // core release gate. It tracks optional execution lanes on this host but
        // never turns Redis/Object Cache or host-level Origin Budget adoption into
        // mandatory market-release requirements.
        $closeoutGates = array(
            'canonical_runtime_identity' => !empty($coreGates['canonical_runtime_identity']),
            'runtime_current_clean' => $runtimeClean,
            'control_plane_not_blocked' => $controlAcceptable,
            'origin_cost_evidence' => $originCostMeasured,
            'page_cache_authority_proven' => $pageAuthorityProven || $pageReviewReady,
            'persistent_object_cache_active' => $objectActive,
            'origin_budget_active' => $originActive,
            'legacy_provider_exit_resolved' => $providerSafe,
        );
        $closeoutPassed = 0;
        foreach ($closeoutGates as $passed) {
            if ($passed) {
                ++$closeoutPassed;
            }
        }
        $closeoutTotal = count($closeoutGates);
        $closeoutProgress = $closeoutTotal > 0 ? (int) round(($closeoutPassed / $closeoutTotal) * 100) : 0;
        $openItems = array();
        foreach (array(
            'canonical_runtime_identity' => __('Canonical runtime identity', 'yablonsky-performance'),
            'runtime_current_clean' => __('Current Runtime Acceptance', 'yablonsky-performance'),
            'control_plane_not_blocked' => __('Control-plane reliability', 'yablonsky-performance'),
            'origin_cost_evidence' => __('Origin Cost evidence', 'yablonsky-performance'),
            'page_cache_authority_proven' => __('Page-cache authority proven', 'yablonsky-performance'),
            'persistent_object_cache_active' => __('Persistent object-cache live execution', 'yablonsky-performance'),
            'origin_budget_active' => __('Origin Budget live adoption', 'yablonsky-performance'),
            'legacy_provider_exit_resolved' => __('Legacy provider exit gate', 'yablonsky-performance'),
        ) as $key => $label) {
            if (empty($closeoutGates[$key])) {
                $openItems[] = $label;
            }
        }

        $state = 'blocked';
        $label = __('RELEASE GATE BLOCKED', 'yablonsky-performance');
        $reason = __('Current Runtime Acceptance, Origin Cost and control-plane reliability must be healthy before YPerf treats this build as a release candidate.', 'yablonsky-performance');
        if ($runtimeClean && $controlAcceptable && $originCostMeasured) {
            $state = 'manual_plugin_check_required';
            $label = __('PLUGIN CHECK CONFIRMATION REQUIRED', 'yablonsky-performance');
            $reason = __('The local runtime/control-plane gates are healthy. Official WordPress Plugin Check remains an external human acceptance gate and is never fabricated from inside YPerf.', 'yablonsky-performance');
        }

        $closeoutState = $closeoutPassed === $closeoutTotal ? 'coverage_complete' : 'optional_expansion_available';
        $closeoutLabel = $closeoutPassed === $closeoutTotal
            ? __('SITE EXECUTION COVERAGE COMPLETE', 'yablonsky-performance')
            : __('OPTIONAL EXECUTION LANES AVAILABLE', 'yablonsky-performance');

        $fingerprintPayload = array(
            'version' => YPERF_VERSION,
            'runtime_generated_at' => (int) ($runtime['generated_at'] ?? 0),
            'runtime_fail' => (int) ($summary['fail'] ?? 0),
            'origin_measured_routes' => (int) ($originCost['measured_routes'] ?? 0),
            'control_state' => $controlState,
            'page_state' => $pageState,
            'portable_page_cache_state' => sanitize_key((string) ($portablePageCache['state'] ?? 'off')),
            'portable_page_cache_version' => sanitize_text_field((string) ($portablePageCache['dropin_version'] ?? '')),
            'page_campaign_status' => sanitize_key((string) ($pageCacheCampaign['status'] ?? 'not_proven')),
            'page_contract' => sanitize_text_field((string) ($pageOps['operation_contract']['fingerprint'] ?? '')),
            'object_state' => $objectState,
            'object_contract' => sanitize_text_field((string) ($objectExecution['contract_fingerprint'] ?? $objectContract['contract']['fingerprint'] ?? '')),
            'origin_state' => $originState,
            'origin_contract' => sanitize_text_field((string) ($originAdoption['fingerprint'] ?? $originContract['contract']['fingerprint'] ?? '')),
            'provider_exit_status' => sanitize_key((string) ($providerExitGate['status'] ?? 'not_applicable')),
        );

        return array(
            'schema' => 5,
            'generated_at' => time(),
            'state' => $state,
            'label' => $label,
            'reason' => $reason,
            'core_progress_percent' => $coreProgress,
            'origin_cost_measured_routes' => (int) ($originCost['measured_routes'] ?? 0),
            'core_gates' => $coreGates,
            'execution_lanes' => $lanes,
            'closeout' => array(
                'state' => $closeoutState,
                'label' => $closeoutLabel,
                'progress_percent' => $closeoutProgress,
                'passed' => $closeoutPassed,
                'total' => $closeoutTotal,
                'gates' => $closeoutGates,
                'open_items' => $openItems,
                'evidence_fingerprint' => hash('sha256', (string) wp_json_encode($fingerprintPayload)),
                'plugin_check_external' => true,
            ),
            'manual_acceptance' => array(
                'plugin_check_required' => true,
                'runtime_acceptance_required' => true,
                'multi_environment_release_acceptance_required' => true,
            ),
        );
    }
}
