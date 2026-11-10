<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Bounded Cloudflare performance adapter.
 *
 * Authority boundary:
 * - YPerf may inspect Cloudflare performance delivery and, after explicit
 *   administrator adoption, manage only the allowlisted performance settings
 *   and cache purge bridge implemented here.
 * - YPerf never reads or writes AI crawler, robots, Bot Management, WAF, DNS,
 *   security policy or SEO crawler-signalling configuration. Those semantics
 *   belong to YSEO / security authorities.
 */
final class CloudflareEdge
{
    private const AUDIT_OPTION = 'yperf_cloudflare_edge_audit_v1';
    private const ADOPTION_OPTION = 'yperf_cloudflare_edge_adoption_v1';
    private const LAST_OPERATION_OPTION = 'yperf_cloudflare_edge_operation_v1';

    /** @return array<string,mixed> */
    public static function status(): array
    {
        return array(
            'credentials' => CloudflareCredentials::status(),
            'audit' => self::lastAudit(),
            'adoption' => self::adoption(),
            'operation' => self::lastOperation(),
            'contract' => self::authorityContract(),
        );
    }

    /** @return array<string,mixed> */
    public static function inspect(): array
    {
        $credentials = CloudflareCredentials::status();
        if (empty($credentials['configured'])) {
            return self::saveAudit(array(
                'state' => 'not_configured',
                'ready' => false,
                'message' => __('Connect a least-privilege Cloudflare API token before running the edge audit.', 'yablonsky-performance'),
            ));
        }
        $zone = CloudflareClient::discoverZone();
        if (is_wp_error($zone)) {
            return self::saveAudit(array(
                'state' => 'zone_unresolved',
                'ready' => false,
                'message' => sanitize_text_field($zone->get_error_message()),
            ));
        }

        $settings = array();
        $errors = array();
        foreach (array('rocket_loader', 'http3', 'early_hints', 'speed_brain', 'automatic_platform_optimization') as $settingId) {
            $value = CloudflareClient::getZoneSetting((string) $zone['id'], $settingId);
            if (is_wp_error($value)) {
                $errors[$settingId] = sanitize_text_field($value->get_error_message());
                continue;
            }
            $settings[$settingId] = self::cleanSetting($value);
        }

        $probe = self::probeEdge();
        $recommended = self::recommendedProfile();
        $drift = array();
        foreach ($recommended as $settingId => $wanted) {
            $current = (string) ($settings[$settingId]['value'] ?? 'unknown');
            if ($current !== 'unknown' && $current !== $wanted) {
                $drift[$settingId] = array('current' => $current, 'recommended' => $wanted);
            }
        }

        return self::saveAudit(array(
            'state' => $errors === array() ? 'inspected' : 'inspected_partial',
            'ready' => isset($settings['rocket_loader'], $settings['http3']),
            'zone' => array(
                'id' => sanitize_text_field((string) ($zone['id'] ?? '')),
                'name' => sanitize_text_field((string) ($zone['name'] ?? '')),
                'source' => sanitize_key((string) ($zone['source'] ?? '')),
            ),
            'settings' => $settings,
            'errors' => $errors,
            'probe' => $probe,
            'recommended' => $recommended,
            'drift' => $drift,
            'message' => $errors === array()
                ? __('Cloudflare performance delivery was inspected successfully.', 'yablonsky-performance')
                : __('Cloudflare connection works, but one or more performance settings could not be inspected with the current token or plan.', 'yablonsky-performance'),
        ));
    }

