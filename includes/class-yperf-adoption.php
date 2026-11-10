<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class Adoption
{
    /** @return array<string,mixed> */
    public static function buildSnapshot(): array
    {
        $capabilities = CapabilityDetector::detect();
        $hostSnapshot = HostSnapshot::last();
        $nativePlan = NativeAdoptionPlan::build($capabilities, $hostSnapshot);
        $pagePlugins = $capabilities['page_cache']['known_page_cache_plugins'] ?? array();
        $hasExistingPageLayer = !empty($capabilities['page_cache']['advanced_cache_dropin']) || !empty($pagePlugins);
        $hasObjectLayer = !empty($capabilities['object_cache']['dropin_present']);
        $providers = is_array($capabilities['providers'] ?? null) ? $capabilities['providers'] : array();
        $w3tc = is_array($providers['w3-total-cache'] ?? null) ? $providers['w3-total-cache'] : array();
        $w3Layers = is_array($w3tc['layers'] ?? null) ? $w3tc['layers'] : array();
        $w3Page = is_array($w3Layers['page_cache'] ?? null) ? $w3Layers['page_cache'] : array();
        $pageClassification = $hasExistingPageLayer ? 'adopt_or_conflict_review' : 'available_for_ypc';
        if (!empty($w3tc['config_readable']) && empty($w3Page['enabled'])) {
            $pageClassification = 'provider_installed_cache_off_review';
        }

        return array(
            'schema'       => 1,
            'captured_at'  => time(),
            'capabilities' => $capabilities,
            'classification' => array(
                'page_cache'   => $pageClassification,
                'object_cache' => $hasObjectLayer ? 'adopt_candidate' : 'not_detected',
                'origin'       => !empty($capabilities['origin']['fpm_candidate']) ? 'helper_required' : 'manual_review',
            ),
            'host_snapshot' => $hostSnapshot,
            'native_adoption_plan' => $nativePlan,
            'mode' => 'read_only_preflight',
        );
    }

    /** @return array<string,mixed> */
    public static function capture(): array
    {
        $snapshot = self::buildSnapshot();
        update_option('yperf_adoption_snapshot_v1', $snapshot, false);
        return $snapshot;
    }

    /** @return array<string,mixed> */
    public static function lastSnapshot(): array
    {
        $snapshot = get_option('yperf_adoption_snapshot_v1', array());
        return is_array($snapshot) ? $snapshot : array();
    }
}
