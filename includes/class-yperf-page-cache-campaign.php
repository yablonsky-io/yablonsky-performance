<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Guided YPerf Page Cache activation and delivery-stability campaign.
 *
 * The campaign is intentionally bounded and local. It never changes Nginx,
 * Redis, PHP-FPM, Cloudflare, theme styles or third-party scripts. It can only
 * activate the YPerf-owned portable Page Cache, prove MISS -> HIT, run Runtime
 * Acceptance and collect compact same-origin hot-HIT timing evidence.
 */
final class PageCacheCampaign
{
    private const STATE_OPTION = 'yperf_page_cache_campaign_state_v1';
    private const REPORT_OPTION = 'yperf_page_cache_campaign_report_v1';
    private const MAX_EVENTS = 18;
    private const STABILITY_SAMPLES = 7;

    /** @return array<string,mixed> */
    public static function state(): array
    {
        $value = get_option(self::STATE_OPTION, array());
        return is_array($value) ? $value : array();
    }

    /** @return array<string,mixed> */
    public static function report(): array
    {
        $value = get_option(self::REPORT_OPTION, array());
        return is_array($value) ? $value : array();
    }

    /** @return array<string,mixed> */
    public static function start(): array
    {
        $externalAuthority = RuntimeAcceptance::externalPageCacheAuthority(RuntimeAcceptance::lastReport());
        if (!empty($externalAuthority['detected']) && !PageCacheExecutor::enabled()) {
            return self::error(__('An external page-cache authority is already visible in current Runtime evidence. YPerf will not activate a competing portable Page Cache.', 'yablonsky-performance'));
        }
        if (NativePageCacheAdoption::last() !== array()) {
            return self::error(__('A native page-cache adoption record already exists. YPerf will not activate a second page-cache executor until that authority is reviewed or released.', 'yablonsky-performance'));
        }

        $state = array(
            'schema' => 1,
            'status' => 'running',
            'started_at' => time(),
            'updated_at' => time(),
            'version' => YPERF_VERSION,
            'progress' => 0,
            'phase' => 'preflight',
            'expected_step' => 'preflight',
            'message' => __('Preparing a fresh read-only baseline.', 'yablonsky-performance'),
            'was_active' => PageCacheExecutor::enabled(),
            'newly_activated' => false,
            'preflight_home_ms' => 0,
            'post_home_ms' => 0,
            'hit_samples_ms' => array(),
            'events' => array(),
        );
        self::pushEvent($state, __('Campaign started.', 'yablonsky-performance'));
        self::saveState($state);
        return self::publicState($state);
    }

    /** @return array<string,mixed> */
    public static function step(string $step): array
    {
        $state = self::state();
        if ($state === array() || (string) ($state['version'] ?? '') !== YPERF_VERSION) {
            return self::error(__('Start a new Page Cache stability campaign for the current YPerf version.', 'yablonsky-performance'));
        }
        if ((string) ($state['status'] ?? '') !== 'running') {
            return self::error(__('The Page Cache stability campaign is not currently running.', 'yablonsky-performance'));
        }

        $expected = sanitize_key((string) ($state['expected_step'] ?? 'preflight'));
        if ($step !== $expected) {
            return self::error(__('The Page Cache campaign step is out of sequence. Start a fresh campaign instead of skipping safety gates.', 'yablonsky-performance'));
        }

        if ($step === 'preflight') {
            return self::runPreflight($state);
        }
        if ($step === 'activate') {
            return self::runActivation($state);
        }
        if (preg_match('/^stability_(\d+)$/', $step, $match) === 1) {
            return self::runStabilitySample($state, (int) $match[1]);
        }
        if ($step === 'finalize') {
            return self::finalize($state);
        }
        return self::error(__('Unknown Page Cache campaign step.', 'yablonsky-performance'));
    }

