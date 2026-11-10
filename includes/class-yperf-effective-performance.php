<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Human-readable execution truth.
 *
 * This class intentionally separates "configured" from "proven". A detected
 * plugin, daemon or drop-in is never presented as active acceleration unless
 * WordPress/runtime evidence confirms execution.
 */
final class EffectivePerformance
{
    /**
     * @param array<string,mixed> $caps
     * @param array<string,mixed> $runtime
     * @param array<string,mixed> $nativeProof
     * @param array<string,mixed> $plan
     * @return array<string,mixed>
     */
    public static function build(array $caps, array $runtime, array $nativeProof, array $plan, array $originBudgetAdoption = array()): array
    {
        $frontendEnabled = get_option('yperf_frontend_optimizer', '0') === '1';
        $guardEnabled = get_option('yperf_deep_archive_guard', '0') === '1';

        $frontendTest = self::runtimeTest($runtime, 'YPerf frontend control');
        $guardTest = self::runtimeTest($runtime, 'YPerf first-hit protection');
        $homeTest = self::runtimeTest($runtime, 'Homepage');

        $nativePageState = sanitize_key((string) ($nativeProof['page_cache']['state'] ?? 'not_proven'));
        $runtimePageProof = self::runtimePageCacheProof($runtime);
        $yperfPage = PageCacheExecutor::last();
        $yperfPageProof = is_array($yperfPage['last_proof'] ?? null) ? $yperfPage['last_proof'] : array();
        $yperfPageActive = PageCacheExecutor::enabled() && !empty($yperfPageProof['ok']) && (string) ($yperfPageProof['version'] ?? '') === YPERF_VERSION;
        $yperfMiss = is_array($yperfPageProof['first'] ?? null) ? $yperfPageProof['first'] : array();
        $frontendProvenByPageCacheMiss = $frontendEnabled
            && $yperfPageActive
            && strtoupper((string) ($yperfMiss['cache'] ?? '')) === 'MISS'
            && sanitize_key((string) ($yperfMiss['frontend'] ?? '')) === 'active';

        $frontendProven = $frontendEnabled
            && (((string) ($frontendTest['status'] ?? '') === 'pass'
                && (string) (($frontendTest['cache_signals']['x-yperf-frontend'] ?? '')) === 'active')
                || $frontendProvenByPageCacheMiss);

        $guardProven = $guardEnabled
            && (string) ($guardTest['status'] ?? '') === 'pass'
            && (string) (($guardTest['cache_signals']['x-yperf-first-hit'] ?? '')) === 'protected';
        if ($yperfPageActive) {
            $runtimePageProof = array('proven' => true, 'provider' => 'Yablonsky Performance', 'reason' => 'YPerf proved its own page-cache MISS → HIT path on the current plugin version.');
        }
        $pageActive = $yperfPageActive || in_array($nativePageState, array('miss_then_hit_confirmed', 'hit_confirmed'), true) || $runtimePageProof['proven'];
        $pageArmed = !$pageActive && (self::providerPageCacheEnabled($caps) || self::nativePageConfigured($nativePageState));
        $objectActive = !empty($caps['object_cache']['external_enabled']);
        $yperfObjectStatus = function_exists('yperf_object_cache_runtime_status') ? yperf_object_cache_runtime_status() : array();
        $yperfObjectActive = $objectActive && !empty($yperfObjectStatus['redis_connected']);
        $redisState = sanitize_key((string) ($nativeProof['object_cache']['redis']['state'] ?? 'unknown'));
        $objectNeedsReview = !$objectActive && in_array($redisState, array('high_memory_pressure', 'active_eviction_pressure', 'unbounded_attention'), true);
        $opcacheActive = !empty($caps['opcode_cache']['enabled']);

        $encoding = strtolower((string) (($homeTest['cache_signals']['content-encoding'] ?? '')));
        $compressionActive = in_array($encoding, array('gzip', 'br', 'deflate', 'zstd'), true);
        $cfState = strtoupper((string) (($homeTest['cache_signals']['cf-cache-status'] ?? '')));
        $edgeHtmlActive = in_array($cfState, array('HIT', 'STALE', 'REVALIDATED', 'UPDATING'), true);

        $originMapped = (string) ($nativeProof['origin']['state'] ?? '') === 'site_pool_mapped';
        $originBudgetActive = !empty($originBudgetAdoption['active']);

        $layers = array(
            'yperf_frontend' => self::layer(
                'YPerf frontend control',
                $frontendProven ? 'active' : ($frontendEnabled ? 'armed' : 'off'),
                'Yablonsky Performance',
                $frontendProven
                    ? ($frontendProvenByPageCacheMiss
                        ? 'The Page Cache MISS execution path proved the local frontend-overhead control before subsequent HITs bypassed WordPress.'
                        : 'Runtime Acceptance proved the local frontend-overhead control on the public homepage.')
                    : ($frontendEnabled ? 'Enabled, but a fresh runtime proof has not confirmed execution yet.' : 'Not enabled by the current performance profile.'),
                $frontendProven
            ),
            'first_hit_guard' => self::layer(
                'First-hit protection',
                $guardProven ? 'active' : ($guardEnabled ? 'armed' : 'off'),
                'Yablonsky Performance',
                $guardProven
                    ? 'A real WooCommerce deep-archive request was protected by the bounded query guard.'
                    : ($guardEnabled ? 'Armed. It activates only on eligible public WooCommerce deep archives; no eligible request may have been observed yet.' : 'Not enabled by the current performance profile.'),
                $guardProven
            ),
            'page_cache' => self::layer(
                'Page cache',
                $pageActive ? 'active' : ($pageArmed ? 'armed' : 'not_proven'),
                self::pageProvider($caps, $nativeProof, $runtimePageProof),
                $pageActive
                    ? (string) $runtimePageProof['reason']
                    : ($pageArmed
                        ? 'A page-cache executor is configured, but this site has no current HIT proof. YPerf will not call it ACTIVE yet.'
                        : 'A cache configuration/provider may exist, but YPerf does not have execution proof for this site yet.'),
                $pageActive
            ),
            'object_cache' => self::layer(
                'Persistent object cache',
                $objectActive ? 'active' : ($objectNeedsReview ? 'needs_review' : 'off'),
                $objectActive ? ($yperfObjectActive ? 'Yablonsky Performance · Redis' : 'WordPress external object cache') : 'No adopted executor',
                $objectActive
                    ? ($yperfObjectActive ? 'WordPress confirms the YPerf-owned Redis object-cache drop-in is loaded and its Redis connection is active.' : 'WordPress confirms that an external persistent object cache is executing.')
                    : self::objectReason($nativeProof),
                $objectActive
            ),
            'opcache' => self::layer(
                'PHP OPcache',
                $opcacheActive ? 'active' : 'off',
                'PHP runtime',
                $opcacheActive ? 'Compiled PHP bytecode caching is active.' : 'PHP OPcache is not confirmed active.',
                $opcacheActive
            ),
            'compression' => self::layer(
                'HTML compression',
                $compressionActive ? 'active' : 'not_proven',
                $compressionActive
                    ? ((!empty($homeTest['delivery_signals']['cloudflare']) ? 'Cloudflare edge · ' : 'HTTP delivery · ') . strtoupper($encoding))
                    : 'HTTP delivery',
                $compressionActive ? 'The latest homepage probe received compressed HTML.' : 'The latest homepage probe did not expose a supported Content-Encoding signal.',
                $compressionActive
            ),
            'edge_html' => self::layer(
                'Edge HTML cache',
                $edgeHtmlActive ? 'active' : ($cfState === 'DYNAMIC' ? 'off' : 'not_proven'),
                $cfState !== '' ? 'Cloudflare' : 'Edge / CDN',
                $edgeHtmlActive
                    ? 'The latest homepage probe was served from edge HTML cache.'
                    : ($cfState === 'DYNAMIC' ? 'Cloudflare is present, but the latest HTML response was dynamic.' : 'No edge HTML HIT signal is available.'),
                $edgeHtmlActive
            ),
            'origin_budget' => self::layer(
                'Origin budget',
                $originBudgetActive ? 'active' : ($originMapped ? 'needs_review' : 'not_proven'),
                'PHP-FPM / host',
                $originBudgetActive
                    ? 'YPerf has adopted the existing bounded PHP-FPM max_children value as the authoritative site Origin Budget; the native host limit is the executor.'
                    : ($originMapped
                        ? 'The site is mapped to a concrete FPM pool, but YPerf has not adopted a worker-budget authority yet.'
                        : 'The exact site pool/budget is not proven. No worker limits are changed by WordPress.'),
                $originBudgetActive
            ),
        );

        $yperfEnabled = 0;
        $yperfProven = 0;
        foreach (array('yperf_frontend', 'first_hit_guard') as $key) {
            $state = (string) ($layers[$key]['state'] ?? 'off');
            if (in_array($state, array('active', 'armed'), true)) {
                ++$yperfEnabled;
            }
            if ($state === 'active') {
                ++$yperfProven;
            }
        }
        // The portable Page Cache is a real YPerf-owned executor, not merely a
        // detected site acceleration layer. Count it in YPerf execution truth
        // only when YPerf itself owns and proves the MISS -> HIT path.
        if ($yperfPageActive) {
            ++$yperfEnabled;
            ++$yperfProven;
        }

        $siteAcceleration = 0;
        foreach (array('page_cache', 'object_cache', 'opcache', 'compression', 'edge_html') as $key) {
            if ((string) ($layers[$key]['state'] ?? '') === 'active') {
                ++$siteAcceleration;
            }
        }

        $profile = (string) ($plan['profile'] ?? 'observe');
        $overallState = 'observe';
        $overallLabel = 'Observe mode';
        if ($profile !== 'observe' || $yperfEnabled > 0) {
            $overallState = $yperfProven > 0 ? 'active' : 'partial';
            $overallLabel = $yperfProven > 0 ? 'YPerf controls proven active' : 'YPerf controls armed · proof pending';
        }

        return array(
            'schema' => 1,
            'generated_at' => time(),
            'profile' => $profile,
            'overall_state' => $overallState,
            'overall_label' => $overallLabel,
            'yperf_controls_enabled' => $yperfEnabled,
            'yperf_controls_proven' => $yperfProven,
            'site_acceleration_layers_active' => $siteAcceleration,
            'layers' => $layers,
            'proof_note' => 'ACTIVE means execution evidence exists. ARMED means YPerf is enabled but the matching workload has not yet produced proof. NOT PROVEN never means active.',
        );
    }

