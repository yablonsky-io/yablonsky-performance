<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Optional Yablonsky AI advisor.
 *
 * The AI model can recommend only an allow-listed YPerf profile. It never owns
 * execution. Every recommendation is constrained by the deterministic local
 * Autopilot ceiling and must be explicitly approved by an administrator before
 * the existing Runtime Acceptance / rollback campaign can apply anything.
 */
final class AIAdvisor
{
    private const OPTION_RECOMMENDATION = 'yperf_ai_advisor_recommendation_v1';
    private const ANSWER_TRANSIENT_PREFIX = 'yperf_ai_advisor_answer_';
    private const MAX_QUESTION_LENGTH = 1200;

    /** @return array<string,mixed> */
    public static function availability(): array
    {
        $availability = YablonskyAIClient::availability();
        $availability['service_readiness'] = ExternalServiceReadiness::analyze();
        $availability['release_train_gate'] = ReleaseTrainGate::build();
        return $availability;
    }

    /**
     * Build the deliberately minimized evidence envelope that may leave the site
     * only after an administrator explicitly requests AI analysis.
     *
     * @param array<string,mixed> $caps
     * @param array<string,mixed> $runtime
     * @param array<string,mixed> $proof
     * @param array<string,mixed> $plan
     * @param array<string,mixed> $host
     * @param array<string,mixed> $benchmark
     * @return array<string,mixed>
     */
    public static function evidence(array $caps, array $runtime, array $proof, array $plan, array $host, array $benchmark): array
    {
        $runtimeSummary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        $originCost = is_array($runtime['origin_cost_summary'] ?? null) ? $runtime['origin_cost_summary'] : array();
        $pageProof = is_array($proof['page_cache'] ?? null) ? $proof['page_cache'] : array();
        $redisProof = is_array($proof['object_cache']['redis'] ?? null) ? $proof['object_cache']['redis'] : array();
        $originProof = is_array($proof['origin'] ?? null) ? $proof['origin'] : array();
        $hostInfo = is_array($host['host'] ?? null) ? $host['host'] : array();
        $nginx = is_array($host['nginx'] ?? null) ? $host['nginx'] : array();
        $fpm = is_array($host['php_fpm'] ?? null) ? $host['php_fpm'] : array();
        $redis = is_array($host['redis'] ?? null) ? $host['redis'] : array();
        $benchmarkSummary = is_array($benchmark['summary'] ?? null) ? $benchmark['summary'] : array();
        $autopilot = Autopilot::recommend($caps, $runtime, $proof);

        return array(
            'schema' => 1,
            'yperf' => array(
                'version' => YPERF_VERSION,
                'current_profile' => sanitize_key((string) ($plan['profile'] ?? 'observe')),
                'deterministic_ceiling' => sanitize_key((string) ($autopilot['target_profile'] ?? 'observe')),
                'deterministic_ready' => !empty($autopilot['ready']),
            ),
            'wordpress' => array(
                'version' => (string) get_bloginfo('version'),
                'multisite' => is_multisite(),
                'active_plugin_count' => (int) ($caps['application']['active_plugin_count'] ?? 0),
                'woocommerce' => !empty($caps['application']['woocommerce']),
                'multilingual_present' => !empty($caps['application']['ypm']) || !empty($caps['application']['wpml']) || !empty($caps['application']['polylang']),
            ),
            'runtime' => array(
                'report_present' => $runtime !== array(),
                'version_current' => $runtime !== array() && (string) ($runtime['version'] ?? '') === YPERF_VERSION,
                'pass' => (int) ($runtimeSummary['pass'] ?? 0),
                'fail' => (int) ($runtimeSummary['fail'] ?? 0),
                'skip' => (int) ($runtimeSummary['skip'] ?? 0),
                'attention' => (int) ($runtimeSummary['attention'] ?? 0),
                'origin_cost' => array(
                    'measured_routes' => (int) ($originCost['measured_routes'] ?? 0),
                    'max_db_time_ms' => round((float) ($originCost['max_db_time_ms'] ?? 0), 2),
                    'slow_queries_100ms_total' => (int) ($originCost['slow_queries_100ms_total'] ?? 0),
                    'sql_calc_found_rows_total' => (int) ($originCost['sql_calc_found_rows_total'] ?? 0),
                ),
            ),
            'stack' => array(
                'web_server_family' => sanitize_key((string) ($caps['web_server']['family'] ?? 'unknown')),
                'php_version' => sanitize_text_field((string) ($caps['origin']['php_version'] ?? PHP_VERSION)),
                'php_sapi' => sanitize_key((string) ($caps['origin']['php_sapi'] ?? PHP_SAPI)),
                'opcache_enabled' => !empty($caps['opcode_cache']['enabled']),
                'external_object_cache' => !empty($caps['object_cache']['external_enabled']),
                'redis_extension' => !empty($caps['object_cache']['redis_extension']),
                'page_cache_proof_state' => sanitize_key((string) ($pageProof['state'] ?? 'not_proven')),
                'redis_proof_state' => sanitize_key((string) ($redisProof['state'] ?? 'unknown')),
                'origin_proof_state' => sanitize_key((string) ($originProof['state'] ?? 'unknown')),
            ),
            'host_snapshot' => array(
                'present' => $host !== array(),
                'read_only' => !empty($host['read_only']),
                'cpu_threads' => (int) ($hostInfo['cpu_threads'] ?? 0),
                'memory_total_mb' => (int) ($hostInfo['memory_total_mb'] ?? 0),
                'memory_available_mb' => (int) ($hostInfo['memory_available_mb'] ?? 0),
                'load_1' => round((float) ($hostInfo['load_1'] ?? 0), 2),
                'load_5' => round((float) ($hostInfo['load_5'] ?? 0), 2),
                'load_15' => round((float) ($hostInfo['load_15'] ?? 0), 2),
                'nginx_installed' => !empty($nginx['installed']),
                'nginx_service_active' => !empty($nginx['service_active']),
                'gzip_enabled' => !empty($nginx['gzip_enabled']),
                'brotli_enabled' => !empty($nginx['brotli_enabled']),
                'fpm_installed' => !empty($fpm['installed']),
                'fpm_site_pool_mapped' => (string) ($fpm['site_pool_name'] ?? '') !== '',
                'fpm_site_pool_pm' => sanitize_key((string) ($fpm['site_pool_pm'] ?? '')),
                'fpm_site_pool_max_children' => (int) ($fpm['site_pool_max_children'] ?? 0),
                'redis_reachable' => !empty($redis['reachable']),
                'redis_memory_usage_pct' => round((float) ($redisProof['memory_usage_pct'] ?? 0), 1),
                'redis_evictions_per_second' => round((float) ($redisProof['evictions_per_second_since_start'] ?? 0), 2),
                'redis_policy' => sanitize_text_field((string) ($redisProof['maxmemory_policy'] ?? 'unknown')),
            ),
            'benchmark' => array(
                'present' => $benchmark !== array(),
                'version_current' => $benchmark !== array() && (string) ($benchmark['version'] ?? '') === YPERF_VERSION,
                'pass' => (int) ($benchmarkSummary['pass'] ?? 0),
                'fail' => (int) ($benchmarkSummary['fail'] ?? 0),
                'skip' => (int) ($benchmarkSummary['skip'] ?? 0),
            ),
            'idle_budget' => IdleBudget::analyze(),
            'policy' => array(
                'allowed_profiles' => array('observe', 'safe', 'commerce'),
                'local_first' => true,
                'vendor_runtime_cdn_for_yperf_assets' => false,
                'host_writes_allowed' => false,
                'provider_configuration_changes_allowed' => false,
                'explicit_human_apply_required' => true,
                'runtime_acceptance_and_rollback_required' => true,
            ),
        );
    }