    /** @param array<string,mixed> $state @return array<string,mixed> */
    private static function runPreflight(array $state): array
    {
        self::setProgress($state, 10, 'preflight', __('Running fresh Runtime Acceptance before any cache write.', 'yablonsky-performance'));
        $runtime = RuntimeAcceptance::run();
        update_option('yperf_runtime_acceptance_v1', $runtime, false);
        Telemetry::recordRuntime($runtime);
        RuntimeHistory::record($runtime, 'page_cache_campaign_preflight');
        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        if ((int) ($summary['fail'] ?? 0) > 0) {
            return self::failState($state, __('Fresh Runtime Acceptance contains a functional failure. Page Cache activation was not attempted.', 'yablonsky-performance'));
        }
        $state['preflight_home_ms'] = self::extractHomepageMs($runtime);
        self::pushEvent($state, __('Preflight Runtime Acceptance passed.', 'yablonsky-performance'));
        $state['expected_step'] = 'activate';
        self::setProgress($state, 24, 'preflight_complete', __('Fresh baseline is clean. Ready to prove Page Cache execution.', 'yablonsky-performance'));
        return self::publicState($state);
    }

    /** @param array<string,mixed> $state @return array<string,mixed> */
    private static function runActivation(array $state): array
    {
        self::setProgress($state, 32, 'activation', __('Activating the YPerf-owned Page Cache boundary.', 'yablonsky-performance'));
        $wasActive = !empty($state['was_active']);
        if (!$wasActive) {
            $activation = PageCacheExecutor::activate(false);
            if (empty($activation['ok'])) {
                return self::failState($state, sanitize_text_field((string) ($activation['message'] ?? __('Page Cache activation failed before execution proof.', 'yablonsky-performance'))));
            }
            $state['newly_activated'] = true;
            self::pushEvent($state, __('YPerf Page Cache drop-in activated.', 'yablonsky-performance'));
        } else {
            self::pushEvent($state, __('Existing YPerf Page Cache activation detected; campaign will re-prove it without changing ownership.', 'yablonsky-performance'));
        }

        self::setProgress($state, 42, 'cache_proof', __('Proving MISS → HIT execution.', 'yablonsky-performance'));
        $proof = PageCacheExecutor::prove();
        if (empty($proof['ok'])) {
            $rollback = self::rollbackNewActivation($state);
            $message = !empty($state['newly_activated']) || $wasActive
                ? __('YPerf Page Cache did not prove MISS → HIT. Review is required before any further cache change.', 'yablonsky-performance')
                : ($rollback
                    ? __('YPerf Page Cache did not prove MISS → HIT. The new activation was rolled back automatically.', 'yablonsky-performance')
                    : __('YPerf Page Cache did not prove MISS → HIT and automatic rollback could not be confirmed. Stop and review the cache artifact before continuing.', 'yablonsky-performance'));
            return self::failState($state, $message);
        }
        self::pushEvent($state, __('MISS → HIT execution proof passed.', 'yablonsky-performance'));

        self::setProgress($state, 50, 'post_activation_acceptance', __('Running Runtime Acceptance after Page Cache activation.', 'yablonsky-performance'));
        $runtime = RuntimeAcceptance::run();
        update_option('yperf_runtime_acceptance_v1', $runtime, false);
        Telemetry::recordRuntime($runtime);
        RuntimeHistory::record($runtime, 'page_cache_campaign_post_activation');
        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        if ((int) ($summary['fail'] ?? 0) > 0) {
            self::recordRuntimeFailures($state, $runtime);
            $rollback = self::rollbackNewActivation($state);
            if ($wasActive) {
                return self::failState($state, __('Runtime Acceptance failed while re-proving the existing Page Cache. The pre-existing executor was not removed automatically and now requires review.', 'yablonsky-performance'));
            }
            $rollbackVerified = $rollback && self::verifyRollbackRuntime();
            return self::failState($state, ($rollback && $rollbackVerified)
                ? __('Runtime Acceptance failed after Page Cache activation. The new activation was rolled back automatically.', 'yablonsky-performance')
                : __('Runtime Acceptance failed after Page Cache activation and automatic rollback could not be confirmed. Stop and review the cache artifact before continuing.', 'yablonsky-performance'));
        }
        $state['post_home_ms'] = self::extractHomepageMs($runtime);
        self::pushEvent($state, __('Post-activation Runtime Acceptance passed.', 'yablonsky-performance'));
        $state['expected_step'] = 'stability_1';
        self::setProgress($state, 58, 'accepted', __('Page Cache is execution-proven. Measuring repeat-HIT stability.', 'yablonsky-performance'));
        return self::publicState($state);
    }

