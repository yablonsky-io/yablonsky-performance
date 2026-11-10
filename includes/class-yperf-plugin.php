<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class Plugin
{
    public static function boot(): void
    {
        add_action('init', array(LanguageRegistry::class, 'loadTextdomain'), 0);
        add_action('change_locale', array(LanguageRegistry::class, 'onLocaleChange'), 10, 1);
        ManagedRuntime::register();
        InvalidationBus::register();
        YMultiInvalidationBridge::register();
        PurgeController::register();
        PageCacheExecutor::register();
        ObjectCacheExecutor::register();
        BrowserCacheController::register();
        self::registerQueryCostTelemetrySafely();
        FrontendOptimizer::register();
        if (!defined('YPERF_EARLY_RUNTIME_ACTIVE') || !YPERF_EARLY_RUNTIME_ACTIVE) {
            QueryCostGuard::register();
        }
        Admin::registerToolbar();
        if (is_admin()) {
            UpgradeHealth::register();
            FieldAcceptance::register();
            Admin::register();
        }
    }
    private static function registerQueryCostTelemetrySafely(): void
    {
        // Active plugins are loaded before WordPress pluggable functions are
        // guaranteed to exist. Signed probe authorization uses wp_salt(), so
        // never execute it directly from the plugin file bootstrap.
        if (did_action('plugins_loaded')) {
            QueryCostTelemetry::register();
            return;
        }

        add_action('plugins_loaded', array(QueryCostTelemetry::class, 'register'), 0);
    }

}
