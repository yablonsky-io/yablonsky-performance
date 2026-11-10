<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class CapabilityDetector
{
    /** @return array<string,mixed> */
    public static function detect(): array
    {
        $software = isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash((string) $_SERVER['SERVER_SOFTWARE'])) : '';
        $server = self::serverFamily($software);
        $contentDir = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : '';
        $activePlugins = (array) get_option('active_plugins', array());

        if (is_multisite()) {
            $networkPlugins = array_keys((array) get_site_option('active_sitewide_plugins', array()));
            $activePlugins = array_values(array_unique(array_merge($activePlugins, $networkPlugins)));
        }

        $advancedCache = $contentDir !== '' && is_file($contentDir . '/advanced-cache.php');
        $objectCache = $contentDir !== '' && is_file($contentDir . '/object-cache.php');
        $providers = ProviderInspector::inspect($activePlugins);

        return array(
            'web_server' => array(
                'family'   => $server,
                'software' => $software !== '' ? $software : __('Unknown', 'yablonsky-performance'),
                'native_page_cache_candidate' => in_array($server, array('nginx', 'litespeed', 'apache'), true),
            ),
            'page_cache' => array(
                'wp_cache_constant'    => defined('WP_CACHE') && WP_CACHE,
                'advanced_cache_dropin'=> $advancedCache,
                'known_page_cache_plugins' => self::knownPageCachePlugins($activePlugins),
            ),
            'object_cache' => array(
                'external_enabled' => function_exists('wp_using_ext_object_cache') && wp_using_ext_object_cache(),
                'dropin_present'   => $objectCache,
                'redis_extension'  => extension_loaded('redis') || class_exists('Redis', false),
                'memcached_extension' => extension_loaded('memcached'),
                'memcache_extension'  => extension_loaded('memcache'),
                'predis_loaded'    => class_exists('Predis\\Client', false),
                'known_object_cache_plugins' => self::knownObjectCachePlugins($activePlugins),
            ),
            'opcode_cache' => array(
                'extension_loaded' => extension_loaded('Zend OPcache'),
                'enabled'          => self::opcacheEnabled(),
                'validate_timestamps' => (string) ini_get('opcache.validate_timestamps'),
            ),
            'origin' => array(
                'php_sapi'       => PHP_SAPI,
                'php_version'    => PHP_VERSION,
                'fpm_candidate'  => PHP_SAPI === 'fpm-fcgi',
                'worker_control' => 'host_helper_required',
            ),
            'providers' => $providers,
            'application' => array(
                'woocommerce' => defined('WC_VERSION') || class_exists('WooCommerce', false),
                'ypm'         => self::yMultiAvailable($activePlugins),
                'yps'         => self::ySeoAvailable($activePlugins),
                'wpml'        => defined('ICL_SITEPRESS_VERSION') || self::pluginActive($activePlugins, 'sitepress-multilingual-cms/'),
                'polylang'    => defined('POLYLANG_VERSION') || self::pluginActive($activePlugins, 'polylang/'),
                'active_plugin_count' => count($activePlugins),
            ),
        );
    }


    /** @param array<int,string> $activePlugins */
    private static function yMultiAvailable(array $activePlugins): bool
    {
        // Prefer the explicit YMulti/YPM capability contract when the provider is loaded.
        if (function_exists('has_filter') && has_filter('ypm_language_provider')) {
            return true;
        }

        // Preserve compatibility with both the canonical and historical plugin directories.
        return self::pluginActive($activePlugins, 'yablonsky-multilingual/')
            || self::pluginActive($activePlugins, 'yablonsky-performance-multilingual/');
    }

    /** @param array<int,string> $activePlugins */
    private static function ySeoAvailable(array $activePlugins): bool
    {
        return self::pluginActive($activePlugins, 'yablonsky-seo/')
            || self::pluginActive($activePlugins, 'yablonsky-performance-seo/');
    }

    private static function opcacheEnabled(): bool
    {
        if (!extension_loaded('Zend OPcache')) {
            return false;
        }
        $enabled = strtolower(trim((string) ini_get('opcache.enable')));
        return in_array($enabled, array('1', 'on', 'yes', 'true'), true);
    }

    private static function serverFamily(string $software): string
    {
        $value = strtolower($software);
        if (strpos($value, 'litespeed') !== false) {
            return 'litespeed';
        }
        if (strpos($value, 'nginx') !== false) {
            return 'nginx';
        }
        if (strpos($value, 'apache') !== false) {
            return 'apache';
        }
        return 'unknown';
    }

    /**
     * @param array<int,string> $activePlugins
     * @return array<int,string>
     */
    private static function knownPageCachePlugins(array $activePlugins): array
    {
        $known = array(
            'wp-rocket/'        => 'WP Rocket',
            'litespeed-cache/'  => 'LiteSpeed Cache',
            'w3-total-cache/'   => 'W3 Total Cache',
            'wp-optimize/'      => 'WP-Optimize',
            'flying-press/'      => 'FlyingPress',
        );
        $found = array();

        foreach ($known as $prefix => $label) {
            if (self::pluginActive($activePlugins, $prefix)) {
                $found[] = $label;
            }
        }

        return $found;
    }


    /**
     * @param array<int,string> $activePlugins
     * @return array<int,string>
     */
    private static function knownObjectCachePlugins(array $activePlugins): array
    {
        $known = array(
            'redis-cache/' => 'Redis Object Cache',
        );
        $found = array();

        foreach ($known as $prefix => $label) {
            if (self::pluginActive($activePlugins, $prefix)) {
                $found[] = $label;
            }
        }

        return $found;
    }

    /** @param array<int,string> $activePlugins */
    private static function pluginActive(array $activePlugins, string $directoryPrefix): bool
    {
        foreach ($activePlugins as $plugin) {
            if (strpos((string) $plugin, $directoryPrefix) === 0) {
                return true;
            }
        }
        return false;
    }
}
