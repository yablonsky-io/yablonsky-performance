<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Human-facing migration journey.
 *
 * This class does not mutate providers. It converts existing evidence and
 * migration contracts into one ordered path with exactly one current step.
 */
final class MigrationJourney
{
    /**
     * @param array<string,mixed> $migration
     * @param array<string,mixed> $runtime
     * @param array<string,mixed> $benchmark
     * @param array<string,mixed> $performancePlan
     * @return array<string,mixed>
     */
    public static function build(array $migration, array $runtime, array $benchmark, array $performancePlan): array
    {
        if (empty($migration['available'])) {
            $last = W3tcMigrationExecutor::last();
            $cleanup = W3tcMigrationExecutor::postHandoverCleanupStatus();
            $completedHistory = (string) ($last['state'] ?? '') === 'active' && !empty($cleanup['complete']);
            if ($completedHistory) {
                $steps = array(
                    self::step(1, __('Detect the source', 'yablonsky-performance'), __('Legacy W3 Total Cache ownership was identified before handover.', 'yablonsky-performance'), true),
                    self::step(2, __('Prove the current site', 'yablonsky-performance'), __('A clean baseline was captured before migration.', 'yablonsky-performance'), true),
                    self::step(3, __('Activate a safe YPerf baseline', 'yablonsky-performance'), __('YPerf-owned replacement responsibilities were activated and proved.', 'yablonsky-performance'), true),
                    self::step(4, __('Resolve legacy responsibilities', 'yablonsky-performance'), __('Legacy responsibilities were retired or replaced explicitly.', 'yablonsky-performance'), true),
                    self::step(5, __('Handover and verify', 'yablonsky-performance'), __('Post-handover Health Check completed and cleanup was reconciled.', 'yablonsky-performance'), true),
                );
                foreach ($steps as &$step) { $step['state'] = 'complete'; }
                unset($step);
                return array(
                    'schema' => 2,
                    'applicable' => true,
                    'history_only' => true,
                    'provider' => 'W3 Total Cache',
                    'provider_version' => sanitize_text_field((string) ($last['provider_version'] ?? '')),
                    'completed' => 5,
                    'total' => 5,
                    'progress_percent' => 100,
                    'current_step' => 5,
                    'steps' => $steps,
                    'handover_active' => true,
                    'cleanup_required' => false,
                    'cleanup' => $cleanup,
                    'summary' => __('Migration completed successfully. W3 Total Cache was handed over to YPerf and the completed migration remains visible as site history.', 'yablonsky-performance'),
                    'completed_at' => max(0, (int) ($last['completed_at'] ?? $last['updated_at'] ?? $last['started_at'] ?? 0)),
                );
            }
            return array(
                'schema' => 2,
                'applicable' => false,
                'provider' => '',
                'provider_version' => '',
                'completed' => 0,
                'total' => 0,
                'progress_percent' => 100,
                'current_step' => 0,
                'steps' => array(),
                'summary' => __('No supported legacy performance provider is active. No provider migration is required.', 'yablonsky-performance'),
            );
        }

        $runtimeSummary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        $runtimeCurrentClean = $runtime !== array()
            && (string) ($runtime['version'] ?? '') === YPERF_VERSION
            && (int) ($runtimeSummary['fail'] ?? 0) === 0;
        $benchmarkCurrent = $benchmark !== array() && (string) ($benchmark['version'] ?? '') === YPERF_VERSION;
        $evidenceReady = $runtimeCurrentClean && $benchmarkCurrent;

        $profile = sanitize_key((string) ($performancePlan['profile'] ?? 'observe'));
        $lastApply = is_array($performancePlan['last_apply'] ?? null) ? $performancePlan['last_apply'] : array();
        $profileAccepted = $profile !== 'observe'
            && (string) ($lastApply['outcome'] ?? '') === 'accepted'
            && empty($lastApply['rolled_back']);

        $handover = W3tcMigrationExecutor::assessment();
        $pendingHandoff = W3tcMigrationExecutor::pendingHandoff();
        $awaitingUserHandoff = $pendingHandoff !== array()
            && (string) ($pendingHandoff['state'] ?? '') === 'awaiting_user_deactivation';
        $lastHandover = W3tcMigrationExecutor::last();
        $cleanup = W3tcMigrationExecutor::postHandoverCleanupStatus();
        $cleanupRequired = !empty($cleanup['required']) || !empty($cleanup['blocked']);
        $handoverReady = !empty($handover['ready']) || $awaitingUserHandoff;
        $handoverActive = (string) ($lastHandover['state'] ?? '') === 'active';

        $responsibilitiesReady = $profileAccepted && ($handoverReady || $handoverActive);
        $handoverVerified = $responsibilitiesReady && $handoverActive && !$cleanupRequired;
        $states = array(
            true,
            $evidenceReady,
            $evidenceReady && $profileAccepted,
            $responsibilitiesReady,
            $handoverVerified,
        );

        $steps = array(
            self::step(
                1,
                __('Detect the source', 'yablonsky-performance'),
                __('Identify the current provider, its responsibilities and owned WordPress drop-ins before changing anything.', 'yablonsky-performance'),
                $states[0]
            ),
            self::step(
                2,
                __('Prove the current site', 'yablonsky-performance'),
                __('Refresh Environment, Runtime Acceptance and Benchmark so migration decisions use current-version evidence.', 'yablonsky-performance'),
                $states[1]
            ),
            self::step(
                3,
                __('Activate a safe YPerf baseline', 'yablonsky-performance'),
                __('Apply the recommended YPerf-owned local profile and prove it with a fresh Runtime Acceptance before provider retirement.', 'yablonsky-performance'),
                $states[2]
            ),
            self::step(
                4,
                __('Resolve legacy responsibilities', 'yablonsky-performance'),
                __('Every active W3 Total Cache responsibility must have a proven replacement or an explicit retirement path before handover.', 'yablonsky-performance'),
                $states[3]
            ),
            self::step(
                5,
                __('Handover and verify', 'yablonsky-performance'),
                __('Confirm the provider handoff yourself in WordPress, then let YPerf prove the replacement layers. YPerf never changes another plugin’s activation status.', 'yablonsky-performance'),
                $states[4]
            ),
        );

        $completed = 0;
        $current = 0;
        foreach ($states as $index => $complete) {
            if ($complete) {
                ++$completed;
                continue;
            }
            if ($current === 0) {
                $current = $index + 1;
            }
        }
        if ($current === 0 && $completed === count($states)) {
            $current = count($states);
        }

        foreach ($steps as $index => &$step) {
            $number = $index + 1;
            $step['state'] = !empty($states[$index]) ? 'complete' : ($number === $current ? 'current' : 'locked');
        }
        unset($step);

        $total = count($states);
        $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return array(
            'schema' => 1,
            'applicable' => true,
            'provider' => sanitize_text_field((string) ($migration['provider'] ?? '')),
            'provider_version' => sanitize_text_field((string) ($migration['provider_version'] ?? '')),
            'completed' => $completed,
            'total' => $total,
            'progress_percent' => $percent,
            'current_step' => $current,
            'steps' => $steps,
            'runtime_current_clean' => $runtimeCurrentClean,
            'benchmark_current' => $benchmarkCurrent,
            'evidence_ready' => $evidenceReady,
            'profile' => $profile,
            'profile_accepted' => $profileAccepted,
            'recommended_profile' => sanitize_key((string) ($performancePlan['recommended_profile'] ?? 'safe')),
            'handover_ready' => $handoverReady || ($handoverActive && !empty($cleanup['required'])),
            'handover_active' => $handoverActive,
            'awaiting_user_handoff' => $awaitingUserHandoff,
            'cleanup_required' => $cleanupRequired,
            'cleanup' => $cleanup,
            'handover' => $handover,
            'summary' => $handoverActive
                ? ($cleanupRequired
                    ? __('Provider handover succeeded. One provider-owned WordPress drop-in still needs protected cleanup and proof.', 'yablonsky-performance')
                    : __('Migration is complete and the YPerf handover record is active.', 'yablonsky-performance'))
                : ($awaitingUserHandoff
                    ? __('Preparation is complete. The provider activation decision stays with you; continue after W3 Total Cache is inactive.', 'yablonsky-performance')
                    : __('Follow this screen from top to bottom. YPerf exposes only the next safe action.', 'yablonsky-performance')),
        );
    }

    /** @return array<string,mixed> */
    private static function step(int $number, string $title, string $description, bool $complete): array
    {
        return array(
            'number' => $number,
            'title' => $title,
            'description' => $description,
            'state' => $complete ? 'complete' : 'locked',
        );
    }
}
