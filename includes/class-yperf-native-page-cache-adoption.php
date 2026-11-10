<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Local authority record for an already-existing native Nginx FastCGI page cache.
 *
 * R1 never edits Nginx, never restarts services and never changes provider state.
 * It only records that fresh site-specific execution proof has been reviewed and
 * accepted as the authoritative page-cache executor for this WordPress site.
 */
final class NativePageCacheAdoption
{
    private const OPTION = 'yperf_native_page_cache_adoption_v1';
    private const MAX_PROOF_AGE = DAY_IN_SECONDS;

    /** @param array<string,mixed> $host @param array<string,mixed> $proof @param array<string,mixed> $runtime @return array<string,mixed> */
    public static function analyze(array $host, array $proof, array $runtime): array
    {
        $site = is_array($host['site_runtime'] ?? null) ? $host['site_runtime'] : array();
        $page = is_array($proof['page_cache'] ?? null) ? $proof['page_cache'] : array();
        $record = self::last();

        $homeHost = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        $targetHost = strtolower(trim((string) ($site['target_host'] ?? $page['target_host'] ?? '')));
        $targetMatches = $homeHost !== '' && $targetHost !== '' && $homeHost === $targetHost;

        $proofState = sanitize_key((string) ($page['state'] ?? 'not_proven'));
        $executionProven = in_array($proofState, array('miss_then_hit_confirmed', 'hit_confirmed'), true);
        $cacheHeaderExposed = !empty($page['cache_status_header_exposed']);
        $secondHttp = (int) ($page['probe2_http_code'] ?? 0);

        $generatedAt = max(0, (int) ($host['generated_at'] ?? 0));
        $age = $generatedAt > 0 ? max(0, time() - $generatedAt) : 0;
        $fresh = $generatedAt > 0 && $age <= self::MAX_PROOF_AGE;

        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        $runtimeCurrent = $runtime !== array() && (string) ($runtime['version'] ?? '') === YPERF_VERSION;
        $runtimeClean = $runtimeCurrent && (int) ($summary['fail'] ?? 0) === 0;

        $fingerprint = self::fingerprint($host, $page);
        $recordFingerprint = (string) ($record['proof_fingerprint'] ?? '');
        $recordMatches = $record !== array() && $fingerprint !== '' && $recordFingerprint !== '' && hash_equals($recordFingerprint, $fingerprint);
        $recordTargetMatches = $record !== array() && strtolower((string) ($record['target_host'] ?? '')) === $homeHost;

        $portablePageCacheActive = PageCacheExecutor::enabled();
        $eligible = $executionProven
            && $cacheHeaderExposed
            && $secondHttp === 200
            && $targetMatches
            && $fresh
            && $runtimeClean
            && !$portablePageCacheActive;

        $state = 'proof_required';
        $label = __('PROOF REQUIRED', 'yablonsky-performance');
        $reason = __('Import a fresh site-specific Host Intelligence snapshot that proves a native Nginx cache HIT before adoption.', 'yablonsky-performance');

        if ($portablePageCacheActive) {
            $state = 'portable_executor_active';
            $label = __('YPERF PAGE CACHE ACTIVE', 'yablonsky-performance');
            $reason = __('The YPerf-owned portable Page Cache already owns page-cache execution. Native Nginx adoption stays locked so the site never runs two competing page-cache authorities.', 'yablonsky-performance');
        } elseif ($record !== array()) {
            if ($recordMatches && $recordTargetMatches && $fresh && $runtimeClean && $executionProven) {
                $state = 'adopted';
                $label = __('ADOPTED · READ-ONLY', 'yablonsky-performance');
                $reason = __('YPerf recognizes the proven Nginx FastCGI cache as the authoritative page-cache executor. No server configuration was changed.', 'yablonsky-performance');
            } else {
                $state = 'reproof_required';
                $label = __('RE-PROOF REQUIRED', 'yablonsky-performance');
                $reason = __('A previous adoption record exists, but current proof no longer matches or is stale. Re-prove execution before treating the adoption as current.', 'yablonsky-performance');
            }
        } elseif ($eligible) {
            $state = 'ready';
            $label = __('READY TO ADOPT', 'yablonsky-performance');
            $reason = __('Fresh HIT evidence, target-host match and current clean Runtime Acceptance make this existing Nginx cache eligible for local adoption recognition.', 'yablonsky-performance');
        } elseif ($executionProven && !$fresh) {
            $state = 'stale_proof';
            $label = __('REFRESH PROOF', 'yablonsky-performance');
            $reason = __('Native cache execution was proven previously, but the Host Intelligence snapshot is older than the 24-hour adoption window.', 'yablonsky-performance');
        } elseif ($executionProven && !$targetMatches) {
            $state = 'target_mismatch';
            $label = __('TARGET MISMATCH', 'yablonsky-performance');
            $reason = __('The native proof belongs to a different hostname, so YPerf will not adopt it for this site.', 'yablonsky-performance');
        } elseif ($executionProven && !$runtimeClean) {
            $state = 'runtime_gate_required';
            $label = __('RUNTIME GATE REQUIRED', 'yablonsky-performance');
            $reason = __('Native HIT proof exists, but adoption stays blocked until current-version Runtime Acceptance is clean.', 'yablonsky-performance');
        } elseif ($proofState === 'bypass_confirmed') {
            $state = 'bypass_proven';
            $label = __('BYPASS PROVEN', 'yablonsky-performance');
            $reason = __('The current origin probe proves BYPASS rather than HIT, so there is nothing safe to adopt as an active page-cache executor yet.', 'yablonsky-performance');
        }

        $purgeDirectives = (int) ($site['fastcgi_cache_purge_directives'] ?? 0);
        $cacheZone = sanitize_text_field((string) ($site['fastcgi_cache_zone'] ?? ''));

        return array(
            'schema' => 1,
            'generated_at' => time(),
            'state' => $state,
            'label' => $label,
            'reason' => $reason,
            'eligible' => $eligible,
            'adopted' => $state === 'adopted',
            'target_host' => sanitize_text_field($targetHost),
            'home_host' => sanitize_text_field($homeHost),
            'target_matches' => $targetMatches,
            'proof_state' => $proofState,
            'proof_fresh' => $fresh,
            'proof_age_seconds' => $age,
            'proof_fingerprint' => $fingerprint,
            'runtime_current' => $runtimeCurrent,
            'runtime_clean' => $runtimeClean,
            'cache_status_header_exposed' => $cacheHeaderExposed,
            'cache_zone' => $cacheZone,
            'record' => self::publicRecord($record),
            'operations' => array(
                'prewarm' => ($state === 'adopted' || $eligible) ? 'available' : 'blocked',
                'native_purge' => $purgeDirectives > 0 ? 'host_capability_visible_write_path_not_connected' : 'not_proven',
                'configuration_writes' => 'locked',
                'service_restart' => 'locked',
                'rollback' => 'release_local_adoption_record_only',
            ),
            'safety' => array(
                'nginx_changed' => false,
                'provider_changed' => false,
                'server_restart' => false,
                'local_option_only' => true,
                'proof_max_age_seconds' => self::MAX_PROOF_AGE,
            ),
        );
    }

