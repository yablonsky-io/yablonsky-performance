<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Conservative automatic regression guard for explicit performance applies.
 *
 * Functional failures always fail closed. Timing/DB regressions must be both
 * material and repeatable before YPerf rolls back automatically, because one
 * network/server sample is too noisy to treat as causality.
 */
final class RegressionGuard
{
    /** @param array<string,mixed> $before @param array<string,mixed> $after @return array<string,mixed> */
    public static function evaluate(array $before, array $after): array
    {
        $summary = is_array($after['summary'] ?? null) ? $after['summary'] : array();
        if ((int) ($summary['fail'] ?? 0) > 0) {
            return array(
                'schema' => 1,
                'state' => 'functional_failure',
                'suspected' => true,
                'functional_failure' => true,
                'failed_tests' => self::failedTestNames($after),
                'failed_test_evidence' => self::failedTestEvidence($after),
                'timing_regressions' => array(),
                'origin_regressions' => array(),
                'reason' => 'Runtime Acceptance contains a functional FAIL.',
            );
        }

        $beforeTiming = self::timingMap($before);
        $afterTiming = self::timingMap($after);
        $timingRegressions = array();
        foreach ($afterTiming as $name => $afterMs) {
            if (!isset($beforeTiming[$name])) {
                continue;
            }
            $beforeMs = (int) $beforeTiming[$name];
            if ($beforeMs < 1 || $afterMs < 1) {
                continue;
            }
            $delta = $afterMs - $beforeMs;
            $pct = ($delta / $beforeMs) * 100;
            if ($delta >= 300 && $pct >= 35) {
                $timingRegressions[] = array(
                    'name' => sanitize_text_field((string) $name),
                    'before_ms' => $beforeMs,
                    'after_ms' => $afterMs,
                    'delta_ms' => $delta,
                    'delta_pct' => round($pct, 1),
                );
            }
        }

        $beforeOrigin = self::originMap($before);
        $afterOrigin = self::originMap($after);
        $originRegressions = array();
        foreach ($afterOrigin as $name => $metrics) {
            if (!isset($beforeOrigin[$name])) {
                continue;
            }
            $beforeMetrics = $beforeOrigin[$name];
            $beforeDb = (float) ($beforeMetrics['db_time_ms'] ?? 0);
            $afterDb = (float) ($metrics['db_time_ms'] ?? 0);
            $beforeQueries = (int) ($beforeMetrics['query_count'] ?? 0);
            $afterQueries = (int) ($metrics['query_count'] ?? 0);
            $dbDelta = $afterDb - $beforeDb;
            $dbPct = $beforeDb > 0 ? ($dbDelta / $beforeDb) * 100 : 0.0;
            $queryDelta = $afterQueries - $beforeQueries;
            $queryPct = $beforeQueries > 0 ? ($queryDelta / $beforeQueries) * 100 : 0.0;
            if (($dbDelta >= 150 && $dbPct >= 50) || ($queryDelta >= 30 && $queryPct >= 50)) {
                $originRegressions[] = array(
                    'name' => sanitize_text_field((string) $name),
                    'before_db_ms' => round($beforeDb, 2),
                    'after_db_ms' => round($afterDb, 2),
                    'db_delta_pct' => round($dbPct, 1),
                    'before_queries' => $beforeQueries,
                    'after_queries' => $afterQueries,
                    'query_delta_pct' => round($queryPct, 1),
                );
            }
        }

        $severeSingle = false;
        foreach ($timingRegressions as $row) {
            if ((int) ($row['delta_ms'] ?? 0) >= 750 && (float) ($row['delta_pct'] ?? 0) >= 60) {
                $severeSingle = true;
                break;
            }
        }
        $suspected = $severeSingle || count($timingRegressions) >= 2 || count($originRegressions) >= 2;

        return array(
            'schema' => 1,
            'state' => $suspected ? 'suspected' : 'clean',
            'suspected' => $suspected,
            'functional_failure' => false,
            'timing_regressions' => $timingRegressions,
            'origin_regressions' => $originRegressions,
            'reason' => $suspected
                ? 'Material regression evidence was observed. A confirmation pass is required before automatic rollback.'
                : 'No material regression crossed the conservative automatic guard thresholds.',
        );
    }

