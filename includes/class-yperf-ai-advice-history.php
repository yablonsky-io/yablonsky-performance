<?php

declare(strict_types=1);
namespace Yablonsky\Performance;

final class AIAdviceHistory
{
    private const META = 'yperf_ai_advice_history_v1';
    /** @param array<string,mixed> $row */
    public static function record(array $row): void
    {
        $uid = get_current_user_id(); if ($uid < 1) { return; }
        $history = get_user_meta($uid, self::META, true); if (!is_array($history)) { $history = array(); }
        array_unshift($history, array('time'=>(int)($row['time']??time()),'question'=>substr(sanitize_textarea_field((string)($row['question']??'')),0,1200),'answer'=>substr(sanitize_textarea_field((string)($row['answer']??'')),0,6000),'evidence_fingerprint'=>sanitize_text_field((string)($row['evidence_fingerprint']??''))));
        update_user_meta($uid, self::META, array_slice($history, 0, 10));
    }
    /** @return array<string,mixed> */
    public static function latest(): array
    {
        $history = get_user_meta(get_current_user_id(), self::META, true);
        return is_array($history) && isset($history[0]) && is_array($history[0]) ? $history[0] : array();
    }
}
