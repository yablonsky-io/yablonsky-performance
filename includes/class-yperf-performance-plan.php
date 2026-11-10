<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class PerformancePlan
{
    public const OPTION_PROFILE = 'yperf_performance_profile';
    public const OPTION_LAST_APPLY = 'yperf_performance_plan_last_apply_v1';

    /** @param array<string,mixed> $caps @param array<string,mixed> $runtime @param array<string,mixed> $nativeProof @param array<string,mixed> $nativePlan @return array<string,mixed> */
    public static function build(array $caps, array $runtime, array $nativeProof, array $nativePlan): array
    {
        $profile = self::profile();
        $runtimeSummary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        $runtimeVersionCurrent = (string) ($runtime['version'] ?? '') === YPERF_VERSION;
        $runtimeClean = $runtime !== array() && $runtimeVersionCurrent && (int) ($runtimeSummary['fail'] ?? 0) === 0;
        $woocommerce = !empty($caps['application']['woocommerce']);
        $frontend = FrontendOptimizer::enabled();
        $guard = get_option('yperf_deep_archive_guard', '0') === '1';
        $bridge = get_option('yperf_external_purge_bridge', '0') === '1';

        $pageActive = self::pageAccelerationActive($caps, $nativeProof);
        $objectActive = !empty($caps['object_cache']['external_enabled']);
        $nativePageState = sanitize_key((string) ($nativeProof['page_cache']['state'] ?? 'not_proven'));
        $redisState = sanitize_key((string) ($nativeProof['object_cache']['redis']['state'] ?? 'unknown'));
        $originState = sanitize_key((string) ($nativeProof['origin']['state'] ?? 'unknown'));

        $recommended = 'safe';
        if ($woocommerce && $runtimeClean && self::deepArchiveWasProven($runtime)) {
            $recommended = 'commerce';
        }

        $controlCount = 1 + ($frontend ? 1 : 0) + ($guard ? 1 : 0) + ($bridge ? 1 : 0);
        $accelerationCount = ($pageActive ? 1 : 0) + ($objectActive ? 1 : 0);
        $state = 'observe';
        $label = 'Observe mode';
        if ($profile !== 'observe' || $frontend || $guard || $bridge) {
            $state = $accelerationCount > 0 ? 'active' : 'control_active';
            $label = $accelerationCount > 0 ? 'Performance active' : 'Control active · acceleration pending';
        }

        return array(
            'schema' => 2,
            'generated_at' => time(),
            'profile' => $profile,
            'recommended_profile' => $recommended,
            'profile_definition' => self::profileDefinition($profile),
            'state' => $state,
            'label' => $label,
            'runtime_clean' => $runtimeClean,
            'runtime_version_current' => $runtimeVersionCurrent,
            'controls' => array(
                'semantic_invalidation' => true,
                'frontend_optimizer' => $frontend,
                'first_hit_guard' => $guard,
                'provider_purge_bridge' => $bridge,
                'active_count' => $controlCount,
            ),
            'acceleration' => array(
                'page_cache' => $pageActive,
                'object_cache' => $objectActive,
                'active_count' => $accelerationCount,
                'native_page_state' => $nativePageState,
                'redis_state' => $redisState,
                'origin_state' => $originState,
            ),
            'native_plan' => $nativePlan,
            'last_apply' => self::lastApply(),
        );
    }

    /** @param array<string,mixed> $caps @param array<string,mixed> $runtime @param array<string,mixed> $nativeProof @return array<string,mixed> */
    public static function apply(string $profile, array $caps, array $runtime, array $nativeProof): array
    {
        $profile = in_array($profile, array('observe', 'safe', 'commerce'), true) ? $profile : 'safe';
        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        $runtimeVersionCurrent = (string) ($runtime['version'] ?? '') === YPERF_VERSION;
        $runtimeClean = $runtime !== array() && $runtimeVersionCurrent && (int) ($summary['fail'] ?? 0) === 0;
        $woocommerce = !empty($caps['application']['woocommerce']);
        $pageActive = self::pageAccelerationActive($caps, $nativeProof);

        $frontend = $profile !== 'observe';
        $guard = $profile === 'commerce' && $runtimeClean && $woocommerce;
        $bridge = $profile !== 'observe' && self::providerPageCacheEnabled($caps);

        $frontendBefore = FrontendOptimizer::settingsSnapshot();
        update_option(self::OPTION_PROFILE, $profile, false);
        update_option(FrontendOptimizer::OPTION_ENABLED, $frontend ? '1' : '0', false);
        update_option(FrontendOptimizer::OPTION_WOO_ROUTE_GATE, $frontend ? '1' : '0', false);
        update_option(FrontendOptimizer::OPTION_HEAVY_JS_DELAY, $frontend ? '1' : '0', false);
        update_option(FrontendOptimizer::OPTION_LATE_ANALYTICS, $frontend ? '1' : '0', false);
        update_option(FrontendOptimizer::OPTION_INLINE_CSS, $frontend ? '1' : '0', false);
        update_option(FrontendOptimizer::OPTION_REWRITTEN_CSS_RESOLUTION, $frontend ? '1' : '0', false);
        update_option(FrontendOptimizer::OPTION_IDLE_HEAVY_RELEASE, $frontend ? '1' : '0', false);
        update_option(FrontendOptimizer::OPTION_LCP_OBSERVED_GATE, $frontend ? '1' : '0', false);
        update_option(FrontendOptimizer::OPTION_FINAL_STYLE_MARKERS, $frontend ? '1' : '0', false);
        update_option(FrontendOptimizer::OPTION_PAINT_JQUERY_DELAY, $frontend ? '1' : '0', false);
        update_option(FrontendOptimizer::OPTION_NATIVE_DEFER, $frontend ? '1' : '0', false);
        update_option('yperf_deep_archive_guard', $guard ? '1' : '0', false);
        update_option('yperf_external_purge_bridge', $bridge ? '1' : '0', false);
        if ($frontendBefore !== FrontendOptimizer::settingsSnapshot() && PageCacheExecutor::enabled()) {
            PageCacheExecutor::purgeAll('frontend_policy_change');
        }

        $result = array(
            'time' => time(),
            'profile' => $profile,
            'profile_definition' => self::profileDefinition($profile),
            'frontend_optimizer' => $frontend,
            'first_hit_guard' => $guard,
            'provider_purge_bridge' => $bridge,
            'server_configuration_changed' => false,
            'provider_configuration_changed' => false,
            'runtime_clean_at_apply' => $runtimeClean,
            'runtime_version_current_at_apply' => $runtimeVersionCurrent,
            'notes' => $guard
                ? 'WordPress + WooCommerce protection profile applied. Native/server layers remain unchanged.'
                : 'Local performance controls applied. Native/server layers remain unchanged.',
        );
        update_option(self::OPTION_LAST_APPLY, $result, false);
        Telemetry::recordPerformancePlan($result);
        return $result;
    }

    /** @return array<string,mixed> */
    public static function profileDefinition(string $profile): array
    {
        $profile = in_array($profile, array('observe', 'safe', 'commerce'), true) ? $profile : 'observe';
        if ($profile === 'commerce') {
            return array(
                'label' => 'WordPress + WooCommerce Performance',
                'changes' => array(
                    'Page-cache authority: preserve a proven external executor; otherwise activate and verify YPerf Page Cache',
                    'Browser-cache policy: coordinate with the active page-cache authority',
                    'WordPress frontend delivery optimization: ON (route-aware WooCommerce gate + paint-first dependency scheduling + interaction-aware heavy JS and analytics scheduling)',
                    'WooCommerce bounded deep-archive first-hit query guard: ON when a matching workload is proven',
                    'Semantic purge / prewarm controls: available',
                    'Nginx / Redis / PHP-FPM configuration: unchanged',
                ),
            );
        }
        if ($profile === 'safe') {
            return array(
                'label' => 'WordPress Safe Performance',
                'changes' => array(
                    'Page-cache authority: preserve a proven external executor; otherwise activate and verify YPerf Page Cache',
                    'Browser-cache policy: coordinate with the active page-cache authority',
                    'WordPress frontend delivery optimization: ON (route-aware WooCommerce gate + paint-first dependency scheduling + interaction-aware heavy JS and analytics scheduling)',
                    'WooCommerce first-hit query guard: OFF unless you choose the Commerce profile',
                    'Semantic purge / prewarm controls: available',
                    'Nginx / Redis / PHP-FPM configuration: unchanged',
                ),
            );
        }
        return array(
            'label' => 'Observe',
            'changes' => array(
                'Local frontend compatibility overhead reduction: OFF',
                'WooCommerce first-hit query guard: OFF',
                'Semantic provider purge bridge: OFF',
                'Diagnostics, telemetry, benchmark, purge and prewarm remain available',
            ),
        );
    }

    /** @return array<string,string> */
    public static function settingsSnapshot(): array
    {
        return array_merge(
            array(self::OPTION_PROFILE => self::profile()),
            FrontendOptimizer::settingsSnapshot(),
            array(
                'yperf_deep_archive_guard' => get_option('yperf_deep_archive_guard', '0') === '1' ? '1' : '0',
                'yperf_external_purge_bridge' => get_option('yperf_external_purge_bridge', '0') === '1' ? '1' : '0',
            )
        );
    }

    /** @param array<string,string> $snapshot */
    public static function restore(array $snapshot): void
    {
        update_option(self::OPTION_PROFILE, in_array((string) ($snapshot[self::OPTION_PROFILE] ?? ''), array('observe', 'safe', 'commerce'), true) ? (string) $snapshot[self::OPTION_PROFILE] : 'observe', false);
        $frontendBefore = FrontendOptimizer::settingsSnapshot();
        FrontendOptimizer::restoreSettings($snapshot);
        foreach (array('yperf_deep_archive_guard', 'yperf_external_purge_bridge') as $key) {
            update_option($key, (string) ($snapshot[$key] ?? '0') === '1' ? '1' : '0', false);
        }
        if ($frontendBefore !== FrontendOptimizer::settingsSnapshot() && PageCacheExecutor::enabled()) {
            PageCacheExecutor::purgeAll('frontend_policy_restore');
        }
    }

    /** @param array<string,mixed> $acceptance */
    public static function markAcceptance(array $acceptance, bool $rolledBack): void
    {
        $last = self::lastApply();
        if ($last === array()) {
            return;
        }
        $summary = is_array($acceptance['summary'] ?? null) ? $acceptance['summary'] : array();
        $last['acceptance'] = array(
            'pass' => (int) ($summary['pass'] ?? 0),
            'fail' => (int) ($summary['fail'] ?? 0),
            'skip' => (int) ($summary['skip'] ?? 0),
            'attention' => (int) ($summary['attention'] ?? 0),
        );
        $last['outcome'] = $rolledBack ? 'rolled_back' : 'accepted';
        $last['rolled_back'] = $rolledBack;
        update_option(self::OPTION_LAST_APPLY, $last, false);
    }

    public static function profile(): string
    {
        $profile = sanitize_key((string) get_option(self::OPTION_PROFILE, 'observe'));
        return in_array($profile, array('observe', 'safe', 'commerce'), true) ? $profile : 'observe';
    }

    /** @return array<string,mixed> */
    public static function lastApply(): array
    {
        $value = get_option(self::OPTION_LAST_APPLY, array());
        return is_array($value) ? $value : array();
    }

    /** @param array<string,mixed> $caps */
    private static function providerPageCacheEnabled(array $caps): bool
    {
        $providers = is_array($caps['providers'] ?? null) ? $caps['providers'] : array();
        $w3tc = is_array($providers['w3-total-cache'] ?? null) ? $providers['w3-total-cache'] : array();
        $layers = is_array($w3tc['layers'] ?? null) ? $w3tc['layers'] : array();
        $page = is_array($layers['page_cache'] ?? null) ? $layers['page_cache'] : array();
        return !empty($page['enabled']);
    }

    /** @param array<string,mixed> $caps @param array<string,mixed> $proof */
    private static function pageAccelerationActive(array $caps, array $proof): bool
    {
        unset($caps);
        $state = (string) ($proof['page_cache']['state'] ?? '');
        return in_array($state, array('miss_then_hit_confirmed', 'hit_confirmed'), true);
    }

    /** @param array<string,mixed> $runtime */
    private static function deepArchiveWasProven(array $runtime): bool
    {
        foreach ((array) ($runtime['tests'] ?? array()) as $test) {
            if (!is_array($test) || (string) ($test['name'] ?? '') !== 'WooCommerce deep pagination') {
                continue;
            }
            return (string) ($test['status'] ?? '') === 'pass';
        }
        return false;
    }
}