    /** @param array<string,mixed> $first @param array<string,mixed> $second @return array<string,mixed> */
    public static function confirm(array $first, array $second): array
    {
        if (!empty($first['functional_failure']) || !empty($second['functional_failure'])) {
            return array(
                'schema' => 1,
                'state' => 'confirmed',
                'confirmed' => true,
                'reason' => 'A functional Runtime Acceptance failure was observed.',
                'matching_routes' => array(),
            );
        }
        if (empty($first['suspected']) || empty($second['suspected'])) {
            return array(
                'schema' => 1,
                'state' => 'not_confirmed',
                'confirmed' => false,
                'reason' => 'The suspected performance regression did not repeat on the confirmation pass.',
                'matching_routes' => array(),
            );
        }
        $firstNames = self::regressionNames($first);
        $secondNames = self::regressionNames($second);
        $matching = array_values(array_intersect($firstNames, $secondNames));
        $confirmed = count($matching) > 0;
        return array(
            'schema' => 1,
            'state' => $confirmed ? 'confirmed' : 'not_confirmed',
            'confirmed' => $confirmed,
            'reason' => $confirmed
                ? 'Material regression repeated on the same route in two post-apply passes.'
                : 'The regression signal changed routes and was treated as environmental noise rather than a confirmed YPerf regression.',
            'matching_routes' => array_map('sanitize_text_field', $matching),
        );
    }

    /** @param array<string,mixed> $report @return array<string,int> */
    private static function timingMap(array $report): array
    {
        $map = array();
        foreach ((array) ($report['tests'] ?? array()) as $test) {
            if (!is_array($test) || !isset($test['ms'])) {
                continue;
            }
            $name = sanitize_text_field((string) ($test['name'] ?? ''));
            if ($name !== '') {
                $map[$name] = max(0, (int) $test['ms']);
            }
        }
        return $map;
    }

    /** @param array<string,mixed> $report @return array<string,array<string,mixed>> */
    private static function originMap(array $report): array
    {
        $map = array();
        foreach ((array) ($report['tests'] ?? array()) as $test) {
            if (!is_array($test)) {
                continue;
            }
            $metrics = is_array($test['origin_metrics'] ?? null) ? $test['origin_metrics'] : array();
            if ((string) ($metrics['state'] ?? '') !== 'measured') {
                continue;
            }
            $name = sanitize_text_field((string) ($test['name'] ?? ''));
            if ($name !== '') {
                $map[$name] = $metrics;
            }
        }
        return $map;
    }

    /** @param array<string,mixed> $report @return array<int,string> */
    private static function failedTestNames(array $report): array
    {
        $names = array();
        foreach ((array) ($report['tests'] ?? array()) as $test) {
            if (!is_array($test) || (string) ($test['status'] ?? '') !== 'fail') {
                continue;
            }
            $name = sanitize_text_field((string) ($test['name'] ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
        }
        return array_values(array_unique($names));
    }

    /** @param array<string,mixed> $report @return array<int,array<string,mixed>> */
    private static function failedTestEvidence(array $report): array
    {
        $rows = array();
        foreach ((array) ($report['tests'] ?? array()) as $test) {
            if (!is_array($test) || (string) ($test['status'] ?? '') !== 'fail') {
                continue;
            }
            $row = array(
                'name' => sanitize_text_field((string) ($test['name'] ?? '')),
                'details' => sanitize_text_field((string) ($test['details'] ?? '')),
            );
            if (isset($test['proof_checks']) && is_array($test['proof_checks'])) {
                $row['proof_checks'] = array(
                    'frontend_header' => !empty($test['proof_checks']['frontend_header']),
                    'emoji_payload_absent' => !empty($test['proof_checks']['emoji_payload_absent']),
                    'delay_markers' => !empty($test['proof_checks']['delay_markers']),
                    'css_route_applicable' => !empty($test['proof_checks']['css_route_applicable']),
                    'css_markers' => !empty($test['proof_checks']['css_markers']),
                );
            }
            if (isset($test['js_delay_proof']) && is_array($test['js_delay_proof'])) {
                $row['js_delay_proof'] = $test['js_delay_proof'];
            }
            if (isset($test['css_inline_proof']) && is_array($test['css_inline_proof'])) {
                $row['css_inline_proof'] = $test['css_inline_proof'];
            }
            $rows[] = $row;
        }
        return $rows;
    }

    /** @param array<string,mixed> $evaluation @return array<int,string> */
    private static function regressionNames(array $evaluation): array
    {
        $names = array();
        foreach (array('timing_regressions', 'origin_regressions') as $key) {
            foreach ((array) ($evaluation[$key] ?? array()) as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $name = sanitize_text_field((string) ($row['name'] ?? ''));
                if ($name !== '') {
                    $names[] = $name;
                }
            }
        }
        return array_values(array_unique($names));
    }
}
