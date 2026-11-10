<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Owns the single YPerf MU bootstrap.
 *
 * The loader is tiny, versioned, atomically written and fails open when the
 * main plugin/runtime is absent or version-mismatched.
 */
final class ManagedRuntime
{
    private const FILE = 'yablonsky-performance-runtime.php';
    private const MARKER = 'YPerf Managed MU Runtime';
    private const OPTION = 'yperf_managed_runtime_v1';

    public static function register(): void
    {
        add_action('admin_init', array(__CLASS__, 'reconcile'), 1);
        add_action('upgrader_process_complete', array(__CLASS__, 'afterUpgrade'), 10, 2);
    }

    public static function reconcile(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        self::ensureInstalled();
    }

    /** @param mixed $upgrader @param array<string,mixed> $options */
    public static function afterUpgrade($upgrader, array $options): void
    {
        if (($options['type'] ?? '') !== 'plugin') {
            return;
        }
        $plugins = $options['plugins'] ?? array();
        if (is_string($plugins)) {
            $plugins = array($plugins);
        }
        if (!is_array($plugins) || !in_array(YPERF_BASENAME, $plugins, true)) {
            return;
        }
        self::ensureInstalled();
    }

    /** @return array<string,mixed> */
    public static function ensureInstalled(): array
    {
        $fs = self::filesystem();
        if ($fs === null) {
            return self::result(false, 'filesystem_unavailable', __('WordPress Filesystem could not obtain direct write access for the managed runtime.', 'yablonsky-performance'));
        }

        $dir = self::dir();
        if (!$fs->is_dir($dir) && !$fs->mkdir($dir, defined('FS_CHMOD_DIR') ? FS_CHMOD_DIR : 0755)) {
            return self::result(false, 'mu_directory_unavailable', __('The WordPress MU plugin directory could not be prepared.', 'yablonsky-performance'));
        }

        $target = self::path();
        if ($fs->exists($target)) {
            $existing = $fs->get_contents($target);
            if (!is_string($existing) || strpos($existing, self::MARKER) === false) {
                return self::result(false, 'foreign_loader_present', __('A non-YPerf file already uses the managed runtime filename. YPerf will not overwrite it.', 'yablonsky-performance'));
            }
        }

        $template = $fs->get_contents(YPERF_DIR . 'runtime/mu-loader.php.tpl');
        if (!is_string($template) || $template === '') {
            return self::result(false, 'template_unavailable', __('The managed runtime loader template is unavailable.', 'yablonsky-performance'));
        }
        $rendered = str_replace('__YPERF_VERSION__', self::escapeSingleQuotedPhpString(YPERF_VERSION), $template);
        if (strpos($rendered, '__YPERF_VERSION__') !== false) {
            return self::result(false, 'template_render_failed', __('The managed runtime loader template could not be rendered.', 'yablonsky-performance'));
        }

        $expected = hash('sha256', $rendered);
        if ($fs->exists($target)) {
            $current = $fs->get_contents($target);
            if (is_string($current) && hash_equals($expected, hash('sha256', $current))) {
                self::storeRecord($expected);
                return self::result(true, 'already_current', __('The managed runtime loader is current.', 'yablonsky-performance'), $expected);
            }
        }

        $write = self::atomicWrite($fs, $target, $rendered);
        if (empty($write['ok'])) {
            return self::result(false, 'write_failed', __('YPerf could not atomically install its managed runtime loader.', 'yablonsky-performance'));
        }
        self::storeRecord((string) ($write['sha256'] ?? $expected));
        return self::result(true, 'installed', __('The managed runtime loader is installed.', 'yablonsky-performance'), (string) ($write['sha256'] ?? $expected));
    }

    /** @return array<string,mixed> */
    public static function removeOwned(): array
    {
        $fs = self::filesystem();
        if ($fs === null) {
            return self::result(false, 'filesystem_unavailable', __('WordPress Filesystem could not obtain direct write access for managed runtime cleanup.', 'yablonsky-performance'));
        }
        $target = self::path();
        if (!$fs->exists($target)) {
            delete_option(self::OPTION);
            return self::result(true, 'absent', __('The managed runtime loader is already absent.', 'yablonsky-performance'));
        }
        $contents = $fs->get_contents($target);
        if (!is_string($contents) || strpos($contents, self::MARKER) === false) {
            return self::result(false, 'ownership_ambiguous', __('The managed runtime loader ownership is ambiguous. Automatic removal is blocked.', 'yablonsky-performance'));
        }
        $record = self::record();
        $expected = (string) ($record['sha256'] ?? '');
        $actual = hash('sha256', $contents);
        if ($expected !== '' && !hash_equals($expected, $actual)) {
            return self::result(false, 'ownership_hash_review_required', __('The managed runtime loader changed after installation. Automatic removal is blocked for review.', 'yablonsky-performance'));
        }
        if (!$fs->delete($target, false, 'f')) {
            return self::result(false, 'delete_failed', __('YPerf could not remove its managed runtime loader.', 'yablonsky-performance'));
        }
        delete_option(self::OPTION);
        return self::result(true, 'removed', __('The managed runtime loader was removed.', 'yablonsky-performance'));
    }

