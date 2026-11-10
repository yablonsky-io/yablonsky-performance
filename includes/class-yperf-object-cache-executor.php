<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Explicit R3 Redis object-cache write boundary.
 *
 * The executor writes only a YPerf-owned wp-content/object-cache.php after the
 * R2 contract is current and the operator explicitly approves the exact
 * fingerprint. It never flushes Redis, changes Redis configuration, inspects
 * arbitrary keys or restarts a service.
 */
final class ObjectCacheExecutor
{
    private const OPTION = 'yperf_object_cache_execution_v1';
    private const PENDING = 'yperf_object_cache_execution_pending_v1';
    private const MAX_PENDING_AGE = 600;
    private const WATCHDOG_HOOK = 'yperf_object_cache_pending_watchdog';

    public static function register(): void
    {
        add_action(self::WATCHDOG_HOOK, array(self::class, 'recoverStalePending'));
        add_action('init', array(self::class, 'ensurePendingWatchdog'), 1);
    }

    public static function ensurePendingWatchdog(): void
    {
        $pending = self::lastPending();
        if ($pending === array()) {
            self::clearPendingWatchdog();
            return;
        }
        $startedAt = max(0, (int) ($pending['started_at'] ?? 0));
        if ($startedAt > 0 && (time() - $startedAt) >= self::MAX_PENDING_AGE) {
            self::recoverStalePending();
            return;
        }
        self::schedulePendingWatchdog($startedAt > 0 ? $startedAt + self::MAX_PENDING_AGE + 5 : time() + self::MAX_PENDING_AGE);
    }

    public static function recoverStalePending(): void
    {
        $pending = self::lastPending();
        if ($pending === array()) {
            self::clearPendingWatchdog();
            return;
        }
        $startedAt = max(0, (int) ($pending['started_at'] ?? 0));
        if ($startedAt < 1 || (time() - $startedAt) < self::MAX_PENDING_AGE) {
            self::schedulePendingWatchdog(($startedAt > 0 ? $startedAt : time()) + self::MAX_PENDING_AGE + 5);
            return;
        }
        self::rollbackWithFailure($pending, __('Object-cache verification expired before completion.', 'yablonsky-performance'));
    }

