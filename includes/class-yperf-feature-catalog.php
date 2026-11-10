<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class FeatureCatalog
{
    /**
     * @param array<string,mixed> $caps
     * @param array<string,mixed> $runtime
     * @param array<string,mixed> $effectiveness
     * @return array<string,array<string,mixed>>
     */
    public static function build(array $caps, array $runtime, bool $guardEnabled, array $effectiveness = array()): array
    {
        $pageProviders = (array) ($caps['page_cache']['known_page_cache_plugins'] ?? array());
        $pageProvider = $pageProviders !== array() ? implode(', ', array_map('strval', $pageProviders)) : '';
        $hasPageLayer = !empty($caps['page_cache']['advanced_cache_dropin']) || $pageProvider !== '';
        $objectActive = !empty($caps['object_cache']['external_enabled']);
        $objectProvider = self::providerList((array) ($caps['object_cache']['known_object_cache_plugins'] ?? array()));
        $opcacheEnabled = !empty($caps['opcode_cache']['enabled']);
        $edge = self::edgeProvider($runtime);
        $effectiveLayers = is_array($effectiveness['layers'] ?? null) ? $effectiveness['layers'] : array();
        $frontendExecutionState = sanitize_key((string) (($effectiveLayers['yperf_frontend']['state'] ?? (FrontendOptimizer::enabled() ? 'armed' : 'off'))));
        $guardExecutionState = sanitize_key((string) (($effectiveLayers['first_hit_guard']['state'] ?? ($guardEnabled ? 'armed' : 'off'))));
        $providers = is_array($caps['providers'] ?? null) ? $caps['providers'] : array();
        $w3tc = is_array($providers['w3-total-cache'] ?? null) ? $providers['w3-total-cache'] : array();
        $w3tcReadable = !empty($w3tc['config_readable']);
        $w3Page = self::providerLayer($w3tc, 'page_cache');
        $w3Db = self::providerLayer($w3tc, 'database_cache');
        $w3Object = self::providerLayer($w3tc, 'object_cache');
        $w3Browser = self::providerLayer($w3tc, 'browser_cache');
        $w3Minify = self::providerLayer($w3tc, 'minify');
        $w3Fragment = self::providerLayer($w3tc, 'fragment_cache');
        $hasW3tc = $w3tc !== array() || in_array('W3 Total Cache', $pageProviders, true);

        $pageExec = PageCacheExecutor::status();
        $pageRecord = is_array($pageExec['record'] ?? null) ? $pageExec['record'] : array();
        $pageProof = is_array($pageRecord['last_proof'] ?? null) ? $pageRecord['last_proof'] : array();
        $pageProofCurrent = !empty($pageProof['ok']) && (string) ($pageProof['version'] ?? '') === YPERF_VERSION;
        $pageEnabled = !empty($pageExec['enabled']);
        $pageState = $pageEnabled && $pageProofCurrent ? 'active' : ($pageEnabled ? 'inspect' : 'off');
        $pageStatus = $pageState === 'active'
            ? __('Runtime-proven active', 'yablonsky-performance')
            : ($pageEnabled ? __('Enabled · proof pending', 'yablonsky-performance') : __('Off', 'yablonsky-performance'));
        $pageProviderText = __('Yablonsky Performance', 'yablonsky-performance');
        $pageNext = $pageState === 'active'
            ? __('Keep MISS → HIT proof healthy and use selective purge / prewarm for changes.', 'yablonsky-performance')
            : ($pageEnabled ? __('Run execution proof or disable and re-enable if the installed drop-in belongs to an older YPerf version.', 'yablonsky-performance') : __('Enable the YPerf-owned Page Cache only after provider ownership is safe for handover.', 'yablonsky-performance'));

        $objectExec = ObjectCacheExecutor::status();
        $objectTruth = sanitize_key((string) ($objectExec['state'] ?? 'inactive'));
        $objectActive = !empty($objectExec['active']);
        $objectNeedsReview = in_array($objectTruth, array('pending_verification', 'dropin_present_not_active', 'upgrade_reactivation_required'), true);
        $objectProviderText = $objectActive ? __('Yablonsky Performance · Redis', 'yablonsky-performance') : __('Yablonsky Performance · Redis candidate', 'yablonsky-performance');

        return array(
            'page-cache' => self::feature(
                __('Page Cache', 'yablonsky-performance'),
                __('Cache', 'yablonsky-performance'),
                $pageState,
                $pageStatus,
                $pageProviderText,
                __('Stores complete public responses so repeat visitors can avoid full WordPress execution.', 'yablonsky-performance'),
                __('YPerf owns the portable WordPress page-cache executor, cacheability policy, selective purge, prewarm, execution proof and rollback. Native server cache remains a separate adoption path.', 'yablonsky-performance'),
                $pageNext
            ),
            'object-cache' => self::feature(
                __('Object Cache', 'yablonsky-performance'),
                __('Cache', 'yablonsky-performance'),
                $objectActive ? 'active' : ($objectNeedsReview ? 'inspect' : 'off'),
                $objectActive ? __('Runtime-proven active', 'yablonsky-performance') : ($objectNeedsReview ? __('Enabled · proof pending', 'yablonsky-performance') : __('Off', 'yablonsky-performance')),
                $objectProviderText,
                __('Keeps reusable WordPress objects and repeated query results in persistent memory.', 'yablonsky-performance'),
                __('YPerf treats Redis/Valkey as bounded working memory with isolation, maxmemory, hit/miss and eviction visibility.', 'yablonsky-performance'),
                $objectActive ? __('Inspect isolation, memory policy and eviction health.', 'yablonsky-performance') : ($objectNeedsReview ? __('Disable & Roll Back Object Cache', 'yablonsky-performance') : __('Benchmark available memory engines before enabling a persistent object-cache drop-in.', 'yablonsky-performance'))
            ),
            'database-cache' => self::feature(
                __('Database Cache', 'yablonsky-performance'),
                __('Cache', 'yablonsky-performance'),
                $w3tcReadable ? (!empty($w3Db['enabled']) ? 'active' : 'off') : ($hasW3tc ? 'inspect' : 'roadmap'),
                $w3tcReadable ? (!empty($w3Db['enabled']) ? __('External layer active', 'yablonsky-performance') : __('Provider installed · cache off', 'yablonsky-performance')) : ($hasW3tc ? __('Provider capability detected', 'yablonsky-performance') : __('Roadmap · not in current market baseline', 'yablonsky-performance')),
                $w3tcReadable ? 'W3 Total Cache · ' . (string) ($w3Db['engine'] ?? __('None', 'yablonsky-performance')) : ($hasW3tc ? 'W3 Total Cache' : __('Future YPerf module', 'yablonsky-performance')),
                __('Caches selected database-query results. It can help some workloads, but can also add churn or disk overhead.', 'yablonsky-performance'),
                __('YPerf will not enable database query caching just to increase a feature count. It must prove a measurable benefit beside object cache and real database indexes.', 'yablonsky-performance'),
                __('Not part of the current market baseline. Keep using object-cache evidence and origin-cost diagnostics for current decisions.', 'yablonsky-performance')
            ),
            'opcode-cache' => self::feature(
                __('Opcode Cache', 'yablonsky-performance'),
                __('Cache', 'yablonsky-performance'),
                $opcacheEnabled ? 'active' : 'inspect',
                $opcacheEnabled ? __('PHP OPcache active', 'yablonsky-performance') : __('Needs host inspection', 'yablonsky-performance'),
                __('PHP runtime', 'yablonsky-performance'),
                __('Stores compiled PHP bytecode in memory so PHP files do not need to be parsed and compiled on every request.', 'yablonsky-performance'),
                __('This is a PHP runtime capability, not something YPerf should imitate in WordPress.', 'yablonsky-performance'),
                $opcacheEnabled ? __('Expose safe status and health signals.', 'yablonsky-performance') : __('Confirm host-level OPcache availability without changing PHP configuration.', 'yablonsky-performance')
            ),
            'browser-cache' => self::feature(
                __('Browser Cache', 'yablonsky-performance'),
                __('Delivery', 'yablonsky-performance'),
                BrowserCacheController::enabled() ? 'active' : 'off',
                BrowserCacheController::enabled() ? __('YPerf response policy active', 'yablonsky-performance') : __('Off', 'yablonsky-performance'),
                BrowserCacheController::enabled() ? __('Yablonsky Performance', 'yablonsky-performance') : ($w3tcReadable && !empty($w3Browser['enabled']) ? __('External: W3 Total Cache', 'yablonsky-performance') : __('Yablonsky Performance', 'yablonsky-performance')),
                __('Controls browser reuse of YPerf-owned public HTML through conservative Cache-Control and Vary response headers.', 'yablonsky-performance'),
                __('YPerf changes browser HTML caching only when its own Page Cache is active and the request policy classifies the request as public/cacheable.', 'yablonsky-performance'),
                BrowserCacheController::enabled() ? __('Keep the response-header proof healthy.', 'yablonsky-performance') : __('Enable only after YPerf Page Cache has proved MISS → HIT.', 'yablonsky-performance')
            ),
            'fragment-cache' => self::feature(
                __('Fragment Cache', 'yablonsky-performance'),
                __('Cache', 'yablonsky-performance'),
                !empty($w3Fragment['enabled']) ? (!empty($w3Fragment['configured']) ? 'active' : 'inspect') : 'roadmap',
                !empty($w3Fragment['enabled']) ? (!empty($w3Fragment['configured']) ? __('External extension configured', 'yablonsky-performance') : __('Extension active · not configured', 'yablonsky-performance')) : __('Roadmap · not in current market baseline', 'yablonsky-performance'),
                !empty($w3Fragment['enabled']) ? 'W3 Total Cache' : __('Future YPerf module', 'yablonsky-performance'),
                __('Caches expensive reusable fragments without caching an entire personalized page.', 'yablonsky-performance'),
                __('Useful only with explicit fragment boundaries and invalidation contracts. YPerf will not guess dynamic WooCommerce fragments.', 'yablonsky-performance'),
                __('Not part of the current market baseline. A future fragment API requires explicit boundaries and invalidation contracts.', 'yablonsky-performance')
            ),
            'minify' => self::feature(
                __('Minify & Assets', 'yablonsky-performance'),
                __('Delivery', 'yablonsky-performance'),
                $w3tcReadable ? (!empty($w3Minify['enabled']) ? 'active' : 'off') : ($hasW3tc ? 'inspect' : 'roadmap'),
                $w3tcReadable ? (!empty($w3Minify['enabled']) ? __('External asset optimization active', 'yablonsky-performance') : __('Provider installed · minify off', 'yablonsky-performance')) : ($hasW3tc ? __('Provider capability detected', 'yablonsky-performance') : __('Roadmap · not in current market baseline', 'yablonsky-performance')),
                $w3tcReadable ? 'W3 Total Cache · ' . (string) ($w3Minify['engine'] ?? __('None', 'yablonsky-performance')) : ($hasW3tc ? 'W3 Total Cache' : __('Future YPerf module', 'yablonsky-performance')),
                __('Reduces and optimizes CSS/JavaScript/HTML delivery, with compatibility-sensitive combine/defer/delay decisions.', 'yablonsky-performance'),
                __('Asset optimization is separate from cache correctness. Every change needs frontend acceptance and rollback.', 'yablonsky-performance'),
                __('Not part of the current market baseline. Asset write controls require inventory, safe presets and per-file rollback first.', 'yablonsky-performance')
            ),
            'frontend-optimizer' => self::feature(
                __('Frontend Overhead', 'yablonsky-performance'),
                __('Delivery', 'yablonsky-performance'),
                $frontendExecutionState === 'active' ? 'active' : ($frontendExecutionState === 'armed' ? 'inspect' : 'off'),
                $frontendExecutionState === 'active' ? __('Runtime-proven active', 'yablonsky-performance') : ($frontendExecutionState === 'armed' ? __('Enabled · proof pending', 'yablonsky-performance') : __('Off', 'yablonsky-performance')),
                __('Yablonsky Performance', 'yablonsky-performance'),
                __('Reduces non-critical frontend execution: removes emoji overhead, gates transactional WooCommerce assets away from non-transactional routes, and schedules heavyweight WordPress/WooCommerce JavaScript plus analytics after first user intent or a bounded late timeout.', 'yablonsky-performance'),
                __('The delivery engine is route-aware, skips cart/checkout/account and logged-in traffic, preserves theme/jQuery execution, and is covered by Runtime Acceptance plus automatic rollback.', 'yablonsky-performance'),
                FrontendOptimizer::enabled() ? __('Keep enabled while Runtime Acceptance remains healthy.', 'yablonsky-performance') : __('Enable through Optimize → WordPress Safe Performance.', 'yablonsky-performance')
            ),
            'edge-cdn' => self::feature(
                __('CDN / Edge Cache', 'yablonsky-performance'),
                __('Delivery', 'yablonsky-performance'),
                $edge !== '' ? 'detected' : 'planned',
                $edge !== '' ? __('Edge detected', 'yablonsky-performance') : __('Adapter planned', 'yablonsky-performance'),
                $edge !== '' ? $edge : __('Cloudflare / compatible CDN', 'yablonsky-performance'),
                __('Moves static or full-page delivery closer to visitors and can shield the origin from repeat traffic.', 'yablonsky-performance'),
                __('Edge cache complements first-hit cost control and origin concurrency protection.', 'yablonsky-performance'),
                $edge !== '' ? __('Inspect edge cache status and purge contract.', 'yablonsky-performance') : __('Add provider-neutral edge adapter after native page-cache adoption.', 'yablonsky-performance')
            ),
            'selective-purge' => self::feature(
                __('Selective Purge', 'yablonsky-performance'),
                __('Control', 'yablonsky-performance'),
                'active',
                __('Manual + semantic purge ready', 'yablonsky-performance'),
                __('Yablonsky Performance', 'yablonsky-performance'),
                __('Invalidates only affected URLs instead of flushing the whole cache for routine content changes.', 'yablonsky-performance'),
                __('YMulti/YSEO and other plugins emit semantic invalidation intent; YPerf remains the execution authority.', 'yablonsky-performance'),
                __('Manual provider purge is available now; automatic bridge remains opt-in during migration.', 'yablonsky-performance')
            ),
            'prewarm' => self::feature(
                __('Prewarm', 'yablonsky-performance'),
                __('Control', 'yablonsky-performance'),
                'active',
                __('Bounded prewarm ready', 'yablonsky-performance'),
                __('Yablonsky Performance', 'yablonsky-performance'),
                __('Warms selected high-value URLs after purge or deployment without generating an uncontrolled crawl storm.', 'yablonsky-performance'),
                __('Prewarm must be bounded, prioritized and aware of origin budget.', 'yablonsky-performance'),
                __('Run bounded warm-up after purge or deployment; native cache adapters can reuse the same URL plan.', 'yablonsky-performance')
            ),
            'cache-statistics' => self::feature(
                __('Caching Statistics', 'yablonsky-performance'),
                __('Observability', 'yablonsky-performance'),
                'active',
                __('Local telemetry active', 'yablonsky-performance'),
                __('Yablonsky Performance', 'yablonsky-performance'),
                __('Tracks Runtime Acceptance, benchmark, purge and prewarm activity now; native adapters can add HIT/MISS/BYPASS/STALE, object-cache hit ratio and eviction counters.', 'yablonsky-performance'),
                __('Statistics must distinguish edge, page, object and origin behavior instead of collapsing everything into one hit rate.', 'yablonsky-performance'),
                __('Local operation history now includes purge execution timing and bounded prewarm HIT proof; native adapters may add more layer-specific counters later.', 'yablonsky-performance')
            ),
            'purge-audit' => self::feature(
                __('Purge Logs', 'yablonsky-performance'),
                __('Observability', 'yablonsky-performance'),
                'active',
                __('Local audit enabled', 'yablonsky-performance'),
                __('Yablonsky Performance', 'yablonsky-performance'),
                __('Records what invalidation was requested, when, why and which URLs were involved.', 'yablonsky-performance'),
                __('The log is local, bounded and intended for debugging invalidation storms and unexpected cache churn.', 'yablonsky-performance'),
                __('Execution result, provider confirmation and duration are recorded for local purge operations.', 'yablonsky-performance')
            ),
            'pagespeed' => self::feature(
                __('PageSpeed / Web Vitals', 'yablonsky-performance'),
                __('Observability', 'yablonsky-performance'),
                'active',
                __('Local diagnostics active', 'yablonsky-performance'),
                __('Local response timing + optional external data', 'yablonsky-performance'),
                __('Correlates user-visible speed metrics with cache and origin behavior.', 'yablonsky-performance'),
                __('A score is diagnostic evidence, not a cache engine. YPerf should explain why a page is slow and which layer owns the cost.', 'yablonsky-performance'),
                __('Runtime Acceptance and Benchmark provide local response timing now; external lab/field APIs remain optional integrations.', 'yablonsky-performance')
            ),
            'first-hit-guard' => self::feature(
                __('First-hit Query Guard', 'yablonsky-performance'),
                __('Protection', 'yablonsky-performance'),
                $guardExecutionState === 'active' ? 'active' : ($guardExecutionState === 'armed' ? 'inspect' : 'off'),
                $guardExecutionState === 'active' ? __('Runtime-proven active', 'yablonsky-performance') : ($guardExecutionState === 'armed' ? __('Armed · workload proof pending', 'yablonsky-performance') : __('Off', 'yablonsky-performance')),
                __('Yablonsky Performance', 'yablonsky-performance'),
                __('Makes expensive uncached WooCommerce archive requests cheaper before they can overwhelm PHP and MariaDB.', 'yablonsky-performance'),
                __('This is a core YPerf advantage: first-hit work is treated separately from repeat-hit cache.', 'yablonsky-performance'),
                $guardEnabled ? __('Keep under runtime acceptance and workload telemetry.', 'yablonsky-performance') : __('Remain off until site-specific eligibility and semantics are proven.', 'yablonsky-performance')
            ),
            'origin-survival' => self::feature(
                __('Origin Survival', 'yablonsky-performance'),
                __('Protection', 'yablonsky-performance'),
                $guardEnabled ? 'active' : 'available',
                $guardEnabled ? __('Crawler admission armed', 'yablonsky-performance') : __('Ready in current build', 'yablonsky-performance'),
                __('Yablonsky Performance · Managed Early Runtime', 'yablonsky-performance'),
                __('Keeps crawler cold-cache work inside a small PHP-worker budget while cached responses continue on their fastest available path.', 'yablonsky-performance'),
                __('The portable MU layer provides local origin protection on standard WordPress hosting. Native server and edge adapters can extend protection earlier in the request path.', 'yablonsky-performance'),
                $guardEnabled ? __('Review Runtime Acceptance evidence and keep the crawler budget aligned with origin capacity.', 'yablonsky-performance') : __('Enable WordPress Safe Performance to arm the portable crawler-origin budget.', 'yablonsky-performance')
            ),
            'origin-budget' => self::feature(
                __('Origin Budget', 'yablonsky-performance'),
                __('Protection', 'yablonsky-performance'),
                !empty($caps['origin']['fpm_candidate']) ? 'detected' : 'inspect',
                !empty($caps['origin']['fpm_candidate']) ? __('PHP-FPM detected', 'yablonsky-performance') : __('Needs host inspection', 'yablonsky-performance'),
                __('PHP-FPM / origin workers', 'yablonsky-performance'),
                __('Bounds concurrent uncached work so a burst of unique requests cannot consume the entire server.', 'yablonsky-performance'),
                __('Origin Budget provides the concurrency reserve that complements cache delivery.', 'yablonsky-performance'),
                __('Add privileged read-only host helper, then guarded diff/apply/rollback.', 'yablonsky-performance')
            ),
            'query-cost-telemetry' => self::feature(
                __('Query Cost Telemetry', 'yablonsky-performance'),
                __('Observability', 'yablonsky-performance'),
                (int) (($runtime['origin_cost_summary']['measured_routes'] ?? 0)) > 0 ? 'active' : 'available',
                (int) (($runtime['origin_cost_summary']['measured_routes'] ?? 0)) > 0
                    ? sprintf(
                        /* translators: %d: measured frontend routes */
                        __('Probe-bounded · %d routes measured', 'yablonsky-performance'),
                        (int) ($runtime['origin_cost_summary']['measured_routes'] ?? 0)
                    )
                    : __('Ready on next Runtime Acceptance', 'yablonsky-performance'),
                __('Yablonsky Performance', 'yablonsky-performance'),
                __('Measures WordPress database query count, aggregate DB time, slow-query counts, query families and SQL_CALC_FOUND_ROWS use on signed Runtime Acceptance probes.', 'yablonsky-performance'),
                __('Telemetry runs only on signed YPerf probe requests. Normal visitor traffic does not enable query recording, and raw SQL is never exported.', 'yablonsky-performance'),
                __('Use the heaviest measured route to decide whether query guard, application tuning or host/database investigation should come next.', 'yablonsky-performance')
            ),
        );
    }

    /** @return array<string,mixed> */
    private static function feature(string $name, string $group, string $state, string $status, string $provider, string $what, string $policy, string $next): array
    {
        return array(
            'name'       => $name,
            'group'      => $group,
            'state'      => $state,
            'status'     => $status,
            'provider'   => $provider,
            'what'       => $what,
            'policy'     => $policy,
            'next'       => $next,
            'local_free' => true,
        );
    }

    /** @param array<string,mixed> $provider @return array<string,mixed> */
    private static function providerLayer(array $provider, string $layer): array
    {
        $layers = is_array($provider['layers'] ?? null) ? $provider['layers'] : array();
        return is_array($layers[$layer] ?? null) ? $layers[$layer] : array();
    }

    /** @param array<int,mixed> $providers */
    private static function providerList(array $providers): string
    {
        $clean = array();
        foreach ($providers as $provider) {
            $provider = trim((string) $provider);
            if ($provider !== '') {
                $clean[] = $provider;
            }
        }
        return implode(', ', array_values(array_unique($clean)));
    }

    /** @param array<string,mixed> $caps */
    private static function serverProvider(array $caps): string
    {
        $family = strtoupper((string) ($caps['web_server']['family'] ?? 'unknown'));
        return $family !== 'UNKNOWN' ? $family : __('Native server adapter', 'yablonsky-performance');
    }

    /** @param array<string,mixed> $runtime */
    private static function edgeProvider(array $runtime): string
    {
        foreach ((array) ($runtime['tests'] ?? array()) as $test) {
            if (!is_array($test)) {
                continue;
            }
            $signals = is_array($test['cache_signals'] ?? null) ? $test['cache_signals'] : array();
            $server = strtolower((string) ($signals['server'] ?? ''));
            if ($server === 'cloudflare' || isset($signals['cf-cache-status'])) {
                return 'Cloudflare';
            }
        }
        return '';
    }
}
