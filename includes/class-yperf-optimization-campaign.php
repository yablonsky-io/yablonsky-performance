<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class OptimizationCampaign
{
    private const OPTION_LAST = 'yperf_optimization_campaign_last_v1';
    private const OPTION_HISTORY = 'yperf_optimization_campaign_history_v1';
    private const LIMIT = 8;

    /** @param array<string,mixed> $recommendation @return array<string,mixed> */
    public static function start(string $mode, string $profile, array $recommendation = array()): array
    {
        $campaign = array(
            'schema' => 1,
            'id' => wp_generate_uuid4(),
            'started_at' => time(),
            'finished_at' => 0,
            'mode' => in_array($mode, array('manual', 'autopilot', 'ai_advisor'), true) ? $mode : 'manual',
            'profile' => in_array($profile, array('observe', 'safe', 'commerce'), true) ? $profile : 'safe',
            'status' => 'running',
            'recommendation' => self::sanitize($recommendation),
            'regression_guard' => array(),
        );
        update_option(self::OPTION_LAST, $campaign, false);
        return $campaign;
    }

    /** @param array<string,mixed> $campaign @param array<string,mixed> $guard @return array<string,mixed> */
    public static function finish(array $campaign, string $status, array $guard = array()): array
    {
        $campaign['finished_at'] = time();
        $campaign['status'] = sanitize_key($status);
        $campaign['regression_guard'] = self::sanitize($guard);
        update_option(self::OPTION_LAST, $campaign, false);
        $history = self::history();
        array_unshift($history, $campaign);
        if (count($history) > self::LIMIT) {
            $history = array_slice($history, 0, self::LIMIT);
        }
        update_option(self::OPTION_HISTORY, $history, false);
        Telemetry::recordCampaign($campaign);
        return $campaign;
    }

    /** @return array<string,mixed> */
    public static function last(): array
    {
        $value = get_option(self::OPTION_LAST, array());
        return is_array($value) ? $value : array();
    }

    /** @return array<int,array<string,mixed>> */
    public static function history(): array
    {
        $value = get_option(self::OPTION_HISTORY, array());
        return is_array($value) ? $value : array();
    }

    /** @return array<string,mixed> */
    public static function report(): array
    {
        $history = self::history();
        $counts = array('accepted' => 0, 'rolled_back' => 0, 'blocked' => 0, 'error' => 0);
        foreach ($history as $item) {
            if (!is_array($item)) {
                continue;
            }
            $status = sanitize_key((string) ($item['status'] ?? ''));
            if (isset($counts[$status])) {
                ++$counts[$status];
            }
        }
        return array(
            'schema' => 1,
            'generated_at' => time(),
            'last' => self::last(),
            'history_count' => count($history),
            'counts' => $counts,
        );
    }

    /** @param array<string,mixed> $value @return array<string,mixed> */
    private static function sanitize(array $value): array
    {
        $clean = array();
        foreach ($value as $key => $item) {
            $safeKey = sanitize_key((string) $key);
            if (is_array($item)) {
                $clean[$safeKey] = self::sanitize($item);
            } elseif (is_bool($item) || is_int($item) || is_float($item) || $item === null) {
                $clean[$safeKey] = $item;
            } else {
                $clean[$safeKey] = sanitize_text_field((string) $item);
            }
        }
        return $clean;
    }
}
