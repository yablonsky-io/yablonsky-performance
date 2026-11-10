<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class RuntimeAcceptance
{
    private const SITEMAP_STEP_BUDGET_SECONDS = 7;
    private const SITEMAP_PUBLIC_TIMEOUT_SECONDS = 3;
    private const SITEMAP_ORIGIN_TIMEOUT_SECONDS = 3;

    /** @return array<int,array{id:string,label:string}> */
    public static function stepDefinitions(): array
    {
        return array(
            array('id' => 'policy_public', 'label' => 'Public cache policy'),
            array('id' => 'policy_query', 'label' => 'Query-string bypass'),
            array('id' => 'policy_logged_in', 'label' => 'Logged-in bypass'),
            array('id' => 'policy_woo_cookie', 'label' => 'Woo session bypass'),
            array('id' => 'policy_private_route', 'label' => 'Private Woo routes'),
            array('id' => 'policy_rest', 'label' => 'REST bypass'),
            array('id' => 'policy_post', 'label' => 'POST bypass'),
            array('id' => 'policy_protected_service', 'label' => 'Protected service bypass'),
            array('id' => 'yperf_early_runtime', 'label' => 'Managed early runtime'),
            array('id' => 'yperf_origin_admission', 'label' => 'Crawler origin admission'),
            array('id' => 'background_workload', 'label' => 'Background workload'),
            array('id' => 'homepage', 'label' => 'Homepage'),
            array('id' => 'external_page_cache', 'label' => 'External page-cache authority'),
            array('id' => 'yperf_page_cache', 'label' => 'YPerf Page Cache proof'),
            array('id' => 'yperf_browser_cache', 'label' => 'YPerf Browser Cache proof'),
            array('id' => 'yperf_frontend', 'label' => 'YPerf frontend proof'),
            array('id' => 'yperf_native_defer_canary', 'label' => 'Native defer canary proof'),
            array('id' => 'yperf_sitemap_isolation', 'label' => 'Sitemap isolation proof'),
            array('id' => 'woo_shop', 'label' => 'WooCommerce shop'),
            array('id' => 'woo_sort', 'label' => 'WooCommerce sort URL'),
            array('id' => 'woo_product', 'label' => 'WooCommerce product'),
            array('id' => 'woo_category', 'label' => 'WooCommerce category'),
            array('id' => 'woo_pagination', 'label' => 'WooCommerce pagination'),
            array('id' => 'yperf_first_hit', 'label' => 'YPerf first-hit proof'),
            array('id' => 'woo_cart', 'label' => 'Cart route policy'),
            array('id' => 'woo_checkout', 'label' => 'Checkout route policy'),
            array('id' => 'woo_account', 'label' => 'Account route policy'),
            array('id' => 'multilingual', 'label' => 'Multilingual alternate'),
        );
    }

    /** @return array<string,mixed> */
    public static function runStep(string $step): array
    {
        switch ($step) {
            case 'policy_public':
                return self::policyTest('Policy: anonymous public GET', array(), true, 'public_queryless_get');
            case 'policy_query':
                return self::policyTest('Policy: query-bearing GET', array('query' => array('orderby' => 'price')), false, 'query_string');
            case 'policy_logged_in':
                return self::policyTest('Policy: logged-in request', array('logged_in' => true), false, 'logged_in');
            case 'policy_woo_cookie':
                return self::policyTest('Policy: Woo session cookie', array('sensitive_cookie' => true), false, 'private_cookie');
            case 'policy_private_route':
                return self::policyTest('Policy: cart/checkout/account route', array('private_route' => true), false, 'private_route');
            case 'policy_rest':
                return self::policyTest('Policy: REST request', array('is_rest' => true), false, 'rest');
            case 'policy_post':
                return self::policyTest('Policy: POST request', array('method' => 'POST'), false, 'method');
            case 'policy_protected_service':
                return self::policyTest('Policy: protected service endpoint', array('protected_endpoint' => true), false, 'protected_endpoint');
            case 'yperf_early_runtime':
                return self::yperfEarlyRuntimeControl();
            case 'yperf_origin_admission':
                return self::yperfOriginAdmissionControl();
            case 'background_workload':
                return self::backgroundWorkloadControl();
            case 'homepage':
                return self::probeUrl(home_url('/'), 'Homepage')['test'];
            case 'external_page_cache':
                return self::externalPageCacheControl();
            case 'yperf_page_cache':
                return self::yperfPageCacheControl();
            case 'yperf_browser_cache':
                return self::yperfBrowserCacheControl();
            case 'yperf_frontend':
                return self::yperfFrontendControl();
            case 'yperf_native_defer_canary':
                return self::yperfNativeDeferCanaryControl();
            case 'yperf_sitemap_isolation':
                return self::yperfSitemapIsolation();
            case 'woo_shop':
                return self::wooShop();
            case 'woo_sort':
                return self::wooSort();
            case 'woo_product':
                return self::wooProduct();
            case 'woo_category':
                return self::wooCategory();
            case 'woo_pagination':
                return self::wooPagination();
            case 'yperf_first_hit':
                return self::yperfFirstHitControl();
            case 'woo_cart':
                return self::wooRoute('cart', 'Cart route policy');
            case 'woo_checkout':
                return self::wooRoute('checkout', 'Checkout route policy');
            case 'woo_account':
                return self::wooRoute('myaccount', 'Account route policy');
            case 'multilingual':
                return self::multilingual();
            default:
                return array(
                    'name'    => 'Unknown Runtime Acceptance step',
                    'status'  => 'fail',
                    'kind'    => 'internal',
                    'details' => 'The requested Runtime Acceptance step is not registered.',
                );
        }
    }

    /** @return array<string,mixed> */
    public static function run(): array
    {
        $tests = array();
        foreach (self::stepDefinitions() as $definition) {
            $tests[] = self::runStep($definition['id']);
        }
        return self::buildReport($tests);
    }

    /** @param array<int,mixed> $tests @return array<string,mixed> */
    public static function buildReport(array $tests): array
    {
        $normalized = array();

        foreach ($tests as $test) {
            if (!is_array($test)) {
                continue;
            }
            $clean = self::normalizeTest($test);
            if ($clean === array()) {
                continue;
            }
            $normalized[] = $clean;
        }

        $normalized = self::reconcileExecutionProofs($normalized);
        $summary = array('pass' => 0, 'fail' => 0, 'skip' => 0, 'attention' => 0);
        foreach ($normalized as $clean) {
            $status = (string) ($clean['status'] ?? 'fail');
            if (isset($summary[$status])) {
                ++$summary[$status];
            }
            if (!empty($clean['attention'])) {
                ++$summary['attention'];
            }
        }

        return array(
            'schema'       => 9,
            'generated_at' => time(),
            'plugin'       => 'Yablonsky Performance',
            'version'      => YPERF_VERSION,
            'site'         => home_url('/'),
            'summary'      => $summary,
            'origin_cost_summary' => self::originCostSummary($normalized),
            'tests'        => $normalized,
        );
    }

    /** @param array<int,array<string,mixed>> $tests @return array<int,array<string,mixed>> */
    private static function reconcileExecutionProofs(array $tests): array
    {
        $frontendProvenElsewhere = false;
        foreach ($tests as $test) {
            $signals = is_array($test['cache_signals'] ?? null) ? $test['cache_signals'] : array();
            if ((string) ($signals['x-yperf-frontend'] ?? '') === 'active' && (string) ($test['status'] ?? '') === 'pass') {
                $frontendProvenElsewhere = true;
                break;
            }
        }

        if (!$frontendProvenElsewhere) {
            return $tests;
        }

        foreach ($tests as &$test) {
            if ((string) ($test['name'] ?? '') !== 'YPerf frontend control' || (string) ($test['status'] ?? '') !== 'skip') {
                continue;
            }
            $test['status'] = 'pass';
            $test['kind'] = 'yperf-proof';
            $test['details'] = 'At least one current Health Check route reached WordPress and returned x-yperf-frontend=active. Frontend optimization is proven even if another sampled route was served from cache.';
            $test['proof_source'] = 'current_runtime_route';
        }
        unset($test);
        return $tests;
    }

    /** @return array<string,mixed> */
    public static function lastReport(): array
    {
        $report = get_option('yperf_runtime_acceptance_v1', array());
        return is_array($report) ? $report : array();
    }

    /** @param array<string,mixed> $overrides @return array<string,mixed> */
    private static function policyTest(string $name, array $overrides, bool $expectedCacheable, string $expectedReason): array
    {
        $base = array(
            'method'           => 'GET',
            'is_admin'         => false,
            'doing_ajax'       => false,
            'doing_cron'       => false,
            'is_rest'          => false,
            'logged_in'        => false,
            'private_route'    => false,
            'sensitive_cookie' => false,
            'query'            => array(),
        );
        $decision = RequestPolicy::classify(array_merge($base, $overrides));
        $pass = $decision['cacheable'] === $expectedCacheable && $decision['reason'] === $expectedReason;

        return array(
            'name'    => $name,
            'status'  => $pass ? 'pass' : 'fail',
            'kind'    => 'policy',
            'details' => 'decision=' . $decision['decision'] . '; reason=' . $decision['reason'],
        );
    }

    /** @param array<string,mixed> $runtime @return array{detected:bool,header:string,value:string,provider:string} */
    public static function externalPageCacheAuthority(array $runtime): array
    {
        foreach ((array) ($runtime['tests'] ?? array()) as $test) {
            if (!is_array($test) || (string) ($test['name'] ?? '') !== 'External page-cache authority') {
                continue;
            }
            if ((string) ($test['status'] ?? '') !== 'pass') {
                continue;
            }
            $header = sanitize_key((string) ($test['external_cache_header'] ?? ''));
            $value = strtoupper(sanitize_text_field((string) ($test['external_cache_value'] ?? '')));
            $provider = sanitize_text_field((string) ($test['provider'] ?? 'External page cache'));

            // Runtime reports are normalized before Optimize consumes them. Keep
            // explicit authority metadata when present, and independently derive
            // the same authority from preserved cache signals. This makes
            // Optimize resilient to report-schema changes and prevents a proven
            // Nginx/FastCGI cache from being mistaken for an empty delivery layer.
            if ($header === '' || $value === '') {
                $signals = is_array($test['cache_signals'] ?? null) ? $test['cache_signals'] : array();
                $authority = self::findExternalCacheSignal($signals);
                if (!empty($authority['detected'])) {
                    $header = sanitize_key((string) ($authority['header'] ?? ''));
                    $value = strtoupper(sanitize_text_field((string) ($authority['value'] ?? '')));
                }
            }
            if ($header === '' || $value === '') {
                continue;
            }
            return array(
                'detected' => true,
                'header' => $header,
                'value' => $value,
                'provider' => $provider,
            );
        }
        return array('detected' => false, 'header' => '', 'value' => '', 'provider' => '');
    }

    /** @return array<string,mixed> */
    private static function yperfEarlyRuntimeControl(): array
    {
        $status = ManagedRuntime::status();
        $loaderCurrent = !empty($status['loader_present'])
            && !empty($status['loader_owned'])
            && !empty($status['loader_version_current'])
            && !empty($status['runtime_source_present']);
        $active = !empty($status['runtime_active_this_request'])
            && (string) ($status['runtime_version'] ?? '') === YPERF_VERSION;

        if (!$loaderCurrent) {
            return array(
                'name' => 'YPerf managed early runtime',
                'status' => 'fail',
                'kind' => 'yperf-proof',
                'details' => 'The single YPerf-owned MU bootstrap is missing, foreign, stale or its runtime source is unavailable.',
                'managed_runtime' => $status,
            );
        }
        if (!$active) {
            return array(
                'name' => 'YPerf managed early runtime',
                'status' => 'fail',
                'kind' => 'yperf-proof',
                'details' => 'The managed MU bootstrap is current but the version-matched early runtime did not boot in this request.',
                'managed_runtime' => $status,
            );
        }
        return array(
            'name' => 'YPerf managed early runtime',
            'status' => 'pass',
            'kind' => 'yperf-proof',
            'details' => 'The single canonical YPerf MU bootstrap loaded the version-matched early runtime and is the first-party early WordPress performance authority.',
            'managed_runtime' => $status,
        );
    }

    /** @return array<string,mixed> */
    private static function yperfOriginAdmissionControl(): array
    {
        if (!class_exists(EarlyRuntime::class, false) || !method_exists(EarlyRuntime::class, 'originAdmissionStatus')) {
            return array(
                'name' => 'Crawler origin admission',
                'status' => 'fail',
                'kind' => 'yperf-proof',
                'details' => __('Managed Early Runtime is ready for reconciliation with the current Origin Survival capability. Reconcile the YPerf managed runtime to the current plugin version.', 'yablonsky-performance'),
            );
        }

        $status = EarlyRuntime::originAdmissionStatus();
        $slots = max(1, (int) ($status['slots'] ?? 0));
        $configured = !empty($status['configured']);
        return array(
            'name' => 'Crawler origin admission',
            'status' => 'pass',
            'kind' => 'yperf-proof',
            'details' => $configured
                ? sprintf(
                    /* translators: %d: crawler cold-origin concurrency slots */
                    __('Origin Survival is armed with %d crawler cold-origin slots. Cache HITs finish before this layer; admitted crawler MISS traffic keeps a bounded PHP-worker footprint.', 'yablonsky-performance'),
                    $slots
                )
                : sprintf(
                    /* translators: %d: crawler cold-origin concurrency slots */
                    __('Origin Survival capability is ready with a %d-slot portable crawler budget. The current performance profile keeps it in Standby.', 'yablonsky-performance'),
                    $slots
                ),
            'origin_admission' => $status,
        );
    }

    /** @return array<string,mixed> */
    private static function backgroundWorkloadControl(): array
    {
        if (!class_exists('\\ActionScheduler_Store') || !method_exists('\\ActionScheduler_Store', 'instance')) {
            return self::skip(
                'Background workload',
                __('Action Scheduler is outside the active runtime stack for this site.', 'yablonsky-performance')
            );
        }

        try {
            /** @var \ActionScheduler_Store $store */
            $store = \ActionScheduler_Store::instance();
            if (!is_object($store) || !method_exists($store, 'query_actions') || !method_exists($store, 'action_counts')) {
                return self::skip(
                    'Background workload',
                    __('Action Scheduler workload evidence is available after its runtime store initializes.', 'yablonsky-performance')
                );
            }

            $counts = $store->action_counts();
            $counts = is_array($counts) ? $counts : array();
            $pending = max(0, (int) ($counts['pending'] ?? 0));
            $failed = max(0, (int) ($counts['failed'] ?? 0));
            $complete = max(0, (int) ($counts['complete'] ?? 0));
            $inProgress = max(0, (int) ($counts['in-progress'] ?? 0));

            $pastDue = 0;
            if (function_exists('as_get_datetime_object')) {
                $pastDue = max(
                    0,
                    (int) $store->query_actions(
                        array(
                            'status' => 'pending',
                            'date' => as_get_datetime_object(),
                            'date_compare' => '<=',
                        ),
                        'count'
                    )
                );
            }

            $attention = $pastDue > 0 || $failed > 0;
            $test = array(
                'name' => 'Background workload',
                'status' => 'pass',
                'kind' => 'background-workload',
                'details' => sprintf(
                    /* translators: 1: pending actions, 2: past-due actions, 3: failed actions */
                    __('Action Scheduler workload is visible: %1$d pending, %2$d past-due and %3$d failed actions.', 'yablonsky-performance'),
                    $pending,
                    $pastDue,
                    $failed
                ),
                'background_workload' => array(
                    'provider' => 'action_scheduler',
                    'pending' => $pending,
                    'past_due' => $pastDue,
                    'failed' => $failed,
                    'complete' => $complete,
                    'in_progress' => $inProgress,
                    'read_only' => true,
                ),
            );
            if ($attention) {
                $test['attention'] = true;
                $test['attention_note'] = __(
                    'Background maintenance pressure is elevated. Review past-due and failed Action Scheduler work before starting additional heavy maintenance.',
                    'yablonsky-performance'
                );
            }
            return $test;
        } catch (\Throwable $error) {
            return array(
                'name' => 'Background workload',
                'status' => 'skip',
                'kind' => 'background-workload',
                'details' => __(
                    'Health Check is continuing with the remaining evidence while Action Scheduler workload evidence awaits a later refresh.',
                    'yablonsky-performance'
                ),
                'background_workload' => array(
                    'provider' => 'action_scheduler',
                    'read_only' => true,
                    'state' => 'refresh_later',
                ),
            );
        }
    }

    /** @return array<string,mixed> */
    private static function externalPageCacheControl(): array
    {
        $probe = self::probeUrl(home_url('/'), 'External page-cache authority probe');
        $test = is_array($probe['test'] ?? null) ? $probe['test'] : array();
        $signals = is_array($test['cache_signals'] ?? null) ? $test['cache_signals'] : array();
        $authority = self::findExternalCacheSignal($signals);
        $proofSource = 'public';

        // Cloud/CDN bot protection can hide origin cache headers from a public
        // server-side probe. When that happens, perform one bounded request to
        // this server's own address with the canonical Host header. This never
        // leaves the current origin and never changes Nginx configuration.
        if (empty($authority['detected'])) {
            $origin = self::originPageCacheProbe();
            $originSignals = is_array($origin['cache_signals'] ?? null) ? $origin['cache_signals'] : array();
            $originAuthority = self::findExternalCacheSignal($originSignals);
            if (!empty($originAuthority['detected'])) {
                $authority = $originAuthority;
                $signals = $originSignals;
                $test['http_code'] = (int) ($origin['http_code'] ?? 0);
                $test['ms'] = (int) ($origin['ms'] ?? 0);
                $proofSource = 'local_origin';
            }
        }

        if (empty($authority['detected'])) {
            return self::skip('External page-cache authority', 'No external origin page-cache status header was exposed by either the public route or the bounded local-origin probe.');
        }
        $header = (string) $authority['header'];
        $value = (string) $authority['value'];
        $httpCode = (int) ($test['http_code'] ?? 0);
        if ($httpCode < 200 || $httpCode >= 400) {
            return array(
                'name' => 'External page-cache authority',
                'status' => 'fail',
                'kind' => 'external-execution',
                'details' => sprintf('An external cache layer is visible via %s=%s, but the sampled response is HTTP %d. Cache execution is not accepted as healthy until the route returns a successful response.', $header, $value, $httpCode),
                'http_code' => $httpCode,
                'ms' => (int) ($test['ms'] ?? 0),
                'provider' => 'External origin page cache',
                'external_cache_header' => $header,
                'external_cache_value' => $value,
                'proof_source' => $proofSource,
                'cache_signals' => $signals,
            );
        }
        return array(
            'name' => 'External page-cache authority',
            'status' => 'pass',
            'kind' => 'external-execution',
            'details' => sprintf('An external page-cache authority is visible via %s=%s (%s proof). YPerf portable Page Cache activation must remain separate so two page-cache executors never compete.', $header, $value, $proofSource),
            'http_code' => (int) ($test['http_code'] ?? 0),
            'ms' => (int) ($test['ms'] ?? 0),
            'provider' => 'External origin page cache',
            'external_cache_header' => $header,
            'external_cache_value' => $value,
            'proof_source' => $proofSource,
            'cache_signals' => $signals,
        );
    }

    /**
     * Permit unverified direct-IP TLS probes only over explicitly scoped
     * local addresses. PHP's reserved-range flags also accept unspecified,
     * multicast, and link-local addresses, which are not trusted targets.
     */
    private static function isTrustedOriginIp(string $address): bool
    {
        $packed = @inet_pton($address);
        if ($packed === false) {
            return false;
        }
        if (strlen($packed) === 4) {
            $octets = array_values(unpack('C4', $packed));
            return $octets[0] === 127
                || $octets[0] === 10
                || ($octets[0] === 172 && $octets[1] >= 16 && $octets[1] <= 31)
                || ($octets[0] === 192 && $octets[1] === 168);
        }
        if (strlen($packed) === 16) {
            return $address === '::1'
                || ((ord($packed[0]) & 0xfe) === 0xfc);
        }
        return false;
    }

    /** @return array<string,mixed> */
    private static function originPageCacheProbe(): array
    {
        $serverAddr = isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field(wp_unslash((string) $_SERVER['SERVER_ADDR'])) : '';
        $homeHost = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        if ($serverAddr === '' || $homeHost === '' || filter_var($serverAddr, FILTER_VALIDATE_IP) === false) {
            return array();
        }
        // The direct IP probe has no certificate-name validation. Restrict it
        // to loopback/private origin addresses; use the verified public route
        // when the server address is publicly routable.
        if (!self::isTrustedOriginIp($serverAddr)) {
            return array();
        }
        $urlHost = strpos($serverAddr, ':') !== false ? '[' . $serverAddr . ']' : $serverAddr;
        $target = 'https://' . $urlHost . '/';
        $started = microtime(true);
        $response = wp_remote_get($target, array(
            'timeout' => 6,
            'redirection' => 0,
            'sslverify' => false,
            'limit_response_size' => 32768,
            'headers' => array(
                'Host' => $homeHost,
                'User-Agent' => 'Yablonsky-Performance/' . YPERF_VERSION . ' OriginProbe',
                'Accept' => 'text/html,application/xhtml+xml',
            ),
        ));
        if (is_wp_error($response)) {
            return array();
        }
        return array(
            'http_code' => (int) wp_remote_retrieve_response_code($response),
            'ms' => max(0, (int) round((microtime(true) - $started) * 1000)),
            'cache_signals' => self::cacheSignals(wp_remote_retrieve_headers($response)),
        );
    }

    /** @param array<string,string> $signals @return array{detected:bool,header:string,value:string} */
    private static function findExternalCacheSignal(array $signals): array
    {
        foreach ($signals as $header => $rawValue) {
            $name = strtolower((string) $header);
            if ($name === 'x-yperf-cache' || $name === 'x-yperf-cache-version' || $name === 'cf-cache-status') {
                continue;
            }
            if (preg_match('/^x-[a-z0-9-]*(?:cache|cache-status)$/', $name) !== 1) {
                continue;
            }
            $value = strtoupper(trim((string) $rawValue));
            if (!in_array($value, array('HIT', 'MISS', 'BYPASS', 'STALE', 'EXPIRED', 'UPDATING', 'REVALIDATED'), true)) {
                continue;
            }
            return array('detected' => true, 'header' => $name, 'value' => $value);
        }
        return array('detected' => false, 'header' => '', 'value' => '');
    }

    /** @return array<string,mixed> */
    private static function yperfPageCacheControl(): array
    {
        if (!PageCacheExecutor::enabled()) {
            return self::skip('YPerf Page Cache', 'The YPerf-owned page-cache executor is OFF.');
        }
        $proof = PageCacheExecutor::prove();
        if (empty($proof['ok'])) {
            return array(
                'name' => 'YPerf Page Cache',
                'status' => 'fail',
                'kind' => 'execution',
                'details' => 'YPerf Page Cache was enabled but failed the current MISS → HIT execution proof.',
                'cache_signals' => array('x-yperf-cache' => 'not_proven'),
            );
        }
        return array(
            'name' => 'YPerf Page Cache',
            'status' => 'pass',
            'kind' => 'execution',
            'details' => 'Two bounded same-origin requests proved YPerf Page Cache MISS → HIT execution.',
            'http_code' => (int) (($proof['second']['http_code'] ?? 0)),
            'ms' => (int) (($proof['second']['ms'] ?? 0)),
            'cache_signals' => array(
                'x-yperf-cache' => 'HIT',
                'x-yperf-cache-version' => sanitize_text_field((string) ($proof['second']['version'] ?? '')),
            ),
        );
    }

    /** @return array<string,mixed> */
    private static function yperfBrowserCacheControl(): array
    {
        if (!BrowserCacheController::enabled()) {
            return self::skip('YPerf Browser Cache', 'The YPerf-owned public HTML browser-cache policy is OFF.');
        }
        $proof = BrowserCacheController::prove();
        if (empty($proof['ok'])) {
            return array(
                'name' => 'YPerf Browser Cache',
                'status' => 'fail',
                'kind' => 'execution',
                'details' => 'Browser Cache is enabled but its same-origin response-header proof failed.',
            );
        }
        return array(
            'name' => 'YPerf Browser Cache',
            'status' => 'pass',
            'kind' => 'execution',
            'details' => 'A bounded same-origin request proved the YPerf public HTML browser-cache response policy.',
            'http_code' => (int) ($proof['http_code'] ?? 0),
            'cache_signals' => array(
                'x-yperf-browser-cache' => (string) ($proof['marker'] ?? ''),
                'cache-control' => (string) ($proof['cache_control'] ?? ''),
            ),
        );
    }

    /** @return array<string,mixed> */
    private static function yperfFrontendControl(): array
    {
        if (!FrontendOptimizer::enabled()) {
            return self::skip('YPerf frontend control', 'The local frontend-overhead control is OFF in the current performance profile.');
        }

        // The generic Homepage step runs before this control and may warm YPerf Page Cache.
        // Use a query-bearing same-origin URL for this signed diagnostic request so the
        // request reaches current WordPress/frontend execution and can expose the
        // probe-only CSS inline/ownership marker instead of being satisfied by cache.
        $frontendProofUrl = add_query_arg('yperf_runtime_probe', 'frontend', home_url('/'));
        $probe = self::probeUrl($frontendProofUrl, 'YPerf frontend control');
        $test = $probe['test'];
        if ((string) ($test['status'] ?? '') !== 'pass') {
            return $test;
        }

        $signals = is_array($test['cache_signals'] ?? null) ? $test['cache_signals'] : array();
        $headerProven = (string) ($signals['x-yperf-frontend'] ?? '') === 'active';
        $body = (string) ($probe['body'] ?? '');
        $emojiPayloadAbsent = stripos($body, 'wp-emoji') === false
            && stripos($body, 'emojiSettings') === false
            && stripos($body, 'print_emoji_detection_script') === false;

        if (!$headerProven && self::signalsShowCacheHit($signals)) {
            return self::skip('YPerf frontend control', 'The local control is ARMED, but this homepage sample was served by an existing cache layer and did not reach the current WordPress execution path. Purge/adoption proof is required before YPerf marks it ACTIVE.');
        }

        $delayPolicyEnabled = FrontendOptimizer::heavyJsDelayEnabled() || FrontendOptimizer::lateAnalyticsEnabled();
        $jsProof = self::parseJsDelayProof($body);
        $nativeDeferEnabled = FrontendOptimizer::nativeDeferEnabled();
        $nativeDeferProof = self::parseNativeDeferProof($body);
        $nativeDeferProven = !$nativeDeferEnabled || $nativeDeferProof !== array();
        if ($nativeDeferProof !== array()) {
            $test['native_defer_proof'] = $nativeDeferProof;
        }
        $delayMarkersProven = !$delayPolicyEnabled;
        if ($delayPolicyEnabled && $jsProof !== array()) {
            $candidateCount = (int) ($jsProof['candidates'] ?? 0);
            $delayMarkersProven = $candidateCount === 0
                || ((int) ($jsProof['delayed'] ?? 0) > 0 && !empty($jsProof['loader']));
            $test['js_delay_proof'] = $jsProof;
        } elseif ($delayPolicyEnabled) {
            $delayMarkersProven = stripos($body, 'data-yperf-delayed=') !== false && stripos($body, 'data-yperf-loader=') !== false;
        }
        $cssPolicyEnabled = FrontendOptimizer::inlineCssEnabled();
        $cssProof = self::parseCssInlineProof($body);
        $frontendPolicyHeader = strtolower(trim((string) ($signals['x-yperf-frontend-policy'] ?? '')));
        $paintFirstKnown = preg_match('/(?:^|;)paint_first=([01])(?:;|$)/', $frontendPolicyHeader, $paintFirstMatch) === 1;
        // CSS delivery is document-wide on every eligible public route. The
        // historical paint_first bit now describes only the early source-level
        // hint; final-document image discovery may still promote an LCP candidate.
        $cssRouteApplicable = true;
        $cssMarkersProven = !$cssPolicyEnabled || $cssProof !== array();
        $adaptiveLcpCandidate = stripos($body, 'data-yperf-lcp-priority="1"') !== false;
        if ($cssProof !== array()) {
            $test['css_inline_proof'] = $cssProof;
        }
        $test['frontend_policy'] = array(
            'paint_first_known' => $paintFirstKnown,
            'paint_first' => $paintFirstKnown ? ((string) ($paintFirstMatch[1] ?? '0') === '1' ? 1 : 0) : null,
            'css_route_applicable' => $cssRouteApplicable,
            'adaptive_lcp_candidate' => $adaptiveLcpCandidate ? 1 : 0,
        );
        $cssPreloadProof = self::parseCssPreloadProof($body);
        $test['css_preload_proof'] = $cssPreloadProof;
        $remainingStylesheets = self::remainingStylesheetLinks($body);
        if ($remainingStylesheets !== array()) {
            $test['remaining_stylesheets'] = $remainingStylesheets;
        }
        $test['proof_checks'] = array(
            'frontend_header' => $headerProven,
            'emoji_payload_absent' => $emojiPayloadAbsent,
            'delay_markers' => $delayMarkersProven,
            'native_defer_proof' => $nativeDeferProven,
            'css_route_applicable' => $cssRouteApplicable,
            'css_markers' => $cssMarkersProven,
            'adaptive_lcp_candidate' => $adaptiveLcpCandidate,
        );
        // Route gating is primarily proven by the transactional-route safety checks below.
        // A checkout bundle may legitimately remain in the HTML when WordPress/WooCommerce
        // aliases the handle differently; when Heavy JS Delay is active it can still be moved
        // off the initial execution path safely. Do not turn that harmless variation into a
        // false rollback trigger here.
        $test['status'] = ($headerProven && $emojiPayloadAbsent && $delayMarkersProven && $nativeDeferProven && $cssMarkersProven) ? 'pass' : 'fail';
        $test['kind'] = 'yperf-proof';
        $test['details'] = $test['status'] === 'pass'
            ? 'YPerf execution header is present; emoji overhead is absent; active frontend delivery policies are visible in the sampled HTML.'
            : 'Frontend optimization is enabled, but the sampled homepage did not prove every active YPerf frontend delivery policy.';
        if ($cssProof !== array()) {
            $test['details'] .= sprintf(
                ' CSS proof: inlined %d stylesheet(s) / %d bytes; ownership anchor %d, unique tail %d, current theme %d, local path %d; %d stylesheet link(s) remain.',
                (int) ($cssProof['inlined'] ?? 0),
                (int) ($cssProof['bytes'] ?? 0),
                (int) ($cssProof['anchor_hits'] ?? 0),
                (int) ($cssProof['tail_hits'] ?? 0),
                (int) ($cssProof['theme_hits'] ?? 0),
                (int) ($cssProof['local_hits'] ?? 0),
                (int) ($cssProof['remaining_stylesheets'] ?? 0)
            );
        }
        $test['details'] .= sprintf(' Early CSS preload(s): %d. Adaptive LCP candidate: %d.', (int) ($cssPreloadProof['count'] ?? 0), $adaptiveLcpCandidate ? 1 : 0);
        $test['details'] .= sprintf(
            ' Proof gates: frontend_header=%d; emoji_payload_absent=%d; delay_markers=%d; native_defer=%d; css_route=%s; css_markers=%d.',
            $headerProven ? 1 : 0,
            $emojiPayloadAbsent ? 1 : 0,
            $delayMarkersProven ? 1 : 0,
            $nativeDeferProven ? 1 : 0,
            $cssRouteApplicable ? 'active' : 'standby',
            $cssMarkersProven ? 1 : 0
        );
        if ($jsProof !== array()) {
            $test['details'] .= sprintf(
                ' JS proof: candidates=%d; delayed=%d; loader=%d.',
                (int) ($jsProof['candidates'] ?? 0),
                (int) ($jsProof['delayed'] ?? 0),
                !empty($jsProof['loader']) ? 1 : 0
            );
        }
        if ($nativeDeferProof !== array()) {
            $test['details'] .= sprintf(
                ' Native defer proof: enabled=%d; intended=%d; rendered=%d; blocked_intended=%d; head_external=%d; head_blocking=%d.',
                !empty($nativeDeferProof['enabled']) ? 1 : 0,
                (int) ($nativeDeferProof['intended'] ?? 0),
                (int) ($nativeDeferProof['rendered'] ?? 0),
                (int) ($nativeDeferProof['blocked_intended'] ?? 0),
                (int) ($nativeDeferProof['head_external'] ?? 0),
                (int) ($nativeDeferProof['head_blocking'] ?? 0)
            );
        }
        if ($remainingStylesheets !== array()) {
            $firstRemaining = (string) ($remainingStylesheets[0]['href'] ?? '');
            if ($firstRemaining !== '') {
                $test['details'] .= ' First remaining stylesheet: ' . $firstRemaining . '.';
                $firstReason = sanitize_key((string) ($remainingStylesheets[0]['skip_reason'] ?? ''));
                if ($firstReason !== '') {
                    $test['details'] .= ' Skip reason: ' . $firstReason . '.';
                }
            }
        }
        return $test;
    }

    /** @return array<int,array<string,mixed>> */
    private static function remainingStylesheetLinks(string $body): array
    {
        if ($body === '' || preg_match_all('~<link\b[^>]*>~i', $body, $links) < 1) {
            return array();
        }

        $remaining = array();
        foreach ((array) ($links[0] ?? array()) as $link) {
            $tag = (string) $link;
            $rel = strtolower(self::htmlTagAttribute($tag, 'rel'));
            if ($rel === '' || strpos(' ' . preg_replace('/\s+/', ' ', $rel) . ' ', ' stylesheet ') === false) {
                continue;
            }
            $href = html_entity_decode(self::htmlTagAttribute($tag, 'href'), ENT_QUOTES, 'UTF-8');
            if ($href === '') {
                continue;
            }
            if (strpos($href, '/') === 0 && strpos($href, '//') !== 0) {
                $href = home_url($href);
            }
            $href = esc_url_raw($href);
            if ($href === '') {
                continue;
            }
            $media = sanitize_text_field(self::htmlTagAttribute($tag, 'media'));
            $id = sanitize_key(self::htmlTagAttribute($tag, 'id'));
            $entry = array(
                'href' => $href,
                'same_origin' => self::sameOrigin($href),
                'media' => $media,
                'id' => $id,
                'integrity' => self::htmlTagAttribute($tag, 'integrity') !== '',
                'no_inline' => stripos($tag, 'data-yperf-no-inline') !== false,
                'skip_reason' => sanitize_key(self::htmlTagAttribute($tag, 'data-yperf-css-skip')),
            );
            $remaining[] = $entry;
            if (count($remaining) >= 8) {
                break;
            }
        }
        return $remaining;
    }

    private static function htmlTagAttribute(string $tag, string $attribute): string
    {
        $quoted = '~\b' . preg_quote($attribute, '~') . '\s*=\s*(["\'])(.*?)\1~is';
        if (preg_match($quoted, $tag, $m) === 1) {
            return trim((string) ($m[2] ?? ''));
        }
        $bare = '~\b' . preg_quote($attribute, '~') . '\s*=\s*([^\s>]+)~i';
        if (preg_match($bare, $tag, $m) === 1) {
            return trim((string) ($m[1] ?? ''));
        }
        return '';
    }

    /** @return array<string,mixed> */
    private static function parseJsDelayProof(string $body): array
    {
        if (preg_match('/<!--yperf-js-proof:candidates=(\d+);delayed=(\d+);loader=([01])-->/', $body, $m) !== 1) {
            return array();
        }
        return array(
            'candidates' => (int) ($m[1] ?? 0),
            'delayed' => (int) ($m[2] ?? 0),
            'loader' => (int) ($m[3] ?? 0) === 1,
        );
    }

    /** @return array<string,int|bool> */
    private static function parseNativeDeferProof(string $body): array
    {
        if (preg_match('/<!--yperf-native-defer-proof:enabled=([01]);intended=(\d+);rendered=(\d+);blocked_intended=(\d+);head_external=(\d+);head_blocking=(\d+)-->/', $body, $m) !== 1) {
            return array();
        }
        return array(
            'enabled' => (int) ($m[1] ?? 0) === 1,
            'intended' => (int) ($m[2] ?? 0),
            'rendered' => (int) ($m[3] ?? 0),
            'blocked_intended' => (int) ($m[4] ?? 0),
            'head_external' => (int) ($m[5] ?? 0),
            'head_blocking' => (int) ($m[6] ?? 0),
        );
    }

    /** @return array<string,int> */
    private static function parseCssPreloadProof(string $body): array
    {
        $count = preg_match_all('/<link\b[^>]*data-yperf-css-preload\s*=\s*(["\'])1\1[^>]*>/i', $body, $matches);
        return array('count' => ($count === false) ? 0 : max(0, (int) $count));
    }

    /** @return array<string,int> */
    private static function parseCssInlineProof(string $body): array
    {
        if (preg_match('/<!--yperf-css-proof:inlined=(\d+);bytes=(\d+);anchor=(\d+);tail=(\d+);theme=(\d+);local=(\d+);remaining=(\d+)-->/', $body, $m) !== 1) {
            return array();
        }
        return array(
            'inlined' => (int) ($m[1] ?? 0),
            'bytes' => (int) ($m[2] ?? 0),
            'anchor_hits' => (int) ($m[3] ?? 0),
            'tail_hits' => (int) ($m[4] ?? 0),
            'theme_hits' => (int) ($m[5] ?? 0),
            'local_hits' => (int) ($m[6] ?? 0),
            'remaining_stylesheets' => (int) ($m[7] ?? 0),
        );
    }

    /** @return array<string,mixed> */
    private static function yperfFirstHitControl(): array
    {
        if (get_option('yperf_deep_archive_guard', '0') !== '1') {
            return self::skip('YPerf first-hit protection', 'The WooCommerce first-hit query guard is OFF in the current performance profile.');
        }
        if (!function_exists('wc_get_page_permalink')) {
            return self::skip('YPerf first-hit protection', 'WooCommerce is not available on this site.');
        }
        $shop = (string) wc_get_page_permalink('shop');
        if ($shop === '') {
            return self::skip('YPerf first-hit protection', 'WooCommerce shop URL is not configured.');
        }

        $shopProbe = self::probeUrl($shop, 'YPerf first-hit discovery');
        if (($shopProbe['test']['status'] ?? 'fail') !== 'pass') {
            return self::skip('YPerf first-hit protection', 'The shop page could not be used to discover a real deep-archive request.');
        }
        $next = self::discoverPaginationUrl((string) $shopProbe['body']);
        if ($next === '') {
            return self::skip('YPerf first-hit protection', 'The guard is ARMED, but this shop exposes no real next-page URL, so no synthetic workload was invented.');
        }

        $probe = self::probeUrl($next, 'YPerf first-hit protection');
        $test = $probe['test'];
        if ((string) ($test['status'] ?? '') !== 'pass') {
            return $test;
        }
        $signals = is_array($test['cache_signals'] ?? null) ? $test['cache_signals'] : array();
        $protected = (string) ($signals['x-yperf-first-hit'] ?? '') === 'protected';
        if ($protected) {
            $test['status'] = 'pass';
            $test['kind'] = 'yperf-proof';
            $test['details'] = 'A real WooCommerce deep-archive request was intercepted by the bounded first-hit query guard.';
            return $test;
        }

        if (self::signalsShowCacheHit($signals)) {
            return self::skip('YPerf first-hit protection', 'The guard is ARMED, but this sampled deep-archive request was already served by a cache layer, so origin interception was not exercised.');
        }

        $test['status'] = 'fail';
        $test['kind'] = 'yperf-proof';
        $test['details'] = 'The guard is enabled and a real uncached deep-archive URL exists, but the execution proof header was not observed.';
        return $test;
    }

    /** @return array<string,mixed> */
    private static function wooShop(): array
    {
        if (!function_exists('wc_get_page_permalink')) {
            return self::skip('WooCommerce shop', 'WooCommerce is not available on this site.');
        }
        $shop = (string) wc_get_page_permalink('shop');
        if ($shop === '') {
            return self::skip('WooCommerce shop', 'WooCommerce shop URL is not configured.');
        }
        return self::probeUrl($shop, 'WooCommerce shop')['test'];
    }

    /** @return array<string,mixed> */
    private static function wooSort(): array
    {
        if (!function_exists('wc_get_page_permalink')) {
            return self::skip('WooCommerce sort URL', 'WooCommerce is not available on this site.');
        }
        $shop = (string) wc_get_page_permalink('shop');
        if ($shop === '') {
            return self::skip('WooCommerce sort URL', 'WooCommerce shop URL is not configured.');
        }
        $probe = self::probeUrl(add_query_arg('orderby', 'price', $shop), 'WooCommerce sort URL');
        $test = $probe['test'];
        $test['policy'] = 'bypass:query_string';
        return $test;
    }

    /** @return array<string,mixed> */
    private static function wooProduct(): array
    {
        if (!post_type_exists('product')) {
            return self::skip('WooCommerce product', 'WooCommerce product post type is not available.');
        }
        $productId = self::publishedProductId();
        if ($productId < 1) {
            return self::skip('WooCommerce product', 'No published product was found.');
        }
        $url = get_permalink($productId);
        if (!is_string($url) || $url === '') {
            return self::skip('WooCommerce product', 'A published product exists but its permalink could not be resolved.');
        }
        return self::probeUrl($url, 'WooCommerce product')['test'];
    }

    /** @return array<string,mixed> */
    private static function wooCategory(): array
    {
        if (!taxonomy_exists('product_cat')) {
            return self::skip('WooCommerce product category', 'WooCommerce product categories are not available.');
        }
        $url = self::productCategoryUrl();
        if ($url === '') {
            return self::skip('WooCommerce product category', 'No non-empty product category URL was found.');
        }
        return self::probeUrl($url, 'WooCommerce product category')['test'];
    }

    /** @return array<string,mixed> */
    private static function wooPagination(): array
    {
        if (!function_exists('wc_get_page_permalink')) {
            return self::skip('WooCommerce deep pagination', 'WooCommerce is not available on this site.');
        }
        $shop = (string) wc_get_page_permalink('shop');
        if ($shop === '') {
            return self::skip('WooCommerce deep pagination', 'WooCommerce shop URL is not configured.');
        }

        $shopProbe = self::probeUrl($shop, 'WooCommerce pagination discovery');
        if (($shopProbe['test']['status'] ?? 'fail') !== 'pass') {
            return self::skip('WooCommerce deep pagination', 'The shop page could not be used to discover a real pagination URL.');
        }
        $next = self::discoverPaginationUrl((string) $shopProbe['body']);
        if ($next === '') {
            return self::skip('WooCommerce deep pagination', 'The shop does not expose a real next-page URL, so no pagination request was invented.');
        }
        return self::probeUrl($next, 'WooCommerce deep pagination')['test'];
    }

    /** @return array<string,mixed> */
    private static function wooRoute(string $page, string $label): array
    {
        if (!function_exists('wc_get_page_permalink')) {
            return self::skip($label, 'WooCommerce is not available on this site.');
        }
        $url = (string) wc_get_page_permalink($page);
        if ($url === '') {
            return self::skip($label, 'WooCommerce page URL is not configured.');
        }
        return array(
            'name'    => $label,
            'status'  => 'pass',
            'kind'    => 'route-policy',
            'url'     => $url,
            'details' => 'YPerf policy requires BYPASS for this private WooCommerce route. No live request was sent.',
        );
    }

    /** @return array<string,mixed> */
    private static function yperfNativeDeferCanaryControl(): array
    {
        if (!FrontendOptimizer::enabled() || !FrontendOptimizer::nativeDeferEnabled()) {
            return self::skip('YPerf native defer canary', 'Native defer is not armed in the current performance profile.');
        }

        $url = add_query_arg('yperf_runtime_probe', 'native-defer', home_url('/'));
        $probe = self::probeUrl($url, 'YPerf native defer canary');
        $test = $probe['test'];
        if ((string) ($test['status'] ?? '') !== 'pass') {
            return $test;
        }

        $body = (string) ($probe['body'] ?? '');
        $proof = self::parseNativeDeferProof($body);
        if ($proof === array() || empty($proof['enabled'])) {
            $test['status'] = 'fail';
            $test['kind'] = 'yperf-canary';
            $test['details'] = 'The native-defer canary reached the current WordPress runtime, but no enabled canary proof was emitted.';
            return $test;
        }

        $test['native_defer_proof'] = $proof;
        $test['status'] = 'pass';
        $test['kind'] = 'yperf-canary';
        $intended = max(0, (int) ($proof['intended'] ?? 0));
        $rendered = max(0, (int) ($proof['rendered'] ?? 0));
        $blocked = max(0, (int) ($proof['blocked_intended'] ?? 0));
        $headBlocking = max(0, (int) ($proof['head_blocking'] ?? 0));
        $test['details'] = sprintf(
            'The isolated native-defer canary returned HTTP 200 and emitted execution proof: intended=%d; rendered=%d; blocked_intended=%d; head_blocking=%d.',
            $intended,
            $rendered,
            $blocked,
            $headBlocking
        );
        if ($headBlocking > 0 || ($intended > 0 && $rendered === 0)) {
            $test['attention'] = true;
            $test['attention_note'] = 'The canary is functional, while parser-blocking head scripts remain. Compare the matched control and native-defer browser canaries before promoting this behavior to ordinary traffic.';
        }
        return $test;
    }

    /** @return array<string,mixed> */
    private static function yperfSitemapIsolation(): array
    {
        $deadline = microtime(true) + self::SITEMAP_STEP_BUDGET_SECONDS;
        $budgetExhausted = false;
        $candidates = array(
            home_url('/sitemap_index.xml'),
            home_url('/sitemap.xml'),
            home_url('/wp-sitemap.xml'),
        );
        $samples = array();
        $rootBody = '';
        $rootUrl = '';
        $firstObservedUrl = '';

        // Sitemap health belongs to the producer (YSEO/WordPress), not YPerf.
        // Probe the bounded standard roots so one broken producer route cannot
        // prevent YPerf from proving its own non-interference boundary.
        foreach ($candidates as $candidate) {
            if (microtime(true) >= $deadline) {
                $budgetExhausted = true;
                break;
            }
            $sample = self::probeServiceUrl((string) $candidate, $deadline);
            if (!empty($sample['diagnostic_budget_exhausted'])) {
                $budgetExhausted = true;
            }
            $code = (int) ($sample['http_code'] ?? 0);
            if (($code === 0 || $code === 404) && empty($sample['edge_inconclusive'])) {
                continue;
            }
            if ($firstObservedUrl === '') {
                $firstObservedUrl = (string) ($sample['url'] ?? '');
            }
            $samples[] = $sample;
            if ($rootUrl === '' && $code >= 200 && $code < 400 && !empty($sample['xml_like'])) {
                $rootBody = (string) ($sample['body'] ?? '');
                $rootUrl = (string) ($sample['url'] ?? '');
            }
        }

        if ($samples === array()) {
            return self::skip('YPerf sitemap isolation', 'No standard same-origin sitemap root was discovered. Protected XML/service bypass remains covered by the deterministic request-policy check.');
        }

        if ($rootBody !== '' && preg_match_all('~<loc>\s*(.*?)\s*</loc>~is', $rootBody, $locMatches) > 0) {
            $seen = array();
            foreach ($samples as $sample) {
                $sampleUrl = (string) ($sample['url'] ?? '');
                if ($sampleUrl !== '') {
                    $seen[$sampleUrl] = true;
                }
            }
            foreach ((array) ($locMatches[1] ?? array()) as $rawLoc) {
                $loc = html_entity_decode(trim(wp_strip_all_tags((string) $rawLoc)), ENT_QUOTES, 'UTF-8');
                if ($loc === '' || isset($seen[$loc]) || !self::sameOrigin($loc)) {
                    continue;
                }
                $path = (string) wp_parse_url($loc, PHP_URL_PATH);
                if (!RequestPolicy::isProtectedServicePath($path)) {
                    continue;
                }
                if (microtime(true) >= $deadline) {
                    $budgetExhausted = true;
                    break;
                }
                $seen[$loc] = true;
                $childSample = self::probeServiceUrl($loc, $deadline);
                if (!empty($childSample['diagnostic_budget_exhausted'])) {
                    $budgetExhausted = true;
                }
                $samples[] = $childSample;
                if (count($samples) >= 9) {
                    break;
                }
            }
        }

        $touched = false;
        $producerAttention = false;
        $contentAttention = false;
        $edgeAttention = false;
        $edgeInconclusive = false;
        $badUrls = array();
        $firstProblem = '';
        foreach ($samples as $sample) {
            if (!empty($sample['yperf_touched'])) {
                $touched = true;
            }
            if (!empty($sample['edge_attention'])) {
                $edgeAttention = true;
            }
            if (!empty($sample['edge_inconclusive'])) {
                $edgeInconclusive = true;
                // A blocked public diagnostic plus an incomplete local proof
                // is not evidence that the sitemap producer itself is broken.
                continue;
            }
            $code = (int) ($sample['http_code'] ?? 0);
            $xmlLike = !empty($sample['xml_like']);
            if (!$xmlLike) {
                $contentAttention = true;
            }
            if ($code < 200 || $code >= 400 || !$xmlLike) {
                $producerAttention = true;
                $badUrl = (string) ($sample['url'] ?? '');
                $badUrls[] = $badUrl;
                if ($firstProblem === '') {
                    $reason = $code > 0 ? 'HTTP ' . $code : 'request error';
                    if (!$xmlLike) {
                        $reason .= ($reason !== '' ? ' / ' : '') . 'not XML-like';
                    }
                    $firstProblem = trim($reason . ' at ' . $badUrl);
                }
            }
        }

        $displayRoot = $rootUrl !== '' ? $rootUrl : $firstObservedUrl;
        $displayCode = 0;
        foreach ($samples as $sample) {
            if ((string) ($sample['url'] ?? '') === $displayRoot) {
                $displayCode = (int) ($sample['http_code'] ?? 0);
                break;
            }
        }
        $test = array(
            'name' => 'YPerf sitemap isolation',
            'status' => $touched ? 'fail' : 'pass',
            'kind' => 'sitemap-isolation',
            'url' => $displayRoot,
            'http_code' => $displayCode,
            'sitemap_proof' => array(
                'sampled' => count($samples),
                'yperf_touched' => $touched,
                'content_attention' => $contentAttention,
                'producer_attention' => $producerAttention,
                'edge_attention' => $edgeAttention,
                'edge_inconclusive' => $edgeInconclusive,
                'budget_seconds' => self::SITEMAP_STEP_BUDGET_SECONDS,
                'budget_exhausted' => $budgetExhausted,
                'samples' => $samples,
            ),
            'details' => $touched
                ? 'At least one sampled sitemap route was touched by a YPerf HTML delivery layer. This violates the sitemap isolation boundary.'
                : 'Sampled sitemap routes stayed outside YPerf Page Cache, Browser Cache and frontend HTML transforms.',
        );
        if ($producerAttention && !$touched) {
            $test['attention'] = true;
            $test['attention_note'] = 'Sitemap producer health needs attention outside YPerf; performance controls remain unblocked because YPerf did not touch the sampled service routes. First observed problem: ' . ($firstProblem !== '' ? $firstProblem : (string) ($badUrls[0] ?? $displayRoot));
            $test['details'] .= ' Sitemap producer health is reported separately as ATTENTION and does not fail YPerf isolation.';
        } elseif (($edgeAttention || $budgetExhausted) && !$touched) {
            $test['attention'] = true;
            if ($budgetExhausted) {
                $test['attention_note'] = __('Sitemap producer evidence reached the bounded Health Check time budget. YPerf isolation evidence remains available; refresh producer evidence later.', 'yablonsky-performance');
                $test['details'] .= ' ' . __('The sitemap diagnostic stayed inside its bounded execution budget.', 'yablonsky-performance');
            } else {
                $test['attention_note'] = $edgeInconclusive
                    ? 'The public edge blocked at least one sitemap self-probe and a bounded local-origin proof was inconclusive. Sitemap isolation is proven, but producer health remains unverified rather than failed.'
                    : 'The public edge blocked at least one sitemap self-probe, while the local origin returned the sitemap successfully. Sitemap producer health is good at origin; review edge bot/WAF policy separately.';
                $test['details'] .= ' Edge blocking is reported separately as ATTENTION and does not fail YPerf isolation.';
            }
        }
        return $test;
    }

    /** @return array<string,mixed> */
    private static function probeServiceUrl(string $url, float $deadline = 0.0): array
    {
        if (!self::sameOrigin($url)) {
            return array('url' => $url, 'http_code' => 0, 'body' => '', 'xml_like' => false, 'yperf_touched' => false);
        }
        if ($deadline <= 0.0) {
            $deadline = microtime(true) + self::SITEMAP_STEP_BUDGET_SECONDS;
        }
        $remaining = $deadline - microtime(true);
        if ($remaining <= 0.25) {
            return array(
                'url' => $url,
                'http_code' => 0,
                'body' => '',
                'content_type' => '',
                'xml_like' => false,
                'yperf_touched' => false,
                'proof_source' => 'diagnostic_budget_exhausted',
                'edge_inconclusive' => true,
                'diagnostic_budget_exhausted' => true,
            );
        }
        $publicTimeout = max(1, min(self::SITEMAP_PUBLIC_TIMEOUT_SECONDS, (int) ceil($remaining)));
        $probeCredentials = QueryCostTelemetry::credentials();
        $accept = 'application/xml,text/xml,application/rss+xml;q=0.9,*/*;q=0.1';
        $response = wp_safe_remote_get(
            $url,
            array(
                'timeout' => $publicTimeout,
                'redirection' => 3,
                'sslverify' => true,
                'limit_response_size' => 262144,
                'user-agent' => 'Yablonsky-Performance/' . YPERF_VERSION . ' SitemapIsolation',
                'headers' => array(
                    'Accept' => $accept,
                    'X-YPerf-Probe-Token' => (string) $probeCredentials['token'],
                    'X-YPerf-Probe-Signature' => (string) $probeCredentials['signature'],
                ),
            )
        );
        if (is_wp_error($response)) {
            QueryCostTelemetry::consume((string) $probeCredentials['token']);
            return array('url' => $url, 'http_code' => 0, 'body' => '', 'content_type' => '', 'xml_like' => false, 'yperf_touched' => false, 'error' => sanitize_text_field($response->get_error_message()));
        }

        $publicCode = (int) wp_remote_retrieve_response_code($response);
        $publicBody = (string) wp_remote_retrieve_body($response);
        $publicSignals = self::cacheSignals(wp_remote_retrieve_headers($response));
        $publicDelivery = self::deliverySignals($publicSignals, $publicBody);
        $effectiveResponse = $response;
        $proofSource = 'public_edge';
        $originCode = 0;
        $originMs = 0;
        $originError = '';
        $edgeAttention = false;
        $edgeInconclusive = false;

        if (in_array($publicCode, array(403, 429), true) && !empty($publicDelivery['cloudflare'])) {
            $edgeAttention = true;
            $remaining = $deadline - microtime(true);
            if ($remaining <= 0.25) {
                $edgeInconclusive = true;
                $proofSource = 'diagnostic_budget_exhausted';
                $originError = 'Health Check sitemap budget reached.';
            } else {
                $originTimeout = max(1, min(self::SITEMAP_ORIGIN_TIMEOUT_SECONDS, (int) ceil($remaining)));
                $origin = self::localOriginProbeUrl($url, $probeCredentials, $accept, $originTimeout);
                $originCode = (int) ($origin['http_code'] ?? 0);
                $originMs = max(0, (int) ($origin['ms'] ?? 0));
                $originError = sanitize_text_field((string) ($origin['error'] ?? ''));
                if ($originCode > 0 && isset($origin['response'])) {
                    $effectiveResponse = $origin['response'];
                    $proofSource = 'local_origin_after_edge_block';
                } else {
                    $edgeInconclusive = true;
                    $proofSource = 'edge_block_origin_inconclusive';
                }
            }
        }

        // This probe is about delivery isolation, not database cost. Consume and
        // discard any probe-bounded telemetry so no short-lived transient is left.
        QueryCostTelemetry::consume((string) $probeCredentials['token']);
        $code = (int) wp_remote_retrieve_response_code($effectiveResponse);
        $body = (string) wp_remote_retrieve_body($effectiveResponse);
        $headers = wp_remote_retrieve_headers($effectiveResponse);
        $signals = self::cacheSignals($headers);
        $contentType = strtolower(trim((string) wp_remote_retrieve_header($effectiveResponse, 'content-type')));
        $bodyStart = ltrim(substr($body, 0, 512));
        $xmlLike = strpos($contentType, 'xml') !== false
            || preg_match('~^(?:<\?xml\b|<(?:sitemapindex|urlset|rss|feed)\b)~i', $bodyStart) === 1;
        $yperfCache = strtoupper(trim((string) ($signals['x-yperf-cache'] ?? '')));
        $pageCacheTouched = in_array($yperfCache, array('HIT', 'HIT-WAIT', 'MISS'), true);
        $browserTouched = strtolower(trim((string) ($signals['x-yperf-browser-cache'] ?? ''))) === 'active';
        $frontendTouched = strtolower(trim((string) ($signals['x-yperf-frontend'] ?? ''))) === 'active';
        return array(
            'url' => $url,
            'http_code' => $code,
            'public_http_code' => $publicCode,
            'origin_http_code' => $originCode,
            'origin_ms' => $originMs,
            'origin_probe_error' => $originError,
            'proof_source' => $proofSource,
            'edge_attention' => $edgeAttention,
            'edge_inconclusive' => $edgeInconclusive,
            'diagnostic_budget_exhausted' => $proofSource === 'diagnostic_budget_exhausted' || microtime(true) >= $deadline,
            'content_type' => $contentType,
            'xml_like' => $xmlLike,
            'yperf_cache' => $yperfCache,
            'browser_cache' => $browserTouched,
            'frontend_transform' => $frontendTouched,
            'yperf_touched' => $pageCacheTouched || $browserTouched || $frontendTouched,
            'body' => $body,
        );
    }

    /** @return array<string,mixed> */
    private static function multilingual(): array
    {
        $homeProbe = self::probeUrl(home_url('/'), 'Multilingual discovery');
        if (($homeProbe['test']['status'] ?? 'fail') !== 'pass') {
            return self::skip('Multilingual alternate URL', 'The homepage could not be used for hreflang discovery.');
        }
        $alternate = self::firstAlternateLanguageUrl((string) $homeProbe['body']);
        if ($alternate === '') {
            return self::skip('Multilingual alternate URL', 'No same-origin hreflang alternate was exposed on the homepage.');
        }
        return self::probeUrl($alternate, 'Multilingual alternate URL')['test'];
    }

    /** @return array{test:array<string,mixed>,body:string} */
    private static function probeUrl(string $url, string $name): array
    {
        if (!self::sameOrigin($url)) {
            return array(
                'test' => self::skip($name, 'URL is not same-origin and was not requested.'),
                'body' => '',
            );
        }

        $probeCredentials = QueryCostTelemetry::credentials();
        $probeToken = (string) $probeCredentials['token'];
        $started = microtime(true);
        $response = wp_safe_remote_get(
            $url,
            array(
                'timeout'             => 8,
                'redirection'         => 3,
                'sslverify'           => true,
                'limit_response_size' => 262144,
                'user-agent'          => 'Yablonsky-Performance/' . YPERF_VERSION . ' RuntimeAcceptance',
                'headers'             => array(
                    'Accept' => 'text/html,application/xhtml+xml',
                    'X-YPerf-Probe-Token' => $probeToken,
                    'X-YPerf-Probe-Signature' => (string) $probeCredentials['signature'],
                ),
            )
        );
        $elapsedMs = (int) round((microtime(true) - $started) * 1000);

        if (is_wp_error($response)) {
            return array(
                'test' => array(
                    'name'    => $name,
                    'status'  => 'fail',
                    'kind'    => 'http',
                    'url'     => $url,
                    'details' => 'HTTP probe failed: ' . $response->get_error_message(),
                    'ms'      => $elapsedMs,
                ),
                'body' => '',
            );
        }

        $publicCode = (int) wp_remote_retrieve_response_code($response);
        $publicBody = (string) wp_remote_retrieve_body($response);
        $publicSignals = self::cacheSignals(wp_remote_retrieve_headers($response));
        $publicDeliverySignals = self::deliverySignals($publicSignals, $publicBody);

        $proofSource = 'public_edge';
        $edgeAttention = false;
        $edgeAttentionNote = '';
        $effectiveResponse = $response;
        $elapsedProofMs = $elapsedMs;
        $originCode = 0;
        $originMs = 0;
        $originError = '';

        if (
            in_array($publicCode, array(403, 429), true)
            && !empty($publicDeliverySignals['cloudflare'])
        ) {
            /*
             * Large production stores can take materially longer to render an
             * uncached local-origin page than the public edge takes to reject
             * this diagnostic user-agent. Keep the proof bounded, but give the
             * origin enough room to answer before declaring the check
             * inconclusive. This path is user-initiated Health Check only.
             */
            $origin = self::localOriginProbeUrl(
                $url,
                $probeCredentials,
                'text/html,application/xhtml+xml',
                15
            );
            $originCode = (int) ($origin['http_code'] ?? 0);
            $originMs = max(0, (int) ($origin['ms'] ?? 0));
            $originError = sanitize_text_field((string) ($origin['error'] ?? ''));

            if ($originCode > 0 && isset($origin['response'])) {
                $effectiveResponse = $origin['response'];
                $elapsedProofMs = $originMs > 0 ? $originMs : $elapsedMs;
                $proofSource = 'local_origin_after_edge_block';
                $edgeAttention = true;
                if ($originCode >= 200 && $originCode < 400) {
                    $edgeAttentionNote = sprintf(
                        'The public Cloudflare edge returned HTTP %d to the bounded YPerf self-probe, while the same request reached the local origin successfully with HTTP %d. Runtime execution is healthy; review edge bot/WAF policy separately.',
                        $publicCode,
                        $originCode
                    );
                } else {
                    $edgeAttentionNote = sprintf(
                        'The public Cloudflare edge returned HTTP %d to the bounded YPerf self-probe. The local origin was reached and returned HTTP %d, so the origin response is used for this Health Check result.',
                        $publicCode,
                        $originCode
                    );
                }
            } else {
                QueryCostTelemetry::consume($probeToken);
                $note = sprintf(
                    'The public Cloudflare edge returned HTTP %d to the bounded YPerf self-probe, and the bounded local-origin proof did not complete. This is diagnostic inconclusiveness, not evidence that YPerf or the public route is broken.',
                    $publicCode
                );
                if ($originError !== '') {
                    $note .= ' Origin probe: ' . $originError . '.';
                }
                return array(
                    'test' => array(
                        'name'          => $name,
                        'status'        => 'skip',
                        'kind'          => 'http-inconclusive',
                        'url'           => $url,
                        'http_code'     => $publicCode,
                        'public_http_code' => $publicCode,
                        'origin_http_code' => $originCode,
                        'origin_probe_error' => $originError,
                        'origin_ms'     => $originMs,
                        'proof_source'  => 'edge_block_origin_inconclusive',
                        'ms'            => $originMs > 0 ? $originMs : $elapsedMs,
                        'cache_signals' => $publicSignals,
                        'delivery_signals' => $publicDeliverySignals,
                        'attention'     => true,
                        'attention_note'=> $note,
                        'details'       => 'Public-edge verification was blocked and local-origin verification was inconclusive; no YPerf functional failure is asserted.',
                    ),
                    'body' => '',
                );
            }
        }

        $code = (int) wp_remote_retrieve_response_code($effectiveResponse);
        $body = (string) wp_remote_retrieve_body($effectiveResponse);
        $signals = self::cacheSignals(wp_remote_retrieve_headers($effectiveResponse));
        $deliverySignals = self::deliverySignals($signals, $body);
        $controlCode = 0;
        if ($code >= 500) {
            $controlCode = self::unsignedControlHttpCode($url);
        }
        $originMetrics = QueryCostTelemetry::consume($probeToken);
        if ($originMetrics === array()) {
            $telemetrySignal = sanitize_key((string) ($signals['x-yperf-origin-telemetry'] ?? ''));
            $originMetrics = array(
                'state' => $telemetrySignal === 'savequeries-disabled'
                    ? 'savequeries_disabled'
                    : (self::signalsShowCacheHit($signals) ? 'not_executed_cache_hit' : 'not_captured'),
                'scope' => 'signed_runtime_probe_only',
                'raw_sql_exported' => false,
            );
        }
        $ok = $code >= 200 && $code < 400;
        $cookieAttention = isset($signals['set-cookie']);
        $attention = $edgeAttention || $cookieAttention;
        $attentionNote = $edgeAttentionNote;
        if ($cookieAttention) {
            $attentionNote = trim($attentionNote . ' Origin response sets a cookie. Keep this visible during future cache-adoption decisions.');
        }

        return array(
            'test' => array(
                'name'          => $name,
                'status'        => $ok ? 'pass' : 'fail',
                'kind'          => 'http',
                'url'           => $url,
                'http_code'     => $code,
                'public_http_code' => $publicCode,
                'origin_http_code' => $originCode,
                'origin_probe_error' => $originError,
                'origin_ms'     => $originMs,
                'proof_source'  => $proofSource,
                'control_http_code' => $controlCode,
                'ms'            => $elapsedProofMs,
                'cache_signals' => $signals,
                'public_edge_signals' => $publicSignals,
                'delivery_signals' => $deliverySignals,
                'public_edge_delivery_signals' => $publicDeliverySignals,
                'origin_metrics' => $originMetrics,
                'attention'     => $attention,
                'attention_note'=> $attentionNote,
                'details'       => $ok
                    ? ($proofSource === 'public_edge'
                        ? 'Anonymous same-origin frontend request completed without a 4xx/5xx response.'
                        : 'The public edge blocked the bounded self-probe, but a same-origin local-origin proof completed successfully.')
                    : (($code >= 500 && $controlCode >= 200 && $controlCode < 400)
                        ? 'The signed YPerf instrumentation request failed, while an unsigned same-origin control request remained healthy. Treat this as a probe-instrumentation failure, not proof that the public frontend is down.'
                        : 'Unexpected frontend HTTP response.'),
            ),
            'body' => $body,
        );
    }

    /** @param array<string,string> $probeCredentials @return array<string,mixed> */
    private static function localOriginProbeUrl(
        string $url,
        array $probeCredentials,
        string $accept = 'text/html,application/xhtml+xml',
        int $timeout = 15
    ): array {
        $serverAddr = isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field(wp_unslash((string) $_SERVER['SERVER_ADDR'])) : '';
        $homeHost = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        if ($serverAddr === '' || $homeHost === '' || filter_var($serverAddr, FILTER_VALIDATE_IP) === false) {
            return array(
                'http_code' => 0,
                'ms' => 0,
                'error' => 'Local origin address is unavailable or invalid.',
            );
        }

        // Never send signed probe credentials to a publicly routed IP while
        // TLS certificate verification is disabled for the origin-IP request.
        // A public origin can still be checked via the normal verified route.
        if (!self::isTrustedOriginIp($serverAddr)) {
            return array(
                'http_code' => 0,
                'ms' => 0,
                'error' => 'Direct origin probe skipped: public origin IP requires verified TLS.',
            );
        }

        // Signed diagnostic credentials are valid only for the canonical site's
        // own URL. Never rewrite a foreign URL onto the local IP and attach
        // those credentials, even if a caller later becomes externally driven.
        $probeHost = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
        $probeScheme = strtolower((string) wp_parse_url($url, PHP_URL_SCHEME));
        if ($probeHost === '' || $probeHost !== $homeHost || !in_array($probeScheme, array('http', 'https'), true)) {
            return array(
                'http_code' => 0,
                'ms' => 0,
                'error' => 'Direct origin probe skipped: URL is not on the site origin.',
            );
        }

        $path = (string) wp_parse_url($url, PHP_URL_PATH);
        if ($path === '') {
            $path = '/';
        }
        $query = (string) wp_parse_url($url, PHP_URL_QUERY);
        $urlHost = strpos($serverAddr, ':') !== false ? '[' . $serverAddr . ']' : $serverAddr;
        $target = 'https://' . $urlHost . $path . ($query !== '' ? '?' . $query : '');
        $timeout = max(1, min(20, $timeout));

        $started = microtime(true);
        $response = wp_remote_get(
            $target,
            array(
                'timeout'             => $timeout,
                'redirection'         => 0,
                'sslverify'           => false,
                'limit_response_size' => 262144,
                'headers'             => array(
                    'Host' => $homeHost,
                    'User-Agent' => 'Yablonsky-Performance/' . YPERF_VERSION . ' OriginProbe',
                    'Accept' => $accept,
                    'X-YPerf-Probe-Token' => (string) ($probeCredentials['token'] ?? ''),
                    'X-YPerf-Probe-Signature' => (string) ($probeCredentials['signature'] ?? ''),
                ),
            )
        );
        $elapsedMs = (int) round((microtime(true) - $started) * 1000);
        if (is_wp_error($response)) {
            return array(
                'http_code' => 0,
                'ms' => $elapsedMs,
                'error' => sanitize_text_field($response->get_error_message()),
            );
        }

        return array(
            'response' => $response,
            'http_code' => (int) wp_remote_retrieve_response_code($response),
            'ms' => $elapsedMs,
            'error' => '',
        );
    }

    private static function unsignedControlHttpCode(string $url): int
    {
        $response = wp_safe_remote_get(
            $url,
            array(
                'timeout'             => 8,
                'redirection'         => 3,
                'sslverify'           => true,
                'limit_response_size' => 16384,
                'user-agent'          => 'Yablonsky-Performance/' . YPERF_VERSION . ' RuntimeControl',
                'headers'             => array('Accept' => 'text/html,application/xhtml+xml'),
            )
        );
        return is_wp_error($response) ? 0 : (int) wp_remote_retrieve_response_code($response);
    }

    /** @param array<string,string> $signals @return array<string,mixed> */
    private static function deliverySignals(array $signals, string $body): array
    {
        $server = strtolower(trim((string) ($signals['server'] ?? '')));
        $cfRay = trim((string) ($signals['cf-ray'] ?? ''));
        $encoding = strtolower(trim((string) ($signals['content-encoding'] ?? '')));
        $altSvc = strtolower((string) ($signals['alt-svc'] ?? ''));
        $speculation = trim((string) ($signals['speculation-rules'] ?? ''));

        $cloudflare = $server === 'cloudflare' || $cfRay !== '';
        $rocketLoader = stripos($body, 'rocket-loader.min.js') !== false
            || stripos($body, 'data-cf-settings') !== false;
        $http3Advertised = strpos($altSvc, 'h3=') !== false || strpos($altSvc, 'h3-') !== false;

        return array(
            'edge_provider' => $cloudflare ? 'cloudflare' : ($server !== '' ? $server : 'unknown'),
            'cloudflare' => $cloudflare,
            'content_encoding' => $encoding,
            'rocket_loader' => $rocketLoader,
            'speed_brain' => $speculation !== '',
            'http3_advertised' => $http3Advertised,
        );
    }

    /** @param array<string,string> $signals */
    private static function signalsShowCacheHit(array $signals): bool
    {
        foreach ($signals as $header => $rawValue) {
            $name = strtolower((string) $header);
            if ($name === 'x-yperf-cache-version') {
                continue;
            }
            $isCacheHeader = in_array($name, array('cf-cache-status', 'x-cache', 'x-cache-status', 'x-fastcgi-cache'), true)
                || preg_match('/^x-[a-z0-9-]*(?:cache|cache-status)$/', $name) === 1;
            if (!$isCacheHeader) {
                continue;
            }
            $value = strtoupper(trim((string) $rawValue));
            if (in_array($value, array('HIT', 'STALE', 'REVALIDATED', 'UPDATING'), true)) {
                return true;
            }
        }

        // YPerf's portable Page Cache intentionally serves a proven HIT before
        // WordPress/plugins bootstrap. Origin-only execution headers are absent
        // on that hot path by design, so this is a cache-served sample rather
        // than proof that the underlying local control failed.
        $yperf = strtoupper(trim((string) ($signals['x-yperf-cache'] ?? '')));
        return in_array($yperf, array('HIT', 'HIT-WAIT'), true);
    }

    /** @param mixed $headers @return array<string,string> */
    private static function cacheSignals($headers): array
    {
        $wanted = array('cache-control', 'age', 'content-encoding', 'x-cache', 'x-cache-status', 'x-fastcgi-cache', 'cf-cache-status', 'cf-ray', 'speculation-rules', 'alt-svc', 'vary', 'server', 'x-yperf-cache', 'x-yperf-cache-version', 'x-yperf-browser-cache', 'x-yperf-frontend', 'x-yperf-frontend-policy', 'x-yperf-first-hit', 'x-yperf-origin-telemetry');
        $signals = array();
        foreach ($wanted as $name) {
            $value = '';
            if (is_object($headers) && method_exists($headers, 'offsetGet')) {
                $value = (string) $headers->offsetGet($name);
            } elseif (is_array($headers) && isset($headers[$name])) {
                $value = (string) $headers[$name];
            }
            if ($value !== '') {
                $signals[$name] = sanitize_text_field($value);
            }
        }

        // Host-managed Nginx/FastCGI installations often expose a custom
        // X-<site>-Cache header. Capture such headers generically so YPerf can
        // recognize an existing page-cache authority without site-specific
        // configuration or server writes.
        $allHeaders = array();
        if ($headers instanceof \Traversable) {
            foreach ($headers as $key => $value) {
                $allHeaders[(string) $key] = $value;
            }
        } elseif (is_object($headers) && method_exists($headers, 'getAll')) {
            $candidateHeaders = $headers->getAll();
            if (is_array($candidateHeaders)) {
                $allHeaders = $candidateHeaders;
            }
        } elseif (is_array($headers)) {
            $allHeaders = $headers;
        }
        foreach ($allHeaders as $key => $rawValue) {
            $name = strtolower(sanitize_key((string) $key));
            if ($name === '' || isset($signals[$name]) || $name === 'x-yperf-cache' || $name === 'x-yperf-cache-version' || $name === 'cf-cache-status') {
                continue;
            }
            if (preg_match('/^x-[a-z0-9-]*(?:cache|cache-status)$/', $name) !== 1) {
                continue;
            }
            $value = is_array($rawValue) ? implode(',', array_map('strval', $rawValue)) : (string) $rawValue;
            if ($value !== '') {
                $signals[$name] = sanitize_text_field($value);
            }
        }

        $setCookie = '';
        if (is_object($headers) && method_exists($headers, 'offsetGet')) {
            $setCookie = (string) $headers->offsetGet('set-cookie');
        } elseif (is_array($headers) && isset($headers['set-cookie'])) {
            $setCookie = is_array($headers['set-cookie']) ? implode(';', $headers['set-cookie']) : (string) $headers['set-cookie'];
        }
        if ($setCookie !== '') {
            $signals['set-cookie'] = 'present';
        }
        return $signals;
    }

    private static function publishedProductId(): int
    {
        $ids = get_posts(
            array(
                'post_type'              => 'product',
                'post_status'            => 'publish',
                'posts_per_page'         => 1,
                'orderby'                => 'ID',
                'order'                  => 'DESC',
                'fields'                 => 'ids',
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            )
        );
        return isset($ids[0]) ? (int) $ids[0] : 0;
    }

    private static function productCategoryUrl(): string
    {
        $terms = get_terms(
            array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => true,
                'number'     => 1,
                'orderby'    => 'count',
                'order'      => 'DESC',
            )
        );
        if (is_wp_error($terms) || !isset($terms[0])) {
            return '';
        }
        $url = get_term_link($terms[0]);
        return is_string($url) ? $url : '';
    }

    private static function discoverPaginationUrl(string $html): string
    {
        if ($html === '') {
            return '';
        }

        if (preg_match_all('~<link\\b[^>]*>~i', $html, $links) > 0) {
            foreach ((array) ($links[0] ?? array()) as $link) {
                if (preg_match('~\\brel=["\\\'][^"\\\']*next[^"\\\']*["\\\']~i', (string) $link) !== 1
                    || preg_match('~\\bhref=["\\\']([^"\\\']+)["\\\']~i', (string) $link, $href) !== 1
                ) {
                    continue;
                }
                $candidate = self::normalizeDiscoveredUrl((string) $href[1]);
                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        if (preg_match_all('~<a\\b[^>]*>.*?</a>~is', $html, $anchors) > 0) {
            foreach ((array) ($anchors[0] ?? array()) as $anchor) {
                if (preg_match('~\\bhref=["\\\']([^"\\\']+)["\\\']~i', (string) $anchor, $href) !== 1) {
                    continue;
                }
                $looksLikePagination = preg_match('~page-numbers|rel=["\\\']next["\\\']|>\\s*2\\s*<~i', (string) $anchor) === 1;
                if (!$looksLikePagination) {
                    continue;
                }
                $candidate = self::normalizeDiscoveredUrl((string) $href[1]);
                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }
        return '';
    }

    private static function normalizeDiscoveredUrl(string $raw): string
    {
        $candidate = esc_url_raw(html_entity_decode($raw, ENT_QUOTES, 'UTF-8'));
        if ($candidate === '') {
            return '';
        }
        if (strpos($candidate, '/') === 0 && strpos($candidate, '//') !== 0) {
            $candidate = home_url($candidate);
        }
        return self::sameOrigin($candidate) ? $candidate : '';
    }

    private static function firstAlternateLanguageUrl(string $html): string
    {
        if ($html === '' || preg_match_all('~<link\\b[^>]*>~i', $html, $links) < 1) {
            return '';
        }

        $home = untrailingslashit(home_url('/'));
        foreach ((array) ($links[0] ?? array()) as $link) {
            if (preg_match('~\\brel=["\\\']alternate["\\\']~i', (string) $link) !== 1
                || preg_match('~\\bhreflang=["\\\'][^"\\\']+["\\\']~i', (string) $link) !== 1
                || preg_match('~\\bhref=["\\\']([^"\\\']+)["\\\']~i', (string) $link, $href) !== 1
            ) {
                continue;
            }
            $candidate = self::normalizeDiscoveredUrl((string) $href[1]);
            if ($candidate !== '' && untrailingslashit($candidate) !== $home) {
                return $candidate;
            }
        }
        return '';
    }

    private static function sameOrigin(string $url): bool
    {
        $targetHost = wp_parse_url($url, PHP_URL_HOST);
        $homeHost = wp_parse_url(home_url('/'), PHP_URL_HOST);
        if (!is_string($targetHost) || !is_string($homeHost) || $targetHost === '' || $homeHost === '') {
            return false;
        }
        return strtolower($targetHost) === strtolower($homeHost);
    }

    /** @param array<string,mixed> $test @return array<string,mixed> */
    private static function normalizeTest(array $test): array
    {
        $name = sanitize_text_field((string) ($test['name'] ?? ''));
        if ($name === '') {
            return array();
        }
        $status = sanitize_key((string) ($test['status'] ?? 'fail'));
        if (!in_array($status, array('pass', 'fail', 'skip'), true)) {
            $status = 'fail';
        }

        $clean = array(
            'name'    => $name,
            'status'  => $status,
            'kind'    => sanitize_key((string) ($test['kind'] ?? 'unknown')),
            'details' => sanitize_text_field((string) ($test['details'] ?? '')),
        );
        if (isset($test['url'])) {
            $clean['url'] = esc_url_raw((string) $test['url']);
        }
        if (isset($test['http_code'])) {
            $clean['http_code'] = max(0, (int) $test['http_code']);
        }
        if (isset($test['control_http_code']) && (int) $test['control_http_code'] > 0) {
            $clean['control_http_code'] = max(0, (int) $test['control_http_code']);
        }
        if (isset($test['public_http_code'])) {
            $clean['public_http_code'] = max(0, (int) $test['public_http_code']);
        }
        if (isset($test['origin_http_code'])) {
            $clean['origin_http_code'] = max(0, (int) $test['origin_http_code']);
        }
        if (isset($test['origin_ms'])) {
            $clean['origin_ms'] = max(0, (int) $test['origin_ms']);
        }
        if (isset($test['proof_source'])) {
            $clean['proof_source'] = sanitize_key((string) $test['proof_source']);
        }
        if (isset($test['provider']) && (string) $test['provider'] !== '') {
            $clean['provider'] = sanitize_text_field((string) $test['provider']);
        }
        if (isset($test['external_cache_header']) && (string) $test['external_cache_header'] !== '') {
            $clean['external_cache_header'] = sanitize_key((string) $test['external_cache_header']);
        }
        if (isset($test['external_cache_value']) && (string) $test['external_cache_value'] !== '') {
            $clean['external_cache_value'] = strtoupper(sanitize_text_field((string) $test['external_cache_value']));
        }
        if (isset($test['origin_probe_error']) && (string) $test['origin_probe_error'] !== '') {
            $clean['origin_probe_error'] = sanitize_text_field((string) $test['origin_probe_error']);
        }
        if (isset($test['ms'])) {
            $clean['ms'] = max(0, (int) $test['ms']);
        }
        if (isset($test['policy'])) {
            $clean['policy'] = sanitize_text_field((string) $test['policy']);
        }
        if (!empty($test['attention'])) {
            $clean['attention'] = true;
            $clean['attention_note'] = sanitize_text_field((string) ($test['attention_note'] ?? 'Attention required.'));
        }
        if (isset($test['managed_runtime']) && is_array($test['managed_runtime'])) {
            $runtime = $test['managed_runtime'];
            $clean['managed_runtime'] = array(
                'schema' => max(0, (int) ($runtime['schema'] ?? 0)),
                'version' => sanitize_text_field((string) ($runtime['version'] ?? '')),
                'loader_present' => !empty($runtime['loader_present']),
                'loader_owned' => !empty($runtime['loader_owned']),
                'loader_version' => sanitize_text_field((string) ($runtime['loader_version'] ?? '')),
                'loader_version_current' => !empty($runtime['loader_version_current']),
                'runtime_source_present' => !empty($runtime['runtime_source_present']),
                'runtime_active_this_request' => !empty($runtime['runtime_active_this_request']),
                'runtime_version' => sanitize_text_field((string) ($runtime['runtime_version'] ?? '')),
                'single_mu_bootstrap' => !empty($runtime['single_mu_bootstrap']),
                'execution_hook' => sanitize_key((string) ($runtime['execution_hook'] ?? '')),
                'execution_priority' => (int) ($runtime['execution_priority'] ?? 0),
                'coordination_contract' => sanitize_key((string) ($runtime['coordination_contract'] ?? '')),
                'security_priority_reserved' => (int) ($runtime['security_priority_reserved'] ?? 0),
                'performance_priority' => (int) ($runtime['performance_priority'] ?? 0),
            );
        }
        if (isset($test['origin_admission']) && is_array($test['origin_admission'])) {
            $admission = $test['origin_admission'];
            $clean['origin_admission'] = array(
                'available' => !empty($admission['available']),
                'configured' => !empty($admission['configured']),
                'mode' => sanitize_key((string) ($admission['mode'] ?? '')),
                'slots' => max(0, (int) ($admission['slots'] ?? 0)),
                'retry_after_seconds' => max(0, (int) ($admission['retry_after_seconds'] ?? 0)),
                'lease_seconds' => max(0, (int) ($admission['lease_seconds'] ?? 0)),
                'current_request_state' => sanitize_key((string) ($admission['current_request_state'] ?? '')),
                'current_request_slot' => (int) ($admission['current_request_slot'] ?? -1),
                'current_request_backend' => sanitize_key((string) ($admission['current_request_backend'] ?? '')),
                'third_party_service_required' => !empty($admission['third_party_service_required']),
                'execution_hook' => sanitize_key((string) ($admission['execution_hook'] ?? '')),
                'execution_priority' => (int) ($admission['execution_priority'] ?? 0),
                'security_precedes_capacity_priority' => (int) ($admission['security_precedes_capacity_priority'] ?? 0),
                'coordination_contract' => sanitize_key((string) ($admission['coordination_contract'] ?? '')),
            );
        }
        if (isset($test['background_workload']) && is_array($test['background_workload'])) {
            $workload = $test['background_workload'];
            $clean['background_workload'] = array(
                'provider' => sanitize_key((string) ($workload['provider'] ?? '')),
                'pending' => max(0, (int) ($workload['pending'] ?? 0)),
                'past_due' => max(0, (int) ($workload['past_due'] ?? 0)),
                'failed' => max(0, (int) ($workload['failed'] ?? 0)),
                'complete' => max(0, (int) ($workload['complete'] ?? 0)),
                'in_progress' => max(0, (int) ($workload['in_progress'] ?? 0)),
                'read_only' => !empty($workload['read_only']),
                'state' => sanitize_key((string) ($workload['state'] ?? '')),
            );
        }
        if (isset($test['cache_signals']) && is_array($test['cache_signals'])) {
            $signals = array();
            foreach ($test['cache_signals'] as $key => $value) {
                $signals[sanitize_key((string) $key)] = sanitize_text_field((string) $value);
            }
            if ($signals !== array()) {
                $clean['cache_signals'] = $signals;
            }
        }
        if (isset($test['delivery_signals']) && is_array($test['delivery_signals'])) {
            $delivery = $test['delivery_signals'];
            $clean['delivery_signals'] = array(
                'edge_provider' => sanitize_key((string) ($delivery['edge_provider'] ?? 'unknown')),
                'cloudflare' => !empty($delivery['cloudflare']),
                'content_encoding' => sanitize_key((string) ($delivery['content_encoding'] ?? '')),
                'rocket_loader' => !empty($delivery['rocket_loader']),
                'speed_brain' => !empty($delivery['speed_brain']),
                'http3_advertised' => !empty($delivery['http3_advertised']),
            );
        }
        if (isset($test['origin_metrics']) && is_array($test['origin_metrics'])) {
            $clean['origin_metrics'] = self::normalizeOriginMetrics($test['origin_metrics']);
        }
        if (isset($test['js_delay_proof']) && is_array($test['js_delay_proof'])) {
            $proof = $test['js_delay_proof'];
            $clean['js_delay_proof'] = array(
                'candidates' => max(0, (int) ($proof['candidates'] ?? 0)),
                'delayed' => max(0, (int) ($proof['delayed'] ?? 0)),
                'loader' => !empty($proof['loader']),
            );
        }
        if (isset($test['native_defer_proof']) && is_array($test['native_defer_proof'])) {
            $proof = $test['native_defer_proof'];
            $clean['native_defer_proof'] = array(
                'enabled' => !empty($proof['enabled']),
                'intended' => max(0, (int) ($proof['intended'] ?? 0)),
                'rendered' => max(0, (int) ($proof['rendered'] ?? 0)),
                'blocked_intended' => max(0, (int) ($proof['blocked_intended'] ?? 0)),
                'head_external' => max(0, (int) ($proof['head_external'] ?? 0)),
                'head_blocking' => max(0, (int) ($proof['head_blocking'] ?? 0)),
            );
        }
        if (isset($test['css_inline_proof']) && is_array($test['css_inline_proof'])) {
            $proof = $test['css_inline_proof'];
            $clean['css_inline_proof'] = array(
                'inlined' => max(0, (int) ($proof['inlined'] ?? 0)),
                'bytes' => max(0, (int) ($proof['bytes'] ?? 0)),
                'anchor_hits' => max(0, (int) ($proof['anchor_hits'] ?? 0)),
                'tail_hits' => max(0, (int) ($proof['tail_hits'] ?? 0)),
                'theme_hits' => max(0, (int) ($proof['theme_hits'] ?? 0)),
                'local_hits' => max(0, (int) ($proof['local_hits'] ?? 0)),
                'remaining_stylesheets' => max(0, (int) ($proof['remaining_stylesheets'] ?? 0)),
            );
        }
        if (isset($test['css_preload_proof']) && is_array($test['css_preload_proof'])) {
            $clean['css_preload_proof'] = array(
                'count' => max(0, (int) ($test['css_preload_proof']['count'] ?? 0)),
            );
        }
        if (isset($test['proof_checks']) && is_array($test['proof_checks'])) {
            $checks = $test['proof_checks'];
            $clean['proof_checks'] = array(
                'frontend_header' => !empty($checks['frontend_header']),
                'emoji_payload_absent' => !empty($checks['emoji_payload_absent']),
                'delay_markers' => !empty($checks['delay_markers']),
                'css_route_applicable' => !empty($checks['css_route_applicable']),
                'css_markers' => !empty($checks['css_markers']),
                'adaptive_lcp_candidate' => !empty($checks['adaptive_lcp_candidate']),
            );
        }
        if (isset($test['sitemap_proof']) && is_array($test['sitemap_proof'])) {
            $proof = $test['sitemap_proof'];
            $cleanSamples = array();
            foreach (array_slice((array) ($proof['samples'] ?? array()), 0, 9) as $sample) {
                if (!is_array($sample)) {
                    continue;
                }
                $cleanSamples[] = array(
                    'url' => esc_url_raw((string) ($sample['url'] ?? '')),
                    'http_code' => max(0, (int) ($sample['http_code'] ?? 0)),
                    'public_http_code' => max(0, (int) ($sample['public_http_code'] ?? 0)),
                    'origin_http_code' => max(0, (int) ($sample['origin_http_code'] ?? 0)),
                    'origin_ms' => max(0, (int) ($sample['origin_ms'] ?? 0)),
                    'proof_source' => sanitize_key((string) ($sample['proof_source'] ?? '')),
                    'origin_probe_error' => sanitize_text_field((string) ($sample['origin_probe_error'] ?? '')),
                    'edge_attention' => !empty($sample['edge_attention']),
                    'edge_inconclusive' => !empty($sample['edge_inconclusive']),
                    'diagnostic_budget_exhausted' => !empty($sample['diagnostic_budget_exhausted']),
                    'content_type' => sanitize_text_field((string) ($sample['content_type'] ?? '')),
                    'xml_like' => !empty($sample['xml_like']),
                    'yperf_cache' => sanitize_text_field((string) ($sample['yperf_cache'] ?? '')),
                    'browser_cache' => !empty($sample['browser_cache']),
                    'frontend_transform' => !empty($sample['frontend_transform']),
                    'yperf_touched' => !empty($sample['yperf_touched']),
                );
            }
            $clean['sitemap_proof'] = array(
                'sampled' => max(0, (int) ($proof['sampled'] ?? count($cleanSamples))),
                'yperf_touched' => !empty($proof['yperf_touched']),
                'content_attention' => !empty($proof['content_attention']),
                'producer_attention' => !empty($proof['producer_attention']),
                'edge_attention' => !empty($proof['edge_attention']),
                'edge_inconclusive' => !empty($proof['edge_inconclusive']),
                'budget_seconds' => max(0, (int) ($proof['budget_seconds'] ?? 0)),
                'budget_exhausted' => !empty($proof['budget_exhausted']),
                'samples' => $cleanSamples,
            );
        }
        if (isset($test['remaining_stylesheets']) && is_array($test['remaining_stylesheets'])) {
            $remaining = array();
            foreach (array_slice($test['remaining_stylesheets'], 0, 8) as $stylesheet) {
                if (!is_array($stylesheet)) {
                    continue;
                }
                $href = esc_url_raw((string) ($stylesheet['href'] ?? ''));
                if ($href === '') {
                    continue;
                }
                $remaining[] = array(
                    'href' => $href,
                    'same_origin' => !empty($stylesheet['same_origin']),
                    'media' => sanitize_text_field((string) ($stylesheet['media'] ?? '')),
                    'id' => sanitize_key((string) ($stylesheet['id'] ?? '')),
                    'integrity' => !empty($stylesheet['integrity']),
                    'no_inline' => !empty($stylesheet['no_inline']),
                    'skip_reason' => sanitize_key((string) ($stylesheet['skip_reason'] ?? '')),
                );
            }
            if ($remaining !== array()) {
                $clean['remaining_stylesheets'] = $remaining;
            }
        }
        return $clean;
    }

    /** @param array<string,mixed> $metrics @return array<string,mixed> */
    private static function normalizeOriginMetrics(array $metrics): array
    {
        $state = sanitize_key((string) ($metrics['state'] ?? 'not_captured'));
        if ($state !== 'measured') {
            return array(
                'state' => in_array($state, array('not_executed_cache_hit', 'not_captured', 'savequeries_disabled'), true) ? $state : 'not_captured',
                'scope' => 'signed_runtime_probe_only',
                'raw_sql_exported' => false,
            );
        }
        $families = is_array($metrics['query_families'] ?? null) ? $metrics['query_families'] : array();
        $cleanFamilies = array();
        foreach (array('posts', 'postmeta', 'terms', 'options', 'woocommerce_lookup', 'other') as $key) {
            $cleanFamilies[$key] = max(0, (int) ($families[$key] ?? 0));
        }
        return array(
            'state' => 'measured',
            'query_count' => max(0, (int) ($metrics['query_count'] ?? 0)),
            'db_time_ms' => max(0.0, round((float) ($metrics['db_time_ms'] ?? 0), 2)),
            'max_query_ms' => max(0.0, round((float) ($metrics['max_query_ms'] ?? 0), 2)),
            'slow_queries_25ms' => max(0, (int) ($metrics['slow_queries_25ms'] ?? 0)),
            'slow_queries_100ms' => max(0, (int) ($metrics['slow_queries_100ms'] ?? 0)),
            'sql_calc_found_rows_count' => max(0, (int) ($metrics['sql_calc_found_rows_count'] ?? 0)),
            'select_count' => max(0, (int) ($metrics['select_count'] ?? 0)),
            'write_count' => max(0, (int) ($metrics['write_count'] ?? 0)),
            'query_families' => $cleanFamilies,
            'hotspot_schema' => 1,
            'hotspots' => QueryCostTelemetry::sanitizeHotspots($metrics['hotspots'] ?? array()),
            'max_query_family' => sanitize_key((string) ($metrics['max_query_family'] ?? 'other')),
            'max_query_signature_sha256' => preg_match('/^[a-f0-9]{64}$/', (string) ($metrics['max_query_signature_sha256'] ?? '')) === 1 ? (string) $metrics['max_query_signature_sha256'] : '',
            'php_elapsed_ms' => max(0.0, round((float) ($metrics['php_elapsed_ms'] ?? 0), 2)),
            'peak_memory_mb' => max(0.0, round((float) ($metrics['peak_memory_mb'] ?? 0), 2)),
            'scope' => 'signed_runtime_probe_only',
            'raw_sql_exported' => false,
        );
    }

    /** @param array<int,array<string,mixed>> $tests @return array<string,mixed> */
    private static function originCostSummary(array $tests): array
    {
        $measured = 0;
        $maxDbMs = 0.0;
        $maxQueryMs = 0.0;
        $maxQueries = 0;
        $slow25 = 0;
        $slow100 = 0;
        $foundRows = 0;
        $heaviestRoute = '';
        foreach ($tests as $test) {
            $metrics = is_array($test['origin_metrics'] ?? null) ? $test['origin_metrics'] : array();
            if ((string) ($metrics['state'] ?? '') !== 'measured') {
                continue;
            }
            ++$measured;
            $dbMs = (float) ($metrics['db_time_ms'] ?? 0);
            if ($dbMs > $maxDbMs) {
                $maxDbMs = $dbMs;
                $heaviestRoute = sanitize_text_field((string) ($test['name'] ?? ''));
            }
            $maxQueryMs = max($maxQueryMs, (float) ($metrics['max_query_ms'] ?? 0));
            $maxQueries = max($maxQueries, (int) ($metrics['query_count'] ?? 0));
            $slow25 += (int) ($metrics['slow_queries_25ms'] ?? 0);
            $slow100 += (int) ($metrics['slow_queries_100ms'] ?? 0);
            $foundRows += (int) ($metrics['sql_calc_found_rows_count'] ?? 0);
        }
        return array(
            'state' => $measured > 0 ? 'measured' : 'not_measured',
            'measured_routes' => $measured,
            'max_db_time_ms' => round($maxDbMs, 2),
            'max_single_query_ms' => round($maxQueryMs, 2),
            'max_query_count' => $maxQueries,
            'slow_queries_25ms_total' => $slow25,
            'slow_queries_100ms_total' => $slow100,
            'sql_calc_found_rows_total' => $foundRows,
            'heaviest_route' => $heaviestRoute,
            'scope' => 'signed_runtime_probe_only',
            'normal_traffic_overhead' => false,
            'raw_sql_exported' => false,
        );
    }

    /** @return array<string,mixed> */
    private static function skip(string $name, string $details): array
    {
        return array(
            'name'    => $name,
            'status'  => 'skip',
            'kind'    => 'discovery',
            'details' => $details,
        );
    }
}
