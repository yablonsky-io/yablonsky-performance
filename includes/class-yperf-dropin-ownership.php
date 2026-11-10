<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class DropinOwnership
{
    /** @return array<string,mixed> */
    public static function inspect(): array
    {
        if (!function_exists('WP_Filesystem') && defined('ABSPATH')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if (function_exists('WP_Filesystem')) {
            WP_Filesystem();
        }
        $contentDir = defined('WP_CONTENT_DIR') ? (string) WP_CONTENT_DIR : '';
        return array(
            'schema' => 1,
            'generated_at' => time(),
            'advanced_cache' => self::inspectFile($contentDir !== '' ? $contentDir . '/advanced-cache.php' : '', 'advanced-cache.php'),
            'object_cache' => self::inspectFile($contentDir !== '' ? $contentDir . '/object-cache.php' : '', 'object-cache.php'),
            'runtime' => array(
                'wp_cache_constant' => defined('WP_CACHE') && WP_CACHE,
                'external_object_cache' => function_exists('wp_using_ext_object_cache') ? (bool) wp_using_ext_object_cache() : false,
            ),
            'privacy' => array(
                'file_contents_exported' => false,
                'paths_exported' => false,
                'secrets_included' => false,
            ),
        );
    }

    /** @return array<string,mixed> */
    private static function inspectFile(string $path, string $label): array
    {
        global $wp_filesystem;
        if ($path === '' || !is_object($wp_filesystem) || !method_exists($wp_filesystem, 'exists') || !$wp_filesystem->exists($path)) {
            return array('file' => $label, 'present' => false, 'owner' => 'none', 'confidence' => 'certain');
        }

        $contents = method_exists($wp_filesystem, 'get_contents') ? $wp_filesystem->get_contents($path) : false;
        $raw = is_string($contents) ? $contents : '';
        $sample = strtolower(substr($raw, 0, 65536));
        $attribution = self::attribute($sample, $label);
        $size = method_exists($wp_filesystem, 'size') ? (int) $wp_filesystem->size($path) : strlen($raw);
        $mtime = method_exists($wp_filesystem, 'mtime') ? (int) $wp_filesystem->mtime($path) : 0;

        $artifactVersion = '';
        if ($attribution['owner'] === 'yablonsky-performance' && preg_match('/^\s*\*\s*Version:\s*([0-9]+\.[0-9]+\.[0-9]+)\s*$/mi', substr($raw, 0, 4096), $match) === 1) {
            $artifactVersion = sanitize_text_field((string) $match[1]);
        }

        return array(
            'file' => $label,
            'present' => true,
            'owner' => $attribution['owner'],
            'confidence' => $attribution['confidence'],
            'evidence' => $attribution['evidence'],
            'artifact_version' => $artifactVersion,
            'size_bytes' => $size,
            'modified_at' => $mtime,
            'sha256' => $raw !== '' ? hash('sha256', $raw) : '',
        );
    }

    /** @return array<string,string> */
    private static function attribute(string $sample, string $label): array
    {
        if ($sample === '') {
            return array('owner' => 'unknown', 'confidence' => 'low', 'evidence' => 'file_not_readable');
        }
        $signatures = array(
            'w3-total-cache' => array('w3 total cache', 'w3tc_', 'w3tc\\', '/w3-total-cache/'),
            'redis-object-cache' => array('redis object cache', 'wp redis', 'objectcache\\redis', 'tillkruss'),
            'litespeed-cache' => array('litespeed cache', 'litespeed-cache'),
            'wp-rocket' => array('wp rocket', 'wp-rocket'),
            'yablonsky-performance' => array('yablonsky performance', 'yablonsky\\performancecache'),
        );
        foreach ($signatures as $owner => $needles) {
            foreach ($needles as $needle) {
                if (strpos($sample, $needle) !== false) {
                    return array('owner' => $owner, 'confidence' => 'high', 'evidence' => $needle);
                }
            }
        }
        if ($label === 'object-cache.php' && strpos($sample, 'wp_object_cache') !== false) {
            return array('owner' => 'unknown-object-cache-provider', 'confidence' => 'medium', 'evidence' => 'wp_object_cache_signature');
        }
        if ($label === 'advanced-cache.php' && strpos($sample, 'wp_cache') !== false) {
            return array('owner' => 'unknown-page-cache-provider', 'confidence' => 'medium', 'evidence' => 'wp_cache_signature');
        }
        return array('owner' => 'unknown', 'confidence' => 'low', 'evidence' => 'no_known_signature');
    }
}
