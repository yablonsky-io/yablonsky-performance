<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * R3 native-first Origin Budget adoption.
 *
 * If the existing site PHP-FPM pool already provides a bounded worker limit
 * that satisfies the reviewed contract, YPerf can explicitly adopt that
 * existing limit as the authoritative Origin Budget without rewriting the
 * host. Drift is detected from subsequent Host Intelligence evidence.
 */
final class OriginBudgetAdoption
{
    private const OPTION = 'yperf_origin_budget_adoption_v1';

    /** @param array<string,mixed> $contract @param array<string,mixed> $host @param array<string,mixed> $runtime @return array<string,mixed> */
    public static function analyze(array $contract, array $host, array $runtime): array
    {
        $details = is_array($contract['contract'] ?? null) ? $contract['contract'] : array();
        $runtimeSummary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        $runtimeClean = $runtime !== array()
            && (string) ($runtime['version'] ?? '') === YPERF_VERSION
            && (int) ($runtimeSummary['fail'] ?? 0) === 0;
        $contractReady = (string) ($contract['state'] ?? '') === 'operation_contract_ready';
        $pool = sanitize_text_field((string) ($details['site_pool'] ?? ''));
        $maxChildren = max(0, (int) ($details['current_max_children'] ?? 0));
        $reserve = max(0, (int) ($details['control_plane_reserve_workers'] ?? 0));
        $fingerprint = (string) ($details['enforcement_fingerprint'] ?? '');
        $record = self::last();

        $current = $record !== array()
            && (string) ($record['pool'] ?? '') === $pool
            && (int) ($record['max_children'] ?? 0) === $maxChildren
            && (string) ($record['enforcement_fingerprint'] ?? '') === $fingerprint
            && $runtimeClean;
        $drifted = $record !== array() && !$current;

        $state = 'contract_required';
        $label = __('ORIGIN BUDGET CONTRACT REQUIRED', 'yablonsky-performance');
        $reason = __('Build a current Origin Budget R2 contract before adopting the existing PHP-FPM worker limit.', 'yablonsky-performance');
        if ($current) {
            $state = 'active';
            $label = __('ORIGIN BUDGET ACTIVE', 'yablonsky-performance');
            $reason = __('YPerf has adopted the existing PHP-FPM max_children limit as the site Origin Budget. No host file was rewritten because the current native limit already enforces the reviewed boundary.', 'yablonsky-performance');
        } elseif ($drifted) {
            $state = 'drifted';
            $label = __('ORIGIN BUDGET DRIFT · REVIEW', 'yablonsky-performance');
            $reason = __('Current PHP-FPM evidence no longer matches the adopted Origin Budget authority. Refresh Host Intelligence and review the new contract before re-adopting.', 'yablonsky-performance');
        } elseif ($contractReady && $runtimeClean && $pool !== '' && $maxChildren > 0 && $fingerprint !== '') {
            $state = 'adoption_ready';
            $label = __('CURRENT FPM BUDGET READY TO ADOPT', 'yablonsky-performance');
            $reason = __('The existing site pool already has a bounded worker limit and current acceptance is clean. YPerf can adopt that native limit without changing PHP-FPM.', 'yablonsky-performance');
        }

        return array(
            'schema' => 1,
            'generated_at' => time(),
            'state' => $state,
            'label' => $label,
            'reason' => $reason,
            'active' => $state === 'active',
            'eligible_to_adopt' => $state === 'adoption_ready',
            'drifted' => $drifted,
            'contract' => array(
                'pool' => $pool,
                'max_children' => $maxChildren,
                'control_plane_reserve_workers' => $reserve,
                'frontend_worker_envelope' => max(0, $maxChildren - $reserve),
                'enforcement_fingerprint' => preg_match('/^[a-f0-9]{64}$/', $fingerprint) === 1 ? $fingerprint : '',
            ),
            'record' => self::publicRecord($record),
            'safety' => array(
                'host_write_required_for_adoption' => false,
                'php_fpm_reloaded' => false,
                'existing_native_limit_is_executor' => true,
                'drift_requires_reapproval' => true,
            ),
        );
    }

    /** @param array<string,mixed> $contract @param array<string,mixed> $host @param array<string,mixed> $runtime @return array<string,mixed> */
    public static function adopt(array $contract, array $host, array $runtime, string $expectedFingerprint): array
    {
        $analysis = self::analyze($contract, $host, $runtime);
        if (empty($analysis['eligible_to_adopt'])) {
            return array('ok' => false, 'message' => sanitize_text_field((string) ($analysis['reason'] ?? __('Origin Budget adoption is blocked.', 'yablonsky-performance'))));
        }
        $details = is_array($analysis['contract'] ?? null) ? $analysis['contract'] : array();
        $fingerprint = (string) ($details['enforcement_fingerprint'] ?? '');
        if ($expectedFingerprint === '' || $fingerprint === '' || !hash_equals($fingerprint, $expectedFingerprint)) {
            return array('ok' => false, 'message' => __('The Origin Budget contract changed before approval. Refresh Setup and review the current evidence.', 'yablonsky-performance'));
        }
        $record = array(
            'schema' => 1,
            'adopted_at' => time(),
            'yperf_version' => YPERF_VERSION,
            'pool' => sanitize_text_field((string) ($details['pool'] ?? '')),
            'max_children' => max(0, (int) ($details['max_children'] ?? 0)),
            'control_plane_reserve_workers' => max(0, (int) ($details['control_plane_reserve_workers'] ?? 0)),
            'frontend_worker_envelope' => max(0, (int) ($details['frontend_worker_envelope'] ?? 0)),
            'enforcement_fingerprint' => $fingerprint,
            'host_write_performed' => false,
        );
        update_option(self::OPTION, $record, false);
        return array('ok' => true, 'record' => self::publicRecord($record));
    }

    public static function release(): void
    {
        delete_option(self::OPTION);
    }

    /** @return array<string,mixed> */
    public static function last(): array
    {
        $value = get_option(self::OPTION, array());
        return is_array($value) ? $value : array();
    }

    /** @param array<string,mixed> $record @return array<string,mixed> */
    private static function publicRecord(array $record): array
    {
        if ($record === array()) {
            return array();
        }
        return array(
            'adopted_at' => max(0, (int) ($record['adopted_at'] ?? 0)),
            'yperf_version' => sanitize_text_field((string) ($record['yperf_version'] ?? '')),
            'pool' => sanitize_text_field((string) ($record['pool'] ?? '')),
            'max_children' => max(0, (int) ($record['max_children'] ?? 0)),
            'control_plane_reserve_workers' => max(0, (int) ($record['control_plane_reserve_workers'] ?? 0)),
            'frontend_worker_envelope' => max(0, (int) ($record['frontend_worker_envelope'] ?? 0)),
            'enforcement_fingerprint' => preg_match('/^[a-f0-9]{64}$/', (string) ($record['enforcement_fingerprint'] ?? '')) === 1 ? (string) $record['enforcement_fingerprint'] : '',
            'host_write_performed' => !empty($record['host_write_performed']),
        );
    }
}
