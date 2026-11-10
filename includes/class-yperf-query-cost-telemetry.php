<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Probe-bounded origin query telemetry.
 *
 * The collector is activated only for a signed YPerf Runtime Acceptance
 * request. Normal visitor requests do not enable wpdb query recording.
 * Raw SQL, credentials and query arguments are never persisted or exported.
 */
final class QueryCostTelemetry
{
    private const HEADER_TOKEN = 'HTTP_X_YPERF_PROBE_TOKEN';
    private const HEADER_SIGNATURE = 'HTTP_X_YPERF_PROBE_SIGNATURE';
    private const TRANSIENT_PREFIX = 'yperf_probe_origin_';

    /** @var string */
    private static $token = '';

    /** @var float */
    private static $started = 0.0;

    /** @var bool */
    private static $persisted = false;

    public static function register(): void
    {
        if (!self::authorizedProbeRequest()) {
            return;
        }

        global $wpdb;
        if (!is_object($wpdb)) {
            return;
        }

        /*
         * wpdb records timing data when SAVEQUERIES is true at query time.
         * It does not expose a portable `save_queries` property that can be
         * switched on after construction. Because this code runs only inside
         * an authenticated YPerf probe request, defining SAVEQUERIES here
         * keeps the overhead bounded to this one request while allowing the
         * remainder of WordPress rendering to be measured accurately.
         */
        if (defined('SAVEQUERIES') && !SAVEQUERIES) {
            add_action('send_headers', array(__CLASS__, 'emitUnavailableHeader'), PHP_INT_MAX);
            return;
        }
        if (!defined('SAVEQUERIES')) {
            // SAVEQUERIES is a WordPress core debugging constant. YPerf defines
            // it only inside an authenticated, signed Runtime Acceptance probe.
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Intentional WordPress core constant; never a YPerf global.
            define('SAVEQUERIES', true);
        }
        if (!SAVEQUERIES) {
            return;
        }
        if (!isset($wpdb->queries) || !is_array($wpdb->queries)) {
            $wpdb->queries = array();
        }
        self::$started = microtime(true);

        // Persist on wp_footer before the HTTP client is allowed to observe a
        // completed HTML response. Shutdown remains a final fallback for
        // templates that omit wp_footer. persist() is idempotent.
        add_action('wp_footer', array(__CLASS__, 'persist'), PHP_INT_MAX);
        add_action('shutdown', array(__CLASS__, 'persist'), PHP_INT_MAX);
    }


    public static function emitUnavailableHeader(): void
    {
        if (!headers_sent()) {
            header('X-YPerf-Origin-Telemetry: savequeries-disabled');
        }
    }

    /** @return array{token:string,signature:string} */
    public static function credentials(): array
    {
        try {
            $token = bin2hex(random_bytes(16));
        } catch (\Throwable $throwable) {
            $token = md5(wp_generate_uuid4() . '|' . microtime(true));
        }
        return array(
            'token' => $token,
            'signature' => hash_hmac('sha256', $token, wp_salt('auth')),
        );
    }

    /** @return array<string,mixed> */
    public static function consume(string $token): array
    {
        if (preg_match('/^[a-f0-9]{32}$/', $token) !== 1) {
            return array();
        }
        $key = self::transientKey($token);
        $value = get_transient($key);
        delete_transient($key);
        return is_array($value) ? self::sanitizeMetrics($value) : array();
    }

    public static function persist(): void
    {
        if (self::$token === '' || self::$persisted) {
            return;
        }
        self::$persisted = true;

        global $wpdb;
        $queries = is_object($wpdb) && isset($wpdb->queries) && is_array($wpdb->queries) ? $wpdb->queries : array();
        $metrics = self::aggregate($queries);
        $metrics['php_elapsed_ms'] = max(0.0, round((microtime(true) - self::$started) * 1000, 2));
        $metrics['peak_memory_mb'] = round(memory_get_peak_usage(true) / 1048576, 2);
        $metrics['scope'] = 'signed_runtime_probe_only';
        $metrics['capture_phase'] = function_exists('current_filter') ? sanitize_key((string) current_filter()) : '';
        $metrics['raw_sql_exported'] = false;

        set_transient(self::transientKey(self::$token), $metrics, 60);
    }

