<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/** Safe local-only recommendation engine. Native/server write paths are excluded. */
final class Autopilot
{
    /** @param array<string,mixed> $caps @param array<string,mixed> $runtime @param array<string,mixed> $proof @return array<string,mixed> */
    public static function recommend(array $caps, array $runtime, array $proof): array
    {
        unset($proof);
        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        $hasCleanEvidence = $runtime !== array() && (int) ($summary['fail'] ?? 0) === 0;
        $woocommerce = !empty($caps['application']['woocommerce']);
        $deepArchiveProven = self::deepArchiveProven($runtime);

        // Autopilot itself always performs a fresh read-only preflight before any
        // write. Do not force the user to run a separate prerequisite check first.
        // If stored evidence is missing/stale, start conservatively at SAFE and
        // let the fresh preflight re-evaluate the final profile before applying.
        if (!$hasCleanEvidence) {
            return array(
                'schema' => 2,
                'state' => 'ready_preflight',
                'ready' => true,
                'target_profile' => 'safe',
                'label' => 'WordPress Safe Performance',
                'confidence' => 'preflight',
                'reasons' => array(
                    'A fresh read-only Health Check will run automatically before any setting changes.',
                    'If the baseline is not healthy, YPerf stops without changing the site.',
                ),
                'boundaries' => self::boundaries(),
            );
        }

        $profile = ($woocommerce && $deepArchiveProven) ? 'commerce' : 'safe';
        $reasons = array('Stored health evidence is clean; Autopilot will still capture a fresh preflight before any write.');
        if ($profile === 'commerce') {
            $reasons[] = 'WooCommerce is present and a real deep-archive workload was proven, so the maximum local WordPress + WooCommerce profile can be exercised.';
        } elseif ($woocommerce) {
            $reasons[] = 'WooCommerce is present, but no real deep-pagination workload is proven; Autopilot stays on the lower-risk WordPress Safe Performance profile.';
        } else {
            $reasons[] = 'No WooCommerce deep-archive workload requires the commerce-specific guard.';
        }

        return array(
            'schema' => 2,
            'state' => 'ready',
            'ready' => true,
            'target_profile' => $profile,
            'label' => $profile === 'commerce' ? 'WordPress + WooCommerce Performance' : 'WordPress Safe Performance',
            'confidence' => $profile === 'commerce' ? 'high' : 'high',
            'reasons' => $reasons,
            'boundaries' => self::boundaries(),
        );
    }

    /** @return array<int,string> */
    private static function boundaries(): array
    {
        return array(
            'Fresh Runtime Acceptance baseline before any write.',
            'Only YPerf-owned WordPress controls may change automatically.',
            'Nginx, Redis, PHP-FPM, OS packages and provider configuration stay unchanged.',
            'Functional FAIL triggers rollback.',
            'Material performance regression must repeat before automatic rollback.',
        );
    }

    /** @param array<string,mixed> $runtime */
    private static function deepArchiveProven(array $runtime): bool
    {
        foreach ((array) ($runtime['tests'] ?? array()) as $test) {
            if (!is_array($test) || (string) ($test['name'] ?? '') !== 'WooCommerce deep pagination') {
                continue;
            }
            return (string) ($test['status'] ?? '') === 'pass';
        }
        return false;
    }
}