    /** @param array<string,mixed> $contract @return array<string,mixed> */
    public static function activate(array $contract, string $expectedFingerprint): array
    {
        $details = is_array($contract['contract'] ?? null) ? $contract['contract'] : array();
        $fingerprint = (string) ($details['fingerprint'] ?? '');
        if ((string) ($contract['state'] ?? '') !== 'operation_contract_ready' || $fingerprint === '') {
            return self::error(__('The Redis activation contract is not ready.', 'yablonsky-performance'));
        }
        if ($expectedFingerprint === '' || !hash_equals($fingerprint, $expectedFingerprint)) {
            return self::error(__('The Redis activation contract changed before approval. Refresh Setup and review the current contract.', 'yablonsky-performance'));
        }
        if (!extension_loaded('redis') || !class_exists('Redis')) {
            return self::error(__('The PHP Redis extension is not available in this request.', 'yablonsky-performance'));
        }
        $existingPending = self::lastPending();
        if ($existingPending !== array()) {
            $startedAt = max(0, (int) ($existingPending['started_at'] ?? 0));
            if ($startedAt > 0 && (time() - $startedAt) >= self::MAX_PENDING_AGE) {
                self::recoverStalePending();
                $existingPending = self::lastPending();
            }
        }
        if ($existingPending !== array()) {
            return self::error(__('A Redis object-cache activation is already pending verification.', 'yablonsky-performance'));
        }

        $database = (int) ($details['database'] ?? -1);
        $prefix = sanitize_text_field((string) ($details['key_prefix'] ?? ''));
        if ($database < 0 || preg_match('/^yperf:[a-f0-9]{12}:$/', $prefix) !== 1) {
            return self::error(__('The frozen Redis database or key namespace is invalid.', 'yablonsky-performance'));
        }

        // Close the Host Snapshot → apply TOCTOU gap with a live, read-only
        // Redis DBSIZE check immediately before the filesystem write. The R1
        // reservation is evidence, not a lock: another workload may have
        // started using the logical database after the snapshot was captured.
        $liveIsolation = self::redisDatabaseIsEmpty($database);
        if (empty($liveIsolation['ok'])) {
            return self::error(__('The reserved Redis database could not be re-verified immediately before activation.', 'yablonsky-performance'));
        }
        if (empty($liveIsolation['empty'])) {
            return self::error(__('The reserved Redis database is no longer empty. YPerf stopped before writing object-cache.php; release the reservation and refresh Host Intelligence.', 'yablonsky-performance'));
        }

        $fs = self::filesystem();
        if ($fs === null) {
            return self::error(__('WordPress Filesystem could not obtain direct write access to wp-content.', 'yablonsky-performance'));
        }
        $target = self::targetPath();
        if (!self::safeDirectChildPath($target) || is_link($target)) {
            return self::error(__('object-cache.php appeared after the contract was frozen. YPerf will not overwrite it.', 'yablonsky-performance'));
        }
        if ($fs->exists($target)) {
            return self::error(__('object-cache.php appeared after the contract was frozen. YPerf will not overwrite it.', 'yablonsky-performance'));
        }
        $templatePath = dirname(__DIR__) . '/dropins/object-cache.php.tpl';
        $template = $fs->get_contents($templatePath);
        if (!is_string($template) || $template === '') {
            return self::error(__('The YPerf object-cache drop-in template is unavailable.', 'yablonsky-performance'));
        }
        $rendered = str_replace(
            array('__YPERF_CONTRACT__', '__YPERF_REDIS_DB__', '__YPERF_PREFIX__'),
            array($fingerprint, (string) $database, self::escapeSingleQuotedPhpString($prefix)),
            $template
        );
        if (strpos($rendered, '__YPERF_') !== false) {
            return self::error(__('The object-cache drop-in template could not be rendered safely.', 'yablonsky-performance'));
        }

        $temporary = $target . '.yperf-tmp';
        if (!self::safeDirectChildPath($temporary) || is_link($temporary)) {
            return self::error(__('YPerf could not stage the object-cache drop-in.', 'yablonsky-performance'));
        }
        if ($fs->exists($temporary)) {
            if (is_link($temporary) || !$fs->delete($temporary, false, 'f')) {
                return self::error(__('YPerf could not stage the object-cache drop-in.', 'yablonsky-performance'));
            }
        }
        $chmod = defined('FS_CHMOD_FILE') ? FS_CHMOD_FILE : 0644;
        if (!$fs->put_contents($temporary, $rendered, $chmod)) {
            return self::error(__('YPerf could not stage the object-cache drop-in.', 'yablonsky-performance'));
        }
        $staged = $fs->get_contents($temporary);
        if (!is_string($staged) || !hash_equals(hash('sha256', $rendered), hash('sha256', $staged))) {
            $fs->delete($temporary, false, 'f');
            return self::error(__('The staged object-cache drop-in failed its read-back hash check.', 'yablonsky-performance'));
        }
        if (!$fs->move($temporary, $target, false)) {
            $fs->delete($temporary, false, 'f');
            return self::error(__('YPerf could not atomically place wp-content/object-cache.php.', 'yablonsky-performance'));
        }
        $placed = $fs->get_contents($target);
        if (is_link($target) || !is_string($placed) || !hash_equals(hash('sha256', $rendered), hash('sha256', $placed))) {
            if (!is_link($target) && is_string($placed) && hash_equals(hash('sha256', $rendered), hash('sha256', $placed))) {
                $fs->delete($target, false, 'f');
            }
            return self::error(__('The staged object-cache drop-in failed its read-back hash check.', 'yablonsky-performance'));
        }

        $pending = array(
            'schema' => 1,
            'started_at' => time(),
            'yperf_version' => YPERF_VERSION,
            'fingerprint' => $fingerprint,
            'database' => $database,
            'key_prefix' => $prefix,
            'dropin_sha256' => hash('sha256', $rendered),
            'state' => 'pending_verification',
        );
        update_option(self::PENDING, $pending, false);
        update_option(self::OPTION, $pending, false);
        self::schedulePendingWatchdog((int) $pending['started_at'] + self::MAX_PENDING_AGE + 5);
        return array('ok' => true, 'state' => 'pending_verification', 'fingerprint' => $fingerprint);
    }

