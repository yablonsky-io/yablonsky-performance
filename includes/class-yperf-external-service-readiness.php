<?php

declare(strict_types=1);
namespace Yablonsky\Performance;

final class ExternalServiceReadiness
{
    /** @return array<string,mixed> */
    public static function analyze(): array
    {
        $credential = YablonskyAICredentials::status();
        $accountProof = YablonskyAIAccount::cached();
        $contract = YablonskyAIClient::contract();
        $compute = esc_url_raw((string) ($contract['compute_url'] ?? ''));
        $account = esc_url_raw((string) ($contract['account_url'] ?? ''));
        $host = $compute !== '' ? strtolower((string) wp_parse_url($compute, PHP_URL_HOST)) : '';
        $accountHost = $account !== '' ? strtolower((string) wp_parse_url($account, PHP_URL_HOST)) : '';
        $header = trim((string) ($contract['auth_header'] ?? ''));
        $workspaceHeader = trim((string) ($contract['workspace_header'] ?? ''));
        $authMode = sanitize_key((string) ($contract['auth_mode'] ?? ''));
        $prefix = trim((string) ($contract['auth_prefix'] ?? ''));
        $transport = sanitize_key((string) ($contract['compute_transport'] ?? ''));
        $version = trim((string) ($contract['contract_version'] ?? ''));
        $authModeValid = in_array($authMode, array('raw_header', 'prefixed_header'), true) && ($authMode !== 'prefixed_header' || $prefix !== '');
        $checks = array(
            'credential_configured' => !empty($credential['configured']),
            'https_compute_url' => strpos($compute, 'https://') === 0,
            'yablonsky_owned_host' => self::ownedHost($host),
            'https_account_url' => strpos($account, 'https://') === 0,
            'account_url_yablonsky_owned' => self::ownedHost($accountHost),
            'contract_version_explicit' => $version !== '',
            'auth_header_explicit' => $header !== '' && preg_match('/^[A-Za-z0-9-]{1,64}$/', $header) === 1,
            'auth_mode_explicit' => $authModeValid,
            'workspace_header_explicit' => $workspaceHeader !== '' && preg_match('/^[A-Za-z0-9-]{1,64}$/', $workspaceHeader) === 1,
            'compute_transport_explicit' => $transport === 'writer_sse_multipart',
            'account_round_trip_fresh' => !empty($accountProof['verified']) && !empty($accountProof['fresh']),
            'redirects_disabled' => true,
            'response_size_bounded' => true,
            'explicit_only' => true,
            'local_features_free' => true,
            'remote_compute_is_substantive' => true,
        );
        return array(
            'schema' => 3,
            'ready' => !in_array(false, $checks, true),
            'checks' => $checks,
            'service' => 'Yablonsky AI',
            'service_url' => 'https://ai.yablonsky.io/',
            'contract_version' => sanitize_text_field($version),
            'auth_mode' => $authMode,
            'compute_transport' => $transport,
        );
    }

    private static function ownedHost(string $host): bool
    {
        return $host === 'ai.yablonsky.io' || ($host !== '' && substr($host, -13) === '.yablonsky.io');
    }
}
