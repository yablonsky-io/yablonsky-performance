<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class Benchmark
{
    private const OPTION = 'yperf_benchmark_report_v1';

    /** @return array<int,array{id:string,label:string}> */
    public static function stepDefinitions(): array
    {
        return array(
            array('id' => 'homepage', 'label' => 'Homepage response'),
            array('id' => 'shop', 'label' => 'WooCommerce shop'),
            array('id' => 'product', 'label' => 'WooCommerce product'),
            array('id' => 'category', 'label' => 'WooCommerce category'),
            array('id' => 'redis', 'label' => 'Redis engine'),
            array('id' => 'memcached', 'label' => 'Memcached engine'),
        );
    }

    /** @return array<string,mixed> */
    public static function runStep(string $step): array
    {
        switch ($step) {
            case 'homepage':
                return self::httpBenchmark(home_url('/'), 'Homepage');
            case 'shop':
                return self::wooShop();
            case 'product':
                return self::wooProduct();
            case 'category':
                return self::wooCategory();
            case 'redis':
                return self::redisBenchmark();
            case 'memcached':
                return self::memcachedBenchmark();
            default:
                return self::result($step, 'fail', 'internal', array(), 'Unknown benchmark step.');
        }
    }

    /** @param array<int,mixed> $results @return array<string,mixed> */
    public static function buildReport(array $results): array
    {
        $clean = array();
        $summary = array('pass' => 0, 'fail' => 0, 'skip' => 0);
        $engineAverages = array();

        foreach ($results as $result) {
            if (!is_array($result)) {
                continue;
            }
            $normalized = self::normalize($result);
            if ($normalized === array()) {
                continue;
            }
            $clean[] = $normalized;
            $status = (string) ($normalized['status'] ?? 'fail');
            if (isset($summary[$status])) {
                ++$summary[$status];
            }
            if (($normalized['kind'] ?? '') === 'engine' && $status === 'pass' && isset($normalized['average_ms'])) {
                $engineAverages[(string) $normalized['id']] = (float) $normalized['average_ms'];
            }
        }

        $fastest = $engineAverages === array() ? 0.0 : min($engineAverages);
        if ($fastest > 0) {
            foreach ($clean as &$item) {
                if (($item['kind'] ?? '') !== 'engine' || ($item['status'] ?? '') !== 'pass' || !isset($item['average_ms'])) {
                    continue;
                }
                $average = (float) $item['average_ms'];
                $item['relative_to_fastest_pct'] = round((($average / $fastest) - 1) * 100, 2);
                $item['fastest'] = abs($average - $fastest) < 0.0001;
            }
            unset($item);
        }

        $report = array(
            'schema'       => 1,
            'generated_at' => time(),
            'plugin'       => 'Yablonsky Performance',
            'version'      => YPERF_VERSION,
            'site'         => home_url('/'),
            'summary'      => $summary,
            'results'      => $clean,
            'methodology'  => array(
                'http_samples'   => 3,
                'engine_samples' => 40,
                'note'           => 'HTTP tests measure the current stack. Redis/Memcached tests are isolated localhost read-latency microbenchmarks and do not change site cache configuration.',
            ),
        );
        update_option(self::OPTION, $report, false);
        Telemetry::recordBenchmark($report);
        return $report;
    }

    /** @return array<string,mixed> */
    public static function lastReport(): array
    {
        $value = get_option(self::OPTION, array());
        return is_array($value) ? $value : array();
    }

    /** @return array<string,mixed> */
    private static function wooShop(): array
    {
        if (!function_exists('wc_get_page_permalink')) {
            return self::skip('shop', 'WooCommerce shop', 'WooCommerce is not available.');
        }
        $url = (string) wc_get_page_permalink('shop');
        return $url !== '' ? self::httpBenchmark($url, 'WooCommerce shop') : self::skip('shop', 'WooCommerce shop', 'Shop URL is not configured.');
    }

    /** @return array<string,mixed> */
    private static function wooProduct(): array
    {
        if (!post_type_exists('product')) {
            return self::skip('product', 'WooCommerce product', 'WooCommerce products are not available.');
        }
        $ids = get_posts(
            array(
                'post_type'              => 'product',
                'post_status'            => 'publish',
                'posts_per_page'         => 1,
                'fields'                 => 'ids',
                'orderby'                => 'ID',
                'order'                  => 'DESC',
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            )
        );
        if (!isset($ids[0])) {
            return self::skip('product', 'WooCommerce product', 'No published product was found.');
        }
        $url = get_permalink((int) $ids[0]);
        return is_string($url) && $url !== '' ? self::httpBenchmark($url, 'WooCommerce product') : self::skip('product', 'WooCommerce product', 'Product URL could not be resolved.');
    }

    /** @return array<string,mixed> */
    private static function wooCategory(): array
    {
        if (!taxonomy_exists('product_cat')) {
            return self::skip('category', 'WooCommerce category', 'Product categories are not available.');
        }
        $terms = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 1, 'orderby' => 'count', 'order' => 'DESC'));
        if (is_wp_error($terms) || !isset($terms[0])) {
            return self::skip('category', 'WooCommerce category', 'No non-empty product category was found.');
        }
        $url = get_term_link($terms[0]);
        return is_string($url) && $url !== '' ? self::httpBenchmark($url, 'WooCommerce category') : self::skip('category', 'WooCommerce category', 'Category URL could not be resolved.');
    }

    /** @return array<string,mixed> */
    private static function httpBenchmark(string $url, string $name): array
    {
        if (!self::sameOrigin($url)) {
            return self::skip(sanitize_key($name), $name, 'Only same-origin URLs are benchmarked.');
        }

        $samples = array();
        $codes = array();
        for ($i = 0; $i < 3; ++$i) {
            $start = microtime(true);
            $response = wp_safe_remote_get(
                $url,
                array(
                    'timeout'             => 8,
                    'redirection'         => 3,
                    'sslverify'           => true,
                    'limit_response_size' => 65536,
                    'user-agent'          => 'Yablonsky-Performance/' . YPERF_VERSION . ' Benchmark',
                    'headers'             => array('Accept' => 'text/html,application/xhtml+xml'),
                )
            );
            $elapsed = round((microtime(true) - $start) * 1000, 2);
            if (is_wp_error($response)) {
                return self::result(sanitize_key($name), 'fail', 'http', array('url' => $url), 'HTTP benchmark failed: ' . $response->get_error_message());
            }
            $samples[] = $elapsed;
            $codes[] = (int) wp_remote_retrieve_response_code($response);
        }

        sort($samples, SORT_NUMERIC);
        $average = array_sum($samples) / max(1, count($samples));
        $median = $samples[(int) floor(count($samples) / 2)];
        $code = $codes[0] ?? 0;
        $ok = $codes !== array();
        foreach ($codes as $sampleCode) {
            if ($sampleCode < 200 || $sampleCode >= 400) {
                $ok = false;
                break;
            }
        }

        return self::result(
            sanitize_key($name),
            $ok ? 'pass' : 'fail',
            'http',
            array(
                'name'        => $name,
                'url'         => $url,
                'http_code'   => $code,
                'http_codes'  => array_map('intval', $codes),
                'samples_ms'  => array_map('floatval', $samples),
                'average_ms' => round($average, 2),
                'median_ms'  => round((float) $median, 2),
                'min_ms'     => round((float) min($samples), 2),
                'max_ms'     => round((float) max($samples), 2),
            ),
            $ok ? 'Three anonymous same-origin samples completed.' : 'Unexpected frontend HTTP response.'
        );
    }

    /** @return array<string,mixed> */
    private static function redisBenchmark(): array
    {
        if (!class_exists('\\Redis', false)) {
            return self::skip('redis', 'Redis', 'PHP Redis extension is not available.');
        }
        try {
            $redis = new \Redis();
            if (!$redis->connect('127.0.0.1', 6379, 0.2)) {
                return self::skip('redis', 'Redis', 'Redis service is not reachable on 127.0.0.1:6379.');
            }
            $key = 'yperf:benchmark:' . wp_generate_uuid4();
            $payload = str_repeat('YPerf', 192);
            $redis->setEx($key, 15, $payload);
            $samples = array();
            for ($i = 0; $i < 40; ++$i) {
                $start = microtime(true);
                $redis->get($key);
                $samples[] = (microtime(true) - $start) * 1000;
            }
            $redis->del($key);
            $redis->close();
            return self::engineResult('redis', 'Redis', $samples, '127.0.0.1:6379');
        } catch (\Throwable $throwable) {
            return self::result('redis', 'fail', 'engine', array('name' => 'Redis'), 'Redis benchmark failed: ' . sanitize_text_field($throwable->getMessage()));
        }
    }

    /** @return array<string,mixed> */
    private static function memcachedBenchmark(): array
    {
        if (!class_exists('\\Memcached', false)) {
            return self::skip('memcached', 'Memcached', 'PHP Memcached extension is not available.');
        }
        try {
            $memcached = new \Memcached();
            if (defined('Memcached::OPT_CONNECT_TIMEOUT')) {
                $memcached->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 200);
            }
            $memcached->addServer('127.0.0.1', 11211);
            $key = 'yperf:benchmark:' . wp_generate_uuid4();
            $payload = str_repeat('YPerf', 192);
            if (!$memcached->set($key, $payload, 15)) {
                return self::skip('memcached', 'Memcached', 'Memcached service is not reachable on 127.0.0.1:11211.');
            }
            $samples = array();
            for ($i = 0; $i < 40; ++$i) {
                $start = microtime(true);
                $memcached->get($key);
                $samples[] = (microtime(true) - $start) * 1000;
            }
            $memcached->delete($key);
            return self::engineResult('memcached', 'Memcached', $samples, '127.0.0.1:11211');
        } catch (\Throwable $throwable) {
            return self::result('memcached', 'fail', 'engine', array('name' => 'Memcached'), 'Memcached benchmark failed: ' . sanitize_text_field($throwable->getMessage()));
        }
    }

    /** @param array<int,float> $samples @return array<string,mixed> */
    private static function engineResult(string $id, string $name, array $samples, string $endpoint): array
    {
        if ($samples === array()) {
            return self::skip($id, $name, 'No benchmark samples were collected.');
        }
        sort($samples, SORT_NUMERIC);
        $average = array_sum($samples) / count($samples);
        $median = $samples[(int) floor(count($samples) / 2)];
        return self::result(
            $id,
            'pass',
            'engine',
            array(
                'name'       => $name,
                'endpoint'   => $endpoint,
                'samples'    => count($samples),
                'average_ms' => round($average, 4),
                'median_ms'  => round((float) $median, 4),
                'min_ms'     => round((float) min($samples), 4),
                'max_ms'     => round((float) max($samples), 4),
            ),
            'Isolated ephemeral read benchmark completed; the site cache configuration was not changed.'
        );
    }

    /** @param array<string,mixed> $extra @return array<string,mixed> */
    private static function result(string $id, string $status, string $kind, array $extra, string $details): array
    {
        return array_merge(
            array(
                'id'      => sanitize_key($id),
                'name'    => sanitize_text_field((string) ($extra['name'] ?? $id)),
                'status'  => sanitize_key($status),
                'kind'    => sanitize_key($kind),
                'details' => sanitize_text_field($details),
            ),
            $extra
        );
    }

    /** @return array<string,mixed> */
    private static function skip(string $id, string $name, string $details): array
    {
        return self::result($id, 'skip', 'availability', array('name' => $name), $details);
    }

    /** @param array<string,mixed> $value @return array<string,mixed> */
    private static function normalize(array $value): array
    {
        $id = sanitize_key((string) ($value['id'] ?? ''));
        $name = sanitize_text_field((string) ($value['name'] ?? ''));
        if ($id === '' || $name === '') {
            return array();
        }
        $status = sanitize_key((string) ($value['status'] ?? 'fail'));
        if (!in_array($status, array('pass', 'fail', 'skip'), true)) {
            $status = 'fail';
        }
        $clean = array(
            'id'      => $id,
            'name'    => $name,
            'status'  => $status,
            'kind'    => sanitize_key((string) ($value['kind'] ?? 'unknown')),
            'details' => sanitize_text_field((string) ($value['details'] ?? '')),
        );
        foreach (array('url', 'endpoint') as $field) {
            if (isset($value[$field])) {
                $clean[$field] = $field === 'url' ? esc_url_raw((string) $value[$field]) : sanitize_text_field((string) $value[$field]);
            }
        }
        foreach (array('http_code', 'samples') as $field) {
            if (isset($value[$field])) {
                $clean[$field] = max(0, (int) $value[$field]);
            }
        }
        foreach (array('average_ms', 'median_ms', 'min_ms', 'max_ms') as $field) {
            if (isset($value[$field])) {
                $clean[$field] = round(max(0.0, (float) $value[$field]), 4);
            }
        }
        if (isset($value['samples_ms']) && is_array($value['samples_ms'])) {
            $clean['samples_ms'] = array_map(static function ($sample): float {
                return round(max(0.0, (float) $sample), 2);
            }, array_slice($value['samples_ms'], 0, 10));
        }
        return $clean;
    }

    private static function sameOrigin(string $url): bool
    {
        $target = wp_parse_url($url);
        $home = wp_parse_url(home_url('/'));
        if (!is_array($target) || !is_array($home)) {
            return false;
        }
        $targetHost = strtolower((string) ($target['host'] ?? ''));
        $homeHost = strtolower((string) ($home['host'] ?? ''));
        $targetScheme = strtolower((string) ($target['scheme'] ?? ''));
        $homeScheme = strtolower((string) ($home['scheme'] ?? ''));
        if ($targetHost === '' || $homeHost === '' || $targetHost !== $homeHost || $targetScheme !== $homeScheme) {
            return false;
        }
        $defaultPort = static function (string $scheme): int {
            return $scheme === 'https' ? 443 : 80;
        };
        $targetPort = isset($target['port']) ? (int) $target['port'] : $defaultPort($targetScheme);
        $homePort = isset($home['port']) ? (int) $home['port'] : $defaultPort($homeScheme);
        return $targetPort === $homePort;
    }
}