    /**
     * Ask Yablonsky AI for a structured performance recommendation.
     *
     * @param array<string,mixed> $caps
     * @param array<string,mixed> $runtime
     * @param array<string,mixed> $proof
     * @param array<string,mixed> $plan
     * @param array<string,mixed> $host
     * @param array<string,mixed> $benchmark
     * @return array<string,mixed>|\WP_Error
     */
    public static function recommend(array $caps, array $runtime, array $proof, array $plan, array $host, array $benchmark)
    {
        $availability = self::availability();
        if (empty($availability['ready'])) {
            return new \WP_Error('yperf_ai_unavailable', __('Connect Yablonsky AI and verify the saved connection before using AI Advisor.', 'yablonsky-performance'));
        }

        $evidence = self::evidence($caps, $runtime, $proof, $plan, $host, $benchmark);
        $privacyAudit = AIPrivacyAudit::inspect($evidence);
        if (empty($privacyAudit['pass'])) {
            return new \WP_Error('yperf_ai_privacy_gate', __('The AI request was blocked by the local YPerf privacy gate.', 'yablonsky-performance'));
        }
        $schema = array(
            'type' => 'object',
            'properties' => array(
                'target_profile' => array('type' => 'string', 'enum' => array('observe', 'safe', 'commerce')),
                'confidence' => array('type' => 'string', 'enum' => array('low', 'medium', 'high')),
                'summary' => array('type' => 'string'),
                'reasons' => array('type' => 'array', 'items' => array('type' => 'string')),
                'warnings' => array('type' => 'array', 'items' => array('type' => 'string')),
            ),
            'required' => array('target_profile', 'confidence', 'summary', 'reasons', 'warnings'),
            'additionalProperties' => false,
        );
        $prompt = 'Evaluate this privacy-minimized Yablonsky Performance evidence and choose the safest useful YPerf profile. '
            . 'Choose only observe, safe, or commerce. Do not invent settings, plugins, server facts, URLs, paths, or credentials. '
            . 'Prefer the minimum profile that is justified by evidence. Never recommend a profile above yperf.deterministic_ceiling. '
            . 'Do not recommend moving YPerf CSS, JavaScript, or fonts to a vendor-owned runtime CDN. '
            . 'Nginx, Redis, PHP-FPM, OS packages, external provider configuration, and arbitrary WordPress options are outside AI write authority. '
            . 'If Runtime Acceptance is missing, stale, or failing, prefer observe and explain the evidence needed next. '
            . "Evidence JSON:\n" . wp_json_encode($evidence, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $remote = YablonskyAIClient::compute('performance_recommendation', array(
            'instruction' => $prompt,
            'response_schema' => $schema,
            'mode' => 'structured_json',
        ));
        if (is_wp_error($remote)) {
            return $remote;
        }
        $candidate = $remote['result'] ?? $remote['output'] ?? $remote['data'] ?? $remote;
        if (is_string($candidate)) {
            $decoded = self::decodeJsonObject($candidate);
        } else {
            $decoded = is_array($candidate) ? $candidate : null;
        }
        if (!is_array($decoded)) {
            return new \WP_Error('yperf_ai_invalid_response', __('The AI provider returned a response that could not be validated as a YPerf plan.', 'yablonsky-performance'));
        }

        $recommendation = AIPlanPolicy::validate(self::normalizeRecommendation($decoded));
        $recommendation = self::guardRecommendation($recommendation, $caps, $runtime, $proof);
        $recommendation['schema'] = 1;
        $recommendation['generated_at'] = time();
        $recommendation['yperf_version'] = YPERF_VERSION;
        $recommendation['evidence_fingerprint'] = AIEvidenceBinding::fingerprint($evidence);
        $recommendation['external_call_explicit'] = true;
        $recommendation['model'] = sanitize_text_field((string) ($remote['model'] ?? ''));
        $recommendation['model_label'] = sanitize_text_field((string) ($remote['model_label'] ?? ''));
        $recommendation['human_apply_required'] = true;
        $recommendation['diff'] = AIRecommendationDiff::build($recommendation);
        update_option(self::OPTION_RECOMMENDATION, $recommendation, false);
        return $recommendation;
    }

    /**
     * Re-run only the local deterministic safety gate. This does not contact AI.
     *
     * @param array<string,mixed> $recommendation
     * @param array<string,mixed> $caps
     * @param array<string,mixed> $runtime
     * @param array<string,mixed> $proof
     * @return array<string,mixed>
     */
    public static function revalidate(array $recommendation, array $caps, array $runtime, array $proof): array
    {
        $recommendation = AIPlanPolicy::validate(self::normalizeRecommendation($recommendation));
        $recommendation = self::guardRecommendation($recommendation, $caps, $runtime, $proof);
        $recommendation['schema'] = 1;
        $recommendation['generated_at'] = (int) ($recommendation['generated_at'] ?? time());
        $recommendation['yperf_version'] = YPERF_VERSION;
        $recommendation['human_apply_required'] = true;
        $recommendation['diff'] = AIRecommendationDiff::build($recommendation);
        if (!AIEvidenceBinding::fresh($recommendation)) {
            $recommendation['ready_to_apply'] = false;
            $recommendation['warnings'][] = __('The AI recommendation expired. Build a fresh recommendation from current evidence.', 'yablonsky-performance');
        }
        return $recommendation;
    }

    /** @return array<string,mixed> */
    public static function lastRecommendation(): array
    {
        $value = get_option(self::OPTION_RECOMMENDATION, array());
        if (!is_array($value) || !AIEvidenceBinding::fresh($value)) {
            return array();
        }
        return $value;
    }

    /**
     * Ask a read-only question against the same minimized evidence envelope.
     *
     * @param array<string,mixed> $caps
     * @param array<string,mixed> $runtime
     * @param array<string,mixed> $proof
     * @param array<string,mixed> $plan
     * @param array<string,mixed> $host
     * @param array<string,mixed> $benchmark
     * @return array<string,mixed>|\WP_Error
     */
    public static function ask(string $question, array $caps, array $runtime, array $proof, array $plan, array $host, array $benchmark)
    {
        $question = trim(sanitize_textarea_field($question));
        if ($question === '') {
            return new \WP_Error('yperf_ai_empty_question', __('Enter a performance question for the AI advisor.', 'yablonsky-performance'));
        }
        $questionLength = function_exists('mb_strlen') ? mb_strlen($question, 'UTF-8') : strlen($question);
        if ($questionLength > self::MAX_QUESTION_LENGTH) {
            return new \WP_Error('yperf_ai_question_too_long', __('The AI question is too long. Keep it under 1,200 characters.', 'yablonsky-performance'));
        }
        $availability = self::availability();
        if (empty($availability['ready'])) {
            return new \WP_Error('yperf_ai_unavailable', __('Connect Yablonsky AI and verify the saved connection before using AI Advisor.', 'yablonsky-performance'));
        }

        $evidence = self::evidence($caps, $runtime, $proof, $plan, $host, $benchmark);
        $privacyAudit = AIPrivacyAudit::inspect($evidence);
        if (empty($privacyAudit['pass'])) {
            return new \WP_Error('yperf_ai_privacy_gate', __('The AI request was blocked by the local YPerf privacy gate.', 'yablonsky-performance'));
        }
        $prompt = "Answer the administrator's Yablonsky Performance question using only the supplied privacy-minimized evidence. "
            . 'Be concise and practical. Separate what is proven from what still needs evidence. '
            . 'Do not claim that you changed anything. Do not ask for passwords, API keys, nonces, SQL, filesystem paths, visitor data, or private content. '
            . 'Do not recommend vendor-owned runtime CDN delivery for YPerf CSS, JavaScript, or fonts. '
            . 'Any configuration change must stay within YPerf-owned controls and still requires explicit human Apply plus Runtime Acceptance. '
            . "Question:\n" . $question . "\nEvidence JSON:\n" . wp_json_encode($evidence, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $remote = YablonskyAIClient::compute('performance_advice', array(
            'instruction' => $prompt,
            'mode' => 'text',
        ));
        if (is_wp_error($remote)) {
            return $remote;
        }
        $answer = $remote['text'] ?? $remote['result'] ?? $remote['output'] ?? '';
        if (is_array($answer)) {
            $answer = (string) ($answer['text'] ?? $answer['content'] ?? '');
        }
        if (!is_scalar($answer) || trim((string) $answer) === '') {
            return new \WP_Error('yperf_ai_invalid_response', __('Yablonsky AI returned a response that could not be validated.', 'yablonsky-performance'));
        }

        $result = array(
            'time' => time(),
            'question' => $question,
            'answer' => trim(sanitize_textarea_field((string) $answer)),
            'evidence_fingerprint' => self::fingerprint($evidence),
            'model' => sanitize_text_field((string) ($remote['model'] ?? '')),
            'model_label' => sanitize_text_field((string) ($remote['model_label'] ?? '')),
        );
        AIAdviceHistory::record($result);
        set_transient(self::ANSWER_TRANSIENT_PREFIX . get_current_user_id(), $result, 10 * MINUTE_IN_SECONDS);
        return $result;
    }

    /** @return array<string,mixed> */
    public static function lastAnswer(): array
    {
        $value = get_transient(self::ANSWER_TRANSIENT_PREFIX . get_current_user_id());
        return is_array($value) ? $value : AIAdviceHistory::latest();
    }


    /** @return array<string,mixed>|null */
    private static function decodeJsonObject(string $value): ?array
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        // Tolerate a model wrapping otherwise-valid JSON in a Markdown fence.
        $value = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $value) ?? $value;
        $decoded = json_decode(trim($value), true);
        if (is_array($decoded)) {
            return $decoded;
        }
        $start = strpos($value, '{');
        $end = strrpos($value, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $decoded = json_decode(substr($value, $start, $end - $start + 1), true);
            return is_array($decoded) ? $decoded : null;
        }
        return null;
    }

