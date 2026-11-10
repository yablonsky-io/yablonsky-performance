<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * YPerf's only remote AI boundary: Yablonsky AI.
 *
 * The built-in contract below is intentionally narrow: it uses the reviewed
 * Yablonsky AI User API account round-trip and Writer completion transport.
 * Administrators still own every external call and every YPerf Apply action.
 */
final class YablonskyAIClient
{
    private const SERVICE_URL = 'https://ai.yablonsky.io/';
    private const MAX_RESPONSE_BYTES = 1048576;
    private const CONTRACT_VERSION = 'user-openapi-2.8.0-core-4.1.1';

    /** @return array<string,mixed> */
    public static function availability(): array
    {
        $credential = YablonskyAICredentials::status();
        $contract = self::contract();
        $contractReady = self::contractIsValid($contract);
        $account = YablonskyAIAccount::cached();
        $accountVerified = !empty($account['verified']);
        $accountFresh = !empty($account['fresh']);
        $workspaceReady = $accountVerified && self::uuidIsValid((string) ($account['workspace_id'] ?? ''));
        $keyConfigured = !empty($credential['configured']);
        $ready = $keyConfigured && $contractReady && $accountVerified && $workspaceReady;

        if (!$keyConfigured) {
            $state = 'key_required';
        } elseif (!$contractReady) {
            $state = 'contract_review';
        } elseif (!$accountVerified) {
            $state = 'verification_required';
        } elseif (!$workspaceReady) {
            $state = 'workspace_required';
        } else {
            $state = 'ready';
        }

        return array(
            'state' => $state,
            'key_configured' => $keyConfigured,
            'contract_ready' => $contractReady,
            'account_verified' => $accountVerified,
            'account_fresh' => $accountFresh,
            'workspace_ready' => $workspaceReady,
            'ready' => $ready,
            'service' => 'Yablonsky AI',
            'service_url' => self::SERVICE_URL,
            'contract_version' => sanitize_text_field((string) ($contract['contract_version'] ?? '')),
            'account' => $account,
        );
    }

