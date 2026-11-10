<?php

declare(strict_types=1);
namespace Yablonsky\Performance;

final class YablonskyAIAccount
{
    private const OPTION = 'yperf_yablonsky_ai_account_v1';
    private const PREFERENCES_OPTION = 'yperf_yablonsky_ai_preferences_v1';
    private const TTL = 21600;

    /** @return array<string,mixed> */
    public static function cached(): array
    {
        $row = get_option(self::OPTION, array());
        if (!is_array($row)) {
            return array();
        }
        $models = array_values(array_filter(array_map('strval', (array) ($row['models'] ?? array()))));
        if ($models !== array()) {
            $writerModel = sanitize_text_field((string) ($row['writer_model'] ?? ''));
            $row['text_models'] = self::filterTextModels($models, $writerModel);
            $row['text_model_count'] = count($row['text_models']);
        }
        $row['fresh'] = (int) ($row['schema'] ?? 0) >= 4 && (int) ($row['checked_at'] ?? 0) >= time() - self::TTL;
        return $row;
    }

    public static function clear(): void
    {
        delete_option(self::OPTION);
    }

    /** @return array<string,mixed> */
    public static function preferences(): array
    {
        $value = get_option(self::PREFERENCES_OPTION, array());
        $value = is_array($value) ? $value : array();
        return array(
            'model' => sanitize_text_field((string) ($value['model'] ?? 'auto')) ?: 'auto',
        );
    }

    public static function saveModel(string $model): bool
    {
        $account = self::cached();
        $model = trim(sanitize_text_field($model));
        if ($model === '' || $model === 'auto') {
            update_option(self::PREFERENCES_OPTION, array('model' => 'auto'), false);
            return true;
        }
        $available = array_map('strval', (array) ($account['text_models'] ?? array()));
        if (!in_array($model, $available, true)) {
            return false;
        }
        update_option(self::PREFERENCES_OPTION, array('model' => $model), false);
        return true;
    }

    /** @param array<string,mixed>|null $account @return array{id:string,label:string,manual:bool} */
    public static function resolvedModel(?array $account = null): array
    {
        $account = is_array($account) ? $account : self::cached();
        $preference = self::preferences();
        $selected = (string) ($preference['model'] ?? 'auto');
        $writer = sanitize_text_field((string) ($account['writer_model'] ?? ''));
        $available = array_map('strval', (array) ($account['text_models'] ?? array()));
        if ($selected !== 'auto' && in_array($selected, $available, true)) {
            return array('id' => $selected, 'label' => self::humanizeModel($selected), 'manual' => true);
        }
        return array(
            'id' => $writer !== '' ? $writer : 'auto',
            'label' => $writer !== '' ? self::humanizeModel($writer) : __('Workspace Writer model', 'yablonsky-performance'),
            'manual' => false,
        );
    }

    public static function humanizeModel(string $id): string
    {
        $label = str_replace(array('/', '_', '-'), ' ', trim($id));
        $label = preg_replace('/\s+/', ' ', $label) ?: $label;
        $label = ucwords($label);
        $label = preg_replace('/\bGpt\b/i', 'GPT', $label) ?: $label;
        $label = preg_replace('/\bAi\b/', 'AI', $label) ?: $label;
        $label = preg_replace('/\bC4ai\b/i', 'C4AI', $label) ?: $label;
        $label = preg_replace('/\bGoogleai\b/i', 'Google AI', $label) ?: $label;
        return $label;
    }