    /** @return array<string,mixed> */
    public static function applyRecommended(): array
    {
        $audit = self::inspect();
        if (empty($audit['ready']) || empty($audit['zone']['id'])) {
            return self::saveOperation(array(
                'type' => 'apply',
                'state' => 'blocked',
                'ok' => false,
                'message' => sanitize_text_field((string) ($audit['message'] ?? __('Cloudflare performance profile is not ready to apply.', 'yablonsky-performance'))),
            ));
        }

        $zoneId = (string) $audit['zone']['id'];
        $recommended = self::recommendedProfile();
        $snapshot = array();
        $changed = array();
        foreach ($recommended as $settingId => $wanted) {
            $current = (string) ($audit['settings'][$settingId]['value'] ?? 'unknown');
            if (!in_array($current, array('on', 'off'), true)) {
                return self::saveOperation(array(
                    'type' => 'apply',
                    'state' => 'blocked',
                    'ok' => false,
                    'message' => sprintf(
                        /* translators: %s: Cloudflare setting identifier */
                        __('Cloudflare setting %s could not be read safely, so YPerf made no provider changes.', 'yablonsky-performance'),
                        $settingId
                    ),
                ));
            }
            $snapshot[$settingId] = $current;
            if ($current === $wanted) {
                continue;
            }
            $write = CloudflareClient::setZoneSetting($zoneId, $settingId, $wanted);
            if (is_wp_error($write)) {
                $rollback = self::restoreChangedSettings($zoneId, $snapshot, $changed);
                return self::saveOperation(array(
                    'type' => 'apply',
                    'state' => empty($rollback['failed']) ? 'rolled_back' : 'rollback_attention',
                    'ok' => false,
                    'message' => empty($rollback['failed'])
                        ? sanitize_text_field($write->get_error_message())
                        : __('Cloudflare apply failed and one or more rollback writes also need review. YPerf stopped without making further provider changes.', 'yablonsky-performance'),
                    'changed' => $changed,
                    'rollback' => $rollback,
                ));
            }
            $changed[$settingId] = $wanted;
        }

        $proof = self::inspect();
        foreach ($recommended as $settingId => $wanted) {
            if ((string) ($proof['settings'][$settingId]['value'] ?? '') !== $wanted) {
                $rollback = self::restoreChangedSettings($zoneId, $snapshot, $changed);
                self::inspect();
                return self::saveOperation(array(
                    'type' => 'apply',
                    'state' => empty($rollback['failed']) ? 'readback_failed_rolled_back' : 'readback_failed_rollback_attention',
                    'ok' => false,
                    'message' => empty($rollback['failed'])
                        ? __('Cloudflare read-back did not prove the requested performance profile. YPerf restored every setting it had changed.', 'yablonsky-performance')
                        : __('Cloudflare read-back failed and one or more rollback writes also need review. YPerf stopped without making further provider changes.', 'yablonsky-performance'),
                    'changed' => $changed,
                    'rollback' => $rollback,
                ));
            }
        }

        $adoption = array(
            'schema' => 1,
            'active' => true,
            'adopted_at' => time(),
            'version' => YPERF_VERSION,
            'zone' => array(
                'id' => sanitize_text_field((string) ($proof['zone']['id'] ?? $zoneId)),
                'name' => sanitize_text_field((string) ($proof['zone']['name'] ?? '')),
            ),
            'snapshot' => $snapshot,
            'applied' => $recommended,
            'changed' => $changed,
            'purge_sync' => true,
            'authority' => 'performance_delivery_only',
        );
        update_option(self::ADOPTION_OPTION, $adoption, false);

        return self::saveOperation(array(
            'type' => 'apply',
            'state' => 'accepted',
            'ok' => true,
            'message' => $changed === array()
                ? __('Cloudflare already matched the YPerf performance profile. Read-back proof is clean.', 'yablonsky-performance')
                : __('Cloudflare performance profile was applied and proved by read-back.', 'yablonsky-performance'),
            'changed' => $changed,
        ));
    }

    /** @return array<string,mixed> */
    public static function rollback(): array
    {
        $adoption = self::adoption();
        if (empty($adoption['active']) || empty($adoption['zone']['id']) || !is_array($adoption['snapshot'] ?? null)) {
            return self::saveOperation(array(
                'type' => 'rollback',
                'state' => 'nothing_to_restore',
                'ok' => true,
                'message' => __('No active YPerf Cloudflare performance adoption needs rollback.', 'yablonsky-performance'),
            ));
        }
        $zoneId = sanitize_text_field((string) $adoption['zone']['id']);
        $snapshot = (array) $adoption['snapshot'];
        $applied = is_array($adoption['applied'] ?? null) ? $adoption['applied'] : array();
        $restored = array();
        $skipped = array();
        $failed = array();

        foreach ($snapshot as $settingId => $previous) {
            $settingId = sanitize_key((string) $settingId);
            $previous = (string) $previous;
            $expectedCurrent = (string) ($applied[$settingId] ?? '');
            if (!in_array($previous, array('on', 'off'), true) || !in_array($expectedCurrent, array('on', 'off'), true)) {
                continue;
            }
            $current = CloudflareClient::getZoneSetting($zoneId, $settingId);
            if (is_wp_error($current)) {
                $failed[$settingId] = sanitize_text_field($current->get_error_message());
                continue;
            }
            $currentValue = (string) ($current['value'] ?? '');
            if ($currentValue !== $expectedCurrent) {
                $skipped[$settingId] = array('current' => $currentValue, 'expected_yperf' => $expectedCurrent, 'previous' => $previous);
                continue;
            }
            if ($currentValue === $previous) {
                $restored[$settingId] = $previous;
                continue;
            }
            $write = CloudflareClient::setZoneSetting($zoneId, $settingId, $previous);
            if (is_wp_error($write)) {
                $failed[$settingId] = sanitize_text_field($write->get_error_message());
            } else {
                $restored[$settingId] = $previous;
            }
        }

        if ($failed === array() && $skipped === array()) {
            delete_option(self::ADOPTION_OPTION);
        } else {
            $adoption['active'] = false;
            $adoption['rollback_attention'] = array('failed' => $failed, 'skipped_external_drift' => $skipped);
            update_option(self::ADOPTION_OPTION, $adoption, false);
        }
        $proof = self::inspect();
        unset($proof);

        return self::saveOperation(array(
            'type' => 'rollback',
            'state' => ($failed === array() && $skipped === array()) ? 'restored' : 'attention',
            'ok' => $failed === array(),
            'message' => ($failed === array() && $skipped === array())
                ? __('YPerf restored the Cloudflare settings it had adopted.', 'yablonsky-performance')
                : __('Cloudflare rollback stopped on external drift or an API error instead of overwriting unknown changes.', 'yablonsky-performance'),
            'restored' => $restored,
            'skipped' => $skipped,
            'failed' => $failed,
        ));
    }