    /** @return array<string,mixed>|\WP_Error */
    public static function compute(string $operation, array $payload)
    {
        $availability = self::availability();
        if (empty($availability['key_configured'])) {
            return new \WP_Error('yperf_yablonsky_ai_key_required', __('Connect your Yablonsky AI account before using AI Advisor.', 'yablonsky-performance'));
        }
        if (empty($availability['contract_ready'])) {
            return new \WP_Error('yperf_yablonsky_ai_contract_pending', __('The reviewed Yablonsky AI User API contract is not available.', 'yablonsky-performance'));
        }
        if (empty($availability['account_verified']) || empty($availability['workspace_ready'])) {
            return new \WP_Error('yperf_yablonsky_ai_verification_required', __('Verify the saved Yablonsky AI connection before using AI Advisor.', 'yablonsky-performance'));
        }

        $instruction = trim((string) ($payload['instruction'] ?? ''));
        if ($instruction === '') {
            return new \WP_Error('yperf_yablonsky_ai_empty_instruction', __('Yablonsky AI request was blocked because the instruction was empty.', 'yablonsky-performance'));
        }
        if ((string) ($payload['mode'] ?? '') === 'structured_json') {
            $schema = is_array($payload['response_schema'] ?? null) ? $payload['response_schema'] : array();
            $instruction .= "\n\nReturn only one valid JSON object. Do not use Markdown code fences or commentary outside JSON.";
            if ($schema !== array()) {
                $instruction .= "\nRequired response schema:\n" . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }

        $account = (array) ($availability['account'] ?? array());
        $model = YablonskyAIAccount::resolvedModel($account);
        if (!empty($model['manual'])) {
            return self::conversationCompletion(sanitize_key($operation), $instruction, $account, (string) $model['id']);
        }
        return self::writerCompletion(sanitize_key($operation), $instruction, $account);
    }

    /** @return array<string,mixed>|\WP_Error */
    public static function fetchAccount()
    {
        $availability = self::availability();
        $contract = self::contract();
        $url = esc_url_raw((string) ($contract['account_url'] ?? ''));
        if (empty($availability['key_configured']) || empty($availability['contract_ready']) || $url === '' || !self::urlIsYablonsky($url)) {
            return new \WP_Error('yperf_yablonsky_ai_account_unavailable', __('Yablonsky AI account verification is not available under the reviewed service contract.', 'yablonsky-performance'));
        }
        $key = YablonskyAICredentials::revealForRequest();
        if ($key === '') {
            return new \WP_Error('yperf_yablonsky_ai_key_unreadable', __('The saved Yablonsky AI credential could not be read. Save the key again.', 'yablonsky-performance'));
        }
        $headers = self::authenticationHeaders($contract, $key);
        unset($key);
        if (is_wp_error($headers)) {
            return $headers;
        }
        $headers['Accept'] = 'application/json';
        $response = wp_safe_remote_get($url, array(
            'timeout' => 20,
            'redirection' => 0,
            'reject_unsafe_urls' => true,
            'limit_response_size' => self::MAX_RESPONSE_BYTES,
            'headers' => $headers,
        ));
        unset($headers);
        if (is_wp_error($response)) {
            return $response;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $decoded = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300 || !is_array($decoded)) {
            return new \WP_Error('yperf_yablonsky_ai_account_error', __('Yablonsky AI account verification failed. Check the API key and try again.', 'yablonsky-performance'));
        }
        $resource = isset($decoded['data']) && is_array($decoded['data']) ? $decoded['data'] : $decoded;
        if (!self::uuidIsValid((string) ($resource['id'] ?? '')) || !is_array($resource['workspace'] ?? null)) {
            return new \WP_Error('yperf_yablonsky_ai_account_shape_invalid', __('Yablonsky AI returned an account response that did not match the reviewed User API shape.', 'yablonsky-performance'));
        }
        return YablonskyAIAccount::store($resource);
    }

    /** @return array<string,mixed> */
    public static function contract(): array
    {
        $default = array(
            'contract_version' => self::CONTRACT_VERSION,
            'service_url' => self::SERVICE_URL,
            'account_url' => 'https://ai.yablonsky.io/api/account',
            'compute_url' => 'https://ai.yablonsky.io/api/ai/completions',
            'conversation_url' => 'https://ai.yablonsky.io/api/ai/conversations',
            'auth_mode' => 'raw_header',
            'auth_header' => 'X-Api-Key',
            'auth_prefix' => '',
            'workspace_header' => 'X-Workspace-Id',
            'compute_transport' => 'writer_sse_multipart',
            'manual_model_transport' => 'conversation_message_sse_multipart',
        );
        $contract = apply_filters('yperf_yablonsky_ai_public_contract', $default);
        return is_array($contract) ? $contract : array();
    }

    /** @return array<string,mixed>|\WP_Error */
    private static function writerCompletion(string $operation, string $instruction, array $account)
    {
        $contract = self::contract();
        $endpoint = esc_url_raw((string) ($contract['compute_url'] ?? ''));
        $workspaceHeader = self::sanitizeHeaderName((string) ($contract['workspace_header'] ?? ''));
        $workspaceId = sanitize_text_field((string) ($account['workspace_id'] ?? ''));
        if ($endpoint === '' || !self::urlIsYablonsky($endpoint) || $workspaceHeader === '' || !self::uuidIsValid($workspaceId)) {
            return new \WP_Error('yperf_yablonsky_ai_contract_invalid', __('The reviewed Yablonsky AI compute contract is incomplete.', 'yablonsky-performance'));
        }
        $key = YablonskyAICredentials::revealForRequest();
        if ($key === '') {
            return new \WP_Error('yperf_yablonsky_ai_key_unreadable', __('The saved Yablonsky AI credential could not be read. Save the key again.', 'yablonsky-performance'));
        }
        $headers = self::authenticationHeaders($contract, $key);
        unset($key);
        if (is_wp_error($headers)) {
            return $headers;
        }
        $headers['Accept'] = 'text/event-stream';
        $headers[$workspaceHeader] = $workspaceId;

        $query = "Yablonsky Performance operation: " . $operation . "\n\n" . $instruction;
        $multipart = self::multipartBody(array('query' => $query, 'temperature' => '0.2'));
        $headers['Content-Type'] = 'multipart/form-data; boundary=' . $multipart['boundary'];
        $response = wp_safe_remote_post($endpoint, array(
            'timeout' => 60,
            'redirection' => 0,
            'reject_unsafe_urls' => true,
            'limit_response_size' => self::MAX_RESPONSE_BYTES,
            'headers' => $headers,
            'body' => $multipart['body'],
            'data_format' => 'body',
        ));
        unset($headers, $multipart);
        if (is_wp_error($response)) {
            return $response;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $body = (string) wp_remote_retrieve_body($response);
        if ($code < 200 || $code >= 300) {
            return new \WP_Error('yperf_yablonsky_ai_remote_error', __('Yablonsky AI returned an unsuccessful response.', 'yablonsky-performance'));
        }
        $text = self::extractWriterText($body);
        if ($text === '') {
            return new \WP_Error('yperf_yablonsky_ai_empty_response', __('Yablonsky AI returned no usable Writer text.', 'yablonsky-performance'));
        }
        return array(
            'text' => $text,
            'result' => $text,
            'transport' => 'writer_sse_multipart',
            'model' => (string) (YablonskyAIAccount::resolvedModel($account)['id'] ?? 'auto'),
            'model_label' => (string) (YablonskyAIAccount::resolvedModel($account)['label'] ?? __('Workspace Writer model', 'yablonsky-performance')),
        );
    }

    /** @return array<string,mixed>|\WP_Error */
    private static function conversationCompletion(string $operation, string $instruction, array $account, string $model)
    {
        $contract = self::contract();
        $base = esc_url_raw((string) ($contract['conversation_url'] ?? ''));
        $workspaceHeader = self::sanitizeHeaderName((string) ($contract['workspace_header'] ?? ''));
        $workspaceId = sanitize_text_field((string) ($account['workspace_id'] ?? ''));
        $availableModels = array_map('strval', (array) ($account['text_models'] ?? array()));
        if ($base === '' || !self::urlIsYablonsky($base) || $workspaceHeader === '' || !self::uuidIsValid($workspaceId) || !in_array($model, $availableModels, true)) {
            return new \WP_Error('yperf_yablonsky_ai_model_invalid', __('The selected Yablonsky AI text model is not available to this verified workspace.', 'yablonsky-performance'));
        }
        $key = YablonskyAICredentials::revealForRequest();
        if ($key === '') {
            return new \WP_Error('yperf_yablonsky_ai_key_unreadable', __('The saved Yablonsky AI credential could not be read. Save the key again.', 'yablonsky-performance'));
        }
        $headers = self::authenticationHeaders($contract, $key);
        unset($key);
        if (is_wp_error($headers)) {
            return $headers;
        }
        $headers[$workspaceHeader] = $workspaceId;
        $headers['Accept'] = 'application/json';
        $created = wp_safe_remote_post($base, array(
            'timeout' => 20,
            'redirection' => 0,
            'reject_unsafe_urls' => true,
            'limit_response_size' => self::MAX_RESPONSE_BYTES,
            'headers' => $headers,
            'body' => '',
        ));
        if (is_wp_error($created)) {
            return $created;
        }
        $createdCode = (int) wp_remote_retrieve_response_code($created);
        $createdJson = json_decode((string) wp_remote_retrieve_body($created), true);
        $resource = is_array($createdJson) && isset($createdJson['data']) && is_array($createdJson['data']) ? $createdJson['data'] : $createdJson;
        $conversationId = is_array($resource) ? sanitize_text_field((string) ($resource['id'] ?? '')) : '';
        if ($createdCode < 200 || $createdCode >= 300 || !self::uuidIsValid($conversationId)) {
            return new \WP_Error('yperf_yablonsky_ai_conversation_error', __('Yablonsky AI could not open a model-specific analysis session.', 'yablonsky-performance'));
        }

        $headers['Accept'] = 'text/event-stream';
        $query = "Yablonsky Performance operation: " . $operation . "\n\n" . $instruction;
        $multipart = self::multipartBody(array('model' => $model, 'content' => $query));
        $headers['Content-Type'] = 'multipart/form-data; boundary=' . $multipart['boundary'];
        $endpoint = trailingslashit($base) . rawurlencode($conversationId) . '/messages';
        $response = wp_safe_remote_post($endpoint, array(
            'timeout' => 60,
            'redirection' => 0,
            'reject_unsafe_urls' => true,
            'limit_response_size' => self::MAX_RESPONSE_BYTES,
            'headers' => $headers,
            'body' => $multipart['body'],
            'data_format' => 'body',
        ));
        unset($headers, $multipart);
        if (is_wp_error($response)) {
            return $response;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $body = (string) wp_remote_retrieve_body($response);
        if ($code < 200 || $code >= 300) {
            return new \WP_Error('yperf_yablonsky_ai_remote_error', __('Yablonsky AI returned an unsuccessful model-specific response.', 'yablonsky-performance'));
        }
        $text = self::extractWriterText($body);
        if ($text === '') {
            return new \WP_Error('yperf_yablonsky_ai_empty_response', __('Yablonsky AI returned no usable text from the selected model.', 'yablonsky-performance'));
        }
        return array(
            'text' => $text,
            'result' => $text,
            'transport' => 'conversation_message_sse_multipart',
            'model' => $model,
            'model_label' => YablonskyAIAccount::humanizeModel($model),
        );
    }

    /** @param array<string,string> $fields @return array{boundary:string,body:string} */
    private static function multipartBody(array $fields): array
    {
        $boundary = '----YPerf' . bin2hex(random_bytes(12));
        $body = '';
        foreach ($fields as $name => $value) {
            $safeName = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $name) ?: 'field';
            $body .= '--' . $boundary . "\r\n";
            $body .= 'Content-Disposition: form-data; name="' . $safeName . '"' . "\r\n\r\n";
            $body .= (string) $value . "\r\n";
        }
        $body .= '--' . $boundary . "--\r\n";
        return array('boundary' => $boundary, 'body' => $body);
    }

    private static function extractWriterText(string $body): string
    {
        $body = str_replace("\r\n", "\n", $body);
        $trimmed = trim($body);
        if ($trimmed === '') {
            return '';
        }

        // Graceful compatibility if a deployment returns a JSON envelope instead of SSE.
        $whole = json_decode($trimmed, true);
        if (is_array($whole)) {
            return self::extractTextValue($whole);
        }

        $parts = preg_split("/\n\n+/", $body) ?: array();
        $chunks = array();
        foreach ($parts as $part) {
            $event = '';
            $dataLines = array();
            foreach (explode("\n", $part) as $line) {
                if (strpos($line, 'event:') === 0) {
                    $event = trim(substr($line, 6));
                } elseif (strpos($line, 'data:') === 0) {
                    $dataLines[] = ltrim(substr($line, 5));
                }
            }
            if ($dataLines === array() || $event === 'document' || $event === 'message-end') {
                continue;
            }
            $data = implode("\n", $dataLines);
            if ($data === '[DONE]') {
                continue;
            }
            $decoded = json_decode($data, true);
            $text = is_array($decoded) ? self::extractTextValue($decoded) : (is_string($decoded) ? $decoded : $data);
            if ($text !== '') {
                $chunks[] = $text;
            }
        }
        return trim(implode('', $chunks));
    }

    /** @param mixed $value */
    private static function extractTextValue($value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (!is_array($value)) {
            return '';
        }
        foreach (array('text', 'delta', 'content', 'output') as $key) {
            if (isset($value[$key]) && is_string($value[$key])) {
                return $value[$key];
            }
        }
        foreach ($value as $child) {
            if (is_array($child)) {
                $text = self::extractTextValue($child);
                if ($text !== '') {
                    return $text;
                }
            }
        }
        return '';
    }

    /** @param array<string,mixed> $contract @return array<string,string>|\WP_Error */
    private static function authenticationHeaders(array $contract, string $key)
    {
        $authHeader = self::sanitizeHeaderName((string) ($contract['auth_header'] ?? ''));
        $authMode = sanitize_key((string) ($contract['auth_mode'] ?? ''));
        $authPrefix = trim((string) ($contract['auth_prefix'] ?? ''));
        if ($authHeader === '' || !in_array($authMode, array('raw_header', 'prefixed_header'), true)) {
            return new \WP_Error('yperf_yablonsky_ai_contract_invalid', __('The reviewed Yablonsky AI authentication contract is incomplete.', 'yablonsky-performance'));
        }
        if ($authMode === 'prefixed_header' && $authPrefix === '') {
            return new \WP_Error('yperf_yablonsky_ai_contract_invalid', __('The reviewed Yablonsky AI authentication contract is incomplete.', 'yablonsky-performance'));
        }
        return array($authHeader => $authMode === 'raw_header' ? $key : trim($authPrefix . ' ' . $key));
    }

    /** @param array<string,mixed> $contract */
    private static function contractIsValid(array $contract): bool
    {
        $compute = esc_url_raw((string) ($contract['compute_url'] ?? ''));
        $account = esc_url_raw((string) ($contract['account_url'] ?? ''));
        $conversation = esc_url_raw((string) ($contract['conversation_url'] ?? ''));
        $version = trim((string) ($contract['contract_version'] ?? ''));
        $authHeader = self::sanitizeHeaderName((string) ($contract['auth_header'] ?? ''));
        $workspaceHeader = self::sanitizeHeaderName((string) ($contract['workspace_header'] ?? ''));
        $authMode = sanitize_key((string) ($contract['auth_mode'] ?? ''));
        $transport = sanitize_key((string) ($contract['compute_transport'] ?? ''));
        $authPrefix = trim((string) ($contract['auth_prefix'] ?? ''));

        if ($compute === '' || $account === '' || $conversation === '' || strpos($compute, 'https://') !== 0 || strpos($account, 'https://') !== 0 || strpos($conversation, 'https://') !== 0 || $version === '' || $authHeader === '' || $workspaceHeader === '') {
            return false;
        }
        if (!self::urlIsYablonsky($compute) || !self::urlIsYablonsky($account) || !self::urlIsYablonsky($conversation)) {
            return false;
        }
        if (!in_array($authMode, array('raw_header', 'prefixed_header'), true) || ($authMode === 'prefixed_header' && $authPrefix === '')) {
            return false;
        }
        return $transport === 'writer_sse_multipart';
    }

    private static function sanitizeHeaderName(string $name): string
    {
        $name = trim($name);
        if ($name === '' || preg_match('/^[A-Za-z0-9-]{1,64}$/', $name) !== 1) {
            return '';
        }
        return $name;
    }

    private static function uuidIsValid(string $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
    }

    private static function urlIsYablonsky(string $url): bool
    {
        $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
        return $host === 'ai.yablonsky.io' || ($host !== '' && substr($host, -13) === '.yablonsky.io');
    }
}
