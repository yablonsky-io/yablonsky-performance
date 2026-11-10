<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * R3 operation-contract analysis for an adopted native Nginx FastCGI cache.
 *
 * This class is intentionally read-only. It proves cache-zone ownership and
 * describes the next safe operation boundary. It never edits Nginx, deletes
 * cache files, restarts services or changes a provider configuration.
 */
final class NativePageCacheOperations
{
    /** @param array<string,mixed> $host @param array<string,mixed> $adoption @param array<string,mixed> $runtime @return array<string,mixed> */
    public static function analyze(array $host, array $adoption, array $runtime): array
    {
        $site = is_array($host['site_runtime'] ?? null) ? $host['site_runtime'] : array();
        $hostSchema = (int) ($host['schema'] ?? 0);
        $zone = sanitize_text_field((string) ($site['fastcgi_cache_zone'] ?? $adoption['cache_zone'] ?? ''));
        $ownerBlocks = max(0, (int) ($site['cache_zone_owner_blocks'] ?? 0));
        $ownerSignature = sanitize_text_field((string) ($site['cache_zone_owner_signature'] ?? ''));
        $targetOwner = !empty($site['cache_zone_target_owner']);
        $zoneDefinitions = max(0, (int) ($site['cache_zone_definition_count'] ?? 0));
        $pathFingerprint = sanitize_text_field((string) ($site['cache_zone_path_hash'] ?? ''));
        $pathLabel = sanitize_text_field((string) ($site['cache_zone_path_basename'] ?? ''));
        $cacheKey = sanitize_text_field((string) ($site['fastcgi_cache_key_expression'] ?? ''));
        $cacheKeyHostScoped = !empty($site['fastcgi_cache_key_host_scoped']);
        $purgeDirectives = max(0, (int) ($site['fastcgi_cache_purge_directives'] ?? 0));

        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        $runtimeCurrent = $runtime !== array() && (string) ($runtime['version'] ?? '') === YPERF_VERSION;
        $runtimeClean = $runtimeCurrent && (int) ($summary['fail'] ?? 0) === 0;
        $adopted = !empty($adoption['adopted']);
        $targetMatches = !empty($adoption['target_matches']);
        $freshProof = !empty($adoption['proof_fresh']);

        $ownership = 'unknown';
        if ($hostSchema >= 4 && $zone !== '') {
            if ($ownerBlocks === 1) {
                $ownership = 'exclusive_server_block';
            } elseif ($ownerBlocks > 1) {
                $ownership = 'shared_server_blocks';
            }
        }

        $gates = array(
            'adoption_current' => $adopted && $targetMatches && $freshProof,
            'runtime_clean' => $runtimeClean,
            'zone_identified' => $zone !== '',
            'zone_ownership_proven' => $ownership !== 'unknown',
            'zone_exclusive' => $ownership === 'exclusive_server_block',
            'purge_capability_visible' => $purgeDirectives > 0,
        );
        $readyCount = 0;
        foreach ($gates as $passed) {
            if ($passed) {
                $readyCount++;
            }
        }
        $progress = (int) round(($readyCount / max(1, count($gates))) * 100);

        $state = 'adoption_required';
        $label = __('ADOPTION REQUIRED', 'yablonsky-performance');
        $reason = __('Adopt a fresh, proven native page-cache executor before preparing host operations.', 'yablonsky-performance');

        if ($adopted && !$runtimeClean) {
            $state = 'runtime_gate_required';
            $label = __('RUNTIME GATE REQUIRED', 'yablonsky-performance');
            $reason = __('The native executor is adopted, but current-version Runtime Acceptance must be clean before an operation plan can advance.', 'yablonsky-performance');
        } elseif ($adopted && $hostSchema < 4) {
            $state = 'ownership_probe_required';
            $label = __('REFRESH HOST PROOF', 'yablonsky-performance');
            $reason = __('Refresh Host Intelligence with Inspector 1.5.0+ so YPerf can prove whether this cache zone is exclusive or shared.', 'yablonsky-performance');
        } elseif ($adopted && $ownership === 'shared_server_blocks') {
            $state = 'shared_zone_blocked';
            $label = __('SHARED ZONE · BLOCKED', 'yablonsky-performance');
            $reason = __('The adopted cache zone is used by more than one Nginx server block. YPerf will not prepare a zone-wide purge path that could affect another site.', 'yablonsky-performance');
        } elseif ($adopted && $ownership === 'unknown') {
            $state = 'ownership_unproven';
            $label = __('OWNERSHIP UNPROVEN', 'yablonsky-performance');
            $reason = __('The cache zone is visible, but its server-block ownership is not proven yet.', 'yablonsky-performance');
        } elseif ($adopted && $ownership === 'exclusive_server_block' && $purgeDirectives <= 0) {
            $state = 'exclusive_purge_unproven';
            $label = __('EXCLUSIVE · PURGE UNPROVEN', 'yablonsky-performance');
            $reason = __('Cache-zone ownership is exclusive, but no native purge capability is visible. Prewarm is safe; native purge still needs a reviewed host path.', 'yablonsky-performance');
        } elseif ($adopted && $ownership === 'exclusive_server_block' && $purgeDirectives > 0 && $runtimeClean) {
            $state = 'operation_review_ready';
            $label = __('OPERATIONS REVIEW READY', 'yablonsky-performance');
            $reason = __('Execution, current runtime, exclusive cache-zone ownership and a native purge capability are all visible. The next step is a reviewed Host Helper write contract with exact diff, read-back and rollback.', 'yablonsky-performance');
        } elseif ($adopted && $ownership === 'exclusive_server_block') {
            $state = 'exclusive_read_only';
            $label = __('EXCLUSIVE · READ-ONLY', 'yablonsky-performance');
            $reason = __('The zone is exclusive to this server block. YPerf can operate prewarm now while host writes remain locked.', 'yablonsky-performance');
        }

        $purgeState = 'blocked';
        if ($state === 'shared_zone_blocked') {
            $purgeState = 'blocked_shared_zone';
        } elseif ($ownership === 'exclusive_server_block' && $purgeDirectives > 0 && $runtimeClean && $adopted) {
            $purgeState = 'capability_proven_write_path_locked';
        } elseif ($ownership === 'exclusive_server_block' && $adopted) {
            $purgeState = 'exclusive_zone_purge_capability_unproven';
        } elseif ($adopted) {
            $purgeState = 'ownership_proof_required';
        }

        $contractPayload = array(
            'target_host' => sanitize_text_field((string) ($adoption['target_host'] ?? '')),
            'cache_zone' => $zone,
            'zone_ownership' => $ownership,
            'zone_owner_signature' => $ownerSignature,
            'cache_path_fingerprint' => $pathFingerprint,
            'cache_key_host_scoped' => $cacheKeyHostScoped,
            'purge_directives' => $purgeDirectives,
            'runtime_version' => sanitize_text_field((string) ($runtime['version'] ?? '')),
        );
        $contractFingerprint = hash('sha256', (string) wp_json_encode($contractPayload));
        $operationContract = array(
            'schema' => 1,
            'fingerprint' => $contractFingerprint,
            'ready_for_review' => $state === 'operation_review_ready',
            'purge_scope' => $ownership === 'exclusive_server_block' ? 'exclusive_cache_zone' : 'blocked',
            'prewarm_scope' => $adopted && $runtimeClean ? 'same_origin_bounded_urls' : 'blocked',
            'requires' => array(
                'explicit_operator_approval',
                'exact_target_read_back',
                'runtime_acceptance_after_operation',
                'rollback_or_abort_on_failure',
            ),
            'host_write_executed' => false,
        );

        return array(
            'schema' => 3,
            'generated_at' => time(),
            'state' => $state,
            'label' => $label,
            'reason' => $reason,
            'progress_percent' => $progress,
            'gates' => $gates,
            'host_schema' => $hostSchema,
            'target_host' => sanitize_text_field((string) ($adoption['target_host'] ?? '')),
            'cache_zone' => $zone,
            'zone_ownership' => $ownership,
            'zone_owner_blocks' => $ownerBlocks,
            'zone_owner_signature' => $ownerSignature,
            'target_is_zone_owner' => $targetOwner,
            'zone_definition_count' => $zoneDefinitions,
            'cache_path_fingerprint' => $pathFingerprint,
            'cache_path_label' => $pathLabel,
            'cache_key_expression' => $cacheKey,
            'cache_key_host_scoped' => $cacheKeyHostScoped,
            'purge_directives' => $purgeDirectives,
            'operation_contract' => $operationContract,
            'operations' => array(
                'prewarm' => $adopted && $runtimeClean ? 'available' : 'blocked',
                'native_purge' => $purgeState,
                'host_write_contract' => $state === 'operation_review_ready' ? 'ready_for_explicit_review' : 'blocked',
                'configuration_writes' => 'locked',
                'service_restart' => 'locked',
            ),
            'host_write_requirements' => array(
                'explicit_operator_approval',
                'target_host_match',
                'exclusive_cache_zone_or_site_scoped_purge',
                'exact_before_state',
                'exact_intended_diff',
                'nginx_syntax_test_when_config_changes',
                'operation_read_back',
                'runtime_acceptance_after_apply',
                'rollback_on_failure',
            ),
            'safety' => array(
                'read_only_analysis' => true,
                'nginx_changed' => false,
                'cache_files_deleted' => false,
                'provider_changed' => false,
                'server_restart' => false,
                'global_zone_purge_allowed' => false,
            ),
        );
    }
}
