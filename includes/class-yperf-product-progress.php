<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Site-readiness model for the public YPerf admin surface.
 *
 * This intentionally does NOT expose internal product-development percentages.
 * It answers one administrator question only: how far is this site from a
 * proven production performance state?
 */
final class ProductProgress
{
    /**
     * @param array<string,mixed> $releaseReadiness
     * @param array<string,mixed> $environmentCheck
     * @param array<string,mixed> $runtime
     * @param array<string,mixed> $effectiveness
     * @param array<string,mixed> $providerExitGate
     * @return array<string,mixed>
     */
    public static function build(array $releaseReadiness, array $environmentCheck = array(), array $runtime = array(), array $effectiveness = array(), array $providerExitGate = array(), array $evidence = array()): array
    {
        $manifest = self::manifest();
        $packs = is_array($manifest['packs'] ?? null) ? $manifest['packs'] : array();
        $totalLanguages = (int) ($manifest['language_scope']['languages'] ?? count($packs));
        $completeLanguages = 0;
        foreach ($packs as $pack) {
            if (!is_array($pack)) {
                continue;
            }
            $status = sanitize_key((string) ($pack['status'] ?? 'target'));
            $coverage = (int) ($pack['coverage_percent'] ?? 0);
            if ($status === 'source' && $coverage === 100) {
                ++$completeLanguages;
                continue;
            }
            if ($status === 'complete' && $coverage === 100 && self::packFilesVerified($pack)) {
                ++$completeLanguages;
            }
        }

        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        $runtimeCurrentClean = $runtime !== array()
            && (string) ($runtime['version'] ?? '') === YPERF_VERSION
            && (int) ($summary['fail'] ?? 0) === 0;
        $environmentReady = $environmentCheck !== array();
        $overallState = sanitize_key((string) ($effectiveness['overall_state'] ?? 'observe'));
        $yperfProven = (int) ($effectiveness['yperf_controls_proven'] ?? 0);
        $optimizationProven = $runtimeCurrentClean && ($overallState === 'active' || $yperfProven > 0);

        $coreGates = is_array($releaseReadiness['core_gates'] ?? null) ? $releaseReadiness['core_gates'] : array();
        $providerExitResolved = empty($providerExitGate['applicable']) || !empty($providerExitGate['safe_to_remove']);
        $productionReady = $optimizationProven
            && !empty($coreGates['control_plane_not_blocked'])
            && !empty($coreGates['origin_cost_evidence'])
            && $providerExitResolved;

        $stages = array(
            array(
                'id' => 'scan',
                'label' => __('Scan', 'yablonsky-performance'),
                'description' => __('Environment and provider ownership inspected', 'yablonsky-performance'),
                'complete' => $environmentReady,
                'tab' => 'setup',
            ),
            array(
                'id' => 'prove',
                'label' => __('Acceptance', 'yablonsky-performance'),
                'description' => __('Current Runtime Acceptance is clean', 'yablonsky-performance'),
                'complete' => $runtimeCurrentClean,
                'tab' => 'overview',
            ),
            array(
                'id' => 'optimize',
                'label' => __('Optimize', 'yablonsky-performance'),
                'description' => __('A YPerf optimization is enabled and proven', 'yablonsky-performance'),
                'complete' => $optimizationProven,
                'tab' => 'optimize',
            ),
            array(
                'id' => 'production',
                'label' => __('Production', 'yablonsky-performance'),
                'description' => __('Runtime, origin evidence and control plane are ready', 'yablonsky-performance'),
                'complete' => $productionReady,
                'tab' => 'insights',
            ),
        );

        $completed = 0;
        foreach ($stages as $stage) {
            if (!empty($stage['complete'])) {
                ++$completed;
            }
        }
        $percent = (int) round(($completed / count($stages)) * 100);

        $next = array('label' => __('Review production readiness', 'yablonsky-performance'), 'tab' => 'insights');
        if (!$environmentReady) {
            $next = array('label' => __('Run Environment Check', 'yablonsky-performance'), 'tab' => 'setup');
        } elseif (!$runtimeCurrentClean) {
            $next = array('label' => __('Run Runtime Acceptance', 'yablonsky-performance'), 'tab' => 'overview');
        } elseif (!$optimizationProven) {
            $next = array('label' => __('Review safe optimization', 'yablonsky-performance'), 'tab' => 'optimize');
        } elseif (!$productionReady) {
            $next = array('label' => __('Close production evidence gates', 'yablonsky-performance'), 'tab' => 'insights');
        }

        $remainingLanguages = max(0, $totalLanguages - $completeLanguages);

        $sources = is_array($evidence['sources'] ?? null) ? $evidence['sources'] : array();
        $refreshDue = false;
        foreach (array('environment_check', 'runtime_acceptance', 'benchmark') as $sourceId) {
            $source = is_array($sources[$sourceId] ?? null) ? $sources[$sourceId] : array();
            $freshness = sanitize_key((string) ($source['freshness'] ?? 'unknown'));
            $versionRelation = sanitize_key((string) ($source['version_relation'] ?? ''));
            $quality = sanitize_key((string) ($source['quality'] ?? ''));
            $healthy = !empty($source['available'])
                && in_array($freshness, array('fresh', 'recent'), true)
                && ($versionRelation === '' || $versionRelation === 'current')
                && ($quality === '' || $quality === 'usable');
            if (!$healthy) {
                $refreshDue = true;
                break;
            }
        }
        $conflictCount = max(0, (int) ($evidence['conflict_count'] ?? 0));
        $readinessLabel = $productionReady
            ? ($refreshDue ? __('Production foundation proven · evidence refresh due', 'yablonsky-performance') : __('Production evidence current', 'yablonsky-performance'))
            : __('Prepare this site for proven performance', 'yablonsky-performance');
        return array(
            'schema' => 2,
            'site_readiness_percent' => $percent,
            'site_readiness_complete' => $productionReady,
            'site_readiness_label' => $readinessLabel,
            'evidence_refresh_due' => $refreshDue,
            'evidence_conflict_count' => $conflictCount,
            'site_readiness_completed_stages' => $completed,
            'site_readiness_total_stages' => count($stages),
            'stages' => $stages,
            'next' => $next,
            // Localization remains visible in its dedicated engineering/readiness card,
            // not mixed into the site's runtime progress indicator.
            'languages_complete' => $completeLanguages,
            'languages_total' => $totalLanguages,
            'languages_remaining' => $remainingLanguages,
            'localization_percent' => $totalLanguages > 0 ? (int) round(($completeLanguages / $totalLanguages) * 100) : 0,
            'locale_variants' => (int) ($manifest['language_scope']['locale_variants'] ?? 0),
            'packs' => $packs,
            'provider_exit_applicable' => !empty($providerExitGate['applicable']),
            'provider_exit' => array(
                'provider' => sanitize_text_field((string) ($providerExitGate['provider'] ?? '')),
                'percent' => max(0, min(100, (int) ($providerExitGate['guided_progress_percent'] ?? ($providerExitGate['progress_percent'] ?? 0)))),
                'ready_items' => max(0, (int) ($providerExitGate['guided_ready_items'] ?? ($providerExitGate['ready_items'] ?? 0))),
                'total_items' => max(0, (int) ($providerExitGate['guided_total_items'] ?? ($providerExitGate['total_items'] ?? 0))),
                'status' => sanitize_key((string) ($providerExitGate['status'] ?? 'not_applicable')),
            ),
        );
    }

    /** @param array<string,mixed> $pack */
    private static function packFilesVerified(array $pack): bool
    {
        $locales = array_map('strval', (array) ($pack['locales'] ?? array()));
        if ($locales === array()) {
            return false;
        }
        foreach ($locales as $locale) {
            $base = dirname(__DIR__) . '/languages/yablonsky-performance-' . $locale;
            if (!is_readable($base . '.po') || !is_readable($base . '.mo')) {
                return false;
            }
        }
        return true;
    }

    /** @return array<string,mixed> */
    private static function manifest(): array
    {
        $path = dirname(__DIR__) . '/languages/manifest.json';
        if (!is_readable($path)) {
            return array();
        }
        $decoded = wp_json_file_decode($path, array('associative' => true));
        return is_array($decoded) ? $decoded : array();
    }
}