    /** @param array<string,mixed> $state @return array<string,mixed> */
    private static function runStabilitySample(array $state, int $sample): array
    {
        if ($sample < 1 || $sample > self::STABILITY_SAMPLES) {
            return self::error(__('Invalid Page Cache stability sample.', 'yablonsky-performance'));
        }
        if (!PageCacheExecutor::enabled()) {
            return self::failState($state, __('The YPerf Page Cache is no longer active, so stability sampling stopped.', 'yablonsky-performance'));
        }
        $baseProgress = 58 + (($sample - 1) * 4);
        self::setProgress($state, $baseProgress, 'stability', sprintf(
            /* translators: 1: sample number, 2: total samples */
            __('Measuring cached delivery sample %1$d/%2$d.', 'yablonsky-performance'),
            $sample,
            self::STABILITY_SAMPLES
        ));
        $hit = PageCacheExecutor::sampleHit();
        if (empty($hit['ok'])) {
            $wasNew = !empty($state['newly_activated']);
            $rollback = self::rollbackNewActivation($state);
            if (!$wasNew) {
                return self::failState($state, __('A hot-cache HIT sample failed while re-proving an existing executor. The pre-existing cache was not removed automatically and requires review.', 'yablonsky-performance'));
            }
            return self::failState($state, $rollback
                ? __('A hot-cache HIT sample failed during stability measurement. The new activation was rolled back automatically.', 'yablonsky-performance')
                : __('A hot-cache HIT sample failed and automatic rollback could not be confirmed. Stop and review the cache artifact before continuing.', 'yablonsky-performance'));
        }
        $sampleData = is_array($hit['sample'] ?? null) ? $hit['sample'] : array();
        $ms = max(0, (int) ($sampleData['ms'] ?? 0));
        $samples = is_array($state['hit_samples_ms'] ?? null) ? $state['hit_samples_ms'] : array();
        $samples[] = $ms;
        $state['hit_samples_ms'] = array_values(array_slice($samples, -self::STABILITY_SAMPLES));
        self::pushEvent($state, sprintf(
            /* translators: 1: sample number, 2: cached response time in milliseconds */
            __('Hot-cache HIT sample %1$d passed at %2$d ms.', 'yablonsky-performance'),
            $sample,
            $ms
        ));
        $state['expected_step'] = $sample < self::STABILITY_SAMPLES ? 'stability_' . (string) ($sample + 1) : 'finalize';
        self::setProgress($state, min(88, $baseProgress + 4), 'stability', __('Hot-cache HIT sample accepted.', 'yablonsky-performance'));
        return self::publicState($state);
    }

