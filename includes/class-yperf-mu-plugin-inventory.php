<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Read-only inventory of must-use plugin entry files.
 *
 * The inventory exists for field migration work (for example MonParle). It
 * never disables, deletes or edits MU plugins and never exports source code or
 * filesystem paths.
 */
final class MuPluginInventory
{
    private const MAX_FILES = 100;
    private const MAX_READ_BYTES = 1048576;

    /** @return array<string,mixed> */
    public static function scan(): array
    {
        $dir = defined('WPMU_PLUGIN_DIR') ? (string) WPMU_PLUGIN_DIR : '';
        if ($dir === '' || !is_dir($dir) || !is_readable($dir)) {
            return self::unavailable();
        }

        if (!function_exists('get_mu_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $listed = function_exists('get_mu_plugins') ? get_mu_plugins() : array();
        if (!is_array($listed)) {
            $listed = array();
        }
        $listed = array_slice($listed, 0, self::MAX_FILES, true);

        $filesystem = self::filesystem();
        $plugins = array();
        $review = 0;
        foreach ($listed as $relative => $meta) {
            $relative = ltrim((string) $relative, '/\\');
            $basename = sanitize_file_name(basename($relative));
            if ($basename === '') {
                continue;
            }
            $path = trailingslashit($dir) . $relative;
            $source = '';
            $size = 0;
            $sha256 = '';
            if ($filesystem !== null && $filesystem->exists($path) && $filesystem->is_readable($path)) {
                $size = max(0, (int) $filesystem->size($path));
                // MU entry files should be small. Never read an unexpectedly
                // large file merely to populate a diagnostic inventory.
                if ($size <= self::MAX_READ_BYTES) {
                    $raw = $filesystem->get_contents($path);
                    if (is_string($raw)) {
                        $source = $raw;
                        $sha256 = hash('sha256', $raw);
                    }
                }
            }
            $signals = self::signals($source);
            $candidate = $signals !== array();
            if ($candidate) {
                ++$review;
            }
            $meta = is_array($meta) ? $meta : array();
            $plugins[] = array(
                'file' => $basename,
                'name' => sanitize_text_field((string) ($meta['Name'] ?? '')),
                'version' => sanitize_text_field((string) ($meta['Version'] ?? '')),
                'description' => sanitize_text_field((string) ($meta['Description'] ?? '')),
                'size_bytes' => $size,
                'sha256' => $sha256,
                'signals' => $signals,
                'review_candidate' => $candidate,
            );
        }

        return array(
            'schema' => 2,
            'available' => true,
            'count' => count($plugins),
            'review_candidates' => $review,
            'plugins' => $plugins,
            'safety' => array(
                'read_only' => true,
                'source_exported' => false,
                'paths_exported' => false,
                'automatic_disable' => false,
                'automatic_delete' => false,
                'bounded_file_read' => true,
                'wordpress_filesystem_api' => true,
            ),
        );
    }

    /** @return object|null */
    private static function filesystem()
    {
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if (!function_exists('WP_Filesystem') || !WP_Filesystem()) {
            return null;
        }
        global $wp_filesystem;
        return is_object($wp_filesystem) ? $wp_filesystem : null;
    }

    /** @return array<string,mixed> */
    private static function unavailable(): array
    {
        return array(
            'schema' => 2,
            'available' => false,
            'count' => 0,
            'review_candidates' => 0,
            'plugins' => array(),
        );
    }

    /** @return array<int,string> */
    private static function signals(string $source): array
    {
        if ($source === '') {
            return array();
        }
        $rules = array(
            'cache' => '/cache|transient|wp_cache|advanced-cache/i',
            'redis' => '/redis|object-cache|object_cache/i',
            'database' => '/wpdb|query|mysql|mariadb|sql_calc_found_rows/i',
            'woocommerce' => '/woocommerce|wc_|product_query|shop_order/i',
            'origin' => '/php-fpm|pm\.max_children|nginx|fastcgi|worker/i',
            'cron' => '/wp_cron|cron_schedules|action_scheduler/i',
        );
        $signals = array();
        foreach ($rules as $id => $pattern) {
            if (preg_match($pattern, $source) === 1) {
                $signals[] = $id;
            }
        }
        return $signals;
    }
}
