<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Read-only evidence for WordPress control-plane install/update requests.
 *
 * The collector never changes upgrader behavior or captures response bodies.
 * It records bounded lifecycle, fatal and resource-headroom evidence so an
 * empty wp-admin response can be separated from cache, memory and upgrader
 * failures without storing query strings, nonces or raw fatal messages.
 */
final class UpgradeHealth
{
    private const OPTION = 'yperf_upgrade_health_v1';
    private const MAX_EVENTS = 24;

    /** @var string */
    private static $requestId = '';

    /** @var string */
    private static $route = '';

    /** @var bool */
    private static $shutdownRegistered = false;

    public static function register(): void
    {
        add_action('upgrader_process_complete', array(__CLASS__, 'recordProcessComplete'), PHP_INT_MAX, 2);
        add_action('upgrader_overwrote_package', array(__CLASS__, 'recordOverwrite'), PHP_INT_MAX, 3);

        self::$route = self::controlPlaneRoute();
        if (self::$route !== '') {
            self::registerShutdown();
        }
        add_action('current_screen', array(__CLASS__, 'registerScreenShutdown'), 1);
    }


    /** @param mixed $screen */
    public static function registerScreenShutdown($screen): void
    {
        if (!is_object($screen)) {
            return;
        }
        $id = isset($screen->id) ? sanitize_key((string) $screen->id) : '';
        if ($id === '' || strpos($id, 'plugin-check') === false) {
            return;
        }
        self::$route = 'plugin-check';
        self::registerShutdown();
    }

    /** @param mixed $upgrader @param array<string,mixed> $hookExtra */
    public static function recordProcessComplete($upgrader, array $hookExtra): void
    {
        unset($upgrader);
        self::ensureRequestIdentity();
        self::append(array(
            'kind' => 'upgrader_process_complete',
            'request_id' => self::$requestId,
            'type' => sanitize_key((string) ($hookExtra['type'] ?? '')),
            'action' => sanitize_key((string) ($hookExtra['action'] ?? '')),
            'bulk' => !empty($hookExtra['bulk']),
            'plugins_count' => isset($hookExtra['plugins']) && is_array($hookExtra['plugins']) ? count($hookExtra['plugins']) : 0,
            'theme_present' => !empty($hookExtra['theme']),
        ));
    }

    /** @param array<string,mixed> $data */
    public static function recordOverwrite(string $package, array $data, string $packageType): void
    {
        unset($package);
        self::ensureRequestIdentity();
        self::append(array(
            'kind' => 'upgrader_overwrote_package',
            'request_id' => self::$requestId,
            'type' => sanitize_key($packageType),
            'name' => sanitize_text_field((string) ($data['Name'] ?? $data['Title'] ?? '')),
            'version' => sanitize_text_field((string) ($data['Version'] ?? '')),
        ));
    }

    public static function recordShutdown(): void
    {
        self::ensureRequestIdentity();
        $error = error_get_last();
        $fatalTypes = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR);
        $fatal = is_array($error) && in_array((int) ($error['type'] ?? 0), $fatalTypes, true);
        $message = $fatal && is_array($error) ? sanitize_text_field((string) ($error['message'] ?? '')) : '';
        $fatalClass = self::fatalClass($message);
        $memory = self::memorySnapshot();
        $headersFile = '';
        $headersLine = 0;
        $headersSent = headers_sent($headersFile, $headersLine);
        $bufferLength = ob_get_level() > 0 ? ob_get_length() : false;

