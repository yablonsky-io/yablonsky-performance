<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class EnvironmentCheck
{
    public const OPTION = 'yperf_environment_check_v1';

    /** @param array<string,mixed> $caps @return array<string,mixed> */
    public static function run(array $caps): array
    {
        $checks = array();
        $server = (string) ($caps['web_server']['family'] ?? 'unknown');
        $origin = is_array($caps['origin'] ?? null) ? $caps['origin'] : array();
        $object = is_array($caps['object_cache'] ?? null) ? $caps['object_cache'] : array();
        $page = is_array($caps['page_cache'] ?? null) ? $caps['page_cache'] : array();
        $app = is_array($caps['application'] ?? null) ? $caps['application'] : array();

        $checks[] = self::check(
            'web_server',
            __('Web server', 'yablonsky-performance'),
            in_array($server, array('nginx', 'apache', 'litespeed'), true) ? 'ready' : 'unknown',
            strtoupper($server),
            in_array($server, array('nginx', 'apache', 'litespeed'), true)
                ? __('Native adapter family is recognized.', 'yablonsky-performance')
                : __('YPerf does not yet recognize this web-server family.', 'yablonsky-performance')
        );
        $checks[] = self::check(
            'php_fpm',
            __('PHP runtime', 'yablonsky-performance'),
            !empty($origin['fpm_candidate']) ? 'ready' : 'available',
            PHP_SAPI,
            !empty($origin['fpm_candidate'])
                ? __('PHP-FPM is visible. Worker configuration still requires host-level inspection.', 'yablonsky-performance')
                : __('PHP-FPM was not confirmed from this request.', 'yablonsky-performance')
        );
        $checks[] = self::extensionCheck('opcache', __('PHP OPcache', 'yablonsky-performance'), extension_loaded('Zend OPcache') && self::iniEnabled('opcache.enable'));
        $checks[] = self::extensionCheck('curl', __('cURL', 'yablonsky-performance'), extension_loaded('curl'));
        $checks[] = self::extensionCheck('zlib', __('Gzip / zlib', 'yablonsky-performance'), extension_loaded('zlib'));
        $checks[] = self::extensionCheck('brotli', __('Brotli', 'yablonsky-performance'), extension_loaded('brotli'), true);
        $checks[] = self::extensionCheck('mbstring', __('Multibyte strings', 'yablonsky-performance'), extension_loaded('mbstring'));
        $checks[] = self::extensionCheck('fileinfo', __('MIME detection', 'yablonsky-performance'), extension_loaded('fileinfo'));

        $redisExtension = !empty($object['redis_extension']);
        $redisReachable = $redisExtension ? self::redisReachable() : null;
        $checks[] = self::serviceCheck(
            'redis',
            'Redis',
            $redisExtension,
            $redisReachable,
            '127.0.0.1:6379'
        );

        $memcachedExtension = !empty($object['memcached_extension']);
        $memcachedReachable = $memcachedExtension ? self::memcachedReachable() : null;
        $checks[] = self::serviceCheck(
            'memcached',
            'Memcached',
            $memcachedExtension,
            $memcachedReachable,
            '127.0.0.1:11211'
        );

        $checks[] = self::check(
            'wp_cache',
            'WP_CACHE',
            !empty($page['wp_cache_constant']) ? 'ready' : 'available',
            !empty($page['wp_cache_constant']) ? __('Defined', 'yablonsky-performance') : __('Not defined', 'yablonsky-performance'),
            !empty($page['wp_cache_constant'])
                ? __('WordPress cache bootstrap is allowed.', 'yablonsky-performance')
                : __('Some page-cache drop-ins require WP_CACHE before they can execute.', 'yablonsky-performance')
        );
        $checks[] = self::check(
            'advanced_cache',
            'advanced-cache.php',
            !empty($page['advanced_cache_dropin']) ? 'attention' : 'available',
            !empty($page['advanced_cache_dropin']) ? __('Present', 'yablonsky-performance') : __('Absent', 'yablonsky-performance'),
            !empty($page['advanced_cache_dropin'])
                ? __('A drop-in exists. Ownership must be known before YPerf adopts page caching.', 'yablonsky-performance')
                : __('No page-cache drop-in currently claims this path.', 'yablonsky-performance')
        );
        $checks[] = self::check(
            'object_cache_dropin',
            'object-cache.php',
            !empty($object['external_enabled']) ? 'ready' : (!empty($object['dropin_present']) ? 'attention' : 'available'),
            !empty($object['dropin_present']) ? __('Present', 'yablonsky-performance') : __('Absent', 'yablonsky-performance'),
            !empty($object['external_enabled'])
                ? __('WordPress confirms an external persistent object cache.', 'yablonsky-performance')
                : (!empty($object['dropin_present'])
                    ? __('A drop-in exists but WordPress did not confirm an active external object cache.', 'yablonsky-performance')
                    : __('No persistent object-cache drop-in is active.', 'yablonsky-performance'))
        );
        $checks[] = self::check(
            'permalinks',
            __('Permalinks', 'yablonsky-performance'),
            (string) get_option('permalink_structure', '') !== '' ? 'ready' : 'attention',
            (string) get_option('permalink_structure', '') !== '' ? __('Pretty URLs', 'yablonsky-performance') : __('Plain URLs', 'yablonsky-performance'),
            __('Cacheability and selective purge are easier to reason about with stable public URLs.', 'yablonsky-performance')
        );
        $checks[] = self::check(
            'woocommerce',
            'WooCommerce',
            !empty($app['woocommerce']) ? 'ready' : 'available',
            !empty($app['woocommerce']) ? __('Detected', 'yablonsky-performance') : __('Not detected', 'yablonsky-performance'),
            !empty($app['woocommerce'])
                ? __('YPerf will preserve cart, checkout, account and session bypass rules.', 'yablonsky-performance')
                : __('WooCommerce-specific protection is not required on this site.', 'yablonsky-performance')
        );
        $checks[] = self::check(
            'multilingual',
            __('Multilingual authority', 'yablonsky-performance'),
            (!empty($app['ypm']) || !empty($app['wpml']) || !empty($app['polylang'])) ? 'ready' : 'available',
            !empty($app['ypm']) ? 'YMulti' : (!empty($app['wpml']) ? 'WPML' : (!empty($app['polylang']) ? 'Polylang' : __('None', 'yablonsky-performance'))),
            __('Multilingual URL ownership affects selective purge and prewarm boundaries.', 'yablonsky-performance')
        );
        $checks[] = self::check(
            'host_helper',
            __('Host helper', 'yablonsky-performance'),
            'unknown',
            __('Not connected', 'yablonsky-performance'),
            __('Read-only WordPress cannot inspect Nginx cache zones, PHP-FPM pools or daemon configuration yet.', 'yablonsky-performance')
        );

        $summary = array('ready' => 0, 'available' => 0, 'attention' => 0, 'blocked' => 0, 'unknown' => 0);
        foreach ($checks as $check) {
            $state = (string) ($check['state'] ?? 'unknown');
            if (!isset($summary[$state])) {
                $state = 'unknown';
            }
            ++$summary[$state];
        }

        $report = array(
            'schema'       => 1,
            'generated_at' => time(),
            'site'         => home_url('/'),
            'summary'      => $summary,
            'checks'       => $checks,
            'safety'       => array(
                'read_only'   => true,
                'remote_sent' => false,
                'host_changes'=> false,
            ),
        );
        update_option(self::OPTION, $report, false);
        Telemetry::recordEnvironment($report);
        return $report;
    }

    /** @return array<string,mixed> */
    public static function lastReport(): array
    {
        $value = get_option(self::OPTION, array());
        return is_array($value) ? $value : array();
    }

    /** @return array<string,mixed> */
    private static function check(string $id, string $name, string $state, string $value, string $details): array
    {
        return array(
            'id'      => sanitize_key($id),
            'name'    => sanitize_text_field($name),
            'state'   => sanitize_key($state),
            'value'   => sanitize_text_field($value),
            'details' => sanitize_text_field($details),
        );
    }

    /** @return array<string,mixed> */
    private static function extensionCheck(string $id, string $name, bool $loaded, bool $optional = false): array
    {
        return self::check(
            $id,
            $name,
            $loaded ? 'ready' : ($optional ? 'available' : 'attention'),
            $loaded ? __('Installed', 'yablonsky-performance') : __('Not installed', 'yablonsky-performance'),
            $loaded
                ? __('Runtime capability is available to PHP.', 'yablonsky-performance')
                : ($optional
                    ? __('Optional capability is not currently installed.', 'yablonsky-performance')
                    : __('A feature depending on this extension may be limited.', 'yablonsky-performance'))
        );
    }

    /** @return array<string,mixed> */
    private static function serviceCheck(string $id, string $name, bool $extensionLoaded, ?bool $reachable, string $endpoint): array
    {
        if (!$extensionLoaded) {
            return self::check(
                $id,
                $name,
                'available',
                __('PHP extension missing', 'yablonsky-performance'),
                __('YPerf keeps this option visible so the user can install or ask the host for it later.', 'yablonsky-performance')
            );
        }
        if ($reachable === true) {
            return self::check($id, $name, 'ready', __('Service reachable', 'yablonsky-performance'), $endpoint);
        }
        return self::check(
            $id,
            $name,
            'attention',
            __('Extension installed · service not confirmed', 'yablonsky-performance'),
            $endpoint . ' · ' . __('No configuration was changed.', 'yablonsky-performance')
        );
    }

    private static function redisReachable(): ?bool
    {
        if (!class_exists('\\Redis', false)) {
            return null;
        }
        try {
            $redis = new \Redis();
            $connected = $redis->connect('127.0.0.1', 6379, 0.15);
            if (!$connected) {
                return false;
            }
            try {
                $redis->ping();
            } catch (\Throwable $throwable) {
                unset($throwable);
            }
            $redis->close();
            return true;
        } catch (\Throwable $throwable) {
            unset($throwable);
            return false;
        }
    }

    private static function memcachedReachable(): ?bool
    {
        if (!class_exists('\\Memcached', false)) {
            return null;
        }
        try {
            $memcached = new \Memcached();
            if (defined('Memcached::OPT_CONNECT_TIMEOUT')) {
                $memcached->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 150);
            }
            $memcached->addServer('127.0.0.1', 11211);
            $versions = $memcached->getVersion();
            if (!is_array($versions)) {
                return false;
            }
            foreach ($versions as $version) {
                if (is_string($version) && $version !== '' && $version !== '255.255.255') {
                    return true;
                }
            }
            return false;
        } catch (\Throwable $throwable) {
            unset($throwable);
            return false;
        }
    }

    private static function iniEnabled(string $key): bool
    {
        $value = strtolower(trim((string) ini_get($key)));
        return in_array($value, array('1', 'on', 'yes', 'true'), true);
    }
}
