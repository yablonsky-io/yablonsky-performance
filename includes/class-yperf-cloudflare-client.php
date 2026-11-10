<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class CloudflareClient
{
    private const BASE = 'https://api.cloudflare.com/client/v4';

    /** @return array<string,mixed>|\WP_Error */
    public static function discoverZone()
    {
        $override = CloudflareCredentials::zoneOverride();
        if ($override !== '') {
            return array('id' => $override, 'name' => self::homeHost(), 'source' => 'manual_override');
        }

        $response = self::request('GET', '/zones', null, array('status' => 'active', 'per_page' => '50'));
        if (is_wp_error($response)) {
            return $response;
        }
        $zones = is_array($response['result'] ?? null) ? $response['result'] : array();
        $host = self::homeHost();
        $best = array();
        $bestLength = -1;
        foreach ($zones as $zone) {
            if (!is_array($zone)) {
                continue;
            }
            $name = strtolower(trim((string) ($zone['name'] ?? '')));
            $id = strtolower(trim((string) ($zone['id'] ?? '')));
            if ($name === '' || preg_match('/^[a-f0-9]{32}$/', $id) !== 1) {
                continue;
            }
            $matches = $host === $name || ($host !== '' && substr($host, -strlen('.' . $name)) === '.' . $name);
            if ($matches && strlen($name) > $bestLength) {
                $best = array('id' => $id, 'name' => $name, 'source' => 'auto_discovery');
                $bestLength = strlen($name);
            }
        }
        if ($best === array()) {
            return new \WP_Error('yperf_cloudflare_zone_not_found', __('Cloudflare connection worked, but no active zone matched this WordPress hostname. Add the Zone ID manually.', 'yablonsky-performance'));
        }
        return $best;
    }

    /** @return array<string,mixed>|\WP_Error */
    public static function getZoneSetting(string $zoneId, string $settingId)
    {
        if (!self::validZoneId($zoneId) || !self::allowedSetting($settingId)) {
            return new \WP_Error('yperf_cloudflare_setting_invalid', __('Cloudflare setting request was blocked by the YPerf allowlist.', 'yablonsky-performance'));
        }
        $response = self::request('GET', '/zones/' . $zoneId . '/settings/' . $settingId);
        if (is_wp_error($response)) {
            return $response;
        }
        return is_array($response['result'] ?? null) ? $response['result'] : array();
    }

    /** @return array<string,mixed>|\WP_Error */
    public static function setZoneSetting(string $zoneId, string $settingId, string $value)
    {
        if (!self::validZoneId($zoneId) || !self::allowedWritableSetting($settingId) || !in_array($value, array('on', 'off'), true)) {
            return new \WP_Error('yperf_cloudflare_setting_invalid', __('Cloudflare setting update was blocked by the YPerf performance allowlist.', 'yablonsky-performance'));
        }
        $response = self::request('PATCH', '/zones/' . $zoneId . '/settings/' . $settingId, array('value' => $value));
        if (is_wp_error($response)) {
            return $response;
        }
        return is_array($response['result'] ?? null) ? $response['result'] : array();
    }

    /** @param array<int,string> $urls @return array<string,mixed>|\WP_Error */
    public static function purgeUrls(string $zoneId, array $urls)
    {
        if (!self::validZoneId($zoneId)) {
            return new \WP_Error('yperf_cloudflare_zone_invalid', __('Cloudflare purge was blocked because the zone identity is invalid.', 'yablonsky-performance'));
        }
        $clean = array();
        foreach ($urls as $url) {
            $url = esc_url_raw((string) $url);
            if ($url !== '' && self::sameOrigin($url)) {
                $clean[] = $url;
            }
            if (count($clean) >= 100) {
                break;
            }
        }
        $clean = array_values(array_unique($clean));
        if ($clean === array()) {
            return new \WP_Error('yperf_cloudflare_purge_empty', __('Cloudflare purge did not receive a valid same-origin URL.', 'yablonsky-performance'));
        }
        return self::request('POST', '/zones/' . $zoneId . '/purge_cache', array('files' => $clean));
    }

    /** @return array<string,mixed>|\WP_Error */
    public static function purgeHost(string $zoneId)
    {
        if (!self::validZoneId($zoneId)) {
            return new \WP_Error('yperf_cloudflare_zone_invalid', __('Cloudflare purge was blocked because the zone identity is invalid.', 'yablonsky-performance'));
        }
        $host = self::homeHost();
        if ($host === '') {
            return new \WP_Error('yperf_cloudflare_host_invalid', __('Cloudflare host purge could not resolve the WordPress hostname.', 'yablonsky-performance'));
        }
        return self::request('POST', '/zones/' . $zoneId . '/purge_cache', array('hosts' => array($host)));
    }

    /** @return array<string,mixed>|\WP_Error */
    private static function request(string $method, string $path, ?array $body = null, array $query = array())
    {
        $token = CloudflareCredentials::revealForRequest();
        if ($token === '') {
            return new \WP_Error('yperf_cloudflare_not_configured', __('Cloudflare API token is not configured.', 'yablonsky-performance'));
        }
        $method = strtoupper($method);
        if (!in_array($method, array('GET', 'POST', 'PATCH'), true) || $path === '' || $path[0] !== '/' || strpos($path, '..') !== false) {
            return new \WP_Error('yperf_cloudflare_request_blocked', __('Cloudflare API request was blocked by the local request policy.', 'yablonsky-performance'));
        }
        $url = self::BASE . $path;
        if ($query !== array()) {
            $url = add_query_arg($query, $url);
        }
        $args = array(
            'method' => $method,
            'timeout' => 12,
            'redirection' => 0,
            'sslverify' => true,
            'limit_response_size' => 262144,
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'Yablonsky-Performance/' . YPERF_VERSION . ' Cloudflare-Adapter',
            ),
        );
        if ($body !== null) {
            $encoded = wp_json_encode($body);
            if (!is_string($encoded)) {
                return new \WP_Error('yperf_cloudflare_json_failed', __('Cloudflare API request body could not be encoded safely.', 'yablonsky-performance'));
            }
            $args['body'] = $encoded;
        }
        $response = wp_safe_remote_request($url, $args);
        if (is_wp_error($response)) {
            return $response;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $raw = (string) wp_remote_retrieve_body($response);
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return new \WP_Error('yperf_cloudflare_invalid_json', sprintf(
                /* translators: %d: HTTP response status */
                __('Cloudflare API returned an unreadable response (HTTP %d).', 'yablonsky-performance'),
                $code
            ));
        }
        if ($code < 200 || $code >= 300 || empty($decoded['success'])) {
            $message = self::errorMessage($decoded);
            return new \WP_Error('yperf_cloudflare_api_error', $message !== '' ? $message : sprintf(
                /* translators: %d: HTTP response status */
                __('Cloudflare API request failed (HTTP %d).', 'yablonsky-performance'),
                $code
            ));
        }
        return $decoded;
    }

    private static function errorMessage(array $decoded): string
    {
        $errors = is_array($decoded['errors'] ?? null) ? $decoded['errors'] : array();
        $messages = array();
        foreach ($errors as $error) {
            if (!is_array($error)) {
                continue;
            }
            $message = sanitize_text_field((string) ($error['message'] ?? ''));
            if ($message !== '') {
                $messages[] = $message;
            }
        }
        return implode('; ', array_slice(array_unique($messages), 0, 3));
    }

    private static function allowedSetting(string $settingId): bool
    {
        return in_array($settingId, array('rocket_loader', 'http3', 'early_hints', 'speed_brain', 'automatic_platform_optimization'), true);
    }

    private static function allowedWritableSetting(string $settingId): bool
    {
        return in_array($settingId, array('rocket_loader', 'http3'), true);
    }

    private static function validZoneId(string $zoneId): bool
    {
        return preg_match('/^[a-f0-9]{32}$/', strtolower(trim($zoneId))) === 1;
    }

    private static function homeHost(): string
    {
        return strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    }

    private static function sameOrigin(string $url): bool
    {
        $target = wp_parse_url($url);
        $home = wp_parse_url(home_url('/'));
        if (!is_array($target) || !is_array($home)) {
            return false;
        }
        return strtolower((string) ($target['scheme'] ?? '')) === strtolower((string) ($home['scheme'] ?? ''))
            && strtolower((string) ($target['host'] ?? '')) === strtolower((string) ($home['host'] ?? ''));
    }
}