    /** @param array<string,mixed> $remote @return array<string,mixed> */
    public static function store(array $remote): array
    {
        $workspace = is_array($remote['workspace'] ?? null) ? $remote['workspace'] : array();
        $subscription = is_array($workspace['subscription'] ?? null) ? $workspace['subscription'] : array();
        $plan = is_array($subscription['plan'] ?? null) ? $subscription['plan'] : array();
        $config = is_array($plan['config'] ?? null) ? $plan['config'] : array();
        $writer = is_array($config['writer'] ?? null) ? $config['writer'] : array();
        $writerModel = sanitize_text_field((string) ($writer['model'] ?? ''));
        $modelMap = is_array($config['models'] ?? null) ? $config['models'] : array();
        $models = array();
        foreach ($modelMap as $modelId => $enabled) {
            if (!$enabled || !is_string($modelId)) {
                continue;
            }
            $clean = sanitize_text_field($modelId);
            if ($clean !== '') {
                $models[] = $clean;
            }
        }
        $models = array_values(array_unique($models));
        sort($models, SORT_NATURAL | SORT_FLAG_CASE);
        $textModels = self::filterTextModels($models, $writerModel);

        $usageCredits = is_numeric($workspace['credit_count'] ?? null) ? (float) $workspace['credit_count'] : null;
        $planCreditsRemaining = is_numeric($subscription['credit_count'] ?? null) ? (float) $subscription['credit_count'] : null;
        $planCreditsAllocated = is_numeric($plan['credit_count'] ?? null) ? (float) $plan['credit_count'] : null;
        $availableCredits = is_numeric($workspace['total_credit_count'] ?? null) ? (float) $workspace['total_credit_count'] : null;
        if ($availableCredits === null && $usageCredits !== null && $planCreditsRemaining !== null) {
            $availableCredits = $usageCredits + $planCreditsRemaining;
        }
        $usagePercentRaw = $subscription['usage_percentage'] ?? null;
        $usagePercentage = null;
        if (is_numeric($usagePercentRaw)) {
            $usagePercentage = (float) $usagePercentRaw;
        } elseif (is_string($usagePercentRaw) && $usagePercentRaw !== '') {
            $usagePercentage = (float) rtrim(trim($usagePercentRaw), "% ");
        }
        $usageResetAt = is_numeric($subscription['renew_at'] ?? null)
            ? (int) $subscription['renew_at']
            : (is_numeric($subscription['next_billing_at'] ?? null) ? (int) $subscription['next_billing_at'] : null);
        $firstName = sanitize_text_field((string) ($remote['first_name'] ?? ''));
        $lastName = sanitize_text_field((string) ($remote['last_name'] ?? ''));
        $label = trim($firstName . ' ' . $lastName);
        if ($label === '') {
            $label = __('Connected account', 'yablonsky-performance');
        }

        $row = array(
            'schema' => 4,
            'checked_at' => time(),
            'verified' => true,
            'account_id' => sanitize_text_field((string) ($remote['id'] ?? '')),
            'account_label' => $label,
            'email_verified' => !empty($remote['is_email_verified']),
            'workspace_id' => sanitize_text_field((string) ($workspace['id'] ?? '')),
            'workspace_name' => sanitize_text_field((string) ($workspace['name'] ?? '')),
            'usage_credits' => $usageCredits,
            'available_credits' => $availableCredits,
            'plan_credits_remaining' => $planCreditsRemaining,
            'plan_credits_allocated' => $planCreditsAllocated,
            'plan_usage_percentage' => $usagePercentage,
            'usage_reset_at' => $usageResetAt,
            'plan_title' => sanitize_text_field((string) ($plan['title'] ?? '')),
            'writer_model' => $writerModel,
            'models' => $models,
            'text_models' => $textModels,
            'model_count' => count($models),
            'text_model_count' => count($textModels),
            // Backward-compatible aliases for evidence created by 1.70.0 integrations.
            'credits' => $usageCredits,
            'total_credits' => $availableCredits,
        );
        update_option(self::OPTION, $row, false);
        $row['fresh'] = true;
        return $row;
    }

    /** @param array<int,string> $models @return array<int,string> */
    private static function filterTextModels(array $models, string $writerModel): array
    {
        // The plan response is the authority for entitlement. This bounded classifier
        // only removes model ids that are unambiguously media/audio generators.
        $nonTextPrefixes = array('fal-ai/', 'luma/');
        $nonTextTokens = array(
            'dall-e', 'stable-diffusion', 'sd3', 'sd-core', 'sd-ultra', 'flux', 'recraft', 'ideogram',
            'imagen', 'seedream', 'sora', 'veo', 'kling', 'seedance', 'runway', 'hailuo', 'minimax-video',
            'minimax/video', 'wan-', '/wan', 'luma-ray', 'gpt-image', 'grok-imagine-image', 'grok-imagine-video',
            'lucy-14b', 'tts', 'eleven_', 'voice', 'whisper', 'transcrib', 'chirp-v',
        );
        $text = array();
        foreach ($models as $model) {
            $lower = strtolower($model);
            $media = false;
            foreach ($nonTextPrefixes as $prefix) {
                if (strpos($lower, $prefix) === 0) {
                    $media = true;
                    break;
                }
            }
            if ($media) {
                continue;
            }
            foreach ($nonTextTokens as $token) {
                if (strpos($lower, $token) !== false) {
                    $media = true;
                    break;
                }
            }
            if (!$media) {
                $text[] = $model;
            }
        }
        if ($writerModel !== '' && in_array($writerModel, $models, true) && !in_array($writerModel, $text, true)) {
            array_unshift($text, $writerModel);
        }
        $text = array_values(array_unique($text));
        $filtered = apply_filters('yperf_yablonsky_ai_text_models', $text, $models, $writerModel);
        return is_array($filtered) ? array_values(array_filter(array_map('strval', $filtered))) : $text;
    }
}