    /** @return array<string,mixed> */
    public static function finalize(): array
    {
        $pending = self::lastPending();
        if ($pending === array()) {
            return self::error(__('No pending YPerf object-cache activation exists.', 'yablonsky-performance'));
        }
        $age = max(0, time() - (int) ($pending['started_at'] ?? 0));
        if ($age >= self::MAX_PENDING_AGE) {
            return self::rollbackWithFailure($pending, __('Object-cache verification expired before completion.', 'yablonsky-performance'));
        }

        $ownership = DropinOwnership::inspect();
        $object = is_array($ownership['object_cache'] ?? null) ? $ownership['object_cache'] : array();
        $owned = !empty($object['present']) && (string) ($object['owner'] ?? '') === 'yablonsky-performance';
        $external = !empty($ownership['runtime']['external_object_cache']);
        if (!$owned || !$external) {
            return self::rollbackWithFailure($pending, __('WordPress did not load the newly written YPerf object-cache drop-in as the external object cache.', 'yablonsky-performance'));
        }
        $expectedSha = (string) ($pending['dropin_sha256'] ?? '');
        $actualSha = (string) ($object['sha256'] ?? '');
        if ($expectedSha === '' || $actualSha === '' || !hash_equals($expectedSha, $actualSha)) {
            return self::rollbackWithFailure($pending, __('The loaded object-cache drop-in does not match the approved artifact hash.', 'yablonsky-performance'));
        }

        $runtimeStatus = function_exists('yperf_object_cache_runtime_status') ? yperf_object_cache_runtime_status() : array();
        $runtimeRedisConnected = !empty($runtimeStatus['redis_connected']);
        $runtimeDatabaseMatches = isset($runtimeStatus['database']) && (int) $runtimeStatus['database'] === (int) ($pending['database'] ?? -1);
        $runtimePrefixMatches = isset($runtimeStatus['prefix']) && hash_equals((string) ($pending['key_prefix'] ?? ''), (string) $runtimeStatus['prefix']);
        $runtimePayloadAuthenticated = !empty($runtimeStatus['payload_authentication'])
            && (string) ($runtimeStatus['payload_format'] ?? '') === 'yperf2-hmac-sha256';
        if (!$runtimeRedisConnected || !$runtimeDatabaseMatches || !$runtimePrefixMatches || !$runtimePayloadAuthenticated) {
            return self::rollbackWithFailure($pending, __('The loaded YPerf drop-in did not prove its own Redis connection, database and namespace.', 'yablonsky-performance'));
        }

        $roundtrip = self::redisRoundTrip((int) ($pending['database'] ?? -1), (string) ($pending['key_prefix'] ?? ''));
        if (empty($roundtrip['ok'])) {
            return self::rollbackWithFailure($pending, __('The reserved Redis target failed its bounded write/read/delete proof.', 'yablonsky-performance'));
        }

        $runtime = RuntimeAcceptance::run();
        update_option('yperf_runtime_acceptance_v1', $runtime, false);
        Telemetry::recordRuntime($runtime);
        RuntimeHistory::record($runtime, 'object_cache_activation');
        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        if ((int) ($summary['fail'] ?? 0) > 0) {
            return self::rollbackWithFailure($pending, __('Runtime Acceptance failed after enabling the YPerf object cache, so the drop-in was removed.', 'yablonsky-performance'), $runtime);
        }

        $record = array_merge($pending, array(
            'state' => 'active',
            'verified_at' => time(),
            'redis_roundtrip' => true,
            'wordpress_external_object_cache' => true,
            'dropin_runtime_redis_connected' => true,
            'dropin_payload_authenticated' => true,
            'runtime_pass' => (int) ($summary['pass'] ?? 0),
            'runtime_fail' => (int) ($summary['fail'] ?? 0),
            'rollback_available' => true,
        ));
        update_option(self::OPTION, $record, false);
        delete_option(self::PENDING);
        self::clearPendingWatchdog();
        return array('ok' => true, 'state' => 'active', 'record' => self::publicRecord($record));
    }