        $event = array(
            'kind' => 'control_plane_shutdown',
            'request_id' => self::$requestId,
            'route' => self::$route,
            'request_method' => self::requestMethod(),
            'response_code' => (int) http_response_code(),
            'memory_limit_mb' => $memory['memory_limit_mb'],
            'peak_memory_mb' => $memory['peak_memory_mb'],
            'headroom_mb' => $memory['headroom_mb'],
            'output_buffer_level' => ob_get_level(),
            'output_buffer_bytes' => is_int($bufferLength) ? max(0, $bufferLength) : -1,
            'headers_sent' => $headersSent,
            'fatal' => $fatal,
            'fatal_class' => $fatalClass,
            'response_cache' => self::responseCacheHeaders(),
        );
        if ($headersSent && $headersFile !== '') {
            $event['headers_sent_file'] = self::safeFileLabel($headersFile);
            $event['headers_sent_line'] = max(0, $headersLine);
        }
        if ($fatal && is_array($error)) {
            $event['fatal_type'] = (int) ($error['type'] ?? 0);
            $event['fatal_file'] = self::safeFileLabel((string) ($error['file'] ?? ''));
            $event['fatal_line'] = max(0, (int) ($error['line'] ?? 0));
            $event['fatal_message_hash'] = $message !== '' ? hash('sha256', $message) : '';
        }
        self::append($event);
    }

    /** @return array<string,mixed> */
    public static function report(): array
    {
        $events = get_option(self::OPTION, array());
        if (!is_array($events)) {
            $events = array();
        }
        $events = array_values(array_filter($events, 'is_array'));

        $lastShutdown = array();
        $lastUpdateShutdown = array();
        $lastMemoryFatal = array();
        foreach ($events as $event) {
            $kind = sanitize_key((string) ($event['kind'] ?? ''));
            $route = sanitize_text_field((string) ($event['route'] ?? ''));
            if ($lastShutdown === array() && $kind === 'control_plane_shutdown') {
                $lastShutdown = $event;
            }
            if ($lastUpdateShutdown === array() && $kind === 'control_plane_shutdown' && strpos($route, 'update.php') !== false) {
                $lastUpdateShutdown = $event;
            }
            if ($lastMemoryFatal === array() && !empty($event['fatal']) && (string) ($event['fatal_class'] ?? '') === 'memory_exhaustion') {
                $lastMemoryFatal = $event;
            }
        }

        $requestId = sanitize_text_field((string) ($lastUpdateShutdown['request_id'] ?? ''));
        $correlatedProcess = array();
        $correlatedOverwrite = array();
        if ($requestId !== '') {
            foreach ($events as $event) {
                if (!is_array($event) || (string) ($event['request_id'] ?? '') !== $requestId) {
                    continue;
                }
                $kind = sanitize_key((string) ($event['kind'] ?? ''));
                if ($correlatedProcess === array() && $kind === 'upgrader_process_complete') {
                    $correlatedProcess = $event;
                }
                if ($correlatedOverwrite === array() && $kind === 'upgrader_overwrote_package') {
                    $correlatedOverwrite = $event;
                }
            }
        }

        $correlated = self::classifyCorrelatedUpdate($lastUpdateShutdown, $correlatedProcess, $correlatedOverwrite);
        $resource = self::resourceHeadroom();
        $state = 'no_evidence';
        $label = 'No control-plane evidence yet';
        if ((string) ($correlated['state'] ?? '') === 'completed_response_failed') {
            $state = 'response_failure_after_upgrade';
            $label = 'Upgrader completed, response failed';
        } elseif ((string) ($correlated['state'] ?? '') === 'completed_clean') {
            $state = 'upgrader_completed';
            $label = 'Correlated upgrader completion observed';
        } elseif (!empty($lastShutdown['fatal'])) {
            $state = 'fatal_observed';
            $label = 'PHP fatal observed in wp-admin';
        } elseif ((string) ($resource['state'] ?? '') === 'constrained') {
            $state = 'resource_constrained';
            $label = 'Admin resource headroom is constrained';
        } elseif ($lastShutdown !== array()) {
            $state = 'control_plane_observed';
            $label = 'wp-admin control-plane request observed';
        }

        return array(
            'schema' => 3,
            'state' => $state,
            'label' => $label,
            'events_retained' => min(self::MAX_EVENTS, count($events)),
            'resource_headroom' => $resource,
            'correlated_update' => $correlated,
            'last_shutdown' => self::sanitizeEvent($lastShutdown),
            'last_update_shutdown' => self::sanitizeEvent($lastUpdateShutdown),
            'last_memory_fatal' => self::sanitizeEvent($lastMemoryFatal),
            'events' => array_map(array(__CLASS__, 'sanitizeEvent'), array_slice($events, 0, self::MAX_EVENTS)),
            'safety' => array(
                'changes_upgrader_behavior' => false,
                'changes_memory_limit' => false,
                'changes_cache' => false,
                'remote_sent' => false,
                'query_strings_stored' => false,
                'nonces_stored' => false,
                'error_messages_stored' => false,
                'response_bodies_stored' => false,
            ),
        );
    }

    /** @param array<string,mixed> $shutdown @param array<string,mixed> $process @param array<string,mixed> $overwrite @return array<string,mixed> */
    private static function classifyCorrelatedUpdate(array $shutdown, array $process, array $overwrite): array
    {
        if ($shutdown === array()) {
            return array('state' => 'no_update_request', 'label' => 'No update.php request retained');
        }
        $responseCode = (int) ($shutdown['response_code'] ?? 0);
        $fatal = !empty($shutdown['fatal']);
        $processReached = $process !== array();
        $overwriteReached = $overwrite !== array();
        $state = 'request_observed';
        $label = 'Update request observed';
        if ($processReached && !$fatal && $responseCode >= 200 && $responseCode < 400) {
            $state = 'completed_clean';
            $label = 'Upgrader and response completed cleanly';
        } elseif ($processReached && ($fatal || $responseCode >= 500)) {
            $state = 'completed_response_failed';
            $label = 'Upgrader completed but response failed';
        } elseif (!$processReached && ($fatal || $responseCode >= 500)) {
            $state = 'request_failed_before_completion';
            $label = 'Update request failed before upgrader completion';
        }
        return array(
            'state' => $state,
            'label' => $label,
            'request_id' => sanitize_text_field((string) ($shutdown['request_id'] ?? '')),
            'process_complete' => $processReached,
            'overwrite_complete' => $overwriteReached,
            'response_code' => $responseCode,
            'fatal' => $fatal,
            'fatal_class' => sanitize_key((string) ($shutdown['fatal_class'] ?? '')),
            'memory_limit_mb' => (float) ($shutdown['memory_limit_mb'] ?? -1),
            'peak_memory_mb' => (float) ($shutdown['peak_memory_mb'] ?? -1),
            'headroom_mb' => (float) ($shutdown['headroom_mb'] ?? -1),
            'headers_sent' => !empty($shutdown['headers_sent']),
            'output_buffer_level' => (int) ($shutdown['output_buffer_level'] ?? -1),
            'output_buffer_bytes' => (int) ($shutdown['output_buffer_bytes'] ?? -1),
        );
    }

    /** @return array<string,mixed> */
    private static function resourceHeadroom(): array
    {
        $memory = self::memorySnapshot();
        $active = get_option('active_plugins', array());
        $active = is_array($active) ? array_values(array_filter($active, 'is_string')) : array();
        $pluginCheckActive = in_array('plugin-check/plugin.php', $active, true);
        $limit = (float) $memory['memory_limit_mb'];
        $headroom = (float) $memory['headroom_mb'];
        $activeCount = count($active);

        $state = 'healthy';
        $label = 'Admin memory headroom looks reasonable';
        if ($limit > 0 && (($pluginCheckActive && $limit <= 128.0) || ($activeCount >= 18 && $limit <= 128.0) || ($headroom >= 0 && $headroom < 24.0))) {
            $state = 'constrained';
            $label = 'Admin memory headroom is constrained';
        } elseif ($limit > 0 && ($limit <= 192.0 || ($headroom >= 0 && $headroom < 48.0))) {
            $state = 'attention';
            $label = 'Admin memory headroom deserves attention';
        }

        return array(
            'state' => $state,
            'label' => $label,
            'memory_limit_raw' => sanitize_text_field((string) ini_get('memory_limit')),
            'memory_limit_mb' => $memory['memory_limit_mb'],
            'current_memory_mb' => $memory['current_memory_mb'],
            'peak_memory_mb' => $memory['peak_memory_mb'],
            'headroom_mb' => $memory['headroom_mb'],
            'wp_memory_limit' => defined('WP_MEMORY_LIMIT') ? sanitize_text_field((string) WP_MEMORY_LIMIT) : '',
            'wp_max_memory_limit' => defined('WP_MAX_MEMORY_LIMIT') ? sanitize_text_field((string) WP_MAX_MEMORY_LIMIT) : '',
            'active_plugins' => $activeCount,
            'plugin_check_active' => $pluginCheckActive,
            'recommendation' => $state === 'constrained'
                ? 'Increase admin/PHP memory through hosting or server configuration before relying on heavy static-analysis or upgrade workloads. YPerf does not change this limit automatically.'
                : 'Keep current headroom visible during heavy plugin checks and upgrade operations.',
        );
    }

    /** @return array<string,float> */
    private static function memorySnapshot(): array
    {
        $limit = self::parseMemoryLimit((string) ini_get('memory_limit'));
        $current = round(memory_get_usage(true) / 1048576, 2);
        $peak = round(memory_get_peak_usage(true) / 1048576, 2);
        $headroom = $limit > 0 ? max(0.0, round($limit - $peak, 2)) : -1.0;
        return array(
            'memory_limit_mb' => $limit,
            'current_memory_mb' => $current,
            'peak_memory_mb' => $peak,
            'headroom_mb' => $headroom,
        );
    }

    private static function parseMemoryLimit(string $raw): float
    {
        $raw = trim($raw);
        if ($raw === '' || $raw === '-1') {
            return -1.0;
        }
        if (!preg_match('/^([0-9]+(?:\.[0-9]+)?)\s*([KMG]?)$/i', $raw, $matches)) {
            return -1.0;
        }
        $value = (float) $matches[1];
        $unit = strtoupper((string) ($matches[2] ?? ''));
        if ($unit === 'G') {
            return round($value * 1024, 2);
        }
        if ($unit === 'K') {
            return round($value / 1024, 2);
        }
        if ($unit === 'M') {
            return round($value, 2);
        }
        return round($value / 1048576, 2);
    }

    private static function fatalClass(string $message): string
    {
        $lower = strtolower($message);
        if ($lower === '') {
            return '';
        }
        if (strpos($lower, 'allowed memory size') !== false || strpos($lower, 'out of memory') !== false) {
            return 'memory_exhaustion';
        }
        if (strpos($lower, 'maximum execution time') !== false) {
            return 'execution_timeout';
        }
        return 'php_fatal';
    }

    private static function ensureRequestIdentity(): void
    {
        if (self::$requestId === '') {
            self::$requestId = wp_generate_uuid4();
        }
        if (self::$route === '') {
            self::$route = self::controlPlaneRoute();
            if (self::$route === '') {
                self::$route = 'upgrader_hook';
            }
        }
    }

    private static function registerShutdown(): void
    {
        if (self::$shutdownRegistered) {
            return;
        }
        if (self::$requestId === '') {
            self::$requestId = wp_generate_uuid4();
        }
        self::$shutdownRegistered = true;
        add_action('shutdown', array(__CLASS__, 'recordShutdown'), PHP_INT_MAX);
    }

    private static function controlPlaneRoute(): string
    {
        if (!is_admin()) {
            return '';
        }
        $script = isset($_SERVER['SCRIPT_NAME']) ? sanitize_text_field(wp_unslash((string) $_SERVER['SCRIPT_NAME'])) : '';
        $base = basename($script);
        if ($base === 'update.php') {
            return 'update.php';
        }
        if ($base === 'plugins.php') {
            return 'plugins.php';
        }
        if ($base === 'plugin-install.php') {
            return 'plugin-install.php';
        }
        return '';
    }

    /** @param array<string,mixed> $event */
    private static function append(array $event): void
    {
        $events = get_option(self::OPTION, array());
        if (!is_array($events)) {
            $events = array();
        }
        $event['timestamp'] = time();
        if (!isset($event['route'])) {
            $event['route'] = self::$route !== '' ? self::$route : 'upgrader_hook';
        }
        array_unshift($events, self::sanitizeEvent($event));
        update_option(self::OPTION, array_slice($events, 0, self::MAX_EVENTS), false);
    }

    private static function requestMethod(): string
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_key(wp_unslash((string) $_SERVER['REQUEST_METHOD'])) : '';
        $method = strtoupper($method);
        return in_array($method, array('GET', 'POST', 'HEAD'), true) ? $method : 'OTHER';
    }

    /** @return array<string,string> */
    private static function responseCacheHeaders(): array
    {
        $allowed = array('cache-control', 'pragma', 'expires', 'x-cache', 'x-cache-status', 'x-fastcgi-cache', 'cf-cache-status');
        $result = array();
        foreach (headers_list() as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }
            list($name, $value) = array_map('trim', explode(':', $line, 2));
            $key = strtolower($name);
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            $result[$key] = sanitize_text_field($value);
        }
        return $result;
    }

    private static function safeFileLabel(string $file): string
    {
        $file = wp_normalize_path($file);
        $content = defined('WP_CONTENT_DIR') ? wp_normalize_path((string) WP_CONTENT_DIR) : '';
        if ($content !== '' && strpos($file, $content . '/') === 0) {
            return 'wp-content/' . ltrim(substr($file, strlen($content)), '/');
        }
        return basename($file);
    }

    /** @param array<string,mixed> $event @return array<string,mixed> */
    private static function sanitizeEvent(array $event): array
    {
        $clean = array();
        foreach ($event as $key => $value) {
            $safeKey = sanitize_key((string) $key);
            if ($safeKey === '') {
                continue;
            }
            if (is_bool($value) || is_int($value) || is_float($value)) {
                $clean[$safeKey] = $value;
            } elseif (is_string($value)) {
                $clean[$safeKey] = sanitize_text_field($value);
            } elseif (is_array($value)) {
                $nested = array();
                foreach ($value as $nestedKey => $nestedValue) {
                    if (!is_scalar($nestedValue)) {
                        continue;
                    }
                    $nested[sanitize_key((string) $nestedKey)] = sanitize_text_field((string) $nestedValue);
                }
                $clean[$safeKey] = $nested;
            }
        }
        return $clean;
    }
}
