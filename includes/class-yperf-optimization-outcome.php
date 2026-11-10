<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class OptimizationOutcome
{
    private const OPTION = 'yperf_optimization_outcome_v1';

    /**
     * @param array<string,mixed> $beforeRuntime
     * @param array<string,mixed> $afterRuntime
     * @param array<string,string> $beforeSettings
     * @param array<string,string> $afterSettings
     * @param array<string,mixed> $effectiveness
     * @return array<string,mixed>
     */
    public static function build(array $beforeRuntime, array $afterRuntime, array $beforeSettings, array $afterSettings, array $effectiveness, bool $rolledBack, array $regressionGuard = array(), array $campaign = array()): array
    {
        $changes = array();
        foreach ($afterSettings as $key => $value) {
            $before = (string) ($beforeSettings[$key] ?? '');
            if ($before === (string) $value) {
                continue;
            }
            $changes[] = array(
                'setting' => sanitize_key((string) $key),
                'from' => sanitize_text_field($before),
                'to' => sanitize_text_field((string) $value),
            );
        }

        $beforeAt = (int) ($beforeRuntime['generated_at'] ?? 0);
        $afterAt = (int) ($afterRuntime['generated_at'] ?? 0);
        $baselineAge = $beforeAt > 0 && $afterAt >= $beforeAt ? $afterAt - $beforeAt : null;

        return array(
            'schema' => 3,
            'generated_at' => time(),
            'outcome' => $rolledBack ? 'rolled_back' : 'accepted',
            'profile' => sanitize_key((string) ($afterSettings[PerformancePlan::OPTION_PROFILE] ?? PerformancePlan::profile())),
            'changes' => $changes,
            'runtime_before' => self::runtimeSummary($beforeRuntime),
            'runtime_after' => self::runtimeSummary($afterRuntime),
            'observed_timing_deltas' => self::timingDeltas($beforeRuntime, $afterRuntime),
            'origin_cost_deltas' => self::originCostDeltas($beforeRuntime, $afterRuntime),
            'baseline_age_seconds' => $baselineAge,
            'effectiveness' => $effectiveness,
            'regression_guard' => $regressionGuard,
            'campaign' => $campaign,
            'measurement_note' => 'Timing and origin-cost deltas compare two fresh Runtime Acceptance passes captured in the same activation action. They are directional evidence, not a causal lab benchmark; network/server variance can dominate small changes.',
        );
    }

    /** @param array<string,mixed> $outcome */
    public static function save(array $outcome): void
    {
        update_option(self::OPTION, $outcome, false);
    }

    /** @return array<string,mixed> */
    public static function last(): array
    {
        $value = get_option(self::OPTION, array());
        return is_array($value) ? $value : array();
    }

    /** @param array<string,mixed> $runtime @return array<string,mixed> */
    private static function runtimeSummary(array $runtime): array
    {
        if ($runtime === array()) {
            return array('available' => false);
        }
        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        return array(
            'available' => true,
            'generated_at' => (int) ($runtime['generated_at'] ?? 0),
            'pass' => (int) ($summary['pass'] ?? 0),
            'fail' => (int) ($summary['fail'] ?? 0),
            'skip' => (int) ($summary['skip'] ?? 0),
            'attention' => (int) ($summary['attention'] ?? 0),
        );
    }

    /** @param array<string,mixed> $before @param array<string,mixed> $after @return array<int,array<string,mixed>> */
    private static function timingDeltas(array $before, array $after): array
    {
        $beforeMap = self::timingMap($before);
        $rows = array();
        foreach (self::timingMap($after) as $name => $afterMs) {
            if (!isset($beforeMap[$name])) {
                continue;
            }
            $beforeMs = (int) $beforeMap[$name];
            if ($beforeMs < 1 || $afterMs < 1) {
                continue;
            }
            $delta = $afterMs - $beforeMs;
            $pct = ($delta / $beforeMs) * 100;
            $rows[] = array(
                'name' => sanitize_text_field((string) $name),
                'before_ms' => $beforeMs,
                'after_ms' => $afterMs,
                'delta_ms' => $delta,
                'delta_pct' => round($pct, 1),
            );
        }
        return $rows;
    }

    /** @param array<string,mixed> $before @param array<string,mixed> $after @return array<int,array<string,mixed>> */
    private static function originCostDeltas(array $before, array $after): array
    {
        $beforeMap = self::originCostMap($before);
        $rows = array();
        foreach (self::originCostMap($after) as $name => $afterMetrics) {
            if (!isset($beforeMap[$name])) {
                continue;
            }
            $beforeMetrics = $beforeMap[$name];
            $beforeDb = (float) ($beforeMetrics['db_time_ms'] ?? 0);
            $afterDb = (float) ($afterMetrics['db_time_ms'] ?? 0);
            $beforeQueries = (int) ($beforeMetrics['query_count'] ?? 0);
            $afterQueries = (int) ($afterMetrics['query_count'] ?? 0);
            $dbPct = $beforeDb > 0 ? (($afterDb - $beforeDb) / $beforeDb) * 100 : null;
            $rows[] = array(
                'name' => sanitize_text_field((string) $name),
                'before_db_ms' => round($beforeDb, 2),
                'after_db_ms' => round($afterDb, 2),
                'db_delta_ms' => round($afterDb - $beforeDb, 2),
                'db_delta_pct' => $dbPct === null ? null : round($dbPct, 1),
                'before_queries' => $beforeQueries,
                'after_queries' => $afterQueries,
                'query_delta' => $afterQueries - $beforeQueries,
                'before_found_rows' => (int) ($beforeMetrics['sql_calc_found_rows_count'] ?? 0),
                'after_found_rows' => (int) ($afterMetrics['sql_calc_found_rows_count'] ?? 0),
            );
        }
        return $rows;
    }

    /** @param array<string,mixed> $runtime @return array<string,array<string,mixed>> */
    private static function originCostMap(array $runtime): array
    {
        $map = array();
        foreach ((array) ($runtime['tests'] ?? array()) as $test) {
            if (!is_array($test)) {
                continue;
            }
            $metrics = is_array($test['origin_metrics'] ?? null) ? $test['origin_metrics'] : array();
            if ((string) ($metrics['state'] ?? '') !== 'measured') {
                continue;
            }
            $name = sanitize_text_field((string) ($test['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $map[$name] = $metrics;
        }
        return $map;
    }

    /** @param array<string,mixed> $runtime @return array<string,int> */
    private static function timingMap(array $runtime): array
    {
        $map = array();
        foreach ((array) ($runtime['tests'] ?? array()) as $test) {
            if (!is_array($test) || !isset($test['ms'])) {
                continue;
            }
            $name = sanitize_text_field((string) ($test['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $map[$name] = max(0, (int) $test['ms']);
        }
        return $map;
    }
}