    /** @return array<string,mixed> */
    public static function rollback(): array
    {
        $record = self::last();
        $ownership = DropinOwnership::inspect();
        $object = is_array($ownership['object_cache'] ?? null) ? $ownership['object_cache'] : array();
        if (empty($object['present'])) {
            delete_option(self::PENDING);
            self::clearPendingWatchdog();
            $record['state'] = 'inactive';
            $record['rolled_back_at'] = time();
            update_option(self::OPTION, $record, false);
            ObjectCacheAdoption::release();
            return array('ok' => true, 'state' => 'inactive');
        }
        if ((string) ($object['owner'] ?? '') !== 'yablonsky-performance') {
            return self::error(__('object-cache.php is not owned by Yablonsky Performance. YPerf refuses to remove another provider’s drop-in.', 'yablonsky-performance'));
        }
        $target = self::targetPath();
        if (!self::safeDirectChildPath($target) || is_link($target)) {
            return self::error(__('The YPerf object-cache drop-in changed after activation. Automatic removal is blocked until ownership is reviewed.', 'yablonsky-performance'));
        }
        $expectedSha = (string) ($record['dropin_sha256'] ?? '');
        $actualSha = (string) ($object['sha256'] ?? '');
        if ($expectedSha === '' || $actualSha === '' || !hash_equals($expectedSha, $actualSha)) {
            return self::error(__('The YPerf object-cache drop-in changed after activation. Automatic removal is blocked until ownership is reviewed.', 'yablonsky-performance'));
        }
        $fs = self::filesystem();
        if ($fs === null || !$fs->delete($target, false, 'f')) {
            return self::error(__('YPerf could not remove its object-cache drop-in.', 'yablonsky-performance'));
        }
        delete_option(self::PENDING);
        self::clearPendingWatchdog();
        $record['state'] = 'rolled_back';
        $record['rolled_back_at'] = time();
        $record['redis_flushdb'] = false;
        update_option(self::OPTION, $record, false);
        ObjectCacheAdoption::release();
        return array('ok' => true, 'state' => 'rolled_back');
    }

    /** @return array<string,mixed> */
    public static function status(): array
    {
        $record = self::last();
        $pending = self::lastPending();
        $ownership = DropinOwnership::inspect();
        $object = is_array($ownership['object_cache'] ?? null) ? $ownership['object_cache'] : array();
        $owned = !empty($object['present']) && (string) ($object['owner'] ?? '') === 'yablonsky-performance';
        $external = !empty($ownership['runtime']['external_object_cache']);
        $runtimeStatus = function_exists('yperf_object_cache_runtime_status') ? yperf_object_cache_runtime_status() : array();
        $runtimeRedisConnected = !empty($runtimeStatus['redis_connected']);
        $payloadCurrent = !empty($runtimeStatus['payload_authentication'])
            && (string) ($runtimeStatus['payload_format'] ?? '') === 'yperf2-hmac-sha256';
        $state = sanitize_key((string) ($record['state'] ?? 'inactive'));
        if ($pending !== array()) {
            $state = 'pending_verification';
        } elseif ($owned && $external && $payloadCurrent && $runtimeRedisConnected) {
            $state = 'active';
        } elseif ($owned && $external && !$payloadCurrent) {
            $state = 'upgrade_reactivation_required';
        } elseif ($owned && $external && !$runtimeRedisConnected) {
            $state = 'dropin_present_not_active';
        } elseif ($owned && !$external) {
            $state = 'dropin_present_not_active';
        }
        return array(
            'schema' => 1,
            'state' => $state,
            'active' => $state === 'active',
            'pending' => $state === 'pending_verification',
            'dropin_owned' => $owned,
            'wordpress_external_object_cache' => $external,
            'payload_authentication_current' => $payloadCurrent,
            'dropin_runtime_redis_connected' => $runtimeRedisConnected,
            'record' => self::publicRecord($record),
            'safety' => array(
                'flushdb_used' => false,
                'redis_configuration_changed' => false,
                'service_restarted' => false,
                'rollback_removes_only_owned_dropin' => true,
                'rollback_requires_exact_artifact_hash' => true,
                'pending_activation_watchdog' => true,
            ),
        );
    }