    /** @param array<string,mixed> $runtime @return array<string,mixed> */
    private static function runtimeTest(array $runtime, string $name): array
    {
        foreach ((array) ($runtime['tests'] ?? array()) as $test) {
            if (is_array($test) && (string) ($test['name'] ?? '') === $name) {
                return $test;
            }
        }
        return array();
    }

    /** @return array<string,mixed> */
    private static function layer(string $name, string $state, string $provider, string $evidence, bool $proven): array
    {
        return array(
            'name' => $name,
            'state' => $state,
            'provider' => $provider,
            'evidence' => $evidence,
            'proven' => $proven,
        );
    }

    /** @param array<string,mixed> $caps @param array<string,mixed> $nativeProof @param array<string,mixed> $runtimeProof */
    private static function pageProvider(array $caps, array $nativeProof, array $runtimeProof): string
    {
        $nativeState = (string) ($nativeProof['page_cache']['state'] ?? '');
        if (in_array($nativeState, array('miss_then_hit_confirmed', 'hit_confirmed'), true)) {
            return 'Native page cache';
        }
        if (!empty($runtimeProof['proven'])) {
            return sanitize_text_field((string) ($runtimeProof['provider'] ?? 'HTTP page cache'));
        }
        $providers = (array) ($caps['page_cache']['known_page_cache_plugins'] ?? array());
        if ($providers !== array()) {
            return implode(', ', array_map('strval', $providers));
        }
        return 'No proven executor';
    }