    /** @param array<int,mixed> $queries @return array<string,mixed> */
    private static function aggregate(array $queries): array
    {
        global $wpdb;

        $totalMs = 0.0;
        $maxMs = 0.0;
        $slow25 = 0;
        $slow100 = 0;
        $foundRows = 0;
        $selects = 0;
        $writes = 0;
        $families = array(
            'posts' => 0,
            'postmeta' => 0,
            'terms' => 0,
            'options' => 0,
            'woocommerce_lookup' => 0,
            'other' => 0,
        );
        $maxSignature = '';
        $maxType = 'other';
        $hotspots = array();

        foreach ($queries as $entry) {
            if (!is_array($entry) || !isset($entry[0], $entry[1])) {
                continue;
            }
            $sql = (string) $entry[0];
            $elapsedMs = max(0.0, (float) $entry[1] * 1000);
            $totalMs += $elapsedMs;
            if ($elapsedMs >= 25.0) {
                ++$slow25;
            }
            if ($elapsedMs >= 100.0) {
                ++$slow100;
            }
            if (stripos($sql, 'SQL_CALC_FOUND_ROWS') !== false) {
                ++$foundRows;
            }
            if (preg_match('/^\\s*SELECT\\b/i', $sql) === 1) {
                ++$selects;
            } elseif (preg_match('/^\\s*(INSERT|UPDATE|DELETE|REPLACE)\\b/i', $sql) === 1) {
                ++$writes;
            }

            $family = self::queryFamily($sql, $wpdb);
            ++$families[$family];

            // Collect SQL shapes only inside signed Runtime Acceptance probes.
            // Never store SQL, arguments, caller strings or personal data.
            if ($elapsedMs >= 25.0) {
                $shapeHash = hash('sha256', self::normalizeSqlShape($sql));
                if (!isset($hotspots[$shapeHash])) {
                    $hotspots[$shapeHash] = array(
                        'signature_sha256' => $shapeHash,
                        'family' => $family,
                        'caller_class' => self::callerClass((string) ($entry[2] ?? '')),
                        'count' => 0,
                        'total_ms' => 0.0,
                        'max_ms' => 0.0,
                        'found_rows' => stripos($sql, 'SQL_CALC_FOUND_ROWS') !== false,
                        'order_by' => preg_match('/\bORDER\s+BY\b/i', $sql) === 1,
                        'group_by' => preg_match('/\bGROUP\s+BY\b/i', $sql) === 1,
                        'joins' => preg_match('/\bJOIN\b/i', $sql) === 1,
                        'has_limit' => preg_match('/\bLIMIT\b/i', $sql) === 1,
                    );
                }
                ++$hotspots[$shapeHash]['count'];
                $hotspots[$shapeHash]['total_ms'] += $elapsedMs;
                $hotspots[$shapeHash]['max_ms'] = max($hotspots[$shapeHash]['max_ms'], $elapsedMs);
            }

            if ($elapsedMs > $maxMs) {
                $maxMs = $elapsedMs;
                $maxType = $family;
                $maxSignature = hash('sha256', self::normalizeSqlShape($sql));
            }
        }

        // Rank by cumulative time, not one slow outlier. Keep at most five
        // read-only fingerprints per route and no raw SQL or call-site strings.
        $rankedHotspots = array_values($hotspots);
        usort($rankedHotspots, static function (array $left, array $right): int {
            $totalOrder = (float) $right['total_ms'] <=> (float) $left['total_ms'];
            return $totalOrder !== 0 ? $totalOrder : strcmp((string) $left['signature_sha256'], (string) $right['signature_sha256']);
        });
        $rankedHotspots = array_slice($rankedHotspots, 0, 5);
        foreach ($rankedHotspots as &$hotspot) {
            $hotspot['total_ms'] = round((float) $hotspot['total_ms'], 2);
            $hotspot['max_ms'] = round((float) $hotspot['max_ms'], 2);
        }
        unset($hotspot);

        return array(
            'state' => 'measured',
            'hotspot_schema' => 1,
            'hotspots' => $rankedHotspots,
            'query_count' => count($queries),
            'db_time_ms' => round($totalMs, 2),
            'max_query_ms' => round($maxMs, 2),
            'slow_queries_25ms' => $slow25,
            'slow_queries_100ms' => $slow100,
            'sql_calc_found_rows_count' => $foundRows,
            'select_count' => $selects,
            'write_count' => $writes,
            'query_families' => $families,
            'max_query_family' => $maxType,
            'max_query_signature_sha256' => $maxSignature,
        );
    }

