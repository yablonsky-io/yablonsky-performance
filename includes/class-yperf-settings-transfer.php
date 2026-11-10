<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/** Portable YPerf configuration backup/import. Host evidence and secrets are excluded. */
final class SettingsTransfer
{
    private const SCHEMA = 1;

    /** @return array<string,mixed> */
    public static function export(): array
    {
        return array(
            'schema' => self::SCHEMA,
            'product' => 'Yablonsky Performance',
            'exported_at' => time(),
            'source_version' => YPERF_VERSION,
            'settings' => array(
                'performance_profile' => PerformancePlan::profile(),
                'page_cache' => PageCacheExecutor::enabled(),
                'browser_cache' => BrowserCacheController::enabled(),
                'frontend_optimization' => FrontendOptimizer::enabled(),
                'frontend_woo_route_gate' => FrontendOptimizer::wooRouteGateEnabled(),
                'frontend_heavy_js_delay' => FrontendOptimizer::heavyJsDelayEnabled(),
                'frontend_late_analytics' => FrontendOptimizer::lateAnalyticsEnabled(),
                'frontend_inline_css' => FrontendOptimizer::inlineCssEnabled(),
                'frontend_rewritten_css_resolution' => FrontendOptimizer::rewrittenCssResolutionEnabled(),
                'frontend_idle_heavy_release' => FrontendOptimizer::idleHeavyReleaseEnabled(),
                'frontend_lcp_observed_gate' => FrontendOptimizer::lcpObservedGateEnabled(),
                'frontend_final_style_markers' => FrontendOptimizer::finalStyleMarkersEnabled(),
                'frontend_paint_jquery_delay' => FrontendOptimizer::paintJqueryDelayEnabled(),
                'frontend_native_defer' => FrontendOptimizer::nativeDeferEnabled(),
                'first_hit_guard' => get_option('yperf_deep_archive_guard', '0') === '1',
                'semantic_purge_bridge' => get_option('yperf_external_purge_bridge', '0') === '1',
            ),
            'excluded' => array(
                'api_keys',
                'runtime_evidence',
                'host_snapshots',
                'redis_reservations',
                'object_cache_runtime',
                'native_server_authority',
                'migration_backups',
            ),
        );
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    public static function import(array $payload): array
    {
        if ((int) ($payload['schema'] ?? 0) !== self::SCHEMA || (string) ($payload['product'] ?? '') !== 'Yablonsky Performance') {
            return array('ok' => false, 'message' => __('This file is not a supported Yablonsky Performance settings backup.', 'yablonsky-performance'));
        }
        $settings = is_array($payload['settings'] ?? null) ? $payload['settings'] : array();
        if ($settings === array()) {
            return array('ok' => false, 'message' => __('The settings backup does not contain any portable YPerf settings.', 'yablonsky-performance'));
        }

        $before = self::snapshot();
        $desiredPage = !empty($settings['page_cache']);
        $desiredBrowser = !empty($settings['browser_cache']);
        $desiredFrontend = !empty($settings['frontend_optimization']);
        $desiredWooGate = array_key_exists('frontend_woo_route_gate', $settings) ? !empty($settings['frontend_woo_route_gate']) : false;
        $desiredHeavyDelay = array_key_exists('frontend_heavy_js_delay', $settings) ? !empty($settings['frontend_heavy_js_delay']) : false;
        $desiredLateAnalytics = array_key_exists('frontend_late_analytics', $settings) ? !empty($settings['frontend_late_analytics']) : false;
        $desiredInlineCss = array_key_exists('frontend_inline_css', $settings) ? !empty($settings['frontend_inline_css']) : false;
        $desiredCssAlias = array_key_exists('frontend_rewritten_css_resolution', $settings) ? !empty($settings['frontend_rewritten_css_resolution']) : false;
        $desiredIdleRelease = array_key_exists('frontend_idle_heavy_release', $settings) ? !empty($settings['frontend_idle_heavy_release']) : false;
        $desiredLcpObservedGate = array_key_exists('frontend_lcp_observed_gate', $settings) ? !empty($settings['frontend_lcp_observed_gate']) : false;
        $desiredFinalStyleMarkers = array_key_exists('frontend_final_style_markers', $settings) ? !empty($settings['frontend_final_style_markers']) : false;
        $desiredPaintJqueryDelay = array_key_exists('frontend_paint_jquery_delay', $settings) ? !empty($settings['frontend_paint_jquery_delay']) : false;
        $desiredNativeDefer = array_key_exists('frontend_native_defer', $settings) ? !empty($settings['frontend_native_defer']) : false;
        $desiredFirstHit = !empty($settings['first_hit_guard']);
        $desiredBridge = !empty($settings['semantic_purge_bridge']);
        $profile = sanitize_key((string) ($settings['performance_profile'] ?? 'safe'));
        if (!in_array($profile, array('observe', 'safe', 'commerce'), true)) {
            $profile = 'safe';
        }

        try {
            update_option('yperf_performance_profile', $profile, false);
            update_option(FrontendOptimizer::OPTION_ENABLED, $desiredFrontend ? '1' : '0', false);
            update_option(FrontendOptimizer::OPTION_WOO_ROUTE_GATE, $desiredWooGate ? '1' : '0', false);
            update_option(FrontendOptimizer::OPTION_HEAVY_JS_DELAY, $desiredHeavyDelay ? '1' : '0', false);
            update_option(FrontendOptimizer::OPTION_LATE_ANALYTICS, $desiredLateAnalytics ? '1' : '0', false);
            update_option(FrontendOptimizer::OPTION_INLINE_CSS, $desiredInlineCss ? '1' : '0', false);
            update_option(FrontendOptimizer::OPTION_REWRITTEN_CSS_RESOLUTION, $desiredCssAlias ? '1' : '0', false);
            update_option(FrontendOptimizer::OPTION_IDLE_HEAVY_RELEASE, $desiredIdleRelease ? '1' : '0', false);
            update_option(FrontendOptimizer::OPTION_LCP_OBSERVED_GATE, $desiredLcpObservedGate ? '1' : '0', false);
            update_option(FrontendOptimizer::OPTION_FINAL_STYLE_MARKERS, $desiredFinalStyleMarkers ? '1' : '0', false);
            update_option(FrontendOptimizer::OPTION_PAINT_JQUERY_DELAY, $desiredPaintJqueryDelay ? '1' : '0', false);
            update_option(FrontendOptimizer::OPTION_NATIVE_DEFER, $desiredNativeDefer ? '1' : '0', false);
            if (PageCacheExecutor::enabled()) {
                PageCacheExecutor::purgeAll('settings_import_frontend');
            }
            update_option('yperf_deep_archive_guard', $desiredFirstHit ? '1' : '0', false);
            update_option('yperf_external_purge_bridge', $desiredBridge ? '1' : '0', false);

            if ($desiredPage) {
                $repair = PageCacheExecutor::repair();
                if (empty($repair['ok'])) {
                    throw new \RuntimeException((string) ($repair['message'] ?? __('Page Cache could not be enabled from the imported configuration.', 'yablonsky-performance')));
                }
            } elseif (PageCacheExecutor::enabled()) {
                $disable = PageCacheExecutor::disable(false);
                if (empty($disable['ok'])) {
                    throw new \RuntimeException((string) ($disable['message'] ?? __('Page Cache could not be disabled from the imported configuration.', 'yablonsky-performance')));
                }
            }

            if ($desiredBrowser) {
                if (!PageCacheExecutor::enabled()) {
                    throw new \RuntimeException(__('Browser Cache requires Page Cache to be enabled.', 'yablonsky-performance'));
                }
                if (!BrowserCacheController::setEnabled(true)) {
                    throw new \RuntimeException(__('Browser Cache could not be enabled safely.', 'yablonsky-performance'));
                }
                $browserProof = BrowserCacheController::prove();
                if (empty($browserProof['ok'])) {
                    BrowserCacheController::setEnabled(false);
                    throw new \RuntimeException(__('Browser Cache proof failed, so the imported change was rolled back.', 'yablonsky-performance'));
                }
            } else {
                BrowserCacheController::setEnabled(false);
            }

            $runtime = RuntimeAcceptance::run();
            $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
            if ((int) ($summary['fail'] ?? 0) > 0) {
                throw new \RuntimeException(__('The imported configuration failed the final Health Check.', 'yablonsky-performance'));
            }
            update_option('yperf_runtime_acceptance_v1', $runtime, false);
            Telemetry::recordRuntime($runtime);
            RuntimeHistory::record($runtime, 'settings_import');

            return array('ok' => true, 'message' => __('Settings imported and verified successfully.', 'yablonsky-performance'));
        } catch (\Throwable $throwable) {
            self::restore($before);
            return array('ok' => false, 'message' => sanitize_text_field($throwable->getMessage()));
        }
    }

    /** @return array<string,mixed> */
    public static function snapshot(): array
    {
        return array(
            'performance_profile' => PerformancePlan::profile(),
            'page_cache' => PageCacheExecutor::enabled(),
            'browser_cache' => BrowserCacheController::enabled(),
            'frontend_optimization' => FrontendOptimizer::enabled(),
            'frontend_woo_route_gate' => FrontendOptimizer::wooRouteGateEnabled(),
            'frontend_heavy_js_delay' => FrontendOptimizer::heavyJsDelayEnabled(),
            'frontend_late_analytics' => FrontendOptimizer::lateAnalyticsEnabled(),
            'frontend_inline_css' => FrontendOptimizer::inlineCssEnabled(),
            'frontend_rewritten_css_resolution' => FrontendOptimizer::rewrittenCssResolutionEnabled(),
            'frontend_idle_heavy_release' => FrontendOptimizer::idleHeavyReleaseEnabled(),
            'frontend_lcp_observed_gate' => FrontendOptimizer::lcpObservedGateEnabled(),
            'frontend_final_style_markers' => FrontendOptimizer::finalStyleMarkersEnabled(),
            'frontend_paint_jquery_delay' => FrontendOptimizer::paintJqueryDelayEnabled(),
            'frontend_native_defer' => FrontendOptimizer::nativeDeferEnabled(),
            'first_hit_guard' => get_option('yperf_deep_archive_guard', '0') === '1',
            'semantic_purge_bridge' => get_option('yperf_external_purge_bridge', '0') === '1',
        );
    }

    /** @param array<string,mixed> $snapshot */
    public static function restore(array $snapshot): void
    {
        update_option('yperf_performance_profile', in_array((string) ($snapshot['performance_profile'] ?? ''), array('observe', 'safe', 'commerce'), true) ? (string) $snapshot['performance_profile'] : 'observe', false);
        update_option(FrontendOptimizer::OPTION_ENABLED, !empty($snapshot['frontend_optimization']) ? '1' : '0', false);
        update_option(FrontendOptimizer::OPTION_WOO_ROUTE_GATE, !empty($snapshot['frontend_woo_route_gate']) ? '1' : '0', false);
        update_option(FrontendOptimizer::OPTION_HEAVY_JS_DELAY, !empty($snapshot['frontend_heavy_js_delay']) ? '1' : '0', false);
        update_option(FrontendOptimizer::OPTION_LATE_ANALYTICS, !empty($snapshot['frontend_late_analytics']) ? '1' : '0', false);
        update_option(FrontendOptimizer::OPTION_INLINE_CSS, !empty($snapshot['frontend_inline_css']) ? '1' : '0', false);
        update_option(FrontendOptimizer::OPTION_REWRITTEN_CSS_RESOLUTION, !empty($snapshot['frontend_rewritten_css_resolution']) ? '1' : '0', false);
        update_option(FrontendOptimizer::OPTION_IDLE_HEAVY_RELEASE, !empty($snapshot['frontend_idle_heavy_release']) ? '1' : '0', false);
        update_option(FrontendOptimizer::OPTION_LCP_OBSERVED_GATE, !empty($snapshot['frontend_lcp_observed_gate']) ? '1' : '0', false);
        update_option(FrontendOptimizer::OPTION_FINAL_STYLE_MARKERS, !empty($snapshot['frontend_final_style_markers']) ? '1' : '0', false);
        update_option(FrontendOptimizer::OPTION_PAINT_JQUERY_DELAY, !empty($snapshot['frontend_paint_jquery_delay']) ? '1' : '0', false);
        update_option(FrontendOptimizer::OPTION_NATIVE_DEFER, !empty($snapshot['frontend_native_defer']) ? '1' : '0', false);
        if (PageCacheExecutor::enabled()) {
            PageCacheExecutor::purgeAll('settings_restore_frontend');
        }
        update_option('yperf_deep_archive_guard', !empty($snapshot['first_hit_guard']) ? '1' : '0', false);
        update_option('yperf_external_purge_bridge', !empty($snapshot['semantic_purge_bridge']) ? '1' : '0', false);

        $wantedPage = !empty($snapshot['page_cache']);
        if ($wantedPage && !PageCacheExecutor::enabled()) {
            PageCacheExecutor::repair();
        } elseif (!$wantedPage && PageCacheExecutor::enabled()) {
            PageCacheExecutor::disable(false);
        }
        if (!empty($snapshot['browser_cache']) && PageCacheExecutor::enabled()) {
            BrowserCacheController::setEnabled(true);
        } else {
            BrowserCacheController::setEnabled(false);
        }
    }
}