    /** @param array<string,mixed> $runtime @return array{proven:bool,provider:string,reason:string} */
    private static function runtimePageCacheProof(array $runtime): array
    {
        if ((string) ($runtime['version'] ?? '') !== YPERF_VERSION) {
            return array('proven' => false, 'provider' => '', 'reason' => 'Current-version page-cache proof has not been captured yet.');
        }

        $externalAuthority = RuntimeAcceptance::externalPageCacheAuthority($runtime);
        if (!empty($externalAuthority['detected'])) {
            return array(
                'proven' => true,
                'provider' => (string) ($externalAuthority['provider'] ?? 'External origin page cache'),
                'reason' => sprintf('Current Runtime evidence exposes %s=%s from an external origin page-cache authority.', (string) ($externalAuthority['header'] ?? ''), (string) ($externalAuthority['value'] ?? '')),
            );
        }

        $staleYperfEvidence = false;
        foreach ((array) ($runtime['tests'] ?? array()) as $test) {
            if (!is_array($test)) {
                continue;
            }
            $name = (string) ($test['name'] ?? '');
            if (!in_array($name, array('Homepage', 'WooCommerce shop', 'WooCommerce product', 'WooCommerce product category'), true)) {
                continue;
            }
            $signals = is_array($test['cache_signals'] ?? null) ? $test['cache_signals'] : array();
            foreach (array('x-yperf-cache' => 'Yablonsky Performance', 'x-fastcgi-cache' => 'Native FastCGI cache', 'x-cache-status' => 'HTTP page cache', 'x-cache' => 'HTTP page cache') as $header => $provider) {
                $value = strtoupper((string) ($signals[$header] ?? ''));
                if (!in_array($value, array('HIT', 'STALE', 'REVALIDATED', 'UPDATING'), true)) {
                    continue;
                }

                if ($header === 'x-yperf-cache') {
                    $executorVersion = sanitize_text_field((string) ($signals['x-yperf-cache-version'] ?? ''));
                    if (!PageCacheExecutor::enabled() || $executorVersion !== YPERF_VERSION) {
                        $staleYperfEvidence = true;
                        continue;
                    }
                }

                return array(
                    'proven' => true,
                    'provider' => $provider,
                    'reason' => sprintf('%s returned %s=%s; page-cache execution is proven for this sampled public route.', $name, $header, $value),
                );
            }
        }

        if ($staleYperfEvidence) {
            return array(
                'proven' => false,
                'provider' => '',
                'reason' => 'Stored YPerf page-cache HIT evidence belongs to a disabled or non-current executor and is not current execution proof.',
            );
        }

        return array('proven' => false, 'provider' => '', 'reason' => 'No current page-cache execution proof is available for the sampled public routes.');
    }

