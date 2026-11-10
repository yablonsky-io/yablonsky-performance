<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Account-scoped Yablonsky AI credential vault.
 *
 * The raw API key is never returned to admin HTML, JavaScript, reports or logs.
 * Local YPerf functionality never depends on this credential.
 */
final class YablonskyAICredentials
{
    private const OPTION = 'yperf_yablonsky_ai_credentials_v1';

    /** @return array<string,mixed> */
    public static function status(): array
    {
        $row = get_option(self::OPTION, array());
        if (!is_array($row)) {
            $row = array();
        }
        $configured = (string) ($row['ciphertext'] ?? '') !== '';
        return array(
            'configured' => $configured,
            'hint' => $configured ? sanitize_text_field((string) ($row['hint'] ?? '')) : '',
            'saved_at' => (int) ($row['saved_at'] ?? 0),
            'storage' => $configured ? sanitize_key((string) ($row['storage'] ?? 'unknown')) : 'none',
        );
    }

    public static function save(string $key): bool
    {
        $key = trim($key);
        if ($key === '' || strlen($key) < 16 || strlen($key) > 512) {
            return false;
        }
        $encrypted = self::encrypt($key);
        if ($encrypted === array()) {
            return false;
        }
        $encrypted['hint'] = strlen($key) > 8 ? substr($key, -6) : 'saved';
        $encrypted['saved_at'] = time();
        $saved = update_option(self::OPTION, $encrypted, false);
        if ($saved) {
            // A rotated/re-saved credential must never inherit proof from the previous key.
            YablonskyAIAccount::clear();
            delete_transient('yperf_yablonsky_ai_capabilities_v1');
        }
        return $saved;
    }

    public static function clear(): void
    {
        delete_option(self::OPTION);
        delete_option('yperf_yablonsky_ai_account_v1');
        delete_transient('yperf_yablonsky_ai_capabilities_v1');
    }

    public static function revealForRequest(): string
    {
        $row = get_option(self::OPTION, array());
        if (!is_array($row) || (string) ($row['ciphertext'] ?? '') === '') {
            return '';
        }
        return self::decrypt($row);
    }

    /** @return array<string,string> */
    private static function encrypt(string $plaintext): array
    {
        $key = self::siteKey();
        if (function_exists('sodium_crypto_secretbox')) {
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $cipher = sodium_crypto_secretbox($plaintext, $nonce, $key);
            return array('storage' => 'sodium', 'nonce' => base64_encode($nonce), 'ciphertext' => base64_encode($cipher));
        }
        if (function_exists('openssl_encrypt')) {
            $iv = random_bytes(12);
            $tag = '';
            $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
            if ($cipher !== false) {
                return array('storage' => 'openssl', 'nonce' => base64_encode($iv), 'tag' => base64_encode($tag), 'ciphertext' => base64_encode($cipher));
            }
        }
        return array();
    }

    /** @param array<string,mixed> $row */
    private static function decrypt(array $row): string
    {
        $storage = (string) ($row['storage'] ?? '');
        $cipher = base64_decode((string) ($row['ciphertext'] ?? ''), true);
        $nonce = base64_decode((string) ($row['nonce'] ?? ''), true);
        if ($cipher === false || $nonce === false) {
            return '';
        }
        $key = self::siteKey();
        if ($storage === 'sodium' && function_exists('sodium_crypto_secretbox_open')) {
            $plain = sodium_crypto_secretbox_open($cipher, $nonce, $key);
            return is_string($plain) ? $plain : '';
        }
        if ($storage === 'openssl' && function_exists('openssl_decrypt')) {
            $tag = base64_decode((string) ($row['tag'] ?? ''), true);
            if ($tag === false) {
                return '';
            }
            $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
            return is_string($plain) ? $plain : '';
        }
        return '';
    }

    private static function siteKey(): string
    {
        $material = defined('AUTH_KEY') ? (string) AUTH_KEY : '';
        $material .= defined('SECURE_AUTH_KEY') ? (string) SECURE_AUTH_KEY : '';
        $material .= '|' . wp_salt('auth') . '|yperf-yablonsky-ai-v1';
        return hash('sha256', $material, true);
    }
}
