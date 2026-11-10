<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class ProviderInspector
{
    /** @param array<int,string> $activePlugins @return array<string,mixed> */
    public static function inspect(array $activePlugins): array
    {
        $providers = array();

        if (self::pluginActive($activePlugins, 'w3-total-cache/')) {
            $providers['w3-total-cache'] = self::inspectW3tc();
        }

        foreach (
            array(
                'wp-rocket/'       => array('id' => 'wp-rocket', 'name' => 'WP Rocket'),
                'litespeed-cache/' => array('id' => 'litespeed-cache', 'name' => 'LiteSpeed Cache'),
                'wp-optimize/'     => array('id' => 'wp-optimize', 'name' => 'WP-Optimize'),
                'flying-press/'    => array('id' => 'flyingpress', 'name' => 'FlyingPress'),
            ) as $prefix => $meta
        ) {
            if (!self::pluginActive($activePlugins, $prefix)) {
                continue;
            }
            $providers[(string) $meta['id']] = array(
                'name'             => (string) $meta['name'],
                'installed'        => true,
                'config_readable'  => false,
                'inspection_state' => 'installed_only',
                'note'             => __('Provider detected. Exact configuration adapter is not implemented in this build.', 'yablonsky-performance'),
            );
        }

        return $providers;
    }

    /** @return array<string,mixed> */
    private static function inspectW3tc(): array
    {
        $result = array(
            'name'             => 'W3 Total Cache',
            'installed'        => true,
            'config_readable'  => false,
            'inspection_state' => 'installed_only',
            'version'          => defined('W3TC_VERSION') ? sanitize_text_field((string) W3TC_VERSION) : '',
            'layers'           => array(),
        );

        if (!class_exists('\\W3TC\\Config')) {
            $result['note'] = __('W3 Total Cache is active, but its configuration API is not available in this request.', 'yablonsky-performance');
            return $result;
        }

        try {
            $config = new \W3TC\Config();
            $extensions = $config->get_array('extensions.active');
            if (!is_array($extensions)) {
                $extensions = array();
            }

            $result['config_readable'] = true;
            $result['inspection_state'] = 'read_only';
            $result['layers'] = array(
                'page_cache' => self::layer(
                    $config->get_boolean('pgcache.enabled'),
                    $config->get_string('pgcache.engine'),
                    array(
                        'prime_enabled' => $config->get_boolean('pgcache.prime.enabled'),
                        'lifetime'      => $config->get_integer('pgcache.lifetime'),
                    )
                ),
                'database_cache' => self::layer(
                    $config->get_boolean('dbcache.enabled'),
                    $config->get_string('dbcache.engine'),
                    array('lifetime' => $config->get_integer('dbcache.lifetime'))
                ),
                'object_cache' => self::layer(
                    $config->get_boolean('objectcache.enabled'),
                    $config->get_string('objectcache.engine'),
                    array('lifetime' => $config->get_integer('objectcache.lifetime'))
                ),
                'minify' => self::layer(
                    $config->get_boolean('minify.enabled'),
                    $config->get_string('minify.engine'),
                    array(
                        'html' => $config->get_boolean('minify.html.enable'),
                        'css'  => $config->get_boolean('minify.css.enable'),
                        'js'   => $config->get_boolean('minify.js.enable'),
                    )
                ),
                'browser_cache' => self::layer(
                    $config->get_boolean('browsercache.enabled'),
                    'native_headers',
                    array(
                        'html_compression'  => $config->get_boolean('browsercache.html.compression'),
                        'cssjs_compression' => $config->get_boolean('browsercache.cssjs.compression'),
                    )
                ),
                'statistics' => self::layer($config->get_boolean('stats.enabled'), 'w3tc_stats'),
                'lazy_load'  => self::layer($config->get_boolean('lazyload.enabled'), 'w3tc_lazyload'),
                'cdn'        => self::layer($config->get_boolean('cdn.enabled'), $config->get_string('cdn.engine')),
                'full_site_delivery' => self::layer($config->get_boolean('cdnfsd.enabled'), $config->get_string('cdnfsd.engine')),
                'fragment_cache' => array(
                    'enabled' => isset($extensions['fragmentcache']),
                    'engine'  => self::engineLabel($config->get_string('fragmentcache.engine')),
                    'configured' => trim($config->get_string('fragmentcache.engine')) !== '',
                ),
                'cloudflare_extension' => array(
                    'enabled' => isset($extensions['cloudflare']),
                    'configured' => isset($extensions['cloudflare']),
                ),
            );
        } catch (\Throwable $throwable) {
            unset($throwable);
            $result['inspection_state'] = 'read_failed';
            $result['note'] = __('W3 Total Cache configuration could not be read safely. No settings were changed.', 'yablonsky-performance');
        }

        return $result;
    }

    /** @param array<string,mixed> $extra @return array<string,mixed> */
    private static function layer(bool $enabled, string $engine, array $extra = array()): array
    {
        return array_merge(
            array(
                'enabled'    => $enabled,
                'configured' => $enabled || trim($engine) !== '',
                'engine'     => self::engineLabel($engine),
                'engine_key' => sanitize_key($engine),
            ),
            $extra
        );
    }

    private static function engineLabel(string $engine): string
    {
        $engine = trim($engine);
        $map = array(
            ''               => __('None', 'yablonsky-performance'),
            'file'           => __('Disk: Basic', 'yablonsky-performance'),
            'file_generic'   => __('Disk: Enhanced', 'yablonsky-performance'),
            'redis'          => 'Redis',
            'memcached'      => 'Memcached',
            'nginx_memcached'=> 'Nginx + Memcached',
            'apc'            => 'APC / APCu',
            'eaccelerator'   => 'eAccelerator',
            'xcache'         => 'XCache',
            'wincache'       => 'WinCache',
        );
        return isset($map[$engine]) ? $map[$engine] : sanitize_text_field($engine);
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