    /** @param array<int,string> $urls @return array<string,mixed> */
    public static function purgeUrls(array $urls): array
    {
        $adoption = self::adoption();
        if (empty($adoption['active']) || empty($adoption['purge_sync'])) {
            return array('state' => 'not_adopted', 'ok' => false, 'message' => 'Cloudflare purge sync is not adopted.');
        }
        $zoneId = sanitize_text_field((string) ($adoption['zone']['id'] ?? ''));
        $result = CloudflareClient::purgeUrls($zoneId, $urls);
        if (is_wp_error($result)) {
            return array('state' => 'failed', 'ok' => false, 'message' => sanitize_text_field($result->get_error_message()));
        }
        return array('state' => 'accepted', 'ok' => true, 'message' => 'Cloudflare accepted the URL purge request.');
    }

    /** @return array<string,mixed> */
    public static function purgeHost(): array
    {
        $adoption = self::adoption();
        if (empty($adoption['active']) || empty($adoption['purge_sync'])) {
            return array('state' => 'not_adopted', 'ok' => false, 'message' => 'Cloudflare purge sync is not adopted.');
        }
        $zoneId = sanitize_text_field((string) ($adoption['zone']['id'] ?? ''));
        $result = CloudflareClient::purgeHost($zoneId);
        if (is_wp_error($result)) {
            return array('state' => 'failed', 'ok' => false, 'message' => sanitize_text_field($result->get_error_message()));
        }
        return array('state' => 'accepted', 'ok' => true, 'message' => 'Cloudflare accepted the hostname purge request.');
    }

    /** @return array<string,string> */
    public static function recommendedProfile(): array
    {
        // Keep this profile deliberately small. Early Hints and Speed Brain are
        // observed but not auto-written until YPerf owns a proven corresponding
        // origin contract. Compression is provider-native and verified through
        // response evidence rather than the deprecated Brotli toggle.
        return array(
            'rocket_loader' => 'off',
            'http3' => 'on',
        );
    }

    /** @return array<string,mixed> */
    public static function authorityContract(): array
    {
        return array(
            'owner' => 'Yablonsky Performance',
            'scope' => array('rocket_loader', 'http3', 'performance_edge_audit', 'cache_purge_sync'),
            'observe_only' => array('early_hints', 'speed_brain', 'automatic_platform_optimization', 'effective_content_encoding'),
            'excluded' => array('ai_search', 'ai_training', 'ai_agents', 'bot_preference_sync', 'robots_txt', 'waf', 'firewall', 'dns', 'crawler_policy', 'seo_signals'),
            'crawler_authority' => 'Yablonsky SEO',
            'rule' => 'YPerf never writes Cloudflare crawler, robots, Bot Management, WAF, DNS or SEO policy.',
        );
    }

    /** @return array<string,mixed> */
    public static function lastAudit(): array
    {
        $value = get_option(self::AUDIT_OPTION, array());
        return is_array($value) ? $value : array();
    }

    /** @return array<string,mixed> */
    public static function adoption(): array
    {
        $value = get_option(self::ADOPTION_OPTION, array());
        return is_array($value) ? $value : array();
    }

    /** @return array<string,mixed> */
    public static function lastOperation(): array
    {
        $value = get_option(self::LAST_OPERATION_OPTION, array());
        return is_array($value) ? $value : array();
    }

