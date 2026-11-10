<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Frontend delivery engine.
 *
 * The parent control remains deliberately small and reversible. Additional
 * delivery policies are stored as separate options so an update never turns a
 * new behavior on merely because the legacy emoji-overhead switch was already
 * enabled. PerformancePlan/Autopilot explicitly adopts the new policies.
 */
final class FrontendOptimizer
{
    public const OPTION_ENABLED = 'yperf_frontend_optimizer';
    public const OPTION_WOO_ROUTE_GATE = 'yperf_frontend_woo_route_gate';
    public const OPTION_HEAVY_JS_DELAY = 'yperf_frontend_heavy_js_delay';
    public const OPTION_LATE_ANALYTICS = 'yperf_frontend_late_analytics';
    public const OPTION_INLINE_CSS = 'yperf_frontend_inline_css';
    public const OPTION_REWRITTEN_CSS_RESOLUTION = 'yperf_frontend_rewritten_css_resolution';
    public const OPTION_IDLE_HEAVY_RELEASE = 'yperf_frontend_idle_heavy_release';
    public const OPTION_LCP_OBSERVED_GATE = 'yperf_frontend_lcp_observed_gate';
    public const OPTION_FINAL_STYLE_MARKERS = 'yperf_frontend_final_style_markers';
    public const OPTION_PAINT_JQUERY_DELAY = 'yperf_frontend_paint_jquery_delay';
    public const OPTION_NATIVE_DEFER = 'yperf_frontend_native_defer';
    public const OPTION_VIDEO_LITE_PUBLIC = 'yperf_frontend_video_lite_public';

    // The original field canary remains available with an explicit query selector.
    // Public adoption is a separate, default-off, human-controlled setting.
    private const YOUTUBE_LITE_CANARY_SELECTOR = 'yperf_video_lite_canary';
    // Vimeo is canary-only until a browser and accessibility field gate passes.
    private const VIMEO_LITE_CANARY_SELECTOR = 'yperf_vimeo_canary';

    private const LATE_EXECUTION_MS = 8000;
    private const NON_PAINT_HEAVY_FALLBACK_MS = 8000;
    private const INLINE_CSS_TOTAL_LIMIT = 420000;
    private const INLINE_CSS_FILE_LIMIT = 260000;
    private const EARLY_THEME_CSS_PRELOAD_DISTANCE = 32768;

    /** @var array<string,array{href:string,css:string,size:int}> */
    private static $capturedStylesheets = array();

    /** @var array<string,string> opaque anchor token => registered stylesheet handle */
    private static $styleAnchorHandles = array();

    /** @var array{inlined:int,bytes:int,anchor_hits:int,tail_hits:int,theme_hits:int,local_hits:int,remaining_stylesheets:int} */
    private static $cssInlineProof = array('inlined' => 0, 'bytes' => 0, 'anchor_hits' => 0, 'tail_hits' => 0, 'theme_hits' => 0, 'local_hits' => 0, 'remaining_stylesheets' => 0);

    /** @var array<string,bool> */
    private static $nativeDeferIntended = array();

    /** @var array<int,string> */
    private const INTERACTIVE_WOO_BLOCKS = array(
        'woocommerce/cart',
        'woocommerce/checkout',
        'woocommerce/mini-cart',
        'woocommerce/product-collection',
        'woocommerce/product-query',
        'woocommerce/all-products',
        'woocommerce/attribute-filter',
        'woocommerce/active-filters',
        'woocommerce/price-filter',
        'woocommerce/stock-filter',
        'woocommerce/rating-filter',
        'woocommerce/catalog-sorting',
        'woocommerce/customer-account',
    );

    /** @var array<int,string> */
    private const HEAVY_HANDLE_PATTERNS = array(
        'wp-components',
        'wp-compose',
        'wp-data',
        'wp-data-controls',
        'wp-rich-text',
        'wp-notices',
        'wp-primitives',
        'wp-private-apis',
        'wp-redux-routine',
        'wp-date',
        'wp-api-fetch',
        'react',
        'react-dom',
        'react-jsx-runtime',
        'lodash',
        'moment',
        'wc-blocks',
        'wc-settings',
    );

