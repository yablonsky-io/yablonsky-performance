<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * YMulti semantic invalidation consumer.
 *
 * YMulti reports what multilingual state changed. YPerf remains the cache
 * execution authority and translates bounded semantic events into the least
 * destructive local invalidation it can prove.
 */
final class YMultiInvalidationBridge
{
    private const CONTRACT = 'ymulti-semantic-invalidation/v1';
    private const MAX_URLS = 200;
    private const MAX_CANDIDATES = 400;
    private const MAX_SEEN = 256;

    /** @var array<string,bool> */
    private static $seen = array();

    public static function register(): void
    {
        add_action('yablonsky_multilingual_semantic_invalidated_v1', array(self::class, 'consume'), 10, 1);
    }

    /** @param mixed $event */
    public static function consume($event): void
    {
        if (!is_array($event) || (string) ($event['contract'] ?? '') !== self::CONTRACT) {
            return;
        }
        if (!empty($event['purge_executor'])) {
            return;
        }

        $fingerprintInput = array(
            'contract' => self::CONTRACT,
            'scope' => sanitize_key((string) ($event['scope'] ?? 'exact')),
            'reason' => sanitize_key((string) ($event['reason'] ?? 'semantic_change')),
            'broad' => !empty($event['requires_broad_invalidation']),
            'urls' => array_slice((array) ($event['urls'] ?? array()), 0, self::MAX_CANDIDATES),
            'resource_ids' => array_slice((array) ($event['resource_ids'] ?? array()), 0, self::MAX_CANDIDATES),
            'term_resources' => array_slice((array) ($event['term_resources'] ?? array()), 0, self::MAX_CANDIDATES),
        );
        $fingerprint = hash('sha256', (string) wp_json_encode($fingerprintInput));
        if (isset(self::$seen[$fingerprint])) {
            return;
        }
        if (count(self::$seen) >= self::MAX_SEEN) {
            array_shift(self::$seen);
        }
        self::$seen[$fingerprint] = true;

        $scope = sanitize_key((string) ($event['scope'] ?? 'exact'));
        $broad = !empty($event['requires_broad_invalidation']);
        if ($broad && $scope === 'language_configuration') {
            InvalidationBus::invalidateAll('ymulti:language_configuration');
            return;
        }

        $urls = array();
        foreach (array_slice((array) ($event['resource_ids'] ?? array()), 0, self::MAX_CANDIDATES) as $resourceId) {
            $resourceId = absint($resourceId);
            if ($resourceId < 1) {
                continue;
            }
            $url = get_permalink($resourceId);
            if (is_string($url) && $url !== '' && self::sameOrigin($url)) {
                $urls[$url] = $url;
            }
            if (count($urls) >= self::MAX_URLS) {
                break;
            }
        }

        if (count($urls) < self::MAX_URLS) {
            foreach (array_slice((array) ($event['term_resources'] ?? array()), 0, self::MAX_CANDIDATES) as $termResource) {
                if (!is_array($termResource)) {
                    continue;
                }
                $termId = absint($termResource['term_id'] ?? 0);
                $taxonomy = sanitize_key((string) ($termResource['taxonomy'] ?? ''));
                if ($termId < 1 || $taxonomy === '') {
                    continue;
                }
                $url = get_term_link($termId, $taxonomy);
                if (!is_wp_error($url) && is_string($url) && $url !== '' && self::sameOrigin($url)) {
                    $urls[$url] = $url;
                }
                if (count($urls) >= self::MAX_URLS) {
                    break;
                }
            }
        }

        if (count($urls) < self::MAX_URLS) {
            foreach (array_slice((array) ($event['urls'] ?? array()), 0, self::MAX_CANDIDATES) as $url) {
                $clean = esc_url_raw((string) $url);
                if ($clean !== '' && self::sameOrigin($clean)) {
                    $urls[$clean] = $clean;
                }
                if (count($urls) >= self::MAX_URLS) {
                    break;
                }
            }
        }

        if ($urls === array()) {
            return;
        }

        $reason = sanitize_key((string) ($event['reason'] ?? 'semantic_change'));
        InvalidationBus::invalidateUrls(array_values($urls), 'ymulti:' . $reason);
    }

    private static function sameOrigin(string $url): bool
    {
        $target = wp_parse_url($url);
        $home = wp_parse_url(home_url('/'));
        if (!is_array($target) || !is_array($home)) {
            return false;
        }
        $targetHost = strtolower((string) ($target['host'] ?? ''));
        $homeHost = strtolower((string) ($home['host'] ?? ''));
        $targetScheme = strtolower((string) ($target['scheme'] ?? ''));
        $homeScheme = strtolower((string) ($home['scheme'] ?? ''));
        if ($targetHost === '' || $homeHost === '' || $targetHost !== $homeHost || $targetScheme !== $homeScheme) {
            return false;
        }
        $defaultPort = static function (string $scheme): int {
            return $scheme === 'https' ? 443 : 80;
        };
        $targetPort = isset($target['port']) ? (int) $target['port'] : $defaultPort($targetScheme);
        $homePort = isset($home['port']) ? (int) $home['port'] : $defaultPort($homeScheme);
        return $targetPort === $homePort;
    }

    /** @return array<string,mixed> */
    public static function status(): array
    {
        $provider = apply_filters('yablonsky_multilingual_invalidation_provider_v1', null);
        $valid = is_array($provider) && (string) ($provider['contract'] ?? '') === self::CONTRACT;
        return array(
            'available' => is_array($provider),
            'contract_valid' => $valid,
            'contract' => $valid ? self::CONTRACT : sanitize_text_field((string) (is_array($provider) ? ($provider['contract'] ?? '') : '')),
        );
    }
}