    /** @param mixed $wpdb */
    private static function queryFamily(string $sql, $wpdb): string
    {
        $tables = array(
            'postmeta' => is_object($wpdb) ? (string) ($wpdb->postmeta ?? '') : '',
            'posts' => is_object($wpdb) ? (string) ($wpdb->posts ?? '') : '',
            'terms' => is_object($wpdb) ? (string) ($wpdb->terms ?? '') : '',
            'options' => is_object($wpdb) ? (string) ($wpdb->options ?? '') : '',
        );
        foreach ($tables as $family => $table) {
            if ($table !== '' && stripos($sql, $table) !== false) {
                return $family;
            }
        }
        if (stripos($sql, 'wc_product_') !== false || stripos($sql, 'woocommerce_') !== false) {
            return 'woocommerce_lookup';
        }
        return 'other';
    }

    private static function callerClass(string $caller): string
    {
        // WordPress returns full caller traces. Export only conservative,
        // explicitly allowed categories; never persist raw traces or paths.
        if (stripos($caller, 'WC_') !== false || stripos($caller, 'WooCommerce') !== false) {
            return 'woocommerce';
        }
        if (stripos($caller, 'WP_Query') !== false) {
            return 'wp_query';
        }
        if (stripos($caller, 'WP_Term_Query') !== false) {
            return 'wp_term_query';
        }
        if (stripos($caller, 'WP_Meta_Query') !== false) {
            return 'wp_meta_query';
        }
        return 'other';
    }

    /** @param mixed $rows @return array<int,array<string,mixed>> */
    public static function sanitizeHotspots($rows): array
    {
        if (!is_array($rows)) {
            return array();
        }
        $clean = array();
        foreach (array_slice($rows, 0, 5) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $hash = (string) ($row['signature_sha256'] ?? '');
            if (preg_match('/^[a-f0-9]{64}$/', $hash) !== 1) {
                continue;
            }
            $family = (string) ($row['family'] ?? 'other');
            $caller = (string) ($row['caller_class'] ?? 'other');
            $clean[] = array(
                'signature_sha256' => $hash,
                'family' => in_array($family, array('posts', 'postmeta', 'terms', 'options', 'woocommerce_lookup', 'other'), true) ? $family : 'other',
                'caller_class' => in_array($caller, array('woocommerce', 'wp_query', 'wp_term_query', 'wp_meta_query', 'other'), true) ? $caller : 'other',
                'count' => min(100000, max(0, (int) ($row['count'] ?? 0))),
                'total_ms' => min(600000.0, max(0.0, round((float) ($row['total_ms'] ?? 0), 2))),
                'max_ms' => min(600000.0, max(0.0, round((float) ($row['max_ms'] ?? 0), 2))),
                'found_rows' => !empty($row['found_rows']),
                'order_by' => !empty($row['order_by']),
                'group_by' => !empty($row['group_by']),
                'joins' => !empty($row['joins']),
                'has_limit' => !empty($row['has_limit']),
            );
        }
        return $clean;
    }