    public static function clearCachedAudit(): void
    {
        delete_option(self::AUDIT_OPTION);
    }

    public static function clearState(): void
    {
        delete_option(self::AUDIT_OPTION);
        delete_option(self::ADOPTION_OPTION);
        delete_option(self::LAST_OPERATION_OPTION);
    }

    /** @param array<string,mixed> $setting @return array<string,mixed> */
    private static function cleanSetting(array $setting): array
    {
        $id = sanitize_key((string) ($setting['id'] ?? ''));
        $value = $setting['value'] ?? 'unknown';
        if (is_bool($value)) {
            $value = $value ? 'on' : 'off';
        } elseif (is_array($value)) {
            if ($id === 'automatic_platform_optimization') {
                $value = !empty($value['enabled']) ? 'on' : 'off';
            } else {
                $value = 'complex';
            }
        } else {
            $value = sanitize_text_field((string) $value);
        }
        return array(
            'id' => $id,
            'value' => $value,
            'editable' => !empty($setting['editable']),
            'modified_on' => sanitize_text_field((string) ($setting['modified_on'] ?? '')),
        );
    }

    /** @return array<string,mixed> */
    private static function probeEdge(): array
    {
        $started = microtime(true);
        $response = wp_safe_remote_get(home_url('/'), array(
            'timeout' => 10,
            'redirection' => 2,
            'sslverify' => true,
            'decompress' => false,
            'limit_response_size' => 16384,
            'user-agent' => 'Yablonsky-Performance/' . YPERF_VERSION . ' Edge-Probe',
            'headers' => array(
                'Accept' => 'text/html,application/xhtml+xml',
                'Accept-Encoding' => 'zstd, br, gzip',
                'Cache-Control' => 'no-cache',
            ),
        ));
        $ms = (int) round((microtime(true) - $started) * 1000);
        if (is_wp_error($response)) {
            return array('state' => 'failed', 'ms' => $ms, 'message' => sanitize_text_field($response->get_error_message()));
        }
        $server = strtolower(sanitize_text_field((string) wp_remote_retrieve_header($response, 'server')));
        $cfRay = sanitize_text_field((string) wp_remote_retrieve_header($response, 'cf-ray'));
        return array(
            'state' => 'measured',
            'http_code' => (int) wp_remote_retrieve_response_code($response),
            'ms' => $ms,
            'cloudflare_detected' => strpos($server, 'cloudflare') !== false || $cfRay !== '',
            'server' => $server,
            'cf_ray' => $cfRay !== '' ? 'present' : '',
            'cf_cache_status' => strtoupper(sanitize_text_field((string) wp_remote_retrieve_header($response, 'cf-cache-status'))),
            'content_encoding' => strtolower(sanitize_text_field((string) wp_remote_retrieve_header($response, 'content-encoding'))),
            'alt_svc' => sanitize_text_field((string) wp_remote_retrieve_header($response, 'alt-svc')),
            'speculation_rules' => sanitize_text_field((string) wp_remote_retrieve_header($response, 'speculation-rules')),
        );
    }

    /**
     * @param array<string,string> $snapshot
     * @param array<string,string> $changed
     * @return array{restored:array<string,string>,failed:array<string,string>}
     */
    private static function restoreChangedSettings(string $zoneId, array $snapshot, array $changed): array
    {
        $restored = array();
        $failed = array();
        foreach (array_reverse(array_keys($changed)) as $settingId) {
            $previous = (string) ($snapshot[$settingId] ?? '');
            if (!in_array($previous, array('on', 'off'), true)) {
                continue;
            }
            $result = CloudflareClient::setZoneSetting($zoneId, (string) $settingId, $previous);
            if (is_wp_error($result)) {
                $failed[(string) $settingId] = sanitize_text_field($result->get_error_message());
            } else {
                $restored[(string) $settingId] = $previous;
            }
        }
        return array('restored' => $restored, 'failed' => $failed);
    }

    /** @param array<string,mixed> $audit @return array<string,mixed> */
    private static function saveAudit(array $audit): array
    {
        $audit['schema'] = 1;
        $audit['generated_at'] = time();
        $audit['version'] = YPERF_VERSION;
        $audit['authority'] = self::authorityContract();
        update_option(self::AUDIT_OPTION, $audit, false);
        return $audit;
    }

    /** @param array<string,mixed> $operation @return array<string,mixed> */
    private static function saveOperation(array $operation): array
    {
        $operation['schema'] = 1;
        $operation['generated_at'] = time();
        $operation['version'] = YPERF_VERSION;
        update_option(self::LAST_OPERATION_OPTION, $operation, false);
        return $operation;
    }
}