    /** @param array<string,mixed> $raw @return array<string,mixed> */
    private static function normalizeRecommendation(array $raw): array
    {
        $reasons = array();
        foreach (array_slice((array) ($raw['reasons'] ?? array()), 0, 6) as $reason) {
            if (is_scalar($reason)) {
                $value = trim(sanitize_text_field((string) $reason));
                if ($value !== '') {
                    $reasons[] = $value;
                }
            }
        }
        $warnings = array();
        foreach (array_slice((array) ($raw['warnings'] ?? array()), 0, 6) as $warning) {
            if (is_scalar($warning)) {
                $value = trim(sanitize_text_field((string) $warning));
                if ($value !== '') {
                    $warnings[] = $value;
                }
            }
        }
        $target = sanitize_key((string) ($raw['ai_target_profile'] ?? $raw['target_profile'] ?? 'observe'));
        if (!in_array($target, array('observe', 'safe', 'commerce'), true)) {
            $target = 'observe';
        }
        $confidence = sanitize_key((string) ($raw['confidence'] ?? 'low'));
        if (!in_array($confidence, array('low', 'medium', 'high'), true)) {
            $confidence = 'low';
        }
        return array(
            'ai_target_profile' => $target,
            'target_profile' => $target,
            'confidence' => $confidence,
            'summary' => trim(sanitize_text_field((string) ($raw['summary'] ?? ''))),
            'reasons' => $reasons,
            'warnings' => $warnings,
        );
    }

