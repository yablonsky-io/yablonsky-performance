<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class Prewarm
{
    private const OPTION = 'yperf_prewarm_report_v1';

    /** @return array<int,string> */
    public static function plan(): array
    {
        $urls = array(home_url('/'));

        if (function_exists('wc_get_page_permalink')) {
            $shop = (string) wc_get_page_permalink('shop');
            if ($shop !== '') {
                $urls[] = $shop;
            }
        }

        $postTypes = post_type_exists('product') ? array('post', 'page', 'product') : array('post', 'page');
        $ids = get_posts(
            array(
                'post_type'              => $postTypes,
                'post_status'            => 'publish',
                'posts_per_page'         => 6,
                'fields'                 => 'ids',
                'orderby'                => 'modified',
                'order'                  => 'DESC',
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            )
        );
        foreach ($ids as $id) {
            $url = get_permalink((int) $id);
            if (is_string($url) && $url !== '') {
                $urls[] = $url;
            }
        }

        if (taxonomy_exists('product_cat')) {
            $terms = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 4, 'orderby' => 'count', 'order' => 'DESC'));
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $url = get_term_link($term);
                    if (is_string($url) && $url !== '') {
                        $urls[] = $url;
                    }
                }
            }
        }

        $clean = array();
        foreach ($urls as $url) {
            $url = esc_url_raw((string) $url);
            if ($url !== '' && self::sameOrigin($url)) {
                $clean[$url] = $url;
            }
        }
        return array_slice(array_values($clean), 0, 12);
    }


    /** @return array<string,mixed> */
    public static function runBoundedPlan(int $limit = 12, int $budgetMs = 30000): array
    {
        $limit = max(1, min(12, $limit));
        $budgetMs = max(1000, min(60000, $budgetMs));
        $plan = array_slice(self::plan(), 0, $limit);
        $started = microtime(true);
        $items = array();

        foreach ($plan as $index => $url) {
            $elapsedMs = (int) round((microtime(true) - $started) * 1000);
            if ($index > 0 && $elapsedMs >= $budgetMs) {
                $items[] = array(
                    'url' => $url,
                    'status' => 'skip',
                    'http_code' => 0,
                    'ms' => 0,
                    'requests' => 0,
                    'verified_hit' => false,
                    'proof_state' => 'budget_exhausted',
                    'details' => 'Bounded prewarm time budget was reached before this URL.',
                );
                continue;
            }
            $items[] = self::runUrl($url);
        }

        $report = self::buildReport($items);
        $report['budget_ms'] = $budgetMs;
        $report['requested_limit'] = $limit;
        $report['elapsed_ms'] = (int) round((microtime(true) - $started) * 1000);
        update_option(self::OPTION, $report, false);
        return $report;
    }

    /** @return array<string,mixed> */
    public static function runUrl(string $url): array
    {
        $url = esc_url_raw(trim($url));
        if ($url === '' || !self::sameOrigin($url)) {
            return array(
                'url' => $url,
                'status' => 'skip',
                'http_code' => 0,
                'ms' => 0,
                'requests' => 0,
                'verified_hit' => false,
                'proof_state' => 'not_applicable',
                'details' => 'URL is not same-origin.',
            );
        }

        $first = self::requestOnce($url);
        $status = (string) ($first['status'] ?? 'fail');
        $cacheFirst = (string) ($first['cache'] ?? '');
        $cacheVerify = '';
        $verifiedHit = in_array($cacheFirst, array('HIT', 'HIT-WAIT'), true);
        $proofState = $verifiedHit ? 'already_hit' : 'not_observed';
        $requests = 1;
        $verifyMs = 0;
        $totalMs = (int) ($first['ms'] ?? 0);

        // A YPerf-owned page-cache prewarm is not merely a successful HTTP
        // request. When the first request is a MISS, run one bounded follow-up
        // request and prove that the freshly generated response became a HIT.
        // This stays sequential and is capped by plan() at 12 URLs.
        if ($status === 'pass' && PageCacheExecutor::enabled() && $cacheFirst === 'MISS') {
            $verify = self::requestOnce($url);
            ++$requests;
            $verifyMs = (int) ($verify['ms'] ?? 0);
            $totalMs += $verifyMs;
            $cacheVerify = (string) ($verify['cache'] ?? '');
            $verifiedHit = (string) ($verify['status'] ?? 'fail') === 'pass'
                && in_array($cacheVerify, array('HIT', 'HIT-WAIT'), true);
            $proofState = $verifiedHit ? 'miss_then_hit' : 'miss_not_confirmed';
        }

        $details = (string) ($first['details'] ?? '');
        if ($verifiedHit) {
            $details = $proofState === 'miss_then_hit'
                ? 'Warm request completed and the YPerf Page Cache HIT was verified.'
                : 'URL was already warm in the YPerf Page Cache.';
        } elseif ($status === 'pass' && PageCacheExecutor::enabled() && $cacheFirst === 'MISS') {
            $details = 'Warm request completed, but the follow-up YPerf Page Cache HIT was not observed.';
        }

        return array(
            'url' => $url,
            'status' => $status,
            'http_code' => max(0, (int) ($first['http_code'] ?? 0)),
            'ms' => max(0, $totalMs),
            'first_ms' => max(0, (int) ($first['ms'] ?? 0)),
            'verify_ms' => max(0, $verifyMs),
            'requests' => $requests,
            'cache' => $cacheVerify !== '' ? $cacheVerify : $cacheFirst,
            'cache_first' => $cacheFirst,
            'cache_verify' => $cacheVerify,
            'verified_hit' => $verifiedHit,
            'proof_state' => $proofState,
            'details' => $details,
        );
    }

    /** @return array<string,mixed> */
    private static function requestOnce(string $url): array
    {
        $start = microtime(true);
        $response = wp_safe_remote_get(
            $url,
            array(
                'timeout'             => 8,
                'redirection'         => 3,
                'sslverify'           => true,
                'limit_response_size' => 32768,
                'user-agent'          => 'Yablonsky-Performance/' . YPERF_VERSION . ' Prewarm',
                'headers'             => array('Accept' => 'text/html,application/xhtml+xml'),
            )
        );
        $ms = (int) round((microtime(true) - $start) * 1000);
        if (is_wp_error($response)) {
            return array(
                'status' => 'fail',
                'http_code' => 0,
                'ms' => $ms,
                'cache' => '',
                'details' => sanitize_text_field($response->get_error_message()),
            );
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        return array(
            'status' => ($code >= 200 && $code < 400) ? 'pass' : 'fail',
            'http_code' => $code,
            'ms' => $ms,
            'cache' => strtoupper(sanitize_key((string) wp_remote_retrieve_header($response, 'x-yperf-cache'))),
            'details' => ($code >= 200 && $code < 400) ? 'Warm request completed.' : 'Unexpected HTTP response.',
        );
    }

    /** @param array<int,mixed> $items @return array<string,mixed> */
    public static function buildReport(array $items): array
    {
        $results = array();
        $summary = array(
            'pass' => 0,
            'fail' => 0,
            'skip' => 0,
            'verified_hit' => 0,
            'requests' => 0,
            'total_ms' => 0,
            'avg_ms' => 0,
        );
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $status = sanitize_key((string) ($item['status'] ?? 'fail'));
            if (!isset($summary[$status])) {
                $status = 'fail';
            }
            ++$summary[$status];
            $verifiedHit = !empty($item['verified_hit']);
            $requests = max(0, (int) ($item['requests'] ?? 1));
            $duration = max(0, (int) ($item['ms'] ?? 0));
            if ($verifiedHit) {
                ++$summary['verified_hit'];
            }
            $summary['requests'] += $requests;
            $summary['total_ms'] += $duration;
            $results[] = array(
                'url'          => esc_url_raw((string) ($item['url'] ?? '')),
                'status'       => $status,
                'http_code'    => max(0, (int) ($item['http_code'] ?? 0)),
                'ms'           => $duration,
                'first_ms'     => max(0, (int) ($item['first_ms'] ?? $duration)),
                'verify_ms'    => max(0, (int) ($item['verify_ms'] ?? 0)),
                'requests'     => $requests,
                'cache'        => strtoupper(sanitize_key((string) ($item['cache'] ?? ''))),
                'cache_first'  => strtoupper(sanitize_key((string) ($item['cache_first'] ?? ''))),
                'cache_verify' => strtoupper(sanitize_key((string) ($item['cache_verify'] ?? ''))),
                'verified_hit' => $verifiedHit,
                'proof_state'  => sanitize_key((string) ($item['proof_state'] ?? 'not_observed')),
                'details'      => sanitize_text_field((string) ($item['details'] ?? '')),
            );
        }
        $summary['avg_ms'] = $results === array() ? 0 : (int) round($summary['total_ms'] / count($results));
        $report = array(
            'schema'       => 2,
            'generated_at' => time(),
            'plugin'       => 'Yablonsky Performance',
            'version'      => YPERF_VERSION,
            'site'         => home_url('/'),
            'summary'      => $summary,
            'results'      => $results,
            'bounded'      => true,
        );
        update_option(self::OPTION, $report, false);
        Telemetry::recordPrewarm($report);
        return $report;
    }

    /** @return array<string,mixed> */
    public static function lastReport(): array
    {
        $value = get_option(self::OPTION, array());
        return is_array($value) ? $value : array();
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