    /** @return array<string,mixed> */
    public static function status(): array
    {
        $fs = self::filesystem();
        $target = self::path();
        $present = false;
        $owned = false;
        $version = '';
        $sha256 = '';
        if ($fs !== null && $fs->exists($target)) {
            $present = true;
            $contents = $fs->get_contents($target);
            if (is_string($contents)) {
                $owned = strpos($contents, self::MARKER) !== false;
                $sha256 = hash('sha256', $contents);
                if (preg_match('/Version:\s*([0-9]+\.[0-9]+\.[0-9]+)/', $contents, $match) === 1) {
                    $version = sanitize_text_field((string) $match[1]);
                }
            }
        }
        $source = YPERF_DIR . 'runtime/early-runtime.php';
        return array(
            'schema' => 1,
            'version' => YPERF_VERSION,
            'loader_present' => $present,
            'loader_owned' => $owned,
            'loader_version' => $version,
            'loader_version_current' => $owned && $version !== '' && hash_equals(YPERF_VERSION, $version),
            'loader_sha256' => $sha256,
            'runtime_source_present' => is_readable($source),
            'runtime_active_this_request' => defined('YPERF_EARLY_RUNTIME_ACTIVE') && YPERF_EARLY_RUNTIME_ACTIVE,
            'runtime_version' => defined('YPERF_EARLY_RUNTIME_VERSION') ? sanitize_text_field((string) YPERF_EARLY_RUNTIME_VERSION) : '',
            'fail_open_on_mismatch' => true,
            'single_mu_bootstrap' => true,
            'execution_hook' => defined('YPERF_EARLY_RUNTIME_PHASE_HOOK') ? sanitize_key((string) YPERF_EARLY_RUNTIME_PHASE_HOOK) : 'direct',
            'execution_priority' => defined('YPERF_EARLY_RUNTIME_PHASE_PRIORITY') ? (int) YPERF_EARLY_RUNTIME_PHASE_PRIORITY : 0,
            'coordination_contract' => 'security_then_capacity',
            'security_priority_reserved' => -1000,
            'performance_priority' => -900,
        );
    }

    private static function storeRecord(string $sha256): void
    {
        update_option(self::OPTION, array(
            'schema' => 1,
            'installed_at' => time(),
            'version' => YPERF_VERSION,
            'sha256' => preg_match('/^[a-f0-9]{64}$/', $sha256) === 1 ? $sha256 : '',
        ), false);
    }

    /** @return array<string,mixed> */
    private static function record(): array
    {
        $record = get_option(self::OPTION, array());
        return is_array($record) ? $record : array();
    }

    private static function dir(): string
    {
        return trailingslashit(defined('WPMU_PLUGIN_DIR') ? (string) WPMU_PLUGIN_DIR : trailingslashit((string) WP_CONTENT_DIR) . 'mu-plugins');
    }

    private static function path(): string
    {
        return self::dir() . self::FILE;
    }

    /** @return object|null */
    private static function filesystem()
    {
        if (!function_exists('WP_Filesystem') && defined('ABSPATH')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if (!function_exists('WP_Filesystem') || !WP_Filesystem()) {
            return null;
        }
        global $wp_filesystem;
        return is_object($wp_filesystem) ? $wp_filesystem : null;
    }

    /** @param object $fs @return array<string,mixed> */
    private static function atomicWrite($fs, string $target, string $contents): array
    {
        $temporary = $target . '.yperf-tmp';
        if ($fs->exists($temporary)) {
            $fs->delete($temporary, false, 'f');
        }
        $chmod = defined('FS_CHMOD_FILE') ? FS_CHMOD_FILE : 0644;
        if (!$fs->put_contents($temporary, $contents, $chmod)) {
            return array('ok' => false);
        }
        $readback = $fs->get_contents($temporary);
        if (!is_string($readback) || !hash_equals(hash('sha256', $contents), hash('sha256', $readback))) {
            $fs->delete($temporary, false, 'f');
            return array('ok' => false);
        }
        if (!$fs->move($temporary, $target, true)) {
            $fs->delete($temporary, false, 'f');
            return array('ok' => false);
        }
        $final = $fs->get_contents($target);
        if (!is_string($final)) {
            return array('ok' => false);
        }
        $sha = hash('sha256', $final);
        return hash_equals(hash('sha256', $contents), $sha)
            ? array('ok' => true, 'sha256' => $sha)
            : array('ok' => false);
    }

    private static function escapeSingleQuotedPhpString(string $value): string
    {
        return str_replace(array('\\', "'"), array('\\\\', "\\'"), $value);
    }

    /** @return array<string,mixed> */
    private static function result(bool $ok, string $state, string $message, string $sha256 = ''): array
    {
        return array(
            'ok' => $ok,
            'state' => sanitize_key($state),
            'message' => sanitize_text_field($message),
            'version' => YPERF_VERSION,
            'sha256' => preg_match('/^[a-f0-9]{64}$/', $sha256) === 1 ? $sha256 : '',
        );
    }
}