    /** @param array<string,mixed> $recommendation @param array<string,mixed> $caps @param array<string,mixed> $runtime @param array<string,mixed> $proof @return array<string,mixed> */
    private static function guardRecommendation(array $recommendation, array $caps, array $runtime, array $proof): array
    {
        $local = Autopilot::recommend($caps, $runtime, $proof);
        $requested = sanitize_key((string) ($recommendation['ai_target_profile'] ?? 'observe'));
        $ceiling = !empty($local['ready']) ? sanitize_key((string) ($local['target_profile'] ?? 'safe')) : 'observe';
        $rank = array('observe' => 0, 'safe' => 1, 'commerce' => 2);
        if (!isset($rank[$requested])) {
            $requested = 'observe';
        }
        if (!isset($rank[$ceiling])) {
            $ceiling = 'observe';
        }
        $validated = $rank[$requested] <= $rank[$ceiling] ? $requested : $ceiling;
        $adjusted = $validated !== $requested;

        if ($adjusted) {
            $recommendation['warnings'][] = __('The local YPerf safety gate lowered the AI profile because current deterministic evidence does not authorize the requested level.', 'yablonsky-performance');
        }
        if (empty($local['ready'])) {
            $recommendation['warnings'][] = __('A clean current-version Runtime Acceptance is required before AI-assisted configuration can be applied.', 'yablonsky-performance');
        }

        $recommendation['target_profile'] = $validated;
        $recommendation['deterministic_ceiling'] = $ceiling;
        $recommendation['guard_adjusted'] = $adjusted;
        $recommendation['ready_to_apply'] = !empty($local['ready']);
        $recommendation['boundaries'] = (array) ($local['boundaries'] ?? array());
        return $recommendation;
    }

    /** @param array<string,mixed> $evidence */
    private static function fingerprint(array $evidence): string
    {
        return hash('sha256', (string) wp_json_encode($evidence, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
