<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class Telemetry
{
    private const OPTION = 'yperf_telemetry_v1';
    private const LIMIT = 120;

    /** @param array<string,mixed> $report */
    public static function recordRuntime(array $report): void
    {
        $summary = is_array($report['summary'] ?? null) ? $report['summary'] : array();
        $timings = array();
        foreach ((array) ($report['tests'] ?? array()) as $test) {
            if (is_array($test) && isset($test['ms'])) {
                $timings[] = max(0, (int) $test['ms']);
            }
        }
        self::record('runtime', array(
            'pass'      => (int) ($summary['pass'] ?? 0),
            'fail'      => (int) ($summary['fail'] ?? 0),
            'skip'      => (int) ($summary['skip'] ?? 0),
            'attention' => (int) ($summary['attention'] ?? 0),
            'avg_ms'    => $timings === array() ? 0 : (int) round(array_sum($timings) / count($timings)),
        ));
    }

    /** @param array<string,mixed> $report */
    public static function recordBenchmark(array $report): void
    {
        $summary = is_array($report['summary'] ?? null) ? $report['summary'] : array();
        self::record('benchmark', array(
            'pass' => (int) ($summary['pass'] ?? 0),
            'fail' => (int) ($summary['fail'] ?? 0),
            'skip' => (int) ($summary['skip'] ?? 0),
        ));
    }

    /** @param array<string,mixed> $report */
    public static function recordEnvironment(array $report): void
    {
        $summary = is_array($report['summary'] ?? null) ? $report['summary'] : array();
        self::record('environment', array(
            'ready'     => (int) ($summary['ready'] ?? 0),
            'available' => (int) ($summary['available'] ?? 0),
            'attention' => (int) ($summary['attention'] ?? 0),
            'blocked'   => (int) ($summary['blocked'] ?? 0),
            'unknown'   => (int) ($summary['unknown'] ?? 0),
        ));
    }

    /** @param array<string,mixed> $result */
    public static function recordPurge(array $result): void
    {
        self::record('purge', array(
            'mode'        => sanitize_key((string) ($result['mode'] ?? 'unknown')),
            'status'      => sanitize_key((string) ($result['status'] ?? 'unknown')),
            'provider'    => sanitize_text_field((string) ($result['provider'] ?? '')),
            'count'       => max(0, (int) ($result['count'] ?? 0)),
            'affected'    => max(0, (int) ($result['affected'] ?? 0)),
            'duration_ms' => max(0, (int) ($result['duration_ms'] ?? 0)),
            'confirmed'   => !empty($result['confirmed']),
        ));
    }

    /** @param array<string,mixed> $result */
    public static function recordPerformancePlan(array $result): void
    {
        self::record('performance_plan', array(
            'profile' => sanitize_key((string) ($result['profile'] ?? 'observe')),
            'frontend_optimizer' => !empty($result['frontend_optimizer']),
            'first_hit_guard' => !empty($result['first_hit_guard']),
            'provider_purge_bridge' => !empty($result['provider_purge_bridge']),
        ));
    }

    /** @param array<string,mixed> $campaign */
    public static function recordCampaign(array $campaign): void
    {
        self::record('optimization_campaign', array(
            'mode' => sanitize_key((string) ($campaign['mode'] ?? 'manual')),
            'profile' => sanitize_key((string) ($campaign['profile'] ?? 'observe')),
            'status' => sanitize_key((string) ($campaign['status'] ?? 'unknown')),
        ));
    }

    /** @param array<string,mixed> $report */
    public static function recordPrewarm(array $report): void
    {
        $summary = is_array($report['summary'] ?? null) ? $report['summary'] : array();
        self::record('prewarm', array(
            'pass'          => (int) ($summary['pass'] ?? 0),
            'fail'          => (int) ($summary['fail'] ?? 0),
            'skip'          => (int) ($summary['skip'] ?? 0),
            'verified_hit'  => (int) ($summary['verified_hit'] ?? 0),
            'requests'      => (int) ($summary['requests'] ?? 0),
            'avg_ms'        => (int) ($summary['avg_ms'] ?? 0),
        ));
    }

    /** @return array<string,mixed> */
    public static function report(): array
    {
        $events = self::events();
        $now = time();
        $day = $now - DAY_IN_SECONDS;
        $summary = array(
            'runtime_runs_24h'   => 0,
            'benchmark_runs_24h' => 0,
            'purges_24h'         => 0,
            'purge_failures_24h' => 0,
            'purge_confirmed_24h'=> 0,
            'purge_duration_ms_24h' => 0,
            'purge_avg_ms_24h'   => 0,
            'prewarm_runs_24h'   => 0,
            'prewarm_verified_hits_24h' => 0,
            'prewarm_requests_24h' => 0,
            'prewarm_avg_ms_24h' => 0,
            'performance_plan_applies_24h' => 0,
            'optimization_campaigns_24h' => 0,
            'autopilot_campaigns_24h' => 0,
            'ai_advisor_campaigns_24h' => 0,
            'automatic_rollbacks_24h' => 0,
            'last_runtime_avg_ms'=> 0,
            'last_event_at'      => 0,
        );

        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }
            $time = (int) ($event['time'] ?? 0);
            $type = sanitize_key((string) ($event['type'] ?? ''));
            if ($summary['last_event_at'] === 0 && $time > 0) {
                $summary['last_event_at'] = $time;
            }
            if ($type === 'runtime' && $summary['last_runtime_avg_ms'] === 0) {
                $payload = is_array($event['payload'] ?? null) ? $event['payload'] : array();
                $summary['last_runtime_avg_ms'] = (int) ($payload['avg_ms'] ?? 0);
            }
            if ($time < $day) {
                continue;
            }
            if ($type === 'runtime') {
                ++$summary['runtime_runs_24h'];
            } elseif ($type === 'benchmark') {
                ++$summary['benchmark_runs_24h'];
            } elseif ($type === 'purge') {
                ++$summary['purges_24h'];
                $payload = is_array($event['payload'] ?? null) ? $event['payload'] : array();
                $summary['purge_duration_ms_24h'] += max(0, (int) ($payload['duration_ms'] ?? 0));
                if ((string) ($payload['status'] ?? '') === 'fail') {
                    ++$summary['purge_failures_24h'];
                }
                if (!empty($payload['confirmed'])) {
                    ++$summary['purge_confirmed_24h'];
                }
            } elseif ($type === 'prewarm') {
                ++$summary['prewarm_runs_24h'];
                $payload = is_array($event['payload'] ?? null) ? $event['payload'] : array();
                $summary['prewarm_verified_hits_24h'] += max(0, (int) ($payload['verified_hit'] ?? 0));
                $summary['prewarm_requests_24h'] += max(0, (int) ($payload['requests'] ?? 0));
                $summary['prewarm_avg_ms_24h'] += max(0, (int) ($payload['avg_ms'] ?? 0));
            } elseif ($type === 'performance_plan') {
                ++$summary['performance_plan_applies_24h'];
            } elseif ($type === 'optimization_campaign') {
                ++$summary['optimization_campaigns_24h'];
                $payload = is_array($event['payload'] ?? null) ? $event['payload'] : array();
                if ((string) ($payload['mode'] ?? '') === 'autopilot') {
                    ++$summary['autopilot_campaigns_24h'];
                } elseif ((string) ($payload['mode'] ?? '') === 'ai_advisor') {
                    ++$summary['ai_advisor_campaigns_24h'];
                }
                if ((string) ($payload['status'] ?? '') === 'rolled_back') {
                    ++$summary['automatic_rollbacks_24h'];
                }
            }
        }

        if ($summary['purges_24h'] > 0) {
            $summary['purge_avg_ms_24h'] = (int) round($summary['purge_duration_ms_24h'] / $summary['purges_24h']);
        }
        if ($summary['prewarm_runs_24h'] > 0) {
            $summary['prewarm_avg_ms_24h'] = (int) round($summary['prewarm_avg_ms_24h'] / $summary['prewarm_runs_24h']);
        }

        return array(
            'schema'       => 5,
            'generated_at' => $now,
            'summary'      => $summary,
            'events'       => array_slice($events, 0, 30),
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function events(): array
    {
        $value = get_option(self::OPTION, array());
        return is_array($value) ? $value : array();
    }

    /** @param array<string,mixed> $payload */
    private static function record(string $type, array $payload): void
    {
        $events = self::events();
        array_unshift($events, array(
            'time'    => time(),
            'type'    => sanitize_key($type),
            'payload' => self::sanitize($payload),
        ));
        if (count($events) > self::LIMIT) {
            $events = array_slice($events, 0, self::LIMIT);
        }
        update_option(self::OPTION, $events, false);
    }

    /** @param array<string,mixed> $values @return array<string,mixed> */
    private static function sanitize(array $values): array
    {
        $clean = array();
        foreach ($values as $key => $value) {
            $safeKey = sanitize_key((string) $key);
            if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
                $clean[$safeKey] = $value;
            } else {
                $clean[$safeKey] = sanitize_text_field((string) $value);
            }
        }
        return $clean;
    }
}