    private static function normalizeSqlShape(string $sql): string
    {
        $sql = preg_replace("/'(?:''|[^'])*'/", "'?'", $sql) ?? $sql;
        $sql = preg_replace('/\\b\\d+(?:\\.\\d+)?\\b/', '?', $sql) ?? $sql;
        $sql = preg_replace('/\\s+/', ' ', trim($sql)) ?? trim($sql);
        return strtolower($sql);
    }

    private static function authorizedProbeRequest(): bool
    {
        $token = isset($_SERVER[self::HEADER_TOKEN]) ? sanitize_text_field(wp_unslash((string) $_SERVER[self::HEADER_TOKEN])) : '';
        $signature = isset($_SERVER[self::HEADER_SIGNATURE]) ? sanitize_text_field(wp_unslash((string) $_SERVER[self::HEADER_SIGNATURE])) : '';
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_USER_AGENT'])) : '';

        if (preg_match('/^[a-f0-9]{32}$/', $token) !== 1 || preg_match('/^[a-f0-9]{64}$/', $signature) !== 1) {
            return false;
        }
        if (!function_exists('wp_salt')) {
            return false;
        }
        $runtimeProbe = strpos($agent, 'Yablonsky-Performance/') === 0;
        $originProbe = strpos($agent, 'Yablonsky-Performance-Origin-Probe/') === 0;
        if (!$runtimeProbe && !$originProbe) {
            return false;
        }
        $expected = hash_hmac('sha256', $token, wp_salt('auth'));
        if (!hash_equals($expected, $signature)) {
            return false;
        }
        self::$token = $token;
        return true;
    }

    private static function transientKey(string $token): string
    {
        return self::TRANSIENT_PREFIX . substr(hash('sha256', $token), 0, 32);
    }

    /** @param array<string,mixed> $metrics @return array<string,mixed> */
    private static function sanitizeMetrics(array $metrics): array
    {
        $families = is_array($metrics['query_families'] ?? null) ? $metrics['query_families'] : array();
        $cleanFamilies = array();
        foreach (array('posts', 'postmeta', 'terms', 'options', 'woocommerce_lookup', 'other') as $key) {
            $cleanFamilies[$key] = max(0, (int) ($families[$key] ?? 0));
        }
        return array(
            'state' => 'measured',
            'query_count' => max(0, (int) ($metrics['query_count'] ?? 0)),
            'db_time_ms' => max(0.0, round((float) ($metrics['db_time_ms'] ?? 0), 2)),
            'max_query_ms' => max(0.0, round((float) ($metrics['max_query_ms'] ?? 0), 2)),
            'slow_queries_25ms' => max(0, (int) ($metrics['slow_queries_25ms'] ?? 0)),
            'slow_queries_100ms' => max(0, (int) ($metrics['slow_queries_100ms'] ?? 0)),
            'sql_calc_found_rows_count' => max(0, (int) ($metrics['sql_calc_found_rows_count'] ?? 0)),
            'select_count' => max(0, (int) ($metrics['select_count'] ?? 0)),
            'write_count' => max(0, (int) ($metrics['write_count'] ?? 0)),
            'query_families' => $cleanFamilies,
            'hotspot_schema' => 1,
            'hotspots' => self::sanitizeHotspots($metrics['hotspots'] ?? array()),
            'max_query_family' => sanitize_key((string) ($metrics['max_query_family'] ?? 'other')),
            'max_query_signature_sha256' => preg_match('/^[a-f0-9]{64}$/', (string) ($metrics['max_query_signature_sha256'] ?? '')) === 1 ? (string) $metrics['max_query_signature_sha256'] : '',
            'php_elapsed_ms' => max(0.0, round((float) ($metrics['php_elapsed_ms'] ?? 0), 2)),
            'peak_memory_mb' => max(0.0, round((float) ($metrics['peak_memory_mb'] ?? 0), 2)),
            'scope' => 'signed_runtime_probe_only',
            'capture_phase' => sanitize_key((string) ($metrics['capture_phase'] ?? '')),
            'raw_sql_exported' => false,
        );
    }
}
