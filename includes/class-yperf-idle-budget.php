<?php

declare(strict_types=1);
namespace Yablonsky\Performance;

final class IdleBudget
{
    /** @return array<string,mixed> */
    public static function analyze(): array
    {
        $profile = sanitize_key((string) get_option('yperf_performance_profile', 'observe'));
        $deepGuard = get_option('yperf_deep_archive_guard', '0') === '1';
        $frontendControls = 0;
        if ($profile === 'safe' || $profile === 'commerce') { $frontendControls++; }
        if ($profile === 'commerce' && $deepGuard) { $frontendControls++; }
        return array(
            'schema' => 1,
            'profile' => $profile,
            'active_frontend_controls' => $frontendControls,
            'ai_frontend_calls' => 0,
            'ai_dashboard_auto_calls' => 0,
            'local_first' => true,
            'zero_ai_idle_overhead' => true,
        );
    }
}