    /** @param array<string,mixed> $host @param array<string,mixed> $proof @param array<string,mixed> $runtime @return array<string,mixed> */
    public static function adopt(array $host, array $proof, array $runtime): array
    {
        $analysis = self::analyze($host, $proof, $runtime);
        if (empty($analysis['eligible'])) {
            return array(
                'ok' => false,
                'message' => sanitize_text_field((string) ($analysis['reason'] ?? __('Native page cache is not ready for adoption.', 'yablonsky-performance'))),
                'analysis' => $analysis,
            );
        }

        $record = array(
            'schema' => 1,
            'adopted_at' => time(),
            'target_host' => sanitize_text_field((string) ($analysis['target_host'] ?? '')),
            'executor' => 'nginx_fastcgi_cache',
            'proof_state' => sanitize_key((string) ($analysis['proof_state'] ?? '')),
            'proof_fingerprint' => sanitize_text_field((string) ($analysis['proof_fingerprint'] ?? '')),
            'cache_zone' => sanitize_text_field((string) ($analysis['cache_zone'] ?? '')),
            'yperf_version' => YPERF_VERSION,
            'server_configuration_changed' => false,
        );
        update_option(self::OPTION, $record, false);

        return array(
            'ok' => true,
            'message' => __('Existing Nginx FastCGI cache adopted as the local authoritative page-cache executor. No host configuration was changed.', 'yablonsky-performance'),
            'record' => self::publicRecord($record),
        );
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

    /** @param array<string,mixed> $host @param array<string,mixed> $page */
    private static function fingerprint(array $host, array $page): string
    {
        $site = is_array($host['site_runtime'] ?? null) ? $host['site_runtime'] : array();
        $payload = array(
            'target_host' => strtolower((string) ($site['target_host'] ?? $page['target_host'] ?? '')),
            'proof_state' => sanitize_key((string) ($page['state'] ?? '')),
            'cache_header' => sanitize_text_field((string) ($page['cache_header'] ?? '')),
            'probe2_value' => strtoupper(sanitize_text_field((string) ($page['probe2_cache_value'] ?? ''))),
            'matching_server_blocks' => (int) ($page['matching_server_blocks'] ?? 0),
            'fastcgi_cache_directives' => (int) ($page['fastcgi_cache_directives'] ?? 0),
            'cache_zone' => sanitize_text_field((string) ($site['fastcgi_cache_zone'] ?? '')),
            'fastcgi_pass_target' => sanitize_text_field((string) ($site['fastcgi_pass_target'] ?? '')),
        );
        return hash('sha256', (string) wp_json_encode($payload));
    }

    /** @param array<string,mixed> $record @return array<string,mixed> */
    private static function publicRecord(array $record): array
    {
        if ($record === array()) {
            return array();
        }
        return array(
            'schema' => (int) ($record['schema'] ?? 1),
            'adopted_at' => (int) ($record['adopted_at'] ?? 0),
            'target_host' => sanitize_text_field((string) ($record['target_host'] ?? '')),
            'executor' => sanitize_key((string) ($record['executor'] ?? '')),
            'proof_state' => sanitize_key((string) ($record['proof_state'] ?? '')),
            'cache_zone' => sanitize_text_field((string) ($record['cache_zone'] ?? '')),
            'yperf_version' => sanitize_text_field((string) ($record['yperf_version'] ?? '')),
            'server_configuration_changed' => !empty($record['server_configuration_changed']),
        );
    }
}