    /** @param array<string,mixed> $caps */
    private static function providerPageCacheEnabled(array $caps): bool
    {
        $providers = is_array($caps['providers'] ?? null) ? $caps['providers'] : array();
        foreach ($providers as $provider) {
            if (!is_array($provider)) {
                continue;
            }
            $layers = is_array($provider['layers'] ?? null) ? $provider['layers'] : array();
            $page = is_array($layers['page_cache'] ?? null) ? $layers['page_cache'] : array();
            if (!empty($page['enabled'])) {
                return true;
            }
        }
        return false;
    }

    private static function nativePageConfigured(string $state): bool
    {
        return in_array($state, array('global_native_config_visible_site_unproven', 'native_configured_status_header_not_exposed', 'native_configured_execution_unproven', 'execution_unproven'), true);
    }

    /** @param array<string,mixed> $nativeProof */
    private static function objectReason(array $nativeProof): string
    {
        $state = (string) ($nativeProof['object_cache']['redis']['state'] ?? 'unknown');
        if (in_array($state, array('high_memory_pressure', 'active_eviction_pressure'), true)) {
            return 'Redis is reachable but under memory/eviction pressure. YPerf intentionally does not add WordPress as another consumer without ownership review.';
        }
        return 'A memory engine may be available, but WordPress does not confirm an adopted persistent object-cache executor.';
    }
}