    public static function register(): void
    {
        if (!self::enabled()) {
            return;
        }

        add_action('init', array(__CLASS__, 'reduceOverhead'), 100);
        add_filter('emoji_svg_url', '__return_false');
        // Capture the original registered stylesheet source before security/path-hiding
        // filters can rewrite the public URL. The late tag marker below still binds
        // the final public tag to its WordPress handle; the full-document pass remains
        // the only place allowed to inline CSS after CSP and byte-cap checks.
        add_filter('style_loader_src', array(__CLASS__, 'captureStylesheetSource'), -9999, 2);
        // Register the final tag marker only after normal enqueue callbacks have
        // completed. This puts YPerf behind path-hiding filters that were already
        // registered by other plugins, even when they use the same max priority.
        // Canary assets are owned by WordPress and loaded only on explicitly selected public routes.
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueueYoutubeLiteAssets'), 1);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'applyNativeDeferredStrategies'), PHP_INT_MAX - 100);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'registerLateStyleMarker'), PHP_INT_MAX);
        add_action('template_redirect', array(__CLASS__, 'beginHtmlTransform'), 0);
        add_action('template_redirect', array(__CLASS__, 'emitDiagnosticHeader'), PHP_INT_MAX);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'gateWooTransactionalAssets'), PHP_INT_MAX);
    }

    public static function enabled(): bool
    {
        return get_option(self::OPTION_ENABLED, '0') === '1';
    }

    public static function wooRouteGateEnabled(): bool
    {
        return get_option(self::OPTION_WOO_ROUTE_GATE, '0') === '1';
    }

    public static function heavyJsDelayEnabled(): bool
    {
        return get_option(self::OPTION_HEAVY_JS_DELAY, '0') === '1';
    }

    public static function lateAnalyticsEnabled(): bool
    {
        return get_option(self::OPTION_LATE_ANALYTICS, '0') === '1';
    }

    public static function inlineCssEnabled(): bool
    {
        return get_option(self::OPTION_INLINE_CSS, '0') === '1';
    }

    public static function rewrittenCssResolutionEnabled(): bool
    {
        return get_option(self::OPTION_REWRITTEN_CSS_RESOLUTION, '0') === '1';
    }

    public static function idleHeavyReleaseEnabled(): bool
    {
        return get_option(self::OPTION_IDLE_HEAVY_RELEASE, '0') === '1';
    }

    public static function lcpObservedGateEnabled(): bool
    {
        return get_option(self::OPTION_LCP_OBSERVED_GATE, '0') === '1';
    }

    public static function finalStyleMarkersEnabled(): bool
    {
        return get_option(self::OPTION_FINAL_STYLE_MARKERS, '0') === '1';
    }

    public static function paintJqueryDelayEnabled(): bool
    {
        return get_option(self::OPTION_PAINT_JQUERY_DELAY, '0') === '1';
    }

    public static function nativeDeferEnabled(): bool
    {
        // New release behavior is opt-in through Optimize/applyProfile so merely
        // upgrading cannot change script execution order on an already-live site.
        return get_option(self::OPTION_NATIVE_DEFER, '0') === '1';
    }

    /**
     * Keep experimental native-defer execution off ordinary visitor traffic until
     * the exact dependency-safe strategy is proven by an explicit YPerf canary.
     *
     * A normal Runtime Acceptance frontend probe intentionally follows the same
     * execution path as production traffic. The separate native-defer probe is
     * opt-in so baseline and experiment can be compared without changing any
     * other request property.
     */
    private static function nativeDeferExecutionAllowed(): bool
    {
        return self::nativeDeferEnabled()
            && (self::criticalPathCanaryMode() === 'native-defer' || self::isNativeDeferProbe());
    }

    /** @return string control|native-defer|empty */
    private static function criticalPathCanaryMode(): string
    {
        // Read-only diagnostic selectors. No request value changes stored state.
        $value = filter_input(INPUT_GET, 'yperf_critical_path_canary', FILTER_UNSAFE_RAW);
        if (is_string($value)) {
            $mode = sanitize_key($value);
            if (in_array($mode, array('control', 'native-defer'), true)) {
                return $mode;
            }
        }

        // Backward compatibility for the 2.46.1/2.46.2 field-canary URL.
        $legacy = filter_input(INPUT_GET, 'yperf_native_defer_canary', FILTER_UNSAFE_RAW);
        if (is_string($legacy) && sanitize_text_field($legacy) === '1') {
            return 'native-defer';
        }
        return '';
    }

    private static function isCriticalPathCanary(): bool
    {
        return self::criticalPathCanaryMode() !== '';
    }

    private static function isNativeDeferProbe(): bool
    {
        if (!self::isYperfProbe()) {
            return false;
        }
        $value = filter_input(INPUT_GET, 'yperf_runtime_probe', FILTER_UNSAFE_RAW);
        return is_string($value) && sanitize_key($value) === 'native-defer';
    }

    public static function publicVideoLiteEnabled(): bool
    {
        // Installation and Optimize Now never activate this policy implicitly.
        return get_option(self::OPTION_VIDEO_LITE_PUBLIC, '0') === '1';
    }

    /**
     * A matched GET canary (?yperf_video_lite_canary=1 / =0) remains available
     * even after public adoption, without mutating the saved policy.
     * A WordPress visitor with commerce-session state stays on normal embeds.
     */
    private static function youtubeLiteEnabledForRequest(): bool
    {
        if (!self::shouldOptimizePublicRequest()) {
            return false;
        }
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper(sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_METHOD']))) : 'GET';
        if ($method !== 'GET' || self::hasPrivateWooCookie()) {
            return false;
        }
        $value = filter_input(INPUT_GET, self::YOUTUBE_LITE_CANARY_SELECTOR, FILTER_UNSAFE_RAW);
        if (is_string($value) && ($value === '0' || $value === '1')) {
            return $value === '1';
        }
        return self::publicVideoLiteEnabled();
    }

    /**
     * Opt-in, GET-only Vimeo compatibility canary. It has no persistent option,
     * cannot be activated by Optimize Now and cannot affect a normal public URL.
     */
    private static function vimeoCanaryEnabledForRequest(): bool
    {
        if (!self::shouldOptimizePublicRequest()) {
            return false;
        }
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper(sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_METHOD']))) : 'GET';
        if ($method !== 'GET' || self::hasPrivateWooCookie()) {
            return false;
        }
        return filter_input(INPUT_GET, self::VIMEO_LITE_CANARY_SELECTOR, FILTER_UNSAFE_RAW) === '1';
    }

    private static function videoLiteModeForRequest(): string
    {
        if (!self::youtubeLiteEnabledForRequest()) {
            return 'off';
        }
        $value = filter_input(INPUT_GET, self::YOUTUBE_LITE_CANARY_SELECTOR, FILTER_UNSAFE_RAW);
        return is_string($value) && $value === '1' ? 'canary' : 'public';
    }

    private static function hasPrivateWooCookie(): bool
    {
        foreach (array_keys($_COOKIE) as $name) {
            if (!is_string($name)) {
                continue;
            }
            if ($name === 'woocommerce_cart_hash'
                || $name === 'woocommerce_items_in_cart'
                || strpos($name, 'wp_woocommerce_session_') === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * WordPress-owned Video Lite assets for a field canary or adopted public
     * routes. Normal WordPress enqueue preserves script/style ownership.
     */
    public static function enqueueYoutubeLiteAssets(): void
    {
        if (!self::youtubeLiteEnabledForRequest() && !self::vimeoCanaryEnabledForRequest()) {
            return;
        }
        wp_enqueue_style(
            'yperf-video-lite',
            plugins_url('assets/yperf-video-lite.css', YPERF_FILE),
            array(),
            YPERF_VERSION
        );
        wp_enqueue_script(
            'yperf-video-lite',
            plugins_url('assets/yperf-video-lite.js', YPERF_FILE),
            array(),
            YPERF_VERSION,
            true
        );
        if (function_exists('wp_localize_script')) {
            wp_localize_script('yperf-video-lite', 'YPerfVideoLiteI18n', array(
                'previewUnavailable' => __('Video preview unavailable', 'yablonsky-performance'),
                'openOnYoutube' => __('Open on YouTube', 'yablonsky-performance'),
                'openOnVimeo' => __('Open on Vimeo', 'yablonsky-performance'),
                'video' => __('Video', 'yablonsky-performance'),
                'vimeoVideo' => __('Vimeo video', 'yablonsky-performance'),
                'youtubeVideo' => __('YouTube video', 'yablonsky-performance'),
            ));
        }
        if (function_exists('wp_script_add_data')) {
            wp_script_add_data('yperf-video-lite', 'strategy', 'defer');
        }
    }

    public static function deliveryEngineEnabled(): bool
    {
        return self::enabled()
            && self::wooRouteGateEnabled()
            && self::heavyJsDelayEnabled()
            && self::lateAnalyticsEnabled()
            && self::inlineCssEnabled()
            && self::lcpObservedGateEnabled()
            && self::finalStyleMarkersEnabled()
            && self::paintJqueryDelayEnabled();
    }

    /** @return array<string,string> */
    public static function settingsSnapshot(): array
    {
        return array(
            self::OPTION_ENABLED => self::enabled() ? '1' : '0',
            self::OPTION_WOO_ROUTE_GATE => self::wooRouteGateEnabled() ? '1' : '0',
            self::OPTION_HEAVY_JS_DELAY => self::heavyJsDelayEnabled() ? '1' : '0',
            self::OPTION_LATE_ANALYTICS => self::lateAnalyticsEnabled() ? '1' : '0',
            self::OPTION_INLINE_CSS => self::inlineCssEnabled() ? '1' : '0',
            self::OPTION_REWRITTEN_CSS_RESOLUTION => self::rewrittenCssResolutionEnabled() ? '1' : '0',
            self::OPTION_IDLE_HEAVY_RELEASE => self::idleHeavyReleaseEnabled() ? '1' : '0',
            self::OPTION_LCP_OBSERVED_GATE => self::lcpObservedGateEnabled() ? '1' : '0',
            self::OPTION_FINAL_STYLE_MARKERS => self::finalStyleMarkersEnabled() ? '1' : '0',
            self::OPTION_PAINT_JQUERY_DELAY => self::paintJqueryDelayEnabled() ? '1' : '0',
            self::OPTION_NATIVE_DEFER => self::nativeDeferEnabled() ? '1' : '0',
            self::OPTION_VIDEO_LITE_PUBLIC => self::publicVideoLiteEnabled() ? '1' : '0',
        );
    }

    /** @param array<string,string> $snapshot */
    public static function restoreSettings(array $snapshot): void
    {
        foreach (array(self::OPTION_ENABLED, self::OPTION_WOO_ROUTE_GATE, self::OPTION_HEAVY_JS_DELAY, self::OPTION_LATE_ANALYTICS, self::OPTION_INLINE_CSS, self::OPTION_REWRITTEN_CSS_RESOLUTION, self::OPTION_IDLE_HEAVY_RELEASE, self::OPTION_LCP_OBSERVED_GATE, self::OPTION_FINAL_STYLE_MARKERS, self::OPTION_PAINT_JQUERY_DELAY, self::OPTION_NATIVE_DEFER, self::OPTION_VIDEO_LITE_PUBLIC) as $key) {
            update_option($key, (string) ($snapshot[$key] ?? '0') === '1' ? '1' : '0', false);
        }
    }

    public static function applyProfile(string $profile): void
    {
        $active = in_array($profile, array('safe', 'commerce'), true);
        update_option(self::OPTION_ENABLED, $active ? '1' : '0', false);
        update_option(self::OPTION_WOO_ROUTE_GATE, $active ? '1' : '0', false);
        update_option(self::OPTION_HEAVY_JS_DELAY, $active ? '1' : '0', false);
        update_option(self::OPTION_LATE_ANALYTICS, $active ? '1' : '0', false);
        update_option(self::OPTION_INLINE_CSS, $active ? '1' : '0', false);
        update_option(self::OPTION_REWRITTEN_CSS_RESOLUTION, $active ? '1' : '0', false);
        update_option(self::OPTION_IDLE_HEAVY_RELEASE, $active ? '1' : '0', false);
        update_option(self::OPTION_LCP_OBSERVED_GATE, $active ? '1' : '0', false);
        update_option(self::OPTION_FINAL_STYLE_MARKERS, $active ? '1' : '0', false);
        update_option(self::OPTION_PAINT_JQUERY_DELAY, $active ? '1' : '0', false);
        update_option(self::OPTION_NATIVE_DEFER, $active ? '1' : '0', false);
        // Turning the parent engine OFF retires public Video Lite adoption.
        // A subsequent Optimize Now cannot silently re-enable it.
        if (!$active) {
            update_option(self::OPTION_VIDEO_LITE_PUBLIC, '0', false);
        }
    }

    public static function emitDiagnosticHeader(): void
    {
        if (headers_sent() || (!self::isYperfProbe() && !self::isCriticalPathCanary())) {
            return;
        }
        header('X-YPerf-Frontend: active');
        header('X-YPerf-Frontend-Policy: ' . self::policyHeaderValue());
        $canary = self::criticalPathCanaryMode();
        if ($canary !== '') {
            header('X-YPerf-Critical-Path-Canary: ' . $canary);
        }
    }

    public static function registerLateStyleMarker(): void
    {
        if (has_filter('style_loader_tag', array(__CLASS__, 'markStylesheetOrigin')) === false) {
            add_filter('style_loader_tag', array(__CLASS__, 'markStylesheetOrigin'), PHP_INT_MAX, 4);
        }
    }

    /**
     * Ask WordPress Core to defer only dependency-safe, same-origin header scripts.
     *
     * This is deliberately an intention layer, not an HTML-regex scheduler. Core
     * owns the final dependency decision on WordPress 6.3+. YPerf additionally
     * fails closed for scripts carrying inline before/after/data payloads and for
     * jQuery/security/consent bootstraps because arbitrary theme code may expect
     * those globals during initial parsing.
     */
    public static function applyNativeDeferredStrategies(): void
    {
        self::$nativeDeferIntended = array();
        if (!self::nativeDeferExecutionAllowed() || !self::shouldOptimizePublicRequest() || !function_exists('wp_scripts') || !function_exists('wp_script_add_data')) {
            return;
        }

        $wpVersion = isset($GLOBALS['wp_version']) ? (string) $GLOBALS['wp_version'] : '';
        if ($wpVersion === '' && function_exists('get_bloginfo')) {
            $wpVersion = (string) get_bloginfo('version');
        }
        if ($wpVersion === '' || version_compare($wpVersion, '6.3', '<')) {
            return;
        }

        $scripts = wp_scripts();
        if (!is_object($scripts) || !isset($scripts->queue, $scripts->registered) || !is_array($scripts->queue) || !is_array($scripts->registered)) {
            return;
        }

        // Build the queue-reachable dependency set so WordPress can evaluate an
        // internally consistent tree rather than a hand-picked theme allow-list.
        $reachable = array();
        $cursor = array();
        foreach ($scripts->queue as $handle) {
            $handle = (string) $handle;
            if ($handle === '') {
                continue;
            }
            $reachable[strtolower($handle)] = $handle;
            $cursor[] = $handle;
        }
        while ($cursor !== array()) {
            $handle = array_shift($cursor);
            $registered = $scripts->registered[$handle] ?? null;
            $deps = is_object($registered) && isset($registered->deps) && is_array($registered->deps) ? $registered->deps : array();
            foreach ($deps as $dependency) {
                $dependency = (string) $dependency;
                $normalizedDependency = strtolower($dependency);
                if ($dependency === '' || isset($reachable[$normalizedDependency])) {
                    continue;
                }
                $reachable[$normalizedDependency] = $dependency;
                $cursor[] = $dependency;
            }
        }

        foreach ($reachable as $normalized => $handle) {
            $registered = $scripts->registered[$handle] ?? null;
            if (!is_object($registered)) {
                continue;
            }
            $src = isset($registered->src) ? (string) $registered->src : '';
            if ($src === '') {
                continue; // alias handle: Core rejects loading strategies here.
            }

            $group = isset($scripts->groups[$handle]) ? (int) $scripts->groups[$handle] : 0;
            $inFooter = isset($scripts->in_footer) && is_array($scripts->in_footer) && in_array($handle, $scripts->in_footer, true);
            if ($group > 0 || $inFooter) {
                continue; // already outside the parser-blocking head path.
            }

            $extra = isset($registered->extra) && is_array($registered->extra) ? $registered->extra : array();
            if (!empty($extra['conditional']) || !empty($extra['before']) || !empty($extra['after']) || !empty($extra['data'])) {
                continue;
            }

            $existingStrategy = method_exists($scripts, 'get_data') ? strtolower((string) $scripts->get_data($handle, 'strategy')) : '';
            if ($existingStrategy !== '') {
                continue; // preserve author/plugin ownership of an existing strategy.
            }

            $normalizedSrc = self::normalizeRegisteredStylesheetUrl($src);
            if ($normalizedSrc === '' || !self::isSameOriginHttpUrl($normalizedSrc) || self::nativeDeferExcludedHandle((string) $normalized, $normalizedSrc)) {
                continue;
            }

            if (wp_script_add_data($handle, 'strategy', 'defer')) {
                self::$nativeDeferIntended[$normalized] = true;
            }
        }

        if (self::$nativeDeferIntended !== array() && has_filter('script_loader_tag', array(__CLASS__, 'markNativeDeferredIntent')) === false) {
            add_filter('script_loader_tag', array(__CLASS__, 'markNativeDeferredIntent'), PHP_INT_MAX, 3);
        }
    }

    public static function markNativeDeferredIntent(string $tag, string $handle, string $src): string
    {
        $normalized = strtolower($handle);
        if (!isset(self::$nativeDeferIntended[$normalized])) {
            return $tag;
        }

        // WordPress Core remains the primary strategy resolver. If Core elects to
        // keep an intended script blocking, the canary/probe may apply a tightly
        // bounded fallback only to an enqueue-graph leaf with no inline payload.
        // Ordinary visitor traffic never receives this fallback; only the explicit native-defer canary/probe may use it.
        $hasAsync = preg_match('/\sasync(?:\s|=|>)/i', ' ' . $tag) === 1;
        $hasDefer = preg_match('/\sdefer(?:\s|=|>)/i', ' ' . $tag) === 1;
        if (!$hasAsync && !$hasDefer && self::nativeDeferLeafFallbackSafe($handle)) {
            $tag = self::setOpeningTagBooleanAttribute($tag, 'defer');
            $tag = self::setOpeningTagAttribute($tag, 'data-yperf-native-defer-fallback', 'leaf');
        }

        if (self::isYperfProbe() || self::isCriticalPathCanary()) {
            $tag = self::setOpeningTagAttribute($tag, 'data-yperf-native-defer-intent', '1');
        }
        return $tag;
    }

    private static function nativeDeferLeafFallbackSafe(string $handle): bool
    {
        if (!self::nativeDeferExecutionAllowed() || !function_exists('wp_scripts')) {
            return false;
        }
        $scripts = wp_scripts();
        if (!is_object($scripts) || !isset($scripts->registered[$handle]) || !is_object($scripts->registered[$handle])) {
            return false;
        }
        $registered = $scripts->registered[$handle];
        $extra = isset($registered->extra) && is_array($registered->extra) ? $registered->extra : array();
        if (!empty($extra['conditional']) || !empty($extra['before']) || !empty($extra['after']) || !empty($extra['data'])) {
            return false;
        }

        // A leaf has no enqueued registered dependents. Deferring it cannot move a
        // WordPress-managed dependent ahead of the global it provides. Dependencies
        // of the leaf may remain blocking and therefore execute before it.
        $registeredMap = isset($scripts->registered) && is_array($scripts->registered) ? $scripts->registered : array();
        foreach ($registeredMap as $dependentHandle => $dependent) {
            if (!is_object($dependent) || !isset($dependent->deps) || !is_array($dependent->deps) || !in_array($handle, $dependent->deps, true)) {
                continue;
            }
            if (method_exists($scripts, 'query') && $scripts->query((string) $dependentHandle, 'enqueued')) {
                return false;
            }
        }
        return true;
    }

    private static function setOpeningTagBooleanAttribute(string $tag, string $name): string
    {
        $openEnd = strpos($tag, '>');
        if ($openEnd === false) {
            return $tag;
        }
        $opening = substr($tag, 0, $openEnd + 1);
        if (preg_match('/\s' . preg_quote($name, '/') . '(?:\s|=|>)/i', ' ' . $opening) === 1) {
            return $tag;
        }
        return substr($tag, 0, $openEnd) . ' ' . $name . substr($tag, $openEnd);
    }

    private static function setOpeningTagAttribute(string $tag, string $name, string $value): string
    {
        $openEnd = strpos($tag, '>');
        if ($openEnd === false) {
            return $tag;
        }
        $opening = substr($tag, 0, $openEnd + 1);
        $pattern = '/\s+' . preg_quote($name, '/') . '\s*=\s*(["\']).*?\1/i';
        if (preg_match($pattern, $opening) === 1) {
            $opening = (string) preg_replace($pattern, ' ' . $name . '="' . esc_attr($value) . '"', $opening, 1);
            return $opening . substr($tag, $openEnd + 1);
        }
        return substr($tag, 0, $openEnd) . ' ' . $name . '="' . esc_attr($value) . '"' . substr($tag, $openEnd);
    }

    private static function nativeDeferExcludedHandle(string $handle, string $src): bool
    {
        $handle = strtolower($handle);
        if (in_array($handle, array('jquery', 'jquery-core', 'jquery-migrate'), true) || strpos($handle, 'jquery') !== false) {
            return true;
        }
        $path = strtolower((string) wp_parse_url(html_entity_decode($src, ENT_QUOTES), PHP_URL_PATH));
        $haystack = $handle . ' ' . $path;
        foreach (array('consent', 'cookie', 'captcha', 'recaptcha', 'turnstile', 'challenge', 'security', 'antibot', 'bot-protection') as $needle) {
            if (strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    private static function appendNativeDeferProof(string $html): string
    {
        if (!self::isYperfProbe() && !self::isCriticalPathCanary()) {
            return $html;
        }
        $headEnd = stripos($html, '</head>');
        $head = $headEnd === false ? $html : substr($html, 0, $headEnd);
        $rendered = 0;
        $blockedIntended = 0;
        $headBlocking = 0;
        $headExternal = 0;
        if (preg_match_all('/<script\b[^>]*>.*?<\/script>/is', $head, $matches) !== false) {
            foreach ((array) ($matches[0] ?? array()) as $whole) {
                $whole = (string) $whole;
                $src = self::attribute($whole, 'src');
                $type = strtolower(self::attribute($whole, 'type'));
                if ($src === '' || self::isNonJavascriptType($type) || stripos($whole, 'data-yperf-delayed="1"') !== false) {
                    continue;
                }
                ++$headExternal;
                $hasAsync = preg_match('/\sasync(?:\s|=|>)/i', ' ' . $whole) === 1;
                $hasDefer = preg_match('/\sdefer(?:\s|=|>)/i', ' ' . $whole) === 1;
                $intended = stripos($whole, 'data-yperf-native-defer-intent="1"') !== false;
                if ($intended && $hasDefer) {
                    ++$rendered;
                } elseif ($intended) {
                    ++$blockedIntended;
                }
                if (!$hasAsync && !$hasDefer) {
                    ++$headBlocking;
                }
            }
        }
        $proof = sprintf(
            '<!--yperf-native-defer-proof:enabled=%d;intended=%d;rendered=%d;blocked_intended=%d;head_external=%d;head_blocking=%d-->',
            self::nativeDeferExecutionAllowed() ? 1 : 0,
            count(self::$nativeDeferIntended),
            $rendered,
            $blockedIntended,
            $headExternal,
            $headBlocking
        );
        $html = preg_replace('/<!--yperf-native-defer-proof:enabled=[01];intended=\d+;rendered=\d+;blocked_intended=\d+;head_external=\d+;head_blocking=\d+-->/', '', $html) ?? $html;
        if (preg_match('/<head\b[^>]*>/i', $html, $headMatch, PREG_OFFSET_CAPTURE) === 1) {
            $headTag = (string) ($headMatch[0][0] ?? '');
            $headOffset = (int) ($headMatch[0][1] ?? 0) + strlen($headTag);
            return substr($html, 0, $headOffset) . $proof . substr($html, $headOffset);
        }
        return $proof . $html;
    }

    /**
     * Capture trusted local stylesheet bytes while WordPress still exposes the
     * original registered src. This runs before path-hiding URL filters and does
     * not change the src. The later full-document pass still decides whether the
     * stylesheet may be inlined.
     */
    public static function captureStylesheetSource(string $src, string $handle): string
    {
        if (!self::inlineCssEnabled() || !self::rewrittenCssResolutionEnabled() || !self::shouldOptimizePublicRequest()) {
            return $src;
        }

        $safeHandle = sanitize_key($handle);
        if ($safeHandle === '' || isset(self::$capturedStylesheets[$safeHandle])) {
            return $src;
        }

        $normalized = self::normalizeRegisteredStylesheetUrl($src);
        if ($normalized === '' || !self::isSameOriginHttpUrl($normalized)) {
            return $src;
        }

        $path = self::localStylesheetPath($normalized, '', $safeHandle);
        if ($path === '' || !is_readable($path)) {
            return $src;
        }

        $size = @filesize($path);
        if (!is_int($size) || $size < 1 || $size > self::INLINE_CSS_FILE_LIMIT) {
            return $src;
        }

        $css = @file_get_contents($path);
        if (!is_string($css) || $css === '' || stripos($css, '@import') !== false) {
            return $src;
        }

        self::$capturedStylesheets[$safeHandle] = array(
            'href' => $normalized,
            'css' => $css,
            'size' => $size,
        );
        return $src;
    }

    /**
     * Preserve stylesheet ownership after security/path-hiding filters have
     * produced the final public tag. The WordPress handle is emitted only as a
     * temporary non-rendering marker; the full-document output pass consumes it
     * and still owns CSP, local realpath, byte-cap and @import safety decisions.
     */
    public static function markStylesheetOrigin(string $html, string $handle, string $href, string $media): string
    {
        if (!self::inlineCssEnabled() || !self::rewrittenCssResolutionEnabled() || !self::shouldOptimizePublicRequest()) {
            return $html;
        }

        $normalized = self::normalizeRegisteredStylesheetUrl($href);
        if ($normalized === '' || !self::isSameOriginHttpUrl($normalized)) {
            return $html;
        }

        $safeHandle = sanitize_key($handle);

        // Capture trusted local CSS bytes while WordPress still exposes the original
        // registered href/handle. Security/path-hiding layers may rewrite the public
        // URL later, but the full-document pass can still match by the preserved
        // WordPress link id/handle without guessing a filesystem path from the hidden URL.
        if ($safeHandle !== '' && !isset(self::$capturedStylesheets[$safeHandle])) {
            $path = self::localStylesheetPath($normalized, '', $safeHandle);
            if ($path !== '' && is_readable($path)) {
                $size = @filesize($path);
                if (is_int($size) && $size > 0 && $size <= self::INLINE_CSS_FILE_LIMIT) {
                    $css = @file_get_contents($path);
                    if (is_string($css) && $css !== '' && stripos($css, '@import') === false) {
                        self::$capturedStylesheets[$safeHandle] = array(
                            'href' => $normalized,
                            'css' => $css,
                            'size' => $size,
                        );
                    }
                }
            }
        }

        $html = self::setHtmlAttribute($html, 'data-yperf-style-handle', $safeHandle);
        $html = self::setHtmlAttribute($html, 'data-yperf-original-href', $normalized);

        /*
         * Some security/path-hiding plugins rebuild the <link> tag after the
         * registered WordPress href has been generated and can discard custom
         * data-* attributes. 2.14 adds a last-stage, non-rendering ownership
         * marker beside the final tag. The output-buffer pass consumes and
         * removes this marker only after it has seen the complete document,
         * so CSP/meta policy, filesystem ownership and byte caps still govern
         * whether any CSS is actually inlined.
         */
        if (self::finalStyleMarkersEnabled() && $safeHandle !== '') {
            $html .= '<!--yperf-style-handle:' . $safeHandle . '-->';
            $anchor = self::styleAnchorToken($safeHandle, $normalized);
            if ($anchor !== '') {
                self::$styleAnchorHandles[$anchor] = $safeHandle;
                $html .= '<meta data-yperf-style-anchor="' . esc_attr($anchor) . '">';
            }
        }
        return $html;
    }

    public static function beginHtmlTransform(): void
    {
        if (!self::shouldOptimizePublicRequest()) {
            return;
        }
        ob_start(array(__CLASS__, 'transformHtml'));
    }

    /**
     * Delay only heavyweight/non-critical script families. Theme navigation,
     * jQuery, cookie-consent bootstrap and ordinary application scripts remain
     * untouched. Delayed scripts replay in document order after first user
     * intent or after a bounded late-execution timeout.
     */
    public static function transformHtml(string $html): string
    {
        if ($html === '' || stripos($html, '<html') === false) {
            return $html;
        }

        // Collapse eligible same-origin render-blocking stylesheets into the
        // cached HTML so first paint is not held behind a fan-out of CSS
        // requests. The transform preserves CSS order and media semantics,
        // rewrites relative url() references, and fails closed when ownership
        // or CSP safety is uncertain.
        $html = self::inlineEligibleStylesheets($html);
        $html = self::preloadLateDiscoveredCurrentThemeStylesheet($html);

        // Final-document adaptive discovery promotes at most one high-confidence
        // same-origin first-paint image. It uses generic markup/position evidence,
        // never a theme/site allow-list, and fails closed when confidence is low.
        $html = self::prioritizeFirstPaintHero($html);
        $lcpCandidate = stripos($html, 'data-yperf-lcp-priority="1"') !== false;
        // Generic LCP discovery may safely raise network priority, but only the
        // narrower source-proven paint-first route is allowed to activate the
        // LCP-observed heavy-script gate. This prevents theme/widget JS from
        // becoming circularly responsible for making the candidate visible.
        $paintAware = self::paintFirstRoute() && $lcpCandidate;
        if (!headers_sent() && self::isYperfProbe()) {
            header('X-YPerf-Frontend-Policy: ' . self::policyHeaderValue($lcpCandidate), true);
        }
        $html = self::optimizePostHeroWooThumbnails($html);
        // A controlled Video Lite policy reduces third-party iframe startup.
        // Private Woo/session routes are always excluded; public adoption remains
        // OFF until deliberately enabled by the administrator.
        if (self::youtubeLiteEnabledForRequest()) {
            $html = self::applyYoutubeLite($html);
        }
        // Vimeo is never rewritten on regular traffic in 2.51.0. An explicit,
        // anonymous GET canary is required for provider compatibility proof.
        if (self::vimeoCanaryEnabledForRequest()) {
            $html = self::applyVimeoLiteCanary($html);
        }


        $heavy = self::effectiveHeavyJsDelay();
        $analytics = self::lateAnalyticsEnabled();
        if ((!$heavy && !$analytics) || stripos($html, '<script') === false) {
            $html = self::appendJsDelayProof($html, 0, 0, false);
            return self::appendNativeDeferProof($html);
        }

        $jqueryPaint = $heavy && self::paintJqueryDelayEnabled() && $paintAware;
        $delayedHandles = self::delayedHandleKinds($heavy, $analytics, $jqueryPaint);
        if ($jqueryPaint && !self::jqueryDelaySafeForHtml($html, $delayedHandles)) {
            $jqueryPaint = false;
            $delayedHandles = self::delayedHandleKinds($heavy, $analytics, false);
        }
        $candidateBases = array();
        if (preg_match_all('/<script\b[^>]*>.*?<\/script>/is', $html, $matches) === 1 || !empty($matches[0])) {
            foreach ((array) ($matches[0] ?? array()) as $tag) {
                $id = self::attribute((string) $tag, 'id');
                $src = self::attribute((string) $tag, 'src');
                $base = self::scriptBase($id);
                $kind = '';
                if ($base !== '' && isset($delayedHandles[$base])) {
                    $kind = (string) $delayedHandles[$base];
                } elseif ($analytics && self::isAnalyticsScript($src)) {
                    $kind = 'analytics';
                } elseif ($heavy && self::isHeavyScript($base, $src, $jqueryPaint)) {
                    $kind = 'heavy';
                }
                if ($kind !== '' && $base !== '') {
                    if (!isset($candidateBases[$base]) || $kind === 'analytics') {
                        $candidateBases[$base] = $kind;
                    }
                }
            }
        }

        $changed = false;
        $sequence = 0;
        $rewritten = preg_replace_callback(
            '/<script\b([^>]*)>(.*?)<\/script>/is',
            static function (array $match) use (&$changed, &$sequence, $candidateBases, $delayedHandles, $heavy, $analytics, $jqueryPaint): string {
                $attrs = (string) ($match[1] ?? '');
                $body = (string) ($match[2] ?? '');
                $whole = (string) ($match[0] ?? '');
                $id = FrontendOptimizer::attribute($whole, 'id');
                $src = FrontendOptimizer::attribute($whole, 'src');
                $type = strtolower(FrontendOptimizer::attribute($whole, 'type'));
                $base = FrontendOptimizer::scriptBase($id);

                if (FrontendOptimizer::isNonJavascriptType($type) || stripos($whole, 'data-yperf-loader') !== false || FrontendOptimizer::isCriticalInline($id, $body)) {
                    return $whole;
                }

                $kind = '';
                if ($src !== '') {
                    if ($base !== '' && isset($delayedHandles[$base])) {
                        $kind = (string) $delayedHandles[$base];
                    } elseif ($analytics && FrontendOptimizer::isAnalyticsScript($src)) {
                        $kind = 'analytics';
                    } elseif ($heavy && FrontendOptimizer::isHeavyScript($base, $src, $jqueryPaint)) {
                        $kind = 'heavy';
                    }
                } elseif ($base !== '') {
                    if (isset($candidateBases[$base])) {
                        $kind = (string) $candidateBases[$base];
                    } elseif (isset($delayedHandles[$base])) {
                        $kind = (string) $delayedHandles[$base];
                    }
                }

                if ($kind === '') {
                    return $whole;
                }

                ++$sequence;
                $changed = true;
                return FrontendOptimizer::delayScriptTag($attrs, $body, $sequence, $kind);
            },
            $html
        );

        $candidateCount = count($candidateBases);
        if (!is_string($rewritten) || !$changed) {
            $html = self::appendJsDelayProof($html, $candidateCount, 0, false);
            return self::appendNativeDeferProof($html);
        }

        $loader = self::loaderScript($paintAware);
        $bodyClose = strripos($rewritten, '</body>');
        if ($bodyClose !== false) {
            $rewritten = substr($rewritten, 0, $bodyClose) . $loader . substr($rewritten, $bodyClose);
        } else {
            $rewritten .= $loader;
        }
        $rewritten = self::appendJsDelayProof($rewritten, $candidateCount, $sequence, true);
        return self::appendNativeDeferProof($rewritten);
    }

    /**
     * Preview-only, consent-preserving YouTube iframe loading.
     * The original embed URL is retained and activated on click; without the
     * tiny canary JS, the thumbnail remains an ordinary link to YouTube.
     * Retain known YouTube embeds (including browser-lazy iframes) while leaving
     * unknown third-party providers untouched.
     */
    private static function applyYoutubeLite(string $html): string
    {
        if (!function_exists('plugins_url') || stripos($html, '<iframe') === false) {
            return $html;
        }
        $count = 0;
        $rewritten = preg_replace_callback(
            '~<iframe\b[^>]*>(?:\s*)</iframe>~i',
            static function (array $match) use (&$count): string {
                $tag = (string) ($match[0] ?? '');
                if ($tag === '' || stripos($tag, 'srcdoc=') !== false || stripos($tag, 'data-yperf-') !== false) {
                    return $tag;
                }
                $src = html_entity_decode(self::attribute($tag, 'src'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $parsed = wp_parse_url($src);
                if (!is_array($parsed)) {
                    return $tag;
                }
                $host = strtolower((string) ($parsed['host'] ?? ''));
                if (!in_array($host, array('youtube.com', 'www.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'), true)
                    || strtolower((string) ($parsed['scheme'] ?? '')) !== 'https'
                    || preg_match('~^/embed/([A-Za-z0-9_-]{11})(?:/|$)~', (string) ($parsed['path'] ?? ''), $video) !== 1) {
                    return $tag;
                }
                $videoId = (string) $video[1];
                $title = self::attribute($tag, 'title');
                if ($title === '') {
                    $title = 'YouTube video';
                }
                $width = (int) self::attribute($tag, 'width');
                $height = (int) self::attribute($tag, 'height');
                $ratio = ($width > 0 && $height > 0) ? min(2.6, max(0.5, $width / $height)) : (16 / 9);
                $watchUrl = 'https://www.youtube.com/watch?v=' . rawurlencode($videoId);
                // If the original embed begins at a specific second, preserve
                // it in the no-script / no-thumbnail external watch fallback.
                $originalQuery = (string) ($parsed['query'] ?? '');
                if (preg_match('/(?:^|&)start=([0-9]{1,6})(?:&|$)/', $originalQuery, $startMatch) === 1) {
                    $watchUrl .= '&t=' . (int) $startMatch[1] . 's';
                }
                $thumbUrl = 'https://i.ytimg.com/vi/' . rawurlencode($videoId) . '/hqdefault.jpg';
                ++$count;

                return '<div class="yperf-video-lite" style="aspect-ratio:' . esc_attr(sprintf('%.4F', $ratio)) . '"'
                    . ' data-yperf-video-embed="' . esc_attr($src) . '"'
                    . ' data-yperf-video-title="' . esc_attr($title) . '">'
                    . '<a class="yperf-video-lite-trigger" href="' . esc_url($watchUrl) . '" target="_blank" rel="noopener noreferrer"'
                    . ' aria-label="' . esc_attr($title) . '">'
                    . '<img src="' . esc_url($thumbUrl) . '" alt="" loading="lazy" decoding="async">'
                    . '<span class="yperf-video-lite-play" aria-hidden="true">&#9654;</span>'
                    . '</a></div>';
            },
            $html
        );
        if (!is_string($rewritten) || $count === 0) {
            return $html;
        }

        // Diagnostic marker only. The actual CSS/JS tags are emitted by the
        // WordPress enqueue system, which maintains handle ownership and order.
        $proof = '<!--yperf-video-lite:version=' . esc_attr(YPERF_VERSION)
            . ';mode=' . esc_attr(self::videoLiteModeForRequest())
            . ';transformed=' . (int) $count . '-->';
        // If HEAD is absent, fail closed rather than output a fragment.
        if (preg_match('/<head\b[^>]*>/i', $rewritten, $head, PREG_OFFSET_CAPTURE) !== 1) {
            return $html;
        }
        $offset = (int) ($head[0][1] ?? 0) + strlen((string) ($head[0][0] ?? ''));
        return substr($rewritten, 0, $offset) . $proof . substr($rewritten, $offset);
    }

    /**
     * Vimeo iframe canary. Strictly opt-in, no external preview request: the
     * link remains a usable fallback without JavaScript and unknown embeds
     * are never changed. Preserves original player URL including privacy and
     * unlisted-video parameters; skips iframes with sandbox/srcdoc semantics.
     */
    private static function applyVimeoLiteCanary(string $html): string
    {
        if (!function_exists('plugins_url') || stripos($html, '<iframe') === false) {
            return $html;
        }
        $count = 0;
        $rewritten = preg_replace_callback(
            '~<iframe\b[^>]*>(?:\s*)</iframe>~i',
            static function (array $match) use (&$count): string {
                $tag = (string) ($match[0] ?? '');
                if ($tag === '' || preg_match('/\s(?:srcdoc|sandbox|credentialless|data-yperf-[a-z-]+)\s*(?:=|[\s>])/i', $tag) === 1) {
                    return $tag;
                }
                $src = html_entity_decode(self::attribute($tag, 'src'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $parsed = wp_parse_url($src);
                if (!is_array($parsed)
                    || strtolower((string) ($parsed['scheme'] ?? '')) !== 'https'
                    || strtolower((string) ($parsed['host'] ?? '')) !== 'player.vimeo.com'
                    || isset($parsed['port']) || isset($parsed['user']) || isset($parsed['pass'])
                    || preg_match('~^/video/([0-9]{1,12})(?:/|$)~', (string) ($parsed['path'] ?? ''), $video) !== 1) {
                    return $tag;
                }
                $videoId = (string) $video[1];
                $title = self::attribute($tag, 'title');
                if ($title === '') {
                    $title = __('Vimeo video', 'yablonsky-performance');
                }
                $width = (int) self::attribute($tag, 'width');
                $height = (int) self::attribute($tag, 'height');
                $ratio = ($width > 0 && $height > 0) ? min(2.6, max(0.5, $width / $height)) : (16 / 9);
                $watchUrl = 'https://vimeo.com/' . rawurlencode($videoId);
                // Preserve Vimeo's unlisted hash on the no-JavaScript link.
                $originalQuery = (string) ($parsed['query'] ?? '');
                if (preg_match('/(?:^|&)h=([A-Za-z0-9]{6,64})(?:&|$)/', $originalQuery, $hash) === 1) {
                    $watchUrl .= '?h=' . rawurlencode((string) $hash[1]);
                }
                ++$count;
                return '<div class="yperf-video-lite yperf-video-lite-vimeo" style="aspect-ratio:' . esc_attr(sprintf('%.4F', $ratio)) . '"'
                    . ' data-yperf-video-provider="vimeo"'
                    . ' data-yperf-video-embed="' . esc_attr($src) . '"'
                    . ' data-yperf-video-title="' . esc_attr($title) . '">'
                    . '<a class="yperf-video-lite-trigger" href="' . esc_url($watchUrl) . '" target="_blank" rel="noopener noreferrer"'
                    . ' aria-label="' . esc_attr($title) . '">'
                    . '<span class="yperf-video-lite-vimeo-brand" aria-hidden="true">Vimeo</span>'
                    . '<span class="yperf-video-lite-play" aria-hidden="true">&#9654;</span>'
                    . '<span class="yperf-video-lite-vimeo-caption">' . esc_html__('Play video', 'yablonsky-performance') . '</span>'
                    . '</a></div>';
            },
            $html
        );
        if (!is_string($rewritten) || $count === 0) {
            return $html;
        }
        // Keep the existing YouTube proof exactly as is. Vimeo has a separate
        // marker and explicitly identifies experimental scope.
        if (preg_match('/<head\b[^>]*>/i', $rewritten, $head, PREG_OFFSET_CAPTURE) !== 1) {
            return $html;
        }
        $proof = '<!--yperf-vimeo-canary:version=' . esc_attr(YPERF_VERSION) . ';transformed=' . (int) $count . '-->';
        $offset = (int) ($head[0][1] ?? 0) + strlen((string) ($head[0][0] ?? ''));
        return substr($rewritten, 0, $offset) . $proof . substr($rewritten, $offset);
    }

    private static function appendJsDelayProof(string $html, int $candidates, int $delayed, bool $loader): string
    {
        if (!self::isYperfProbe()) {
            return $html;
        }
        $proof = sprintf(
            '<!--yperf-js-proof:candidates=%d;delayed=%d;loader=%d-->',
            max(0, $candidates),
            max(0, $delayed),
            $loader ? 1 : 0
        );
        // Runtime Acceptance intentionally caps downloaded response bytes. Put the
        // probe-only proof immediately after the opening <head> tag so a large
        // inlined stylesheet or long body cannot truncate the evidence.
        $html = preg_replace('/<!--yperf-js-proof:candidates=\d+;delayed=\d+;loader=[01]-->/', '', $html) ?? $html;
        if (preg_match('/<head\b[^>]*>/i', $html, $headMatch, PREG_OFFSET_CAPTURE) === 1) {
            $headTag = (string) ($headMatch[0][0] ?? '');
            $headOffset = (int) ($headMatch[0][1] ?? 0) + strlen($headTag);
            return substr($html, 0, $headOffset) . $proof . substr($html, $headOffset);
        }
        $bodyClose = strripos($html, '</body>');
        if ($bodyClose === false) {
            return $proof . $html;
        }
        return substr($html, 0, $bodyClose) . $proof . substr($html, $bodyClose);
    }

    public static function gateWooTransactionalAssets(): void
    {
        if (!self::wooRouteGateEnabled() || !self::shouldOptimizePublicRequest() || self::requiresTransactionalWooRuntime()) {
            return;
        }

        $scripts = wp_scripts();
        if (!is_object($scripts) || !isset($scripts->queue) || !is_array($scripts->queue)) {
            return;
        }

        foreach ($scripts->queue as $handle) {
            $normalized = strtolower((string) $handle);
            if (preg_match('/^wc(?:-blocks)?-(?:cart|checkout)(?:-|$)/', $normalized) === 1
                || in_array($normalized, array('wc-cart-block', 'wc-checkout-block'), true)) {
                wp_dequeue_script((string) $handle);
            }
        }
    }

    public static function reduceOverhead(): void
    {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('wp_enqueue_scripts', 'wp_enqueue_emoji_styles');
    }

    private static function shouldOptimizePublicRequest(): bool
    {
        if (is_admin() || (function_exists('wp_doing_ajax') && wp_doing_ajax()) || is_user_logged_in()) {
            return false;
        }
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return false;
        }
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper(sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_METHOD']))) : 'GET';
        if (!in_array($method, array('GET', 'HEAD'), true)) {
            return false;
        }
        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_URI'])) : '/';
        $path = (string) wp_parse_url($uri, PHP_URL_PATH);
        if (RequestPolicy::isProtectedServicePath($path)) {
            return false;
        }
        if ((function_exists('is_feed') && is_feed()) || (function_exists('is_preview') && is_preview()) || (function_exists('is_embed') && is_embed())) {
            return false;
        }
        if (self::isPrivateCommerceRoute()) {
            return false;
        }
        return true;
    }

    private static function isPrivateCommerceRoute(): bool
    {
        foreach (array('is_cart', 'is_checkout', 'is_account_page') as $fn) {
            if (function_exists($fn) && call_user_func($fn)) {
                return true;
            }
        }
        return false;
    }

    private static function requiresTransactionalWooRuntime(): bool
    {
        if (self::isPrivateCommerceRoute()) {
            return true;
        }
        $post = get_queried_object();
        if (!is_object($post) || !isset($post->post_content)) {
            return false;
        }
        $content = (string) $post->post_content;
        foreach (self::INTERACTIVE_WOO_BLOCKS as $block) {
            if (function_exists('has_block') && has_block($block, $content)) {
                return true;
            }
        }
        return false;
    }

    private static function isYperfProbe(): bool
    {
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_USER_AGENT'])) : '';
        return strpos($agent, 'Yablonsky-Performance/') === 0
            || strpos($agent, 'Yablonsky-Performance-Origin-Probe/') === 0;
    }

    private static function policyHeaderValue(?bool $documentLcpCandidate = null): string
    {
        $sourcePaintFirst = self::paintFirstRoute();
        $lcpPriority = $documentLcpCandidate !== null ? $documentLcpCandidate : $sourcePaintFirst;
        $paintGate = $sourcePaintFirst && $lcpPriority;
        return sprintf(
            'emoji=1;woo_gate=%d;heavy_delay=%d;late_analytics=%d;css_inline=%d;css_alias=%d;idle_release=%d;css_origin_hint=%d;split_delay=%d;lcp_quiet=%d;lcp_seen_gate=%d;style_marker=%d;paint_first=%d;lcp_priority=%d;woo_media=%d;paint_gate=%d;jquery_paint=%d;native_defer=%d;css_early_capture=1;css_early_preload=1;cf_scheduler_isolation=1',
            self::wooRouteGateEnabled() ? 1 : 0,
            self::effectiveHeavyJsDelay() ? 1 : 0,
            self::lateAnalyticsEnabled() ? 1 : 0,
            self::inlineCssEnabled() ? 1 : 0,
            self::rewrittenCssResolutionEnabled() ? 1 : 0,
            self::idleHeavyReleaseEnabled() ? 1 : 0,
            (self::inlineCssEnabled() && self::rewrittenCssResolutionEnabled()) ? 1 : 0,
            (self::heavyJsDelayEnabled() || self::lateAnalyticsEnabled()) ? 1 : 0,
            (self::idleHeavyReleaseEnabled() && $paintGate) ? 1 : 0,
            (self::lcpObservedGateEnabled() && $paintGate) ? 1 : 0,
            self::finalStyleMarkersEnabled() ? 1 : 0,
            $sourcePaintFirst ? 1 : 0,
            $lcpPriority ? 1 : 0,
            $lcpPriority ? 1 : 0,
            ($paintGate && self::heavyJsDelayEnabled()) ? 1 : 0,
            ($paintGate && self::paintJqueryDelayEnabled()) ? 1 : 0,
            self::nativeDeferExecutionAllowed() ? 1 : 0
        );
    }

    private static function effectiveHeavyJsDelay(): bool
    {
        // Keep heavyweight WordPress/WooCommerce execution out of the initial
        // parse path on eligible anonymous public routes. Final-document hero
        // discovery decides whether the loader uses the LCP-aware path. Routes
        // without a high-confidence image candidate keep heavyweight execution
        // behind load/idle or explicit user intent, with the bounded late fallback.
        return self::heavyJsDelayEnabled();
    }

    private static function paintFirstRoute(): bool
    {
        if (!function_exists('get_queried_object')) {
            return false;
        }
        $post = get_queried_object();
        if (!is_object($post) || !isset($post->post_content)) {
            return false;
        }
        $content = (string) $post->post_content;
        if ($content === '') {
            return false;
        }
        if (function_exists('has_block') && has_block('core/cover', $content)) {
            return true;
        }
        return strpos($content, 'wp-block-cover') !== false;
    }

    private static function preloadLateDiscoveredCurrentThemeStylesheet(string $html): string
    {
        if (stripos($html, '<head') === false || stripos($html, '<link') === false || stripos($html, 'data-yperf-css-preload="1"') !== false) {
            return $html;
        }
        if (preg_match('/<head\b[^>]*>/i', $html, $headMatch, PREG_OFFSET_CAPTURE) !== 1) {
            return $html;
        }
        $headTag = (string) ($headMatch[0][0] ?? '');
        $headEnd = (int) ($headMatch[0][1] ?? 0) + strlen($headTag);
        if ($headTag === '') {
            return $html;
        }
        if (preg_match_all('/<link\b[^>]*\brel\s*=\s*(["\'])[^"\']*stylesheet[^"\']*\1[^>]*>/i', $html, $matches, PREG_OFFSET_CAPTURE) === false) {
            return $html;
        }
        foreach ((array) ($matches[0] ?? array()) as $entry) {
            $tag = (string) ($entry[0] ?? '');
            $offset = (int) ($entry[1] ?? 0);
            if ($tag === '' || $offset <= $headEnd || ($offset - $headEnd) < self::EARLY_THEME_CSS_PRELOAD_DISTANCE) {
                continue;
            }
            $media = strtolower(trim(self::attribute($tag, 'media')));
            if ($media !== '' && $media !== 'all') {
                continue;
            }
            $href = self::attribute($tag, 'href');
            if ($href === '' || !self::isSameOriginHttpUrl($href)) {
                continue;
            }
            $handle = sanitize_key(self::attribute($tag, 'data-yperf-style-handle'));
            if ($handle === '') {
                $linkId = sanitize_key(self::attribute($tag, 'id'));
                if (substr($linkId, -4) === '-css') {
                    $handle = substr($linkId, 0, -4);
                }
            }
            $themePath = self::currentThemeStylesheetPath($href, $handle);
            if ($themePath === '' || !is_readable($themePath)) {
                continue;
            }
            $fileSize = @filesize($themePath);
            if (!is_int($fileSize) || $fileSize <= self::INLINE_CSS_FILE_LIMIT) {
                continue;
            }
            $preload = '<link rel="preload" as="style" href="' . esc_url($href) . '" fetchpriority="high" data-yperf-css-preload="1">';
            return substr($html, 0, $headEnd) . $preload . substr($html, $headEnd);
        }
        return $html;
    }

    private static function prioritizeFirstPaintHero(string $html): string
    {
        if (stripos($html, '<img') === false || stripos($html, 'data-yperf-lcp-priority="1"') !== false) {
            return $html;
        }

        $bodyPos = stripos($html, '<body');
        if ($bodyPos === false) {
            $bodyPos = 0;
        }

        $bestScore = PHP_INT_MIN;
        $bestOffset = -1;
        $bestTag = '';
        $bestSrc = '';
        $seen = 0;
        if (preg_match_all('/<img\b[^>]*>/i', $html, $matches, PREG_OFFSET_CAPTURE) === false) {
            return $html;
        }
        foreach ((array) ($matches[0] ?? array()) as $entry) {
            if (++$seen > 32) {
                break;
            }
            $tag = (string) ($entry[0] ?? '');
            $offset = (int) ($entry[1] ?? -1);
            if ($tag === '' || $offset < $bodyPos) {
                continue;
            }
            $src = self::attribute($tag, 'src');
            if ($src === '' || !self::isSameOriginHttpUrl($src)) {
                continue;
            }
            $score = self::firstPaintImageScore($html, $tag, $offset, $bodyPos);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestOffset = $offset;
                $bestTag = $tag;
                $bestSrc = $src;
            }
        }

        // Fail closed on uncertain markup. The threshold intentionally requires
        // more than simple DOM order, so ordinary logos/icons are not promoted.
        if ($bestOffset < 0 || $bestTag === '' || $bestSrc === '' || $bestScore < 180) {
            return $html;
        }

        $priorityTag = self::setHtmlAttribute($bestTag, 'fetchpriority', 'high');
        $priorityTag = self::setHtmlAttribute($priorityTag, 'loading', 'eager');
        $priorityTag = self::setHtmlAttribute($priorityTag, 'decoding', 'async');
        $priorityTag = self::setHtmlAttribute($priorityTag, 'data-yperf-lcp-priority', '1');
        $rewritten = substr($html, 0, $bestOffset) . $priorityTag . substr($html, $bestOffset + strlen($bestTag));

        if (!self::hasImagePreload($rewritten, $bestSrc)) {
            $preload = '<link rel="preload" as="image" href="' . esc_url($bestSrc) . '" fetchpriority="high" data-yperf-lcp-preload="1"';
            $srcset = self::attribute($priorityTag, 'srcset');
            $sizes = self::attribute($priorityTag, 'sizes');
            if ($srcset !== '') {
                $preload .= ' imagesrcset="' . esc_attr($srcset) . '"';
                if ($sizes !== '') {
                    $preload .= ' imagesizes="' . esc_attr($sizes) . '"';
                }
            }
            $preload .= '>';
            if (preg_match('/<head\b[^>]*>/i', $rewritten, $headMatch, PREG_OFFSET_CAPTURE) === 1) {
                $headTag = (string) ($headMatch[0][0] ?? '');
                $headPos = (int) ($headMatch[0][1] ?? 0) + strlen($headTag);
                $rewritten = substr($rewritten, 0, $headPos) . $preload . substr($rewritten, $headPos);
            }
        }
        return $rewritten;
    }

    private static function firstPaintImageScore(string $html, string $tag, int $offset, int $bodyPos): int
    {
        $score = 0;
        $classId = strtolower(self::attribute($tag, 'class') . ' ' . self::attribute($tag, 'id'));
        $src = strtolower(self::attribute($tag, 'src'));
        $loading = strtolower(self::attribute($tag, 'loading'));
        $priority = strtolower(self::attribute($tag, 'fetchpriority'));
        $ariaHidden = strtolower(self::attribute($tag, 'aria-hidden'));
        $style = strtolower(self::attribute($tag, 'style'));
        $width = (int) preg_replace('/[^0-9].*/', '', self::attribute($tag, 'width'));
        $height = (int) preg_replace('/[^0-9].*/', '', self::attribute($tag, 'height'));

        if ($ariaHidden === 'true' || strpos($style, 'display:none') !== false || strpos($style, 'visibility:hidden') !== false) {
            return -1000;
        }
        if (preg_match('/(?:^|[\s_-])(logo|avatar|icon|emoji|gravatar|badge|flag|spinner|loader)(?:$|[\s_-])/i', $classId) === 1) {
            $score -= 420;
        }
        if ($loading === 'lazy') {
            $score -= 220;
        } elseif ($loading === 'eager') {
            $score += 100;
        }
        if ($priority === 'high') {
            $score += 500;
        }
        if (preg_match('/(?:^|[\s_-])(hero|banner|slide|slider|carousel|cover|masthead|jumbotron|featured|promo)(?:$|[\s_-])/i', $classId) === 1) {
            $score += 260;
        }
        if (preg_match('~(?:hero|banner|slide|slider|carousel|cover|masthead|promo)[^/]*\.(?:avif|webp|png|jpe?g)(?:[?#]|$)~i', $src) === 1) {
            $score += 120;
        }

        $contextStart = max($bodyPos, $offset - 900);
        $context = strtolower(substr($html, $contextStart, max(0, $offset - $contextStart)));
        if (preg_match('/(?:class|id)\s*=\s*["\'][^"\']*(?:hero|banner|slide|slider|carousel|cover|masthead|jumbotron|featured|promo)[^"\']*["\']/i', $context) === 1) {
            $score += 230;
        }

        if ($width > 0 && $height > 0) {
            if ($width <= 96 && $height <= 96) {
                $score -= 360;
            }
            if (($width * $height) >= 60000) {
                $score += 100;
            }
            if ($width >= 480 && $height >= 80) {
                $score += 80;
            }
            if ($height > 0 && ($width / $height) >= 2.0) {
                $score += 60;
            }
        }

        $relative = max(0, $offset - $bodyPos);
        if ($relative < 65536) {
            $score += 80;
        } elseif ($relative < 160000) {
            $score += 40;
        }
        return $score;
    }

    private static function optimizePostHeroWooThumbnails(string $html): string
    {
        $heroPos = stripos($html, 'data-yperf-lcp-priority="1"');
        if ($heroPos === false) {
            return $html;
        }
        $prefix = substr($html, 0, $heroPos);
        $tail = substr($html, $heroPos);
        $changed = 0;
        $rewritten = preg_replace_callback(
            '/<img\b[^>]*>/i',
            static function (array $match) use (&$changed): string {
                if ($changed >= 12) {
                    return (string) ($match[0] ?? '');
                }
                $tag = (string) ($match[0] ?? '');
                $class = strtolower(FrontendOptimizer::attribute($tag, 'class'));
                if (strpos($class, 'attachment-woocommerce_thumbnail') === false && strpos($class, 'size-woocommerce_thumbnail') === false) {
                    return $tag;
                }
                $loading = strtolower(FrontendOptimizer::attribute($tag, 'loading'));
                $priority = strtolower(FrontendOptimizer::attribute($tag, 'fetchpriority'));
                if ($loading === 'eager' || $priority === 'high') {
                    return $tag;
                }
                $tag = FrontendOptimizer::setHtmlAttribute($tag, 'loading', 'lazy');
                $tag = FrontendOptimizer::setHtmlAttribute($tag, 'fetchpriority', 'low');
                $srcset = FrontendOptimizer::attribute($tag, 'srcset');
                if ($srcset !== '') {
                    $sizes = FrontendOptimizer::attribute($tag, 'sizes');
                    if ($sizes === '') {
                        $tag = FrontendOptimizer::setHtmlAttribute($tag, 'sizes', 'auto');
                    } elseif (stripos(ltrim($sizes), 'auto') !== 0) {
                        $tag = FrontendOptimizer::setHtmlAttribute($tag, 'sizes', 'auto, ' . $sizes);
                    }
                }
                ++$changed;
                return $tag;
            },
            $tail
        );
        return $prefix . (is_string($rewritten) ? $rewritten : $tail);
    }


    private static function inlineEligibleStylesheets(string $html): string
    {
        if (!self::inlineCssEnabled()) {
            return $html;
        }

        // One document transform equals one proof sample. Reset counters here so
        // repeated transforms in long-lived/test processes cannot leak evidence
        // from an earlier document into the current Runtime Acceptance sample.
        self::$cssInlineProof = array('inlined' => 0, 'bytes' => 0, 'anchor_hits' => 0, 'tail_hits' => 0, 'theme_hits' => 0, 'local_hits' => 0, 'remaining_stylesheets' => 0);
        if (stripos($html, '<link') === false || !self::inlineStylesAllowedByDocumentPolicy($html)) {
            return self::appendCssInlineProof(self::stripStyleOwnershipAnchors($html));
        }

        $totalBytes = 0;
        $inlined = 0;
        $rewritten = preg_replace_callback(
            '/(<link\b[^>]*>)((?:(?:\s+|<!--(?!yperf-style-handle:)[\s\S]{0,256}?-->)*(?:<!--yperf-style-handle:[a-z0-9_\-]+-->|<meta\b[^>]*\bdata-yperf-style-anchor\s*=\s*(?:"[a-f0-9]{24}"|\'[a-f0-9]{24}\')[^>]*>))*)/i',
            static function (array $match) use (&$totalBytes, &$inlined): string {
                $tag = (string) ($match[1] ?? '');
                $ownershipTrailer = (string) ($match[2] ?? '');
                // Preserve any bounded third-party comment/whitespace that appeared
                // between the stylesheet tag and the YPerf ownership anchor. Only
                // YPerf's own non-rendering anchor is consumed by the transform.
                $preservedTrailer = FrontendOptimizer::stripStyleOwnershipAnchors($ownershipTrailer);
                $markerHandle = FrontendOptimizer::styleHandleFromOwnershipTrailer($ownershipTrailer);
                $rel = strtolower(trim(FrontendOptimizer::attribute($tag, 'rel')));
                if ($rel === '' || strpos(' ' . preg_replace('/\s+/', ' ', $rel) . ' ', ' stylesheet ') === false || strpos(' ' . $rel . ' ', ' alternate ') !== false) {
                    return $tag . $preservedTrailer;
                }
                if (FrontendOptimizer::attribute($tag, 'integrity') !== '') {
                    return FrontendOptimizer::cssSkipTag($tag, $preservedTrailer, 'integrity');
                }
                if (stripos($tag, 'data-yperf-no-inline') !== false) {
                    return FrontendOptimizer::cssSkipTag($tag, $preservedTrailer, 'no_inline');
                }
                $media = strtolower(trim(FrontendOptimizer::attribute($tag, 'media')));
                if ($media === 'print') {
                    return FrontendOptimizer::cssSkipTag($tag, $preservedTrailer, 'print_media');
                }

                $href = FrontendOptimizer::attribute($tag, 'href');
                if ($href === '') {
                    return FrontendOptimizer::cssSkipTag($tag, $preservedTrailer, 'missing_href');
                }
                if (!FrontendOptimizer::isSameOriginHttpUrl($href)) {
                    return FrontendOptimizer::cssSkipTag($tag, $preservedTrailer, 'cross_origin');
                }
                $originalHref = FrontendOptimizer::attribute($tag, 'data-yperf-original-href');
                $lookupHref = ($originalHref !== '' && FrontendOptimizer::isSameOriginHttpUrl($originalHref)) ? $originalHref : $href;
                $handle = FrontendOptimizer::attribute($tag, 'data-yperf-style-handle');
                if ($handle === '') {
                    $handle = $markerHandle;
                }
                if ($handle === '') {
                    $linkId = sanitize_key(FrontendOptimizer::attribute($tag, 'id'));
                    if (substr($linkId, -4) === '-css') {
                        $handle = substr($linkId, 0, -4);
                    }
                }
                $handle = sanitize_key($handle);

                $css = '';
                $size = 0;
                $resolution = '';
                $cssBaseHref = $lookupHref;
                if ($handle !== '' && isset(FrontendOptimizer::$capturedStylesheets[$handle])) {
                    $candidate = FrontendOptimizer::$capturedStylesheets[$handle];
                    $candidateCss = (string) ($candidate['css'] ?? '');
                    $candidateSize = (int) ($candidate['size'] ?? 0);
                    $candidateHref = (string) ($candidate['href'] ?? '');
                    if ($candidateCss !== '' && $candidateSize > 0 && $candidateSize <= FrontendOptimizer::INLINE_CSS_FILE_LIMIT) {
                        $css = $candidateCss;
                        $size = $candidateSize;
                        $resolution = 'anchor';
                        if ($candidateHref !== '' && FrontendOptimizer::isSameOriginHttpUrl($candidateHref)) {
                            $cssBaseHref = $candidateHref;
                        }
                    }
                }

                if ($css === '') {
                    $captured = FrontendOptimizer::uniqueCapturedStylesheetAlias($href);
                    if ($captured !== array()) {
                        $candidateCss = (string) ($captured['css'] ?? '');
                        $candidateSize = (int) ($captured['size'] ?? 0);
                        $candidateHref = (string) ($captured['href'] ?? '');
                        if ($candidateCss !== '' && $candidateSize > 0 && $candidateSize <= FrontendOptimizer::INLINE_CSS_FILE_LIMIT) {
                            $css = $candidateCss;
                            $size = $candidateSize;
                            $resolution = 'tail';
                            if ($candidateHref !== '' && FrontendOptimizer::isSameOriginHttpUrl($candidateHref)) {
                                $cssBaseHref = $candidateHref;
                            }
                        }
                    }
                }

                if ($css === '') {
                    $themePath = FrontendOptimizer::currentThemeStylesheetPath($href, $handle);
                    if ($themePath !== '' && is_readable($themePath)) {
                        $themeSize = @filesize($themePath);
                        $themeCss = (is_int($themeSize) && $themeSize > 0 && $themeSize <= FrontendOptimizer::INLINE_CSS_FILE_LIMIT) ? @file_get_contents($themePath) : false;
                        if (is_string($themeCss) && $themeCss !== '') {
                            $css = $themeCss;
                            $size = (int) $themeSize;
                            $resolution = 'theme';
                        }
                    }
                }

                if ($css === '') {
                    $path = FrontendOptimizer::localStylesheetPath($lookupHref, FrontendOptimizer::attribute($tag, 'id'), $handle);
                    if ($path === '' || !is_readable($path)) {
                        return FrontendOptimizer::cssSkipTag($tag, $preservedTrailer, 'unresolved_local_ownership');
                    }
                    $fileSize = @filesize($path);
                    if (!is_int($fileSize) || $fileSize < 1) {
                        return FrontendOptimizer::cssSkipTag($tag, $preservedTrailer, 'file_stat');
                    }
                    if ($fileSize > FrontendOptimizer::INLINE_CSS_FILE_LIMIT) {
                        return FrontendOptimizer::cssSkipTag($tag, $preservedTrailer, 'file_limit');
                    }
                    $fileCss = @file_get_contents($path);
                    if (!is_string($fileCss) || $fileCss === '') {
                        return FrontendOptimizer::cssSkipTag($tag, $preservedTrailer, 'read_failure');
                    }
                    $css = $fileCss;
                    $size = $fileSize;
                    $resolution = 'local';
                }

                if (($totalBytes + $size) > FrontendOptimizer::INLINE_CSS_TOTAL_LIMIT) {
                    return FrontendOptimizer::cssSkipTag($tag, $preservedTrailer, 'total_limit');
                }
                if (stripos($css, '@import') !== false) {
                    return FrontendOptimizer::cssSkipTag($tag, $preservedTrailer, 'contains_import');
                }

                $css = FrontendOptimizer::rewriteCssUrls($css, $cssBaseHref);
                $css = preg_replace('/^\xEF\xBB\xBF/', '', $css) ?? $css;
                $css = preg_replace('/@charset\s+["\'][^"\']+["\'];?/i', '', $css) ?? $css;
                $css = preg_replace('/\/\*#\s*sourceMappingURL=.*?\*\//is', '', $css) ?? $css;
                $css = str_ireplace('</style', '<\/style', trim($css));
                if ($css === '') {
                    return FrontendOptimizer::cssSkipTag($tag, $preservedTrailer, 'empty_after_sanitize');
                }

                $id = FrontendOptimizer::attribute($tag, 'id');
                $attrs = ' data-yperf-inline-css="1" data-yperf-inline-bytes="' . (int) $size . '"';
                if ($id !== '') {
                    $attrs .= ' id="' . esc_attr($id) . '"';
                }
                if ($media !== '' && $media !== 'all') {
                    $attrs .= ' media="' . esc_attr(FrontendOptimizer::attribute($tag, 'media')) . '"';
                }
                $totalBytes += $size;
                ++$inlined;
                FrontendOptimizer::$cssInlineProof['inlined'] = (int) FrontendOptimizer::$cssInlineProof['inlined'] + 1;
                FrontendOptimizer::$cssInlineProof['bytes'] = (int) FrontendOptimizer::$cssInlineProof['bytes'] + $size;
                if ($resolution === 'anchor') {
                    FrontendOptimizer::$cssInlineProof['anchor_hits'] = (int) FrontendOptimizer::$cssInlineProof['anchor_hits'] + 1;
                } elseif ($resolution === 'tail') {
                    FrontendOptimizer::$cssInlineProof['tail_hits'] = (int) FrontendOptimizer::$cssInlineProof['tail_hits'] + 1;
                } elseif ($resolution === 'theme') {
                    FrontendOptimizer::$cssInlineProof['theme_hits'] = (int) FrontendOptimizer::$cssInlineProof['theme_hits'] + 1;
                } elseif ($resolution === 'local') {
                    FrontendOptimizer::$cssInlineProof['local_hits'] = (int) FrontendOptimizer::$cssInlineProof['local_hits'] + 1;
                }
                return '<style' . $attrs . '>' . $css . '</style>' . $preservedTrailer;
            },
            $html
        );

        if (!is_string($rewritten)) {
            $clean = self::stripStyleOwnershipAnchors($html);
            return self::appendCssInlineProof($clean);
        }
        $clean = self::stripStyleOwnershipAnchors($rewritten);
        return self::appendCssInlineProof($clean);
    }

    private static function cssSkipTag(string $tag, string $trailer, string $reason): string
    {
        if (self::isYperfProbe()) {
            $tag = self::setHtmlAttribute($tag, 'data-yperf-css-skip', sanitize_key($reason));
        }
        return $tag . $trailer;
    }

    private static function appendCssInlineProof(string $html): string
    {
        if (!self::isYperfProbe()) {
            return $html;
        }
        $remaining = 0;
        if (preg_match_all('/<link\b[^>]*\brel\s*=\s*(["\'])[^"\']*stylesheet[^"\']*\1[^>]*>/i', $html, $matches) !== false) {
            $remaining = count((array) ($matches[0] ?? array()));
        }
        self::$cssInlineProof['remaining_stylesheets'] = $remaining;
        $proof = sprintf(
            '<!--yperf-css-proof:inlined=%d;bytes=%d;anchor=%d;tail=%d;theme=%d;local=%d;remaining=%d-->',
            (int) self::$cssInlineProof['inlined'],
            (int) self::$cssInlineProof['bytes'],
            (int) self::$cssInlineProof['anchor_hits'],
            (int) self::$cssInlineProof['tail_hits'],
            (int) self::$cssInlineProof['theme_hits'],
            (int) self::$cssInlineProof['local_hits'],
            (int) self::$cssInlineProof['remaining_stylesheets']
        );
        // Runtime Acceptance intentionally reads a bounded response body.
        // Put the probe-only execution proof immediately after <head> so large
        // stylesheets or long documents cannot push valid evidence past the
        // diagnostic response-size boundary.
        $html = preg_replace('/<!--yperf-css-proof:inlined=\d+;bytes=\d+;anchor=\d+;tail=\d+;theme=\d+;local=\d+;remaining=\d+-->/', '', $html) ?? $html;
        if (preg_match('/<head\b[^>]*>/i', $html, $headMatch, PREG_OFFSET_CAPTURE) === 1) {
            $headTag = (string) ($headMatch[0][0] ?? '');
            $headOffset = (int) ($headMatch[0][1] ?? 0) + strlen($headTag);
            return substr($html, 0, $headOffset) . $proof . substr($html, $headOffset);
        }
        return $proof . $html;
    }

    private static function styleAnchorToken(string $handle, string $href): string
    {
        if ($handle === '' || $href === '') {
            return '';
        }
        return substr(hash('sha256', $handle . "\0" . $href), 0, 24);
    }

    private static function styleHandleFromOwnershipTrailer(string $trailer): string
    {
        if ($trailer === '') {
            return '';
        }
        if (preg_match('/<!--yperf-style-handle:([a-z0-9_\-]+)-->/i', $trailer, $legacy) === 1) {
            return sanitize_key((string) ($legacy[1] ?? ''));
        }
        if (preg_match('/data-yperf-style-anchor\s*=\s*(["\'])([a-f0-9]{24})\1/i', $trailer, $anchor) === 1) {
            $token = strtolower((string) ($anchor[2] ?? ''));
            if ($token !== '' && isset(self::$styleAnchorHandles[$token])) {
                return sanitize_key((string) self::$styleAnchorHandles[$token]);
            }
        }
        return '';
    }

    private static function stripStyleOwnershipAnchors(string $html): string
    {
        $html = preg_replace('/<!--yperf-style-handle:[a-z0-9_\-]+-->/i', '', $html) ?? $html;
        return preg_replace('/<meta\b[^>]*\bdata-yperf-style-anchor\s*=\s*(?:"[a-f0-9]{24}"|\'[a-f0-9]{24}\')[^>]*>/i', '', $html) ?? $html;
    }

    private static function inlineStylesAllowedByDocumentPolicy(string $html): bool
    {
        if (preg_match('/<meta\b[^>]*http-equiv\s*=\s*(["\'])Content-Security-Policy\1[^>]*>/i', $html, $meta) === 1) {
            $content = self::attribute((string) ($meta[0] ?? ''), 'content');
            if ($content !== '' && !self::cspAllowsInlineStyle($content)) {
                return false;
            }
        }
        if (function_exists('headers_list')) {
            foreach (headers_list() as $header) {
                if (stripos((string) $header, 'Content-Security-Policy:') !== 0) {
                    continue;
                }
                $policy = trim(substr((string) $header, strlen('Content-Security-Policy:')));
                if ($policy !== '' && !self::cspAllowsInlineStyle($policy)) {
                    return false;
                }
            }
        }
        return true;
    }

    private static function cspAllowsInlineStyle(string $policy): bool
    {
        $directives = array();
        foreach (explode(';', strtolower($policy)) as $directive) {
            $directive = trim($directive);
            if ($directive === '') {
                continue;
            }
            $parts = preg_split('/\s+/', $directive) ?: array();
            $name = array_shift($parts);
            if (is_string($name) && $name !== '') {
                $directives[$name] = $parts;
            }
        }
        $tokens = $directives['style-src-elem'] ?? ($directives['style-src'] ?? ($directives['default-src'] ?? null));
        if (!is_array($tokens)) {
            return true;
        }
        return in_array("'unsafe-inline'", $tokens, true);
    }

    /**
     * Resolve a path-hidden stylesheet only when the browser link still carries
     * an exact registered WordPress handle and its trailing asset path maps to
     * one unique file inside the active child/parent theme directory.
     * No network request and no path guess outside the current theme roots.
     */
    private static function currentThemeStylesheetPath(string $href, string $handle): string
    {
        $handle = sanitize_key($handle);
        if ($handle === '' || !function_exists('wp_styles')) {
            return '';
        }
        $styles = wp_styles();
        if (!is_object($styles) || !isset($styles->registered) || !is_array($styles->registered) || !isset($styles->registered[$handle])) {
            return '';
        }
        $publicPath = rawurldecode((string) wp_parse_url($href, PHP_URL_PATH));
        $suffix = self::stylesheetPathSuffix($publicPath);
        if ($suffix === '') {
            return '';
        }
        $relative = str_replace('/', DIRECTORY_SEPARATOR, $suffix);
        $roots = array();
        foreach (array('get_stylesheet_directory', 'get_template_directory') as $function) {
            if (!function_exists($function)) {
                continue;
            }
            $root = (string) call_user_func($function);
            $realRoot = $root !== '' ? realpath($root) : false;
            if ($realRoot !== false) {
                $roots[$realRoot] = true;
            }
        }
        $matches = array();
        foreach (array_keys($roots) as $root) {
            $candidate = rtrim((string) $root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relative;
            $real = realpath($candidate);
            if ($real === false || strtolower((string) pathinfo($real, PATHINFO_EXTENSION)) !== 'css') {
                continue;
            }
            $prefix = rtrim((string) $root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if (strpos($real, $prefix) !== 0 || !is_readable($real)) {
                continue;
            }
            $matches[$real] = true;
            if (count($matches) > 1) {
                return '';
            }
        }
        return count($matches) === 1 ? (string) array_key_first($matches) : '';
    }

    private static function localStylesheetPath(string $href, string $linkId = '', string $handleHint = ''): string
    {
        $href = html_entity_decode(trim($href), ENT_QUOTES);
        if ($href === '') {
            return '';
        }

        $candidates = array($href);
        $handle = strtolower(trim($handleHint));
        if ($handle === '') {
            $handle = strtolower(trim($linkId));
            if (substr($handle, -4) === '-css') {
                $handle = substr($handle, 0, -4);
            }
        }
        if (function_exists('wp_styles')) {
            $styles = wp_styles();
            if (is_object($styles) && isset($styles->registered) && is_array($styles->registered)) {
                if ($handle !== '' && isset($styles->registered[$handle]) && is_object($styles->registered[$handle])) {
                    $registeredSrc = self::normalizeRegisteredStylesheetUrl((string) ($styles->registered[$handle]->src ?? ''));
                    if ($registeredSrc !== '') {
                        $candidates[] = $registeredSrc;
                    }
                }

                // Some security/path-hiding plugins rewrite a normal WordPress
                // stylesheet URL before it reaches the browser. When the link
                // ID no longer maps cleanly to a registered handle, recover
                // only a UNIQUE registered stylesheet whose final path suffix
                // matches the public URL. We never fetch the rewritten URL and
                // never guess between ambiguous candidates.
                if (self::rewrittenCssResolutionEnabled()) {
                    $alias = self::uniqueRegisteredStylesheetAlias($href, $styles->registered);
                    if ($alias !== '') {
                        $candidates[] = $alias;
                    }
                }
            }
        }

        $maps = array();
        if (defined('WP_CONTENT_DIR')) {
            $contentPath = (string) wp_parse_url(content_url('/'), PHP_URL_PATH);
            if ($contentPath !== '') {
                $maps[rtrim($contentPath, '/')] = rtrim((string) WP_CONTENT_DIR, '/\\');
            }
        }
        if (defined('ABSPATH') && defined('WPINC')) {
            $includesPath = (string) wp_parse_url(includes_url('/'), PHP_URL_PATH);
            if ($includesPath !== '') {
                $maps[rtrim($includesPath, '/')] = rtrim((string) ABSPATH, '/\\') . DIRECTORY_SEPARATOR . trim((string) WPINC, '/\\');
            }
        }

        foreach (array_values(array_unique($candidates)) as $candidateUrl) {
            $path = rawurldecode((string) wp_parse_url($candidateUrl, PHP_URL_PATH));
            if ($path === '' || strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) !== 'css') {
                continue;
            }
            foreach ($maps as $urlPrefix => $diskPrefix) {
                if ($urlPrefix === '' || strpos($path . '/', $urlPrefix . '/') !== 0) {
                    continue;
                }
                $relative = ltrim(substr($path, strlen($urlPrefix)), '/');
                $candidate = $diskPrefix . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
                $real = realpath($candidate);
                $base = realpath($diskPrefix);
                if ($real === false || $base === false || strpos($real, rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) !== 0) {
                    continue;
                }
                return $real;
            }
        }
        return '';
    }

    private static function normalizeRegisteredStylesheetUrl(string $src): string
    {
        $src = html_entity_decode(trim($src), ENT_QUOTES);
        if ($src === '') {
            return '';
        }
        if (strpos($src, '//') === 0) {
            return (string) wp_parse_url(home_url('/'), PHP_URL_SCHEME) . ':' . $src;
        }
        if ($src[0] === '/') {
            return rtrim(home_url('/'), '/') . $src;
        }
        if (wp_parse_url($src, PHP_URL_HOST) === null) {
            return rtrim(home_url('/'), '/') . '/' . ltrim($src, '/');
        }
        return $src;
    }

    /**
     * Resolve a path-rewritten public CSS URL to one registered WordPress style.
     * The match deliberately needs at least three trailing path segments and
     * must be unique; otherwise the method fails closed.
     *
     * @param array<string,mixed> $registered
     */
    private static function uniqueRegisteredStylesheetAlias(string $publicUrl, array $registered): string
    {
        $publicPath = rawurldecode((string) wp_parse_url($publicUrl, PHP_URL_PATH));
        $suffix = self::stylesheetPathSuffix($publicPath);
        if ($suffix === '') {
            return '';
        }

        $matches = array();
        foreach ($registered as $item) {
            if (!is_object($item)) {
                continue;
            }
            $candidate = self::normalizeRegisteredStylesheetUrl((string) ($item->src ?? ''));
            if ($candidate === '' || !self::isSameOriginHttpUrl($candidate)) {
                continue;
            }
            $candidatePath = rawurldecode((string) wp_parse_url($candidate, PHP_URL_PATH));
            if ($candidatePath === '' || self::stylesheetPathSuffix($candidatePath) !== $suffix) {
                continue;
            }
            $matches[$candidate] = true;
            if (count($matches) > 1) {
                return '';
            }
        }
        return count($matches) === 1 ? (string) array_key_first($matches) : '';
    }

    /**
     * Resolve a final rewritten public stylesheet URL against CSS bytes that
     * were already captured from a same-origin realpath-proven WordPress
     * registration earlier in this request. A match must be unique by the
     * trailing asset path; ambiguity fails closed and performs no fetch.
     *
     * @return array{href:string,css:string,size:int}|array{}
     */
    private static function uniqueCapturedStylesheetAlias(string $publicUrl): array
    {
        $publicPath = rawurldecode((string) wp_parse_url($publicUrl, PHP_URL_PATH));
        $suffix = self::stylesheetPathSuffix($publicPath);
        if ($suffix === '') {
            return array();
        }

        $match = array();
        foreach (self::$capturedStylesheets as $candidate) {
            $candidateHref = (string) ($candidate['href'] ?? '');
            if ($candidateHref === '' || !self::isSameOriginHttpUrl($candidateHref)) {
                continue;
            }
            $candidatePath = rawurldecode((string) wp_parse_url($candidateHref, PHP_URL_PATH));
            if ($candidatePath === '' || self::stylesheetPathSuffix($candidatePath) !== $suffix) {
                continue;
            }
            if ($match !== array()) {
                return array();
            }
            $match = array(
                'href' => $candidateHref,
                'css' => (string) ($candidate['css'] ?? ''),
                'size' => (int) ($candidate['size'] ?? 0),
            );
        }
        return $match;
    }

    private static function stylesheetPathSuffix(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        if ($path === '' || strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) !== 'css') {
            return '';
        }
        $segments = array_values(array_filter(explode('/', $path), static function (string $segment): bool {
            return $segment !== '' && $segment !== '.' && $segment !== '..';
        }));
        if (count($segments) < 3) {
            return '';
        }
        return strtolower(implode('/', array_slice($segments, -3)));
    }

    private static function rewriteCssUrls(string $css, string $stylesheetUrl): string
    {
        $base = html_entity_decode($stylesheetUrl, ENT_QUOTES);
        return (string) preg_replace_callback(
            '/url\(\s*(["\']?)(.*?)\1\s*\)/is',
            static function (array $match) use ($base): string {
                $raw = trim((string) ($match[2] ?? ''));
                if ($raw === '' || $raw[0] === '#' || $raw[0] === '/' || preg_match('#^(?:data:|https?:|//|var\()#i', $raw) === 1) {
                    return (string) ($match[0] ?? '');
                }
                $absolute = FrontendOptimizer::resolveRelativeUrl($base, $raw);
                if ($absolute === '') {
                    return (string) ($match[0] ?? '');
                }
                $quote = (string) ($match[1] ?? '');
                return 'url(' . $quote . $absolute . $quote . ')';
            },
            $css
        );
    }

    private static function resolveRelativeUrl(string $baseUrl, string $relative): string
    {
        $relativeParts = wp_parse_url($relative);
        if (!is_array($relativeParts)) {
            return '';
        }
        $baseParts = wp_parse_url($baseUrl);
        if (!is_array($baseParts)) {
            return '';
        }

        $scheme = (string) ($baseParts['scheme'] ?? wp_parse_url(home_url('/'), PHP_URL_SCHEME));
        $host = (string) ($baseParts['host'] ?? wp_parse_url(home_url('/'), PHP_URL_HOST));
        if ($host === '') {
            return '';
        }
        $port = isset($baseParts['port']) ? ':' . (int) $baseParts['port'] : '';
        $basePath = (string) ($baseParts['path'] ?? '/');
        $relativePath = (string) ($relativeParts['path'] ?? '');
        $path = self::normalizeUrlPath(rtrim(dirname($basePath), '/') . '/' . ltrim($relativePath, '/'));
        $query = isset($relativeParts['query']) ? '?' . (string) $relativeParts['query'] : '';
        $fragment = isset($relativeParts['fragment']) ? '#' . (string) $relativeParts['fragment'] : '';
        return $scheme . '://' . $host . $port . $path . $query . $fragment;
    }

    private static function normalizeUrlPath(string $path): string
    {
        $segments = array();
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }
        return '/' . implode('/', $segments);
    }

    private static function hasImagePreload(string $html, string $heroSrc): bool
    {
        if (preg_match_all('/<link\b[^>]*>/i', $html, $matches) !== 1 && empty($matches[0])) {
            return false;
        }
        foreach ((array) ($matches[0] ?? array()) as $tag) {
            $rel = strtolower(self::attribute((string) $tag, 'rel'));
            $href = self::attribute((string) $tag, 'href');
            $as = strtolower(self::attribute((string) $tag, 'as'));
            if (strpos(' ' . $rel . ' ', ' preload ') !== false && ($as === '' || $as === 'image') && html_entity_decode($href, ENT_QUOTES) === html_entity_decode($heroSrc, ENT_QUOTES)) {
                return true;
            }
        }
        return false;
    }

    private static function setHtmlAttribute(string $tag, string $name, string $value): string
    {
        $pattern = '/\\s+' . preg_quote($name, '/') . '\\s*=\\s*(["\\\']).*?\\1/i';
        if (preg_match($pattern, $tag) === 1) {
            return (string) preg_replace($pattern, ' ' . $name . '="' . esc_attr($value) . '"', $tag, 1);
        }
        $pos = strrpos($tag, '>');
        if ($pos === false) {
            return $tag;
        }
        return substr($tag, 0, $pos) . ' ' . $name . '="' . esc_attr($value) . '"' . substr($tag, $pos);
    }

    private static function isSameOriginHttpUrl(string $url): bool
    {
        $url = html_entity_decode(trim($url), ENT_QUOTES);
        $scheme = strtolower((string) wp_parse_url($url, PHP_URL_SCHEME));
        if ($scheme !== '' && !in_array($scheme, array('http', 'https'), true)) {
            return false;
        }
        $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return true;
        }
        $homeHost = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        return $homeHost !== '' && hash_equals($homeHost, $host);
    }

    private static function scriptBase(string $id): string
    {
        $id = strtolower(trim($id));
        if ($id === '') {
            return '';
        }
        foreach (array('-js-before', '-js-after', '-js-extra', '-js') as $suffix) {
            if (substr($id, -strlen($suffix)) === $suffix) {
                return substr($id, 0, -strlen($suffix));
            }
        }
        return $id;
    }

    /**
     * Build dependency-closed delayed groups. Analytics is the later group and
     * therefore wins when a dependent chain crosses both groups. This prevents
     * a script from being released before one of its delayed dependencies.
     *
     * @return array<string,string> handle => heavy|analytics
     */
    private static function delayedHandleKinds(bool $heavy, bool $analytics, bool $jqueryPaint = false): array
    {
        if (!function_exists('wp_scripts')) {
            return array();
        }
        $scripts = wp_scripts();
        if (!is_object($scripts) || !isset($scripts->queue, $scripts->registered) || !is_array($scripts->queue) || !is_array($scripts->registered)) {
            return array();
        }

        $queued = array();
        foreach ($scripts->queue as $handle) {
            $queued[strtolower((string) $handle)] = (string) $handle;
        }

        // Expand the queue to the registered dependency graph. WordPress keeps
        // many dependency handles out of ->queue even though they will still be
        // printed. Paint-first jQuery scheduling must see jquery-core,
        // jquery-migrate and the exclusive dependency chain, otherwise the
        // browser still pays the same render-blocking network fan-out.
        $reachable = $queued;
        $cursor = array_values($queued);
        while ($cursor !== array()) {
            $handle = array_shift($cursor);
            $registered = $scripts->registered[$handle] ?? null;
            $deps = is_object($registered) && isset($registered->deps) && is_array($registered->deps) ? $registered->deps : array();
            foreach ($deps as $dependency) {
                $normalizedDependency = strtolower((string) $dependency);
                if ($normalizedDependency === '' || isset($reachable[$normalizedDependency])) {
                    continue;
                }
                $reachable[$normalizedDependency] = (string) $dependency;
                $cursor[] = (string) $dependency;
            }
        }

        $depsMap = array();
        $consumers = array();
        foreach ($reachable as $normalized => $handle) {
            $registered = $scripts->registered[$handle] ?? null;
            $deps = is_object($registered) && isset($registered->deps) && is_array($registered->deps) ? $registered->deps : array();
            $depsMap[$normalized] = array();
            foreach ($deps as $dependency) {
                $dependencyNormalized = strtolower((string) $dependency);
                if ($dependencyNormalized === '' || !isset($reachable[$dependencyNormalized])) {
                    continue;
                }
                $depsMap[$normalized][] = $dependencyNormalized;
                if (!isset($consumers[$dependencyNormalized])) {
                    $consumers[$dependencyNormalized] = array();
                }
                $consumers[$dependencyNormalized][$normalized] = true;
            }
        }

        $delayed = array();
        foreach ($reachable as $normalized => $handle) {
            $registered = $scripts->registered[$handle] ?? null;
            $src = is_object($registered) && isset($registered->src) ? (string) $registered->src : '';
            if ($analytics && self::isAnalyticsScript($src)) {
                $delayed[$normalized] = 'analytics';
            } elseif ($heavy && self::isHeavyScript($normalized, $src, $jqueryPaint)) {
                $delayed[$normalized] = 'heavy';
            }
        }

        // If a normal script depends on a delayed dependency, it must join the
        // same delayed group. This preserves WordPress dependency order.
        do {
            $changed = false;
            foreach ($reachable as $normalized => $handle) {
                $kind = isset($delayed[$normalized]) ? (string) $delayed[$normalized] : '';
                foreach ((array) ($depsMap[$normalized] ?? array()) as $dependencyNormalized) {
                    $dependencyKind = (string) ($delayed[$dependencyNormalized] ?? '');
                    if ($dependencyKind === 'analytics') {
                        $kind = 'analytics';
                        break;
                    }
                    if ($dependencyKind === 'heavy' && $kind === '') {
                        $kind = 'heavy';
                    }
                }
                if ($kind !== '' && (!isset($delayed[$normalized]) || $delayed[$normalized] !== $kind)) {
                    $delayed[$normalized] = $kind;
                    $changed = true;
                }
            }
        } while ($changed);

        if ($jqueryPaint) {
            // Pull in dependencies used exclusively by delayed scripts. A
            // top-level explicitly queued handle is never pulled in merely
            // because a delayed consumer references it. This is the bounded
            // replacement for Rocket Loader's broad scheduling: YPerf delays
            // only the dependency chain it can prove belongs to delayed work.
            do {
                $changed = false;
                foreach ($delayed as $normalized => $kind) {
                    foreach ((array) ($depsMap[$normalized] ?? array()) as $dependencyNormalized) {
                        if (isset($delayed[$dependencyNormalized]) || isset($queued[$dependencyNormalized])) {
                            continue;
                        }
                        $dependencyConsumers = array_keys((array) ($consumers[$dependencyNormalized] ?? array()));
                        if ($dependencyConsumers === array()) {
                            continue;
                        }
                        $allDelayed = true;
                        $dependencyKind = 'heavy';
                        foreach ($dependencyConsumers as $consumer) {
                            if (!isset($delayed[$consumer])) {
                                $allDelayed = false;
                                break;
                            }
                            if ((string) $delayed[$consumer] === 'analytics') {
                                $dependencyKind = 'analytics';
                            }
                        }
                        if ($allDelayed) {
                            $delayed[$dependencyNormalized] = $dependencyKind;
                            $changed = true;
                        }
                    }
                }
            } while ($changed);
        }

        return $delayed;
    }

    /** @param array<string,string> $delayedHandles */
    private static function jqueryDelaySafeForHtml(string $html, array $delayedHandles): bool
    {
        if (preg_match_all('/<script\b([^>]*)>(.*?)<\/script>/is', $html, $matches, PREG_SET_ORDER) !== 1 && empty($matches)) {
            return true;
        }
        foreach ((array) $matches as $match) {
            $attrs = (string) ($match[1] ?? '');
            $body = (string) ($match[2] ?? '');
            $whole = '<script' . $attrs . '>' . $body . '</script>';
            if (self::attribute($whole, 'src') !== '' || $body === '') {
                continue;
            }
            if (stripos($body, 'jQuery') === false && preg_match('/(^|[^A-Za-z0-9_$])\$\s*\(/', $body) !== 1) {
                continue;
            }
            $base = self::scriptBase(self::attribute($whole, 'id'));
            if ($base === '' || !isset($delayedHandles[$base])) {
                return false;
            }
        }
        return true;
    }

    private static function isHeavyScript(string $base, string $src, bool $jqueryPaint = false): bool
    {
        $base = strtolower($base);
        if ($jqueryPaint && in_array($base, array('jquery', 'jquery-core', 'jquery-migrate'), true)) {
            return true;
        }
        foreach (self::HEAVY_HANDLE_PATTERNS as $needle) {
            if ($base === $needle || strpos($base, $needle . '-') === 0) {
                return true;
            }
        }

        $path = strtolower((string) wp_parse_url(html_entity_decode($src, ENT_QUOTES), PHP_URL_PATH));
        if ($path === '') {
            return false;
        }
        if ($jqueryPaint && (strpos($path, '/jquery/jquery.min.js') !== false || strpos($path, '/jquery/jquery-migrate.min.js') !== false)) {
            return true;
        }
        foreach (array(
            '/lib/js/dist/components',
            '/lib/js/dist/compose',
            '/lib/js/dist/data',
            '/lib/js/dist/rich-text',
            '/lib/js/dist/vendor/react',
            '/lib/js/dist/vendor/lodash',
            '/lib/js/dist/vendor/moment',
            '/assets/client/blocks/',
        ) as $needle) {
            if (strpos($path, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    private static function isAnalyticsScript(string $src): bool
    {
        if ($src === '') {
            return false;
        }
        $host = strtolower((string) wp_parse_url(html_entity_decode($src, ENT_QUOTES), PHP_URL_HOST));
        $path = strtolower((string) wp_parse_url(html_entity_decode($src, ENT_QUOTES), PHP_URL_PATH));
        return in_array($host, array('www.googletagmanager.com', 'googletagmanager.com'), true)
            && (strpos($path, '/gtag/js') === 0 || strpos($path, '/gtm.js') === 0);
    }

    private static function isCriticalInline(string $id, string $body): bool
    {
        $id = strtolower($id);
        if (in_array($id, array('ycc-consent-default', 'yperf-delay-loader'), true)) {
            return true;
        }
        if (stripos($body, '__YPERF_DELAY_LOADER__') !== false) {
            return true;
        }
        return false;
    }

    private static function isNonJavascriptType(string $type): bool
    {
        if ($type === '' || in_array($type, array('text/javascript', 'application/javascript'), true)) {
            return false;
        }
        return true;
    }

    private static function attribute(string $tag, string $name): string
    {
        $pattern = '/\b' . preg_quote($name, '/') . '\s*=\s*(["\'])(.*?)\1/is';
        if (preg_match($pattern, $tag, $match) === 1) {
            return html_entity_decode((string) ($match[2] ?? ''), ENT_QUOTES);
        }
        return '';
    }

    private static function delayScriptTag(string $attrs, string $body, int $sequence, string $kind): string
    {
        $src = self::attribute('<script ' . $attrs . '></script>', 'src');
        $id = self::attribute('<script ' . $attrs . '></script>', 'id');
        $hadAsync = preg_match('/\sasync(?:\s|=|$)/i', ' ' . $attrs) === 1;
        $hadDefer = preg_match('/\sdefer(?:\s|=|$)/i', ' ' . $attrs) === 1;

        $attrs = preg_replace('/\s+type\s*=\s*(["\']).*?\1/is', '', $attrs) ?? $attrs;
        $attrs = preg_replace('/\s+src\s*=\s*(["\']).*?\1/is', '', $attrs) ?? $attrs;
        $attrs = preg_replace('/\s+(?:async|defer)(?:\s*=\s*(["\']).*?\1)?/is', '', $attrs) ?? $attrs;

        $kind = $kind === 'analytics' ? 'analytics' : 'heavy';
        $extra = ' type="text/yperf-delayed" data-cfasync="false" data-yperf-delayed="1" data-yperf-kind="' . $kind . '" data-yperf-seq="' . $sequence . '"';
        if ($src !== '') {
            $extra .= ' data-yperf-src="' . esc_attr($src) . '"';
        }
        if ($id !== '') {
            $extra .= ' data-yperf-original-id="' . esc_attr($id) . '"';
        }
        if ($hadAsync) {
            $extra .= ' data-yperf-async="1"';
        }
        if ($hadDefer) {
            $extra .= ' data-yperf-defer="1"';
        }

        return '<script' . $attrs . $extra . '>' . $body . '</script>';
    }

    private static function loaderScript(bool $paintAware): string
    {
        $heavyFallback = self::LATE_EXECUTION_MS;
        $nonPaintHeavyFallback = self::NON_PAINT_HEAVY_FALLBACK_MS;
        $analyticsFallback = self::LATE_EXECUTION_MS + 2500;
        $idleRelease = self::idleHeavyReleaseEnabled() ? 'true' : 'false';
        $paintAwareJs = $paintAware ? 'true' : 'false';
        $lcpObservedGate = (self::lcpObservedGateEnabled() && $paintAware) ? 'true' : 'false';
        $script = <<<'JS'
(function(){
"use strict";
if(window.__YPERF_DELAY_LOADER__){return;}
window.__YPERF_DELAY_LOADER__=1;
var replay=false,queue=Promise.resolve(),allRequested=false,lastLcp=0,idleRelease=__IDLE_RELEASE__,paintAware=__PAINT_AWARE__,lcpObservedGate=__LCP_OBSERVED_GATE__;
function nodes(kind){
    var list=Array.prototype.slice.call(document.querySelectorAll('script[data-yperf-delayed="1"]'));
    if(!kind){return list;}
    return list.filter(function(n){return (n.getAttribute('data-yperf-kind')||'heavy')===kind;});
}
function copy(a,b){
    for(var i=0;i<a.attributes.length;i++){
        var x=a.attributes[i];
        if(x.name.indexOf('data-yperf-')===0||x.name==='type'||x.name==='src'){continue;}
        b.setAttribute(x.name,x.value);
    }
}
function lateEventShim(){
    var da=document.addEventListener,wa=window.addEventListener;
    function call(listener,target,type){
        setTimeout(function(){
            var ev;
            try{ev=new Event(type);}catch(e){ev=document.createEvent('Event');ev.initEvent(type,false,false);}
            if(typeof listener==='function'){listener.call(target,ev);}
            else if(listener&&typeof listener.handleEvent==='function'){listener.handleEvent(ev);}
        },0);
    }
    document.addEventListener=function(type,listener,opts){
        if(type==='DOMContentLoaded'&&document.readyState!=='loading'){call(listener,document,type);return;}
        return da.call(document,type,listener,opts);
    };
    window.addEventListener=function(type,listener,opts){
        if(type==='load'&&document.readyState==='complete'){call(listener,window,type);return;}
        return wa.call(window,type,listener,opts);
    };
    return function(){document.addEventListener=da;window.addEventListener=wa;};
}
function run(n){
    return new Promise(function(resolve){
        var s=document.createElement('script');
        copy(n,s);
        var src=n.getAttribute('data-yperf-src');
        if(src){s.src=src;s.async=false;s.onload=resolve;s.onerror=resolve;n.parentNode.insertBefore(s,n);n.remove();return;}
        s.text=n.text||n.textContent||'';
        n.parentNode.insertBefore(s,n);n.remove();resolve();
    });
}
function enqueue(kind){
    queue=queue.then(function(){
        var list=nodes(kind);
        if(!list.length){return true;}
        var restore=lateEventShim();
        return list.reduce(function(p,n){return p.then(function(){return run(n);});},Promise.resolve()).then(function(){restore();return true;},function(){restore();return false;});
    });
    return queue;
}
function activateHeavy(){if(allRequested){return queue;}return enqueue('heavy');}
function activateAnalytics(){if(allRequested){return queue;}return enqueue('analytics');}
function activateAll(){allRequested=true;return enqueue('');}
function intent(){if(nodes('').length){activateAll();}}
function idle(fn,timeout){
    if(idleRelease&&typeof requestIdleCallback==='function'){requestIdleCallback(fn,{timeout:timeout});}
    else{setTimeout(fn,Math.min(timeout,900));}
}
function heavyAfterLcpQuiet(){
    var now=(window.performance&&performance.now)?performance.now():Date.now();
    if(lcpObservedGate&&!lastLcp){setTimeout(heavyAfterLcpQuiet,500);return;}
    var quiet=lastLcp?now-lastLcp:2600;
    if(quiet<2500){setTimeout(heavyAfterLcpQuiet,Math.max(250,2500-quiet));return;}
    idle(activateHeavy,1800);
}
function noteLcp(){lastLcp=(window.performance&&performance.now)?performance.now():Date.now();}
if(typeof PerformanceObserver==='function'){
    try{
        var po=new PerformanceObserver(function(list){var e=list.getEntries();if(e&&e.length){noteLcp();}});
        po.observe({type:'largest-contentful-paint',buffered:true});
    }catch(e){}
}
function afterHero(){
    var release=function(){setTimeout(heavyAfterLcpQuiet,1400);};
    if(typeof requestAnimationFrame==='function'){requestAnimationFrame(function(){requestAnimationFrame(release);});}
    else{setTimeout(release,32);}
}
function releaseNonPaintHeavy(){
    var release=function(){idle(activateHeavy,1000);};
    if(typeof requestAnimationFrame==='function'){requestAnimationFrame(function(){requestAnimationFrame(release);});}
    else{setTimeout(release,32);}
}
function armPaintGate(){
    if(!paintAware){
        if(document.readyState==='complete'){
            releaseNonPaintHeavy();
        }else{
            window.addEventListener('load',releaseNonPaintHeavy,{once:true});
        }
        window.addEventListener('load',function(){setTimeout(activateAnalytics,6500);},{once:true});
        setTimeout(activateHeavy,__NON_PAINT_HEAVY_FALLBACK__);
        setTimeout(activateAnalytics,__ANALYTICS_FALLBACK__);
        return;
    }
    var hero=document.querySelector('img[data-yperf-lcp-priority="1"]');
    if(hero){
        if(hero.complete&&hero.naturalWidth>0){afterHero();}
        else{hero.addEventListener('load',afterHero,{once:true,passive:true});hero.addEventListener('error',afterHero,{once:true,passive:true});}
    }
    window.addEventListener('load',function(){setTimeout(heavyAfterLcpQuiet,1600);setTimeout(activateAnalytics,6500);},{once:true});
    setTimeout(activateHeavy,__HEAVY_FALLBACK__);
    setTimeout(activateAnalytics,__ANALYTICS_FALLBACK__);
}
['pointerdown','touchstart','keydown','scroll'].forEach(function(e){window.addEventListener(e,intent,{passive:true,once:true,capture:true});});
document.addEventListener('click',function(e){
    if(replay||!nodes('').length){return;}
    var t=e.target&&e.target.closest?e.target.closest('a[href],button,input[type=submit],input[type=button]'):null;
    if(!t){intent();return;}
    if(t.tagName==='A'){
        var href=t.getAttribute('href')||'';
        var jsLink=!href||href.charAt(0)==='#'||/^javascript:/i.test(href)||t.getAttribute('role')==='button'||t.hasAttribute('aria-controls')||t.hasAttribute('data-toggle')||t.hasAttribute('data-bs-toggle');
        if(!jsLink){intent();return;}
    }
    e.preventDefault();e.stopImmediatePropagation();
    activateAll().then(function(){replay=true;t.click();setTimeout(function(){replay=false;},0);});
},true);
armPaintGate();
})();
JS;
        $script = str_replace(
            array('__IDLE_RELEASE__', '__PAINT_AWARE__', '__LCP_OBSERVED_GATE__', '__NON_PAINT_HEAVY_FALLBACK__', '__HEAVY_FALLBACK__', '__ANALYTICS_FALLBACK__'),
            array($idleRelease, $paintAwareJs, $lcpObservedGate, (string) $nonPaintHeavyFallback, (string) $heavyFallback, (string) $analyticsFallback),
            $script
        );
        return '<script id="yperf-delay-loader" data-cfasync="false" data-yperf-loader="1">' . $script . '</script>';
    }


}
