<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class RequestPolicy
{
    /**
     * Classify a normalized request context without touching WordPress globals.
     * This pure method is intentionally reusable by future native adapters/tests.
     *
     * @param array<string,mixed> $context
     * @return array{decision:string,reason:string,cacheable:bool}
     */
    public static function classify(array $context): array
    {
        $method = strtoupper((string) ($context['method'] ?? 'GET'));

        if (!in_array($method, array('GET', 'HEAD'), true)) {
            return self::bypass('method');
        }
        if (!empty($context['authorization'])) {
            return self::bypass('authorization');
        }
        if (!empty($context['is_admin'])) {
            return self::bypass('admin');
        }
        if (!empty($context['doing_ajax'])) {
            return self::bypass('ajax');
        }
        if (!empty($context['doing_cron'])) {
            return self::bypass('cron');
        }
        if (!empty($context['is_rest'])) {
            return self::bypass('rest');
        }
        if (!empty($context['protected_endpoint'])) {
            return self::bypass('protected_endpoint');
        }
        if (!empty($context['logged_in'])) {
            return self::bypass('logged_in');
        }
        if (!empty($context['private_route'])) {
            return self::bypass('private_route');
        }
        if (!empty($context['sensitive_cookie'])) {
            return self::bypass('private_cookie');
        }

        $query = $context['query'] ?? array();
        if (is_array($query) && $query !== array()) {
            return self::bypass('query_string');
        }

        return array(
            'decision'  => 'cache',
            'reason'    => 'public_queryless_get',
            'cacheable' => true,
        );
    }

    /**
     * Build the current WordPress-visible request context.
     *
     * @return array<string,mixed>
     */
    public static function currentContext(): array
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_URI'])) : '/';
        $path = wp_parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';

        $query = array();
        $rawQuery = wp_parse_url($uri, PHP_URL_QUERY);
        if (is_string($rawQuery) && $rawQuery !== '') {
            parse_str($rawQuery, $query);
        }

        return array(
            'method'           => isset($_SERVER['REQUEST_METHOD']) ? sanitize_key(wp_unslash((string) $_SERVER['REQUEST_METHOD'])) : 'GET',
            'is_admin'         => is_admin(),
            'doing_ajax'       => function_exists('wp_doing_ajax') && wp_doing_ajax(),
            'doing_cron'       => function_exists('wp_doing_cron') && wp_doing_cron(),
            'is_rest'          => self::isRestRequest($path),
            'protected_endpoint' => self::isProtectedServicePath($path),
            'authorization'    => !empty($_SERVER['HTTP_AUTHORIZATION']) || !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) || !empty($_SERVER['PHP_AUTH_USER']),
            'logged_in'        => is_user_logged_in(),
            'private_route'    => self::isPrivateRoute($path),
            'sensitive_cookie' => self::hasSensitiveCookieNames(array_keys(wp_unslash($_COOKIE))),
            'query'            => is_array($query) ? $query : array(),
            'path'             => $path,
        );
    }

    /**
     * @return array{decision:string,reason:string,cacheable:bool}
     */
    public static function currentDecision(): array
    {
        /**
         * Filters normalized YPerf request context before policy evaluation.
         *
         * @param array<string,mixed> $context
         */
        $context = apply_filters('yablonsky_performance_request_context', self::currentContext());
        $context = apply_filters('yablonsky_performance_cache_request_context', $context);
        if (!is_array($context)) {
            $context = self::currentContext();
        }

        $decision = self::classify($context);

        /**
         * Filters the final request cacheability decision.
         * Adapters must fail closed when a filter returns an invalid value.
         *
         * @param array{decision:string,reason:string,cacheable:bool} $decision
         * @param array<string,mixed> $context
         */
        $filtered = apply_filters('yablonsky_performance_request_decision', $decision, $context);
        $filtered = apply_filters('yablonsky_performance_cache_request_decision', $filtered, $context);

        if (!is_array($filtered)
            || !isset($filtered['decision'], $filtered['reason'], $filtered['cacheable'])
            || !is_bool($filtered['cacheable'])
        ) {
            return self::bypass('invalid_policy_filter');
        }

        // Extensions may classify their own public query variants, but must
        // never promote a WordPress/private/security bypass into public cache.
        if (empty($decision['cacheable']) && (string) ($decision['reason'] ?? '') !== 'query_string'
            && !empty($filtered['cacheable'])) {
            return $decision;
        }
        return array(
            'decision'  => (string) $filtered['decision'],
            'reason'    => (string) $filtered['reason'],
            'cacheable' => $filtered['cacheable'],
        );
    }

    /** @param array<int,string> $cookieNames */
    private static function hasSensitiveCookieNames(array $cookieNames): bool
    {
        $prefixes = array(
            'wordpress_logged_in_',
            'wordpress_sec_',
            'wp_woocommerce_session_',
            'woocommerce_items_in_cart',
            'woocommerce_cart_hash',
            'comment_author_',
            'wp-postpass_',
            'edd_items_in_cart',
            'PHPSESSID',
        );

        foreach ($cookieNames as $name) {
            $name = (string) $name;
            foreach ($prefixes as $prefix) {
                if (strpos($name, $prefix) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function isRestRequest(string $path): bool
    {
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return true;
        }

        return strpos($path, '/wp-json/') !== false || substr($path, -8) === '/wp-json';
    }

    /**
     * Service/non-HTML routes are never YPerf Page Cache or frontend-delivery
     * targets. SEO/crawler semantics stay owned by YSEO/WordPress; YPerf only
     * enforces a fail-closed non-interference boundary here.
     */
    public static function isProtectedServicePath(string $path): bool
    {
        $path = rawurldecode((string) wp_parse_url($path, PHP_URL_PATH));
        $path = '/' . ltrim(strtolower($path !== '' ? $path : '/'), '/');

        if (preg_match('~\\.xml(?:\.gz)?$~', $path) === 1) {
            return true;
        }
        if ((in_array(rtrim($path, '/'), array('/robots.txt', '/xmlrpc.php', '/wp-links-opml.php'), true)
            || preg_match('~(?:^|/)robots\.txt/?$~', $path) === 1)) {
            return true;
        }
        if (preg_match('~/(?:feed|comments/feed)(?:/|$)~', $path) === 1) {
            return true;
        }
        return false;
    }

    private static function isPrivateRoute(string $path): bool
    {
        $path = '/' . ltrim(strtolower($path), '/');
        $needles = array(
            '/wp-admin',
            '/wp-login.php',
            '/wp-cron.php',
            '/wp-comments-post.php',
        );

        foreach ($needles as $needle) {
            if (strpos($path, $needle) !== false) {
                return true;
            }
        }

        if (preg_match('~/(cart|checkout|my-account)(?:/|$)~', $path) === 1) {
            return true;
        }

        if (function_exists('is_cart') && is_cart()) {
            return true;
        }
        if (function_exists('is_checkout') && is_checkout()) {
            return true;
        }
        if (function_exists('is_account_page') && is_account_page()) {
            return true;
        }

        return false;
    }

    /**
     * @return array{decision:string,reason:string,cacheable:bool}
     */
    private static function bypass(string $reason): array
    {
        return array(
            'decision'  => 'bypass',
            'reason'    => $reason,
            'cacheable' => false,
        );
    }
}