    /** @return array<string,mixed> */
    public static function last(): array
    {
        $value = get_option(self::OPTION, array());
        return is_array($value) ? $value : array();
    }

    /** @return array<string,mixed> */
    private static function lastPending(): array
    {
        $value = get_option(self::PENDING, array());
        return is_array($value) ? $value : array();
    }

    /** @param array<string,mixed> $pending @param array<string,mixed> $runtime @return array<string,mixed> */
    private static function rollbackWithFailure(array $pending, string $message, array $runtime = array()): array
    {
        $record = array_merge($pending, array(
            'state' => 'verification_failed',
            'failed_at' => time(),
            'failure_reason' => sanitize_text_field($message),
        ));
        if ($runtime !== array()) {
            $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
            $record['runtime_pass'] = (int) ($summary['pass'] ?? 0);
            $record['runtime_fail'] = (int) ($summary['fail'] ?? 0);
        }
        update_option(self::OPTION, $record, false);
        $result = self::rollback();
        if (empty($result['ok'])) {
            $record['state'] = 'rollback_failed';
            $record['rollback_error'] = sanitize_text_field((string) ($result['message'] ?? ''));
            update_option(self::OPTION, $record, false);
            return array('ok' => false, 'state' => 'rollback_failed', 'message' => $message . ' ' . __('Automatic rollback also failed; inspect drop-in ownership immediately.', 'yablonsky-performance'));
        }
        return array('ok' => false, 'state' => 'rolled_back', 'message' => $message);
    }

    /** @return array{ok:bool,empty:bool} */
    private static function redisDatabaseIsEmpty(int $database): array
    {
        if ($database < 0 || !class_exists('Redis')) {
            return array('ok' => false, 'empty' => false);
        }
        $redis = null;
        try {
            $redis = new \Redis();
            if (!$redis->connect('127.0.0.1', 6379, 0.25) || !$redis->select($database)) {
                return array('ok' => false, 'empty' => false);
            }
            $size = $redis->dbSize();
            return array('ok' => is_int($size), 'empty' => is_int($size) && $size === 0);
        } catch (\Throwable $throwable) {
            return array('ok' => false, 'empty' => false);
        } finally {
            if ($redis instanceof \Redis) {
                try {
                    $redis->close();
                } catch (\Throwable $throwable) {
                }
            }
        }
    }

    /** @return array{ok:bool} */
    private static function redisRoundTrip(int $database, string $prefix): array
    {
        if ($database < 0 || preg_match('/^yperf:[a-f0-9]{12}:$/', $prefix) !== 1 || !class_exists('Redis')) {
            return array('ok' => false);
        }
        $redis = null;
        try {
            $redis = new \Redis();
            if (!$redis->connect('127.0.0.1', 6379, 0.25) || !$redis->select($database)) {
                return array('ok' => false);
            }
            $token = wp_generate_uuid4();
            $key = $prefix . 'health:' . substr(hash('sha256', $token), 0, 24);
            $value = substr(hash('sha256', $token . '|' . microtime(true)), 0, 32);
            if (!$redis->setex($key, 30, $value)) {
                return array('ok' => false);
            }
            $read = $redis->get($key);
            $redis->del($key);
            return array('ok' => is_string($read) && hash_equals($value, $read));
        } catch (\Throwable $throwable) {
            return array('ok' => false);
        } finally {
            if ($redis instanceof \Redis) {
                try {
                    $redis->close();
                } catch (\Throwable $throwable) {
                }
            }
        }
    }

