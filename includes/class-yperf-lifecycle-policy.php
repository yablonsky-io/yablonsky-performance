<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Plugin lifecycle safety boundary.
 *
 * Deactivation may retire only exact YPerf-owned runtime drop-ins. Ambiguous
 * artifacts fail closed and are never deleted. Settings and evidence are kept
 * so a later reactivation can explain the previous state instead of silently
 * rebuilding it.
 */
final class LifecyclePolicy
{
    private const OPTION = 'yperf_lifecycle_evidence_v1';

    public static function activate(): void
    {
        $record = self::last();
        $now = time();
        $lastDeactivation = max(0, (int) ($record['last_deactivation_at'] ?? 0));
        $safeCleanup = !empty($record['safe_cleanup_complete']);
        $record['schema'] = 2;
        $record['last_activation_at'] = $now;
        $record['last_activation_version'] = YPERF_VERSION;
        if ($lastDeactivation > 0 && $lastDeactivation <= $now && $safeCleanup) {
            $record['last_safe_reactivation_at'] = $now;
            $record['last_safe_reactivation_version'] = YPERF_VERSION;
        }
        $record['reactivation_preserves_state'] = true;
        $record['reactivation_auto_enables_cache'] = false;
        $record['uninstall_policy'] = 'preserve_settings_and_evidence';
        $runtime = ManagedRuntime::ensureInstalled();
        $record['managed_runtime'] = self::cleanResult($runtime);
        update_option(self::OPTION, $record, false);
    }

    public static function deactivate(): void
    {
        $before = DropinOwnership::inspect();
        $pageStatus = PageCacheExecutor::status();
        $objectStatus = ObjectCacheExecutor::status();
        $pageResult = array('ok' => true, 'state' => 'not_owned_or_inactive');
        $objectResult = array('ok' => true, 'state' => 'not_owned_or_inactive');

        if (!empty($pageStatus['enabled']) || !empty($pageStatus['dropin_owned'])) {
            if (!empty($pageStatus['dropin_owned']) && !empty($pageStatus['dropin_hash_matches'])) {
                $pageResult = PageCacheExecutor::disable(false);
            } elseif (!empty($pageStatus['dropin_owned'])) {
                $pageResult = array('ok' => false, 'state' => 'ownership_hash_review_required');
            }
        }

        if (!empty($objectStatus['dropin_owned'])) {
            $objectResult = ObjectCacheExecutor::rollback();
        }

        $runtimeResult = ManagedRuntime::removeOwned();
        $after = DropinOwnership::inspect();
        $advancedAfter = is_array($after['advanced_cache'] ?? null) ? $after['advanced_cache'] : array();
        $objectAfter = is_array($after['object_cache'] ?? null) ? $after['object_cache'] : array();
        $ownedPageRemains = !empty($advancedAfter['present']) && (string) ($advancedAfter['owner'] ?? '') === 'yablonsky-performance';
        $ownedObjectRemains = !empty($objectAfter['present']) && (string) ($objectAfter['owner'] ?? '') === 'yablonsky-performance';
        $safe = !$ownedPageRemains && !$ownedObjectRemains && !empty($pageResult['ok']) && !empty($objectResult['ok']) && !empty($runtimeResult['ok']);

        update_option(self::OPTION, array(
            'schema' => 2,
            'last_deactivation_at' => time(),
            'last_deactivation_version' => YPERF_VERSION,
            'safe_cleanup_complete' => $safe,
            'page_cache_cleanup' => self::cleanResult($pageResult),
            'object_cache_cleanup' => self::cleanResult($objectResult),
            'managed_runtime_cleanup' => self::cleanResult($runtimeResult),
            'before' => self::dropinSummary($before),
            'after' => self::dropinSummary($after),
            'settings_preserved' => true,
            'evidence_preserved' => true,
            'uninstall_policy' => 'preserve_settings_and_evidence',
        ), false);
    }

    /** @return array<string,mixed> */
    public static function report(): array
    {
        $record = self::last();
        $lastDeactivationAt = max(0, (int) ($record['last_deactivation_at'] ?? 0));
        $lastActivationAt = max(0, (int) ($record['last_activation_at'] ?? 0));
        $lastDeactivationVersion = sanitize_text_field((string) ($record['last_deactivation_version'] ?? ''));
        $lastActivationVersion = sanitize_text_field((string) ($record['last_activation_version'] ?? ''));
        $currentCycleComplete = !empty($record['safe_cleanup_complete'])
            && $lastDeactivationAt > 0
            && $lastActivationAt > $lastDeactivationAt
            && $lastDeactivationVersion === YPERF_VERSION
            && $lastActivationVersion === YPERF_VERSION;
        return array(
            'schema' => 2,
            'version' => YPERF_VERSION,
            'deactivation_observed' => $lastDeactivationAt > 0,
            'last_deactivation_at' => $lastDeactivationAt,
            'last_deactivation_version' => $lastDeactivationVersion,
            'safe_cleanup_complete' => !empty($record['safe_cleanup_complete']),
            'last_activation_at' => $lastActivationAt,
            'last_activation_version' => $lastActivationVersion,
            'last_safe_reactivation_at' => max(0, (int) ($record['last_safe_reactivation_at'] ?? 0)),
            'last_safe_reactivation_version' => sanitize_text_field((string) ($record['last_safe_reactivation_version'] ?? '')),
            'current_cycle_complete' => $currentCycleComplete,
            'settings_preserved' => array_key_exists('settings_preserved', $record) ? !empty($record['settings_preserved']) : true,
            'evidence_preserved' => array_key_exists('evidence_preserved', $record) ? !empty($record['evidence_preserved']) : true,
            'reactivation_auto_enables_cache' => false,
            'uninstall_policy' => 'preserve_settings_and_evidence',
            'page_cache_cleanup' => is_array($record['page_cache_cleanup'] ?? null) ? $record['page_cache_cleanup'] : array(),
            'object_cache_cleanup' => is_array($record['object_cache_cleanup'] ?? null) ? $record['object_cache_cleanup'] : array(),
            'managed_runtime_cleanup' => is_array($record['managed_runtime_cleanup'] ?? null) ? $record['managed_runtime_cleanup'] : array(),
        );
    }

    /** @return array<string,mixed> */
    private static function last(): array
    {
        $record = get_option(self::OPTION, array());
        return is_array($record) ? $record : array();
    }

    /** @param array<string,mixed> $result @return array<string,mixed> */
    private static function cleanResult(array $result): array
    {
        return array(
            'ok' => !empty($result['ok']),
            'state' => sanitize_key((string) ($result['state'] ?? 'unknown')),
            'message' => sanitize_text_field((string) ($result['message'] ?? '')),
        );
    }

    /** @param array<string,mixed> $ownership @return array<string,mixed> */
    private static function dropinSummary(array $ownership): array
    {
        $summary = array();
        foreach (array('advanced_cache', 'object_cache') as $key) {
            $row = is_array($ownership[$key] ?? null) ? $ownership[$key] : array();
            $summary[$key] = array(
                'present' => !empty($row['present']),
                'owner' => sanitize_key((string) ($row['owner'] ?? 'none')),
                'artifact_version' => sanitize_text_field((string) ($row['artifact_version'] ?? '')),
                'sha256' => preg_match('/^[a-f0-9]{64}$/', (string) ($row['sha256'] ?? '')) === 1 ? (string) $row['sha256'] : '',
            );
        }
        return $summary;
    }
}