    /** @param array<string,mixed> $state @return array<string,mixed> */
    private static function finalize(array $state): array
    {
        $samples = is_array($state['hit_samples_ms'] ?? null) ? array_map('intval', $state['hit_samples_ms']) : array();
        if (count($samples) < self::STABILITY_SAMPLES) {
            return self::failState($state, __('Complete all bounded repeat-HIT samples before finalizing the stability campaign.', 'yablonsky-performance'));
        }

        self::setProgress($state, 92, 'final_acceptance', __('Running final Runtime Acceptance.', 'yablonsky-performance'));
        $runtime = RuntimeAcceptance::run();
        update_option('yperf_runtime_acceptance_v1', $runtime, false);
        Telemetry::recordRuntime($runtime);
        RuntimeHistory::record($runtime, 'page_cache_campaign_final');
        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        if ((int) ($summary['fail'] ?? 0) > 0) {
            self::recordRuntimeFailures($state, $runtime);
            $wasNew = !empty($state['newly_activated']);
            $rollback = self::rollbackNewActivation($state);
            if (!$wasNew) {
                return self::failState($state, __('Final Runtime Acceptance failed while re-proving an existing Page Cache. The pre-existing executor was not removed automatically and requires review.', 'yablonsky-performance'));
            }
            $rollbackVerified = $rollback && self::verifyRollbackRuntime();
            return self::failState($state, ($rollback && $rollbackVerified)
                ? __('Final Runtime Acceptance failed. The new Page Cache activation was rolled back automatically.', 'yablonsky-performance')
                : __('Final Runtime Acceptance failed and automatic rollback could not be confirmed. Stop and review the cache artifact before continuing.', 'yablonsky-performance'));
        }
        $state['post_home_ms'] = self::extractHomepageMs($runtime);

        sort($samples, SORT_NUMERIC);
        $median = self::median($samples);
        $min = (int) min($samples);
        $max = (int) max($samples);
        $spreadPct = $median > 0 ? (($max - $min) / $median) * 100 : 0.0;
        $mad = self::medianAbsoluteDeviation($samples, $median);
        $typicalVariationPct = $median > 0 ? ($mad / $median) * 100 : 0.0;
        $stability = $typicalVariationPct <= 12 ? 'stable' : ($typicalVariationPct <= 25 ? 'variable' : 'noisy');
        $before = max(0, (int) ($state['preflight_home_ms'] ?? 0));
        $after = max(0, (int) ($state['post_home_ms'] ?? 0));
        $improvement = $before > 0 ? round((($before - $after) / $before) * 100, 1) : 0.0;

        $report = array(
            'schema' => 1,
            'generated_at' => time(),
            'version' => YPERF_VERSION,
            'status' => 'proven',
            'page_cache_active' => PageCacheExecutor::enabled(),
            'preflight_home_ms' => $before,
            'post_home_ms' => $after,
            'homepage_change_pct' => $improvement,
            'cached_hit_samples_ms' => $samples,
            'cached_hit_median_ms' => (int) round($median),
            'cached_hit_min_ms' => $min,
            'cached_hit_max_ms' => $max,
            'cached_hit_spread_pct' => round($spreadPct, 1),
            'cached_hit_typical_variation_pct' => round($typicalVariationPct, 1),
            'cached_hit_mad_ms' => round($mad, 1),
            'stability_method' => 'hot_hit_mad_v2',
            'stability_sample_count' => count($samples),
            'stability' => $stability,
            'runtime_summary' => array(
                'pass' => (int) ($summary['pass'] ?? 0),
                'fail' => (int) ($summary['fail'] ?? 0),
                'skip' => (int) ($summary['skip'] ?? 0),
                'attention' => (int) ($summary['attention'] ?? 0),
            ),
            'scope' => array(
                'nginx_changed' => false,
                'redis_changed' => false,
                'php_fpm_changed' => false,
                'cloudflare_changed' => false,
                'third_party_scripts_changed' => false,
                'theme_styles_changed' => false,
            ),
        );
        update_option(self::REPORT_OPTION, $report, false);
        self::pushEvent($state, __('Final Runtime Acceptance passed.', 'yablonsky-performance'));
        self::pushEvent($state, __('Public delivery stability campaign completed.', 'yablonsky-performance'));
        $state['status'] = 'complete';
        $state['phase'] = 'complete';
        $state['expected_step'] = 'complete';
        $state['progress'] = 100;
        $state['message'] = __('Page Cache is active, execution-proven and hot-HIT stability has been measured.', 'yablonsky-performance');
        $state['updated_at'] = time();
        self::saveState($state);
        return self::publicState($state) + array('report' => $report);
    }