    /** @return object|null */
    private static function filesystem()
    {
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        $ok = WP_Filesystem();
        global $wp_filesystem;
        return $ok && is_object($wp_filesystem) ? $wp_filesystem : null;
    }

    private static function targetPath(): string
    {
        $content = realpath((string) WP_CONTENT_DIR);
        $base = is_string($content) && $content !== '' ? $content : (string) WP_CONTENT_DIR;
        return rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'object-cache.php';
    }

    private static function safeDirectChildPath(string $path): bool
    {
        $content = realpath((string) WP_CONTENT_DIR);
        if (!is_string($content) || $content === '') {
            return false;
        }
        $parent = realpath(dirname($path));
        return is_string($parent) && $parent !== '' && hash_equals($content, $parent)
            && in_array(basename($path), array('object-cache.php', 'object-cache.php.yperf-tmp'), true);
    }

    private static function schedulePendingWatchdog(int $timestamp): void
    {
        if (!function_exists('wp_next_scheduled') || !function_exists('wp_schedule_single_event')) {
            return;
        }
        if (wp_next_scheduled(self::WATCHDOG_HOOK) !== false) {
            return;
        }
        wp_schedule_single_event(max(time() + 1, $timestamp), self::WATCHDOG_HOOK);
    }

    private static function clearPendingWatchdog(): void
    {
        if (function_exists('wp_clear_scheduled_hook')) {
            wp_clear_scheduled_hook(self::WATCHDOG_HOOK);
        }
    }

    private static function escapeSingleQuotedPhpString(string $value): string
    {
        return str_replace(array('\\', "'"), array('\\\\', "\\'"), $value);
    }

    /** @param array<string,mixed> $record @return array<string,mixed> */
    private static function publicRecord(array $record): array
    {
        if ($record === array()) {
            return array();
        }
        return array(
            'state' => sanitize_key((string) ($record['state'] ?? 'inactive')),
            'started_at' => max(0, (int) ($record['started_at'] ?? 0)),
            'verified_at' => max(0, (int) ($record['verified_at'] ?? 0)),
            'rolled_back_at' => max(0, (int) ($record['rolled_back_at'] ?? 0)),
            'yperf_version' => sanitize_text_field((string) ($record['yperf_version'] ?? '')),
            'database' => (int) ($record['database'] ?? -1),
            'key_prefix' => sanitize_text_field((string) ($record['key_prefix'] ?? '')),
            'fingerprint' => preg_match('/^[a-f0-9]{64}$/', (string) ($record['fingerprint'] ?? '')) === 1 ? (string) $record['fingerprint'] : '',
            'dropin_sha256' => preg_match('/^[a-f0-9]{64}$/', (string) ($record['dropin_sha256'] ?? '')) === 1 ? (string) $record['dropin_sha256'] : '',
            'redis_roundtrip' => !empty($record['redis_roundtrip']),
            'wordpress_external_object_cache' => !empty($record['wordpress_external_object_cache']),
            'dropin_runtime_redis_connected' => !empty($record['dropin_runtime_redis_connected']),
            'dropin_payload_authenticated' => !empty($record['dropin_payload_authenticated']),
            'runtime_pass' => max(0, (int) ($record['runtime_pass'] ?? 0)),
            'runtime_fail' => max(0, (int) ($record['runtime_fail'] ?? 0)),
            'rollback_available' => !empty($record['rollback_available']),
        );
    }

    /** @return array{ok:false,message:string} */
    private static function error(string $message): array
    {
        return array('ok' => false, 'message' => sanitize_text_field($message));
    }
}
