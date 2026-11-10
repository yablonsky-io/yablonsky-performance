<?php

declare(strict_types=1);
namespace Yablonsky\Performance;

final class AIPlanPolicy
{
    /** @param array<string,mixed> $plan @return array<string,mixed> */
    public static function validate(array $plan): array
    {
        $allowed = array('observe','safe','commerce');
        $target = sanitize_key((string) ($plan['ai_target_profile'] ?? $plan['target_profile'] ?? 'observe'));
        if (!in_array($target, $allowed, true)) { $target = 'observe'; }
        $plan['ai_target_profile'] = $target;
        $plan['target_profile'] = $target;
        $plan['confidence'] = in_array((string) ($plan['confidence'] ?? ''), array('low','medium','high'), true) ? (string) $plan['confidence'] : 'low';
        $plan['summary'] = substr(trim(sanitize_text_field((string) ($plan['summary'] ?? ''))), 0, 600);
        foreach (array('reasons','warnings') as $field) {
            $clean = array();
            foreach (array_slice((array) ($plan[$field] ?? array()), 0, 6) as $item) {
                if (is_scalar($item)) { $v = substr(trim(sanitize_text_field((string) $item)), 0, 400); if ($v !== '') { $clean[] = $v; } }
            }
            $plan[$field] = $clean;
        }
        $plan['policy_version'] = 2;
        $plan['ai_write_authority'] = 'profile_selection_only';
        return $plan;
    }
}
