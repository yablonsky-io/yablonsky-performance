<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Small, bounded live-activity ledger for guided migration actions.
 *
 * The browser polls this while a longer AJAX request is running. The ledger is
 * deliberately local, short-lived in spirit, and contains no credentials or
 * provider secrets.
 */
final class MigrationActivity
{
    private const OPTION = 'yperf_migration_activity_v1';
    private const MAX_EVENTS = 12;

    /** @return array<string,mixed> */
    public static function get(string $channel): array
    {
        $all = get_option(self::OPTION, array());
        if (!is_array($all)) {
            return array();
        }
        $value = $all[sanitize_key($channel)] ?? array();
        return is_array($value) ? $value : array();
    }

    public static function reset(string $channel, string $label, string $detail = ''): void
    {
        self::set($channel, 'starting', 0, $label, $detail, false, true);
    }

    public static function set(
        string $channel,
        string $phase,
        int $percent,
        string $label,
        string $detail = '',
        bool $terminal = false,
        bool $resetEvents = false
    ): void {
        $key = sanitize_key($channel);
        $all = get_option(self::OPTION, array());
        if (!is_array($all)) {
            $all = array();
        }
        $previous = is_array($all[$key] ?? null) ? $all[$key] : array();
        $events = $resetEvents ? array() : (is_array($previous['events'] ?? null) ? $previous['events'] : array());
        $message = trim($label . ($detail !== '' ? ' — ' . $detail : ''));
        if ($message !== '') {
            $events[] = array(
                'time' => time(),
                'message' => sanitize_text_field($message),
            );
        }
        if (count($events) > self::MAX_EVENTS) {
            $events = array_slice($events, -self::MAX_EVENTS);
        }
        $all[$key] = array(
            'channel' => $key,
            'phase' => sanitize_key($phase),
            'percent' => max(0, min(100, $percent)),
            'label' => sanitize_text_field($label),
            'detail' => sanitize_text_field($detail),
            'terminal' => $terminal,
            'updated_at' => time(),
            'events' => $events,
        );
        update_option(self::OPTION, $all, false);
    }
}
