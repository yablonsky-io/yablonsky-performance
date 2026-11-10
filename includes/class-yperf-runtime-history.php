<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Bounded local history of Runtime Acceptance evidence.
 *
 * Stores compact summaries only. Raw response bodies, SQL, credentials and
 * probe signatures are never persisted here.
 */
final class RuntimeHistory
{
    private const OPTION = 'yperf_runtime_history_v1';
    private const LIMIT = 24;

    /** @param array<string,mixed> $report */
    public static function record(array $report, string $context = 'manual'): void
    {
        if ($report === array()) {
            return;
        }
        $entries = self::entries();
        array_unshift($entries, self::compact($report, $context));
        if (count($entries) > self::LIMIT) {
            $entries = array_slice($entries, 0, self::LIMIT);
        }
        update_option(self::OPTION, $entries, false);
    }

    /** @return array<int,array<string,mixed>> */
    public static function entries(): array
    {
        $value = get_option(self::OPTION, array());
        return is_array($value) ? $value : array();
    }

    /** @return array<string,mixed> */
    public static function analyze(): array
    {
        $entries = self::entries();
        $current = array();
        $healthy = array();
        $recentHealthy = array();
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $summary = is_array($entry['summary'] ?? null) ? $entry['summary'] : array();
            if ((int) ($summary['fail'] ?? 0) === 0) {
                $recentHealthy[] = $entry;
            }
            if ((string) ($entry['version'] ?? '') !== YPERF_VERSION) {
                continue;
            }
            $current[] = $entry;
            if ((int) ($summary['fail'] ?? 0) === 0) {
                $healthy[] = $entry;
            }
        }

        $routes = array();
        foreach (array_slice($recentHealthy, 0, 8) as $entry) {
            foreach ((array) ($entry['timings'] ?? array()) as $name => $ms) {
                if (!isset($routes[$name])) {
                    $routes[$name] = array();
                }
                $routes[$name][] = max(0, (int) $ms);
            }
        }

        $baselines = array();
        foreach ($routes as $name => $samples) {
            if ($samples === array()) {
                continue;
            }
            sort($samples, SORT_NUMERIC);
            $median = self::median($samples);
            $min = (int) min($samples);
            $max = (int) max($samples);
            $spreadPct = $median > 0 ? (($max - $min) / $median) * 100 : 0.0;
            $baselines[] = array(
                'name' => sanitize_text_field((string) $name),
                'samples' => count($samples),
                'median_ms' => (int) round($median),
                'min_ms' => $min,
                'max_ms' => $max,
                'spread_pct' => round($spreadPct, 1),
                'stability' => $spreadPct <= 20 ? 'stable' : ($spreadPct <= 45 ? 'variable' : 'noisy'),
            );
        }

        $healthyStreak = 0;
        foreach ($entries as $entry) {
            $summary = is_array($entry['summary'] ?? null) ? $entry['summary'] : array();
            if ((int) ($summary['fail'] ?? 0) > 0) {
                break;
            }
            ++$healthyStreak;
        }

        return array(
            'schema' => 1,
            'generated_at' => time(),
            'retained_runs' => count($entries),
            'current_version_runs' => count($current),
            'current_version_healthy_runs' => count($healthy),
            'recent_runs' => count($entries),
            'recent_healthy_runs' => count($recentHealthy),
            'healthy_streak' => $healthyStreak,
            'route_baselines' => $baselines,
            'latest' => isset($entries[0]) && is_array($entries[0]) ? $entries[0] : array(),
            'privacy' => array(
                'raw_sql_stored' => false,
                'response_bodies_stored' => false,
                'credentials_stored' => false,
            ),
        );
    }

    /** @param array<string,mixed> $report @return array<string,mixed> */
    private static function compact(array $report, string $context): array
    {
        $summary = is_array($report['summary'] ?? null) ? $report['summary'] : array();
        $timings = array();
        $origin = array();
        foreach ((array) ($report['tests'] ?? array()) as $test) {
            if (!is_array($test)) {
                continue;
            }
            $name = sanitize_text_field((string) ($test['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            if (isset($test['ms']) && is_numeric($test['ms'])) {
                $timings[$name] = max(0, (int) $test['ms']);
            }
            $metrics = is_array($test['origin_metrics'] ?? null) ? $test['origin_metrics'] : array();
            if ((string) ($metrics['state'] ?? '') === 'measured') {
                $origin[$name] = array(
                    'db_time_ms' => round((float) ($metrics['db_time_ms'] ?? 0), 2),
                    'query_count' => max(0, (int) ($metrics['query_count'] ?? 0)),
                    'slow_queries_100ms' => max(0, (int) ($metrics['slow_queries_100ms'] ?? 0)),
                    'sql_calc_found_rows_count' => max(0, (int) ($metrics['sql_calc_found_rows_count'] ?? 0)),
                );
            }
        }
        return array(
            'generated_at' => (int) ($report['generated_at'] ?? time()),
            'version' => sanitize_text_field((string) ($report['version'] ?? '')),
            'context' => sanitize_key($context),
            'summary' => array(
                'pass' => (int) ($summary['pass'] ?? 0),
                'fail' => (int) ($summary['fail'] ?? 0),
                'skip' => (int) ($summary['skip'] ?? 0),
                'attention' => (int) ($summary['attention'] ?? 0),
            ),
            'timings' => $timings,
            'origin' => $origin,
        );
    }

    /** @param array<int,int> $values */
    private static function median(array $values): float
    {
        $count = count($values);
        if ($count === 0) {
            return 0.0;
        }
        $middle = intdiv($count, 2);
        if ($count % 2 === 1) {
            return (float) $values[$middle];
        }
        return ((float) $values[$middle - 1] + (float) $values[$middle]) / 2;
    }
}