    /** @param array<string,mixed> $state @param array<string,mixed> $runtime */
    private static function recordRuntimeFailures(array &$state, array $runtime): void
    {
        $recorded = 0;
        foreach ((array) ($runtime['tests'] ?? array()) as $test) {
            if (!is_array($test) || (string) ($test['status'] ?? '') !== 'fail') {
                continue;
            }
            $name = sanitize_text_field((string) ($test['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $details = sanitize_text_field((string) ($test['details'] ?? ''));
            self::pushEvent($state, $details !== '' ? $name . ': ' . $details : $name);
            ++$recorded;
            if ($recorded >= 4) {
                break;
            }
        }
    }

    private static function verifyRollbackRuntime(): bool
    {
        $runtime = RuntimeAcceptance::run();
        update_option('yperf_runtime_acceptance_v1', $runtime, false);
        Telemetry::recordRuntime($runtime);
        RuntimeHistory::record($runtime, 'page_cache_campaign_rollback_verification');
        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        return (int) ($summary['fail'] ?? 0) === 0;
    }

    /** @param array<string,mixed> $state */
    private static function rollbackNewActivation(array &$state): bool
    {
        if (empty($state['newly_activated'])) {
            return false;
        }
        $rollback = PageCacheExecutor::disable(false);
        if (!empty($rollback['ok'])) {
            self::pushEvent($state, __('New Page Cache activation rolled back.', 'yablonsky-performance'));
            $state['newly_activated'] = false;
            return true;
        }
        self::pushEvent($state, __('Automatic Page Cache rollback could not be confirmed and requires review.', 'yablonsky-performance'));
        return false;
    }

    /** @param array<string,mixed> $state @return array<string,mixed> */
    private static function failState(array $state, string $message): array
    {
        $state['status'] = 'error';
        $state['phase'] = 'error';
        $state['progress'] = max(0, min(99, (int) ($state['progress'] ?? 0)));
        $state['message'] = sanitize_text_field($message);
        $state['updated_at'] = time();
        self::pushEvent($state, $message);
        self::saveState($state);
        return array('ok' => false, 'message' => sanitize_text_field($message), 'state' => self::publicState($state));
    }

    /** @param array<string,mixed> $state */
    private static function setProgress(array &$state, int $progress, string $phase, string $message): void
    {
        $state['progress'] = max(0, min(100, $progress));
        $state['phase'] = sanitize_key($phase);
        $state['message'] = sanitize_text_field($message);
        $state['updated_at'] = time();
        self::saveState($state);
    }

    /** @param array<string,mixed> $state */
    private static function pushEvent(array &$state, string $message): void
    {
        $events = is_array($state['events'] ?? null) ? $state['events'] : array();
        $events[] = array('time' => time(), 'message' => sanitize_text_field($message));
        $state['events'] = array_values(array_slice($events, -self::MAX_EVENTS));
    }

    /** @param array<string,mixed> $state */
    private static function saveState(array $state): void
    {
        update_option(self::STATE_OPTION, $state, false);
    }

    /** @param array<string,mixed> $runtime */
    private static function extractHomepageMs(array $runtime): int
    {
        foreach ((array) ($runtime['tests'] ?? array()) as $test) {
            if (!is_array($test)) {
                continue;
            }
            if ((string) ($test['name'] ?? '') === 'Homepage' && isset($test['ms']) && is_numeric($test['ms'])) {
                return max(0, (int) $test['ms']);
            }
        }
        return 0;
    }

    /** @param array<int,int> $values */
    private static function median(array $values): float
    {
        $count = count($values);
        if ($count === 0) {
            return 0.0;
        }
        sort($values, SORT_NUMERIC);
        $middle = intdiv($count, 2);
        if ($count % 2 === 1) {
            return (float) $values[$middle];
        }
        return ((float) $values[$middle - 1] + (float) $values[$middle]) / 2;
    }

    /** @param array<int,int> $values */
    private static function medianAbsoluteDeviation(array $values, float $median): float
    {
        if ($values === array()) {
            return 0.0;
        }
        $deviations = array();
        foreach ($values as $value) {
            $deviations[] = abs((float) $value - $median);
        }
        return self::median(array_map(static function (float $value): int {
            return (int) round($value);
        }, $deviations));
    }

    /** @param array<string,mixed> $state @return array<string,mixed> */
    private static function publicState(array $state): array
    {
        $events = array();
        foreach ((array) ($state['events'] ?? array()) as $event) {
            if (!is_array($event)) {
                continue;
            }
            $events[] = array(
                'time' => max(0, (int) ($event['time'] ?? 0)),
                'message' => sanitize_text_field((string) ($event['message'] ?? '')),
            );
        }
        return array(
            'ok' => (string) ($state['status'] ?? '') !== 'error',
            'status' => sanitize_key((string) ($state['status'] ?? 'idle')),
            'progress' => max(0, min(100, (int) ($state['progress'] ?? 0))),
            'phase' => sanitize_key((string) ($state['phase'] ?? 'idle')),
            'expected_step' => sanitize_key((string) ($state['expected_step'] ?? '')),
            'message' => sanitize_text_field((string) ($state['message'] ?? '')),
            'was_active' => !empty($state['was_active']),
            'newly_activated' => !empty($state['newly_activated']),
            'preflight_home_ms' => max(0, (int) ($state['preflight_home_ms'] ?? 0)),
            'post_home_ms' => max(0, (int) ($state['post_home_ms'] ?? 0)),
            'hit_samples_ms' => array_map('intval', (array) ($state['hit_samples_ms'] ?? array())),
            'events' => $events,
        );
    }

    /** @return array<string,mixed> */
    private static function error(string $message): array
    {
        return array('ok' => false, 'message' => sanitize_text_field($message));
    }
}
