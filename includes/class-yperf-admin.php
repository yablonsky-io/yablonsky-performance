<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class Admin
{
    public static function register(): void
    {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_init', array(__CLASS__, 'settings'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueueAssets'));
        add_action('admin_post_yperf_capture_adoption', array(__CLASS__, 'captureAdoption'));
        add_action('admin_post_yperf_run_runtime_acceptance', array(__CLASS__, 'runRuntimeAcceptance'));
        add_action('admin_post_yperf_download_sql_hotspots', array(__CLASS__, 'downloadSqlHotspots'));
        add_action('admin_post_yperf_run_environment_check', array(__CLASS__, 'runEnvironmentCheck'));
        add_action('admin_post_yperf_import_host_snapshot', array(__CLASS__, 'importHostSnapshot'));
        add_action('admin_post_yperf_clear_host_snapshot', array(__CLASS__, 'clearHostSnapshot'));
        add_action('admin_post_yperf_download_host_inspector', array(__CLASS__, 'downloadHostInspector'));
        add_action('admin_post_yperf_adopt_native_page_cache', array(__CLASS__, 'adoptNativePageCache'));
        add_action('admin_post_yperf_release_native_page_cache', array(__CLASS__, 'releaseNativePageCache'));
        add_action('admin_post_yperf_refresh_page_cache_artifact', array(__CLASS__, 'refreshPageCacheArtifact'));
        add_action('admin_post_yperf_reserve_object_cache', array(__CLASS__, 'reserveObjectCache'));
        add_action('admin_post_yperf_release_object_cache_reservation', array(__CLASS__, 'releaseObjectCacheReservation'));
        add_action('admin_post_yperf_activate_object_cache', array(__CLASS__, 'activateObjectCache'));
        add_action('admin_post_yperf_finalize_object_cache', array(__CLASS__, 'finalizeObjectCache'));
        add_action('admin_post_yperf_rollback_object_cache', array(__CLASS__, 'rollbackObjectCache'));
        add_action('admin_post_yperf_adopt_origin_budget', array(__CLASS__, 'adoptOriginBudget'));
        add_action('admin_post_yperf_release_origin_budget', array(__CLASS__, 'releaseOriginBudget'));
        add_action('admin_post_yperf_apply_performance_plan', array(__CLASS__, 'applyPerformancePlan'));
        add_action('admin_post_yperf_run_autopilot', array(__CLASS__, 'runAutopilot'));
        add_action('admin_post_yperf_save_cloudflare', array(__CLASS__, 'saveCloudflare'));
        add_action('admin_post_yperf_inspect_cloudflare', array(__CLASS__, 'inspectCloudflare'));
        add_action('admin_post_yperf_apply_cloudflare', array(__CLASS__, 'applyCloudflare'));
        add_action('admin_post_yperf_rollback_cloudflare', array(__CLASS__, 'rollbackCloudflare'));
        add_action('admin_post_yperf_verify_cloudflare_purge', array(__CLASS__, 'verifyCloudflarePurge'));
        add_action('admin_post_yperf_save_ai_settings', array(__CLASS__, 'saveAISettings'));
        add_action('admin_post_yperf_verify_ai_connection', array(__CLASS__, 'verifyAIConnection'));
        add_action('admin_post_yperf_run_ai_advisor', array(__CLASS__, 'runAIAdvisor'));
        add_action('admin_post_yperf_apply_ai_advisor', array(__CLASS__, 'applyAIAdvisor'));
        add_action('admin_post_yperf_ask_ai_advisor', array(__CLASS__, 'askAIAdvisor'));
        add_action('admin_post_yperf_cacheability_inspect', array(__CLASS__, 'runCacheabilityInspector'));
        add_action('admin_post_yperf_manual_purge', array(__CLASS__, 'runManualPurge'));
        add_action('admin_post_yperf_toolbar_purge', array(__CLASS__, 'toolbarPurge'));
        add_action('admin_post_yperf_toolbar_optimize', array(__CLASS__, 'toolbarOptimize'));
        add_action('admin_post_yperf_toolbar_prewarm', array(__CLASS__, 'toolbarPrewarm'));
        add_action('admin_post_yperf_export_settings', array(__CLASS__, 'exportSettings'));
        add_action('admin_post_yperf_import_settings', array(__CLASS__, 'importSettings'));
        add_action('admin_post_yperf_control_feature', array(__CLASS__, 'controlFeature'));
        add_action('admin_post_yperf_migrate_w3tc', array(__CLASS__, 'migrateW3tc'));
        add_action('admin_post_yperf_rollback_w3tc', array(__CLASS__, 'rollbackW3tc'));
        add_action('admin_post_yperf_dismiss_recommendation', array(__CLASS__, 'dismissRecommendation'));
        add_action('wp_ajax_yperf_environment_refresh', array(__CLASS__, 'ajaxEnvironmentRefresh'));
        add_action('wp_ajax_yperf_benchmark_step', array(__CLASS__, 'ajaxBenchmarkStep'));
        add_action('wp_ajax_yperf_benchmark_finish', array(__CLASS__, 'ajaxBenchmarkFinish'));
        add_action('wp_ajax_yperf_prewarm_step', array(__CLASS__, 'ajaxPrewarmStep'));
        add_action('wp_ajax_yperf_prewarm_finish', array(__CLASS__, 'ajaxPrewarmFinish'));
        add_action('wp_ajax_yperf_runtime_acceptance_step', array(__CLASS__, 'ajaxRuntimeAcceptanceStep'));
        add_action('wp_ajax_yperf_runtime_acceptance_finish', array(__CLASS__, 'ajaxRuntimeAcceptanceFinish'));
        add_action('wp_ajax_yperf_migration_run', array(__CLASS__, 'ajaxMigrationRun'));
        add_action('wp_ajax_yperf_migration_status', array(__CLASS__, 'ajaxMigrationStatus'));
        add_action('wp_ajax_yperf_migration_baseline_run', array(__CLASS__, 'ajaxMigrationBaselineRun'));
        add_action('wp_ajax_yperf_migration_baseline_status', array(__CLASS__, 'ajaxMigrationBaselineStatus'));
        add_action('wp_ajax_yperf_autopilot_run', array(__CLASS__, 'ajaxAutopilotRun'));
        add_action('wp_ajax_yperf_autopilot_status', array(__CLASS__, 'ajaxAutopilotStatus'));
        add_action('wp_ajax_yperf_autopilot_prewarm_finalize', array(__CLASS__, 'ajaxAutopilotPrewarmFinalize'));
        add_action('wp_ajax_yperf_migration_responsibility_check', array(__CLASS__, 'ajaxMigrationResponsibilityCheck'));
        add_action('wp_ajax_yperf_migration_retire_responsibility', array(__CLASS__, 'ajaxMigrationRetireResponsibility'));
        add_action('wp_ajax_yperf_page_cache_campaign_start', array(__CLASS__, 'ajaxPageCacheCampaignStart'));
        add_action('wp_ajax_yperf_page_cache_campaign_step', array(__CLASS__, 'ajaxPageCacheCampaignStep'));
        add_action('wp_ajax_yperf_page_cache_campaign_status', array(__CLASS__, 'ajaxPageCacheCampaignStatus'));
        add_filter('plugin_action_links_' . YPERF_BASENAME, array(__CLASS__, 'pluginLinks'));
        add_action('admin_notices', array(__CLASS__, 'renderToolbarNotice'));
    }

    public static function registerToolbar(): void
    {
        add_action('admin_bar_menu', array(__CLASS__, 'adminBarMenu'), 9999);
        add_action('wp_before_admin_bar_render', array(__CLASS__, 'ensureAdminBarMenu'), 9999);
    }

    public static function adminBarMenu(\WP_Admin_Bar $bar): void
    {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            return;
        }

        $dashboard = admin_url('admin.php?page=yablonsky-performance');
        $cacheControls = admin_url('admin.php?page=yablonsky-performance&yperf_tab=control&yperf_section=control%3Acontrols');
        $returnUrl = self::currentFrontUrl();
        $currentUrl = is_admin() ? home_url('/') : self::currentFrontUrl();
        $purgeNonce = wp_create_nonce('yperf_toolbar_purge');
        $optimizeNonce = wp_create_nonce('yperf_toolbar_optimize');
        $prewarmNonce = wp_create_nonce('yperf_toolbar_prewarm');
        $edgeActive = CloudflareEdge::adoption() !== array();

        $purgeUrl = static function (string $mode, string $url = '') use ($purgeNonce, $returnUrl): string {
            return add_query_arg(
                array(
                    'action' => 'yperf_toolbar_purge',
                    'yperf_mode' => $mode,
                    'yperf_url' => $url,
                    'yperf_return' => $returnUrl,
                    '_wpnonce' => $purgeNonce,
                ),
                admin_url('admin-post.php')
            );
        };
        $actionUrl = static function (string $action, string $nonce, string $returnUrl): string {
            return add_query_arg(
                array(
                    'action' => $action,
                    'yperf_return' => $returnUrl,
                    '_wpnonce' => $nonce,
                ),
                admin_url('admin-post.php')
            );
        };

        $bar->add_node(array(
            'id' => 'yperf',
            'title' => __('YPerf', 'yablonsky-performance'),
            'href' => $dashboard,
            'meta' => array('title' => __('Yablonsky Performance', 'yablonsky-performance')),
        ));
        $bar->add_node(array('parent' => 'yperf', 'id' => 'yperf-optimize', 'title' => __('Optimize Now', 'yablonsky-performance'), 'href' => $actionUrl('yperf_toolbar_optimize', $optimizeNonce, $returnUrl)));
        $bar->add_node(array('parent' => 'yperf', 'id' => 'yperf-prewarm', 'title' => __('Prewarm Now', 'yablonsky-performance'), 'href' => $actionUrl('yperf_toolbar_prewarm', $prewarmNonce, $returnUrl)));
        $bar->add_node(array('parent' => 'yperf', 'id' => 'yperf-cache-controls', 'title' => __('Open cache controls', 'yablonsky-performance'), 'href' => $cacheControls));

        $homepageLabel = $edgeActive ? __('Purge homepage (YPerf + Cloudflare)', 'yablonsky-performance') : __('Purge homepage', 'yablonsky-performance');
        $pageLabel = $edgeActive ? __('Purge Page Cache (YPerf + Cloudflare)', 'yablonsky-performance') : __('Purge Page Cache', 'yablonsky-performance');
        $allLabel = $edgeActive ? __('Purge all active caches (YPerf + Cloudflare)', 'yablonsky-performance') : __('Purge all active YPerf caches', 'yablonsky-performance');
        if (!is_admin()) {
            $homepageLabel = $edgeActive ? __('Purge this URL (YPerf + Cloudflare)', 'yablonsky-performance') : __('Purge this URL', 'yablonsky-performance');
        }
        $bar->add_node(array('parent' => 'yperf', 'id' => 'yperf-purge-current', 'title' => $homepageLabel, 'href' => $purgeUrl('url', $currentUrl)));
        $bar->add_node(array('parent' => 'yperf', 'id' => 'yperf-purge-page', 'title' => $pageLabel, 'href' => $purgeUrl('page')));
        $bar->add_node(array('parent' => 'yperf', 'id' => 'yperf-purge-all', 'title' => $allLabel, 'href' => $purgeUrl('all')));
    }

    public static function ensureAdminBarMenu(): void
    {
        global $wp_admin_bar;
        if (!($wp_admin_bar instanceof \WP_Admin_Bar) || !is_user_logged_in() || !current_user_can('manage_options')) {
            return;
        }
        if ($wp_admin_bar->get_node('yperf') === null) {
            self::adminBarMenu($wp_admin_bar);
        }
    }

    private static function currentFrontUrl(): string
    {
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_HOST'])) : '';
        $request = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_URI'])) : '/';
        $request = '/' . ltrim($request, '/');
        if ($host === '') {
            return home_url('/');
        }
        $scheme = is_ssl() ? 'https://' : 'http://';
        return esc_url_raw($scheme . $host . $request);
    }

    public static function menu(): void
    {
        add_menu_page(
            __('Yablonsky Performance', 'yablonsky-performance'),
            __('Yablonsky Performance', 'yablonsky-performance'),
            'manage_options',
            'yablonsky-performance',
            array(__CLASS__, 'render'),
            'dashicons-performance',
            81
        );
    }

    public static function settings(): void
    {
        register_setting(
            'yperf_settings',
            'yperf_deep_archive_guard',
            array(
                'type'              => 'string',
                'sanitize_callback' => static function ($value): string {
                    return (string) $value === '1' ? '1' : '0';
                },
                'default'           => '0',
            )
        );
        register_setting(
            'yperf_settings',
            'yperf_external_purge_bridge',
            array(
                'type'              => 'string',
                'sanitize_callback' => static function ($value): string {
                    return (string) $value === '1' ? '1' : '0';
                },
                'default'           => '0',
            )
        );
        register_setting(
            'yperf_settings',
            'yperf_frontend_optimizer',
            array(
                'type'              => 'string',
                'sanitize_callback' => static function ($value): string {
                    return (string) $value === '1' ? '1' : '0';
                },
                'default'           => '0',
            )
        );
    }

    public static function enqueueAssets(string $hook): void
    {
        if ($hook !== 'toplevel_page_yablonsky-performance') {
            return;
        }
        wp_enqueue_style(
            'yperf-admin',
            plugins_url('assets/admin.css', YPERF_FILE),
            array(),
            YPERF_VERSION
        );
        wp_enqueue_style(
            'yperf-media-admin',
            plugins_url('assets/admin-media.css', YPERF_FILE),
            array('yperf-admin'),
            YPERF_VERSION
        );
        wp_enqueue_script(
            'yperf-admin',
            plugins_url('assets/admin.js', YPERF_FILE),
            array(),
            YPERF_VERSION,
            true
        );
        wp_localize_script(
            'yperf-admin',
            'YPerfDashboard',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'version' => YPERF_VERSION,
                'nonce'          => wp_create_nonce('yperf_runtime_acceptance_ajax'),
                'environmentNonce' => wp_create_nonce('yperf_environment_refresh_ajax'),
                'steps'          => RuntimeAcceptance::stepDefinitions(),
                'benchmarkNonce' => wp_create_nonce('yperf_benchmark_ajax'),
                'benchmarkSteps' => Benchmark::stepDefinitions(),
                'prewarmNonce'   => wp_create_nonce('yperf_prewarm_ajax'),
                'migrationNonce' => wp_create_nonce('yperf_migration_ajax'),
                'autopilotNonce' => wp_create_nonce('yperf_autopilot_ajax'),
                'pageCacheCampaignNonce' => wp_create_nonce('yperf_page_cache_campaign_ajax'),
                'prewarmUrls'    => Prewarm::plan(),
                'strings' => array(
                    'starting'    => __('Preparing checks…', 'yablonsky-performance'),
                    'running'     => __('Running', 'yablonsky-performance'),
                    'finishing'   => __('Saving report…', 'yablonsky-performance'),
                    'completed'   => __('Runtime Acceptance complete.', 'yablonsky-performance'),
                    'ajaxError'   => __('Health Check paused before completion. Existing cache and host settings stayed in their current state.', 'yablonsky-performance'),
                    'copied'      => __('Report copied.', 'yablonsky-performance'),
                    'copyFailed'  => __('Could not copy automatically.', 'yablonsky-performance'),
                    'intelligenceCopied' => __('Performance Intelligence report copied.', 'yablonsky-performance'),
                    'benchmarkCompleted' => __('Benchmark complete.', 'yablonsky-performance'),
                    'prewarmStarting' => __('Preparing URLs…', 'yablonsky-performance'),
                    'prewarmCompleted' => __('Prewarm complete.', 'yablonsky-performance'),
                    'evidenceStarting' => __('Preparing read-only evidence refresh…', 'yablonsky-performance'),
                    'evidenceEnvironment' => __('Refreshing Environment Check…', 'yablonsky-performance'),
                    'evidenceRuntime' => __('Refreshing Runtime Acceptance', 'yablonsky-performance'),
                    'evidenceBenchmark' => __('Refreshing Performance Benchmark', 'yablonsky-performance'),
                    'evidenceCompleted' => __('Read-only evidence refresh complete.', 'yablonsky-performance'),
                    'evidenceCompletedDetail' => __('Evidence refresh complete — Environment, Runtime Acceptance and Benchmark are current.', 'yablonsky-performance'),
                    'hostSnapshotTooLarge' => __('Host snapshot is larger than the 512 KB safety limit.', 'yablonsky-performance'),
                    'hostSnapshotReadError' => __('Could not read the selected host snapshot.', 'yablonsky-performance'),
                    'migrationStarting' => __('Preparing protected handoff…', 'yablonsky-performance'),
                    'migrationCompleted' => __('Migration complete. YPerf ownership is proven.', 'yablonsky-performance'),
                    'migrationStopped' => __('Migration stopped safely. Review the last event before retrying.', 'yablonsky-performance'),
                    'baselineStarting' => __('Preparing safe YPerf baseline…', 'yablonsky-performance'),
                    'baselineCompleted' => __('Baseline accepted. Runtime proof is clean.', 'yablonsky-performance'),
                    'baselineStopped' => __('Baseline action stopped safely. Review the status before retrying.', 'yablonsky-performance'),
                    'autopilotReconnecting' => __('Connection interrupted. Checking the server result…', 'yablonsky-performance'),
                    'autopilotNoRetry' => __('The original operation may still be running. Do not start it again.', 'yablonsky-performance'),
                    'autopilotUnverified' => __('Server completion not verified. Reload this page before any retry.', 'yablonsky-performance'),
                    'responsibilityChecking' => __('Rechecking provider responsibilities…', 'yablonsky-performance'),
                    'responsibilityReady' => __('Provider responsibilities are ready for protected handover.', 'yablonsky-performance'),
                    'responsibilityBlocked' => __('Provider responsibilities still need attention.', 'yablonsky-performance'),
                    'responsibilityRetireStarting' => __('Preparing explicit provider retirement…', 'yablonsky-performance'),
                    'responsibilityRetireSnapshot' => __('Capturing the reversible W3TC state…', 'yablonsky-performance'),
                    'responsibilityRetireApply' => __('Applying the selected W3TC retirement…', 'yablonsky-performance'),
                    'responsibilityRetireVerify' => __('Reading the provider state back…', 'yablonsky-performance'),
                    'responsibilityRetireComplete' => __('Provider responsibility retired and verified.', 'yablonsky-performance'),
                    'responsibilityRetireStopped' => __('Provider retirement stopped safely.', 'yablonsky-performance'),
                    'pageCacheCampaignStarting' => __('Preparing public delivery stability campaign…', 'yablonsky-performance'),
                    'pageCacheCampaignStopped' => __('Page Cache stability campaign stopped safely. Review the current step before retrying.', 'yablonsky-performance'),
                    'pageCacheCampaignBoundedStop' => __('Page Cache campaign exceeded its bounded safety step limit. Stop and review before retrying.', 'yablonsky-performance'),
                    'pageCacheCampaignComplete' => __('Public delivery stability campaign complete.', 'yablonsky-performance'),
                    'sqlExportUnavailable' => __('SQL export is unavailable for this saved report.', 'yablonsky-performance'),
                    'benchmarkStopped' => __('Benchmark stopped.', 'yablonsky-performance'),
                    'environmentRefreshed' => __('Environment Check refreshed.', 'yablonsky-performance'),
                    'evidenceStopped' => __('Evidence refresh stopped.', 'yablonsky-performance'),
                    'prewarmStopped' => __('Prewarm stopped.', 'yablonsky-performance'),
                    'migrationConfirmRequired' => __('Your confirmation is required', 'yablonsky-performance'),
                    'migrationManualDeactivate' => __('Deactivate the previous performance plugin from the normal WordPress Plugins screen, then continue the migration here.', 'yablonsky-performance'),
                    'migrationReviewEvent' => __('Review the latest migration event.', 'yablonsky-performance'),
                    'responsibilityCheckStopped' => __('Responsibility check stopped.', 'yablonsky-performance'),
                    'pageCachePreflightAccepted' => __('Request accepted. Preparing fresh preflight…', 'yablonsky-performance'),
                    'importNoFile' => __('No file selected', 'yablonsky-performance'),
                    'importFileTooLarge' => __('File is larger than the 256 KB safety limit.', 'yablonsky-performance'),
                    'importReadyToVerify' => __(' · ready to verify', 'yablonsky-performance'),
                    'importReadError' => __('Could not read this file in the browser.', 'yablonsky-performance'),
                    'cloudflareWorking' => __('Working with Cloudflare…', 'yablonsky-performance'),
                    'cloudflareKeepOpen' => __('Please keep this page open while YPerf completes the request.', 'yablonsky-performance'),
                    'pageCacheStartingShort' => __('Starting…', 'yablonsky-performance'),
                    'pageCacheStarting' => __('Starting public delivery stability campaign…', 'yablonsky-performance'),
                    'optimizeNow' => __('Optimize Now', 'yablonsky-performance'),
                    'prewarmAcceptedCache' => __('Prewarming the accepted public cache.', 'yablonsky-performance'),
                    'requestFailed' => __('Request failed.', 'yablonsky-performance'),
                    'runtimeStopped' => __('Runtime Acceptance stopped.', 'yablonsky-performance'),
                    'reportTitle' => __('Yablonsky Performance — Runtime Acceptance', 'yablonsky-performance'),
                    'reportSite' => __('Site: ', 'yablonsky-performance'),
                    'reportVersion' => __('Version: ', 'yablonsky-performance'),
                    'reportSummary' => __('Summary: ', 'yablonsky-performance'),
                    'reportPassed' => __(' passed / ', 'yablonsky-performance'),
                    'reportFailed' => __(' failed / ', 'yablonsky-performance'),
                    'reportSkipped' => __(' skipped / ', 'yablonsky-performance'),
                    'reportAttention' => __(' attention', 'yablonsky-performance'),
                    'reportAttentionLabel' => __('  Attention: ', 'yablonsky-performance'),
                    'reportCacheSignals' => __('  Cache signals: ', 'yablonsky-performance'),
                    'reportUrlLabel' => __('  URL: ', 'yablonsky-performance'),
                    'environmentEvidenceNotSaved' => __('Environment evidence was not saved.', 'yablonsky-performance'),
                    'runtimeEvidenceNotSaved' => __('Runtime Acceptance did not save current-version evidence.', 'yablonsky-performance'),
                    'benchmarkEvidenceNotSaved' => __('Performance Benchmark did not save current-version evidence.', 'yablonsky-performance'),
                    'responsibilityCheckFailed' => __('Provider responsibility check failed.', 'yablonsky-performance'),
                    'prewarmFinalizationFailed' => __('Automatic prewarm finalization failed.', 'yablonsky-performance'),
                ),
            )
        );
    }

    /** @param array<int,string> $links @return array<int,string> */
    public static function pluginLinks(array $links): array
    {
        $url = admin_url('admin.php?page=yablonsky-performance');
        array_unshift($links, '<a href="' . esc_url($url) . '">' . esc_html__('Dashboard', 'yablonsky-performance') . '</a>');
        return $links;
    }

    public static function captureAdoption(): void
    {
        self::requireOperator('yperf_capture_adoption');
        Adoption::capture();
        set_transient(self::noticeKey('adopted'), '1', 30);
        self::redirectDashboard();
    }

    public static function runRuntimeAcceptance(): void
    {
        self::requireOperator('yperf_run_runtime_acceptance');
        $report = RuntimeAcceptance::run();
        update_option('yperf_runtime_acceptance_v1', $report, false);
        Telemetry::recordRuntime($report);
        RuntimeHistory::record($report, 'manual');
        set_transient(self::noticeKey('runtime'), '1', 30);
        self::redirectDashboard();
    }

    public static function runEnvironmentCheck(): void
    {
        self::requireOperator('yperf_run_environment_check');
        EnvironmentCheck::run(CapabilityDetector::detect());
        set_transient(self::noticeKey('environment'), '1', 30);
        self::redirectDashboard('setup');
    }

    public static function importHostSnapshot(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_import_host_snapshot');
        $json = isset($_POST['yperf_host_snapshot_json']) ? sanitize_textarea_field(wp_unslash((string) $_POST['yperf_host_snapshot_json'])) : '';
        $result = HostSnapshot::importJson($json);
        if (!empty($result['ok'])) {
            set_transient(self::noticeKey('host_import'), '1', 30);
        } else {
            set_transient(self::noticeKey('host_error'), sanitize_text_field((string) ($result['message'] ?? __('Host snapshot import failed.', 'yablonsky-performance'))), 60);
        }
        self::redirectDashboard('setup');
    }

    public static function clearHostSnapshot(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_clear_host_snapshot');
        HostSnapshot::clear();
        set_transient(self::noticeKey('host_clear'), '1', 30);
        self::redirectDashboard('setup');
    }

    public static function downloadHostInspector(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_download_host_inspector');

        require_once ABSPATH . 'wp-admin/includes/file.php';
        global $wp_filesystem;
        if (!$wp_filesystem && !WP_Filesystem()) {
            wp_die(esc_html__('The Host Inspector package could not be opened through the WordPress filesystem API.', 'yablonsky-performance'));
        }
        $path = YPERF_DIR . 'host-inspector/yperf-host-inspector.php.txt';
        $contents = $wp_filesystem ? $wp_filesystem->get_contents($path) : false;
        if (!is_string($contents) || $contents === '') {
            wp_die(esc_html__('The bundled Host Inspector file is missing or unreadable.', 'yablonsky-performance'));
        }

        nocache_headers();
        header('Content-Type: application/x-httpd-php; charset=UTF-8');
        header('Content-Disposition: attachment; filename="yperf-host-inspector-' . YPERF_VERSION . '.php"');
        header('X-Content-Type-Options: nosniff');
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- exact bundled CLI source is intentionally returned as a download.
        echo $contents;
        exit;
    }

    public static function adoptNativePageCache(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_adopt_native_page_cache');
        $host = HostSnapshot::last();
        $proof = NativeRuntimeProof::build($host, DropinOwnership::inspect());
        $runtime = RuntimeAcceptance::lastReport();
        $result = NativePageCacheAdoption::adopt($host, $proof, $runtime);
        if (!empty($result['ok'])) {
            set_transient(self::noticeKey('native_page_cache_adopted'), '1', 30);
        } else {
            set_transient(self::noticeKey('native_page_cache_error'), sanitize_text_field((string) ($result['message'] ?? __('Native page cache adoption was blocked.', 'yablonsky-performance'))), 60);
        }
        self::redirectDashboard('setup');
    }

    public static function releaseNativePageCache(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_release_native_page_cache');
        NativePageCacheAdoption::release();
        set_transient(self::noticeKey('native_page_cache_released'), '1', 30);
        self::redirectDashboard('setup');
    }

    public static function refreshPageCacheArtifact(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_refresh_page_cache_artifact');
        $result = PageCacheExecutor::refreshOwnedArtifact();
        if (!empty($result['ok'])) {
            set_transient(self::noticeKey('page_cache_artifact_refreshed'), '1', 30);
        } else {
            set_transient(self::noticeKey('page_cache_artifact_error'), sanitize_text_field((string) ($result['message'] ?? __('Page Cache artifact refresh was blocked.', 'yablonsky-performance'))), 60);
        }
        $url = add_query_arg(array(
            'page' => 'yablonsky-performance',
            'yperf_tab' => 'setup',
            'yperf_section' => 'setup:page-cache',
        ), admin_url('admin.php'));
        wp_safe_redirect($url);
        exit;
    }

    public static function reserveObjectCache(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_reserve_object_cache');
        $host = RedisEvidence::resolve(HostSnapshot::last());
        $dropins = DropinOwnership::inspect();
        $runtime = RuntimeAcceptance::lastReport();
        $benchmark = Benchmark::lastReport();
        $result = ObjectCacheAdoption::reserve($host, $dropins, $runtime, $benchmark);
        if (!empty($result['ok'])) {
            set_transient(self::noticeKey('object_cache_reserved'), '1', 30);
        } else {
            set_transient(self::noticeKey('object_cache_reservation_error'), sanitize_text_field((string) ($result['message'] ?? __('Redis target reservation was blocked.', 'yablonsky-performance'))), 60);
        }
        self::redirectDashboard('setup');
    }

    public static function releaseObjectCacheReservation(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_release_object_cache_reservation');
        ObjectCacheAdoption::release();
        set_transient(self::noticeKey('object_cache_reservation_released'), '1', 30);
        self::redirectDashboard('setup');
    }

    public static function activateObjectCache(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_activate_object_cache');
        $expected = isset($_POST['yperf_object_cache_contract']) ? sanitize_text_field(wp_unslash((string) $_POST['yperf_object_cache_contract'])) : '';
        $host = RedisEvidence::resolve(HostSnapshot::last());
        $dropins = DropinOwnership::inspect();
        $runtime = RuntimeAcceptance::lastReport();
        $benchmark = Benchmark::lastReport();
        $adoption = ObjectCacheAdoption::analyze($host, $dropins, $runtime, $benchmark);
        $contract = ObjectCacheOperationContract::build($adoption, $host, $dropins, $runtime);
        $result = ObjectCacheExecutor::activate($contract, $expected);
        if (empty($result['ok'])) {
            set_transient(self::noticeKey('object_cache_activation_error'), sanitize_text_field((string) ($result['message'] ?? __('Object-cache activation was blocked.', 'yablonsky-performance'))), 60);
            self::redirectDashboard('setup');
        }
        $url = wp_nonce_url(admin_url('admin-post.php?action=yperf_finalize_object_cache'), 'yperf_finalize_object_cache');
        wp_safe_redirect($url);
        exit;
    }

    public static function finalizeObjectCache(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_finalize_object_cache');
        $result = ObjectCacheExecutor::finalize();
        if (!empty($result['ok'])) {
            set_transient(self::noticeKey('object_cache_activated'), '1', 60);
        } else {
            set_transient(self::noticeKey('object_cache_activation_error'), sanitize_text_field((string) ($result['message'] ?? __('Object-cache verification failed and YPerf attempted rollback.', 'yablonsky-performance'))), 90);
        }
        self::redirectDashboard('setup');
    }

    public static function rollbackObjectCache(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_rollback_object_cache');
        $result = ObjectCacheExecutor::rollback();
        if (!empty($result['ok'])) {
            set_transient(self::noticeKey('object_cache_rolled_back'), '1', 60);
        } else {
            set_transient(self::noticeKey('object_cache_activation_error'), sanitize_text_field((string) ($result['message'] ?? __('Object-cache rollback was blocked.', 'yablonsky-performance'))), 90);
        }
        self::redirectDashboard('setup');
    }

    public static function adoptOriginBudget(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_adopt_origin_budget');
        $expected = isset($_POST['yperf_origin_budget_contract']) ? sanitize_text_field(wp_unslash((string) $_POST['yperf_origin_budget_contract'])) : '';
        $host = HostSnapshot::last();
        $runtime = RuntimeAcceptance::lastReport();
        $upgradeHealth = UpgradeHealth::report();
        $control = ControlPlaneReadiness::analyze($upgradeHealth, $host, $runtime);
        $readiness = OriginBudgetReadiness::analyze($host, $runtime);
        $contract = OriginBudgetContract::build($readiness, $control, $host);
        $result = OriginBudgetAdoption::adopt($contract, $host, $runtime, $expected);
        if (!empty($result['ok'])) {
            set_transient(self::noticeKey('origin_budget_adopted'), '1', 60);
        } else {
            set_transient(self::noticeKey('origin_budget_error'), sanitize_text_field((string) ($result['message'] ?? __('Origin Budget adoption was blocked.', 'yablonsky-performance'))), 90);
        }
        self::redirectDashboard('setup');
    }

    public static function releaseOriginBudget(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_release_origin_budget');
        OriginBudgetAdoption::release();
        set_transient(self::noticeKey('origin_budget_released'), '1', 60);
        self::redirectDashboard('setup');
    }

    public static function applyPerformancePlan(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_apply_performance_plan');
        $profile = isset($_POST['yperf_performance_profile']) ? sanitize_key(wp_unslash((string) $_POST['yperf_performance_profile'])) : 'safe';
        $returnTab = isset($_POST['yperf_return_tab']) ? sanitize_key(wp_unslash((string) $_POST['yperf_return_tab'])) : 'optimize';
        if (!in_array($returnTab, array('overview', 'optimize', 'migration', 'setup', 'control', 'insights', 'diagnostics', 'advanced'), true)) {
            $returnTab = 'optimize';
        }
        self::executePerformanceCampaign($profile, 'manual', array(), false, $returnTab);
    }

    public static function runAutopilot(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_run_autopilot');
        $caps = CapabilityDetector::detect();
        $runtime = RuntimeAcceptance::lastReport();
        $host = HostSnapshot::last();
        $dropins = DropinOwnership::inspect();
        $proof = NativeRuntimeProof::build($host, $dropins);
        $recommendation = Autopilot::recommend($caps, $runtime, $proof);
        if (empty($recommendation['ready'])) {
            set_transient(self::noticeKey('autopilot_blocked'), '1', 60);
            self::redirectDashboard('optimize');
        }
        $profile = sanitize_key((string) ($recommendation['target_profile'] ?? 'safe'));
        self::executePerformanceCampaign($profile, 'autopilot', $recommendation);
    }


    public static function saveCloudflare(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_save_cloudflare');
        if (!empty($_POST['yperf_cloudflare_clear'])) {
            CloudflareCredentials::clear();
            set_transient(self::noticeKey('cloudflare_saved'), '1', 60);
            self::redirectDashboard('setup', 'setup:edge');
        }

        $token = isset($_POST['yperf_cloudflare_api_token']) ? trim(sanitize_text_field(wp_unslash((string) $_POST['yperf_cloudflare_api_token']))) : '';
        $zone = isset($_POST['yperf_cloudflare_zone_id']) ? trim(sanitize_text_field(wp_unslash((string) $_POST['yperf_cloudflare_zone_id']))) : '';
        if ($token !== '') {
            if (!CloudflareCredentials::save($token, $zone)) {
                set_transient(self::noticeKey('cloudflare_error'), __('Cloudflare API token could not be stored. Check the token and local cryptography support.', 'yablonsky-performance'), 90);
                self::redirectDashboard('setup', 'setup:edge');
            }
        } else {
            CloudflareCredentials::saveZoneOverride($zone);
        }
        set_transient(self::noticeKey('cloudflare_saved'), '1', 60);
        self::redirectDashboard('setup', 'setup:edge');
    }

    public static function inspectCloudflare(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_inspect_cloudflare');
        $audit = CloudflareEdge::inspect();
        if (empty($audit['ready'])) {
            set_transient(self::noticeKey('cloudflare_error'), sanitize_text_field((string) ($audit['message'] ?? __('Cloudflare edge audit could not complete.', 'yablonsky-performance'))), 90);
        } else {
            set_transient(self::noticeKey('cloudflare_inspected'), '1', 60);
        }
        self::redirectDashboard('setup', 'setup:edge');
    }

    public static function applyCloudflare(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_apply_cloudflare');
        $result = CloudflareEdge::applyRecommended();
        if (empty($result['ok'])) {
            set_transient(self::noticeKey('cloudflare_error'), sanitize_text_field((string) ($result['message'] ?? __('Cloudflare performance profile was not applied.', 'yablonsky-performance'))), 90);
        } else {
            set_transient(self::noticeKey('cloudflare_applied'), '1', 60);
        }
        self::redirectDashboard('setup', 'setup:edge');
    }

    public static function rollbackCloudflare(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_rollback_cloudflare');
        $result = CloudflareEdge::rollback();
        if (empty($result['ok'])) {
            set_transient(self::noticeKey('cloudflare_error'), sanitize_text_field((string) ($result['message'] ?? __('Cloudflare rollback needs review.', 'yablonsky-performance'))), 90);
        } else {
            set_transient(self::noticeKey('cloudflare_rollback'), '1', 60);
        }
        self::redirectDashboard('setup', 'setup:edge');
    }


    public static function verifyCloudflarePurge(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_verify_cloudflare_purge');

        $result = PurgeController::purge('url', home_url('/'), 'cloudflare_verify');
        $localState = sanitize_key((string) ($result['status'] ?? 'unknown'));
        $edgeState = sanitize_key((string) ($result['edge_status'] ?? 'unknown'));
        if (!in_array($localState, array('fail', 'blocked'), true) && $edgeState === 'accepted') {
            set_transient(self::noticeKey('cloudflare_purge_verified'), '1', 60);
        } else {
            $detail = sanitize_text_field((string) ($result['edge_details'] ?? ''));
            if ($detail === '') {
                $detail = __('Cloudflare URL purge synchronization could not be proved. Run Inspect Cloudflare and confirm the profile is adopted.', 'yablonsky-performance');
            }
            set_transient(self::noticeKey('cloudflare_error'), $detail, 90);
        }
        self::redirectDashboard('setup', 'setup:edge');
    }


    public static function saveAISettings(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_save_ai_settings');
        if (!empty($_POST['yperf_ai_clear'])) {
            YablonskyAICredentials::clear();
            set_transient(self::noticeKey('ai_settings_saved'), '1', 60);
            self::redirectDashboard('optimize');
        }
        if (isset($_POST['yperf_ai_model'])) {
            $model = sanitize_text_field(wp_unslash((string) $_POST['yperf_ai_model']));
            if (!YablonskyAIAccount::saveModel($model)) {
                set_transient(self::noticeKey('ai_advisor_error'), __('The selected AI model is not available to this verified workspace. Refresh the account catalog and choose again.', 'yablonsky-performance'), 90);
                self::redirectDashboard('optimize');
            }
        }
        $key = isset($_POST['yperf_ai_api_key']) ? trim(sanitize_text_field(wp_unslash((string) $_POST['yperf_ai_api_key']))) : '';
        if ($key !== '' && !YablonskyAICredentials::save($key)) {
            set_transient(self::noticeKey('ai_advisor_error'), __('The Yablonsky AI API key could not be saved. Check the key and server cryptography support.', 'yablonsky-performance'), 90);
            self::redirectDashboard('optimize');
        }
        set_transient(self::noticeKey('ai_settings_saved'), '1', 60);
        self::redirectDashboard('optimize');
    }

    public static function verifyAIConnection(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_verify_ai_connection');
        $result = YablonskyAIClient::fetchAccount();
        if (is_wp_error($result)) {
            set_transient(self::noticeKey('ai_advisor_error'), sanitize_text_field($result->get_error_message()), 90);
        } else {
            set_transient(self::noticeKey('ai_connection_verified'), '1', 60);
        }
        self::redirectDashboard('optimize');
    }

    public static function runAIAdvisor(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_run_ai_advisor');
        $caps = CapabilityDetector::detect();
        $runtime = RuntimeAcceptance::lastReport();
        $host = HostSnapshot::last();
        $dropins = DropinOwnership::inspect();
        $proof = NativeRuntimeProof::build($host, $dropins);
        $nativePlan = NativeAdoptionPlan::build($caps, $host, $proof, NativePageCacheAdoption::analyze($host, $proof, $runtime));
        $plan = PerformancePlan::build($caps, $runtime, $proof, $nativePlan);
        $result = AIAdvisor::recommend($caps, $runtime, $proof, $plan, $host, Benchmark::lastReport());
        if (is_wp_error($result)) {
            set_transient(self::noticeKey('ai_advisor_error'), sanitize_text_field($result->get_error_message()), 90);
        } else {
            set_transient(self::noticeKey('ai_advisor_ready'), '1', 60);
        }
        self::redirectDashboard('optimize');
    }

    public static function applyAIAdvisor(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_apply_ai_advisor');
        $recommendation = AIAdvisor::lastRecommendation();
        if ($recommendation === array()) {
            set_transient(self::noticeKey('ai_advisor_error'), __('Generate a current AI recommendation before applying an AI-assisted plan.', 'yablonsky-performance'), 90);
            self::redirectDashboard('optimize');
        }
        $caps = CapabilityDetector::detect();
        $runtime = RuntimeAcceptance::lastReport();
        $host = HostSnapshot::last();
        $proof = NativeRuntimeProof::build($host, DropinOwnership::inspect());
        $recommendation = AIAdvisor::revalidate($recommendation, $caps, $runtime, $proof);
        if (empty($recommendation['ready_to_apply'])) {
            set_transient(self::noticeKey('ai_advisor_error'), __('AI-assisted Apply is blocked until a clean current-version Runtime Acceptance exists.', 'yablonsky-performance'), 90);
            self::redirectDashboard('optimize');
        }
        $profile = sanitize_key((string) ($recommendation['target_profile'] ?? 'observe'));
        self::executePerformanceCampaign($profile, 'ai_advisor', $recommendation);
    }

    public static function askAIAdvisor(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_ask_ai_advisor');
        $question = isset($_POST['yperf_ai_question']) ? sanitize_textarea_field(wp_unslash((string) $_POST['yperf_ai_question'])) : '';
        $caps = CapabilityDetector::detect();
        $runtime = RuntimeAcceptance::lastReport();
        $host = HostSnapshot::last();
        $dropins = DropinOwnership::inspect();
        $proof = NativeRuntimeProof::build($host, $dropins);
        $nativePlan = NativeAdoptionPlan::build($caps, $host, $proof, NativePageCacheAdoption::analyze($host, $proof, $runtime));
        $plan = PerformancePlan::build($caps, $runtime, $proof, $nativePlan);
        $result = AIAdvisor::ask($question, $caps, $runtime, $proof, $plan, $host, Benchmark::lastReport());
        if (is_wp_error($result)) {
            set_transient(self::noticeKey('ai_advisor_error'), sanitize_text_field($result->get_error_message()), 90);
        } else {
            set_transient(self::noticeKey('ai_advisor_answered'), '1', 60);
        }
        self::redirectDashboard('optimize');
    }

    /** @param array<string,mixed> $recommendation @return array<string,mixed> */
    private static function executePerformanceCampaign(string $profile, string $mode, array $recommendation, bool $liveProgress = false, string $returnTab = 'optimize', string $progressChannel = 'baseline', bool $deferPrewarm = false): array
    {
        $returnTab = in_array($returnTab, array('overview', 'optimize', 'migration', 'setup', 'control', 'insights', 'diagnostics', 'advanced'), true) ? $returnTab : 'optimize';
        $progressChannel = sanitize_key($progressChannel);
        if ($progressChannel === '') {
            $progressChannel = 'baseline';
        }
        $progress = static function (string $phase, int $percent, string $label, string $detail = '', bool $terminal = false) use ($liveProgress, $progressChannel): void {
            if (!$liveProgress) {
                return;
            }
            MigrationActivity::set($progressChannel, $phase, $percent, $label, $detail, $terminal);
        };
        if ($liveProgress) {
            MigrationActivity::reset($progressChannel, __('Preparing safe YPerf baseline.', 'yablonsky-performance'), __('Capturing a fresh preflight before any local setting changes.', 'yablonsky-performance'));
        }

        $caps = CapabilityDetector::detect();
        $host = HostSnapshot::last();
        $dropins = DropinOwnership::inspect();
        $proof = NativeRuntimeProof::build($host, $dropins);
        $before = PerformancePlan::settingsSnapshot();
        $deliveryBefore = array(
            'page_cache' => PageCacheExecutor::enabled(),
            'browser_cache' => BrowserCacheController::enabled(),
        );
        $campaign = OptimizationCampaign::start($mode, $profile, $recommendation);
        $result = array('ok' => false, 'state' => 'starting', 'profile' => $profile);

        try {
            $progress('preflight', 10, __('Running fresh preflight Runtime Acceptance.', 'yablonsky-performance'), __('No performance setting has changed yet.', 'yablonsky-performance'));
            $beforeRuntime = RuntimeAcceptance::run();
            Telemetry::recordRuntime($beforeRuntime);
            RuntimeHistory::record($beforeRuntime, $mode . '_baseline');
            $preflightSummary = is_array($beforeRuntime['summary'] ?? null) ? $beforeRuntime['summary'] : array();
            if ((int) ($preflightSummary['fail'] ?? 0) > 0 && self::repairOwnedPreflightFailures($beforeRuntime)) {
                $progress('repair', 18, __('Repairing YPerf-owned cache state.', 'yablonsky-performance'), __('Only YPerf-owned cache artifacts are reconciled; external providers and server configuration remain untouched.', 'yablonsky-performance'));
                $beforeRuntime = RuntimeAcceptance::run();
                Telemetry::recordRuntime($beforeRuntime);
                RuntimeHistory::record($beforeRuntime, $mode . '_baseline_after_repair');
                $preflightSummary = is_array($beforeRuntime['summary'] ?? null) ? $beforeRuntime['summary'] : array();
            }
            if ((int) ($preflightSummary['fail'] ?? 0) > 0) {
                update_option('yperf_runtime_acceptance_v1', $beforeRuntime, false);
                $failedNames = self::failedRuntimeNames($beforeRuntime);
                OptimizationCampaign::finish($campaign, 'blocked', array(
                    'state' => 'preflight_failed',
                    'reason' => 'Fresh Health Check contained a functional failure before any change.',
                    'failed_tests' => $failedNames,
                ));
                set_transient(self::noticeKey('performance_plan_preflight_error'), sprintf(
                    /* translators: %s: failing health check names */
                    __('Optimization stopped safely before changes. Health Check still fails: %s', 'yablonsky-performance'),
                    $failedNames !== array() ? implode(', ', $failedNames) : __('unknown check', 'yablonsky-performance')
                ), 90);
                $progress('blocked', 10, __('Stopped before making changes.', 'yablonsky-performance'), __('Fresh Health Check still contains a functional failure.', 'yablonsky-performance'), true);
                $result = array('ok' => false, 'state' => 'preflight_failed', 'profile' => $profile, 'message' => __('Fresh Health Check failed. No performance setting was changed.', 'yablonsky-performance'));
                if ($liveProgress) {
                    return $result;
                }
                self::redirectDashboard($returnTab, 'optimize:recommended');
                return $result;
            }

            $progress('preflight_ready', 28, __('Preflight accepted.', 'yablonsky-performance'), __('The selected local profile can now be applied inside the YPerf-owned WordPress boundary.', 'yablonsky-performance'));

            // Autopilot re-evaluates against the fresh campaign baseline. A
            // previous deep-archive proof can disappear as content changes, so
            // Autopilot is allowed to downgrade commerce -> safe before writes.
            if ($mode === 'autopilot') {
                $freshRecommendation = Autopilot::recommend($caps, $beforeRuntime, $proof);
                $profile = sanitize_key((string) ($freshRecommendation['target_profile'] ?? 'safe'));
                $campaign['profile'] = $profile;
                $campaign['recommendation'] = $freshRecommendation;
            } elseif ($mode === 'ai_advisor') {
                $freshRecommendation = AIAdvisor::revalidate($recommendation, $caps, $beforeRuntime, $proof);
                if (empty($freshRecommendation['ready_to_apply'])) {
                    OptimizationCampaign::finish($campaign, 'blocked', array(
                        'state' => 'ai_safety_gate_blocked',
                        'reason' => 'Fresh deterministic evidence did not authorize AI-assisted Apply.',
                    ));
                    set_transient(self::noticeKey('ai_advisor_error'), __('Fresh evidence blocked the AI-assisted plan before any setting changed.', 'yablonsky-performance'), 90);
                    $progress('blocked', 100, __('AI-assisted Apply was blocked safely.', 'yablonsky-performance'), __('Fresh deterministic evidence did not authorize the requested profile.', 'yablonsky-performance'), true);
                    $result = array('ok' => false, 'state' => 'ai_safety_gate_blocked', 'profile' => $profile, 'message' => __('Fresh evidence blocked the AI-assisted plan before any setting changed.', 'yablonsky-performance'));
                    if ($liveProgress) {
                        return $result;
                    }
                    self::redirectDashboard($returnTab);
                    return $result;
                }
                $profile = sanitize_key((string) ($freshRecommendation['target_profile'] ?? 'observe'));
                $campaign['profile'] = $profile;
                $campaign['recommendation'] = $freshRecommendation;
            }

            $progress('apply', 42, __('Applying the selected YPerf baseline.', 'yablonsky-performance'), __('Only YPerf-owned local WordPress controls are changing.', 'yablonsky-performance'));
            PerformancePlan::apply($profile, $caps, $beforeRuntime, $proof);
            $deliveryResult = self::applyDeliveryProfile($profile, $beforeRuntime);
            if (empty($deliveryResult['ok'])) {
                PerformancePlan::restore($before);
                self::restoreDeliveryState($deliveryBefore);
                OptimizationCampaign::finish($campaign, 'blocked', array(
                    'state' => 'delivery_apply_failed',
                    'reason' => sanitize_text_field((string) ($deliveryResult['message'] ?? 'Delivery optimization failed.')),
                ));
                set_transient(self::noticeKey('performance_plan_error'), sanitize_text_field((string) ($deliveryResult['message'] ?? __('YPerf could not safely activate the cache baseline.', 'yablonsky-performance'))), 90);
                $progress('blocked', 100, __('Optimization stopped safely.', 'yablonsky-performance'), sanitize_text_field((string) ($deliveryResult['message'] ?? '')), true);
                $result = array('ok' => false, 'state' => 'delivery_apply_failed', 'profile' => $profile, 'message' => sanitize_text_field((string) ($deliveryResult['message'] ?? __('Cache activation failed safely.', 'yablonsky-performance'))));
                if ($liveProgress) {
                    return $result;
                }
                self::redirectDashboard($returnTab, 'optimize:recommended');
                return $result;
            }
            $appliedSettings = PerformancePlan::settingsSnapshot();
            $appliedSettings['yperf_page_cache'] = PageCacheExecutor::enabled() ? '1' : '0';
            $appliedSettings['yperf_browser_cache'] = BrowserCacheController::enabled() ? '1' : '0';

            $progress('runtime_proof', 58, __('Proving execution with Health Check.', 'yablonsky-performance'), __('The post-apply request set is checking public routes and safety policies.', 'yablonsky-performance'));
            $afterFirst = RuntimeAcceptance::run();
            Telemetry::recordRuntime($afterFirst);
            RuntimeHistory::record($afterFirst, $mode . '_post_apply');
            $firstGuard = RegressionGuard::evaluate($beforeRuntime, $afterFirst);
            $afterFinal = $afterFirst;
            $guard = array('first_pass' => $firstGuard, 'confirmation' => array());
            $rollbackReason = '';

            if (!empty($firstGuard['functional_failure'])) {
                $rollbackReason = 'functional_failure';
            } elseif (!empty($firstGuard['suspected'])) {
                $progress('regression_confirmation', 72, __('Confirming a possible regression.', 'yablonsky-performance'), __('A second pass prevents one noisy sample from triggering rollback by itself.', 'yablonsky-performance'));
                $afterSecond = RuntimeAcceptance::run();
                Telemetry::recordRuntime($afterSecond);
                RuntimeHistory::record($afterSecond, $mode . '_regression_confirmation');
                $secondGuard = RegressionGuard::evaluate($beforeRuntime, $afterSecond);
                $confirmation = RegressionGuard::confirm($firstGuard, $secondGuard);
                $guard['second_pass'] = $secondGuard;
                $guard['confirmation'] = $confirmation;
                $afterFinal = $afterSecond;
                if (!empty($confirmation['confirmed'])) {
                    $rollbackReason = 'confirmed_regression';
                }
            } else {
                $progress('regression_guard', 78, __('Regression guard is clean.', 'yablonsky-performance'), __('No confirmed material regression requires rollback.', 'yablonsky-performance'));
            }

            if ($rollbackReason !== '') {
                $progress('rollback', 84, __('Rolling back the local profile.', 'yablonsky-performance'), __('The previous YPerf settings are being restored and re-proved.', 'yablonsky-performance'));
                PerformancePlan::restore($before);
                self::restoreDeliveryState($deliveryBefore);
                $rollbackAcceptance = RuntimeAcceptance::run();
                update_option('yperf_runtime_acceptance_v1', $rollbackAcceptance, false);
                Telemetry::recordRuntime($rollbackAcceptance);
                RuntimeHistory::record($rollbackAcceptance, $mode . '_rollback_validation');
                PerformancePlan::markAcceptance($afterFinal, true);
                $restoredPlan = PerformancePlan::build($caps, $rollbackAcceptance, $proof, NativeAdoptionPlan::build($caps, $host, $proof, NativePageCacheAdoption::analyze($host, $proof, $rollbackAcceptance)));
                $effectiveness = EffectivePerformance::build($caps, $rollbackAcceptance, $proof, $restoredPlan);
                $guard['rollback_reason'] = $rollbackReason;
                if ($rollbackReason === 'functional_failure') {
                    $guard['post_apply_failed_tests'] = self::failedRuntimeNames($afterFinal);
                    $guard['post_apply_failed_test_evidence'] = self::failedRuntimeEvidence($afterFinal);
                }
                $finishedCampaign = OptimizationCampaign::finish($campaign, 'rolled_back', $guard);
                OptimizationOutcome::save(OptimizationOutcome::build($beforeRuntime, $rollbackAcceptance, $before, $appliedSettings, $effectiveness, true, $guard, $finishedCampaign));
                set_transient(self::noticeKey($rollbackReason === 'confirmed_regression' ? 'performance_regression_rollback' : 'performance_plan_rollback'), '1', 60);
                $progress('rolled_back', 100, __('Baseline was rolled back safely.', 'yablonsky-performance'), __('The previous YPerf profile is restored and the rollback Runtime Acceptance completed.', 'yablonsky-performance'), true);
                $result = array('ok' => false, 'state' => 'rolled_back', 'profile' => $profile, 'message' => __('The baseline was rolled back after proof detected a failure or confirmed regression.', 'yablonsky-performance'));
            } else {
                $progress('finalizing', 90, __('Finalizing accepted execution proof.', 'yablonsky-performance'), __('Saving the accepted Runtime evidence and campaign outcome.', 'yablonsky-performance'));
                update_option('yperf_runtime_acceptance_v1', $afterFinal, false);
                PerformancePlan::markAcceptance($afterFinal, false);
                $acceptedPlan = PerformancePlan::build($caps, $afterFinal, $proof, NativeAdoptionPlan::build($caps, $host, $proof, NativePageCacheAdoption::analyze($host, $proof, $afterFinal)));
                $effectiveness = EffectivePerformance::build($caps, $afterFinal, $proof, $acceptedPlan);
                $finishedCampaign = OptimizationCampaign::finish($campaign, 'accepted', $guard);
                OptimizationOutcome::save(OptimizationOutcome::build($beforeRuntime, $afterFinal, $before, $appliedSettings, $effectiveness, false, $guard, $finishedCampaign));
                if ($deferPrewarm) {
                    // YPerf 2.34.5 — the acceptance request has already spent its
                    // execution budget on fresh preflight, apply, Runtime proof and
                    // regression guard. Do not append same-request loopback HTTP
                    // warm-up here: field evidence showed that the request can be
                    // terminated after acceptance, leaving the progress ledger at
                    // 94%. The browser now runs the existing bounded per-URL prewarm
                    // steps as separate AJAX requests and finalizes terminal 100%.
                    $progress('prewarm', 94, __('Prewarming the accepted public cache.', 'yablonsky-performance'), __('YPerf is warming a bounded set of high-value public URLs so the first visitor does not pay the cold-cache cost.', 'yablonsky-performance'));
                    $result = array(
                        'ok' => true,
                        'state' => 'accepted_pending_prewarm',
                        'profile' => $profile,
                        'message' => __('The selected YPerf baseline was applied and accepted by Runtime Acceptance.', 'yablonsky-performance'),
                    );
                } else {
                    $progress('prewarm', 94, __('Prewarming the accepted public cache.', 'yablonsky-performance'), __('YPerf is warming a bounded set of high-value public URLs so the first visitor does not pay the cold-cache cost.', 'yablonsky-performance'));
                    $prewarmReport = Prewarm::runBoundedPlan(6, 15000);
                    set_transient(self::noticeKey($mode === 'autopilot' ? 'autopilot' : ($mode === 'ai_advisor' ? 'ai_advisor_applied' : 'performance_plan')), '1', 30);
                    $progress('accepted', 100, __('Baseline accepted and prewarmed.', 'yablonsky-performance'), __('Runtime Acceptance proved the selected YPerf profile. Bounded automatic prewarm completed without changing the acceptance decision.', 'yablonsky-performance'), true);
                    $result = array(
                        'ok' => true,
                        'state' => 'accepted',
                        'profile' => $profile,
                        'message' => __('The selected YPerf baseline was applied, accepted by Runtime Acceptance and automatically prewarmed.', 'yablonsky-performance'),
                        'prewarm' => $prewarmReport,
                    );
                }
            }
        } catch (\Throwable $throwable) {
            PerformancePlan::restore($before);
            self::restoreDeliveryState($deliveryBefore);
            OptimizationCampaign::finish($campaign, 'error', array(
                'state' => 'exception',
                'reason' => sanitize_text_field($throwable->getMessage()),
            ));
            set_transient(self::noticeKey('performance_plan_error'), sanitize_text_field($throwable->getMessage()), 60);
            $progress('error', 100, __('Baseline action stopped safely.', 'yablonsky-performance'), sanitize_text_field($throwable->getMessage()), true);
            $result = array('ok' => false, 'state' => 'error', 'profile' => $profile, 'message' => sanitize_text_field($throwable->getMessage()));
        }

        if ($liveProgress) {
            return $result;
        }
        self::redirectDashboard($returnTab);
        return $result;
    }

    /** @param array<string,mixed> $runtime */
    private static function repairOwnedPreflightFailures(array $runtime): bool
    {
        $failed = self::failedRuntimeNames($runtime);
        if ($failed === array()) {
            return false;
        }
        foreach ($failed as $name) {
            if (!in_array($name, array('YPerf Page Cache', 'YPerf Browser Cache'), true)) {
                return false;
            }
        }
        $attempted = false;
        if (in_array('YPerf Page Cache', $failed, true)) {
            $attempted = true;
            $repair = PageCacheExecutor::repair();
            if (empty($repair['ok'])) {
                return false;
            }
        }
        if (in_array('YPerf Browser Cache', $failed, true) && BrowserCacheController::enabled()) {
            $attempted = true;
            if (!BrowserCacheController::setEnabled(true)) {
                return false;
            }
            $proof = BrowserCacheController::prove();
            if (empty($proof['ok'])) {
                BrowserCacheController::setEnabled(false);
                return false;
            }
        }
        return $attempted;
    }

    /** @param array<string,mixed> $runtime @return array<int,string> */
    private static function failedRuntimeNames(array $runtime): array
    {
        $names = array();
        foreach ((array) ($runtime['tests'] ?? array()) as $test) {
            if (!is_array($test) || (string) ($test['status'] ?? '') !== 'fail') {
                continue;
            }
            $name = sanitize_text_field((string) ($test['name'] ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
        }
        return array_values(array_unique($names));
    }

    /** @param array<string,mixed> $runtime @return array<int,array<string,mixed>> */
    private static function failedRuntimeEvidence(array $runtime): array
    {
        $rows = array();
        foreach ((array) ($runtime['tests'] ?? array()) as $test) {
            if (!is_array($test) || (string) ($test['status'] ?? '') !== 'fail') {
                continue;
            }
            $row = array(
                'name' => sanitize_text_field((string) ($test['name'] ?? '')),
                'details' => sanitize_text_field((string) ($test['details'] ?? '')),
            );
            if (isset($test['proof_checks']) && is_array($test['proof_checks'])) {
                $row['proof_checks'] = array(
                    'frontend_header' => !empty($test['proof_checks']['frontend_header']),
                    'emoji_payload_absent' => !empty($test['proof_checks']['emoji_payload_absent']),
                    'delay_markers' => !empty($test['proof_checks']['delay_markers']),
                    'css_route_applicable' => !empty($test['proof_checks']['css_route_applicable']),
                    'css_markers' => !empty($test['proof_checks']['css_markers']),
                );
            }
            if (isset($test['js_delay_proof']) && is_array($test['js_delay_proof'])) {
                $row['js_delay_proof'] = $test['js_delay_proof'];
            }
            if (isset($test['css_inline_proof']) && is_array($test['css_inline_proof'])) {
                $row['css_inline_proof'] = $test['css_inline_proof'];
            }
            $rows[] = $row;
        }
        return $rows;
    }

    /** @return array<string,mixed> */
    private static function applyDeliveryProfile(string $profile, array $runtime = array()): array
    {
        if ($profile === 'observe') {
            return array('ok' => true, 'state' => 'unchanged');
        }
        $externalAuthority = RuntimeAcceptance::externalPageCacheAuthority($runtime);
        if (!empty($externalAuthority['detected']) && !PageCacheExecutor::enabled()) {
            // Preserve the proven host/native page-cache authority. YPerf can
            // still apply frontend/runtime controls, but it must not install a
            // second portable page-cache executor on top of Nginx/FastCGI.
            if (BrowserCacheController::enabled()) {
                BrowserCacheController::setEnabled(false);
            }
            return array(
                'ok' => true,
                'state' => 'external_page_cache_preserved',
                'external_cache_header' => sanitize_key((string) ($externalAuthority['header'] ?? '')),
                'external_cache_value' => sanitize_text_field((string) ($externalAuthority['value'] ?? '')),
            );
        }
        $page = PageCacheExecutor::repair();
        if (empty($page['ok'])) {
            return array('ok' => false, 'message' => (string) ($page['message'] ?? __('Page Cache could not be activated safely.', 'yablonsky-performance')));
        }
        if (!BrowserCacheController::setEnabled(true)) {
            return array('ok' => false, 'message' => __('Browser Cache could not update the YPerf-owned cache policy safely.', 'yablonsky-performance'));
        }
        $browser = BrowserCacheController::prove();
        if (empty($browser['ok'])) {
            BrowserCacheController::setEnabled(false);
            return array('ok' => false, 'message' => __('Browser Cache proof failed. The browser policy was restored to OFF.', 'yablonsky-performance'));
        }
        return array('ok' => true, 'state' => 'page_and_browser_cache_proven');
    }

    /** @param array<string,bool> $before */
    private static function restoreDeliveryState(array $before): void
    {
        $wantedPage = !empty($before['page_cache']);
        $wantedBrowser = !empty($before['browser_cache']);
        if ($wantedPage) {
            PageCacheExecutor::repair();
        } elseif (PageCacheExecutor::enabled()) {
            PageCacheExecutor::disable(false);
        }
        if ($wantedBrowser && PageCacheExecutor::enabled()) {
            BrowserCacheController::setEnabled(true);
        } else {
            BrowserCacheController::setEnabled(false);
        }
    }

    public static function runCacheabilityInspector(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_cacheability_inspect');
        $url = isset($_POST['yperf_inspect_url']) ? esc_url_raw(wp_unslash((string) $_POST['yperf_inspect_url'])) : home_url('/');
        CacheabilityInspector::inspect($url);
        set_transient(self::noticeKey('inspect'), '1', 30);
        self::redirectDashboard('control', 'control:operations');
    }

    public static function exportSettings(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_export_settings');
        $payload = SettingsTransfer::export();
        nocache_headers();
        header('Content-Disposition: attachment; filename="yablonsky-performance-settings-' . gmdate('Y-m-d') . '.json"');
        wp_send_json($payload, 200, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function importSettings(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_import_settings');
        // File selection is read in the browser and copied into this bounded
        // textarea before submit. WordPress never accepts arbitrary server paths
        // or performs direct upload-file reads for portable settings import.
        $raw = isset($_POST['yperf_settings_json']) ? trim(sanitize_textarea_field(wp_unslash((string) $_POST['yperf_settings_json']))) : '';
        if (strlen($raw) > 262144) {
            set_transient(self::noticeKey('settings_import_error'), __('The settings backup is larger than the supported 256 KB safety limit.', 'yablonsky-performance'), 30);
            self::redirectDashboard('setup', 'setup:backup');
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            set_transient(self::noticeKey('settings_import_error'), __('The selected settings backup is not valid JSON.', 'yablonsky-performance'), 90);
            self::redirectDashboard('setup', 'setup:backup');
        }
        $result = SettingsTransfer::import($decoded);
        if (!empty($result['ok'])) {
            set_transient(self::noticeKey('settings_import'), '1', 60);
        } else {
            set_transient(self::noticeKey('settings_import_error'), sanitize_text_field((string) ($result['message'] ?? __('Settings import failed.', 'yablonsky-performance'))), 90);
        }
        self::redirectDashboard('setup', 'setup:backup');
    }

    public static function runManualPurge(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_manual_purge');
        $mode = isset($_POST['yperf_purge_mode']) ? sanitize_key(wp_unslash((string) $_POST['yperf_purge_mode'])) : 'smart';
        $url = isset($_POST['yperf_purge_url']) ? esc_url_raw(wp_unslash((string) $_POST['yperf_purge_url'])) : '';
        $result = PurgeController::purge($mode, $url, 'manual');
        $status = sanitize_key((string) ($result['status'] ?? ''));
        $confirmed = $status === 'pass' && !empty($result['confirmed']);
        if ($confirmed) {
            set_transient(self::noticeKey('purge'), '1', 30);
        } elseif (in_array($status, array('blocked', 'fail'), true)) {
            set_transient(self::noticeKey('purge_error'), __('Purge execution needs attention. Review the latest purge report.', 'yablonsky-performance'), 90);
        } else {
            set_transient(self::noticeKey('purge_attention'), __('Purge request sent. Provider confirmation is pending.', 'yablonsky-performance'), 90);
        }
        self::redirectDashboard('control', 'control:operations');
    }

    public static function toolbarPurge(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_toolbar_purge');
        $return = isset($_GET['yperf_return']) ? esc_url_raw(wp_unslash((string) $_GET['yperf_return'])) : '';
        $mode = isset($_GET['yperf_mode']) ? sanitize_key(wp_unslash((string) $_GET['yperf_mode'])) : 'url';
        if (!in_array($mode, array('url', 'page', 'all'), true)) {
            $mode = 'url';
        }
        $url = isset($_GET['yperf_url']) ? esc_url_raw(wp_unslash((string) $_GET['yperf_url'])) : '';
        $result = PurgeController::purge($mode, $url, 'toolbar');
        $edgeState = sanitize_key((string) ($result['edge_status'] ?? 'not_applicable'));
        $status = sanitize_key((string) ($result['status'] ?? ''));
        $confirmed = $status === 'pass' && !empty($result['confirmed']);
        if (in_array($status, array('blocked', 'fail'), true)) {
            $message = __('Purge execution needs attention. Review the latest purge report.', 'yablonsky-performance');
            $level = 'error';
        } elseif ($confirmed && $edgeState === 'accepted') {
            $message = __('Local cache provider executed the purge. Cloudflare accepted the synchronized request.', 'yablonsky-performance');
            $level = 'success';
        } elseif ($confirmed) {
            $message = __('Local cache provider executed the purge.', 'yablonsky-performance');
            $level = 'success';
        } elseif ($edgeState === 'accepted') {
            $message = __('Cloudflare accepted the purge request. Local cache provider confirmation is pending.', 'yablonsky-performance');
            $level = 'warning';
        } else {
            $message = __('Purge request sent. Provider confirmation is pending.', 'yablonsky-performance');
            $level = 'warning';
        }
        set_transient(self::noticeKey('toolbar_message'), $message, 90);
        set_transient(self::noticeKey('toolbar_message_level'), $level, 90);
        self::redirectToolbarReturn($return);
    }

    public static function toolbarOptimize(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_toolbar_optimize');
        $return = isset($_GET['yperf_return']) ? esc_url_raw(wp_unslash((string) $_GET['yperf_return'])) : '';
        ignore_user_abort(true);
        $caps = CapabilityDetector::detect();
        $runtime = RuntimeAcceptance::lastReport();
        $host = HostSnapshot::last();
        $dropins = DropinOwnership::inspect();
        $proof = NativeRuntimeProof::build($host, $dropins);
        $recommendation = Autopilot::recommend($caps, $runtime, $proof);
        if (empty($recommendation['ready'])) {
            set_transient(self::noticeKey('toolbar_message'), __('Optimize Now stopped safely because the current site baseline is not ready. No performance setting was changed.', 'yablonsky-performance'), 90);
            self::redirectToolbarReturn($return);
        }
        $profile = sanitize_key((string) ($recommendation['target_profile'] ?? 'safe'));
        $result = self::executePerformanceCampaign($profile, 'autopilot', $recommendation, true, 'optimize', 'toolbar');
        if (!empty($result['ok'])) {
            $prewarm = is_array($result['prewarm'] ?? null) ? $result['prewarm'] : array();
            $summary = is_array($prewarm['summary'] ?? null) ? $prewarm['summary'] : array();
            set_transient(self::noticeKey('toolbar_message'), sprintf(
                /* translators: 1: warmed URL count, 2: verified page-cache HIT count */
                __('Optimize Now completed. Automatic prewarm: %1$d URLs processed, %2$d verified HIT.', 'yablonsky-performance'),
                (int) (($summary['pass'] ?? 0) + ($summary['skip'] ?? 0)),
                (int) ($summary['verified_hit'] ?? 0)
            ), 90);
        } else {
            set_transient(self::noticeKey('toolbar_message'), sanitize_text_field((string) ($result['message'] ?? __('Optimize Now stopped safely.', 'yablonsky-performance'))), 90);
        }
        self::redirectToolbarReturn($return);
    }

    public static function toolbarPrewarm(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_toolbar_prewarm');
        $return = isset($_GET['yperf_return']) ? esc_url_raw(wp_unslash((string) $_GET['yperf_return'])) : '';
        ignore_user_abort(true);
        $report = Prewarm::runBoundedPlan(12, 30000);
        $summary = is_array($report['summary'] ?? null) ? $report['summary'] : array();
        set_transient(self::noticeKey('toolbar_message'), sprintf(
            /* translators: 1: passed URL count, 2: failed URL count, 3: verified HIT count */
            __('Prewarm completed: %1$d warmed, %2$d failed, %3$d verified HIT.', 'yablonsky-performance'),
            (int) ($summary['pass'] ?? 0),
            (int) ($summary['fail'] ?? 0),
            (int) ($summary['verified_hit'] ?? 0)
        ), 90);
        self::redirectToolbarReturn($return);
    }

    private static function redirectToolbarReturn(string $return): void
    {
        $fallback = admin_url('index.php');
        wp_safe_redirect($return !== '' ? $return : $fallback);
        exit;
    }

    public static function ajaxAutopilotRun(): void
    {
        self::requireCapability();
        check_ajax_referer('yperf_autopilot_ajax');
        ignore_user_abort(true);

        $caps = CapabilityDetector::detect();
        $runtime = RuntimeAcceptance::lastReport();
        $host = HostSnapshot::last();
        $dropins = DropinOwnership::inspect();
        $proof = NativeRuntimeProof::build($host, $dropins);
        $recommendation = Autopilot::recommend($caps, $runtime, $proof);
        if (empty($recommendation['ready'])) {
            MigrationActivity::reset('autopilot', __('Preparing safe YPerf baseline.', 'yablonsky-performance'), __('Capturing a fresh preflight before any local setting changes.', 'yablonsky-performance'));
            MigrationActivity::set('autopilot', 'blocked', 10, __('Stopped before making changes.', 'yablonsky-performance'), __('Fresh Health Check still contains a functional failure.', 'yablonsky-performance'), true);
            wp_send_json_error(array(
                'result' => array(
                    'ok' => false,
                    'state' => 'recommendation_blocked',
                    'message' => __('Fresh Health Check failed. No performance setting was changed.', 'yablonsky-performance'),
                ),
                'progress' => MigrationActivity::get('autopilot'),
            ));
        }

        $profile = sanitize_key((string) ($recommendation['target_profile'] ?? 'safe'));
        $result = self::executePerformanceCampaign($profile, 'autopilot', $recommendation, true, 'optimize', 'autopilot', true);
        $payload = array(
            'result' => $result,
            'progress' => MigrationActivity::get('autopilot'),
        );
        if (empty($result['ok'])) {
            wp_send_json_error($payload);
        }
        wp_send_json_success($payload);
    }

    public static function ajaxAutopilotStatus(): void
    {
        self::requireCapability();
        check_ajax_referer('yperf_autopilot_ajax');
        wp_send_json_success(array('progress' => MigrationActivity::get('autopilot')));
    }

    public static function ajaxAutopilotPrewarmFinalize(): void
    {
        self::requireCapability();
        check_ajax_referer('yperf_autopilot_ajax');
        $current = MigrationActivity::get('autopilot');
        if (!empty($current['terminal'])) {
            wp_send_json_success(array(
                'progress' => $current,
                'prewarm' => Prewarm::lastReport(),
            ));
        }
        $phase = sanitize_key((string) ($current['phase'] ?? ''));
        if (!in_array($phase, array('prewarm', 'prewarm_ready'), true)) {
            wp_send_json_error(array('progress' => $current), 409);
        }

        $prewarmOk = !isset($_POST['prewarm_ok']) || sanitize_key(wp_unslash((string) $_POST['prewarm_ok'])) === '1';
        set_transient(self::noticeKey('autopilot'), '1', 30);
        MigrationActivity::set(
            'autopilot',
            'accepted',
            100,
            $prewarmOk ? __('Baseline accepted and prewarmed.', 'yablonsky-performance') : __('Baseline accepted.', 'yablonsky-performance'),
            $prewarmOk ? __('Runtime Acceptance proved the selected YPerf profile. Bounded automatic prewarm completed without changing the acceptance decision.', 'yablonsky-performance') : __('Baseline accepted. Runtime proof is clean.', 'yablonsky-performance'),
            true
        );
        wp_send_json_success(array(
            'progress' => MigrationActivity::get('autopilot'),
            'prewarm' => Prewarm::lastReport(),
        ));
    }

    public static function ajaxMigrationBaselineRun(): void
    {
        self::requireCapability();
        check_ajax_referer('yperf_migration_ajax');
        ignore_user_abort(true);
        $profile = isset($_POST['profile']) ? sanitize_key(wp_unslash((string) $_POST['profile'])) : 'safe';
        if (!in_array($profile, array('safe', 'commerce'), true)) {
            $profile = 'safe';
        }
        $result = self::executePerformanceCampaign($profile, 'manual', array(), true, 'migration');
        $payload = array(
            'result' => $result,
            'progress' => MigrationActivity::get('baseline'),
            'assessment' => W3tcMigrationExecutor::assessment(),
        );
        if (empty($result['ok'])) {
            wp_send_json_error($payload);
        }
        wp_send_json_success($payload);
    }

    public static function ajaxMigrationBaselineStatus(): void
    {
        self::requireCapability();
        check_ajax_referer('yperf_migration_ajax');
        wp_send_json_success(array('progress' => MigrationActivity::get('baseline')));
    }

    public static function ajaxMigrationResponsibilityCheck(): void
    {
        self::requireCapability();
        check_ajax_referer('yperf_migration_ajax');
        $caps = CapabilityDetector::detect();
        $runtime = RuntimeAcceptance::lastReport();
        $host = HostSnapshot::last();
        $dropins = DropinOwnership::inspect();
        $proof = NativeRuntimeProof::build($host, $dropins);
        $nativePage = NativePageCacheAdoption::analyze($host, $proof, $runtime);
        $nativePlan = NativeAdoptionPlan::build($caps, $host, $proof, $nativePage);
        $plan = PerformancePlan::build($caps, $runtime, $proof, $nativePlan);
        $effectiveness = EffectivePerformance::build($caps, $runtime, $proof, $plan);
        $gate = ProviderExitGate::evaluate($caps, $runtime, $effectiveness, $dropins);
        $assessment = W3tcMigrationExecutor::assessment();
        wp_send_json_success(array(
            'ready' => !empty($assessment['ready']),
            'assessment' => $assessment,
            'exit_gate' => $gate,
        ));
    }

    public static function ajaxMigrationRetireResponsibility(): void
    {
        self::requireCapability();
        check_ajax_referer('yperf_migration_ajax');
        ignore_user_abort(true);
        $responsibility = isset($_POST['responsibility']) ? sanitize_key(wp_unslash((string) $_POST['responsibility'])) : '';
        $result = W3tcResponsibilityResolver::retire($responsibility);
        if (empty($result['ok'])) {
            wp_send_json_error(array(
                'message' => sanitize_text_field((string) ($result['message'] ?? __('The provider responsibility could not be retired safely.', 'yablonsky-performance'))),
            ));
        }

        $caps = CapabilityDetector::detect();
        $runtime = RuntimeAcceptance::lastReport();
        $host = HostSnapshot::last();
        $dropins = DropinOwnership::inspect();
        $proof = NativeRuntimeProof::build($host, $dropins);
        $nativePage = NativePageCacheAdoption::analyze($host, $proof, $runtime);
        $nativePlan = NativeAdoptionPlan::build($caps, $host, $proof, $nativePage);
        $plan = PerformancePlan::build($caps, $runtime, $proof, $nativePlan);
        $effectiveness = EffectivePerformance::build($caps, $runtime, $proof, $plan);
        $gate = ProviderExitGate::evaluate($caps, $runtime, $effectiveness, $dropins);
        $assessment = W3tcMigrationExecutor::assessment();

        wp_send_json_success(array(
            'result' => $result,
            'ready' => !empty($assessment['ready']),
            'assessment' => $assessment,
            'exit_gate' => $gate,
        ));
    }

    public static function ajaxPageCacheCampaignStart(): void
    {
        self::requireCapability();
        check_ajax_referer('yperf_page_cache_campaign_ajax');
        $result = PageCacheCampaign::start();
        if (empty($result['ok'])) {
            wp_send_json_error(array('message' => sanitize_text_field((string) ($result['message'] ?? __('The Page Cache stability campaign could not start.', 'yablonsky-performance')))));
        }
        wp_send_json_success(array('state' => $result));
    }

    public static function ajaxPageCacheCampaignStep(): void
    {
        self::requireCapability();
        check_ajax_referer('yperf_page_cache_campaign_ajax');
        ignore_user_abort(true);
        $step = isset($_POST['step']) ? sanitize_key(wp_unslash((string) $_POST['step'])) : '';
        $result = PageCacheCampaign::step($step);
        if (empty($result['ok'])) {
            $state = is_array($result['state'] ?? null) ? $result['state'] : PageCacheCampaign::state();
            wp_send_json_error(array(
                'message' => sanitize_text_field((string) ($result['message'] ?? __('The Page Cache stability campaign stopped safely.', 'yablonsky-performance'))),
                'state' => $state,
            ));
        }
        wp_send_json_success(array('state' => $result));
    }

    public static function ajaxPageCacheCampaignStatus(): void
    {
        self::requireCapability();
        check_ajax_referer('yperf_page_cache_campaign_ajax');
        wp_send_json_success(array(
            'state' => PageCacheCampaign::state(),
            'report' => PageCacheCampaign::report(),
        ));
    }

    public static function ajaxMigrationRun(): void
    {
        self::requireCapability();
        check_ajax_referer('yperf_migration_ajax');
        ignore_user_abort(true);
        $result = W3tcMigrationExecutor::migrate();
        if (empty($result['ok'])) {
            wp_send_json_error(array(
                'message' => sanitize_text_field((string) ($result['message'] ?? __('W3 Total Cache migration failed.', 'yablonsky-performance'))),
                'progress' => W3tcMigrationExecutor::progress(),
            ));
        }
        wp_send_json_success(array('result' => $result, 'progress' => W3tcMigrationExecutor::progress()));
    }

    public static function ajaxMigrationStatus(): void
    {
        self::requireCapability();
        check_ajax_referer('yperf_migration_ajax');
        wp_send_json_success(array('progress' => W3tcMigrationExecutor::progress()));
    }

    public static function migrateW3tc(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_migrate_w3tc');
        $result = W3tcMigrationExecutor::migrate();
        if (!empty($result['ok']) && !empty($result['requires_user_action'])) {
            set_transient(self::noticeKey('w3tc_user_handoff'), '1', 120);
        } elseif (!empty($result['ok'])) {
            set_transient(self::noticeKey('w3tc_migration'), '1', 90);
        } else {
            set_transient(self::noticeKey('feature_control_error'), sanitize_text_field((string) ($result['message'] ?? __('W3 Total Cache migration failed.', 'yablonsky-performance'))), 120);
        }
        self::redirectDashboard('migration');
    }

    public static function rollbackW3tc(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_rollback_w3tc');
        $result = W3tcMigrationExecutor::rollback();
        if (!empty($result['ok'])) {
            set_transient(self::noticeKey('w3tc_rollback'), '1', 90);
        } else {
            set_transient(self::noticeKey('feature_control_error'), sanitize_text_field((string) ($result['message'] ?? __('W3 Total Cache rollback failed.', 'yablonsky-performance'))), 120);
        }
        self::redirectDashboard('migration');
    }

    public static function controlFeature(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_control_feature');
        $feature = isset($_POST['yperf_feature']) ? sanitize_key(wp_unslash((string) $_POST['yperf_feature'])) : '';
        $operation = isset($_POST['yperf_operation']) ? sanitize_key(wp_unslash((string) $_POST['yperf_operation'])) : '';
        $result = array('ok' => false, 'message' => __('Unsupported YPerf control operation.', 'yablonsky-performance'));

        if ($feature === 'page-cache') {
            if ($operation === 'enable') {
                $result = PageCacheExecutor::activate(false);
                if (!empty($result['ok'])) {
                    $proof = PageCacheExecutor::prove();
                    if (empty($proof['ok'])) {
                        PageCacheExecutor::disable(false);
                        $result = array('ok' => false, 'message' => __('YPerf Page Cache did not prove MISS → HIT, so activation was rolled back.', 'yablonsky-performance'));
                    } else {
                        $runtime = RuntimeAcceptance::run();
                        update_option('yperf_runtime_acceptance_v1', $runtime, false);
                        Telemetry::recordRuntime($runtime);
                        RuntimeHistory::record($runtime, 'page_cache_activation');
                        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
                        if ((int) ($summary['fail'] ?? 0) > 0) {
                            PageCacheExecutor::disable(false);
                            $result = array('ok' => false, 'message' => __('Runtime Acceptance failed after Page Cache activation, so YPerf rolled the cache back.', 'yablonsky-performance'));
                        }
                    }
                }
            } elseif ($operation === 'repair') {
                $result = PageCacheExecutor::repair();
            } elseif ($operation === 'disable') {
                // Browser Cache is a policy implemented by the YPerf page-cache
                // executor. Turning the executor off must not leave a phantom ON
                // switch behind in the user interface.
                BrowserCacheController::setEnabled(false);
                $result = PageCacheExecutor::disable(false);
            }
        } elseif ($feature === 'object-cache') {
            if ($operation === 'prepare') {
                $host = RedisEvidence::resolve(HostSnapshot::last());
                $dropins = DropinOwnership::inspect();
                $runtime = RuntimeAcceptance::lastReport();
                $benchmark = Benchmark::lastReport();
                $result = ObjectCacheAdoption::reserve($host, $dropins, $runtime, $benchmark);
            } elseif ($operation === 'enable') {
                $host = RedisEvidence::resolve(HostSnapshot::last());
                $dropins = DropinOwnership::inspect();
                $runtime = RuntimeAcceptance::lastReport();
                $benchmark = Benchmark::lastReport();
                $adoption = ObjectCacheAdoption::analyze($host, $dropins, $runtime, $benchmark);
                $contract = ObjectCacheOperationContract::build($adoption, $host, $dropins, $runtime);
                $fingerprint = (string) (($contract['contract']['fingerprint'] ?? ''));
                $result = ObjectCacheExecutor::activate($contract, $fingerprint);
                if (!empty($result['ok'])) {
                    $url = wp_nonce_url(admin_url('admin-post.php?action=yperf_finalize_object_cache'), 'yperf_finalize_object_cache');
                    wp_safe_redirect($url);
                    exit;
                }
            } elseif ($operation === 'disable') {
                $result = ObjectCacheExecutor::rollback();
            }
        } elseif ($feature === 'browser-cache') {
            if ($operation === 'enable') {
                $pageRepair = PageCacheExecutor::repair();
                if (empty($pageRepair['ok'])) {
                    $result = array('ok' => false, 'message' => __('YPerf could not verify Page Cache first, so Browser Cache was not enabled.', 'yablonsky-performance'));
                } else {
                    if (!BrowserCacheController::setEnabled(true)) {
                        $result = array('ok' => false, 'message' => __('Browser Cache could not refresh the YPerf-owned page-cache drop-in safely.', 'yablonsky-performance'));
                    } else {
                    $proof = BrowserCacheController::prove();
                    if (empty($proof['ok'])) {
                        BrowserCacheController::setEnabled(false);
                        $result = array('ok' => false, 'message' => __('Browser Cache response-header proof failed, so the policy was rolled back.', 'yablonsky-performance'));
                    } else {
                        $result = array('ok' => true, 'state' => 'active');
                    }
                    }
                }
            } elseif ($operation === 'disable') {
                $result = BrowserCacheController::setEnabled(false)
                    ? array('ok' => true, 'state' => 'off')
                    : array('ok' => false, 'message' => __('Browser Cache could not refresh the YPerf-owned page-cache drop-in safely.', 'yablonsky-performance'));
            }
        } elseif ($feature === 'frontend-optimizer') {
            $result = self::applyFrontendControlWithProof($operation);
        } elseif ($feature === 'video-lite') {
            $result = self::applyVideoLiteControl($operation);
        } elseif ($feature === 'first-hit-guard') {
            $result = self::applyBooleanControlWithProof('yperf_deep_archive_guard', $operation, 'first_hit_guard');
        }

        if (!empty($result['ok'])) {
            set_transient(self::noticeKey($feature === 'video-lite' ? 'video_lite_control' : 'feature_control'), '1', 60);
        } elseif ($feature === 'object-cache' && $operation === 'prepare' && in_array(sanitize_key((string) ($result['state'] ?? '')), array('dropin_owned_elsewhere', 'already_active'), true)) {
            set_transient(self::noticeKey('object_cache_attention'), sanitize_text_field((string) ($result['message'] ?? __('Existing Object Cache detected. YPerf left it untouched.', 'yablonsky-performance'))), 90);
        } else {
            set_transient(self::noticeKey('feature_control_error'), sanitize_text_field((string) ($result['message'] ?? __('The YPerf control operation failed.', 'yablonsky-performance'))), 90);
        }
        $returnTab = isset($_POST['yperf_return_tab']) ? sanitize_key(wp_unslash((string) $_POST['yperf_return_tab'])) : 'optimize';
        $returnSection = isset($_POST['yperf_return_section']) ? sanitize_text_field(wp_unslash((string) $_POST['yperf_return_section'])) : '';
        self::redirectDashboard($returnTab, $returnSection);
    }

    /** @return array<string,mixed> */
    private static function applyFrontendControlWithProof(string $operation): array
    {
        if (!in_array($operation, array('enable', 'disable'), true)) {
            return array('ok' => false, 'message' => __('Unknown frontend control operation.', 'yablonsky-performance'));
        }
        $before = FrontendOptimizer::settingsSnapshot();
        FrontendOptimizer::applyProfile($operation === 'enable' ? 'safe' : 'observe');
        if (PageCacheExecutor::enabled()) {
            PageCacheExecutor::purgeAll('frontend_control_change');
        }
        $runtime = RuntimeAcceptance::run();
        update_option('yperf_runtime_acceptance_v1', $runtime, false);
        Telemetry::recordRuntime($runtime);
        RuntimeHistory::record($runtime, 'frontend_delivery_' . $operation);
        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        if ((int) ($summary['fail'] ?? 0) > 0) {
            FrontendOptimizer::restoreSettings($before);
            if (PageCacheExecutor::enabled()) {
                PageCacheExecutor::purgeAll('frontend_control_rollback');
            }
            $rollback = RuntimeAcceptance::run();
            update_option('yperf_runtime_acceptance_v1', $rollback, false);
            Telemetry::recordRuntime($rollback);
            RuntimeHistory::record($rollback, 'frontend_delivery_rollback');
            return array('ok' => false, 'message' => __('Frontend delivery proof failed, so YPerf restored the previous frontend policy automatically.', 'yablonsky-performance'));
        }
        return array('ok' => true, 'state' => $operation === 'enable' ? 'active' : 'off');
    }

    /**
     * Video Lite is a low-overhead, independently reversible public policy.
     * Do not run another full Runtime Acceptance on this setting: a health probe
     * can consume multiple cold Woo origin slots and must remain a separate step.
     * Require the current MU-runtime contract before writing the option.
     * An external Nginx cache is not owned or silently invalidated here.
     * @return array<string,mixed>
     */
    private static function applyVideoLiteControl(string $operation): array
    {
        if (!in_array($operation, array('enable', 'disable'), true)) {
            return array('ok' => false, 'message' => __('Unknown control operation.', 'yablonsky-performance'));
        }
        if ($operation === 'enable' && !FrontendOptimizer::enabled()) {
            return array('ok' => false, 'message' => __('Enable Frontend Optimization before enabling media delivery optimization.', 'yablonsky-performance'));
        }
        $early = RuntimeAcceptance::runStep('yperf_early_runtime');
        if ((string) ($early['status'] ?? '') !== 'pass') {
            return array('ok' => false, 'message' => __('The current YPerf early runtime must pass before media optimization can be changed.', 'yablonsky-performance'));
        }
        $option = FrontendOptimizer::OPTION_VIDEO_LITE_PUBLIC;
        $before = get_option($option, '0') === '1' ? '1' : '0';
        $wanted = $operation === 'enable' ? '1' : '0';
        if ($before !== $wanted) {
            update_option($option, $wanted, false);
        }
        if ((get_option($option, '0') === '1' ? '1' : '0') !== $wanted) {
            update_option($option, $before, false);
            return array('ok' => false, 'message' => __('Media optimization setting verification failed; the previous value was restored.', 'yablonsky-performance'));
        }
        // Activation is proven at WordPress origin only. A separate external
        // cache connector/proof is required before claiming all HITs are fresh.
        return array('ok' => true, 'state' => $wanted === '1' ? 'origin_enabled' : 'origin_disabled');
    }

    /** @return array<string,mixed> */
    private static function applyBooleanControlWithProof(string $option, string $operation, string $historyContext): array
    {
        if (!in_array($operation, array('enable', 'disable'), true)) {
            return array('ok' => false, 'message' => __('Unknown control operation.', 'yablonsky-performance'));
        }
        $before = get_option($option, '0') === '1' ? '1' : '0';
        $after = $operation === 'enable' ? '1' : '0';
        update_option($option, $after, false);
        $runtime = RuntimeAcceptance::run();
        update_option('yperf_runtime_acceptance_v1', $runtime, false);
        Telemetry::recordRuntime($runtime);
        RuntimeHistory::record($runtime, $historyContext . '_' . $operation);
        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        if ((int) ($summary['fail'] ?? 0) > 0) {
            update_option($option, $before, false);
            $rollback = RuntimeAcceptance::run();
            update_option('yperf_runtime_acceptance_v1', $rollback, false);
            Telemetry::recordRuntime($rollback);
            RuntimeHistory::record($rollback, $historyContext . '_rollback');
            return array('ok' => false, 'message' => __('Runtime Acceptance failed, so YPerf restored the previous setting automatically.', 'yablonsky-performance'));
        }
        return array('ok' => true, 'state' => $after === '1' ? 'active' : 'off');
    }

    public static function dismissRecommendation(): void
    {
        self::requireCapability();
        check_admin_referer('yperf_dismiss_recommendation');
        $id = isset($_POST['yperf_recommendation_id']) ? sanitize_key(wp_unslash((string) $_POST['yperf_recommendation_id'])) : '';
        if ($id !== '') {
            $items = get_option('yperf_dismissed_recommendations_v1', array());
            if (!is_array($items)) {
                $items = array();
            }
            $items[$id] = time();
            update_option('yperf_dismissed_recommendations_v1', $items, false);
        }
        self::redirectDashboard('insights');
    }

    public static function ajaxEnvironmentRefresh(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You are not allowed to perform this action.', 'yablonsky-performance')), 403);
        }
        check_ajax_referer('yperf_environment_refresh_ajax');
        $report = EnvironmentCheck::run(CapabilityDetector::detect());
        wp_send_json_success(array('report' => $report));
    }

    public static function ajaxBenchmarkStep(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You are not allowed to perform this action.', 'yablonsky-performance')), 403);
        }
        check_ajax_referer('yperf_benchmark_ajax');
        $step = isset($_POST['step']) ? sanitize_key(wp_unslash((string) $_POST['step'])) : '';
        $allowed = array();
        foreach (Benchmark::stepDefinitions() as $definition) {
            $allowed[] = $definition['id'];
        }
        if ($step === '' || !in_array($step, $allowed, true)) {
            wp_send_json_error(array('message' => __('Unknown benchmark step.', 'yablonsky-performance')), 400);
        }
        wp_send_json_success(array('result' => Benchmark::runStep($step)));
    }

    public static function ajaxBenchmarkFinish(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You are not allowed to perform this action.', 'yablonsky-performance')), 403);
        }
        check_ajax_referer('yperf_benchmark_ajax');
        $raw = isset($_POST['results']) ? sanitize_textarea_field(wp_unslash((string) $_POST['results'])) : '';
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            wp_send_json_error(array('message' => __('Benchmark results were not valid JSON.', 'yablonsky-performance')), 400);
        }
        wp_send_json_success(array('report' => Benchmark::buildReport($decoded)));
    }

    public static function ajaxPrewarmStep(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You are not allowed to perform this action.', 'yablonsky-performance')), 403);
        }
        check_ajax_referer('yperf_prewarm_ajax');
        $url = isset($_POST['url']) ? esc_url_raw(wp_unslash((string) $_POST['url'])) : '';
        wp_send_json_success(array('result' => Prewarm::runUrl($url)));
    }

    public static function ajaxPrewarmFinish(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You are not allowed to perform this action.', 'yablonsky-performance')), 403);
        }
        check_ajax_referer('yperf_prewarm_ajax');
        $raw = isset($_POST['results']) ? sanitize_textarea_field(wp_unslash((string) $_POST['results'])) : '';
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            wp_send_json_error(array('message' => __('Prewarm results were not valid JSON.', 'yablonsky-performance')), 400);
        }
        wp_send_json_success(array('report' => Prewarm::buildReport($decoded)));
    }

    public static function ajaxRuntimeAcceptanceStep(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You are not allowed to perform this action.', 'yablonsky-performance')), 403);
        }
        check_ajax_referer('yperf_runtime_acceptance_ajax');
        $step = isset($_POST['step']) ? sanitize_key(wp_unslash((string) $_POST['step'])) : '';
        $allowed = array();
        foreach (RuntimeAcceptance::stepDefinitions() as $definition) {
            $allowed[] = $definition['id'];
        }
        if ($step === '' || !in_array($step, $allowed, true)) {
            wp_send_json_error(array('message' => __('Unknown Runtime Acceptance step.', 'yablonsky-performance')), 400);
        }
        wp_send_json_success(array('test' => RuntimeAcceptance::runStep($step)));
    }

    public static function ajaxRuntimeAcceptanceFinish(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You are not allowed to perform this action.', 'yablonsky-performance')), 403);
        }
        check_ajax_referer('yperf_runtime_acceptance_ajax');
        $raw = isset($_POST['tests']) ? sanitize_textarea_field(wp_unslash((string) $_POST['tests'])) : '';
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            wp_send_json_error(array('message' => __('Runtime Acceptance results were not valid JSON.', 'yablonsky-performance')), 400);
        }
        $report = RuntimeAcceptance::buildReport($decoded);
        update_option('yperf_runtime_acceptance_v1', $report, false);
        Telemetry::recordRuntime($report);
        RuntimeHistory::record($report, 'manual_ajax');
        wp_send_json_success(array('report' => $report));
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $caps = CapabilityDetector::detect();
        $snapshot = Adoption::lastSnapshot();
        $runtime = RuntimeAcceptance::lastReport();
        $environmentCheck = EnvironmentCheck::lastReport();
        $hostSnapshot = HostSnapshot::last();
        $dropinOwnership = DropinOwnership::inspect();
        $benchmark = Benchmark::lastReport();
        $nativeRuntimeProof = NativeRuntimeProof::build($hostSnapshot, $dropinOwnership);
        $nativePageCacheAdoption = NativePageCacheAdoption::analyze($hostSnapshot, $nativeRuntimeProof, $runtime);
        $nativePageCacheOperations = NativePageCacheOperations::analyze($hostSnapshot, $nativePageCacheAdoption, $runtime);
        $objectCacheRedisEvidence = RedisEvidence::resolve($hostSnapshot);
        $objectCacheAdoption = ObjectCacheAdoption::analyze($objectCacheRedisEvidence, $dropinOwnership, $runtime, $benchmark);
        $originBudgetReadiness = OriginBudgetReadiness::analyze($hostSnapshot, $runtime);
        $nativeAdoptionPlan = NativeAdoptionPlan::build($caps, $hostSnapshot, $nativeRuntimeProof, $nativePageCacheAdoption);
        $performancePlan = PerformancePlan::build($caps, $runtime, $nativeRuntimeProof, $nativeAdoptionPlan);
        $effectiveness = EffectivePerformance::build($caps, $runtime, $nativeRuntimeProof, $performancePlan);
        $optimizationOutcome = OptimizationOutcome::last();
        $runtimeHistory = RuntimeHistory::analyze();
        $pageCacheCampaignReport = PageCacheCampaign::report();
        $pageCacheCampaignState = PageCacheCampaign::state();
        $autopilot = Autopilot::recommend($caps, $runtime, $nativeRuntimeProof);
        $aiAdvisorStatus = AIAdvisor::availability();
        $aiAdvisorRecommendation = AIAdvisor::lastRecommendation();
        $aiAdvisorAnswer = AIAdvisor::lastAnswer();
        $campaignReport = OptimizationCampaign::report();
        $upgradeHealth = UpgradeHealth::report();
        $controlPlaneReadiness = ControlPlaneReadiness::analyze($upgradeHealth, $hostSnapshot, $runtime);
        $objectCacheOperationContract = ObjectCacheOperationContract::build($objectCacheAdoption, $objectCacheRedisEvidence, $dropinOwnership, $runtime);
        $objectCacheExecution = ObjectCacheExecutor::status();
        $originBudgetContract = OriginBudgetContract::build($originBudgetReadiness, $controlPlaneReadiness, $hostSnapshot);
        $originBudgetAdoption = OriginBudgetAdoption::analyze($originBudgetContract, $hostSnapshot, $runtime);
        $effectiveness = EffectivePerformance::build($caps, $runtime, $nativeRuntimeProof, $performancePlan, $originBudgetAdoption);
        $providerExitGate = ProviderExitGate::evaluate($caps, $runtime, $effectiveness, $dropinOwnership);
        $portablePageCache = PageCacheExecutor::status();
        $releaseReadiness = ReleaseReadiness::build($runtime, $controlPlaneReadiness, $nativePageCacheOperations, $objectCacheOperationContract, $originBudgetContract, $objectCacheExecution, $originBudgetAdoption, $providerExitGate, $portablePageCache, $pageCacheCampaignReport);
        $evidenceReconciliation = EvidenceReconciler::build($caps, $environmentCheck, $hostSnapshot, $dropinOwnership, $runtime, $benchmark, $nativeRuntimeProof);
        $productProgress = ProductProgress::build($releaseReadiness, $environmentCheck, $runtime, $effectiveness, $providerExitGate, $evidenceReconciliation);
        $lifecyclePolicy = LifecyclePolicy::report();
        $telemetry = Telemetry::report();
        $inspection = CacheabilityInspector::lastReport();
        $purgeResult = PurgeController::lastResult();
        $prewarm = Prewarm::lastReport();
        $cloudflare = CloudflareEdge::status();
        $lastInvalidation = get_option('yperf_last_invalidation', array());
        $invalidationLog = get_option('yperf_invalidation_log_v1', array());
        if (!is_array($invalidationLog)) {
            $invalidationLog = array();
        }
        $guardEnabled = get_option('yperf_deep_archive_guard', '0') === '1';
        $features = FeatureCatalog::build($caps, $runtime, $guardEnabled, $effectiveness);
        $migration = Migration::plan($caps, $providerExitGate);
        $migrationJourney = MigrationJourney::build($migration, $runtime, $benchmark, $performancePlan);
        $muPluginInventory = MuPluginInventory::scan();
        $fieldAcceptance = FieldAcceptance::report($releaseReadiness, $muPluginInventory);
        $intelligence = IntelligenceReport::build($caps, $runtime, $features, $invalidationLog, $environmentCheck, $benchmark, $telemetry, $migration, $inspection, $prewarm, $hostSnapshot, $nativeAdoptionPlan, $dropinOwnership, $evidenceReconciliation, $nativeRuntimeProof, $performancePlan, $effectiveness, $optimizationOutcome, $runtimeHistory, $autopilot, $campaignReport, $upgradeHealth, $nativePageCacheAdoption, $nativePageCacheOperations, $objectCacheAdoption, $originBudgetReadiness, $controlPlaneReadiness, $objectCacheOperationContract, $originBudgetContract, $releaseReadiness, $objectCacheExecution, $originBudgetAdoption, $purgeResult);
        $recommendations = is_array($intelligence['recommendations'] ?? null) ? $intelligence['recommendations'] : array();
        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        $fail = (int) ($summary['fail'] ?? 0);
        $pass = (int) ($summary['pass'] ?? 0);
        $skip = (int) ($summary['skip'] ?? 0);
        $attention = (int) ($summary['attention'] ?? 0);
        $runtimeVersionCurrent = $runtime !== array() && (string) ($runtime['version'] ?? '') === YPERF_VERSION;
        $runtimeState = $runtime === array() ? 'not-run' : (($fail > 0 || !$runtimeVersionCurrent) ? 'attention' : 'healthy');
        $runtimeLabel = $runtime === array()
            ? __('Not checked', 'yablonsky-performance')
            : (!$runtimeVersionCurrent ? __('Recheck after upgrade', 'yablonsky-performance') : ($fail > 0 ? __('Needs review', 'yablonsky-performance') : __('Healthy', 'yablonsky-performance')));

        $headerPerfState = sanitize_key((string) ($effectiveness['overall_state'] ?? 'observe'));
        $pageLayer = is_array($effectiveness['layers']['page_cache'] ?? null) ? $effectiveness['layers']['page_cache'] : array();
        $pageHealthyNow = (string) ($pageLayer['state'] ?? '') === 'active' && !empty($pageLayer['proven']);
        $siteHealthyNow = $runtimeVersionCurrent && $fail === 0;
        $optimizationHealthyNow = $siteHealthyNow && $headerPerfState === 'active' && $pageHealthyNow;
        $headerPerfLabel = $optimizationHealthyNow
            ? __('Optimized', 'yablonsky-performance')
            : (!$runtimeVersionCurrent
                ? __('Recheck after upgrade', 'yablonsky-performance')
                : ($fail > 0
                    ? __('Needs review', 'yablonsky-performance')
                    : ($headerPerfState === 'active' ? __('Active · verify', 'yablonsky-performance') : __('Needs optimization', 'yablonsky-performance'))));
        $headerPerfClass = $optimizationHealthyNow ? 'yperf-pill-free' : 'yperf-pill-attention';
        echo '<div class="wrap yperf-wrap yperf-layout-v2" data-yperf-layout-contract="market-control-v4" data-yperf-migration-available="' . esc_attr(!empty($migrationJourney['applicable']) ? '1' : '0') . '">';
        echo '<div class="yperf-header">';
        echo '<div><h1>' . esc_html__('Yablonsky Performance', 'yablonsky-performance') . '</h1>';
        echo '<p>' . esc_html__('Fast WordPress, automatic by default. Manual control when you want it.', 'yablonsky-performance') . '</p></div>';
        echo '<div class="yperf-header-badges"><form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="yperf-header-optimize-form"><input type="hidden" name="action" value="yperf_run_autopilot">';
        wp_nonce_field('yperf_run_autopilot');
        echo '<button type="submit" class="button button-primary">' . esc_html__('Optimize Now', 'yablonsky-performance') . '</button></form><span class="yperf-pill ' . esc_attr($headerPerfClass) . '">' . esc_html($headerPerfLabel) . '</span><span class="yperf-pill">v' . esc_html(YPERF_VERSION) . '</span><span class="yperf-pill yperf-pill-muted">' . esc_html__('Stable', 'yablonsky-performance') . '</span><span class="yperf-pill yperf-pill-free">' . esc_html__('Local features · Free', 'yablonsky-performance') . '</span></div>';
        echo '</div>';

        self::renderNotices();
        echo '<div class="yperf-autopilot-progress-surface" data-yperf-autopilot-progress hidden aria-live="polite">';
        echo '<div class="yperf-progress-top"><strong data-yperf-autopilot-label>' . esc_html__('Preparing safe YPerf baseline…', 'yablonsky-performance') . '</strong><span data-yperf-autopilot-percent>0%</span></div>';
        echo '<div class="yperf-progress is-animated" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span data-yperf-autopilot-bar></span></div>';
        echo '<div class="yperf-migration-stages yperf-autopilot-stages"><div data-threshold="10"><span>○</span><strong>' . esc_html__('1. Fresh preflight', 'yablonsky-performance') . '</strong></div><div data-threshold="42"><span>○</span><strong>' . esc_html__('2. Apply baseline', 'yablonsky-performance') . '</strong></div><div data-threshold="58"><span>○</span><strong>' . esc_html__('3. Runtime proof', 'yablonsky-performance') . '</strong></div><div data-threshold="78"><span>○</span><strong>' . esc_html__('4. Regression guard', 'yablonsky-performance') . '</strong></div><div data-threshold="100"><span>○</span><strong>' . esc_html__('5. Accept / rollback', 'yablonsky-performance') . '</strong></div></div>';
        echo '<div class="yperf-migration-status-line"><strong data-yperf-autopilot-phase>' . esc_html__('No baseline action is running.', 'yablonsky-performance') . '</strong><span data-yperf-autopilot-detail>' . esc_html__('Press Apply & Prove to begin.', 'yablonsky-performance') . '</span></div>';
        echo '<div class="yperf-migration-events" data-yperf-autopilot-events></div></div>';

        echo '<div class="yperf-tabs" role="tablist" aria-label="' . esc_attr__('YPerf dashboard sections', 'yablonsky-performance') . '">';
        echo '<button type="button" class="yperf-tab is-active" role="tab" aria-selected="true" tabindex="0" data-yperf-tab="overview">' . esc_html__('Overview', 'yablonsky-performance') . '</button>';
        echo '<button type="button" class="yperf-tab" role="tab" aria-selected="false" tabindex="-1" data-yperf-tab="optimize">' . esc_html__('Optimize' , 'yablonsky-performance') . '</button>';
        echo '<button type="button" class="yperf-tab" role="tab" aria-selected="false" tabindex="-1" data-yperf-tab="health">' . esc_html__('Health Check', 'yablonsky-performance') . '</button>';
        echo '<button type="button" class="yperf-tab yperf-tab-cache" role="tab" aria-selected="false" tabindex="-1" data-yperf-tab="control">' . esc_html__('Cache', 'yablonsky-performance') . '</button>';
        $migrationTabLabel = !empty($migrationJourney['history_only']) ? __('Migration · Completed', 'yablonsky-performance') : __('Migration', 'yablonsky-performance');
        echo '<button type="button" class="yperf-tab yperf-tab-migration" role="tab" aria-selected="false" tabindex="-1" data-yperf-tab="migration">' . esc_html($migrationTabLabel) . '</button>';
        echo '<button type="button" class="yperf-tab" role="tab" aria-selected="false" tabindex="-1" data-yperf-tab="setup">' . esc_html__('Settings', 'yablonsky-performance') . '</button>';
        echo '<button type="button" class="yperf-tab" role="tab" aria-selected="false" tabindex="-1" data-yperf-tab="insights">' . esc_html__('Insights', 'yablonsky-performance') . '</button>';
        echo '<button type="button" class="yperf-tab" role="tab" aria-selected="false" tabindex="-1" data-yperf-tab="diagnostics">' . esc_html__('Diagnostics', 'yablonsky-performance') . '</button>';
        echo '<button type="button" class="yperf-tab" role="tab" aria-selected="false" tabindex="-1" data-yperf-tab="advanced">' . esc_html__('Advanced', 'yablonsky-performance') . '</button>';
        echo '</div>';

        echo '<section class="yperf-tab-panel is-active" data-yperf-panel="overview">';
        self::renderPanelSubnav('overview', array(
            'status' => __('Status', 'yablonsky-performance'),
            'origin' => __('Origin Cost', 'yablonsky-performance'),
        ), 'status');

        echo '<div class="yperf-subpanel" data-yperf-subpanel="overview:status">';
        echo '<div class="yperf-kpi-grid">';
        self::renderKpiCard(__('Health', 'yablonsky-performance'), $runtimeLabel, self::runtimeMeta($pass, $fail, $skip), $runtimeState, '', 'health');
        $effectiveLayers = is_array($effectiveness['layers'] ?? null) ? $effectiveness['layers'] : array();
        $pageLayer = isset($effectiveLayers['page_cache']) && is_array($effectiveLayers['page_cache']) ? $effectiveLayers['page_cache'] : array();
        $pageState = sanitize_key((string) ($pageLayer['state'] ?? 'not_proven'));
        self::renderKpiCard(__('Page cache', 'yablonsky-performance'), self::executionStateLabel($pageState), (string) ($pageLayer['provider'] ?? __('No proven executor', 'yablonsky-performance')), $pageState === 'active' ? 'healthy' : ($pageState === 'needs_review' ? 'attention' : 'neutral'), 'page-cache');
        $objectLayer = isset($effectiveLayers['object_cache']) && is_array($effectiveLayers['object_cache']) ? $effectiveLayers['object_cache'] : array();
        $objectState = sanitize_key((string) ($objectLayer['state'] ?? 'off'));
        self::renderKpiCard(__('Object cache', 'yablonsky-performance'), self::executionStateLabel($objectState), (string) ($objectLayer['provider'] ?? __('No adopted executor', 'yablonsky-performance')), $objectState === 'active' ? 'healthy' : ($objectState === 'needs_review' ? 'attention' : 'neutral'), 'object-cache');
        $serverFamily = strtoupper((string) ($caps['web_server']['family'] ?? 'unknown'));
        $serverMeta = (string) ($caps['origin']['php_sapi'] ?? 'unknown');
        if (!empty($caps['opcode_cache']['enabled'])) {
            $serverMeta .= ' · ' . __('OPcache active', 'yablonsky-performance');
        }
        self::renderKpiCard(__('Server', 'yablonsky-performance'), $serverFamily, $serverMeta, $serverFamily !== 'UNKNOWN' ? 'healthy' : 'neutral', '', 'diagnostics');
        echo '</div>';
        self::renderPerformanceState($performancePlan, $effectiveness);
        self::renderNextStepCard($runtime, $autopilot, $effectiveness, $environmentCheck);
        echo '</div>';

        echo '<div class="yperf-subpanel" data-yperf-subpanel="overview:origin">';
        self::renderOriginCostSummary($runtime, false);
        echo '</div>';
        echo '</section>';

        echo '<section class="yperf-tab-panel" data-yperf-panel="optimize" hidden>';
        self::renderPerformanceActivationCenter($performancePlan, $caps, $runtime, $nativeAdoptionPlan, $nativeRuntimeProof, $effectiveness, $optimizationOutcome, $autopilot, $runtimeHistory, $campaignReport, $aiAdvisorStatus, $aiAdvisorRecommendation, $aiAdvisorAnswer);
        echo '</section>';

        echo '<section class="yperf-tab-panel" data-yperf-panel="health" hidden>';
        echo '<div class="yperf-main-grid">';
        echo '<div class="yperf-card yperf-runtime-card"><div class="yperf-card-head"><div><h2>' . esc_html__('Health Check', 'yablonsky-performance') . '</h2><p>' . esc_html__('Read-only proof of public routes, cache safety and origin behavior.', 'yablonsky-performance') . '</p></div><span id="yperf-runtime-state" class="yperf-state yperf-state-' . esc_attr($runtimeState) . '">' . esc_html($runtimeLabel) . '</span></div>';
        echo '<div class="yperf-health-action-bar" role="group" aria-label="' . esc_attr__('Health Check', 'yablonsky-performance') . '">';
        echo '<form id="yperf-runtime-form" action="' . esc_url(admin_url('admin-post.php')) . '" method="post"><input type="hidden" name="action" value="yperf_run_runtime_acceptance">';
        wp_nonce_field('yperf_run_runtime_acceptance');
        echo '<button type="submit" class="button button-primary button-hero" id="yperf-run-runtime">' . esc_html__('Run Health Check', 'yablonsky-performance') . '</button></form>';
        if (!empty($runtime['tests']) && is_array($runtime['tests'])) {
            echo '<div class="yperf-health-result-actions"><span class="yperf-health-result-caption">' . esc_html__('Saved evidence — no new scan', 'yablonsky-performance') . '</span>';
            self::renderRuntimeExportActions();
            echo '</div>';
        }
        echo '</div>';
        echo '<p class="yperf-health-action-note">' . esc_html__('Health Check verifies the current site state and refreshes evidence. Optimize Now begins with its own fresh Health Check, applies safe YPerf-owned changes, proves the result and keeps the accepted profile.', 'yablonsky-performance') . '</p>';
        echo '<div id="yperf-runtime-progress" class="yperf-progress-wrap" hidden><div class="yperf-progress-top"><strong id="yperf-progress-label">' . esc_html__('Preparing checks…', 'yablonsky-performance') . '</strong><span id="yperf-progress-percent">0%</span></div><div class="yperf-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span id="yperf-progress-bar"></span></div><div class="yperf-live-summary"><span class="yperf-dot yperf-dot-pass"></span><span id="yperf-live-pass">0</span> ' . esc_html__('passed', 'yablonsky-performance') . '<span class="yperf-dot yperf-dot-fail"></span><span id="yperf-live-fail">0</span> ' . esc_html__('failed', 'yablonsky-performance') . '<span class="yperf-dot yperf-dot-skip"></span><span id="yperf-live-skip">0</span> ' . esc_html__('skipped', 'yablonsky-performance') . '</div></div>';
        self::renderRuntimeSnapshot($runtime);
        echo '</div>';
        echo '<div class="yperf-card yperf-stack-card"><div class="yperf-card-head"><div><h2>' . esc_html__('Current stack', 'yablonsky-performance') . '</h2><p>' . esc_html__('Live WordPress-visible acceleration layers and providers.', 'yablonsky-performance') . '</p></div></div>';
        self::renderStackSummary($caps, $guardEnabled, $attention, $effectiveness);
        echo '</div></div>';
        echo '<div class="yperf-card yperf-health-details-card"><div class="yperf-card-head"><div><h2>' . esc_html__('Health Check details', 'yablonsky-performance') . '</h2><p>' . esc_html__('One complete evidence surface with route, cache and origin proof plus JSON/TXT export.', 'yablonsky-performance') . '</p></div></div>';
        self::renderRuntimeTable($runtime);
        echo '</div>';
        echo '</section>';

        echo '<section class="yperf-tab-panel" data-yperf-panel="migration" hidden>';
        self::renderGuidedMigrationCenter($migrationJourney, $migration, $performancePlan);
        echo '<details class="yperf-migration-technical" id="yperf-migration-technical"><summary><strong>' . esc_html__('Technical migration details', 'yablonsky-performance') . '</strong><span>' . esc_html__('Provider gate, blocker matrix, protected user handoff and rollback evidence', 'yablonsky-performance') . '</span></summary>';
        if (!empty($migration['available'])) {
            echo '<div class="yperf-card yperf-migration-card"><div class="yperf-card-head"><div><h2>' . esc_html__('Provider exit gate', 'yablonsky-performance') . '</h2><p>' . esc_html__('Detailed evidence for the legacy provider. Use this only when the guided step asks for technical review.', 'yablonsky-performance') . '</p></div></div>';
            self::renderMigrationPlan($migration);
            echo '</div>';
        }
        self::renderMuPluginInventory($muPluginInventory);
        echo '</details>';
        echo '</section>';

        echo '<section class="yperf-tab-panel" data-yperf-panel="setup" hidden>';
        self::renderPanelSubnav('setup', array(
            'environment' => __('Site & Server', 'yablonsky-performance'),
            'providers' => __('Benchmark', 'yablonsky-performance'),
            'backup' => __('Backup & Restore', 'yablonsky-performance'),
            'page-cache' => __('Page Cache', 'yablonsky-performance'),
            'object-cache' => __('Object Cache', 'yablonsky-performance'),
            'origin' => __('Server', 'yablonsky-performance'),
            'edge' => __('Edge / Cloudflare', 'yablonsky-performance'),
        ), 'environment');

        echo '<div class="yperf-subpanel yperf-setup-section-grid" data-yperf-subpanel="setup:environment">';
        $environmentChecked = $environmentCheck !== array();
        $environmentStateLabel = $environmentChecked ? __('Environment inspected', 'yablonsky-performance') : __('Live baseline', 'yablonsky-performance');
        $environmentButtonLabel = $environmentChecked ? __('Scan again', 'yablonsky-performance') : __('Scan now', 'yablonsky-performance');
        echo '<div class="yperf-card yperf-environment-card"><div class="yperf-card-head"><div><h2>' . esc_html__('Server & WordPress Scan', 'yablonsky-performance') . '</h2><p>' . esc_html__('Automatically inspects everything WordPress can safely see: PHP, cache layers, local services and application integrations.', 'yablonsky-performance') . '</p></div><span class="yperf-state ' . ($environmentChecked ? 'yperf-state-healthy' : 'yperf-state-neutral') . '">' . esc_html($environmentStateLabel) . '</span></div>';
        echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post"><input type="hidden" name="action" value="yperf_run_environment_check">';
        wp_nonce_field('yperf_run_environment_check');
        echo '<div class="yperf-runtime-actions"><button type="submit" class="button button-primary">' . esc_html($environmentButtonLabel) . '</button><span class="yperf-inline-note">' . esc_html__('Read-only. Re-scanning never installs packages, changes cache settings or restarts services.', 'yablonsky-performance') . '</span></div></form>';
        self::renderEnvironmentCheck($environmentCheck, $caps);
        echo '</div>';
        echo '<div class="yperf-card yperf-card-safe"><div class="yperf-card-head"><div><h2>' . esc_html__('Automatic server visibility', 'yablonsky-performance') . '</h2><p>' . esc_html__('Normal YPerf operation is fully automatic. Deep host-only diagnostics are optional expert tools and never block optimization.', 'yablonsky-performance') . '</p></div><span class="yperf-state yperf-state-healthy">' . esc_html__('READY', 'yablonsky-performance') . '</span></div></div>';
        echo '</div>';

        echo '<div class="yperf-subpanel yperf-setup-section-grid" data-yperf-subpanel="setup:providers">';
        echo '<div class="yperf-card yperf-provider-card"><div class="yperf-card-head"><div><h2>' . esc_html__('Provider ownership', 'yablonsky-performance') . '</h2><p>' . esc_html__('Installed is not the same as enabled, executing or healthy.', 'yablonsky-performance') . '</p></div></div>';
        self::renderProviderOwnership($caps);
        echo '</div>';
        echo '<div class="yperf-card yperf-benchmark-plan"><div class="yperf-card-head"><div><h2>' . esc_html__('Performance Benchmark', 'yablonsky-performance') . '</h2><p>' . esc_html__('Measures the current frontend and available local memory engines without changing cache configuration.', 'yablonsky-performance') . '</p></div><span class="yperf-state ' . ($benchmark === array() ? 'yperf-state-neutral' : 'yperf-state-healthy') . '">' . esc_html($benchmark === array() ? __('Ready', 'yablonsky-performance') : __('Measured', 'yablonsky-performance')) . '</span></div>';
        self::renderBenchmarkLab($benchmark, $caps, $environmentCheck);
        echo '</div></div>';

        echo '<div class="yperf-subpanel yperf-setup-section-grid" data-yperf-subpanel="setup:backup">';
        self::renderSettingsBackup();
        echo '</div>';

        echo '<div class="yperf-subpanel yperf-setup-section-grid" data-yperf-subpanel="setup:page-cache">';
        self::renderPageCacheCampaign($pageCacheCampaignReport, $pageCacheCampaignState, $nativePageCacheAdoption);
        self::renderNativePageCacheAdoption($nativePageCacheAdoption);
        self::renderNativePageCacheOperations($nativePageCacheOperations);
        echo '</div>';

        echo '<div class="yperf-subpanel yperf-setup-section-grid" data-yperf-subpanel="setup:object-cache">';
        self::renderObjectCacheAdoption($objectCacheAdoption);
        self::renderObjectCacheOperationContract($objectCacheOperationContract);
        self::renderObjectCacheExecution($objectCacheExecution, $objectCacheOperationContract);
        echo '</div>';

        echo '<div class="yperf-subpanel yperf-setup-section-grid" data-yperf-subpanel="setup:origin">';
        self::renderOriginBudgetReadiness($originBudgetReadiness);
        self::renderOriginBudgetContract($originBudgetContract);
        self::renderOriginBudgetAdoption($originBudgetAdoption);
        self::renderNativeAdoptionPlan($nativeAdoptionPlan);
        echo '</div>';
        echo '<div class="yperf-subpanel yperf-setup-section-grid" data-yperf-subpanel="setup:edge">';
        self::renderCloudflareEdge($cloudflare);
        echo '</div>';
        echo '</section>';

        echo '<section class="yperf-tab-panel" data-yperf-panel="control" hidden>';
        self::renderPanelSubnav('control', array(
            'controls' => __('Controls', 'yablonsky-performance'),
            'media' => __('Video & Media', 'yablonsky-performance'),
            'operations' => __('Tools', 'yablonsky-performance'),
            'capabilities' => __('System Map', 'yablonsky-performance'),
        ), 'controls');
        echo '<div class="yperf-subpanel" data-yperf-subpanel="control:controls">';
        self::renderMarketControlPanel('control', 'control:controls');
        echo '</div>';
        echo '<div class="yperf-subpanel" data-yperf-subpanel="control:media">';
        self::renderMediaOptimizationPanel();
        echo '</div>';
        echo '<div class="yperf-subpanel" data-yperf-subpanel="control:operations"><div class="yperf-operations-grid">';
        self::renderPurgeControl($purgeResult);
        self::renderPrewarmControl($prewarm);
        self::renderCacheabilityInspector($inspection);
        echo '</div></div>';
        echo '<div class="yperf-subpanel" data-yperf-subpanel="control:capabilities"><div class="yperf-card yperf-control-card"><div class="yperf-card-head"><div><h2>' . esc_html__('System Map', 'yablonsky-performance') . '</h2><p>' . esc_html__('Technical view of YPerf-owned and external acceleration layers. Normal cache control lives in the Controls tab.', 'yablonsky-performance') . '</p></div><span class="yperf-state yperf-state-healthy">' . esc_html__('Local control · Free', 'yablonsky-performance') . '</span></div>';
        self::renderFeatureCatalog($features);
        echo '</div></div>';
        echo '</section>';

        echo '<section class="yperf-tab-panel" data-yperf-panel="insights" hidden>';
        self::renderPanelSubnav('insights', array(
            'health' => __('Health', 'yablonsky-performance'),
            'recommendations' => __('Recommendations', 'yablonsky-performance'),
        ), 'health');
        echo '<div class="yperf-subpanel" data-yperf-subpanel="insights:health"><div class="yperf-card yperf-intelligence-card"><div class="yperf-card-head"><div><h2>' . esc_html__('Performance Intelligence', 'yablonsky-performance') . '</h2><p>' . esc_html__('A local under-the-hood report that combines WordPress, runtime, cache, application and acceptance evidence. Nothing is sent remotely.', 'yablonsky-performance') . '</p></div><span class="yperf-state yperf-state-healthy">' . esc_html__('AI-ready · local', 'yablonsky-performance') . '</span></div>';
        self::renderIntelligenceSummary($intelligence);
        self::renderTelemetrySummary($telemetry);
        self::renderRuntimeHistorySummary($runtimeHistory);
        self::renderEvidenceInline($evidenceReconciliation, $nativeRuntimeProof, $portablePageCache, $pageCacheCampaignReport);
        echo '<div class="yperf-export-row"><button type="button" class="button button-primary" data-yperf-intelligence-action="copy">' . esc_html__('Copy report', 'yablonsky-performance') . '</button><button type="button" class="button" data-yperf-open-tab="health">' . esc_html__('Health Check', 'yablonsky-performance') . '</button><span class="yperf-inline-note">' . esc_html__('JSON/TXT export lives in Health Check.', 'yablonsky-performance') . '</span></div>';
        echo '</div></div>';
        echo '<div class="yperf-subpanel" data-yperf-subpanel="insights:recommendations"><div class="yperf-card yperf-recommendations-card"><div class="yperf-card-head"><div><h2>' . esc_html__('Recommendations', 'yablonsky-performance') . '</h2><p>' . esc_html__('Context first, ecosystem second. Suggestions appear only when YPerf sees a concrete opportunity.', 'yablonsky-performance') . '</p></div></div>';
        self::renderRecommendations($recommendations);
        echo '</div></div>';
        echo '</section>';

        echo '<section class="yperf-tab-panel" data-yperf-panel="diagnostics" hidden>';
        self::renderPanelSubnav('diagnostics', array(
            'sql' => __('SQL Hotspots', 'yablonsky-performance'),
            'system' => __('System & Audit', 'yablonsky-performance'),
            'admin' => __('Admin Health', 'yablonsky-performance'),
        ), 'sql');
        echo '<div class="yperf-subpanel" data-yperf-subpanel="diagnostics:sql">';
        echo '<div class="yperf-card yperf-origin-diagnostics-card"><div class="yperf-card-head"><div><h2>' . esc_html__('SQL Hotspots', 'yablonsky-performance') . '</h2><p>' . esc_html__('Stored signed-probe origin costs and SQL fingerprints. This view does not run additional requests.', 'yablonsky-performance') . '</p></div>';
        if (!empty($runtime['tests']) && is_array($runtime['tests'])) {
            echo '<form class="yperf-sql-export-actions" action="' . esc_url(admin_url('admin-post.php')) . '" method="post"><input type="hidden" name="action" value="yperf_download_sql_hotspots">';
            wp_nonce_field('yperf_download_sql_hotspots');
            echo '<button type="submit" class="button button-primary">' . esc_html__('Download SQL JSON', 'yablonsky-performance') . '</button></form>';
        }
        echo '</div></div>';
        self::renderOriginCostSummary($runtime);
        echo '</div>';
        echo '<div class="yperf-subpanel yperf-diagnostics-pair" data-yperf-subpanel="diagnostics:system">';
        echo '<div class="yperf-card"><div class="yperf-card-head"><div><h2>' . esc_html__('Capability detection', 'yablonsky-performance') . '</h2><p>' . esc_html__('Server, cache layers and application integrations detected by WordPress.', 'yablonsky-performance') . '</p></div></div>';
        self::renderCapabilityTable($caps);
        echo '</div>';
        echo '<div class="yperf-card"><div class="yperf-card-head"><div><h2>' . esc_html__('Purge audit', 'yablonsky-performance') . '</h2><p>' . esc_html__('Local bounded history of semantic invalidation requests. No remote logging.', 'yablonsky-performance') . '</p></div></div>';
        self::renderInvalidationLog($invalidationLog);
        echo '</div></div>';
        echo '<div class="yperf-subpanel yperf-diagnostics-pair" data-yperf-subpanel="diagnostics:admin">';
        echo '<div class="yperf-card yperf-upgrade-health-card"><div class="yperf-card-head"><div><h2>' . esc_html__('Plugin install / update health', 'yablonsky-performance') . '</h2><p>' . esc_html__('Read-only lifecycle, fatal and memory-headroom evidence for plugin install/update and selected wp-admin control-plane requests.', 'yablonsky-performance') . '</p></div></div>';
        self::renderUpgradeHealth($upgradeHealth);
        echo '</div>';
        self::renderControlPlaneReadiness($controlPlaneReadiness);
        echo '</div>';
        echo '</section>';

        echo '<section class="yperf-tab-panel" data-yperf-panel="advanced" hidden>';
        self::renderPanelSubnav('advanced', array(
            'controls' => __('Advanced Controls', 'yablonsky-performance'),
            'integration' => __('Integration & Safety', 'yablonsky-performance'),
            'server' => __('Expert Server Diagnostics', 'yablonsky-performance'),
        ), 'controls');
        echo '<div class="yperf-subpanel yperf-advanced-dashboard" data-yperf-subpanel="advanced:controls">';
        echo '<div class="yperf-card"><h2>' . esc_html__('Adoption Mode', 'yablonsky-performance') . '</h2><p>' . esc_html__('Capture the exact WordPress-visible before-state before YPerf adopts any native cache layer.', 'yablonsky-performance') . '</p><form action="' . esc_url(admin_url('admin-post.php')) . '" method="post"><input type="hidden" name="action" value="yperf_capture_adoption">';
        wp_nonce_field('yperf_capture_adoption');
        submit_button(__('Capture adoption snapshot', 'yablonsky-performance'), 'secondary', 'submit', false);
        echo '</form>';
        if ($snapshot !== array()) { echo '<div class="yperf-inline-code"><code>' . esc_html((string) wp_json_encode($snapshot['classification'] ?? array())) . '</code></div>'; }
        echo '</div>';
        echo '<div class="yperf-card"><h2>' . esc_html__('First-hit Query Cost Guard', 'yablonsky-performance') . '</h2><p>' . esc_html__('Experimental. It changes deep anonymous WooCommerce archive query behavior, so it remains off until runtime acceptance is clean.', 'yablonsky-performance') . '</p><form method="post" action="options.php">';
        settings_fields('yperf_settings');
        echo '<input type="hidden" name="yperf_deep_archive_guard" value="0"><label class="yperf-toggle-row"><input type="checkbox" name="yperf_deep_archive_guard" value="1" ' . checked($guardEnabled, true, false) . '> <span>' . esc_html__('Enable experimental deep archive guard', 'yablonsky-performance') . '</span></label>';
        submit_button(__('Save', 'yablonsky-performance'), 'primary', 'submit', false);
        echo '</form></div></div>';

        echo '<div class="yperf-subpanel yperf-advanced-dashboard" data-yperf-subpanel="advanced:integration">';
        echo '<div class="yperf-card"><h2>' . esc_html__('Invalidation contract', 'yablonsky-performance') . '</h2><p><code>yablonsky_performance_invalidate_urls</code> → <code>yablonsky_performance_adapter_purge_urls</code></p><p>' . esc_html__('YMulti, YSEO and future Yablonsky plugins signal semantic invalidation; YPerf remains the purge execution authority.', 'yablonsky-performance') . '</p>';
        if (is_array($lastInvalidation) && $lastInvalidation !== array()) { echo '<div class="yperf-inline-code"><code>' . esc_html((string) wp_json_encode($lastInvalidation)) . '</code></div>'; }
        echo '</div>';
        $externalBridge = get_option('yperf_external_purge_bridge', '0') === '1';
        echo '<div class="yperf-card"><h2>' . esc_html__('External provider purge bridge', 'yablonsky-performance') . '</h2><p>' . esc_html__('Optional transition mode: semantic Yablonsky invalidations can call the currently installed provider purge API. Keep off when the provider already owns the same automatic hooks.', 'yablonsky-performance') . '</p><form method="post" action="options.php">';
        settings_fields('yperf_settings');
        echo '<input type="hidden" name="yperf_external_purge_bridge" value="0"><label class="yperf-toggle-row"><input type="checkbox" name="yperf_external_purge_bridge" value="1" ' . checked($externalBridge, true, false) . '> <span>' . esc_html__('Enable transition purge bridge', 'yablonsky-performance') . '</span></label>';
        submit_button(__('Save', 'yablonsky-performance'), 'secondary', 'submit', false);
        echo '</form></div>';
        echo '<div class="yperf-card yperf-card-safe"><h2>' . esc_html__('Host safety', 'yablonsky-performance') . '</h2><p>' . esc_html__('Yablonsky Performance never changes Nginx, Apache, LiteSpeed, Redis/Valkey or PHP-FPM without an explicit reviewed operation. Runtime, benchmark and prewarm checks are bounded and user-initiated.', 'yablonsky-performance') . '</p></div>';
        echo '</div>';
        echo '<div class="yperf-subpanel yperf-advanced-dashboard" data-yperf-subpanel="advanced:server">';
        echo '<div class="yperf-card yperf-card-safe"><div class="yperf-card-head"><div><h2>' . esc_html__('Expert server diagnostics', 'yablonsky-performance') . '</h2><p>' . esc_html__('Not required for normal YPerf use. WordPress-level scanning stays automatic; the tools below exist only for administrators of self-managed servers who need exact Nginx/PHP-FPM host evidence.', 'yablonsky-performance') . '</p></div><span class="yperf-state yperf-state-neutral">' . esc_html__('OPTIONAL', 'yablonsky-performance') . '</span></div></div>';
        echo '<div class="yperf-expert-diagnostic-grid">';
        echo '<details class="yperf-external-capabilities yperf-expert-diagnostic-card"><summary><span class="yperf-expert-diagnostic-icon" aria-hidden="true">ID</span><span class="yperf-expert-diagnostic-copy"><strong>' . esc_html__('Technical identity & cache ownership', 'yablonsky-performance') . '</strong><span>' . esc_html__('Confirms which cache drop-ins belong to YPerf and prevents unsafe overwrite.', 'yablonsky-performance') . '</span></span><span class="yperf-state yperf-state-neutral">' . esc_html__('READ-ONLY', 'yablonsky-performance') . '</span></summary><div class="yperf-expert-diagnostic-body">';
        self::renderTechnicalIdentity();
        self::renderDropinOwnership($dropinOwnership);
        echo '</div></details>';
        echo '<details class="yperf-external-capabilities yperf-expert-diagnostic-card"><summary><span class="yperf-expert-diagnostic-icon" aria-hidden="true">HOST</span><span class="yperf-expert-diagnostic-copy"><strong>' . esc_html__('Self-managed server connector', 'yablonsky-performance') . '</strong><span>' . esc_html__('Optional exact Nginx, PHP-FPM, memory and Redis evidence for administrators who control the host.', 'yablonsky-performance') . '</span></span><span class="yperf-state yperf-state-neutral">' . esc_html__('OPTIONAL', 'yablonsky-performance') . '</span></summary><div class="yperf-expert-diagnostic-body">';
        self::renderHostIntelligence($hostSnapshot);
        echo '</div></details>';
        echo '<details class="yperf-external-capabilities yperf-expert-diagnostic-card"><summary><span class="yperf-expert-diagnostic-icon" aria-hidden="true">PROOF</span><span class="yperf-expert-diagnostic-copy"><strong>' . esc_html__('Evidence diagnostics', 'yablonsky-performance') . '</strong><span>' . esc_html__('Shows whether stored proof still matches the live WordPress and runtime state.', 'yablonsky-performance') . '</span></span><span class="yperf-state yperf-state-neutral">' . esc_html__('COMPARE', 'yablonsky-performance') . '</span></summary><div class="yperf-expert-diagnostic-body">';
        self::renderEvidenceReconciliation($evidenceReconciliation);
        echo '</div></details>';
        echo '</div>';
        echo '</div></section>';

        if ($runtime !== array()) {
            $json = (string) wp_json_encode($runtime, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            echo '<textarea id="yperf-runtime-report-data" hidden readonly>' . esc_textarea($json) . '</textarea>';
        }
        $intelligenceJson = (string) wp_json_encode($intelligence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        echo '<textarea id="yperf-intelligence-report-data" hidden readonly>' . esc_textarea($intelligenceJson) . '</textarea>';
        self::renderFeatureTemplates($features);
        echo '<div id="yperf-feature-modal" class="yperf-modal" hidden aria-hidden="true"><div class="yperf-modal-backdrop" data-yperf-close-modal></div><div class="yperf-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="yperf-modal-title"><button type="button" class="yperf-modal-close" data-yperf-close-modal aria-label="' . esc_attr__('Close', 'yablonsky-performance') . '">×</button><div id="yperf-modal-content"></div></div></div>';
        echo '<div id="yperf-toast" class="yperf-toast" role="status" aria-live="polite" hidden></div>';
        echo '</div>';
    }

    /** @param array<string,mixed> $runtime */

    /** @param array<string,mixed> $runtime @param array<string,mixed> $autopilot @param array<string,mixed> $effectiveness @param array<string,mixed> $environmentCheck */
    private static function renderNextStepCard(array $runtime, array $autopilot, array $effectiveness, array $environmentCheck): void
    {
        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        $runtimeMissing = $runtime === array();
        $runtimeStale = !$runtimeMissing && (string) ($runtime['version'] ?? '') !== YPERF_VERSION;
        $runtimeFail = (int) ($summary['fail'] ?? 0) > 0;
        $overallState = sanitize_key((string) ($effectiveness['overall_state'] ?? 'observe'));
        $layers = is_array($effectiveness['layers'] ?? null) ? $effectiveness['layers'] : array();
        $pageLayer = isset($layers['page_cache']) && is_array($layers['page_cache']) ? $layers['page_cache'] : array();
        $pageCacheActive = sanitize_key((string) ($pageLayer['state'] ?? 'not_proven')) === 'active';
        $environmentReady = $environmentCheck !== array();
        $autopilotReady = !empty($autopilot['ready']);

        $eyebrow = __('NEXT SAFE STEP', 'yablonsky-performance');
        $title = __('Build a current runtime baseline', 'yablonsky-performance');
        $body = __('Run Runtime Acceptance first. YPerf uses current-version runtime evidence as the gate for optimization decisions.', 'yablonsky-performance');
        $button = __('Open Overview', 'yablonsky-performance');
        $tab = 'overview';
        $section = '';
        $items = array(
            __('Runtime Acceptance is read-only.', 'yablonsky-performance'),
            __('No cache or server setting is changed by this step.', 'yablonsky-performance'),
            __('Fresh evidence unlocks the next safe activation decision.', 'yablonsky-performance'),
        );

        if ($runtimeFail) {
            $title = __('Review the failing runtime signal', 'yablonsky-performance');
            $body = __('Current Runtime Acceptance contains at least one functional FAIL. Fix the failing route first; activation should not proceed on a broken baseline.', 'yablonsky-performance');
            $button = __('Open Diagnostics', 'yablonsky-performance');
            $tab = 'diagnostics';
            $items = array(
                __('Inspect the failing checks and the affected routes.', 'yablonsky-performance'),
                __('Keep YPerf in observe mode until the baseline is clean again.', 'yablonsky-performance'),
                __('Re-run Runtime Acceptance after the site is healthy.', 'yablonsky-performance'),
            );
        } elseif ($runtimeMissing || $runtimeStale) {
            $title = __('Optimize safely in one click', 'yablonsky-performance');
            $body = __('No setup loop is required. YPerf runs a fresh read-only Health Check automatically before any optimization and stops without changes if the baseline is not healthy.', 'yablonsky-performance');
            $button = __('Optimize Now', 'yablonsky-performance');
            $tab = 'optimize';
            $items = array(
                __('Fresh health evidence is captured automatically before any write.', 'yablonsky-performance'),
                __('Only YPerf-owned local WordPress controls may change.', 'yablonsky-performance'),
                __('Functional failures stop the campaign before changes or trigger rollback.', 'yablonsky-performance'),
            );
        } elseif (!$environmentReady) {
            $title = __('Inspect the environment and provider ownership', 'yablonsky-performance');
            $body = __('Runtime is healthy. Next, verify the visible cache/provider stack so YPerf can separate detected providers from proven execution.', 'yablonsky-performance');
            $button = __('Open Settings', 'yablonsky-performance');
            $tab = 'setup';
            $items = array(
                __('Run Environment Check to inspect the current stack.', 'yablonsky-performance'),
                __('Confirm what is installed versus what is actually enabled.', 'yablonsky-performance'),
                __('Keep host changes explicit and reviewed.', 'yablonsky-performance'),
            );
        } elseif ($autopilotReady && $overallState !== 'active') {
            $profile = (string) ($autopilot['label'] ?? __('WordPress Safe Performance', 'yablonsky-performance'));
            $title = __('Activate the recommended local profile', 'yablonsky-performance');
            $body = sprintf(
                /* translators: %s: profile label */
                __('Autopilot is ready and currently recommends %s. It will run a fresh baseline, apply the selected local profile and verify the result with regression guard protection.', 'yablonsky-performance'),
                $profile
            );
            $button = __('Optimize Now', 'yablonsky-performance');
            $tab = 'optimize';
            $items = array(
                __('Autopilot only changes YPerf-owned local controls.', 'yablonsky-performance'),
                __('Functional failure still triggers automatic rollback.', 'yablonsky-performance'),
                __('Repeated material regression is required before performance rollback.', 'yablonsky-performance'),
            );
        } elseif (!$pageCacheActive && in_array($overallState, array('active', 'partial'), true)) {
            $title = __('Stabilize public HTML delivery', 'yablonsky-performance');
            $body = __('The local YPerf profile is proven, but no page-cache executor is active yet. Run the bounded Page Cache stability campaign to prove MISS → HIT, Runtime safety and repeat-HIT consistency before moving deeper into Redis or Origin tuning.', 'yablonsky-performance');
            $button = __('Open Page Cache stability', 'yablonsky-performance');
            $tab = 'setup';
            $section = 'setup:page-cache';
            $items = array(
                __('The campaign changes only the YPerf-owned portable Page Cache boundary.', 'yablonsky-performance'),
                __('Nginx, Redis, PHP-FPM, Cloudflare, theme styles and third-party scripts stay untouched.', 'yablonsky-performance'),
                __('A new activation rolls back automatically if cache proof or Runtime Acceptance fails.', 'yablonsky-performance'),
            );
        } elseif ($overallState === 'active') {
            $title = __('YPerf is already active', 'yablonsky-performance');
            $body = __('Execution proof is already present. The next best step is operational: inspect Insights, review campaign history and use Control Center for selective purge or bounded prewarm.', 'yablonsky-performance');
            $button = __('Open Insights', 'yablonsky-performance');
            $tab = 'insights';
            $items = array(
                __('Use Insights to review route medians and recent stability.', 'yablonsky-performance'),
                __('Use Tools for bounded purge and prewarm operations.', 'yablonsky-performance'),
                __('Keep Runtime Acceptance green after each meaningful change.', 'yablonsky-performance'),
            );
        }

        echo '<div class="yperf-card yperf-next-step-card">';
        echo '<div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html($eyebrow) . '</span><h2>' . esc_html($title) . '</h2><p>' . esc_html($body) . '</p></div>';
        if ($section !== '') {
            echo '<button type="button" class="button button-primary button-hero" data-yperf-open-section="' . esc_attr($section) . '">' . esc_html($button) . '</button></div>';
        } else {
            echo '<button type="button" class="button button-primary button-hero" data-yperf-open-tab="' . esc_attr($tab) . '">' . esc_html($button) . '</button></div>';
        }
        echo '<ul class="yperf-next-step-list">';
        foreach ($items as $item) {
            echo '<li>' . esc_html($item) . '</li>';
        }
        echo '</ul></div>';
    }

    /** @param array<string,mixed> $health */
    private static function renderUpgradeHealth(array $health): void
    {
        $state = sanitize_key((string) ($health['state'] ?? 'no_evidence'));
        $label = (string) ($health['label'] ?? __('No control-plane evidence yet', 'yablonsky-performance'));
        $attentionStates = array('memory_fatal_observed', 'fatal_observed', 'response_failure_after_upgrade', 'resource_constrained');
        $healthyStates = array('upgrader_completed');
        $stateClass = in_array($state, $attentionStates, true)
            ? 'yperf-state-attention'
            : (in_array($state, $healthyStates, true) ? 'yperf-state-healthy' : 'yperf-state-neutral');

        echo '<div class="yperf-upgrade-health-summary"><span class="yperf-state ' . esc_attr($stateClass) . '">' . esc_html($label) . '</span><span>' . esc_html(sprintf(
            /* translators: %d: retained local events */
            __('%d local events retained', 'yablonsky-performance'),
            (int) ($health['events_retained'] ?? 0)
        )) . '</span></div>';

        $resource = is_array($health['resource_headroom'] ?? null) ? $health['resource_headroom'] : array();
        if ($resource !== array()) {
            $resourceState = sanitize_key((string) ($resource['state'] ?? 'attention'));
            $resourceClass = $resourceState === 'healthy' ? 'yperf-state-healthy' : ($resourceState === 'constrained' ? 'yperf-state-attention' : 'yperf-state-neutral');
            $limit = (float) ($resource['memory_limit_mb'] ?? -1);
            $headroom = (float) ($resource['headroom_mb'] ?? -1);
            echo '<div class="yperf-control-plane-headroom">';
            echo '<div class="yperf-control-plane-head"><div><span class="yperf-eyebrow">' . esc_html__('CONTROL PLANE HEADROOM', 'yablonsky-performance') . '</span><strong>' . esc_html((string) ($resource['label'] ?? __('Admin resource state', 'yablonsky-performance'))) . '</strong></div><span class="yperf-state ' . esc_attr($resourceClass) . '">' . esc_html(strtoupper($resourceState)) . '</span></div>';
            echo '<div class="yperf-upgrade-health-grid">';
            echo '<div><span>' . esc_html__('PHP memory ceiling', 'yablonsky-performance') . '</span><strong>' . esc_html($limit > 0 ? number_format_i18n($limit, 0) . ' MB' : __('Unlimited / unknown', 'yablonsky-performance')) . '</strong><small>' . esc_html((string) ($resource['memory_limit_raw'] ?? '')) . '</small></div>';
            echo '<div><span>' . esc_html__('Current request peak', 'yablonsky-performance') . '</span><strong>' . esc_html(number_format_i18n((float) ($resource['peak_memory_mb'] ?? 0), 1) . ' MB') . '</strong><small>' . esc_html($headroom >= 0 ? sprintf(
                /* translators: %s: remaining memory headroom in MB */
                __('~%s MB headroom in this request', 'yablonsky-performance'),
                number_format_i18n($headroom, 1)
            ) : __('Headroom cannot be derived from this PHP limit.', 'yablonsky-performance')) . '</small></div>';
            echo '<div><span>' . esc_html__('Active plugins', 'yablonsky-performance') . '</span><strong>' . esc_html((string) ((int) ($resource['active_plugins'] ?? 0))) . '</strong><small>' . esc_html(!empty($resource['plugin_check_active']) ? __('Plugin Check is active', 'yablonsky-performance') : __('Plugin Check is not active', 'yablonsky-performance')) . '</small></div>';
            echo '<div><span>' . esc_html__('WordPress admin target', 'yablonsky-performance') . '</span><strong>' . esc_html((string) (($resource['wp_max_memory_limit'] ?? '') !== '' ? $resource['wp_max_memory_limit'] : '—')) . '</strong><small>' . esc_html(sprintf(
                /* translators: %s: WordPress front-end memory target */
                __('WP_MEMORY_LIMIT %s', 'yablonsky-performance'),
                (string) (($resource['wp_memory_limit'] ?? '') !== '' ? $resource['wp_memory_limit'] : '—')
            )) . '</small></div>';
            echo '</div>';
            echo '<p class="yperf-inline-note">' . esc_html((string) ($resource['recommendation'] ?? '')) . '</p>';
            echo '</div>';
        }

        $shutdown = is_array($health['last_update_shutdown'] ?? null) ? $health['last_update_shutdown'] : (is_array($health['last_shutdown'] ?? null) ? $health['last_shutdown'] : array());
        $process = is_array($health['last_process_complete'] ?? null) ? $health['last_process_complete'] : array();
        $overwrite = is_array($health['last_overwrite'] ?? null) ? $health['last_overwrite'] : array();
        $memoryFatal = is_array($health['last_memory_fatal'] ?? null) ? $health['last_memory_fatal'] : array();
        if ($shutdown === array() && $process === array() && $overwrite === array() && $memoryFatal === array()) {
            echo '<div class="yperf-empty"><strong>' . esc_html__('No install/update lifecycle captured yet', 'yablonsky-performance') . '</strong><span>' . esc_html__('Install or overwrite a plugin after this YPerf version is active. YPerf now observes update.php plus selected plugin-control screens for fatal/resource evidence.', 'yablonsky-performance') . '</span></div>';
            return;
        }

        echo '<div class="yperf-upgrade-health-grid">';
        if ($process !== array()) {
            echo '<div><span>' . esc_html__('Upgrader hook', 'yablonsky-performance') . '</span><strong>' . esc_html__('Reached', 'yablonsky-performance') . '</strong><small>' . esc_html((string) ($process['action'] ?? '')) . ' · ' . esc_html((string) ($process['type'] ?? '')) . '</small></div>';
        }
        if ($overwrite !== array()) {
            $overwriteLabel = trim((string) ($overwrite['name'] ?? '') . ' ' . (string) ($overwrite['version'] ?? ''));
            echo '<div><span>' . esc_html__('Overwrite hook', 'yablonsky-performance') . '</span><strong>' . esc_html__('Reached', 'yablonsky-performance') . '</strong><small>' . esc_html($overwriteLabel !== '' ? $overwriteLabel : __('Package overwrite observed', 'yablonsky-performance')) . '</small></div>';
        }
        if ($shutdown !== array()) {
            $route = (string) ($shutdown['route'] ?? 'wp-admin');
            echo '<div><span>' . esc_html__('Control-plane response', 'yablonsky-performance') . '</span><strong>HTTP ' . esc_html((string) ((int) ($shutdown['response_code'] ?? 0))) . '</strong><small>' . esc_html($route) . ' · ' . esc_html(sprintf(
                /* translators: %s: peak memory in MB */
                __('peak %s MB', 'yablonsky-performance'),
                number_format_i18n((float) ($shutdown['peak_memory_mb'] ?? 0), 1)
            )) . '</small></div>';
            echo '<div><span>' . esc_html__('Fatal at shutdown', 'yablonsky-performance') . '</span><strong>' . esc_html(!empty($shutdown['fatal']) ? __('YES', 'yablonsky-performance') : __('No', 'yablonsky-performance')) . '</strong><small>' . esc_html((string) ($shutdown['fatal_class'] ?? ($shutdown['fatal_file'] ?? __('No fatal class recorded', 'yablonsky-performance')))) . '</small></div>';
        }
        if ($memoryFatal !== array()) {
            echo '<div><span>' . esc_html__('Memory exhaustion evidence', 'yablonsky-performance') . '</span><strong>' . esc_html__('OBSERVED', 'yablonsky-performance') . '</strong><small>' . esc_html((string) ($memoryFatal['fatal_file'] ?? __('wp-admin request', 'yablonsky-performance'))) . '</small></div>';
        }
        echo '</div>';

        $cache = is_array($shutdown['response_cache'] ?? null) ? $shutdown['response_cache'] : array();
        if ($cache !== array()) {
            echo '<div class="yperf-upgrade-cache-headers"><strong>' . esc_html__('Safe response-cache headers', 'yablonsky-performance') . '</strong>';
            foreach ($cache as $name => $value) {
                echo '<code>' . esc_html((string) $name) . ': ' . esc_html((string) $value) . '</code>';
            }
            echo '</div>';
        }
        $correlated = is_array($report['correlated_update'] ?? null) ? $report['correlated_update'] : array();
        if ($correlated !== array()) {
            echo '<div class="yperf-def-list">';
            self::renderDefinitionRow(__('Correlated update state', 'yablonsky-performance'), strtoupper(str_replace('_', ' ', (string) ($correlated['state'] ?? 'unknown'))));
            self::renderDefinitionRow(__('Upgrader complete', 'yablonsky-performance'), !empty($correlated['process_complete']) ? __('Yes', 'yablonsky-performance') : __('No', 'yablonsky-performance'));
            self::renderDefinitionRow(__('Overwrite complete', 'yablonsky-performance'), !empty($correlated['overwrite_complete']) ? __('Yes', 'yablonsky-performance') : __('No', 'yablonsky-performance'));
            self::renderDefinitionRow(__('HTTP response', 'yablonsky-performance'), (string) ((int) ($correlated['response_code'] ?? 0)));
            self::renderDefinitionRow(__('Peak / limit memory', 'yablonsky-performance'), number_format_i18n((float) ($correlated['peak_memory_mb'] ?? 0), 1) . ' / ' . number_format_i18n((float) ($correlated['memory_limit_mb'] ?? 0), 1) . ' MB');
            echo '</div>';
        }
        echo '<p class="yperf-inline-note">' . esc_html__('No request query string, nonce, raw fatal message, response body or credential is stored. YPerf does not raise PHP memory or modify the WordPress upgrader automatically.', 'yablonsky-performance') . '</p>';
    }

    /** @param array<string,mixed> $readiness */
    private static function renderControlPlaneReadiness(array $readiness): void
    {
        $state = sanitize_key((string) ($readiness['state'] ?? 'evidence_required'));
        $progress = max(0, min(100, (int) ($readiness['progress_percent'] ?? 0)));
        $gates = is_array($readiness['gates'] ?? null) ? $readiness['gates'] : array();
        $host = is_array($readiness['host'] ?? null) ? $readiness['host'] : array();
        $fpm = is_array($readiness['fpm_reserve'] ?? null) ? $readiness['fpm_reserve'] : array();
        $incidents = is_array($readiness['recent_incidents'] ?? null) ? $readiness['recent_incidents'] : array();
        $blocked = in_array($state, array('runtime_blocked', 'memory_blocked'), true);
        $ready = in_array($state, array('local_ready_host_optional', 'reserve_ready'), true);
        $stateClass = $ready ? 'yperf-state-healthy' : ($blocked ? 'yperf-state-attention' : 'yperf-state-neutral');

        echo '<div class="yperf-card yperf-control-plane-readiness-card"><div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('CONTROL PLANE RELIABILITY · R1', 'yablonsky-performance') . '</span><h2>' . esc_html__('Keep wp-admin operable under performance pressure', 'yablonsky-performance') . '</h2><p>' . esc_html((string) ($readiness['reason'] ?? '')) . '</p></div><span class="yperf-state ' . esc_attr($stateClass) . '">' . esc_html((string) ($readiness['label'] ?? __('REVIEW', 'yablonsky-performance'))) . '</span></div>';
        echo '<div class="yperf-native-op-progress"><div><strong>' . esc_html__('Evidence coverage', 'yablonsky-performance') . '</strong><span>' . esc_html($progress . '%') . '</span></div><div class="yperf-progress-track"><span style="width:' . esc_attr((string) $progress) . '%"></span></div></div>';
        echo '<div class="yperf-def-list">';
        self::renderDefinitionRow(__('Host memory available', 'yablonsky-performance'), sprintf('%.1f%%', (float) ($host['memory_available_pct'] ?? 0)));
        self::renderDefinitionRow(__('Load / CPU thread', 'yablonsky-performance'), number_format_i18n((float) ($host['load_1_per_cpu'] ?? 0), 2));
        self::renderDefinitionRow(__('FPM worker reserve', 'yablonsky-performance'), sprintf('%d / %d', (int) ($fpm['reserve_workers'] ?? 0), (int) ($fpm['max_children'] ?? 0)));
        self::renderDefinitionRow(__('FPM pool PHP memory', 'yablonsky-performance'), (string) (($fpm['pool_memory_limit'] ?? '') !== '' ? $fpm['pool_memory_limit'] : __('Not reported by current host proof', 'yablonsky-performance')));
        self::renderDefinitionRow(__('Recent fatal events', 'yablonsky-performance'), (string) ((int) ($incidents['fatal_count'] ?? 0)));
        self::renderDefinitionRow(__('Recent admin 5xx', 'yablonsky-performance'), (string) ((int) ($incidents['response_5xx_count'] ?? 0)));
        echo '</div><div class="yperf-native-op-gates">';
        foreach (array(
            'runtime_current_clean' => __('Runtime current & clean', 'yablonsky-performance'),
            'admin_memory_not_constrained' => __('Admin memory not constrained', 'yablonsky-performance'),
            'no_recent_php_fatal' => __('No recent PHP fatal', 'yablonsky-performance'),
            'no_recent_memory_fatal' => __('No recent memory fatal', 'yablonsky-performance'),
            'host_proof_current' => __('Host proof current', 'yablonsky-performance'),
            'host_memory_headroom' => __('Host memory reserve', 'yablonsky-performance'),
            'host_load_below_saturation' => __('Host load below saturation', 'yablonsky-performance'),
            'site_fpm_pool_mapped' => __('Site FPM pool mapped', 'yablonsky-performance'),
            'admin_worker_reserve_visible' => __('Admin worker reserve visible', 'yablonsky-performance'),
        ) as $key => $label) {
            $passed = !empty($gates[$key]);
            echo '<div class="' . ($passed ? 'is-pass' : 'is-wait') . '"><span>' . esc_html($passed ? '✓' : '○') . '</span><strong>' . esc_html($label) . '</strong></div>';
        }
        echo '</div><p class="yperf-benchmark-safety"><strong>' . esc_html__('No hidden tuning.', 'yablonsky-performance') . '</strong> ' . esc_html__('This gate turns intermittent admin incidents into explicit release/tuning evidence. It never raises PHP memory, changes FPM or alters the WordPress upgrader automatically.', 'yablonsky-performance') . '</p></div>';
    }

    /** @param array<string,mixed> $readiness */
    private static function renderReleaseReadiness(array $readiness, array $lifecycle = array()): void
    {
        $progress = max(0, min(100, (int) ($readiness['core_progress_percent'] ?? 0)));
        $gates = is_array($readiness['core_gates'] ?? null) ? $readiness['core_gates'] : array();
        $lanes = is_array($readiness['execution_lanes'] ?? null) ? $readiness['execution_lanes'] : array();
        $state = sanitize_key((string) ($readiness['state'] ?? 'blocked'));
        $stateClass = $state === 'manual_plugin_check_required' ? 'yperf-state-healthy' : 'yperf-state-attention';

        echo '<div class="yperf-card yperf-release-readiness-card"><div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('ENGINEERING RELEASE GATE', 'yablonsky-performance') . '</span><h2>' . esc_html__('Release readiness', 'yablonsky-performance') . '</h2><p>' . esc_html((string) ($readiness['reason'] ?? '')) . '</p></div><span class="yperf-state ' . esc_attr($stateClass) . '">' . esc_html((string) ($readiness['label'] ?? __('BLOCKED', 'yablonsky-performance'))) . '</span></div>';
        echo '<div class="yperf-native-op-progress"><div><strong>' . esc_html__('Local core gates', 'yablonsky-performance') . '</strong><span>' . esc_html($progress . '%') . '</span></div><div class="yperf-progress-track"><span style="width:' . esc_attr((string) $progress) . '%"></span></div></div>';
        echo '<div class="yperf-native-op-gates">';
        foreach (array(
            'canonical_runtime_identity' => __('Canonical runtime identity', 'yablonsky-performance'),
            'runtime_current_clean' => __('Runtime Acceptance current & clean', 'yablonsky-performance'),
            'control_plane_not_blocked' => __('Control plane not blocked', 'yablonsky-performance'),
            'origin_cost_evidence' => __('Origin Cost evidence captured', 'yablonsky-performance'),
        ) as $key => $label) {
            $passed = !empty($gates[$key]);
            echo '<div class="' . ($passed ? 'is-pass' : 'is-wait') . '"><span>' . esc_html($passed ? '✓' : '○') . '</span><strong>' . esc_html($label) . '</strong></div>';
        }
        echo '<div class="is-wait"><span>○</span><strong>' . esc_html__('Official Plugin Check · manual external gate', 'yablonsky-performance') . '</strong></div></div>';
        echo '<div class="yperf-def-list">';
        foreach (array(
            'page_cache_authority' => __('Page cache authority', 'yablonsky-performance'),
            'native_page_cache' => __('Native Nginx cache', 'yablonsky-performance'),
            'persistent_object_cache' => __('Persistent object cache', 'yablonsky-performance'),
            'origin_budget' => __('Origin budget', 'yablonsky-performance'),
        ) as $key => $label) {
            $lane = is_array($lanes[$key] ?? null) ? $lanes[$key] : array();
            self::renderDefinitionRow($label, strtoupper(str_replace('_', ' ', (string) ($lane['state'] ?? 'not evaluated'))));
        }
        echo '</div>';
        $closeout = is_array($readiness['closeout'] ?? null) ? $readiness['closeout'] : array();
        if ($closeout !== array()) {
            $closeoutProgress = max(0, min(100, (int) ($closeout['progress_percent'] ?? 0)));
            $closeoutState = sanitize_key((string) ($closeout['state'] ?? 'optional_expansion_available'));
            $closeoutClass = $closeoutState === 'coverage_complete' ? 'yperf-state-healthy' : 'yperf-state-neutral';
            echo '<div class="yperf-release-closeout"><div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('SITE EXECUTION COVERAGE', 'yablonsky-performance') . '</span><h3>' . esc_html__('Optional execution lanes on this site', 'yablonsky-performance') . '</h3><p>' . esc_html__('This site-level view tracks optional Redis/native-host execution lanes and provider-retirement evidence. It is informative and does not make optional host features a market-release requirement.', 'yablonsky-performance') . '</p></div><span class="yperf-state ' . esc_attr($closeoutClass) . '">' . esc_html((string) ($closeout['label'] ?? '')) . '</span></div>';
            echo '<div class="yperf-native-op-progress"><div><strong>' . esc_html__('Execution coverage', 'yablonsky-performance') . '</strong><span>' . esc_html($closeoutProgress . '%') . '</span></div><div class="yperf-progress-track"><span style="width:' . esc_attr((string) $closeoutProgress) . '%"></span></div></div>';
            $closeoutGates = is_array($closeout['gates'] ?? null) ? $closeout['gates'] : array();
            echo '<div class="yperf-native-op-gates">';
            foreach (array(
                'runtime_current_clean' => __('Runtime Acceptance', 'yablonsky-performance'),
                'control_plane_not_blocked' => __('Control-plane reliability', 'yablonsky-performance'),
                'origin_cost_evidence' => __('Origin Cost evidence', 'yablonsky-performance'),
                'page_cache_authority_proven' => __('Page-cache authority proven', 'yablonsky-performance'),
                'persistent_object_cache_active' => __('Persistent object cache active', 'yablonsky-performance'),
                'origin_budget_active' => __('Origin Budget active', 'yablonsky-performance'),
                'legacy_provider_exit_resolved' => __('Legacy provider exit resolved', 'yablonsky-performance'),
            ) as $key => $gateLabel) {
                $passed = !empty($closeoutGates[$key]);
                echo '<div class="' . ($passed ? 'is-pass' : 'is-wait') . '"><span>' . esc_html($passed ? '✓' : '○') . '</span><strong>' . esc_html($gateLabel) . '</strong></div>';
            }
            echo '</div>';
            $openItems = is_array($closeout['open_items'] ?? null) ? $closeout['open_items'] : array();
            if ($openItems !== array()) {
                echo '<div class="yperf-closeout-open"><strong>' . esc_html__('Optional lanes not active', 'yablonsky-performance') . '</strong><span>' . esc_html(implode(' · ', array_map('sanitize_text_field', $openItems))) . '</span></div>';
            }
            $fingerprint = sanitize_text_field((string) ($closeout['evidence_fingerprint'] ?? ''));
            if ($fingerprint !== '') {
                echo '<p class="yperf-inline-note">' . esc_html__('Evidence fingerprint', 'yablonsky-performance') . ': <code>' . esc_html(substr($fingerprint, 0, 16)) . '…</code></p>';
            }
            echo '</div>';
        }
        $deactivationObserved = !empty($lifecycle['deactivation_observed']);
        $cleanupSafe = !empty($lifecycle['safe_cleanup_complete']);
        $cycleComplete = !empty($lifecycle['current_cycle_complete']);
        $lifeClass = $cycleComplete ? 'yperf-state-healthy' : ($deactivationObserved && !$cleanupSafe ? 'yperf-state-attention' : 'yperf-state-neutral');
        $lifeLabel = $cycleComplete ? __('CURRENT VERSION CYCLE PROVEN', 'yablonsky-performance') : (!$deactivationObserved ? __('LIFECYCLE SMOKE PENDING', 'yablonsky-performance') : ($cleanupSafe ? __('SAFE CLEANUP OBSERVED · REACTIVATION PENDING', 'yablonsky-performance') : __('LIFECYCLE REVIEW REQUIRED', 'yablonsky-performance')));
        echo '<div class="yperf-release-lifecycle"><div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('PLUGIN LIFECYCLE', 'yablonsky-performance') . '</span><h3>' . esc_html__('Deactivate / reactivate safety', 'yablonsky-performance') . '</h3><p>' . esc_html__('Lifecycle proof is version-bound. Deactivation retires only exact YPerf-owned runtime drop-ins; settings and evidence are preserved; reactivation never silently re-enables cache.', 'yablonsky-performance') . '</p></div><span class="yperf-state ' . esc_attr($lifeClass) . '">' . esc_html($lifeLabel) . '</span></div>';
        echo '<div class="yperf-def-list">';
        self::renderDefinitionRow(__('Settings on uninstall', 'yablonsky-performance'), __('Preserved by policy', 'yablonsky-performance'));
        self::renderDefinitionRow(__('Reactivation cache writes', 'yablonsky-performance'), __('Explicit approval required', 'yablonsky-performance'));
        if ($deactivationObserved) {
            self::renderDefinitionRow(__('Last deactivation cleanup', 'yablonsky-performance'), $cleanupSafe ? __('PASS', 'yablonsky-performance') : __('REVIEW', 'yablonsky-performance'));
            $lastDeactivationAt = max(0, (int) ($lifecycle['last_deactivation_at'] ?? 0));
            $lastActivationAt = max(0, (int) ($lifecycle['last_activation_at'] ?? 0));
            if ($lastDeactivationAt > 0) {
                self::renderDefinitionRow(__('Last deactivation', 'yablonsky-performance'), wp_date('Y-m-d H:i:s', $lastDeactivationAt) . ' · v' . sanitize_text_field((string) ($lifecycle['last_deactivation_version'] ?? '')));
            }
            if ($lastActivationAt > 0) {
                self::renderDefinitionRow(__('Last activation', 'yablonsky-performance'), wp_date('Y-m-d H:i:s', $lastActivationAt) . ' · v' . sanitize_text_field((string) ($lifecycle['last_activation_version'] ?? '')));
            }
        }
        echo '</div>';
        if (!$cycleComplete) {
            echo '<div class="yperf-intelligence-note"><strong>' . esc_html__('How to complete the lifecycle rehearsal', 'yablonsky-performance') . '</strong><span>' . esc_html__('When convenient, deactivate YPerf once from Plugins, reactivate the same version, then return here. YPerf records exact-owned drop-in cleanup and the later activation as separate evidence. Re-enable or re-prove Page Cache only by explicit approval.', 'yablonsky-performance') . '</span></div>';
        } else {
            echo '<div class="yperf-good-line"><strong>' . esc_html__('Current-version deactivate → safe cleanup → reactivate cycle is proven.', 'yablonsky-performance') . '</strong></div>';
        }
        echo '</div>';
        echo '<p class="yperf-inline-note">' . esc_html__('YPerf deliberately cannot mark its own Plugin Check green. The official checker stays an independent human acceptance signal.', 'yablonsky-performance') . '</p></div>';
    }

    private static function renderOriginCostSummary(array $runtime, bool $detail = true): void
    {
        $summary = is_array($runtime['origin_cost_summary'] ?? null) ? $runtime['origin_cost_summary'] : array();
        $measured = (int) ($summary['measured_routes'] ?? 0);
        echo '<div class="yperf-card yperf-origin-cost-card">';
        echo '<div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('ORIGIN COST', 'yablonsky-performance') . '</span><h2>' . esc_html__('What uncached WordPress actually costs', 'yablonsky-performance') . '</h2><p>' . esc_html__('Measured only on cryptographically signed Runtime Acceptance probes. Normal visitors do not enable query recording and raw SQL is never exported.', 'yablonsky-performance') . '</p></div>';
        echo '<span class="yperf-state ' . ($measured > 0 ? 'yperf-state-healthy' : 'yperf-state-neutral') . '">' . esc_html($measured > 0 ? __('Measured', 'yablonsky-performance') : __('Run Runtime Acceptance', 'yablonsky-performance')) . '</span></div>';
        if ($measured < 1) {
            echo '<p class="yperf-empty">' . esc_html__('The next Runtime Acceptance will capture query count, database time, slow-query counts and query families for origin-executed frontend probes.', 'yablonsky-performance') . '</p></div>';
            return;
        }
        echo '<div class="yperf-origin-kpis">';
        self::renderMiniMetric(__('Measured routes', 'yablonsky-performance'), (string) $measured);
        self::renderMiniMetric(__('Heaviest DB route', 'yablonsky-performance'), number_format_i18n((float) ($summary['max_db_time_ms'] ?? 0), 1) . ' ms');
        self::renderMiniMetric(__('Slowest query', 'yablonsky-performance'), number_format_i18n((float) ($summary['max_single_query_ms'] ?? 0), 1) . ' ms');
        self::renderMiniMetric(__('Max queries / route', 'yablonsky-performance'), (string) ((int) ($summary['max_query_count'] ?? 0)));
        self::renderMiniMetric(__('Queries ≥100 ms', 'yablonsky-performance'), (string) ((int) ($summary['slow_queries_100ms_total'] ?? 0)));
        self::renderMiniMetric(__('FOUND_ROWS signals', 'yablonsky-performance'), (string) ((int) ($summary['sql_calc_found_rows_total'] ?? 0)));
        echo '</div>';
        $route = (string) ($summary['heaviest_route'] ?? '');
        if ($route !== '') {
            echo '<p class="yperf-inline-note">' . esc_html(sprintf(
                /* translators: %s: measured route label */
                __('Heaviest measured route: %s. Use Diagnostics for per-route origin metrics.', 'yablonsky-performance'),
                $route
            )) . '</p>';
        }
        if (!$detail) {
            echo '<div class="yperf-origin-summary-drilldown"><span>' . esc_html__('Five slow-query fingerprints ranked by cumulative time across signed probes. Raw SQL is never exported.', 'yablonsky-performance') . '</span>';
            echo '<button type="button" class="button" data-yperf-open-section="diagnostics:sql">' . esc_html__('SQL Hotspots', 'yablonsky-performance') . '</button></div>';
            echo '</div>';
            return;
        }
        // Bounded leaderboard across signed origin probes. It never contains
        // raw SQL, SQL parameters, stack traces or host-local paths.
        $leaderboard = array();
        foreach ((array) ($runtime['tests'] ?? array()) as $test) {
            if (!is_array($test)) {
                continue;
            }
            $metrics = is_array($test['origin_metrics'] ?? null) ? $test['origin_metrics'] : array();
            if ((string) ($metrics['state'] ?? '') !== 'measured') {
                continue;
            }
            $routeName = sanitize_text_field((string) ($test['name'] ?? ''));
            foreach (QueryCostTelemetry::sanitizeHotspots($metrics['hotspots'] ?? array()) as $hotspot) {
                $leaderboard[] = array('route' => $routeName, 'hotspot' => $hotspot);
            }
        }
        usort($leaderboard, static function (array $left, array $right): int {
            return (float) $right['hotspot']['total_ms'] <=> (float) $left['hotspot']['total_ms'];
        });
        if ($leaderboard !== array()) {
            echo '<div class="yperf-origin-hotspots"><h3>' . esc_html__('Top SQL Hotspots by route', 'yablonsky-performance') . '</h3>';
            echo '<p class="description">' . esc_html__('Five slow-query fingerprints ranked by cumulative time across signed probes. Raw SQL is never exported.', 'yablonsky-performance') . '</p>';
            echo '<table class="widefat striped yperf-hotspots-table"><thead><tr>';
            foreach (array(__('Route', 'yablonsky-performance'), __('Fingerprint', 'yablonsky-performance'), __('Query family', 'yablonsky-performance'), __('Total ms', 'yablonsky-performance'), __('Peak ms', 'yablonsky-performance')) as $heading) {
                echo '<th scope="col">' . esc_html($heading) . '</th>';
            }
            echo '</tr></thead><tbody>';
            foreach (array_slice($leaderboard, 0, 5) as $row) {
                $entry = $row['hotspot'];
                echo '<tr><td>' . esc_html((string) $row['route']) . '</td>';
                echo '<td><code>' . esc_html(substr((string) $entry['signature_sha256'], 0, 12)) . '</code></td>';
                echo '<td>' . esc_html((string) $entry['family']) . '</td>';
                echo '<td>' . esc_html(number_format_i18n((float) $entry['total_ms'], 1)) . '</td>';
                echo '<td>' . esc_html(number_format_i18n((float) $entry['max_ms'], 1)) . '</td></tr>';
            }
            echo '</tbody></table></div>';
        }
        echo '</div>';
    }

    private static function renderMiniMetric(string $label, string $value): void
    {
        echo '<div class="yperf-origin-kpi"><span>' . esc_html($label) . '</span><strong>' . esc_html($value) . '</strong></div>';
    }

    /** @param array<string,mixed> $plan @param array<string,mixed> $effectiveness */
    private static function renderPerformanceState(array $plan, array $effectiveness): void
    {
        $overallState = sanitize_key((string) ($effectiveness['overall_state'] ?? 'observe'));
        $overallLabel = (string) ($effectiveness['overall_label'] ?? __('Observe mode', 'yablonsky-performance'));
        $yperfEnabled = (int) ($effectiveness['yperf_controls_enabled'] ?? 0);
        $yperfProven = (int) ($effectiveness['yperf_controls_proven'] ?? 0);
        $siteLayers = (int) ($effectiveness['site_acceleration_layers_active'] ?? 0);
        $layers = is_array($effectiveness['layers'] ?? null) ? $effectiveness['layers'] : array();
        $stateClass = $overallState === 'active' ? 'healthy' : ($overallState === 'partial' ? 'attention' : 'neutral');

        echo '<div class="yperf-performance-state yperf-card yperf-performance-truth">';
        echo '<div class="yperf-performance-state-copy"><span class="yperf-eyebrow">' . esc_html__('WHAT IS WORKING NOW', 'yablonsky-performance') . '</span><h2>' . esc_html($overallLabel) . '</h2>';
        echo '<p>' . esc_html(sprintf(
            /* translators: 1: enabled YPerf controls, 2: proven YPerf controls, 3: active site acceleration layers */
            __('YPerf controls enabled: %1$d · proven by Runtime Acceptance: %2$d · active acceleration layers on this site: %3$d.', 'yablonsky-performance'),
            $yperfEnabled,
            $yperfProven,
            $siteLayers
        )) . '</p>';
        echo '<div class="yperf-execution-strip">';
        foreach (array('yperf_frontend', 'first_hit_guard', 'page_cache', 'object_cache', 'opcache', 'compression', 'edge_html', 'origin_budget') as $key) {
            if (!isset($layers[$key]) || !is_array($layers[$key])) {
                continue;
            }
            self::renderExecutionLayerCompact($layers[$key]);
        }
        echo '</div></div>';
        echo '<div class="yperf-performance-state-action"><span class="yperf-state yperf-state-' . esc_attr($stateClass) . '">' . esc_html($overallLabel) . '</span><button type="button" class="button button-primary button-hero" data-yperf-open-tab="optimize">' . esc_html__('Open Performance Activation', 'yablonsky-performance') . '</button><small>' . esc_html__('Verified means YPerf observed the feature working on this site. Verifying means enabled but waiting for a matching request.', 'yablonsky-performance') . '</small></div>';
        echo '</div>';
    }

    /** @param array<string,mixed> $layer */
    private static function renderExecutionLayerCompact(array $layer): void
    {
        $state = sanitize_key((string) ($layer['state'] ?? 'not_proven'));
        $stateLabel = self::executionStateLabel($state);
        $class = self::executionStateClass($state);
        echo '<span class="yperf-execution-pill ' . esc_attr($class) . '"><b>' . esc_html((string) ($layer['name'] ?? '')) . '</b><span>' . esc_html($stateLabel) . '</span></span>';
    }

    private static function executionStateLabel(string $state): string
    {
        $labels = array(
            'active' => __('ON · VERIFIED', 'yablonsky-performance'),
            'armed' => __('ON · VERIFYING', 'yablonsky-performance'),
            'off' => __('OFF', 'yablonsky-performance'),
            'needs_review' => __('NEEDS REPAIR', 'yablonsky-performance'),
            'not_proven' => __('NOT ENABLED', 'yablonsky-performance'),
        );
        return (string) ($labels[$state] ?? __('NOT ENABLED', 'yablonsky-performance'));
    }

    private static function executionStateClass(string $state): string
    {
        if ($state === 'active') {
            return 'is-active';
        }
        if ($state === 'armed' || $state === 'needs_review') {
            return 'is-armed';
        }
        return 'is-off';
    }

    /** @param array<string,mixed> $status @param array<string,mixed> $recommendation @param array<string,mixed> $answer */
    private static function renderAIAdvisorCard(array $status, array $recommendation, array $answer): void
    {
        $ready = !empty($status['ready']);
        $keyConfigured = !empty($status['key_configured']);
        $contractReady = !empty($status['contract_ready']);
        $accountVerified = !empty($status['account_verified']);
        $workspaceReady = !empty($status['workspace_ready']);
        $account = is_array($status['account'] ?? null) ? $status['account'] : array();
        if ($ready) {
            $stateLabel = __('READY', 'yablonsky-performance');
            $stateClass = 'yperf-state-healthy';
        } elseif (!$keyConfigured) {
            $stateLabel = __('CONNECT AI', 'yablonsky-performance');
            $stateClass = 'yperf-state-attention';
        } elseif (!$contractReady) {
            $stateLabel = __('CONTRACT REVIEW', 'yablonsky-performance');
            $stateClass = 'yperf-state-attention';
        } elseif (!$accountVerified) {
            $stateLabel = __('VERIFY CONNECTION', 'yablonsky-performance');
            $stateClass = 'yperf-state-neutral';
        } else {
            $stateLabel = __('WORKSPACE REQUIRED', 'yablonsky-performance');
            $stateClass = 'yperf-state-attention';
        }
        $credential = YablonskyAICredentials::status();
        echo '<div class="yperf-card yperf-ai-advisor-card"><div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('YABLONSKY AI ADVISOR', 'yablonsky-performance') . '</span><div class="yperf-heading-help"><h2>' . esc_html__('AI-assisted configuration, human-approved', 'yablonsky-performance') . '</h2>';
        self::renderHelpTip(__('AI-assisted configuration, human-approved', 'yablonsky-performance'), __('Yablonsky AI can analyze privacy-minimized YPerf evidence and recommend one allowed local profile. AI never owns execution: deterministic safety gates and your explicit Apply remain authoritative.', 'yablonsky-performance'));
        echo '</div></div><span class="yperf-state ' . esc_attr($stateClass) . '">' . esc_html($stateLabel) . '</span></div>';

        echo '<div class="yperf-ai-disclosure is-compact"><strong>' . esc_html__('Yablonsky AI only', 'yablonsky-performance');
        self::renderHelpTip(__('Yablonsky AI only', 'yablonsky-performance'), __('The account API key authenticates optional Yablonsky AI remote compute. It is not a plugin license and never unlocks local YPerf functionality. External requests happen only after an explicit administrator action.', 'yablonsky-performance'));
        echo '</strong></div>';
        echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="yperf-ai-connection-form"><input type="hidden" name="action" value="yperf_save_ai_settings">';
        wp_nonce_field('yperf_save_ai_settings');
        echo '<label class="yperf-ai-key-field"><span>' . esc_html__('Yablonsky AI API Key', 'yablonsky-performance') . '</span><input type="password" name="yperf_ai_api_key" autocomplete="new-password" placeholder="' . esc_attr($keyConfigured ? '•••••• · ' . (string) ($credential['hint'] ?? '') : __('Paste account API key', 'yablonsky-performance')) . '"></label>';
        echo '<button type="submit" class="button yperf-ai-save-button">' . esc_html__('Save AI connection', 'yablonsky-performance') . '</button>';
        if ($keyConfigured) {
            echo '<label class="yperf-ai-disconnect"><input type="checkbox" name="yperf_ai_clear" value="1"> <span>' . esc_html__('Disconnect on Save', 'yablonsky-performance') . '</span></label>';
        }
        echo '</form>';

        if (!$keyConfigured) {
            echo '<div class="yperf-callout yperf-callout-attention"><strong>' . esc_html__('Connect Yablonsky AI to use AI Advisor.', 'yablonsky-performance') . '</strong><span>' . esc_html__('Manual configuration and Local Autopilot remain fully available without an AI key.', 'yablonsky-performance') . '</span></div></div>';
            return;
        }
        if (!$contractReady) {
            echo '<div class="yperf-callout yperf-callout-attention"><strong>' . esc_html__('The reviewed Yablonsky AI contract needs attention.', 'yablonsky-performance') . '</strong><span>' . esc_html__('YPerf will not send a credential until the HTTPS endpoint, authentication mode, workspace header and Writer transport all pass the local contract gate.', 'yablonsky-performance') . '</span></div></div>';
            return;
        }
        if (!$accountVerified) {
            echo '<div class="yperf-callout yperf-callout-info"><strong>' . esc_html__('Reviewed User API contract loaded.', 'yablonsky-performance') . '</strong><span>' . esc_html__('Verify the saved key with one explicit read-only account request. Opening this dashboard never contacts Yablonsky AI automatically.', 'yablonsky-performance') . '</span></div>';
            echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="yperf-autopilot-action yperf-ai-verify-row"><input type="hidden" name="action" value="yperf_verify_ai_connection">';
            wp_nonce_field('yperf_verify_ai_connection');
            echo '<button type="submit" class="button button-primary">' . esc_html__('Verify AI connection', 'yablonsky-performance') . '</button><span>' . esc_html__('Read-only: verifies the account and current workspace. No AI generation is performed.', 'yablonsky-performance') . '</span></form></div>';
            return;
        }
        if (!$workspaceReady) {
            echo '<div class="yperf-callout yperf-callout-attention"><strong>' . esc_html__('Account verified, but no usable current workspace was returned.', 'yablonsky-performance') . '</strong><span>' . esc_html__('Select a current workspace in Yablonsky AI, then verify the connection again.', 'yablonsky-performance') . '</span></div>';
            echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="yperf-autopilot-action yperf-ai-verify-row"><input type="hidden" name="action" value="yperf_verify_ai_connection">';
            wp_nonce_field('yperf_verify_ai_connection');
            echo '<button type="submit" class="button">' . esc_html__('Verify again', 'yablonsky-performance') . '</button></form></div>';
            return;
        }

        $availableCredits = is_numeric($account['available_credits'] ?? null)
            ? (float) $account['available_credits']
            : (is_numeric($account['total_credits'] ?? null) ? (float) $account['total_credits'] : null);
        $usageCredits = is_numeric($account['usage_credits'] ?? null)
            ? (float) $account['usage_credits']
            : (is_numeric($account['credits'] ?? null) ? (float) $account['credits'] : null);
        $planCreditsRemaining = is_numeric($account['plan_credits_remaining'] ?? null) ? (float) $account['plan_credits_remaining'] : null;
        $planCreditsAllocated = is_numeric($account['plan_credits_allocated'] ?? null) ? (float) $account['plan_credits_allocated'] : null;
        $usageResetAt = is_numeric($account['usage_reset_at'] ?? null) ? (int) $account['usage_reset_at'] : 0;
        $workspaceLabel = (string) (($account['workspace_name'] ?? '') !== '' ? $account['workspace_name'] : __('Current workspace', 'yablonsky-performance'));
        $availableLabel = $availableCredits !== null ? number_format_i18n($availableCredits, 2) : '—';
        $usageCreditsLabel = $usageCredits !== null ? number_format_i18n($usageCredits, 2) : '—';
        $planRemainingLabel = $planCreditsRemaining !== null ? number_format_i18n($planCreditsRemaining, 2) : '—';
        $planAllocatedLabel = $planCreditsAllocated !== null ? number_format_i18n($planCreditsAllocated, 0) : '—';
        $resetLabel = $usageResetAt > 0
            ? wp_date((string) get_option('date_format') . ' ' . (string) get_option('time_format'), $usageResetAt)
            : '—';

        echo '<div class="yperf-callout yperf-callout-success"><strong>' . esc_html__('Yablonsky AI connection verified.', 'yablonsky-performance') . '</strong><span>' . esc_html(sprintf(
            /* translators: 1: workspace name, 2: total spendable credits */
            __('Workspace: %1$s · available now: %2$s credits', 'yablonsky-performance'),
            $workspaceLabel,
            $availableLabel
        )) . '</span></div>';
        echo '<div class="yperf-ai-credit-grid" aria-label="' . esc_attr__('Yablonsky AI credit balances', 'yablonsky-performance') . '">';
        echo '<div><span class="yperf-help-label">' . esc_html__('Available now', 'yablonsky-performance');
        self::renderHelpTip(__('Available now', 'yablonsky-performance'), __('Plan balance + usage credits', 'yablonsky-performance'));
        echo '</span><strong>' . esc_html($availableLabel) . '</strong></div>';
        $planHelp = sprintf(
            /* translators: %s: monthly plan credit allocation */
            __('of %s included this cycle', 'yablonsky-performance'),
            $planAllocatedLabel
        );
        echo '<div><span class="yperf-help-label">' . esc_html__('Plan balance', 'yablonsky-performance');
        self::renderHelpTip(__('Plan balance', 'yablonsky-performance'), $planHelp);
        echo '</span><strong>' . esc_html($planRemainingLabel) . '</strong></div>';
        echo '<div><span class="yperf-help-label">' . esc_html__('Usage credits', 'yablonsky-performance');
        self::renderHelpTip(__('Usage credits', 'yablonsky-performance'), __('Non-expiring reserve used after the plan allowance', 'yablonsky-performance'));
        echo '</span><strong>' . esc_html($usageCreditsLabel) . '</strong></div>';
        echo '<div><span class="yperf-help-label">' . esc_html__('Usage reset', 'yablonsky-performance');
        self::renderHelpTip(__('Usage reset', 'yablonsky-performance'), __('Monthly plan allowance renews; usage credits remain', 'yablonsky-performance'));
        echo '</span><strong>' . esc_html($resetLabel) . '</strong></div>';
        echo '</div>';
        $textModels = array_map('strval', (array) ($account['text_models'] ?? array()));
        $modelPreference = YablonskyAIAccount::preferences();
        $selectedModel = (string) ($modelPreference['model'] ?? 'auto');
        if ($selectedModel !== 'auto' && !in_array($selectedModel, $textModels, true)) {
            $selectedModel = 'auto';
        }
        $resolvedModel = YablonskyAIAccount::resolvedModel($account);
        $writerModel = (string) ($account['writer_model'] ?? '');
        $workspaceModelLabel = $writerModel !== '' ? YablonskyAIAccount::humanizeModel($writerModel) : __('current default', 'yablonsky-performance');
        /* translators: %s: current workspace Writer model label */
        $autoLabel = sprintf(__('Auto · %s (Workspace Writer)', 'yablonsky-performance'), $workspaceModelLabel);
        $modelHelp = sprintf(
            '%1$s %2$s',
            __('Auto follows the workspace Writer model. A manual choice is sent explicitly with each YPerf AI request and never changes the workspace default.', 'yablonsky-performance'),
            __('The model catalog comes from the verified account plan; YPerf does not ship a frozen provider list.', 'yablonsky-performance')
        );
        echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="yperf-ai-model-form"><input type="hidden" name="action" value="yperf_save_ai_settings">';
        wp_nonce_field('yperf_save_ai_settings');
        echo '<div class="yperf-ai-model-head"><div><span class="yperf-help-label"><strong>' . esc_html__('Text model', 'yablonsky-performance') . '</strong>';
        self::renderHelpTip(__('Text model', 'yablonsky-performance'), $modelHelp);
        echo '</span><p>' . esc_html(sprintf(
            /* translators: 1: number of text models, 2: active model label */
            __('%1$d available · active now: %2$s', 'yablonsky-performance'),
            count($textModels),
            (string) ($resolvedModel['label'] ?? '—')
        )) . '</p></div><span class="yperf-state yperf-state-healthy">' . esc_html__('ACTIVE', 'yablonsky-performance') . '</span></div>';
        echo '<div class="yperf-model-control-row"><div class="yperf-model-picker" data-yperf-model-picker><input type="hidden" name="yperf_ai_model" value="' . esc_attr($selectedModel) . '" data-yperf-model-value>';
        echo '<button type="button" class="yperf-model-picker-button" data-yperf-model-toggle aria-haspopup="listbox" aria-expanded="false"><span data-yperf-model-current>' . esc_html($selectedModel === 'auto' ? $autoLabel : YablonskyAIAccount::humanizeModel($selectedModel)) . '</span><span aria-hidden="true">⌄</span></button>';
        echo '<div class="yperf-model-popover" data-yperf-model-popover hidden><label><span class="screen-reader-text">' . esc_html__('Search text models', 'yablonsky-performance') . '</span><input type="search" placeholder="' . esc_attr__('Search by model name…', 'yablonsky-performance') . '" autocomplete="off" data-yperf-model-search></label><div class="yperf-model-list" role="listbox" data-yperf-model-list>';
        echo '<button type="button" role="option" class="yperf-model-option" data-model-id="auto" data-model-label="' . esc_attr($autoLabel) . '" aria-selected="' . esc_attr($selectedModel === 'auto' ? 'true' : 'false') . '"><strong>' . esc_html($autoLabel) . '</strong><small>' . esc_html__('Follows your workspace default', 'yablonsky-performance') . '</small></button>';
        foreach ($textModels as $modelId) {
            $modelLabel = YablonskyAIAccount::humanizeModel($modelId);
            echo '<button type="button" role="option" class="yperf-model-option" data-model-id="' . esc_attr($modelId) . '" data-model-label="' . esc_attr($modelLabel) . '" aria-selected="' . esc_attr($selectedModel === $modelId ? 'true' : 'false') . '"><strong>' . esc_html($modelLabel) . '</strong><small>' . esc_html($modelId) . '</small></button>';
        }
        echo '<div class="yperf-model-empty" data-yperf-model-empty hidden>' . esc_html__('No matching text models.', 'yablonsky-performance') . '</div></div></div></div>';
        echo '<button type="submit" class="button button-primary yperf-model-apply">' . esc_html__('Use this model', 'yablonsky-performance') . '</button></div></form>';
        echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="yperf-autopilot-action yperf-ai-refresh-row"><input type="hidden" name="action" value="yperf_verify_ai_connection">';
        wp_nonce_field('yperf_verify_ai_connection');
        echo '<button type="submit" class="button">' . esc_html__('Refresh account balance', 'yablonsky-performance') . '</button>';
        self::renderHelpTip(__('Refresh account balance', 'yablonsky-performance'), __('Read-only account refresh. It does not run an AI model or consume generation credits.', 'yablonsky-performance'));
        echo '</form>';
        echo '<div class="yperf-ai-disclosure is-compact"><strong>' . esc_html__('Explicit external call only', 'yablonsky-performance');
        self::renderHelpTip(__('Explicit external call only', 'yablonsky-performance'), __('Clicking an AI action sends only minimized technical evidence to Yablonsky AI. URLs, hostnames, IP addresses, filesystem paths, raw SQL, logs, content, cookies, nonces, passwords, tokens and API keys are excluded.', 'yablonsky-performance'));
        echo '</strong></div>';
        echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="yperf-autopilot-action yperf-help-action"><input type="hidden" name="action" value="yperf_run_ai_advisor">';
        wp_nonce_field('yperf_run_ai_advisor');
        echo '<button type="submit" class="button button-primary button-hero">' . esc_html__('Build AI Recommendation', 'yablonsky-performance') . '</button>';
        self::renderHelpTip(__('Build AI Recommendation', 'yablonsky-performance'), __('Analysis only. No setting changes are made by this button.', 'yablonsky-performance'));
        echo '</form>';

        if ($recommendation !== array()) {
            $target = sanitize_key((string) ($recommendation['target_profile'] ?? 'observe'));
            $definition = PerformancePlan::profileDefinition($target);
            $confidence = strtoupper(sanitize_key((string) ($recommendation['confidence'] ?? 'low')));
            echo '<div class="yperf-ai-plan"><div class="yperf-card-head"><div><strong>' . esc_html__('Current AI plan', 'yablonsky-performance') . '</strong><p>' . esc_html((string) ($recommendation['summary'] ?? '')) . '</p></div><span class="yperf-state yperf-state-neutral">' . esc_html($confidence) . '</span></div>';
            echo '<div class="yperf-last-apply"><strong>' . esc_html__('Validated profile', 'yablonsky-performance') . '</strong><span>' . esc_html((string) ($definition['label'] ?? $target)) . '</span></div>';
            if (!empty($recommendation['guard_adjusted'])) {
                echo '<div class="yperf-callout yperf-callout-attention"><strong>' . esc_html__('Safety gate adjusted the AI plan', 'yablonsky-performance') . '</strong><span>' . esc_html__('The model requested a broader profile than current deterministic evidence allows. YPerf lowered it before any Apply became possible.', 'yablonsky-performance') . '</span></div>';
            }
            foreach (array('reasons' => __('Why this profile', 'yablonsky-performance'), 'warnings' => __('Warnings', 'yablonsky-performance')) as $key => $label) {
                $items = (array) ($recommendation[$key] ?? array());
                if ($items !== array()) {
                    echo '<div class="yperf-plan-preview"><strong>' . esc_html($label) . '</strong><ul>';
                    foreach ($items as $item) { echo '<li>' . esc_html((string) $item) . '</li>'; }
                    echo '</ul></div>';
                }
            }
            echo '<div class="yperf-plan-preview"><strong>' . esc_html__('Exact allowed changes', 'yablonsky-performance') . '</strong><ul>';
            foreach ((array) ($definition['changes'] ?? array()) as $change) { echo '<li>' . esc_html((string) $change) . '</li>'; }
            echo '</ul></div>';
            if (!empty($recommendation['ready_to_apply'])) {
                echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="yperf-apply-row"><input type="hidden" name="action" value="yperf_apply_ai_advisor">';
                wp_nonce_field('yperf_apply_ai_advisor');
                echo '<button type="submit" class="button button-primary button-hero">' . esc_html__('Apply AI Plan & Prove Execution', 'yablonsky-performance') . '</button><span>' . esc_html__('Before any write, YPerf runs a fresh Runtime Acceptance and re-validates the AI plan against the deterministic safety ceiling. Failure or confirmed regression rolls back automatically.', 'yablonsky-performance') . '</span></form>';
            }
            echo '</div>';
        }

        echo '<div class="yperf-ai-question"><strong>' . esc_html__('Ask about this site', 'yablonsky-performance') . '</strong><p>' . esc_html__('Ask a performance question in plain language. Do not paste secrets, credentials, personal data or private content.', 'yablonsky-performance') . '</p><form action="' . esc_url(admin_url('admin-post.php')) . '" method="post"><input type="hidden" name="action" value="yperf_ask_ai_advisor">';
        wp_nonce_field('yperf_ask_ai_advisor');
        echo '<textarea name="yperf_ai_question" rows="3" maxlength="1200" placeholder="' . esc_attr__('Example: Which YPerf profile is safest for this WooCommerce stack, and what evidence is still missing?', 'yablonsky-performance') . '"></textarea><div class="yperf-runtime-actions"><button type="submit" class="button">' . esc_html__('Ask AI Advisor', 'yablonsky-performance') . '</button><span class="yperf-inline-note">' . esc_html__('Read-only advice. Answers never execute settings.', 'yablonsky-performance') . '</span></div></form>';
        if ($answer !== array() && (string) ($answer['answer'] ?? '') !== '') {
            echo '<div class="yperf-ai-answer"><strong>' . esc_html__('Latest AI answer', 'yablonsky-performance') . '</strong><p>' . nl2br(esc_html((string) $answer['answer'])) . '</p></div>';
        }
        echo '</div></div>';
    }

    private static function renderMarketControlPanel(string $returnTab = 'optimize', string $returnSection = 'optimize:recommended'): void
    {
        $page = PageCacheExecutor::status();
        $pageEnabled = !empty($page['enabled']);
        $pageState = sanitize_key((string) ($page['state'] ?? 'off'));
        $pageProof = is_array(($page['record']['last_proof'] ?? null)) ? $page['record']['last_proof'] : array();
        $pageProofCurrent = !empty($pageProof['ok']) && (string) ($pageProof['version'] ?? '') === YPERF_VERSION;
        $pageLabel = !$pageEnabled ? __('Off', 'yablonsky-performance') : ($pageState === 'active' && $pageProofCurrent ? __('On · verified', 'yablonsky-performance') : __('Needs repair', 'yablonsky-performance'));
        $pageClass = !$pageEnabled ? 'off' : ($pageState === 'active' && $pageProofCurrent ? 'on' : 'repair');
        $pageOp = !$pageEnabled ? 'enable' : (($pageState === 'active' && $pageProofCurrent) ? 'disable' : 'repair');
        $pageButton = !$pageEnabled ? __('Turn on', 'yablonsky-performance') : (($pageState === 'active' && $pageProofCurrent) ? __('Turn off', 'yablonsky-performance') : __('Repair & verify', 'yablonsky-performance'));

        $browserEnabled = BrowserCacheController::enabled();
        $frontendEnabled = FrontendOptimizer::enabled();
        $frontendDeliveryEnabled = FrontendOptimizer::deliveryEngineEnabled();
        $firstHitEnabled = get_option('yperf_deep_archive_guard', '0') === '1';
        $object = ObjectCacheExecutor::status();
        $objectActive = !empty($object['active']);
        $objectState = sanitize_key((string) ($object['state'] ?? 'inactive'));
        $objectReadiness = ObjectCacheAdoption::analyze(
            RedisEvidence::resolve(HostSnapshot::last()),
            DropinOwnership::inspect(),
            RuntimeAcceptance::lastReport(),
            Benchmark::lastReport()
        );
        $objectReadinessState = sanitize_key((string) ($objectReadiness['state'] ?? 'redis_evidence_required'));
        $objectPressureBlocked = in_array($objectReadinessState, array('redis_pressure_blocked', 'redis_unbounded', 'eviction_policy_review', 'redis_unreachable', 'isolation_required'), true);
        $objectConflict = in_array($objectReadinessState, array('dropin_owned_elsewhere', 'already_active'), true);
        $objectReady = in_array($objectReadinessState, array('reservation_ready', 'reserved_read_only'), true);

        echo '<div class="yperf-card yperf-market-controls-card">';
        echo '<div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('PERFORMANCE CONTROLS', 'yablonsky-performance') . '</span><h2>' . esc_html__('Everything important, in one place', 'yablonsky-performance') . '</h2><p>' . esc_html__('Manage local performance controls. Each feature uses its own safety checks; host-owned cache freshness requires separate proof.', 'yablonsky-performance') . '</p></div><span class="yperf-state yperf-state-healthy">' . esc_html__('Safe controls', 'yablonsky-performance') . '</span></div>';

        echo '<div class="yperf-market-control-list">';
        self::renderMarketControlRow('page-cache', __('Page Cache', 'yablonsky-performance'), $pageLabel, $pageClass, __('Caches public HTML for dramatically faster repeat requests.', 'yablonsky-performance'), $pageOp, $pageButton, true, $returnTab, $returnSection);
        self::renderMarketControlRow('browser-cache', __('Browser Cache', 'yablonsky-performance'), $browserEnabled ? __('On', 'yablonsky-performance') : __('Off', 'yablonsky-performance'), $browserEnabled ? 'on' : 'off', __('Lets browsers reuse YPerf-owned public HTML safely.', 'yablonsky-performance'), $browserEnabled ? 'disable' : 'enable', $browserEnabled ? __('Turn off', 'yablonsky-performance') : __('Turn on', 'yablonsky-performance'), $browserEnabled || ($pageState === 'active' && $pageProofCurrent), $returnTab, $returnSection);
        $frontendLabel = !$frontendEnabled ? __('Off', 'yablonsky-performance') : ($frontendDeliveryEnabled ? __('On · delivery engine', 'yablonsky-performance') : __('Basic · upgrade available', 'yablonsky-performance'));
        $frontendClass = $frontendDeliveryEnabled ? 'on' : ($frontendEnabled ? 'repair' : 'off');
        $frontendOperation = $frontendDeliveryEnabled ? 'disable' : 'enable';
        $frontendButton = $frontendDeliveryEnabled ? __('Turn off', 'yablonsky-performance') : __('Enable & prove full engine', 'yablonsky-performance');
        self::renderMarketControlRow('frontend-optimizer', __('Frontend Optimization', 'yablonsky-performance'), $frontendLabel, $frontendClass, __('Defers non-critical heavyweight WordPress/WooCommerce execution, removes emoji overhead and keeps transactional WooCommerce routes excluded.', 'yablonsky-performance'), $frontendOperation, $frontendButton, true, $returnTab, $returnSection);
        $videoLiteOn = FrontendOptimizer::publicVideoLiteEnabled();
        echo '<div class="yperf-market-control-row"><div class="yperf-market-control-copy"><div class="yperf-market-control-title"><strong>' . esc_html__('Video & Media Optimization', 'yablonsky-performance') . '</strong><span class="yperf-control-status is-' . ($videoLiteOn ? 'on' : 'off') . '">' . esc_html($videoLiteOn ? __('YouTube optimized', 'yablonsky-performance') : __('Ready to configure', 'yablonsky-performance')) . '</span></div><p>' . esc_html__('One media-delivery surface for supported embeds, independent of WordPress themes and video plugins.', 'yablonsky-performance') . '</p></div><div class="yperf-market-control-action"><button type="button" class="button" data-yperf-open-section="control:media">' . esc_html__('Manage media', 'yablonsky-performance') . '</button></div></div>';
        self::renderMarketControlRow('first-hit-guard', __('WooCommerce First-hit Protection', 'yablonsky-performance'), $firstHitEnabled ? __('On', 'yablonsky-performance') : __('Off', 'yablonsky-performance'), $firstHitEnabled ? 'on' : 'off', __('Protects eligible deep WooCommerce archive requests before cache is warm.', 'yablonsky-performance'), $firstHitEnabled ? 'disable' : 'enable', $firstHitEnabled ? __('Turn off', 'yablonsky-performance') : __('Turn on', 'yablonsky-performance'), true, $returnTab, $returnSection);

        $objectMarketLabel = $objectActive
            ? __('On', 'yablonsky-performance')
            : ($objectConflict
                ? __('Existing cache detected', 'yablonsky-performance')
                : ($objectPressureBlocked ? __('Not recommended now', 'yablonsky-performance') : ($objectReady ? __('Available', 'yablonsky-performance') : __('Automatic decision', 'yablonsky-performance'))));
        $objectMarketClass = $objectActive ? 'is-on' : (($objectPressureBlocked || $objectConflict) ? 'is-repair' : 'is-auto');
        $objectMarketDescription = $objectActive
            ? __('YPerf Redis Object Cache is active and isolated for this site.', 'yablonsky-performance')
            : ($objectConflict
                ? __('An existing object-cache.php is already present. YPerf leaves it untouched and will not install a competing Redis cache. Review ownership only if you want to replace the existing object cache.', 'yablonsky-performance')
                : ($objectPressureBlocked
                    ? (string) ($objectReadiness['reason'] ?? __('Current Redis evidence does not meet the safe activation boundary.', 'yablonsky-performance'))
                    : ($objectReady
                        ? __('Redis isolation and headroom look safe. Open details to review the bounded activation contract.', 'yablonsky-performance')
                        : __('YPerf enables this only when Redis isolation and memory headroom are safe. It is never forced just to increase a feature count.', 'yablonsky-performance'))));
        echo '<div class="yperf-market-control-row"><div class="yperf-market-control-copy"><div class="yperf-market-control-title"><strong>' . esc_html__('Persistent Object Cache', 'yablonsky-performance') . '</strong><span class="yperf-control-status ' . esc_attr($objectMarketClass) . '">' . esc_html($objectMarketLabel) . '</span></div><p>' . esc_html($objectMarketDescription) . '</p></div><div class="yperf-market-control-action">';
        if ($objectActive || in_array($objectState, array('upgrade_reactivation_required', 'dropin_present_not_active'), true)) {
            self::renderInlineControlForm('object-cache', 'disable', __('Turn off', 'yablonsky-performance'), $returnTab, $returnSection);
        } else {
            $objectButton = $objectConflict ? __('Review existing cache', 'yablonsky-performance') : ($objectReady ? __('Set up safely', 'yablonsky-performance') : __('View reason', 'yablonsky-performance'));
            echo '<button type="button" class="button" data-yperf-open-section="setup:object-cache">' . esc_html($objectButton) . '</button>';
        }
        echo '</div></div>';
        echo '</div>';
        echo '<div class="yperf-market-controls-foot"><span>' . esc_html__('Server-native Nginx / PHP-FPM tuning stays automatic-read-only unless an expert explicitly opens Advanced.', 'yablonsky-performance') . '</span><button type="button" class="button" data-yperf-open-tab="setup">' . esc_html__('Open all settings', 'yablonsky-performance') . '</button></div>';
        echo '</div>';
    }

    /**
     * Unified media optimization surface. Provider entries describe capabilities,
     * not installed WordPress plugins. Only the field-proven YouTube iframe
     * handler is enabled; unknown/social/dynamic embeds are never rewritten.
     */
    private static function renderMediaOptimizationPanel(): void
    {
        $enabled = FrontendOptimizer::publicVideoLiteEnabled();
        $frontendEnabled = FrontendOptimizer::enabled();
        $providers = array(
            array(
                'youtube',
                __('YouTube embeds', 'yablonsky-performance'),
                $enabled ? __('Active', 'yablonsky-performance') : __('Available', 'yablonsky-performance'),
                'supported',
                'dashicons-video-alt3',
                __('Standard HTTPS YouTube and YouTube-nocookie iframe embeds, including WordPress block and shortcode output.', 'yablonsky-performance'),
            ),
            array(
                'vimeo',
                __('Vimeo embeds', 'yablonsky-performance'),
                __('Canary only', 'yablonsky-performance'),
                'pending',
                'dashicons-format-video',
                __('Standard Vimeo HTTPS iframe playback has a read-only test mode (?yperf_vimeo_canary=1). Public activation awaits compatibility proof.', 'yablonsky-performance'),
            ),
            array(
                'facebook',
                __('Facebook videos', 'yablonsky-performance'),
                __('Not yet supported', 'yablonsky-performance'),
                'pending',
                'dashicons-facebook-alt',
                __('Social embeds can include interactive content and authentication boundaries.', 'yablonsky-performance'),
            ),
            array(
                'instagram',
                __('Instagram media', 'yablonsky-performance'),
                __('Not yet supported', 'yablonsky-performance'),
                'pending',
                'dashicons-instagram',
                __('Posts, reels and scripts require provider-specific validation before optimization.', 'yablonsky-performance'),
            ),
            array(
                'other',
                __('Other media embeds', 'yablonsky-performance'),
                __('Not yet supported', 'yablonsky-performance'),
                'pending',
                'dashicons-screenoptions',
                __('Unknown iframes, custom players and dynamic embeds remain unchanged.', 'yablonsky-performance'),
            ),
        );

        echo '<div class="yperf-card yperf-media-card yperf-media-shell">';
        echo '<div class="yperf-media-hero">';
        echo '<div class="yperf-media-hero-main">';
        echo '<div class="yperf-media-hero-icon" aria-hidden="true"><span class="dashicons dashicons-format-video"></span></div>';
        echo '<div><span class="yperf-eyebrow">' . esc_html__('MEDIA DELIVERY', 'yablonsky-performance') . '</span>';
        echo '<h2>' . esc_html__('Video & Media Optimization', 'yablonsky-performance') . '</h2>';
        echo '<p>' . esc_html__('Optimize supported media embeds regardless of the WordPress plugin, theme or editor that created them.', 'yablonsky-performance') . '</p></div>';
        echo '</div>';
        echo '<div class="yperf-media-hero-status"><span class="yperf-media-origin-badge ' . ($enabled ? 'is-enabled' : 'is-disabled') . '"><span class="yperf-media-status-dot" aria-hidden="true"></span>' . esc_html($enabled ? __('Enabled at origin', 'yablonsky-performance') : __('Off', 'yablonsky-performance')) . '</span>';
        echo '<span class="yperf-media-hero-caption">' . esc_html__('Supported media delivery', 'yablonsky-performance') . '</span></div>';
        echo '</div>';

        echo '<div class="yperf-media-control-panel">';
        echo '<div class="yperf-media-control-copy"><div class="yperf-media-control-title"><span class="dashicons dashicons-controls-play" aria-hidden="true"></span><h3>' . esc_html__('Supported media delivery', 'yablonsky-performance') . '</h3></div>';
        echo '<p>' . esc_html__('Load recognized video players after visitor interaction. Original media links and reserved layout dimensions are preserved; unsupported embeds are not modified.', 'yablonsky-performance') . '</p>';
        echo '<div class="yperf-media-capabilities"><span><span class="dashicons dashicons-yes" aria-hidden="true"></span>' . esc_html__('Original links preserved', 'yablonsky-performance') . '</span>';
        echo '<span><span class="dashicons dashicons-yes" aria-hidden="true"></span>' . esc_html__('Layout dimensions preserved', 'yablonsky-performance') . '</span>';
        echo '<span><span class="dashicons dashicons-yes" aria-hidden="true"></span>' . esc_html__('Independent of WordPress plugins', 'yablonsky-performance') . '</span></div></div>';
        echo '<div class="yperf-media-control-action">';
        if ($frontendEnabled || $enabled) {
            self::renderInlineControlForm('video-lite', $enabled ? 'disable' : 'enable', $enabled ? __('Turn off', 'yablonsky-performance') : __('Enable for public pages', 'yablonsky-performance'), 'control', 'control:media');
        } else {
            echo '<button type="button" class="button" disabled>' . esc_html__('Requires Frontend Optimization', 'yablonsky-performance') . '</button>';
        }
        echo '</div></div>';

        echo '<div class="yperf-media-section-heading"><div><h3>' . esc_html__('Provider compatibility', 'yablonsky-performance') . '</h3>';
        echo '<p>' . esc_html__('Compatibility is based on the resulting embed format, not the plugin name. Unsupported formats remain unchanged.', 'yablonsky-performance') . '</p></div>';
        echo '<span class="yperf-media-coverage-count">1 / 5 ' . esc_html__('supported', 'yablonsky-performance') . '</span></div>';
        echo '<div class="yperf-media-provider-grid" role="list">';
        foreach ($providers as $provider) {
            $classes = 'yperf-media-provider is-' . $provider[3] . ' provider-' . $provider[0];
            echo '<article role="listitem" class="' . esc_attr($classes) . '">';
            echo '<div class="yperf-media-provider-top"><span class="yperf-media-provider-icon" aria-hidden="true"><span class="dashicons ' . esc_attr($provider[4]) . '"></span></span>';
            echo '<span class="yperf-media-provider-status is-' . esc_attr($provider[3]) . '">' . esc_html($provider[2]) . '</span></div>';
            echo '<h4>' . esc_html($provider[1]) . '</h4>';
            echo '<p>' . esc_html($provider[5]) . '</p>';
            echo '</article>';
        }
        echo '</div>';

        echo '<div class="yperf-media-freshness" role="note"><span class="dashicons dashicons-info-outline" aria-hidden="true"></span><p>';
        echo '<strong>' . esc_html__('External page-cache freshness', 'yablonsky-performance') . '</strong>';
        echo '<span>' . esc_html__('The setting changes WordPress origin delivery only. Existing external Nginx/Cloudflare HTML cache entries require independent freshness verification; YPerf does not silently purge host caches.', 'yablonsky-performance') . '</span>';
        echo '</p></div>';
        echo '</div>';
    }

    private static function renderMarketControlRow(string $feature, string $name, string $status, string $statusClass, string $description, string $operation, string $button, bool $enabled, string $returnTab, string $returnSection): void
    {
        echo '<div class="yperf-market-control-row"><div class="yperf-market-control-copy"><div class="yperf-market-control-title"><strong>' . esc_html($name) . '</strong><span class="yperf-control-status is-' . esc_attr($statusClass) . '">' . esc_html($status) . '</span></div><p>' . esc_html($description) . '</p></div><div class="yperf-market-control-action">';
        if ($enabled) {
            self::renderInlineControlForm($feature, $operation, $button, $returnTab, $returnSection);
        } else {
            echo '<button type="button" class="button" disabled>' . esc_html__('Requires Page Cache', 'yablonsky-performance') . '</button>';
        }
        echo '</div></div>';
    }

    private static function renderInlineControlForm(string $feature, string $operation, string $label, string $returnTab = 'optimize', string $returnSection = 'optimize:recommended'): void
    {
        echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="yperf-inline-control-form">';
        wp_nonce_field('yperf_control_feature');
        echo '<input type="hidden" name="action" value="yperf_control_feature"><input type="hidden" name="yperf_feature" value="' . esc_attr($feature) . '"><input type="hidden" name="yperf_operation" value="' . esc_attr($operation) . '"><input type="hidden" name="yperf_return_tab" value="' . esc_attr($returnTab) . '"><input type="hidden" name="yperf_return_section" value="' . esc_attr($returnSection) . '">';
        echo '<button type="submit" class="button ' . ($operation === 'disable' ? '' : 'button-primary') . '">' . esc_html($label) . '</button></form>';
    }

    private static function renderSettingsBackup(): void
    {
        echo '<div class="yperf-card yperf-settings-backup-card"><div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('PORTABLE CONFIGURATION', 'yablonsky-performance') . '</span><h2>' . esc_html__('Backup & restore YPerf settings', 'yablonsky-performance') . '</h2><p>' . esc_html__('Save the working YPerf configuration and reuse it on another installation. API keys, runtime evidence, server snapshots and host-specific cache ownership are never exported.', 'yablonsky-performance') . '</p></div><span class="yperf-state yperf-state-healthy">' . esc_html__('Portable', 'yablonsky-performance') . '</span></div>';
        echo '<div class="yperf-backup-grid"><div class="yperf-backup-pane"><h3>' . esc_html__('Export settings', 'yablonsky-performance') . '</h3><p>' . esc_html__('Downloads one JSON file containing the safe, portable YPerf configuration.', 'yablonsky-performance') . '</p><form action="' . esc_url(admin_url('admin-post.php')) . '" method="post"><input type="hidden" name="action" value="yperf_export_settings">';
        wp_nonce_field('yperf_export_settings');
        echo '<button type="submit" class="button button-primary">' . esc_html__('Download settings backup', 'yablonsky-performance') . '</button></form></div>';
        echo '<div class="yperf-backup-pane"><h3>' . esc_html__('Import settings', 'yablonsky-performance') . '</h3><p>' . esc_html__('Choose a YPerf settings JSON. Your browser reads the file locally; YPerf applies it transactionally, runs a Health Check and restores the previous configuration if verification fails.', 'yablonsky-performance') . '</p><form action="' . esc_url(admin_url('admin-post.php')) . '" method="post"><input type="hidden" name="action" value="yperf_import_settings">';
        wp_nonce_field('yperf_import_settings');
        echo '<input type="file" id="yperf-settings-import-file" accept="application/json,.json"><span id="yperf-settings-import-file-state" class="yperf-inline-note">' . esc_html__('No file selected', 'yablonsky-performance') . '</span><details class="yperf-import-paste"><summary>' . esc_html__('Or paste JSON', 'yablonsky-performance') . '</summary><textarea id="yperf-settings-import-json" name="yperf_settings_json" rows="5" placeholder="{ ... }"></textarea></details><button type="submit" class="button">' . esc_html__('Import & verify settings', 'yablonsky-performance') . '</button></form></div></div>';
        echo '<div class="yperf-intelligence-note"><strong>' . esc_html__('What is intentionally not portable', 'yablonsky-performance') . '</strong><span>' . esc_html__('Redis reservations, object-cache runtime, Nginx/PHP-FPM authority, migration backups, host evidence and credentials remain bound to the original site.', 'yablonsky-performance') . '</span></div></div>';
    }

    /** @param array<string,mixed> $autopilot @param array<string,mixed> $runtimeHistory @param array<string,mixed> $campaignReport */
    private static function renderAutopilotCard(array $autopilot, array $runtimeHistory, array $campaignReport): void
    {
        $ready = !empty($autopilot['ready']);
        $label = (string) ($autopilot['label'] ?? __('Autopilot', 'yablonsky-performance'));
        $target = sanitize_key((string) ($autopilot['target_profile'] ?? 'observe'));
        $lastCampaign = is_array($campaignReport['last'] ?? null) ? $campaignReport['last'] : array();
        echo '<div class="yperf-card yperf-autopilot-card"><div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('PERFORMANCE AUTOPILOT', 'yablonsky-performance') . '</span><div class="yperf-heading-help"><h2>' . esc_html__('Optimize this site in one click', 'yablonsky-performance') . '</h2>';
        self::renderHelpTip(__('Optimize this site in one click', 'yablonsky-performance'), __('Autopilot runs a fresh Health Check, repairs YPerf-owned cache state when safe, enables the recommended Page Cache / Browser Cache / WordPress controls, proves execution, and rolls back on failure.', 'yablonsky-performance'));
        echo '</div></div><span class="yperf-state ' . ($ready ? 'yperf-state-healthy' : 'yperf-state-attention') . '">' . esc_html($ready ? __('READY', 'yablonsky-performance') : __('BLOCKED', 'yablonsky-performance')) . '</span></div>';
        $recommendedHelp = $target === 'commerce' ? __('WordPress Safe Performance + bounded WooCommerce first-hit protection', 'yablonsky-performance') : __('WordPress-only low-risk local YPerf controls', 'yablonsky-performance');
        echo '<div class="yperf-autopilot-grid"><div><span class="yperf-help-label">' . esc_html__('Recommended now', 'yablonsky-performance');
        self::renderHelpTip(__('Recommended now', 'yablonsky-performance'), $recommendedHelp);
        echo '</span><strong>' . esc_html($label) . '</strong></div><div><span class="yperf-help-label">' . esc_html__('Healthy streak', 'yablonsky-performance');
        self::renderHelpTip(__('Healthy streak', 'yablonsky-performance'), __('Consecutive current-version Runtime Acceptance passes without functional FAIL.', 'yablonsky-performance'));
        echo '</span><strong>' . esc_html((string) ((int) ($runtimeHistory['healthy_streak'] ?? 0))) . '</strong></div><div><span class="yperf-help-label">' . esc_html__('Regression guard', 'yablonsky-performance');
        self::renderHelpTip(__('Regression guard', 'yablonsky-performance'), __('One noisy sample cannot trigger a performance rollback by itself.', 'yablonsky-performance'));
        echo '</span><strong>' . esc_html__('Two-pass confirmation', 'yablonsky-performance') . '</strong></div></div>';
        $reasons = (array) ($autopilot['reasons'] ?? array());
        if ($reasons !== array()) {
            echo '<ul class="yperf-autopilot-reasons">';
            foreach ($reasons as $reason) {
                echo '<li>' . esc_html((string) $reason) . '</li>';
            }
            echo '</ul>';
        }
        if ($ready) {
            echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="yperf-autopilot-action"><input type="hidden" name="action" value="yperf_run_autopilot">';
            wp_nonce_field('yperf_run_autopilot');
            echo '<button type="submit" class="button button-primary button-hero">' . esc_html__('Optimize Now', 'yablonsky-performance') . '</button><span>' . esc_html__('YPerf automatically configures only its own WordPress cache and optimization controls. Nginx, Redis, PHP-FPM, OS packages and third-party providers stay untouched unless you explicitly use an expert workflow.', 'yablonsky-performance') . '</span></form>';
        } else {
            echo '<div class="yperf-autopilot-action"><button type="button" class="button" data-yperf-open-tab="health">' . esc_html__('Run Health Check', 'yablonsky-performance') . '</button></div>';
        }
        if ($lastCampaign !== array()) {
            echo '<div class="yperf-last-apply"><strong>' . esc_html__('Last campaign', 'yablonsky-performance') . '</strong><span>' . esc_html(strtoupper((string) ($lastCampaign['status'] ?? ''))) . ' · ' . esc_html((string) ($lastCampaign['mode'] ?? 'manual')) . ' · ' . esc_html((string) ($lastCampaign['profile'] ?? '')) . '</span></div>';
        }
        echo '</div>';
    }

    /** @param array<string,mixed> $plan @param array<string,mixed> $caps @param array<string,mixed> $runtime @param array<string,mixed> $nativePlan @param array<string,mixed> $proof @param array<string,mixed> $effectiveness @param array<string,mixed> $outcome */

    private static function renderOptimizationFlow(): void
    {
        echo '<div class="yperf-optimize-flow" aria-label="' . esc_attr__('Optimization flow', 'yablonsky-performance') . '">';
        $steps = array(
            array(__('1. Evidence', 'yablonsky-performance'), __('Current Runtime Acceptance', 'yablonsky-performance')),
            array(__('2. Advice', 'yablonsky-performance'), __('Yablonsky AI or local Autopilot', 'yablonsky-performance')),
            array(__('3. Approval', 'yablonsky-performance'), __('Exact diff, human-approved', 'yablonsky-performance')),
            array(__('4. Proof', 'yablonsky-performance'), __('Re-run Runtime + rollback guard', 'yablonsky-performance')),
        );
        foreach ($steps as $index => $step) {
            if ($index > 0) {
                echo '<span class="yperf-flow-arrow" aria-hidden="true">→</span>';
            }
            echo '<div class="yperf-flow-step"><strong>' . esc_html($step[0]) . '</strong><span>' . esc_html($step[1]) . '</span></div>';
        }
        echo '</div>';
    }

    private static function renderPerformanceActivationCenter(array $plan, array $caps, array $runtime, array $nativePlan, array $proof, array $effectiveness, array $outcome, array $autopilot, array $runtimeHistory, array $campaignReport, array $aiAdvisorStatus, array $aiAdvisorRecommendation, array $aiAdvisorAnswer): void
    {
        unset($caps, $runtime, $nativePlan, $proof);
        $profile = (string) ($plan['profile'] ?? 'observe');
        $recommended = (string) ($plan['recommended_profile'] ?? 'safe');
        $runtimeClean = !empty($plan['runtime_clean']);
        $last = is_array($plan['last_apply'] ?? null) ? $plan['last_apply'] : array();
        $layers = is_array($effectiveness['layers'] ?? null) ? $effectiveness['layers'] : array();

        echo '<div class="yperf-optimize-layout">';
        self::renderPanelSubnav('optimize', array(
            'recommended' => __('Recommended', 'yablonsky-performance'),
            'profiles' => __('Profiles', 'yablonsky-performance'),
            'proof' => __('Execution Proof', 'yablonsky-performance'),
        ), 'recommended');
        echo '<div class="yperf-subpanel yperf-subpanel-optimize-recommended" data-yperf-subpanel="optimize:recommended">';
        self::renderAutopilotCard($autopilot, $runtimeHistory, $campaignReport);
        echo '<details class="yperf-external-capabilities yperf-ai-optional"><summary><strong>' . esc_html__('Optional Yablonsky AI Advisor', 'yablonsky-performance') . '</strong><span>' . esc_html__('Local optimization works without an AI account', 'yablonsky-performance') . '</span></summary>';
        self::renderAIAdvisorCard($aiAdvisorStatus, $aiAdvisorRecommendation, $aiAdvisorAnswer);
        echo '</details>';
        echo '</div>';
        echo '<div class="yperf-subpanel yperf-subpanel-optimize-profiles" data-yperf-subpanel="optimize:profiles">';
        echo '<div class="yperf-card yperf-activation-card"><div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('PERFORMANCE ACTIVATION', 'yablonsky-performance') . '</span><h2>' . esc_html__('Choose the local control profile', 'yablonsky-performance') . '</h2><p>' . esc_html__('YPerf changes only controls it owns inside WordPress. Existing Nginx, Redis, PHP-FPM and provider configuration remains untouched until a separate reviewed adoption path exists.', 'yablonsky-performance') . '</p></div><span class="yperf-state ' . ($runtimeClean ? 'yperf-state-healthy' : 'yperf-state-neutral') . '">' . esc_html($runtimeClean ? __('Health check clean', 'yablonsky-performance') : __('Fresh check runs automatically', 'yablonsky-performance')) . '</span></div>';
        echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post"><input type="hidden" name="action" value="yperf_apply_performance_plan">';
        wp_nonce_field('yperf_apply_performance_plan');
        echo '<div class="yperf-profile-grid">';
        self::renderProfileChoice('observe', __('Observe', 'yablonsky-performance'), __('No performance-changing YPerf controls. Diagnostics, benchmark, purge, prewarm and intelligence remain available.', 'yablonsky-performance'), $profile, $recommended, __('MONITOR ONLY', 'yablonsky-performance'));
        self::renderProfileChoice('safe', __('WordPress Safe Performance', 'yablonsky-performance'), __('Turns on and verifies YPerf Page Cache, Browser Cache and safe WordPress frontend optimization. Server configuration and WooCommerce first-hit protection stay unchanged.', 'yablonsky-performance'), $profile, $recommended, __('WORDPRESS', 'yablonsky-performance'));
        self::renderProfileChoice('commerce', __('WordPress + WooCommerce Performance', 'yablonsky-performance'), __('Everything in WordPress Safe Performance plus bounded WooCommerce first-hit protection when an eligible workload is proven. Server configuration stays untouched.', 'yablonsky-performance'), $profile, $recommended, __('MAXIMUM LOCAL PROFILE', 'yablonsky-performance'));
        echo '</div>';
        echo '<div class="yperf-profile-hierarchy"><strong>' . esc_html__('Profile hierarchy', 'yablonsky-performance') . '</strong><span>' . esc_html__('Observe → WordPress Safe Performance → WordPress + WooCommerce Performance. Each step includes the controls from the step before it.', 'yablonsky-performance') . '</span></div>';

        echo '<div class="yperf-plan-preview-grid">';
        foreach (array('observe', 'safe', 'commerce') as $profileKey) {
            $definition = PerformancePlan::profileDefinition($profileKey);
            if ($profileKey === $profile) {
                echo '<div class="yperf-plan-preview" data-yperf-plan-preview="' . esc_attr($profileKey) . '"><strong>' . esc_html__('Exact changes', 'yablonsky-performance') . '</strong><ul>';
            } else {
                echo '<div class="yperf-plan-preview" data-yperf-plan-preview="' . esc_attr($profileKey) . '" hidden="hidden"><strong>' . esc_html__('Exact changes', 'yablonsky-performance') . '</strong><ul>';
            }
            foreach ((array) ($definition['changes'] ?? array()) as $change) {
                echo '<li>' . esc_html((string) $change) . '</li>';
            }
            echo '</ul></div>';
        }
        echo '</div>';

        echo '<div class="yperf-apply-row"><button type="submit" class="button button-primary button-hero">' . esc_html__('Apply & Prove Execution', 'yablonsky-performance') . '</button><span>' . esc_html__('YPerf first captures a fresh read-only baseline, then applies the selected local profile and immediately repeats Runtime Acceptance. A preflight FAIL makes no changes; a post-apply FAIL restores the previous profile automatically.', 'yablonsky-performance') . '</span></div></form>';
        if ($last !== array()) {
            echo '<div class="yperf-last-apply"><strong>' . esc_html__('Last applied', 'yablonsky-performance') . '</strong><span>' . esc_html(ucfirst((string) ($last['profile'] ?? ''))) . ' · ' . esc_html(wp_date('Y-m-d H:i:s', (int) ($last['time'] ?? 0))) . '</span></div>';
        }
        echo '</div>';
        echo '</div>';

        echo '<div class="yperf-subpanel yperf-subpanel-optimize-proof" data-yperf-subpanel="optimize:proof">';
        echo '<div class="yperf-card yperf-activation-map"><div class="yperf-card-head"><div><h2>' . esc_html__('Execution map', 'yablonsky-performance') . '</h2><p>' . esc_html__('This is the answer to “what is actually working?”. Detection alone never gets a green ACTIVE state.', 'yablonsky-performance') . '</p></div></div>';
        echo '<div class="yperf-execution-grid">';
        foreach ($layers as $layer) {
            if (!is_array($layer)) {
                continue;
            }
            self::renderExecutionLayerCard($layer);
        }
        echo '</div><div class="yperf-native-next"><button type="button" class="button" data-yperf-open-tab="setup">' . esc_html__('Open Native Adoption & Setup', 'yablonsky-performance') . '</button><span>' . esc_html__('Native/server execution stays read-only until ownership, diff, acceptance and rollback are proven.', 'yablonsky-performance') . '</span></div></div>';

        self::renderOptimizationOutcome($outcome);
        echo '</div>';
        echo '</div>';
    }

    /** @param array<string,mixed> $layer */
    private static function renderExecutionLayerCard(array $layer): void
    {
        $state = sanitize_key((string) ($layer['state'] ?? 'not_proven'));
        $class = self::executionStateClass($state);
        echo '<div class="yperf-execution-card ' . esc_attr($class) . '"><div class="yperf-execution-card-head"><strong>' . esc_html((string) ($layer['name'] ?? '')) . '</strong><span>' . esc_html(self::executionStateLabel($state)) . '</span></div><p>' . esc_html((string) ($layer['evidence'] ?? '')) . '</p><small>' . esc_html((string) ($layer['provider'] ?? '')) . '</small></div>';
    }

    /** @param array<string,mixed> $outcome */
    private static function renderOptimizationOutcome(array $outcome): void
    {
        echo '<div class="yperf-card yperf-outcome-card"><div class="yperf-card-head"><div><h2>' . esc_html__('Last activation proof', 'yablonsky-performance') . '</h2><p>' . esc_html__('What changed, whether Runtime Acceptance accepted it, and what request timings were observed before/after.', 'yablonsky-performance') . '</p></div>';
        if ($outcome === array()) {
            echo '<span class="yperf-state yperf-state-neutral">' . esc_html__('No activation run yet', 'yablonsky-performance') . '</span></div><div class="yperf-empty"><strong>' . esc_html__('Apply a performance profile to create proof.', 'yablonsky-performance') . '</strong><span>' . esc_html__('YPerf will save the exact local diff and the post-apply Runtime Acceptance result.', 'yablonsky-performance') . '</span></div></div>';
            return;
        }
        $accepted = (string) ($outcome['outcome'] ?? '') === 'accepted';
        echo '<span class="yperf-state ' . ($accepted ? 'yperf-state-healthy' : 'yperf-state-attention') . '">' . esc_html($accepted ? __('ACCEPTED', 'yablonsky-performance') : __('ROLLED BACK', 'yablonsky-performance')) . '</span></div>';

        echo '<div class="yperf-outcome-grid"><div><span>' . esc_html__('Profile', 'yablonsky-performance') . '</span><strong>' . esc_html((string) ($outcome['profile'] ?? '')) . '</strong></div><div><span>' . esc_html__('Changed settings', 'yablonsky-performance') . '</span><strong>' . esc_html((string) count((array) ($outcome['changes'] ?? array()))) . '</strong></div><div><span>' . esc_html__('Baseline age', 'yablonsky-performance') . '</span><strong>' . esc_html(self::formatAge(isset($outcome['baseline_age_seconds']) ? (int) $outcome['baseline_age_seconds'] : 0)) . '</strong></div></div>';

        $changes = (array) ($outcome['changes'] ?? array());
        if ($changes !== array()) {
            echo '<div class="yperf-outcome-changes"><strong>' . esc_html__('Applied local diff', 'yablonsky-performance') . '</strong><ul>';
            foreach ($changes as $change) {
                if (!is_array($change)) {
                    continue;
                }
                echo '<li><code>' . esc_html((string) ($change['setting'] ?? '')) . '</code> ' . esc_html((string) ($change['from'] ?? '')) . ' → ' . esc_html((string) ($change['to'] ?? '')) . '</li>';
            }
            echo '</ul></div>';
        }

        $deltas = (array) ($outcome['observed_timing_deltas'] ?? array());
        if ($deltas !== array()) {
            echo '<div class="yperf-outcome-timings"><strong>' . esc_html__('Observed Runtime Acceptance timings', 'yablonsky-performance') . '</strong>';
            foreach (array_slice($deltas, 0, 8) as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $pct = (float) ($row['delta_pct'] ?? 0.0);
                $direction = $pct < 0 ? 'is-better' : ($pct > 0 ? 'is-worse' : 'is-flat');
                echo '<div class="yperf-outcome-row"><span>' . esc_html((string) ($row['name'] ?? '')) . '</span><strong>' . esc_html((string) ($row['before_ms'] ?? 0)) . ' → ' . esc_html((string) ($row['after_ms'] ?? 0)) . ' ms</strong><em class="' . esc_attr($direction) . '">' . esc_html(($pct > 0 ? '+' : '') . (string) $pct . '%') . '</em></div>';
            }
            echo '</div><p class="yperf-inline-note">' . esc_html((string) ($outcome['measurement_note'] ?? '')) . '</p>';
        }
        $originDeltas = (array) ($outcome['origin_cost_deltas'] ?? array());
        if ($originDeltas !== array()) {
            echo '<div class="yperf-outcome-timings"><strong>' . esc_html__('Observed Origin Cost', 'yablonsky-performance') . '</strong>';
            foreach (array_slice($originDeltas, 0, 8) as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $pctValue = $row['db_delta_pct'] ?? null;
                $pct = is_numeric($pctValue) ? (float) $pctValue : 0.0;
                $direction = $pct < 0 ? 'is-better' : ($pct > 0 ? 'is-worse' : 'is-flat');
                echo '<div class="yperf-outcome-row"><span>' . esc_html((string) ($row['name'] ?? '')) . '</span><strong>' . esc_html(number_format_i18n((float) ($row['before_db_ms'] ?? 0), 1)) . ' → ' . esc_html(number_format_i18n((float) ($row['after_db_ms'] ?? 0), 1)) . ' ms DB</strong><em class="' . esc_attr($direction) . '">' . esc_html(($pct > 0 ? '+' : '') . (string) $pct . '% · Q ' . (string) ((int) ($row['query_delta'] ?? 0))) . '</em></div>';
            }
            echo '</div>';
        }
        echo '</div>';
    }

    private static function renderDefinitionRow(string $label, string $value): void
    {
        echo '<div class="yperf-definition-row"><span>' . esc_html($label) . '</span><strong>' . esc_html($value !== '' ? $value : '—') . '</strong></div>';
    }

    private static function formatAge(int $seconds): string
    {
        if ($seconds <= 0) {
            return __('Unknown', 'yablonsky-performance');
        }
        if ($seconds < 60) {
            /* translators: %d: age in seconds. */
            return sprintf(__('%d sec', 'yablonsky-performance'), $seconds);
        }
        if ($seconds < HOUR_IN_SECONDS) {
            /* translators: %d: age in minutes. */
            return sprintf(__('%d min', 'yablonsky-performance'), (int) floor($seconds / 60));
        }
        /* translators: %d: age in hours. */
        return sprintf(__('%d h', 'yablonsky-performance'), (int) floor($seconds / HOUR_IN_SECONDS));
    }

    private static function renderProfileChoice(string $value, string $title, string $description, string $selected, string $recommended, string $scopeBadge = ''): void
    {
        $isSelected = $value === $selected;
        echo '<label class="yperf-profile-card ' . ($isSelected ? 'is-selected' : '') . '"><input type="radio" name="yperf_performance_profile" value="' . esc_attr($value) . '" ' . checked($isSelected, true, false) . '><span class="yperf-profile-title">' . esc_html($title);
        if ($value === $recommended) {
            echo '<em>' . esc_html__('Recommended', 'yablonsky-performance') . '</em>';
        }
        echo '</span>';
        if ($scopeBadge !== '') {
            echo '<span class="yperf-profile-scope">' . esc_html($scopeBadge) . '</span>';
        }
        echo '<span class="yperf-profile-copy">' . esc_html($description) . '</span></label>';
    }

    private static function renderAccelerationLane(string $name, bool $active, string $description, string $kind): void
    {
        echo '<div class="yperf-lane ' . ($active ? 'is-active' : 'is-pending') . '"><div class="yperf-lane-head"><strong>' . esc_html($name) . '</strong><span>' . esc_html($active ? __('ACTIVE', 'yablonsky-performance') : __('PENDING', 'yablonsky-performance')) . '</span></div><p>' . esc_html($description) . '</p><small>' . esc_html(strtoupper($kind)) . '</small></div>';
    }

    /** @param array<string,mixed> $proof */
    private static function pageLaneText(array $proof): string
    {
        $state = (string) ($proof['page_cache']['state'] ?? 'not_proven');
        if (in_array($state, array('miss_then_hit_confirmed', 'hit_confirmed'), true)) {
            return __('Native page-cache execution is proven for this site.', 'yablonsky-performance');
        }
        if ($state === 'bypass_confirmed') {
            return __('The origin probe currently proves BYPASS, so YPerf will not claim page acceleration.', 'yablonsky-performance');
        }
        return __('Page-cache execution is not yet proven/adopted. YPerf will not pretend that a detected config is an active accelerator.', 'yablonsky-performance');
    }

    /** @param array<string,mixed> $proof */
    private static function objectLaneText(array $proof): string
    {
        if (!empty($proof['object_cache']['runtime_external'])) {
            return __('WordPress confirms an external persistent object cache is active.', 'yablonsky-performance');
        }
        $state = (string) ($proof['object_cache']['redis']['state'] ?? 'unknown');
        if (in_array($state, array('high_memory_pressure', 'active_eviction_pressure'), true)) {
            return __('Redis is reachable but under eviction/memory pressure; adding a new consumer is intentionally blocked pending ownership review.', 'yablonsky-performance');
        }
        return __('A memory engine may be available, but WordPress has not confirmed an adopted persistent object-cache execution layer.', 'yablonsky-performance');
    }

    /** @param array<string,mixed> $proof */
    private static function originLaneText(array $proof): string
    {
        $state = (string) ($proof['origin']['state'] ?? 'unknown');
        if ($state === 'site_pool_mapped') {
            return __('The site is mapped to a concrete PHP-FPM pool; origin-budget recommendations can now be evidence-based.', 'yablonsky-performance');
        }
        return __('PHP-FPM is visible, but the exact site pool/budget is not yet proven. YPerf will not change aggregate worker limits.', 'yablonsky-performance');
    }

    private static function renderTechnicalIdentity(): void
    {
        $canonical = strpos((string) YPERF_BASENAME, 'yablonsky-performance/') === 0;
        echo '<div class="yperf-card yperf-identity-card"><div class="yperf-card-head"><div><h2>' . esc_html__('Technical identity', 'yablonsky-performance') . '</h2><p>' . esc_html__('Canonical runtime only. YPerf no longer performs live self-migration or legacy-directory deletion.', 'yablonsky-performance') . '</p></div><span class="yperf-state ' . ($canonical ? 'yperf-state-healthy' : 'yperf-state-attention') . '">' . esc_html($canonical ? __('Canonical', 'yablonsky-performance') : __('Review path', 'yablonsky-performance')) . '</span></div>';
        echo '<div class="yperf-identity-grid"><div><span>' . esc_html__('Current plugin basename', 'yablonsky-performance') . '</span><code>' . esc_html((string) YPERF_BASENAME) . '</code></div><div><span>' . esc_html__('Canonical directory', 'yablonsky-performance') . '</span><code>yablonsky-performance/</code></div></div>';
        if ($canonical) {
            echo '<div class="yperf-good-line"><strong>' . esc_html__('One canonical runtime. No legacy runtime bridge is loaded.', 'yablonsky-performance') . '</strong></div>';
        }
        echo '</div>';
    }



    /** @param array<string,mixed> $report */
    private static function renderFieldAcceptance(array $report): void
    {
        $progress = max(0, min(100, (int) ($report['progress_percent'] ?? 0)));
        $manual = is_array($report['manual'] ?? null) ? $report['manual'] : array();
        $definitions = is_array($report['definitions'] ?? null) ? $report['definitions'] : array();
        $automatic = is_array($report['automatic'] ?? null) ? $report['automatic'] : array();
        echo '<div class="yperf-card yperf-field-acceptance"><div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('FIELD ACCEPTANCE', 'yablonsky-performance') . '</span><h2>' . esc_html__('Production field checklist', 'yablonsky-performance') . '</h2><p>' . esc_html__('Automatic evidence and human acceptance are tracked separately and bound to this YPerf version and site.', 'yablonsky-performance') . '</p></div><span class="yperf-state ' . (!empty($report['complete']) ? 'yperf-state-healthy' : 'yperf-state-attention') . '">' . esc_html((string) ($report['passed'] ?? 0) . ' / ' . (string) ($report['total'] ?? 0)) . '</span></div>';
        echo '<div class="yperf-native-op-progress"><div><strong>' . esc_html__('Field readiness', 'yablonsky-performance') . '</strong><span>' . esc_html($progress . '%') . '</span></div><div class="yperf-progress-track"><span style="width:' . esc_attr((string) $progress) . '%"></span></div></div>';
        echo '<div class="yperf-field-auto">';
        foreach (array(
            'runtime_current_clean' => __('Runtime current & clean', 'yablonsky-performance'),
            'control_plane_not_blocked' => __('Control plane acceptable', 'yablonsky-performance'),
            'origin_cost_evidence' => __('Origin Cost captured', 'yablonsky-performance'),
            'mu_inventory_captured' => __('MU-plugin inventory available', 'yablonsky-performance'),
        ) as $id => $label) {
            $done = !empty($automatic[$id]);
            echo '<span class="' . ($done ? 'is-pass' : 'is-wait') . '"><b>' . esc_html($done ? '✓' : '○') . '</b>' . esc_html($label) . '</span>';
        }
        echo '</div><form action="' . esc_url(admin_url('admin-post.php')) . '" method="post"><input type="hidden" name="action" value="yperf_save_field_acceptance">';
        wp_nonce_field('yperf_save_field_acceptance');
        echo '<div class="yperf-field-manual">';
        foreach ($definitions as $id => $label) {
            $id = sanitize_key((string) $id);
            echo '<label><input type="checkbox" name="yperf_field_check[' . esc_attr($id) . ']" value="1" ' . checked(!empty($manual[$id]), true, false) . '> <span>' . esc_html((string) $label) . '</span></label>';
        }
        echo '</div><button type="submit" class="button">' . esc_html__('Save field acceptance', 'yablonsky-performance') . '</button></form>';
        echo '<p class="yperf-inline-note">' . esc_html__('Updating YPerf invalidates manual acceptance for the previous build; the next candidate must be accepted again.', 'yablonsky-performance') . '</p></div>';
    }

    /** @param array<string,mixed> $inventory */
    private static function renderMuPluginInventory(array $inventory): void
    {
        $count = (int) ($inventory['count'] ?? 0);
        $candidates = (int) ($inventory['review_candidates'] ?? 0);
        $plugins = is_array($inventory['plugins'] ?? null) ? $inventory['plugins'] : array();
        /* translators: %d: number of detected must-use plugins. */
        $detectedLabel = sprintf(__('%d detected', 'yablonsky-performance'), $count);
        echo '<div class="yperf-card yperf-mu-inventory"><div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('FIELD MIGRATION', 'yablonsky-performance') . '</span><h2>' . esc_html__('MU Plugin Inventory', 'yablonsky-performance') . '</h2><p>' . esc_html__('Read-only inventory for retiring historical performance fixes one responsibility at a time.', 'yablonsky-performance') . '</p></div><span class="yperf-state yperf-state-neutral">' . esc_html($detectedLabel) . '</span></div>';
        if ($count < 1) {
            echo '<p class="yperf-empty">' . esc_html__('No must-use plugin entry files were detected.', 'yablonsky-performance') . '</p></div>';
            return;
        }
        echo '<div class="yperf-mu-summary"><span><strong>' . esc_html((string) $count) . '</strong>' . esc_html__('MU plugins', 'yablonsky-performance') . '</span><span><strong>' . esc_html((string) $candidates) . '</strong>' . esc_html__('performance-review candidates', 'yablonsky-performance') . '</span></div><div class="yperf-mu-list">';
        foreach ($plugins as $plugin) {
            if (!is_array($plugin)) { continue; }
            $signals = array_map('sanitize_key', (array) ($plugin['signals'] ?? array()));
            $label = (string) ($plugin['name'] ?? '') !== '' ? (string) $plugin['name'] : (string) ($plugin['file'] ?? '');
            echo '<div class="yperf-mu-row"><div><strong>' . esc_html($label) . '</strong><small>' . esc_html((string) ($plugin['file'] ?? '')) . ((string) ($plugin['version'] ?? '') !== '' ? ' · v' . esc_html((string) $plugin['version']) : '') . '</small></div><div class="yperf-chip-row">';
            foreach ($signals as $signal) { echo '<span class="yperf-chip">' . esc_html($signal) . '</span>'; }
            echo '</div><code>' . esc_html(substr((string) ($plugin['sha256'] ?? ''), 0, 12)) . '…</code></div>';
        }
        echo '</div><p class="yperf-benchmark-safety"><strong>' . esc_html__('No bulk removal.', 'yablonsky-performance') . '</strong> ' . esc_html__('Prove the equivalent YPerf responsibility, capture a baseline, retire one legacy responsibility, run acceptance, then keep or roll back.', 'yablonsky-performance') . '</p></div>';
    }

    /** @param array<string,mixed> $progress */
    private static function renderLocalizationProgress(array $progress): void
    {
        $packs = is_array($progress['packs'] ?? null) ? $progress['packs'] : array();
        $complete = (int) ($progress['languages_complete'] ?? 0);
        $total = max(1, (int) ($progress['languages_total'] ?? 1));
        $remaining = max(0, (int) ($progress['languages_remaining'] ?? ($total - $complete)));
        $variants = max(0, (int) ($progress['locale_variants'] ?? 0));
        $percent = max(0, min(100, (int) ($progress['localization_percent'] ?? 0)));

        echo '<div class="yperf-card yperf-localization-card"><div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('MULTILINGUAL RELEASE', 'yablonsky-performance') . '</span><h2>' . esc_html__('Localization coverage', 'yablonsky-performance') . '</h2><p>' . esc_html__('Admin UI follows the WordPress administrator locale. A language is complete only after full key parity and translation QA.', 'yablonsky-performance') . '</p></div><span class="yperf-state yperf-state-attention">' . esc_html($complete . ' / ' . $total) . '</span></div>';
        echo '<div class="yperf-localization-summary"><div><span>' . esc_html__('Canonical languages', 'yablonsky-performance') . '</span><strong>' . esc_html((string) $total) . '</strong></div><div><span>' . esc_html__('Locale variants', 'yablonsky-performance') . '</span><strong>' . esc_html((string) $variants) . '</strong></div><div><span>' . esc_html__('Complete', 'yablonsky-performance') . '</span><strong>' . esc_html((string) $complete) . '</strong></div><div><span>' . esc_html__('Remaining', 'yablonsky-performance') . '</span><strong>' . esc_html((string) $remaining) . '</strong></div></div>';
        echo '<div class="yperf-localization-track"><span style="width:' . esc_attr((string) $percent) . '%"></span></div>';
        echo '<div class="yperf-language-chips">';
        foreach ($packs as $pack) {
            if (!is_array($pack)) {
                continue;
            }
            $status = sanitize_key((string) ($pack['status'] ?? 'target'));
            $coverage = (int) ($pack['coverage_percent'] ?? 0);
            $done = in_array($status, array('source', 'complete'), true) && $coverage === 100;
            $native = (string) ($pack['native'] ?? ($pack['name'] ?? ''));
            $locales = implode(' / ', array_map('strval', (array) ($pack['locales'] ?? array())));
            echo '<span class="yperf-language-chip ' . ($done ? 'is-complete' : 'is-target') . '" title="' . esc_attr($locales) . '"><b>' . esc_html($native) . '</b><em>' . esc_html($done ? __('COMPLETE', 'yablonsky-performance') : __('QUEUE', 'yablonsky-performance')) . '</em></span>';
        }
        echo '</div><p class="yperf-inline-note"><strong>' . esc_html__('Translation integrity rule:', 'yablonsky-performance') . '</strong> ' . esc_html__('Target-registry entries are never presented as translated packs. English-filled placeholder catalogs do not count.', 'yablonsky-performance') . '</p></div>';
    }

    private static function renderHelpTip(string $title, string $body): void
    {
        if ($body === '') {
            return;
        }
        echo '<button type="button" class="yperf-help-tip" aria-label="' . esc_attr__('Details', 'yablonsky-performance') . '" aria-expanded="false" data-yperf-help-title="' . esc_attr($title) . '" data-yperf-help-body="' . esc_attr($body) . '"><span aria-hidden="true">?</span></button>';
    }

    /** @param array<string,mixed> $progress @param array<string,mixed> $evidence */
    private static function renderProductProgress(array $progress, array $evidence): void
    {
        $percent = max(0, min(100, (int) ($progress['site_readiness_percent'] ?? 0)));
        $complete = !empty($progress['site_readiness_complete']);
        $completedStages = max(0, (int) ($progress['site_readiness_completed_stages'] ?? 0));
        $totalStages = max(1, (int) ($progress['site_readiness_total_stages'] ?? 4));
        $providerExit = is_array($progress['provider_exit'] ?? null) ? $progress['provider_exit'] : array();
        $stages = is_array($progress['stages'] ?? null) ? $progress['stages'] : array();
        $next = is_array($progress['next'] ?? null) ? $progress['next'] : array();
        $nextLabel = (string) ($next['label'] ?? __('Review production readiness', 'yablonsky-performance'));
        $nextTab = sanitize_key((string) ($next['tab'] ?? 'insights'));
        if (!empty($progress['provider_exit_applicable']) && !$complete) {
            $nextLabel = __('Continue guided migration', 'yablonsky-performance');
            $nextTab = 'migration';
        }
        $sources = is_array($evidence['sources'] ?? null) ? $evidence['sources'] : array();

        $freshRows = array();
        foreach (array(
            'environment_check' => __('Environment', 'yablonsky-performance'),
            'runtime_acceptance' => __('Runtime Acceptance', 'yablonsky-performance'),
            'benchmark' => __('Benchmark', 'yablonsky-performance'),
            'host_snapshot' => __('Host Intelligence', 'yablonsky-performance'),
        ) as $sourceId => $label) {
            $source = is_array($sources[$sourceId] ?? null) ? $sources[$sourceId] : array();
            $freshness = sanitize_key((string) ($source['freshness'] ?? 'unknown'));
            $available = !empty($source['available']);
            $versionRelation = sanitize_key((string) ($source['version_relation'] ?? ''));
            $quality = sanitize_key((string) ($source['quality'] ?? ''));
            $healthy = $available
                && in_array($freshness, array('fresh', 'recent'), true)
                && ($versionRelation === '' || $versionRelation === 'current')
                && ($quality === '' || $quality === 'usable');
            $status = $healthy ? 'current' : (!$available ? 'missing' : ($versionRelation === 'historical' ? 'previous_version' : $freshness));
            $freshRows[$sourceId] = array('label' => $label, 'healthy' => $healthy, 'status' => $status);
        }

        $coreNeedsRefresh = empty($freshRows['environment_check']['healthy'])
            || empty($freshRows['runtime_acceptance']['healthy'])
            || empty($freshRows['benchmark']['healthy']);

        echo '<section class="yperf-product-progress yperf-site-readiness ' . ($complete ? 'is-complete' : 'is-incomplete') . '" aria-label="' . esc_attr__('Site readiness', 'yablonsky-performance') . '">';
        echo '<div class="yperf-product-progress-main"><div><span class="yperf-eyebrow yperf-help-label">' . esc_html__('SITE READINESS', 'yablonsky-performance');
        self::renderHelpTip(__('Site readiness', 'yablonsky-performance'), __('This indicator tracks site configuration and evidence — not YPerf development progress.', 'yablonsky-performance'));
        echo '</span><strong>' . esc_html((string) ($progress['site_readiness_label'] ?? ($complete ? __('Production evidence current', 'yablonsky-performance') : __('Prepare this site for proven performance', 'yablonsky-performance')))) . '</strong></div>';
        echo '<div class="yperf-product-progress-value"><strong>' . esc_html((string) $percent) . '%</strong><span>' . esc_html(sprintf(
            /* translators: 1: completed site-readiness stages, 2: total site-readiness stages. */
            __('%1$d / %2$d stages complete', 'yablonsky-performance'),
            $completedStages,
            $totalStages
        )) . '</span></div></div>';
        echo '<div class="yperf-product-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr((string) $percent) . '"><span style="width:' . esc_attr((string) $percent) . '%"></span></div>';

        echo '<div class="yperf-evidence-freshness" aria-label="' . esc_attr__('Evidence freshness', 'yablonsky-performance') . '">';
        foreach ($freshRows as $sourceId => $row) {
            $healthy = !empty($row['healthy']);
            $status = sanitize_key((string) ($row['status'] ?? 'unknown'));
            $class = $healthy ? 'is-current' : ($status === 'missing' && $sourceId === 'host_snapshot' ? 'is-optional' : 'is-stale');
            $statusLabel = $healthy ? __('CURRENT', 'yablonsky-performance') : self::evidenceStatusLabel($status, $sourceId === 'host_snapshot');
            echo '<div class="yperf-evidence-source ' . esc_attr($class) . '"><span>' . esc_html((string) $row['label']) . '</span><strong>' . esc_html($statusLabel) . '</strong></div>';
        }
        echo '</div>';

        if (!empty($progress['provider_exit_applicable']) && !empty($providerExit['total_items'])) {
            $migrationPercent = max(0, min(100, (int) ($providerExit['percent'] ?? 0)));
            $migrationReady = max(0, (int) ($providerExit['ready_items'] ?? 0));
            $migrationTotal = max(1, (int) ($providerExit['total_items'] ?? 1));
            $migrationProvider = (string) ($providerExit['provider'] ?? 'W3 Total Cache');
            echo '<div class="yperf-migration-readiness-mini"><div class="yperf-migration-readiness-copy"><span class="yperf-help-label">' . esc_html__('MIGRATION READINESS', 'yablonsky-performance');
            self::renderHelpTip(__('Migration readiness', 'yablonsky-performance'), __('This is separate from Site Readiness. It tracks whether the detected legacy provider can be retired without losing an active responsibility.', 'yablonsky-performance'));
            echo '</span><strong>' . esc_html($migrationProvider) . '</strong></div><div class="yperf-migration-readiness-meter"><div class="yperf-progress-top"><span>' . esc_html(sprintf(
                /* translators: 1: ready migration gates, 2: total migration gates. */
                __('%1$d / %2$d gates', 'yablonsky-performance'),
                $migrationReady,
                $migrationTotal
            )) . '</span><strong>' . esc_html((string) $migrationPercent) . '%</strong></div><div class="yperf-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr((string) $migrationPercent) . '"><span style="width:' . esc_attr((string) $migrationPercent) . '%"></span></div></div><button type="button" class="button" data-yperf-open-tab="migration">' . esc_html__('Open migration', 'yablonsky-performance') . '</button></div>';
        }

        echo '<div class="yperf-readiness-stages">';
        foreach ($stages as $stage) {
            if (!is_array($stage)) {
                continue;
            }
            $done = !empty($stage['complete']);
            $tab = sanitize_key((string) ($stage['tab'] ?? 'overview'));
            $stageLabel = (string) ($stage['label'] ?? '');
            $stageDescription = (string) ($stage['description'] ?? '');
            echo '<div class="yperf-readiness-stage-wrap"><button type="button" class="yperf-readiness-stage ' . ($done ? 'is-complete' : 'is-pending') . '" data-yperf-open-tab="' . esc_attr($tab) . '"><span class="yperf-readiness-icon" aria-hidden="true">' . esc_html($done ? '✓' : '○') . '</span><span><strong>' . esc_html($stageLabel) . '</strong></span></button>';
            self::renderHelpTip($stageLabel, $stageDescription);
            echo '</div>';
        }
        echo '<div class="yperf-readiness-next"><span>' . esc_html__('Next safe step', 'yablonsky-performance') . '</span>';
        if ($coreNeedsRefresh) {
            echo '<div class="yperf-help-action"><button type="button" class="button button-primary" id="yperf-refresh-evidence">' . esc_html__('Refresh read-only evidence', 'yablonsky-performance') . '</button>';
            self::renderHelpTip(__('Refresh read-only evidence', 'yablonsky-performance'), __('Environment + Runtime Acceptance + Benchmark. No settings are changed.', 'yablonsky-performance'));
            echo '</div>';
        } else {
            echo '<button type="button" class="button button-primary" data-yperf-open-tab="' . esc_attr($nextTab) . '">' . esc_html($nextLabel) . '</button>';
        }
        echo '</div></div>';
        echo '<div id="yperf-evidence-refresh-progress" class="yperf-progress-wrap yperf-evidence-refresh-progress" hidden><div class="yperf-progress-top"><strong id="yperf-evidence-refresh-label">' . esc_html__('Preparing read-only evidence refresh…', 'yablonsky-performance') . '</strong><span id="yperf-evidence-refresh-percent">0%</span></div><div class="yperf-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span id="yperf-evidence-refresh-bar"></span></div><div class="yperf-evidence-refresh-phases" aria-label="' . esc_attr__('Evidence refresh phases', 'yablonsky-performance') . '"><div data-yperf-evidence-phase="environment"><span aria-hidden="true">○</span><strong>' . esc_html__('Environment', 'yablonsky-performance') . '</strong></div><div data-yperf-evidence-phase="runtime"><span aria-hidden="true">○</span><strong>' . esc_html__('Runtime Acceptance', 'yablonsky-performance') . '</strong></div><div data-yperf-evidence-phase="benchmark"><span aria-hidden="true">○</span><strong>' . esc_html__('Benchmark', 'yablonsky-performance') . '</strong></div></div><small>' . esc_html__('This refresh is read-only: it rechecks the environment, runs Runtime Acceptance and rebuilds the performance benchmark. It does not enable cache, alter providers or change host configuration.', 'yablonsky-performance') . '</small></div>';
        echo '</section>';
    }

    private static function evidenceStatusLabel(string $status, bool $hostOptional = false): string
    {
        if ($hostOptional && $status === 'missing') {
            return __('OPTIONAL', 'yablonsky-performance');
        }
        $labels = array(
            'missing' => __('MISSING', 'yablonsky-performance'),
            'unknown' => __('UNKNOWN', 'yablonsky-performance'),
            'stale' => __('STALE', 'yablonsky-performance'),
            'incident' => __('INCIDENT', 'yablonsky-performance'),
            'previous_version' => __('PREVIOUS VERSION', 'yablonsky-performance'),
        );
        return (string) ($labels[$status] ?? strtoupper(str_replace('_', ' ', $status)));
    }

    /** @param array<string,string> $items */
    private static function renderPanelSubnav(string $panel, array $items, string $active): void
    {
        echo '<div class="yperf-panel-subnav" role="tablist" aria-label="' . esc_attr__('Dashboard sections', 'yablonsky-performance') . '">';
        foreach ($items as $id => $label) {
            $isActive = $id === $active;
            echo '<button type="button" class="yperf-panel-subtab ' . ($isActive ? 'is-active' : '') . '" role="tab" tabindex="' . ($isActive ? '0' : '-1') . '" data-yperf-subtab="' . esc_attr($panel . ':' . $id) . '" aria-selected="' . ($isActive ? 'true' : 'false') . '">' . esc_html($label) . '</button>';
        }
        echo '</div>';
    }

    private static function renderKpiCard(string $label, string $value, string $meta, string $state, string $featureId = '', string $tabTarget = ''): void
    {
        $classes = array('yperf-kpi', 'yperf-kpi-' . sanitize_html_class($state));
        $attributes = array();
        if ($featureId !== '') {
            $classes[] = 'yperf-kpi-clickable';
            $attributes[] = 'data-yperf-feature="' . esc_attr($featureId) . '"';
            $attributes[] = 'role="button"';
            $attributes[] = 'tabindex="0"';
            /* translators: %s: performance feature label. */
            $attributes[] = 'aria-label="' . esc_attr(sprintf(__('Open %s details', 'yablonsky-performance'), $label)) . '"';
        } elseif ($tabTarget !== '') {
            $classes[] = 'yperf-kpi-clickable';
            $attributes[] = 'data-yperf-open-tab="' . esc_attr($tabTarget) . '"';
            $attributes[] = 'role="button"';
            $attributes[] = 'tabindex="0"';
        }
        echo '<div class="' . esc_attr(implode(' ', $classes)) . '"';
        if ($attributes !== array()) {
            echo ' ' . wp_kses_data(implode(' ', $attributes));
        }
        echo '><span class="yperf-kpi-label">' . esc_html($label) . '</span><strong>' . esc_html($value) . '</strong><span class="yperf-kpi-meta">' . esc_html($meta) . '</span><span class="yperf-kpi-arrow" aria-hidden="true">→</span></div>';
    }

    private static function runtimeMeta(int $pass, int $fail, int $skip): string
    {
        if ($pass === 0 && $fail === 0 && $skip === 0) {
            return __('Run the first read-only check', 'yablonsky-performance');
        }
        return sprintf(
            /* translators: 1: passed count, 2: failed count, 3: skipped count. */
            __('%1$d passed · %2$d failed · %3$d skipped', 'yablonsky-performance'),
            $pass,
            $fail,
            $skip
        );
    }

    /** @param array<string,mixed> $caps @return array<string,string> */
    private static function pageCacheKpi(array $caps): array
    {
        $providers = is_array($caps['providers'] ?? null) ? $caps['providers'] : array();
        $w3tc = is_array($providers['w3-total-cache'] ?? null) ? $providers['w3-total-cache'] : array();
        $layers = is_array($w3tc['layers'] ?? null) ? $w3tc['layers'] : array();
        $page = is_array($layers['page_cache'] ?? null) ? $layers['page_cache'] : array();

        if (!empty($w3tc['config_readable'])) {
            $engine = (string) ($page['engine'] ?? __('Unknown engine', 'yablonsky-performance'));
            if (!empty($page['enabled'])) {
                return array(
                    'value' => __('External cache active', 'yablonsky-performance'),
                    'meta'  => 'W3 Total Cache · ' . $engine,
                    'state' => 'healthy',
                );
            }
            return array(
                'value' => __('Cache off', 'yablonsky-performance'),
                'meta'  => 'W3 Total Cache · ' . $engine . (!empty($caps['page_cache']['advanced_cache_dropin']) ? ' · advanced-cache.php' : ''),
                'state' => 'neutral',
            );
        }

        return array(
            'value' => !empty($caps['page_cache']['advanced_cache_dropin']) ? __('External layer detected', 'yablonsky-performance') : __('Ready for adoption', 'yablonsky-performance'),
            'meta'  => self::pageCacheLabel($caps) . (!empty($caps['page_cache']['advanced_cache_dropin']) ? ' · advanced-cache.php' : ''),
            'state' => !empty($caps['page_cache']['advanced_cache_dropin']) ? 'neutral' : 'attention',
        );
    }

    /** @param array<string,mixed> $report @param array<string,mixed> $caps */
    private static function renderEnvironmentCheck(array $report, array $caps): void
    {
        if ($report === array()) {
            self::renderEnvironmentBaseline($caps);
            return;
        }
        $summary = is_array($report['summary'] ?? null) ? $report['summary'] : array();
        echo '<div class="yperf-env-summary">';
        foreach (array('ready' => __('Ready', 'yablonsky-performance'), 'available' => __('Available', 'yablonsky-performance'), 'attention' => __('Attention', 'yablonsky-performance'), 'blocked' => __('Blocked', 'yablonsky-performance'), 'unknown' => __('Unknown', 'yablonsky-performance')) as $state => $label) {
            echo '<div class="yperf-env-count yperf-env-' . esc_attr($state) . '"><strong>' . esc_html((string) ($summary[$state] ?? 0)) . '</strong><span>' . esc_html($label) . '</span></div>';
        }
        echo '</div>';
        echo '<div class="yperf-env-list">';
        foreach ((array) ($report['checks'] ?? array()) as $check) {
            if (!is_array($check)) {
                continue;
            }
            $state = sanitize_html_class((string) ($check['state'] ?? 'unknown'));
            echo '<div class="yperf-env-row"><span class="yperf-env-badge yperf-env-badge-' . esc_attr($state) . '">' . esc_html(strtoupper($state)) . '</span><div><strong>' . esc_html((string) ($check['name'] ?? '')) . '</strong><span>' . esc_html((string) ($check['details'] ?? '')) . '</span></div><code>' . esc_html((string) ($check['value'] ?? '')) . '</code></div>';
        }
        echo '</div>';
        if (!empty($report['generated_at'])) {
            /* translators: %s is the local date and time of the most recent Environment Check. */
            $lastChecked = sprintf(__('Last checked: %s', 'yablonsky-performance'), wp_date('Y-m-d H:i:s', (int) $report['generated_at']));
            echo '<p class="yperf-env-checked-at">' . esc_html($lastChecked) . '</p>';
        }
    }

    /** @param array<string,mixed> $caps */
    private static function renderEnvironmentBaseline(array $caps): void
    {
        $server = strtoupper((string) ($caps['web_server']['family'] ?? 'unknown'));
        $sapi = (string) ($caps['origin']['php_sapi'] ?? PHP_SAPI);
        $opcache = !empty($caps['opcode_cache']['enabled']);
        $object = is_array($caps['object_cache'] ?? null) ? $caps['object_cache'] : array();
        $application = is_array($caps['application'] ?? null) ? $caps['application'] : array();
        $rows = array(
            array('ready', __('Web server', 'yablonsky-performance'), $server, __('Detected immediately from the current request.', 'yablonsky-performance')),
            array('ready', __('PHP runtime', 'yablonsky-performance'), $sapi, __('Visible without a deeper scan.', 'yablonsky-performance')),
            array($opcache ? 'ready' : 'available', __('PHP OPcache', 'yablonsky-performance'), $opcache ? __('Active', 'yablonsky-performance') : __('Not confirmed', 'yablonsky-performance'), __('Runtime state is visible immediately.', 'yablonsky-performance')),
            array(!empty($object['redis_extension']) ? 'available' : 'unknown', __('Redis', 'yablonsky-performance'), !empty($object['redis_extension']) ? __('PHP extension present', 'yablonsky-performance') : __('Extension not detected', 'yablonsky-performance'), __('Run the Environment Check to confirm local service reachability.', 'yablonsky-performance')),
            array(!empty($object['memcached_extension']) ? 'available' : 'unknown', __('Memcached', 'yablonsky-performance'), !empty($object['memcached_extension']) ? __('PHP extension present', 'yablonsky-performance') : __('Extension not detected', 'yablonsky-performance'), __('Run the Environment Check to confirm local service reachability.', 'yablonsky-performance')),
            array(!empty($caps['page_cache']['advanced_cache_dropin']) ? 'attention' : 'available', __('advanced-cache.php', 'yablonsky-performance'), !empty($caps['page_cache']['advanced_cache_dropin']) ? __('Present', 'yablonsky-performance') : __('Absent', 'yablonsky-performance'), __('Presence is an ownership signal, not proof that page cache is enabled.', 'yablonsky-performance')),
            array(!empty($application['woocommerce']) ? 'ready' : 'available', __('WooCommerce', 'yablonsky-performance'), !empty($application['woocommerce']) ? __('Detected', 'yablonsky-performance') : __('Not detected', 'yablonsky-performance'), __('Application context changes cacheability and private-route rules.', 'yablonsky-performance')),
            array(!empty($application['ypm']) ? 'ready' : 'available', __('Multilingual authority', 'yablonsky-performance'), !empty($application['ypm']) ? 'YMulti' : __('Not detected', 'yablonsky-performance'), __('Language ownership affects selective purge and prewarm.', 'yablonsky-performance')),
        );
        echo '<div class="yperf-intelligence-note yperf-baseline-note"><strong>' . esc_html__('Baseline is already visible.', 'yablonsky-performance') . '</strong><span>' . esc_html__('The deeper check only adds localhost service reachability and optional capability confirmation; it is not an installation step.', 'yablonsky-performance') . '</span></div>';
        echo '<div class="yperf-env-list yperf-env-baseline">';
        foreach ($rows as $row) {
            echo '<div class="yperf-env-row"><span class="yperf-env-badge yperf-env-badge-' . esc_attr((string) $row[0]) . '">' . esc_html(strtoupper((string) $row[0])) . '</span><div><strong>' . esc_html((string) $row[1]) . '</strong><span>' . esc_html((string) $row[3]) . '</span></div><code>' . esc_html((string) $row[2]) . '</code></div>';
        }
        echo '</div>';
    }

    /** @param array<string,mixed> $host */
    private static function renderHostIntelligence(array $host): void
    {
        $hasHost = $host !== array();
        $siteHost = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
        $siteHost = preg_match('/^[A-Za-z0-9.-]+$/', $siteHost) === 1 ? strtolower($siteHost) : 'example.com';
        $command = 'sudo php yperf-host-inspector.php --host=' . $siteHost . ' --output=yperf-host-snapshot.json';
        $downloadUrl = wp_nonce_url(
            admin_url('admin-post.php?action=yperf_download_host_inspector'),
            'yperf_download_host_inspector'
        );

        echo '<div class="yperf-card yperf-host-card"><div class="yperf-card-head"><div><h2>' . esc_html__('Host Intelligence', 'yablonsky-performance') . '</h2><p>' . esc_html__('Optional host-level evidence for Nginx, PHP-FPM, memory and Redis. The inspector runs on the server; WordPress receives only a secret-redacted read-only JSON snapshot.', 'yablonsky-performance') . '</p></div><span class="yperf-state ' . ($hasHost ? 'yperf-state-healthy' : 'yperf-state-neutral') . '">' . esc_html($hasHost ? __('Snapshot connected', 'yablonsky-performance') : __('Optional', 'yablonsky-performance')) . '</span></div>';

        echo '<div class="yperf-host-workflow">';
        echo '<div class="yperf-host-step"><span class="yperf-host-step-number">1</span><div><strong>' . esc_html__('Download the read-only Host Inspector', 'yablonsky-performance') . '</strong><p>' . esc_html__('The helper is bundled with this exact YPerf release. It does not install packages, edit configuration, restart services, scan Redis keys or export raw server configuration.', 'yablonsky-performance') . '</p><a class="button" href="' . esc_url($downloadUrl) . '">' . esc_html__('Download Host Inspector', 'yablonsky-performance') . '</a></div></div>';
        echo '<div class="yperf-host-step"><span class="yperf-host-step-number">2</span><div><strong>' . esc_html__('Run it on the WordPress server', 'yablonsky-performance') . '</strong><p>' . esc_html__('Upload the downloaded file to the server and run this command in SSH. sudo is used only by the standalone helper to read host configuration that WordPress itself cannot see.', 'yablonsky-performance') . '</p><div class="yperf-command-row"><code>' . esc_html($command) . '</code><button type="button" class="button" data-yperf-copy-text="' . esc_attr($command) . '">' . esc_html__('Copy command', 'yablonsky-performance') . '</button></div></div></div>';
        echo '<div class="yperf-host-step"><span class="yperf-host-step-number">3</span><div><strong>' . esc_html__('Import the generated JSON below', 'yablonsky-performance') . '</strong><p>' . esc_html__('The JSON is evidence, not a YPerf settings backup. There is intentionally no matching “Export settings” button because the source of truth is the host inspector, not WordPress.', 'yablonsky-performance') . '</p></div></div>';
        echo '</div>';

        if ($hasHost) {
            $hostInfo = is_array($host['host'] ?? null) ? $host['host'] : array();
            $nginx = is_array($host['nginx'] ?? null) ? $host['nginx'] : array();
            $fpm = is_array($host['php_fpm'] ?? null) ? $host['php_fpm'] : array();
            $redis = is_array($host['redis'] ?? null) ? $host['redis'] : array();
            $site = is_array($host['site_runtime'] ?? null) ? $host['site_runtime'] : array();
            $used = (float) ($redis['used_memory_mb'] ?? 0);
            $max = (float) ($redis['maxmemory_mb'] ?? 0);
            $redisPct = $max > 0 ? round(($used / $max) * 100, 1) : 0;
            $fingerprintJson = wp_json_encode($host);
            $fingerprint = is_string($fingerprintJson) ? substr(hash('sha256', $fingerprintJson), 0, 16) : '';
            $helperVersion = sanitize_text_field((string) ($host['helper_version'] ?? 'unknown'));
            $generatedAt = max(0, (int) ($host['generated_at'] ?? 0));
            $age = $generatedAt > 0 ? human_time_diff($generatedAt, time()) : '';
            echo '<div class="yperf-host-proof-meta"><span><b>' . esc_html__('Inspector', 'yablonsky-performance') . '</b> v' . esc_html($helperVersion) . '</span><span><b>' . esc_html__('Schema', 'yablonsky-performance') . '</b> ' . esc_html((string) ((int) ($host['schema'] ?? 0))) . '</span><span><b>' . esc_html__('Fingerprint', 'yablonsky-performance') . '</b> <code>' . esc_html($fingerprint) . '…</code></span>' . ($age !== '' ? '<span><b>' . esc_html__('Age', 'yablonsky-performance') . '</b> ' . esc_html($age) . '</span>' : '') . '</div>';
            echo '<div class="yperf-host-summary">';
            self::renderHostMetric(__('CPU', 'yablonsky-performance'), (string) ((int) ($hostInfo['cpu_threads'] ?? 0)) . ' ' . __('threads', 'yablonsky-performance'));
            self::renderHostMetric(__('Memory', 'yablonsky-performance'), (string) ((int) ($hostInfo['memory_available_mb'] ?? 0)) . ' / ' . (string) ((int) ($hostInfo['memory_total_mb'] ?? 0)) . ' MB ' . __('available', 'yablonsky-performance'));
            self::renderHostMetric(__('Load', 'yablonsky-performance'), (string) ($hostInfo['load_1'] ?? '—') . ' / ' . (string) ($hostInfo['load_5'] ?? '—') . ' / ' . (string) ($hostInfo['load_15'] ?? '—'));
            self::renderHostMetric(__('Nginx cache', 'yablonsky-performance'), sprintf('%d path · %d global use', (int) ($nginx['fastcgi_cache_paths'] ?? 0), (int) ($nginx['fastcgi_cache_uses'] ?? 0)));
            self::renderHostMetric(__('PHP-FPM', 'yablonsky-performance'), !empty($fpm['site_pool_name']) ? sprintf('%s · %d max children', (string) $fpm['site_pool_name'], (int) ($fpm['site_pool_max_children'] ?? 0)) : sprintf('%d pools · %d total max children', (int) ($fpm['pool_count'] ?? 0), (int) ($fpm['max_children_total'] ?? 0)));
            self::renderHostMetric(__('Redis', 'yablonsky-performance'), !empty($redis['reachable']) ? sprintf('%.1f%% memory · %s', $redisPct, (string) ($redis['maxmemory_policy'] ?? 'unknown')) : __('Not confirmed', 'yablonsky-performance'));
            if ($site !== array()) {
                self::renderHostMetric(__('Site cache proof', 'yablonsky-performance'), (string) ($site['probe2_cache_value'] ?? __('No HIT/MISS header', 'yablonsky-performance')));
            }
            echo '</div>';
            if ($generatedAt > 0) {
                /* translators: %s is the local date and time of the imported Host Intelligence snapshot. */
                echo '<p class="yperf-env-checked-at">' . esc_html(sprintf(__('Snapshot: %s', 'yablonsky-performance'), wp_date('Y-m-d H:i:s', $generatedAt))) . '</p>';
            }
            if ($site === array() && (int) ($host['schema'] ?? 1) < 2) {
                echo '<div class="yperf-callout yperf-callout-attention"><strong>' . esc_html__('Site-specific proof not captured yet', 'yablonsky-performance') . '</strong><span>' . esc_html__('This snapshot proves only part of the native stack. Run the Host Inspector bundled with the current YPerf release to capture site-specific Nginx ownership, Redis isolation/headroom and exact PHP-FPM worker-memory evidence.', 'yablonsky-performance') . '</span></div>';
            }
            echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post"><input type="hidden" name="action" value="yperf_clear_host_snapshot">';
            wp_nonce_field('yperf_clear_host_snapshot');
            echo '<button type="submit" class="button">' . esc_html__('Clear snapshot', 'yablonsky-performance') . '</button></form>';
        } else {
            echo '<div class="yperf-intelligence-note"><strong>' . esc_html__('No host snapshot is connected.', 'yablonsky-performance') . '</strong><span>' . esc_html__('Normal YPerf operation does not require it. Create one only when you want exact Nginx cache-zone, PHP-FPM pool, host-memory or host-level Redis evidence.', 'yablonsky-performance') . '</span></div>';
        }
        echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="yperf-host-import"><input type="hidden" name="action" value="yperf_import_host_snapshot">';
        wp_nonce_field('yperf_import_host_snapshot');
        echo '<label><span>' . esc_html__('Host snapshot JSON', 'yablonsky-performance') . '</span><input type="file" id="yperf-host-snapshot-file" accept="application/json,.json"><textarea id="yperf-host-snapshot-json" name="yperf_host_snapshot_json" rows="4" placeholder="{ ... }"></textarea></label><div class="yperf-runtime-actions"><button type="submit" class="button button-primary">' . esc_html__('Import snapshot', 'yablonsky-performance') . '</button><span class="yperf-inline-note">' . esc_html__('File selection is read in your browser and submitted as JSON; no arbitrary server file path is accepted.', 'yablonsky-performance') . '</span></div></form></div>';
    }

    /** @param array<string,mixed> $evidence */
    private static function renderEvidenceReconciliation(array $evidence): void
    {
        $count = (int) ($evidence['conflict_count'] ?? 0);
        $differenceLabel = $count === 0 ? __('Consistent', 'yablonsky-performance') : (($count === 1 ? '1 ' . __('difference', 'yablonsky-performance') : (string) $count . ' ' . __('differences', 'yablonsky-performance')));
        echo '<div class="yperf-card"><div class="yperf-card-head"><div><h2>' . esc_html__('Evidence reconciliation', 'yablonsky-performance') . '</h2><p>' . esc_html__('Separates live state from older stored evidence instead of silently pretending every snapshot was taken at the same time.', 'yablonsky-performance') . '</p></div><span class="yperf-state ' . ($count > 0 ? 'yperf-state-attention' : 'yperf-state-healthy') . '">' . esc_html($differenceLabel) . '</span></div>';
        $sources = is_array($evidence['sources'] ?? null) ? $evidence['sources'] : array();
        echo '<div class="yperf-env-summary">';
        foreach (array('wordpress_live' => __('WordPress live', 'yablonsky-performance'), 'environment_check' => __('Environment', 'yablonsky-performance'), 'host_snapshot' => __('Host', 'yablonsky-performance'), 'runtime_acceptance' => __('Runtime', 'yablonsky-performance'), 'benchmark' => __('Benchmark', 'yablonsky-performance')) as $id => $label) {
            $row = is_array($sources[$id] ?? null) ? $sources[$id] : array();
            $fresh = (string) ($row['freshness'] ?? 'unknown');
            echo '<div class="yperf-env-count"><strong>' . esc_html(strtoupper($fresh)) . '</strong><span>' . esc_html($label) . '</span></div>';
        }
        echo '</div>';
        if ($count > 0) {
            echo '<div class="yperf-env-list">';
            foreach ((array) ($evidence['conflicts'] ?? array()) as $conflict) {
                if (!is_array($conflict)) {
                    continue;
                }
                echo '<div class="yperf-env-row"><span class="yperf-env-badge yperf-env-badge-attention">DIFF</span><div><strong>' . esc_html((string) ($conflict['id'] ?? 'difference')) . '</strong><span>' . esc_html((string) ($conflict['message'] ?? '')) . '</span></div><code>' . esc_html((string) ($conflict['current_source'] ?? 'live')) . '</code></div>';
            }
            echo '</div>';
        }
        echo '</div>';
    }

    /** @param array<string,mixed> $ownership */
    private static function renderDropinOwnership(array $ownership): void
    {
        echo '<div class="yperf-card"><div class="yperf-card-head"><div><h2>' . esc_html__('Drop-in ownership', 'yablonsky-performance') . '</h2><p>' . esc_html__('Attributes WordPress cache drop-ins by safe signatures and hashes without exporting file contents or server paths.', 'yablonsky-performance') . '</p></div><span class="yperf-state yperf-state-neutral">' . esc_html__('Live filesystem', 'yablonsky-performance') . '</span></div><div class="yperf-provider-table">';
        echo '<div class="yperf-provider-head"><span>' . esc_html__('Artifact', 'yablonsky-performance') . '</span><span>' . esc_html__('Owner', 'yablonsky-performance') . '</span><span>' . esc_html__('Runtime', 'yablonsky-performance') . '</span></div>';
        foreach (array('advanced_cache' => 'advanced-cache.php', 'object_cache' => 'object-cache.php') as $id => $label) {
            $row = is_array($ownership[$id] ?? null) ? $ownership[$id] : array();
            $present = !empty($row['present']);
            $owner = $present ? (string) ($row['owner'] ?? 'unknown') : __('Absent', 'yablonsky-performance');
            $runtime = $id === 'object_cache' ? (!empty($ownership['runtime']['external_object_cache']) ? __('External active', 'yablonsky-performance') : __('Not external', 'yablonsky-performance')) : (!empty($ownership['runtime']['wp_cache_constant']) ? __('WP_CACHE on', 'yablonsky-performance') : __('WP_CACHE off', 'yablonsky-performance'));
            echo '<div class="yperf-provider-row"><strong>' . esc_html($label) . '</strong><span class="yperf-provider-state ' . ($present ? 'is-on' : 'is-off') . '">' . esc_html($owner) . '</span><code>' . esc_html($runtime) . '</code></div>';
        }
        echo '</div></div>';
    }

    /** @param array<string,mixed> $evidence @param array<string,mixed> $proof @param array<string,mixed> $portablePageCache @param array<string,mixed> $pageCacheCampaign */
    private static function renderEvidenceInline(array $evidence, array $proof, array $portablePageCache = array(), array $pageCacheCampaign = array()): void
    {
        $nativePage = is_array($proof['page_cache'] ?? null) ? $proof['page_cache'] : array();
        $redis = is_array($proof['object_cache']['redis'] ?? null) ? $proof['object_cache']['redis'] : array();
        $redisState = sanitize_key((string) ($redis['state'] ?? 'unknown'));
        $redisLabel = $redisState === 'host_snapshot_required' ? __('host proof required', 'yablonsky-performance') : str_replace('_', ' ', $redisState);
        $portableActive = !empty($portablePageCache['enabled']) && (string) ($portablePageCache['state'] ?? '') === 'active';
        $campaignProven = (string) ($pageCacheCampaign['status'] ?? '') === 'proven';
        $artifactVersion = sanitize_text_field((string) ($portablePageCache['dropin_version'] ?? ''));
        $artifactState = sanitize_key((string) ($portablePageCache['dropin_artifact_state'] ?? 'unknown'));
        $portableLabel = ($portableActive && $campaignProven) ? __('proven', 'yablonsky-performance') : __('not proven', 'yablonsky-performance');
        if ($artifactVersion !== '') {
            $portableLabel .= ' · ' . $artifactVersion . ' · ' . str_replace('_', ' ', $artifactState);
        }
        echo '<div class="yperf-intelligence-note"><strong>' . esc_html__('Evidence-aware analysis', 'yablonsky-performance') . '</strong><span>';
        echo esc_html(sprintf(
            /* translators: 1: evidence difference count, 2: portable page-cache proof, 3: native Nginx proof, 4: Redis host proof. */
            __('Differences: %1$d · Portable Page Cache: %2$s · Native cache proof: %3$s · Redis host proof: %4$s', 'yablonsky-performance'),
            (int) ($evidence['conflict_count'] ?? 0),
            $portableLabel,
            str_replace('_', ' ', (string) ($nativePage['state'] ?? 'not_proven')),
            $redisLabel
        ));
        echo '</span></div>';
    }


    private static function renderHostMetric(string $label, string $value): void
    {
        echo '<div><span>' . esc_html($label) . '</span><strong>' . esc_html($value) . '</strong></div>';
    }

    /** @param array<string,mixed> $report @param array<string,mixed> $campaignState @param array<string,mixed> $nativeAdoption */
    private static function renderPageCacheCampaign(array $report, array $campaignState, array $nativeAdoption): void
    {
        $page = PageCacheExecutor::status();
        $pageEnabled = !empty($page['enabled']);
        $active = $pageEnabled && (string) ($page['state'] ?? '') === 'active';
        $refreshRequired = $pageEnabled && !empty($page['dropin_version_refreshable']) && empty($page['dropin_version_compatible']);
        $reportCurrent = $report !== array() && (string) ($report['version'] ?? '') === YPERF_VERSION && (string) ($report['status'] ?? '') === 'proven';
        $nativeAdopted = !empty($nativeAdoption['adopted']) || NativePageCacheAdoption::last() !== array();
        $campaignCurrent = (string) ($campaignState['version'] ?? '') === YPERF_VERSION;
        $campaignRunning = $campaignCurrent && (string) ($campaignState['status'] ?? '') === 'running';
        $campaignError = $campaignCurrent && (string) ($campaignState['status'] ?? '') === 'error';
        $progress = $campaignCurrent ? max(0, min(100, (int) ($campaignState['progress'] ?? 0))) : 0;
        $stateClass = $reportCurrent && $active ? 'yperf-state-healthy' : ($campaignRunning || $campaignError ? 'yperf-state-attention' : 'yperf-state-neutral');
        $stateLabel = $reportCurrent && $active
            ? __('PROVEN', 'yablonsky-performance')
            : ($campaignRunning ? __('IN PROGRESS', 'yablonsky-performance') : ($campaignError ? __('REVIEW', 'yablonsky-performance') : __('READY', 'yablonsky-performance')));
        $stability = sanitize_key((string) ($report['stability'] ?? 'not_measured'));
        $stabilityMethod = sanitize_key((string) ($report['stability_method'] ?? 'legacy_peak_to_peak_v1'));
        $legacyStability = $stabilityMethod !== 'hot_hit_mad_v2';
        $stabilityLabel = $legacyStability && $reportCurrent
            ? __('RE-PROVE OPTIONAL', 'yablonsky-performance')
            : ($stability === 'stable' ? __('STABLE', 'yablonsky-performance') : ($stability === 'variable' ? __('VARIABLE', 'yablonsky-performance') : ($stability === 'noisy' ? __('NOISY', 'yablonsky-performance') : __('NOT MEASURED', 'yablonsky-performance'))));
        $median = max(0, (int) ($report['cached_hit_median_ms'] ?? 0));
        $before = max(0, (int) ($report['preflight_home_ms'] ?? 0));
        $after = max(0, (int) ($report['post_home_ms'] ?? 0));
        $spread = isset($report['cached_hit_spread_pct']) ? (float) $report['cached_hit_spread_pct'] : 0.0;
        $typicalVariation = isset($report['cached_hit_typical_variation_pct']) ? (float) $report['cached_hit_typical_variation_pct'] : 0.0;
        $sampleCount = max(0, (int) ($report['stability_sample_count'] ?? count((array) ($report['cached_hit_samples_ms'] ?? array()))));

        echo '<div class="yperf-card yperf-page-cache-campaign-card">';
        echo '<div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('PUBLIC DELIVERY STABILITY', 'yablonsky-performance') . '</span><h2>' . esc_html__('Stabilize repeat public HTML delivery', 'yablonsky-performance') . '</h2><p>' . esc_html__('One bounded campaign activates only the YPerf-owned portable Page Cache, proves MISS → HIT, runs Runtime Acceptance and measures hot-HIT consistency. Nginx, Redis, PHP-FPM and Cloudflare stay untouched.', 'yablonsky-performance') . '</p></div><span class="yperf-state ' . esc_attr($stateClass) . '">' . esc_html($stateLabel) . '</span></div>';

        echo '<div class="yperf-page-cache-metrics">';
        self::renderMiniMetric(__('Page Cache', 'yablonsky-performance'), $active ? __('ACTIVE', 'yablonsky-performance') : ($refreshRequired ? __('REVIEW', 'yablonsky-performance') : __('OFF', 'yablonsky-performance')));
        self::renderMiniMetric(__('Preflight homepage', 'yablonsky-performance'), $before > 0 ? number_format_i18n($before) . ' ms' : '—');
        self::renderMiniMetric(__('Cached HIT median', 'yablonsky-performance'), $median > 0 ? number_format_i18n($median) . ' ms' : '—');
        self::renderMiniMetric(__('Repeat-HIT stability', 'yablonsky-performance'), $stabilityLabel);
        echo '</div>';

        $artifactVersion = sanitize_text_field((string) ($page['dropin_version'] ?? ''));
        if ($pageEnabled && $artifactVersion !== '') {
            $artifactState = !empty($page['dropin_version_current'])
                ? __('current artifact', 'yablonsky-performance')
                : (!empty($page['dropin_version_compatible']) ? __('compatible previous artifact', 'yablonsky-performance') : (!empty($page['dropin_version_refreshable']) ? __('REVIEW', 'yablonsky-performance') : __('artifact review required', 'yablonsky-performance')));
            echo '<div class="yperf-page-cache-artifact-truth"><strong>' . esc_html__('Executor artifact', 'yablonsky-performance') . '</strong><span>' . esc_html('v' . $artifactVersion . ' · ' . $artifactState) . '</span></div>';
            if (empty($page['dropin_version_current']) && (!empty($page['dropin_version_compatible']) || !empty($page['dropin_version_refreshable'])) && !empty($page['dropin_hash_matches'])) {
                echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="yperf-page-cache-artifact-refresh"><input type="hidden" name="action" value="yperf_refresh_page_cache_artifact">';
                wp_nonce_field('yperf_refresh_page_cache_artifact');
                echo '<button type="submit" class="button">' . esc_html(sprintf(
                    /* translators: %s: current YPerf version. */
                    __('Refresh executor to v%s & re-prove', 'yablonsky-performance'),
                    YPERF_VERSION
                )) . '</button><span>' . esc_html__('Explicit bounded write: exact hash → current artifact → MISS/HIT proof → Runtime Acceptance → automatic rollback on failure.', 'yablonsky-performance') . '</span></form>';
            }
        }

        if ($reportCurrent) {
            if (!$legacyStability) {
                echo '<div class="yperf-page-cache-proof-summary"><strong>' . esc_html__('Latest proven campaign', 'yablonsky-performance') . '</strong><span>' . esc_html(sprintf(
                    /* translators: 1: post-activation homepage ms, 2: typical cached HIT variation percentage, 3: full cached HIT range percentage, 4: sample count. */
                    __('Final homepage: %1$d ms · typical HIT variation: ±%2$.1f%% · full range: %3$.1f%% · %4$d samples', 'yablonsky-performance'),
                    $after,
                    $typicalVariation,
                    $spread,
                    $sampleCount
                )) . '</span></div>';
            } else {
                echo '<div class="yperf-page-cache-proof-summary"><strong>' . esc_html__('Page Cache proof carried forward', 'yablonsky-performance') . '</strong><span>' . esc_html__('Execution remains proven. Re-prove only if you want the new seven-sample hot-HIT consistency metric.', 'yablonsky-performance') . '</span></div>';
            }
        }

        if ($refreshRequired) {
            // The explicit hash-guarded executor refresh action above is the only
            // allowed write while a pre-boundary YPerf artifact is still installed.
        } elseif ($nativeAdopted && !$active) {
            echo '<div class="yperf-callout yperf-callout-safe"><strong>' . esc_html__('Native page-cache authority already exists.', 'yablonsky-performance') . '</strong><span>' . esc_html__('YPerf will not activate its portable Page Cache while an adopted native executor owns page-cache authority. Review or release that adoption first if you intentionally want to change executors.', 'yablonsky-performance') . '</span></div>';
        } else {
            echo '<form data-yperf-page-cache-campaign-form class="yperf-page-cache-campaign-actions"><button type="submit" class="button button-primary button-hero" data-yperf-page-cache-campaign-run' . ($campaignRunning ? ' disabled' : '') . '>' . esc_html($active ? __('Re-prove delivery stability', 'yablonsky-performance') : __('Stabilize public delivery', 'yablonsky-performance')) . '</button><span>' . esc_html__('Explicit local write boundary. A new Page Cache activation is rolled back automatically if execution proof or Runtime Acceptance fails.', 'yablonsky-performance') . '</span></form>';
        }

        echo '<div class="yperf-page-cache-campaign-progress' . ($campaignRunning ? ' is-running' : ($campaignError ? ' is-error' : '')) . '" data-yperf-page-cache-campaign-progress' . (!$campaignRunning && !$campaignError ? ' hidden' : '') . '>';
        echo '<div class="yperf-progress-top"><strong data-yperf-page-cache-campaign-label>' . esc_html((string) ($campaignState['message'] ?? __('Waiting for your action.', 'yablonsky-performance'))) . '</strong><span data-yperf-page-cache-campaign-percent>' . esc_html((string) $progress) . '%</span></div><div class="yperf-progress is-animated" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr((string) $progress) . '"><span data-yperf-page-cache-campaign-bar style="width:' . esc_attr((string) $progress) . '%"></span></div>';
        echo '<div class="yperf-page-cache-campaign-phases">';
        foreach (array(
            __('1. Fresh preflight', 'yablonsky-performance'),
            __('2. Activate + prove', 'yablonsky-performance'),
            __('3. Hot HIT stability', 'yablonsky-performance'),
            __('4. Final acceptance', 'yablonsky-performance'),
        ) as $index => $label) {
            echo '<div data-yperf-page-cache-campaign-phase="' . esc_attr((string) ($index + 1)) . '"><span>○</span><strong>' . esc_html($label) . '</strong></div>';
        }
        echo '</div><div class="yperf-page-cache-campaign-events" data-yperf-page-cache-campaign-events></div></div>';

        echo '<div class="yperf-page-cache-boundary"><strong>' . esc_html__('What this campaign does not change', 'yablonsky-performance') . '</strong><span>' . esc_html__('Theme contrast, accessibility styling and third-party scripts such as chat widgets remain outside this write boundary. YPerf may diagnose those costs later, but this campaign does not rewrite external code just to improve a benchmark score.', 'yablonsky-performance') . '</span></div>';
        echo '<p class="yperf-inline-note">' . esc_html__('The stability metric is local same-origin cache evidence, not a Google PageSpeed score. Use repeated PageSpeed runs later as an external field check after YPerf has proved deterministic cache execution.', 'yablonsky-performance') . '</p>';
        echo '</div>';
    }

    /** @param array<string,mixed> $adoption */
    private static function renderNativePageCacheAdoption(array $adoption): void
    {
        $state = sanitize_key((string) ($adoption['state'] ?? 'proof_required'));
        $adopted = !empty($adoption['adopted']);
        $eligible = !empty($adoption['eligible']);
        $stateClass = $adopted ? 'yperf-state-healthy' : ($eligible ? 'yperf-state-attention' : 'yperf-state-neutral');
        $ops = is_array($adoption['operations'] ?? null) ? $adoption['operations'] : array();
        $record = is_array($adoption['record'] ?? null) ? $adoption['record'] : array();
        $age = (int) ($adoption['proof_age_seconds'] ?? 0);

        echo '<div class="yperf-card yperf-native-adoption-r1"><div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('NATIVE PAGE CACHE ADOPTION', 'yablonsky-performance') . '</span><h2>' . esc_html__('Recognize the existing Nginx FastCGI cache', 'yablonsky-performance') . '</h2><p>' . esc_html__('YPerf does not build a second cache. The adoption stage recognizes a fresh, proven site-specific Nginx cache as the authoritative page-cache executor without editing Nginx or restarting any service.', 'yablonsky-performance') . '</p></div><span class="yperf-state ' . esc_attr($stateClass) . '">' . esc_html((string) ($adoption['label'] ?? __('PROOF REQUIRED', 'yablonsky-performance'))) . '</span></div>';

        echo '<div class="yperf-native-adoption-steps">';
        $steps = array(
            array(__('1. Detect', 'yablonsky-performance'), (string) ($adoption['target_host'] ?? '') !== '' ? __('DONE', 'yablonsky-performance') : __('WAITING', 'yablonsky-performance')),
            array(__('2. Prove HIT', 'yablonsky-performance'), in_array((string) ($adoption['proof_state'] ?? ''), array('miss_then_hit_confirmed', 'hit_confirmed'), true) ? __('DONE', 'yablonsky-performance') : __('WAITING', 'yablonsky-performance')),
            array(__('3. Adopt authority', 'yablonsky-performance'), $adopted ? __('DONE', 'yablonsky-performance') : ($eligible ? __('READY', 'yablonsky-performance') : __('WAITING', 'yablonsky-performance'))),
            array(__('4. Operate', 'yablonsky-performance'), $adopted ? __('PARTIAL', 'yablonsky-performance') : __('LOCKED', 'yablonsky-performance')),
        );
        foreach ($steps as $step) {
            echo '<div><span>' . esc_html($step[0]) . '</span><strong>' . esc_html($step[1]) . '</strong></div>';
        }
        echo '</div>';

        echo '<p class="yperf-native-adoption-reason">' . esc_html((string) ($adoption['reason'] ?? '')) . '</p>';
        echo '<div class="yperf-native-adoption-grid">';
        self::renderDefinitionRow(__('Target host', 'yablonsky-performance'), (string) ($adoption['target_host'] ?? '—'));
        self::renderDefinitionRow(__('Execution proof', 'yablonsky-performance'), strtoupper((string) ($adoption['proof_state'] ?? 'not_proven')));
        self::renderDefinitionRow(__('Proof age', 'yablonsky-performance'), $age > 0 ? self::formatAge($age) : __('Unknown', 'yablonsky-performance'));
        self::renderDefinitionRow(__('Cache zone', 'yablonsky-performance'), (string) ($adoption['cache_zone'] ?? '') !== '' ? (string) $adoption['cache_zone'] : __('Not exposed', 'yablonsky-performance'));
        self::renderDefinitionRow(__('Prewarm', 'yablonsky-performance'), strtoupper((string) ($ops['prewarm'] ?? 'blocked')));
        self::renderDefinitionRow(__('Native purge', 'yablonsky-performance'), strtoupper(str_replace('_', ' ', (string) ($ops['native_purge'] ?? 'not_proven'))));
        echo '</div>';

        echo '<div class="yperf-native-adoption-actions">';
        if ($eligible && !$adopted) {
            echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post"><input type="hidden" name="action" value="yperf_adopt_native_page_cache">';
            wp_nonce_field('yperf_adopt_native_page_cache');
            echo '<button type="submit" class="button button-primary">' . esc_html__('Adopt proven Nginx page cache', 'yablonsky-performance') . '</button></form>';
        }
        if ($adopted || $record !== array()) {
            echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post"><input type="hidden" name="action" value="yperf_release_native_page_cache">';
            wp_nonce_field('yperf_release_native_page_cache');
            echo '<button type="submit" class="button">' . esc_html__('Release local adoption record', 'yablonsky-performance') . '</button></form>';
        }
        echo '<button type="button" class="button" data-yperf-open-tab="control">' . esc_html__('Open Prewarm / Purge', 'yablonsky-performance') . '</button>';
        echo '<span>' . esc_html__('Adoption is a local authority record only. Nginx config, cache files, provider settings and service state remain untouched.', 'yablonsky-performance') . '</span></div></div>';
    }


    /** @param array<string,mixed> $operations */
    private static function renderNativePageCacheOperations(array $operations): void
    {
        $state = sanitize_key((string) ($operations['state'] ?? 'adoption_required'));
        $progress = max(0, min(100, (int) ($operations['progress_percent'] ?? 0)));
        $ops = is_array($operations['operations'] ?? null) ? $operations['operations'] : array();
        $gates = is_array($operations['gates'] ?? null) ? $operations['gates'] : array();
        $stateClass = $state === 'operation_review_ready' ? 'yperf-state-healthy' : (strpos($state, 'blocked') !== false ? 'yperf-state-attention' : 'yperf-state-neutral');

        echo '<div class="yperf-card yperf-native-operations-r2">';
        echo '<div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('NATIVE PAGE CACHE OPERATIONS R3', 'yablonsky-performance') . '</span><h2>' . esc_html__('Prove cache-zone ownership before native purge', 'yablonsky-performance') . '</h2><p>' . esc_html__('R3 separates adoption from operation authority and freezes the exact operation fingerprint. YPerf proves whether the Nginx cache zone belongs only to this server block before any host-side purge path can be reviewed.', 'yablonsky-performance') . '</p></div><span class="yperf-state ' . esc_attr($stateClass) . '">' . esc_html((string) ($operations['label'] ?? __('WAITING', 'yablonsky-performance'))) . '</span></div>';

        echo '<div class="yperf-native-ops-progress"><div><strong>' . esc_html__('Operation readiness', 'yablonsky-performance') . '</strong><span>' . esc_html((string) $progress) . '%</span></div><div class="yperf-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr((string) $progress) . '"><span style="width:' . esc_attr((string) $progress) . '%"></span></div></div>';
        echo '<p class="yperf-native-adoption-reason">' . esc_html((string) ($operations['reason'] ?? '')) . '</p>';

        echo '<div class="yperf-native-adoption-grid">';
        self::renderDefinitionRow(__('Cache zone', 'yablonsky-performance'), (string) ($operations['cache_zone'] ?? '') !== '' ? (string) $operations['cache_zone'] : __('Not exposed', 'yablonsky-performance'));
        self::renderDefinitionRow(__('Zone ownership', 'yablonsky-performance'), strtoupper(str_replace('_', ' ', (string) ($operations['zone_ownership'] ?? 'unknown'))));
        self::renderDefinitionRow(__('Owner server blocks', 'yablonsky-performance'), (string) ((int) ($operations['zone_owner_blocks'] ?? 0)));
        self::renderDefinitionRow(__('Owner set fingerprint', 'yablonsky-performance'), (string) ($operations['zone_owner_signature'] ?? '') !== '' ? (string) $operations['zone_owner_signature'] : __('Not proven', 'yablonsky-performance'));
        self::renderDefinitionRow(__('Target server block owns zone', 'yablonsky-performance'), !empty($operations['target_is_zone_owner']) ? __('YES', 'yablonsky-performance') : __('NO / UNKNOWN', 'yablonsky-performance'));
        self::renderDefinitionRow(__('Cache path fingerprint', 'yablonsky-performance'), (string) ($operations['cache_path_fingerprint'] ?? '') !== '' ? (string) $operations['cache_path_fingerprint'] : __('Not proven', 'yablonsky-performance'));
        self::renderDefinitionRow(__('Cache key host scoped', 'yablonsky-performance'), !empty($operations['cache_key_host_scoped']) ? __('YES', 'yablonsky-performance') : __('NO / UNKNOWN', 'yablonsky-performance'));
        self::renderDefinitionRow(__('Prewarm', 'yablonsky-performance'), strtoupper(str_replace('_', ' ', (string) ($ops['prewarm'] ?? 'blocked'))));
        self::renderDefinitionRow(__('Native purge', 'yablonsky-performance'), strtoupper(str_replace('_', ' ', (string) ($ops['native_purge'] ?? 'blocked'))));
        echo '</div>';

        echo '<div class="yperf-native-op-gates">';
        foreach (array(
            'adoption_current' => __('Adoption current', 'yablonsky-performance'),
            'runtime_clean' => __('Runtime clean', 'yablonsky-performance'),
            'zone_identified' => __('Zone identified', 'yablonsky-performance'),
            'zone_ownership_proven' => __('Ownership proven', 'yablonsky-performance'),
            'zone_exclusive' => __('Zone exclusive', 'yablonsky-performance'),
            'purge_capability_visible' => __('Purge capability visible', 'yablonsky-performance'),
        ) as $key => $label) {
            $passed = !empty($gates[$key]);
            echo '<div class="' . ($passed ? 'is-pass' : 'is-wait') . '"><span>' . esc_html($passed ? '✓' : '○') . '</span><strong>' . esc_html($label) . '</strong></div>';
        }
        echo '</div>';

        echo '<p class="yperf-benchmark-safety"><strong>' . esc_html__('Host writes remain locked.', 'yablonsky-performance') . '</strong> ' . esc_html__('Even at 100% readiness, this build does not delete Nginx cache files, edit configuration or restart services. The next host bridge must require explicit approval, exact before-state, read-back, Runtime Acceptance and rollback.', 'yablonsky-performance') . '</p>';
        echo '</div>';
    }

    /** @param array<string,mixed> $adoption */
    private static function renderObjectCacheAdoption(array $adoption): void
    {
        $state = sanitize_key((string) ($adoption['state'] ?? 'redis_evidence_required'));
        $ready = $state === 'reservation_ready' || $state === 'reserved_read_only' || $state === 'already_active';
        $redis = is_array($adoption['redis'] ?? null) ? $adoption['redis'] : array();
        $record = is_array($adoption['record'] ?? null) ? $adoption['record'] : array();
        $gates = is_array($adoption['gates'] ?? null) ? $adoption['gates'] : array();
        $progress = max(0, min(100, (int) ($adoption['progress_percent'] ?? 0)));

        echo '<div class="yperf-card yperf-native-op-card"><div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('PERSISTENT OBJECT CACHE · R1', 'yablonsky-performance') . '</span><h2>' . esc_html__('Redis isolation & adoption readiness', 'yablonsky-performance') . '</h2><p>' . esc_html((string) ($adoption['reason'] ?? '')) . '</p></div><span class="yperf-state ' . ($ready ? 'yperf-state-healthy' : 'yperf-state-attention') . '">' . esc_html((string) ($adoption['label'] ?? __('REVIEW', 'yablonsky-performance'))) . '</span></div>';
        echo '<div class="yperf-native-op-progress"><div><strong>' . esc_html__('Adoption readiness', 'yablonsky-performance') . '</strong><span>' . esc_html($progress . '%') . '</span></div><div class="yperf-progress-track"><span style="width:' . esc_attr((string) $progress) . '%"></span></div></div>';
        echo '<div class="yperf-def-list">';
        $evidenceSource = sanitize_key((string) ($adoption['evidence_source'] ?? 'unknown'));
        $evidenceLabel = $evidenceSource === 'local_php_redis' ? __('Local PhpRedis · read-only', 'yablonsky-performance') : ($evidenceSource === 'host_snapshot' ? __('Host Inspector snapshot', 'yablonsky-performance') : __('Unavailable / stale', 'yablonsky-performance'));
        self::renderDefinitionRow(__('Evidence source', 'yablonsky-performance'), $evidenceLabel);
        self::renderDefinitionRow(__('Redis memory', 'yablonsky-performance'), sprintf('%.1f%% · %s', (float) ($redis['memory_usage_pct'] ?? 0), (string) ($redis['maxmemory_policy'] ?? 'unknown')));
        /* translators: %d: Redis logical database number. */
        $availableDatabase = sprintf(__('DB %d available', 'yablonsky-performance'), (int) ($redis['first_empty_database'] ?? -1));
        self::renderDefinitionRow(__('Logical DB isolation', 'yablonsky-performance'), (int) ($redis['first_empty_database'] ?? -1) >= 0 ? $availableDatabase : __('No empty DB proven', 'yablonsky-performance'));
        self::renderDefinitionRow(__('Current Redis keys', 'yablonsky-performance'), (string) ((int) ($redis['total_keys'] ?? 0)));
        /* translators: %d: Redis logical database number. */
        $reservedDatabase = sprintf(__('DB %d · local record only', 'yablonsky-performance'), (int) ($record['database'] ?? -1));
        self::renderDefinitionRow(__('Reserved target', 'yablonsky-performance'), $record !== array() ? $reservedDatabase : __('None', 'yablonsky-performance'));
        echo '</div>';
        echo '<div class="yperf-native-op-gates">';
        foreach (array('redis_reachable' => __('Redis reachable', 'yablonsky-performance'), 'memory_bounded' => __('Memory bounded', 'yablonsky-performance'), 'eviction_policy_safe' => __('Eviction policy safe', 'yablonsky-performance'), 'memory_headroom' => __('Memory headroom', 'yablonsky-performance'), 'isolated_database_available' => __('Isolated DB available', 'yablonsky-performance'), 'object_cache_dropin_absent' => __('No competing drop-in', 'yablonsky-performance'), 'runtime_clean' => __('Runtime clean', 'yablonsky-performance'), 'redis_benchmark_current' => __('Redis benchmark current', 'yablonsky-performance')) as $key => $label) {
            $passed = !empty($gates[$key]);
            echo '<div class="' . ($passed ? 'is-pass' : 'is-wait') . '"><span>' . esc_html($passed ? '✓' : '○') . '</span><strong>' . esc_html($label) . '</strong></div>';
        }
        echo '</div>';
        if (!empty($adoption['eligible_to_reserve'])) {
            echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="yperf-runtime-actions"><input type="hidden" name="action" value="yperf_reserve_object_cache">';
            wp_nonce_field('yperf_reserve_object_cache');
            echo '<button type="submit" class="button button-primary">' . esc_html__('Reserve isolated Redis target', 'yablonsky-performance') . '</button><span>' . esc_html__('Stores only a local reservation. No Redis key or object-cache.php file is created.', 'yablonsky-performance') . '</span></form>';
        } elseif (!empty($adoption['reserved'])) {
            echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="yperf-runtime-actions"><input type="hidden" name="action" value="yperf_release_object_cache_reservation">';
            wp_nonce_field('yperf_release_object_cache_reservation');
            echo '<button type="submit" class="button">' . esc_html__('Release Redis reservation', 'yablonsky-performance') . '</button><span>' . esc_html__('Releases only YPerf local state; Redis remains untouched.', 'yablonsky-performance') . '</span></form>';
        }
        echo '<p class="yperf-benchmark-safety"><strong>' . esc_html__('R1 is evidence only.', 'yablonsky-performance') . '</strong> ' . esc_html__('R1 proves headroom and isolation. R2 freezes the target contract and R3 owns the separate explicit apply, Redis proof, Runtime Acceptance and rollback boundary.', 'yablonsky-performance') . '</p></div>';
    }

    /** @param array<string,mixed> $contract */
    private static function renderObjectCacheOperationContract(array $contract): void
    {
        $state = sanitize_key((string) ($contract['state'] ?? 'reservation_required'));
        $ready = $state === 'operation_contract_ready';
        $progress = max(0, min(100, (int) ($contract['progress_percent'] ?? 0)));
        $details = is_array($contract['contract'] ?? null) ? $contract['contract'] : array();
        $gates = is_array($contract['gates'] ?? null) ? $contract['gates'] : array();
        $fingerprint = (string) ($details['fingerprint'] ?? '');

        echo '<div class="yperf-card yperf-native-op-card"><div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('PERSISTENT OBJECT CACHE · R2', 'yablonsky-performance') . '</span><h2>' . esc_html__('Freeze the Redis activation & rollback contract', 'yablonsky-performance') . '</h2><p>' . esc_html((string) ($contract['reason'] ?? '')) . '</p></div><span class="yperf-state ' . ($ready ? 'yperf-state-healthy' : 'yperf-state-attention') . '">' . esc_html((string) ($contract['label'] ?? __('REVIEW', 'yablonsky-performance'))) . '</span></div>';
        echo '<div class="yperf-native-op-progress"><div><strong>' . esc_html__('Write-boundary readiness', 'yablonsky-performance') . '</strong><span>' . esc_html($progress . '%') . '</span></div><div class="yperf-progress-track"><span style="width:' . esc_attr((string) $progress) . '%"></span></div></div>';
        echo '<div class="yperf-def-list">';
        /* translators: %d: Redis logical database number. */
        $redisTarget = sprintf(__('127.0.0.1:6379 · DB %d', 'yablonsky-performance'), (int) ($details['database'] ?? -1));
        self::renderDefinitionRow(__('Redis target', 'yablonsky-performance'), (int) ($details['database'] ?? -1) >= 0 ? $redisTarget : __('Not frozen', 'yablonsky-performance'));
        self::renderDefinitionRow(__('Site key prefix', 'yablonsky-performance'), (string) ($details['key_prefix'] ?? __('Not frozen', 'yablonsky-performance')));
        self::renderDefinitionRow(__('Drop-in target', 'yablonsky-performance'), (string) ($details['dropin_target'] ?? 'wp-content/object-cache.php'));
        self::renderDefinitionRow(__('Contract fingerprint', 'yablonsky-performance'), $fingerprint !== '' ? substr($fingerprint, 0, 16) . '…' : __('Not frozen', 'yablonsky-performance'));
        echo '</div><div class="yperf-native-op-gates">';
        foreach (array(
            'reservation_current' => __('Reservation current', 'yablonsky-performance'),
            'host_proof_fresh' => __('Redis evidence fresh', 'yablonsky-performance'),
            'redis_reachable' => __('Redis reachable', 'yablonsky-performance'),
            'reserved_database_still_observed_empty' => __('Reserved DB still empty', 'yablonsky-performance'),
            'object_cache_dropin_absent' => __('No competing drop-in', 'yablonsky-performance'),
            'external_object_cache_inactive' => __('No external object cache active', 'yablonsky-performance'),
            'runtime_current_clean' => __('Runtime clean', 'yablonsky-performance'),
        ) as $key => $label) {
            $passed = !empty($gates[$key]);
            echo '<div class="' . ($passed ? 'is-pass' : 'is-wait') . '"><span>' . esc_html($passed ? '✓' : '○') . '</span><strong>' . esc_html($label) . '</strong></div>';
        }
        echo '</div><p class="yperf-benchmark-safety"><strong>' . esc_html__('R2 contract is now executable through R3 below.', 'yablonsky-performance') . '</strong> ' . esc_html__('R2 remains the immutable approval boundary. R3 may act only when this exact fingerprint is still current; it never overwrites another provider, uses FLUSHDB or changes Redis configuration.', 'yablonsky-performance') . '</p></div>';
    }

    /** @param array<string,mixed> $execution @param array<string,mixed> $contract */
    private static function renderObjectCacheExecution(array $execution, array $contract): void
    {
        $state = sanitize_key((string) ($execution['state'] ?? 'inactive'));
        $active = !empty($execution['active']);
        $pending = !empty($execution['pending']);
        $details = is_array($contract['contract'] ?? null) ? $contract['contract'] : array();
        $fingerprint = (string) ($details['fingerprint'] ?? '');
        $contractReady = (string) ($contract['state'] ?? '') === 'operation_contract_ready' && $fingerprint !== '';
        $record = is_array($execution['record'] ?? null) ? $execution['record'] : array();

        $label = $active ? __('ACTIVE', 'yablonsky-performance') : ($pending ? __('VERIFYING', 'yablonsky-performance') : __('WRITE LOCKED', 'yablonsky-performance'));
        $stateClass = $active ? 'yperf-state-healthy' : ($pending ? 'yperf-state-attention' : 'yperf-state-neutral');
        echo '<div class="yperf-card yperf-native-op-card"><div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('PERSISTENT OBJECT CACHE · R3', 'yablonsky-performance') . '</span><h2>' . esc_html__('Activate, prove and roll back the YPerf Redis executor', 'yablonsky-performance') . '</h2><p>' . esc_html__('This is the first explicit local write boundary: YPerf may create only its own wp-content/object-cache.php and namespaced Redis health/cache keys in the reserved logical database.', 'yablonsky-performance') . '</p></div><span class="yperf-state ' . esc_attr($stateClass) . '">' . esc_html($label) . '</span></div>';
        echo '<div class="yperf-def-list">';
        self::renderDefinitionRow(__('Execution state', 'yablonsky-performance'), strtoupper($state));
        self::renderDefinitionRow(__('Redis target', 'yablonsky-performance'), (int) ($details['database'] ?? -1) >= 0 ? sprintf('127.0.0.1:6379 · DB %d', (int) $details['database']) : __('Not frozen', 'yablonsky-performance'));
        self::renderDefinitionRow(__('Namespace', 'yablonsky-performance'), (string) ($record['key_prefix'] ?? $details['key_prefix'] ?? __('Not frozen', 'yablonsky-performance')));
        self::renderDefinitionRow(__('Drop-in', 'yablonsky-performance'), !empty($execution['dropin_owned']) ? __('wp-content/object-cache.php · YPerf owned', 'yablonsky-performance') : __('Not active', 'yablonsky-performance'));
        self::renderDefinitionRow(__('Last Runtime proof', 'yablonsky-performance'), isset($record['runtime_fail']) ? sprintf('%d PASS · %d FAIL', (int) ($record['runtime_pass'] ?? 0), (int) ($record['runtime_fail'] ?? 0)) : __('Not run by R3 yet', 'yablonsky-performance'));
        echo '</div>';

        if ($active) {
            echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="yperf-runtime-actions"><input type="hidden" name="action" value="yperf_rollback_object_cache">';
            wp_nonce_field('yperf_rollback_object_cache');
            echo '<button type="submit" class="button">' . esc_html__('Roll back YPerf Object Cache', 'yablonsky-performance') . '</button><span>' . esc_html__('Removes only the YPerf-owned drop-in. It does not FLUSHDB, inspect arbitrary keys or change Redis configuration.', 'yablonsky-performance') . '</span></form>';
        } elseif ($contractReady && !$pending) {
            echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="yperf-runtime-actions"><input type="hidden" name="action" value="yperf_activate_object_cache"><input type="hidden" name="yperf_object_cache_contract" value="' . esc_attr($fingerprint) . '">';
            wp_nonce_field('yperf_activate_object_cache');
            echo '<button type="submit" class="button button-primary">' . esc_html__('Activate YPerf Object Cache & Prove', 'yablonsky-performance') . '</button><span>' . esc_html__('Explicitly writes the approved drop-in, proves Redis write/read/delete, runs Runtime Acceptance and automatically removes the drop-in if verification fails.', 'yablonsky-performance') . '</span></form>';
        } elseif ($pending) {
            echo '<p class="yperf-benchmark-safety"><strong>' . esc_html__('Verification pending.', 'yablonsky-performance') . '</strong> ' . esc_html__('Reload Setup or repeat the explicit activation flow if the verification request was interrupted. Pending operations expire and fail closed.', 'yablonsky-performance') . '</p>';
        } else {
            echo '<p class="yperf-benchmark-safety"><strong>' . esc_html__('Activation remains blocked.', 'yablonsky-performance') . '</strong> ' . esc_html__('Reserve an isolated Redis database and satisfy the current R2 contract first. YPerf never overwrites another provider’s object-cache.php.', 'yablonsky-performance') . '</p>';
        }
        echo '<p class="yperf-benchmark-safety"><strong>' . esc_html__('No FLUSHDB.', 'yablonsky-performance') . '</strong> ' . esc_html__('YPerf uses a site-specific namespace and generation-based cache invalidation so rollback never requires deleting unrelated Redis data.', 'yablonsky-performance') . '</p></div>';
    }

    /** @param array<string,mixed> $origin */
    private static function renderOriginBudgetReadiness(array $origin): void
    {
        $capacity = is_array($origin['capacity'] ?? null) ? $origin['capacity'] : array();
        $gates = is_array($origin['gates'] ?? null) ? $origin['gates'] : array();
        $progress = max(0, min(100, (int) ($origin['progress_percent'] ?? 0)));
        $ready = (string) ($origin['state'] ?? '') === 'capacity_review_ready';
        echo '<div class="yperf-card yperf-native-op-card"><div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('ORIGIN BUDGET · R1', 'yablonsky-performance') . '</span><h2>' . esc_html__('PHP-FPM capacity evidence', 'yablonsky-performance') . '</h2><p>' . esc_html((string) ($origin['reason'] ?? '')) . '</p></div><span class="yperf-state ' . ($ready ? 'yperf-state-healthy' : 'yperf-state-attention') . '">' . esc_html((string) ($origin['label'] ?? __('REVIEW', 'yablonsky-performance'))) . '</span></div>';
        echo '<div class="yperf-native-op-progress"><div><strong>' . esc_html__('Origin Budget readiness', 'yablonsky-performance') . '</strong><span>' . esc_html($progress . '%') . '</span></div><div class="yperf-progress-track"><span style="width:' . esc_attr((string) $progress) . '%"></span></div></div>';
        echo '<div class="yperf-def-list">';
        self::renderDefinitionRow(__('Site pool', 'yablonsky-performance'), (string) ($capacity['site_pool'] ?? __('Not mapped', 'yablonsky-performance')));
        self::renderDefinitionRow(__('Workers', 'yablonsky-performance'), sprintf('%d / %d', (int) ($capacity['current_processes'] ?? 0), (int) ($capacity['max_children'] ?? 0)));
        /* translators: 1: average PHP-FPM worker RSS in MB, 2: maximum worker RSS in MB. */
        $workerRss = sprintf(__('avg %1$.1f MB · max %2$.1f MB', 'yablonsky-performance'), (float) ($capacity['avg_worker_rss_mb'] ?? 0), (float) ($capacity['max_worker_rss_mb'] ?? 0));
        self::renderDefinitionRow(__('Worker RSS', 'yablonsky-performance'), $workerRss);
        self::renderDefinitionRow(__('Projected pool memory at max_children', 'yablonsky-performance'), sprintf('%.1f MB · %.1f%%', (float) ($capacity['projected_pool_memory_at_max_children_mb'] ?? 0), (float) ($capacity['projected_pool_memory_pct_host'] ?? 0)));
        echo '</div><div class="yperf-native-op-gates">';
        foreach (array('site_pool_mapped' => __('Site pool mapped', 'yablonsky-performance'), 'max_children_visible' => __('max_children visible', 'yablonsky-performance'), 'worker_rss_sampled' => __('Worker RSS sampled', 'yablonsky-performance'), 'host_memory_visible' => __('Host memory visible', 'yablonsky-performance'), 'runtime_clean' => __('Runtime clean', 'yablonsky-performance')) as $key => $label) {
            $passed = !empty($gates[$key]);
            echo '<div class="' . ($passed ? 'is-pass' : 'is-wait') . '"><span>' . esc_html($passed ? '✓' : '○') . '</span><strong>' . esc_html($label) . '</strong></div>';
        }
        echo '</div><p class="yperf-benchmark-safety"><strong>' . esc_html__('Capacity evidence, not autotuning.', 'yablonsky-performance') . '</strong> ' . esc_html__('YPerf does not change pm.max_children, pm.max_requests or reload PHP-FPM in this release.', 'yablonsky-performance') . '</p></div>';
    }

    /** @param array<string,mixed> $contract */
    private static function renderOriginBudgetContract(array $contract): void
    {
        $state = sanitize_key((string) ($contract['state'] ?? 'evidence_required'));
        $ready = $state === 'operation_contract_ready';
        $progress = max(0, min(100, (int) ($contract['progress_percent'] ?? 0)));
        $details = is_array($contract['contract'] ?? null) ? $contract['contract'] : array();
        $gates = is_array($contract['gates'] ?? null) ? $contract['gates'] : array();
        $fingerprint = (string) ($details['fingerprint'] ?? '');

        echo '<div class="yperf-card yperf-native-op-card"><div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('ORIGIN BUDGET · R2', 'yablonsky-performance') . '</span><h2>' . esc_html__('Freeze the PHP-FPM operation contract', 'yablonsky-performance') . '</h2><p>' . esc_html((string) ($contract['reason'] ?? '')) . '</p></div><span class="yperf-state ' . ($ready ? 'yperf-state-healthy' : 'yperf-state-attention') . '">' . esc_html((string) ($contract['label'] ?? __('REVIEW', 'yablonsky-performance'))) . '</span></div>';
        echo '<div class="yperf-native-op-progress"><div><strong>' . esc_html__('Write-boundary readiness', 'yablonsky-performance') . '</strong><span>' . esc_html($progress . '%') . '</span></div><div class="yperf-progress-track"><span style="width:' . esc_attr((string) $progress) . '%"></span></div></div>';
        echo '<div class="yperf-def-list">';
        self::renderDefinitionRow(__('Site pool', 'yablonsky-performance'), (string) ($details['site_pool'] ?? __('Not mapped', 'yablonsky-performance')));
        self::renderDefinitionRow(__('Current max_children', 'yablonsky-performance'), (string) ((int) ($details['current_max_children'] ?? 0)));
        /* translators: %d: number of PHP-FPM workers reserved for the control plane. */
        $controlPlaneReserve = sprintf(__('%d workers', 'yablonsky-performance'), (int) ($details['control_plane_reserve_workers'] ?? 0));
        self::renderDefinitionRow(__('Control-plane reserve', 'yablonsky-performance'), $controlPlaneReserve);
        /* translators: %d: candidate maximum number of PHP-FPM workers for review. */
        $reviewCeiling = sprintf(__('≤ %d workers', 'yablonsky-performance'), (int) ($details['candidate_max_children_ceiling'] ?? 0));
        self::renderDefinitionRow(__('Review ceiling', 'yablonsky-performance'), $reviewCeiling);
        self::renderDefinitionRow(__('Contract fingerprint', 'yablonsky-performance'), $fingerprint !== '' ? substr($fingerprint, 0, 16) . '…' : __('Not frozen', 'yablonsky-performance'));
        echo '</div><div class="yperf-native-op-gates">';
        foreach (array(
            'origin_capacity_evidence_ready' => __('Origin evidence ready', 'yablonsky-performance'),
            'control_plane_not_blocked' => __('Control plane not blocked', 'yablonsky-performance'),
            'control_plane_evidence_available' => __('Control-plane reserve modeled', 'yablonsky-performance'),
            'site_pool_mapped' => __('Exact FPM pool mapped', 'yablonsky-performance'),
            'worker_memory_sampled' => __('Worker memory sampled', 'yablonsky-performance'),
            'host_memory_visible' => __('Host memory visible', 'yablonsky-performance'),
        ) as $key => $label) {
            $passed = !empty($gates[$key]);
            echo '<div class="' . ($passed ? 'is-pass' : 'is-wait') . '"><span>' . esc_html($passed ? '✓' : '○') . '</span><strong>' . esc_html($label) . '</strong></div>';
        }
        echo '</div><p class="yperf-benchmark-safety"><strong>' . esc_html__('No FPM write or reload.', 'yablonsky-performance') . '</strong> ' . esc_html__('The contract preserves an explicit admin worker reserve and records the exact before-state. R3 can adopt an already-safe native max_children limit without rewriting PHP-FPM; any future host rewrite still requires syntax test, read-back, Runtime Acceptance and rollback.', 'yablonsky-performance') . '</p></div>';
    }

    /** @param array<string,mixed> $adoption */
    private static function renderOriginBudgetAdoption(array $adoption): void
    {
        $state = sanitize_key((string) ($adoption['state'] ?? 'contract_required'));
        $active = !empty($adoption['active']);
        $eligible = !empty($adoption['eligible_to_adopt']);
        $details = is_array($adoption['contract'] ?? null) ? $adoption['contract'] : array();
        $fingerprint = (string) ($details['enforcement_fingerprint'] ?? '');
        $stateClass = $active ? 'yperf-state-healthy' : ($state === 'drifted' ? 'yperf-state-attention' : 'yperf-state-neutral');
        echo '<div class="yperf-card yperf-native-op-card"><div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('ORIGIN BUDGET · R3', 'yablonsky-performance') . '</span><h2>' . esc_html__('Adopt the existing PHP-FPM worker boundary', 'yablonsky-performance') . '</h2><p>' . esc_html((string) ($adoption['reason'] ?? '')) . '</p></div><span class="yperf-state ' . esc_attr($stateClass) . '">' . esc_html((string) ($adoption['label'] ?? __('REVIEW', 'yablonsky-performance'))) . '</span></div>';
        echo '<div class="yperf-def-list">';
        self::renderDefinitionRow(__('Site pool', 'yablonsky-performance'), (string) ($details['pool'] ?? __('Not mapped', 'yablonsky-performance')));
        self::renderDefinitionRow(__('Enforced max_children', 'yablonsky-performance'), (string) ((int) ($details['max_children'] ?? 0)));
        self::renderDefinitionRow(__('Control-plane reserve', 'yablonsky-performance'), sprintf('%d workers', (int) ($details['control_plane_reserve_workers'] ?? 0)));
        self::renderDefinitionRow(__('Frontend worker envelope', 'yablonsky-performance'), sprintf('%d workers', (int) ($details['frontend_worker_envelope'] ?? 0)));
        echo '</div>';
        if ($eligible && $fingerprint !== '') {
            echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="yperf-runtime-actions"><input type="hidden" name="action" value="yperf_adopt_origin_budget"><input type="hidden" name="yperf_origin_budget_contract" value="' . esc_attr($fingerprint) . '">';
            wp_nonce_field('yperf_adopt_origin_budget');
            echo '<button type="submit" class="button button-primary">' . esc_html__('Adopt Current FPM Budget', 'yablonsky-performance') . '</button><span>' . esc_html__('Uses the native max_children that already exists. No pool file, service or process is changed.', 'yablonsky-performance') . '</span></form>';
        } elseif ($active) {
            echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="yperf-runtime-actions"><input type="hidden" name="action" value="yperf_release_origin_budget">';
            wp_nonce_field('yperf_release_origin_budget');
            echo '<button type="submit" class="button">' . esc_html__('Release Origin Budget Authority', 'yablonsky-performance') . '</button><span>' . esc_html__('Releases only YPerf authority state. The native PHP-FPM limit remains exactly as it is.', 'yablonsky-performance') . '</span></form>';
        }
        echo '<p class="yperf-benchmark-safety"><strong>' . esc_html__('Native-first adoption.', 'yablonsky-performance') . '</strong> ' . esc_html__('If the existing FPM limit already provides a safe bounded origin, YPerf adopts it instead of rewriting it. Future Host Intelligence drift invalidates the authority and requires review.', 'yablonsky-performance') . '</p></div>';
    }

    /** @param array<string,mixed> $plan */
    private static function renderNativeAdoptionPlan(array $plan): void
    {
        $layers = is_array($plan['layers'] ?? null) ? $plan['layers'] : array();
        echo '<div class="yperf-card yperf-native-plan-card"><div class="yperf-card-head"><div><h2>' . esc_html__('Native adoption plan', 'yablonsky-performance') . '</h2><p>' . esc_html__('A no-change planning surface built from WordPress evidence plus the optional host snapshot. Explicit execution lives in the dedicated adoption/operation cards and remains gated by ownership, proof and rollback contracts.', 'yablonsky-performance') . '</p></div><span class="yperf-state yperf-state-neutral">' . esc_html__('Read-only plan', 'yablonsky-performance') . '</span></div><div class="yperf-native-plan-list">';
        foreach (array('page_cache' => __('Page Cache', 'yablonsky-performance'), 'object_cache' => __('Object Cache', 'yablonsky-performance'), 'origin_budget' => __('Origin Budget', 'yablonsky-performance')) as $id => $label) {
            $row = is_array($layers[$id] ?? null) ? $layers[$id] : array();
            $risk = sanitize_html_class((string) ($row['risk'] ?? 'unknown'));
            echo '<div class="yperf-native-plan-row"><span class="yperf-env-badge yperf-env-badge-' . esc_attr($risk === 'review' ? 'available' : ($risk === 'attention' ? 'attention' : 'unknown')) . '">' . esc_html(strtoupper((string) ($row['state'] ?? 'unknown'))) . '</span><div><strong>' . esc_html($label) . '</strong><span>' . esc_html((string) ($row['strategy'] ?? '')) . '</span></div></div>';
        }
        echo '</div><p class="yperf-benchmark-safety"><strong>' . esc_html__('No implicit host apply.', 'yablonsky-performance') . '</strong> ' . esc_html__('YPerf may execute only the dedicated local operations whose exact contracts are shown above. Host configuration writes still require a separately reviewed helper with backup, exact diff, syntax test, read-back, acceptance and rollback.', 'yablonsky-performance') . '</p></div>';
    }

    /** @param array<string,mixed> $caps */
    private static function renderProviderOwnership(array $caps): void
    {
        $providers = is_array($caps['providers'] ?? null) ? $caps['providers'] : array();
        $w3tc = is_array($providers['w3-total-cache'] ?? null) ? $providers['w3-total-cache'] : array();
        if ($w3tc === array()) {
            echo '<div class="yperf-empty"><strong>' . esc_html__('No supported provider configuration detected', 'yablonsky-performance') . '</strong><span>' . esc_html__('YPerf can still detect generic cache artifacts; exact migration adapters will appear here when a supported provider is present.', 'yablonsky-performance') . '</span></div>';
            return;
        }
        if (empty($w3tc['config_readable'])) {
            echo '<div class="yperf-empty"><strong>W3 Total Cache</strong><span>' . esc_html((string) ($w3tc['note'] ?? __('Installed, exact state unknown.', 'yablonsky-performance'))) . '</span></div>';
            return;
        }
        $layers = is_array($w3tc['layers'] ?? null) ? $w3tc['layers'] : array();
        $rows = array(
            'page_cache'       => __('Page Cache', 'yablonsky-performance'),
            'database_cache'   => __('Database Cache', 'yablonsky-performance'),
            'object_cache'     => __('Object Cache', 'yablonsky-performance'),
            'minify'           => __('Minify', 'yablonsky-performance'),
            'browser_cache'    => __('Browser Cache', 'yablonsky-performance'),
            'statistics'       => __('Statistics', 'yablonsky-performance'),
            'lazy_load'        => __('Lazy Load', 'yablonsky-performance'),
            'fragment_cache'   => __('Fragment Cache', 'yablonsky-performance'),
        );
        echo '<div class="yperf-provider-table"><div class="yperf-provider-head"><span>' . esc_html__('Layer', 'yablonsky-performance') . '</span><span>' . esc_html__('State', 'yablonsky-performance') . '</span><span>' . esc_html__('Engine', 'yablonsky-performance') . '</span></div>';
        foreach ($rows as $key => $label) {
            $layer = is_array($layers[$key] ?? null) ? $layers[$key] : array();
            $enabled = !empty($layer['enabled']);
            $configured = !empty($layer['configured']);
            if ($enabled && !$configured) {
                $state = __('Extension active · not configured', 'yablonsky-performance');
                $stateClass = 'is-off';
            } else {
                $state = $enabled ? __('Enabled', 'yablonsky-performance') : ($configured ? __('Configured · Off', 'yablonsky-performance') : __('Off', 'yablonsky-performance'));
                $stateClass = $enabled ? 'is-on' : 'is-off';
            }
            $engine = (string) ($layer['engine'] ?? __('None', 'yablonsky-performance'));
            echo '<div class="yperf-provider-row"><strong>' . esc_html($label) . '</strong><span class="yperf-provider-state ' . esc_attr($stateClass) . '">' . esc_html($state) . '</span><code>' . esc_html($engine) . '</code></div>';
        }
        echo '</div><div class="yperf-intelligence-note yperf-provider-note"><strong>' . esc_html__('Read-only W3TC adapter', 'yablonsky-performance') . '</strong><span>' . esc_html__('YPerf reads the provider configuration through its public runtime API. It does not change W3TC settings.', 'yablonsky-performance') . '</span></div>';
    }

    /** @param array<string,mixed> $benchmark @param array<string,mixed> $caps @param array<string,mixed> $environment */
    private static function renderBenchmarkLab(array $benchmark, array $caps, array $environment): void
    {
        unset($caps, $environment);
        echo '<form id="yperf-benchmark-form" action="#" method="post"><div class="yperf-runtime-actions"><button type="submit" class="button button-primary" id="yperf-run-benchmark">' . esc_html($benchmark === array() ? __('Run Benchmark', 'yablonsky-performance') : __('Run Benchmark Again', 'yablonsky-performance')) . '</button><span class="yperf-inline-note">' . esc_html__('Three frontend samples per route plus isolated Redis/Memcached localhost read tests. No cache setting is changed.', 'yablonsky-performance') . '</span></div></form>';
        echo '<div id="yperf-benchmark-progress" class="yperf-progress-wrap" hidden><div class="yperf-progress-top"><strong id="yperf-benchmark-label">' . esc_html__('Preparing benchmark…', 'yablonsky-performance') . '</strong><span id="yperf-benchmark-percent">0%</span></div><div class="yperf-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span id="yperf-benchmark-bar"></span></div></div>';

        if ($benchmark === array()) {
            echo '<div class="yperf-benchmark-flow"><span>' . esc_html__('Measure', 'yablonsky-performance') . '</span><b>→</b><span>' . esc_html__('Compare', 'yablonsky-performance') . '</span><b>→</b><span>' . esc_html__('Recommend', 'yablonsky-performance') . '</span><b>→</b><span>' . esc_html__('Explicit Apply', 'yablonsky-performance') . '</span><b>→</b><span>' . esc_html__('Acceptance / rollback', 'yablonsky-performance') . '</span></div><p class="yperf-benchmark-safety"><strong>' . esc_html__('No “Next = Apply”.', 'yablonsky-performance') . '</strong> ' . esc_html__('Benchmark results never silently alter W3TC, Nginx, Redis, Memcached or PHP-FPM.', 'yablonsky-performance') . '</p>';
            return;
        }

        echo '<div class="yperf-benchmark-results">';
        foreach ((array) ($benchmark['results'] ?? array()) as $result) {
            if (!is_array($result)) {
                continue;
            }
            $status = sanitize_html_class((string) ($result['status'] ?? 'skip'));
            $kind = (string) ($result['kind'] ?? '');
            $metric = isset($result['average_ms']) ? number_format_i18n((float) $result['average_ms'], $kind === 'engine' ? 4 : 0) . ' ms' : '—';
            $delta = '';
            if ($kind === 'engine' && isset($result['relative_to_fastest_pct'])) {
                $pct = (float) $result['relative_to_fastest_pct'];
                $delta = !empty($result['fastest']) ? __('Fastest', 'yablonsky-performance') : sprintf(
                    /* translators: %s: percent slower than fastest engine. */
                    __('+%s%% vs fastest', 'yablonsky-performance'),
                    number_format_i18n($pct, 2)
                );
            } elseif ($kind === 'http' && isset($result['median_ms'])) {
                $delta = sprintf(
                    /* translators: %s: median response time. */
                    __('Median %s ms', 'yablonsky-performance'),
                    number_format_i18n((float) $result['median_ms'], 0)
                );
            }
            echo '<div class="yperf-benchmark-result"><span class="yperf-badge yperf-badge-' . esc_attr($status === 'pass' ? 'pass' : ($status === 'fail' ? 'fail' : 'skip')) . '">' . esc_html(strtoupper($status)) . '</span><div><strong>' . esc_html((string) ($result['name'] ?? '')) . '</strong><span>' . esc_html((string) ($result['details'] ?? '')) . '</span></div><div class="yperf-benchmark-metric"><strong>' . esc_html($metric) . '</strong><small>' . esc_html($delta) . '</small></div></div>';
        }
        echo '</div><p class="yperf-benchmark-safety"><strong>' . esc_html__('Human decides.', 'yablonsky-performance') . '</strong> ' . esc_html__('Yablonsky Performance can recommend an engine, but it never hides a slower option or applies it without explicit approval.', 'yablonsky-performance') . '</p>';
    }

    /**
     * @param array<string,mixed> $journey
     * @param array<string,mixed> $migration
     * @param array<string,mixed> $performancePlan
     */
    private static function renderGuidedMigrationCenter(array $journey, array $migration, array $performancePlan): void
    {
        echo '<div class="yperf-migration-workspace" data-yperf-migration-workspace data-yperf-profile-accepted="' . esc_attr(!empty($journey['profile_accepted']) ? '1' : '0') . '">';
        echo '<div class="yperf-migration-journey">';
        echo '<div class="yperf-card yperf-migration-journey-hero">';
        if (empty($journey['applicable'])) {
            $legacyRecord = W3tcMigrationExecutor::last();
            $cleanup = W3tcMigrationExecutor::postHandoverCleanupStatus();
            $migrationComplete = (string) ($legacyRecord['state'] ?? '') === 'active' && !empty($cleanup['complete']);
            $title = $migrationComplete ? __('Migration complete', 'yablonsky-performance') : __('No provider migration required', 'yablonsky-performance');
            $summary = $migrationComplete
                ? __('W3 Total Cache is retired, provider-owned artifacts are reconciled and post-handover Runtime Acceptance is clean.', 'yablonsky-performance')
                : (string) ($journey['summary'] ?? '');
            $guideTitle = $migrationComplete ? __('Migration finished', 'yablonsky-performance') : __('Nothing to move', 'yablonsky-performance');
            $guideCopy = $migrationComplete
                ? __('No further migration action is required. Continue with performance adoption only when you want to activate another proven layer.', 'yablonsky-performance')
                : __('No supported legacy performance provider requires retirement on this site.', 'yablonsky-performance');
            echo '<div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('GUIDED MIGRATION', 'yablonsky-performance') . '</span><h2>' . esc_html($title) . '</h2><p>' . esc_html($summary) . '</p></div><span class="yperf-state yperf-state-healthy">' . esc_html__('READY', 'yablonsky-performance') . '</span></div>';
            echo '</div></div>';
            echo '<aside class="yperf-migration-companion"><div class="yperf-card"><span class="yperf-eyebrow">' . esc_html__('MIGRATION GUIDE', 'yablonsky-performance') . '</span><h3>' . esc_html($guideTitle) . '</h3><p>' . esc_html($guideCopy) . '</p>';
            if ($migrationComplete) {
                echo '<button type="button" class="button button-primary" data-yperf-open-tab="setup" data-yperf-open-subtab="setup:page-cache">' . esc_html__('Continue to Page Cache', 'yablonsky-performance') . '</button>';
            }
            echo '</div></aside></div>';
            return;
        }

        if (!empty($journey['history_only'])) {
            $completedAt = (int) ($journey['completed_at'] ?? 0);
            echo '<div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('MIGRATION HISTORY', 'yablonsky-performance') . '</span><h2>' . esc_html__('Migration completed successfully', 'yablonsky-performance') . '</h2><p>' . esc_html((string) ($journey['summary'] ?? '')) . '</p></div><span class="yperf-state yperf-state-healthy">' . esc_html__('COMPLETED', 'yablonsky-performance') . '</span></div>';
            echo '<div class="yperf-migration-route"><div><strong>' . esc_html((string) ($journey['provider'] ?? 'W3 Total Cache')) . '</strong><span>' . esc_html__('Previous provider', 'yablonsky-performance') . '</span></div><b aria-hidden="true">→</b><div><strong>Yablonsky Performance</strong><span>' . esc_html__('Current performance authority', 'yablonsky-performance') . '</span></div></div>';
            echo '<div class="yperf-native-op-progress yperf-migration-journey-progress"><div><strong>' . esc_html__('Migration', 'yablonsky-performance') . '</strong><span>100%</span></div><div class="yperf-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="100"><span style="width:100%"></span></div></div>';
            if ($completedAt > 0) {
                echo '<p class="yperf-inline-note">' . esc_html(sprintf(
                    /* translators: %s: local date and time when migration completed. */
                    __('Completed: %s', 'yablonsky-performance'),
                    wp_date('Y-m-d H:i:s', $completedAt)
                )) . '</p>';
            }
            echo '</div><div class="yperf-migration-journey-steps">';
            foreach ((array) ($journey['steps'] ?? array()) as $step) {
                if (!is_array($step)) { continue; }
                /* translators: %d: migration step number. */
                $historyStepLabel = sprintf(__('Step %d', 'yablonsky-performance'), (int) ($step['number'] ?? 0));
                echo '<section class="yperf-migration-journey-step is-complete"><div class="yperf-migration-step-marker" aria-hidden="true">✓</div><div class="yperf-migration-step-body"><div class="yperf-migration-step-head"><div><span>' . esc_html($historyStepLabel) . '</span><h3>' . esc_html((string) ($step['title'] ?? '')) . '</h3></div><span class="yperf-state yperf-state-healthy">' . esc_html__('Complete', 'yablonsky-performance') . '</span></div><p>' . esc_html((string) ($step['description'] ?? '')) . '</p></div></section>';
            }
            echo '</div><aside class="yperf-migration-companion"><div class="yperf-card"><span class="yperf-eyebrow">' . esc_html__('MIGRATION RECORD', 'yablonsky-performance') . '</span><h3>' . esc_html__('No action required', 'yablonsky-performance') . '</h3><p>' . esc_html__('This completed migration remains visible as history. Future YPerf updates do not ask you to migrate the same provider again.', 'yablonsky-performance') . '</p></div></aside></div>';
            return;
        }

        $provider = (string) ($journey['provider'] ?? ($migration['provider'] ?? ''));
        $providerVersion = (string) ($journey['provider_version'] ?? ($migration['provider_version'] ?? ''));
        $completed = (int) ($journey['completed'] ?? 0);
        $total = max(1, (int) ($journey['total'] ?? 5));
        $percent = max(0, min(100, (int) ($journey['progress_percent'] ?? 0)));
        $current = max(1, min($total, (int) ($journey['current_step'] ?? 1)));
        $steps = is_array($journey['steps'] ?? null) ? $journey['steps'] : array();
        $currentStep = is_array($steps[$current - 1] ?? null) ? $steps[$current - 1] : array();
        $gate = is_array($migration['exit_gate'] ?? null) ? $migration['exit_gate'] : array();
        $gateBlockers = is_array($gate['blockers'] ?? null) ? $gate['blockers'] : array();

        echo '<div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('GUIDED MIGRATION', 'yablonsky-performance') . '</span><h2>' . esc_html(sprintf(
            /* translators: 1: source provider name, 2: destination product name. */
            __('Move from %1$s to %2$s', 'yablonsky-performance'),
            $provider,
            'Yablonsky Performance'
        )) . '</h2><p>' . esc_html((string) ($journey['summary'] ?? '')) . '</p></div><span class="yperf-state ' . esc_attr($percent >= 100 ? 'yperf-state-healthy' : 'yperf-state-neutral') . '" data-yperf-journey-count>' . esc_html(sprintf(
            /* translators: 1: completed steps, 2: total steps. */
            __('%1$d / %2$d steps', 'yablonsky-performance'),
            $completed,
            $total
        )) . '</span></div>';
        echo '<div class="yperf-migration-route"><div><strong>' . esc_html($provider) . '</strong><span>' . esc_html($providerVersion !== '' ? $providerVersion : __('Detected source', 'yablonsky-performance')) . '</span></div><b aria-hidden="true">→</b><div><strong>' . esc_html__('Yablonsky Performance', 'yablonsky-performance') . '</strong><span>' . esc_html__('Target control plane', 'yablonsky-performance') . '</span></div></div>';
        echo '<div class="yperf-native-op-progress yperf-migration-journey-progress"><div><strong>' . esc_html__('Overall migration journey', 'yablonsky-performance') . '</strong><span data-yperf-journey-percent>' . esc_html((string) $percent) . '%</span></div><div class="yperf-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr((string) $percent) . '" data-yperf-journey-progress><span data-yperf-journey-bar style="width:' . esc_attr((string) $percent) . '%"></span></div></div>';
        echo '<div class="yperf-callout yperf-callout-info"><strong>' . esc_html__('One screen, one sequence', 'yablonsky-performance') . '</strong><span>' . esc_html__('Complete the highlighted step. Every action reports live progress here; finished steps stay visible as proof and future steps remain locked.', 'yablonsky-performance') . '</span></div>';
        echo '</div>';

        echo '<div class="yperf-migration-journey-steps">';
        foreach ($steps as $step) {
            if (!is_array($step)) {
                continue;
            }
            $number = (int) ($step['number'] ?? 0);
            $state = sanitize_key((string) ($step['state'] ?? 'locked'));
            $state = in_array($state, array('complete', 'current', 'locked'), true) ? $state : 'locked';
            $badge = $state === 'complete' ? __('Complete', 'yablonsky-performance') : ($state === 'current' ? __('Do this now', 'yablonsky-performance') : __('Locked', 'yablonsky-performance'));
            $icon = $state === 'complete' ? '✓' : (string) $number;
            echo '<section class="yperf-migration-journey-step is-' . esc_attr($state) . '" data-yperf-migration-step="' . esc_attr((string) $number) . '">';
            /* translators: %d: migration step number. */
            $stepLabel = sprintf(__('Step %d', 'yablonsky-performance'), $number);
            echo '<div class="yperf-migration-step-marker" aria-hidden="true">' . esc_html($icon) . '</div><div class="yperf-migration-step-body"><div class="yperf-migration-step-head"><div><span>' . esc_html($stepLabel) . '</span><h3>' . esc_html((string) ($step['title'] ?? '')) . '</h3></div><span class="yperf-state ' . esc_attr($state === 'complete' ? 'yperf-state-healthy' : ($state === 'current' ? 'yperf-state-attention' : 'yperf-state-neutral')) . '" data-yperf-step-badge>' . esc_html($badge) . '</span></div><p>' . esc_html((string) ($step['description'] ?? '')) . '</p>';

            if ($number === 1) {
                echo '<div class="yperf-migration-step-proof"><span>' . esc_html__('Source', 'yablonsky-performance') . '</span><strong>' . esc_html(trim($provider . ' ' . $providerVersion)) . '</strong><span>' . esc_html__('Provider responsibilities and drop-in ownership are visible.', 'yablonsky-performance') . '</span></div>';
            } elseif ($number === 2) {
                if ($state === 'complete') {
                    echo '<div class="yperf-migration-step-proof"><span>' . esc_html__('Evidence', 'yablonsky-performance') . '</span><strong>' . esc_html__('Runtime Acceptance and Benchmark are current', 'yablonsky-performance') . '</strong><span>' . esc_html__('The current YPerf version has clean read-only proof.', 'yablonsky-performance') . '</span></div>';
                } else {
                    echo '<div class="yperf-migration-action-box" data-yperf-step-action' . ($state === 'locked' ? ' hidden' : '') . '><strong>' . esc_html__('Refresh all read-only evidence', 'yablonsky-performance') . '</strong><span>' . esc_html__('Environment → Runtime Acceptance → Benchmark. This is read-only and stays on this migration screen.', 'yablonsky-performance') . '</span><button type="button" class="button button-primary button-hero" data-yperf-refresh-evidence>' . esc_html__('Refresh read-only evidence', 'yablonsky-performance') . '</button>';
                    echo '<div class="yperf-migration-inline-progress" data-yperf-evidence-local hidden><div class="yperf-progress-top"><strong data-yperf-local-label>' . esc_html__('Waiting to start…', 'yablonsky-performance') . '</strong><span data-yperf-local-percent>0%</span></div><div class="yperf-progress is-animated" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span data-yperf-local-bar></span></div><div class="yperf-migration-mini-stages"><div data-yperf-evidence-local-phase="environment"><span>○</span><strong>' . esc_html__('Environment', 'yablonsky-performance') . '</strong></div><div data-yperf-evidence-local-phase="runtime"><span>○</span><strong>' . esc_html__('Runtime Acceptance', 'yablonsky-performance') . '</strong></div><div data-yperf-evidence-local-phase="benchmark"><span>○</span><strong>' . esc_html__('Benchmark', 'yablonsky-performance') . '</strong></div></div></div></div>';
                }
            } elseif ($number === 3) {
                $recommended = sanitize_key((string) ($journey['recommended_profile'] ?? 'safe'));
                if (!in_array($recommended, array('safe', 'commerce'), true)) {
                    $recommended = 'safe';
                }
                $definition = PerformancePlan::profileDefinition($recommended);
                if ($state === 'complete') {
                    $currentDefinition = PerformancePlan::profileDefinition((string) ($journey['profile'] ?? 'safe'));
                    echo '<div class="yperf-migration-step-proof"><span>' . esc_html__('Accepted profile', 'yablonsky-performance') . '</span><strong>' . esc_html((string) ($currentDefinition['label'] ?? '')) . '</strong><span>' . esc_html__('The local profile was applied and accepted by Runtime Acceptance.', 'yablonsky-performance') . '</span></div>';
                } else {
                    echo '<div class="yperf-migration-action-box" data-yperf-step-action' . ($state === 'locked' ? ' hidden' : '') . '><div class="yperf-migration-action-title"><div><span>' . esc_html__('Recommended baseline', 'yablonsky-performance') . '</span><strong>' . esc_html((string) ($definition['label'] ?? '')) . '</strong></div><span class="yperf-state yperf-state-healthy">' . esc_html__('Recommended', 'yablonsky-performance') . '</span></div><div class="yperf-plan-preview yperf-migration-plan-preview"><strong>' . esc_html__('Exact changes', 'yablonsky-performance') . '</strong><ul>';
                    foreach ((array) ($definition['changes'] ?? array()) as $change) {
                        echo '<li>' . esc_html((string) $change) . '</li>';
                    }
                    echo '</ul></div><form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" data-yperf-migration-baseline-form data-yperf-profile="' . esc_attr($recommended) . '"><input type="hidden" name="action" value="yperf_apply_performance_plan"><input type="hidden" name="yperf_performance_profile" value="' . esc_attr($recommended) . '"><input type="hidden" name="yperf_return_tab" value="migration">';
                    wp_nonce_field('yperf_apply_performance_plan');
                    echo '<button type="submit" class="button button-primary button-hero" data-yperf-baseline-button>' . esc_html__('Apply & Prove this baseline', 'yablonsky-performance') . '</button></form><span class="yperf-inline-note">' . esc_html__('Fresh preflight first. Functional failure makes no change; post-apply failure restores the previous profile automatically.', 'yablonsky-performance') . '</span>';
                    self::renderBaselineProgress();
                    echo '</div>';
                }
            } elseif ($number === 4) {
                $handover = is_array($journey['handover'] ?? null) ? $journey['handover'] : array();
                if ($state === 'complete') {
                    echo '<div class="yperf-migration-step-proof"><span>' . esc_html__('Provider retirement gate', 'yablonsky-performance') . '</span><strong>' . esc_html__('Every active responsibility has a safe path', 'yablonsky-performance') . '</strong><span>' . esc_html__('YPerf replacement is ready; final plugin activation status remains a user decision.', 'yablonsky-performance') . '</span></div>';
                } else {
                    echo '<div class="yperf-migration-action-box" data-yperf-step-action' . ($state === 'locked' ? ' hidden' : '') . '><strong>' . esc_html__('Resolve the remaining provider responsibilities', 'yablonsky-performance') . '</strong><span>' . esc_html__('Nothing changes silently. Use an explicit retirement action below, or open the legacy settings if you prefer to make the same decision there. YPerf snapshots the relevant W3TC state, writes one bounded change, reads it back, and keeps rollback authority.', 'yablonsky-performance') . '</span>';
                    echo '<div class="yperf-responsibility-list" data-yperf-responsibility-list>';
                    foreach ($gateBlockers as $blocker) {
                        if (!is_array($blocker)) {
                            continue;
                        }
                        $id = sanitize_key((string) ($blocker['id'] ?? ''));
                        $automatic = $id === 'advanced-cache-dropin';
                        echo '<div class="yperf-responsibility-card ' . esc_attr($automatic ? 'is-automatic' : 'is-decision') . '" data-yperf-responsibility="' . esc_attr($id) . '"><div><span class="yperf-state ' . esc_attr($automatic ? 'yperf-state-neutral' : 'yperf-state-attention') . '">' . esc_html($automatic ? __('Reconciled after user handoff', 'yablonsky-performance') : __('Decision required', 'yablonsky-performance')) . '</span><strong>' . esc_html((string) ($blocker['title'] ?? '')) . '</strong></div><p>' . esc_html((string) ($blocker['reason'] ?? '')) . '</p><small>' . esc_html((string) ($blocker['next'] ?? '')) . '</small>';
                        if ($id === 'full-site-delivery') {
                            echo '<span class="yperf-responsibility-safety">' . esc_html__('Safe scope: turns off only W3TC Full-site Delivery. The selected engine and its settings stay stored and are restored if the protected handover rolls back.', 'yablonsky-performance') . '</span><div class="yperf-responsibility-card-actions"><button type="button" class="button button-primary" data-yperf-retire-responsibility="full-site-delivery">' . esc_html__('Retire W3TC Full-site Delivery', 'yablonsky-performance') . '</button><a class="button" target="_blank" rel="noopener noreferrer" href="' . esc_url(admin_url('admin.php?page=w3tc_general')) . '">' . esc_html__('Open legacy settings', 'yablonsky-performance') . ' ↗</a></div>';
                        } elseif ($id === 'cloudflare-extension') {
                            echo '<span class="yperf-responsibility-safety">' . esc_html__('Safe scope: disconnects only the W3TC Cloudflare extension. Cloudflare DNS/proxy, the zone itself and saved W3TC Cloudflare settings are not deleted.', 'yablonsky-performance') . '</span><div class="yperf-responsibility-card-actions"><button type="button" class="button button-primary" data-yperf-retire-responsibility="cloudflare-extension">' . esc_html__('Retire W3TC Cloudflare integration', 'yablonsky-performance') . '</button><a class="button" target="_blank" rel="noopener noreferrer" href="' . esc_url(admin_url('admin.php?page=w3tc_extensions')) . '">' . esc_html__('Open legacy settings', 'yablonsky-performance') . ' ↗</a></div>';
                        }
                        echo '</div>';
                    }
                    echo '</div><div class="yperf-migration-inline-actions"><button type="button" class="button" data-yperf-recheck-responsibilities>' . esc_html__('Recheck only', 'yablonsky-performance') . '</button><button type="button" class="button" data-yperf-toggle-migration-technical>' . esc_html__('Show technical evidence', 'yablonsky-performance') . '</button></div>';
                    echo '<div class="yperf-migration-inline-progress" data-yperf-responsibility-progress hidden><div class="yperf-progress-top"><strong data-yperf-responsibility-label>' . esc_html__('Waiting for a provider action…', 'yablonsky-performance') . '</strong><span data-yperf-responsibility-percent>0%</span></div><div class="yperf-progress is-animated" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span data-yperf-responsibility-bar></span></div><div class="yperf-migration-mini-stages"><div><span>○</span><strong>' . esc_html__('Read provider', 'yablonsky-performance') . '</strong></div><div><span>○</span><strong>' . esc_html__('Snapshot state', 'yablonsky-performance') . '</strong></div><div><span>○</span><strong>' . esc_html__('Apply / verify', 'yablonsky-performance') . '</strong></div><div><span>○</span><strong>' . esc_html__('Rebuild gate', 'yablonsky-performance') . '</strong></div></div></div><span class="yperf-inline-note">' . esc_html__('advanced-cache.php is reconciled only after you make the plugin activation decision in WordPress. YPerf never activates or deactivates another plugin.', 'yablonsky-performance') . '</span></div>';
                }
            } elseif ($number === 5) {
                if ($state === 'complete') {
                    echo '<div class="yperf-good-line"><strong>' . esc_html__('Migration complete and proven.', 'yablonsky-performance') . '</strong><span>' . esc_html__('YPerf owns the migrated responsibilities and post-handover Runtime Acceptance is clean.', 'yablonsky-performance') . '</span></div>';
                } else {
                    $cleanupRequired = !empty($journey['cleanup_required']);
                    echo '<div class="yperf-migration-action-box" data-yperf-step-action' . ($state === 'locked' ? ' hidden' : '') . '><strong>' . esc_html($cleanupRequired ? __('Finish post-handover cleanup', 'yablonsky-performance') : __('User-confirmed protected handoff', 'yablonsky-performance')) . '</strong><span>' . esc_html($cleanupRequired
                        ? __('The provider is already inactive. YPerf will snapshot the remaining high-confidence W3TC advanced-cache.php, retire only that artifact, run Health Check, and restore the exact file automatically if proof fails.', 'yablonsky-performance')
                        : __('YPerf prepares a reversible handoff, then asks you to deactivate W3 Total Cache yourself on the WordPress Plugins screen. After you return, YPerf reconciles ownership, proves execution and runs Health Check. YPerf never changes another plugin’s activation status.', 'yablonsky-performance')) . '</span>';
                    self::renderGuidedHandoverConsole(!empty($journey['handover_ready']), $cleanupRequired);
                    echo '</div>';
                }
            }

            echo '</div></section>';
        }
        echo '</div></div>';

        echo '<aside class="yperf-migration-companion">';
        echo '<div class="yperf-card yperf-migration-companion-card"><span class="yperf-eyebrow">' . esc_html__('MIGRATION COMPANION', 'yablonsky-performance') . '</span><h3>' . esc_html__('What happens next', 'yablonsky-performance') . '</h3><div class="yperf-migration-companion-current"><span>' . esc_html__('Current step', 'yablonsky-performance') . '</span><strong data-yperf-companion-current>' . esc_html((string) ($currentStep['title'] ?? '')) . '</strong></div><p data-yperf-companion-description>' . esc_html((string) ($currentStep['description'] ?? '')) . '</p><div class="yperf-migration-companion-live"><div><strong>' . esc_html__('Live action', 'yablonsky-performance') . '</strong><span data-yperf-companion-percent>0%</span></div><div class="yperf-progress-track"><span data-yperf-companion-bar style="width:0%"></span></div><small data-yperf-companion-status>' . esc_html__('Waiting for your next action.', 'yablonsky-performance') . '</small></div></div>';
        if ($current === 4 && $gateBlockers !== array()) {
            $decisionBlockers = array_values(array_filter($gateBlockers, static function ($item): bool {
                return is_array($item) && sanitize_key((string) ($item['id'] ?? '')) !== 'advanced-cache-dropin';
            }));
            echo '<div class="yperf-card yperf-migration-companion-task"><span class="yperf-eyebrow">' . esc_html__('PENDING ON THIS SITE', 'yablonsky-performance') . '</span><h3>' . esc_html__('Resolve these provider decisions', 'yablonsky-performance') . '</h3><ul>';
            foreach ($decisionBlockers as $blocker) {
                echo '<li><strong>' . esc_html((string) ($blocker['title'] ?? '')) . '</strong><span>' . esc_html((string) ($blocker['next'] ?? '')) . '</span></li>';
            }
            echo '</ul><p><strong>' . esc_html__('Stay on this screen.', 'yablonsky-performance') . '</strong> ' . esc_html__('Use the explicit retirement buttons in Step 4. Each action is bounded, verified in place and reversible until the protected handover succeeds. advanced-cache.php remains automatic.', 'yablonsky-performance') . '</p></div>';
        }
        echo '<div class="yperf-card yperf-migration-doc-card"><span class="yperf-eyebrow">' . esc_html__('MIGRATION SAFETY CONTRACT', 'yablonsky-performance') . '</span><h3>' . esc_html__('You should never have to guess', 'yablonsky-performance') . '</h3><ul><li><strong>' . esc_html__('One active step.', 'yablonsky-performance') . '</strong> ' . esc_html__('Only the next safe action is enabled.', 'yablonsky-performance') . '</li><li><strong>' . esc_html__('Visible work.', 'yablonsky-performance') . '</strong> ' . esc_html__('Every long action shows progress, phases and completion state in the same step.', 'yablonsky-performance') . '</li><li><strong>' . esc_html__('No blind deletion.', 'yablonsky-performance') . '</strong> ' . esc_html__('Provider-owned drop-ins are reconciled only during protected handover.', 'yablonsky-performance') . '</li><li><strong>' . esc_html__('Proof after writes.', 'yablonsky-performance') . '</strong> ' . esc_html__('Runtime Acceptance verifies state-changing operations and rollback remains authoritative.', 'yablonsky-performance') . '</li></ul><button type="button" class="button" data-yperf-toggle-migration-technical>' . esc_html__('Technical migration details', 'yablonsky-performance') . '</button></div>';
        echo '</aside></div>';
    }

    private static function renderBaselineProgress(): void
    {
        echo '<div class="yperf-migration-inline-progress" data-yperf-baseline-progress hidden><div class="yperf-progress-top"><strong data-yperf-baseline-label>' . esc_html__('Waiting to start…', 'yablonsky-performance') . '</strong><span data-yperf-baseline-percent>0%</span></div><div class="yperf-progress is-animated" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span data-yperf-baseline-bar></span></div><div class="yperf-migration-stages yperf-migration-baseline-stages"><div data-threshold="10"><span>○</span><strong>' . esc_html__('1. Fresh preflight', 'yablonsky-performance') . '</strong></div><div data-threshold="42"><span>○</span><strong>' . esc_html__('2. Apply baseline', 'yablonsky-performance') . '</strong></div><div data-threshold="58"><span>○</span><strong>' . esc_html__('3. Runtime proof', 'yablonsky-performance') . '</strong></div><div data-threshold="78"><span>○</span><strong>' . esc_html__('4. Regression guard', 'yablonsky-performance') . '</strong></div><div data-threshold="100"><span>○</span><strong>' . esc_html__('5. Accept / rollback', 'yablonsky-performance') . '</strong></div></div><div class="yperf-migration-status-line"><strong data-yperf-baseline-phase>' . esc_html__('No baseline action is running.', 'yablonsky-performance') . '</strong><span data-yperf-baseline-detail>' . esc_html__('Press Apply & Prove to begin.', 'yablonsky-performance') . '</span></div><div class="yperf-migration-events" data-yperf-baseline-events aria-live="polite"></div></div>';
    }

    private static function renderGuidedHandoverConsole(bool $ready, bool $cleanupRequired = false): void
    {
        $progress = W3tcMigrationExecutor::progress();
        $pending = W3tcMigrationExecutor::pendingHandoff();
        $awaitingUser = (string) ($pending['state'] ?? '') === 'awaiting_user_deactivation';
        $progressCurrent = (string) ($progress['version'] ?? '') === YPERF_VERSION;
        $percent = $cleanupRequired || !$progressCurrent ? 0 : max(0, min(100, (int) ($progress['percent'] ?? 0)));
        $buttonLabel = $cleanupRequired ? __('Finish cleanup & prove', 'yablonsky-performance') : ($awaitingUser ? __('Continue after manual deactivation', 'yablonsky-performance') : __('Prepare safe handoff', 'yablonsky-performance'));
        $activityLabel = $cleanupRequired ? __('Cleanup activity', 'yablonsky-performance') : __('Handover activity', 'yablonsky-performance');
        if ($awaitingUser) {
            echo '<div class="yperf-callout yperf-callout-attention yperf-user-handoff-callout"><strong>' . esc_html__('Your confirmation is required in WordPress Plugins', 'yablonsky-performance') . '</strong><span>' . esc_html__('YPerf prepared the handoff but will not deactivate W3 Total Cache. Open Plugins, deactivate W3 Total Cache yourself, return here, then continue. This keeps plugin activation status under your control.', 'yablonsky-performance') . '</span><a class="button button-primary" href="' . esc_url(admin_url('plugins.php')) . '">' . esc_html__('Open Plugins', 'yablonsky-performance') . '</a></div>';
        }
        echo '<form id="yperf-guided-migration-form" action="' . esc_url(admin_url('admin-post.php')) . '" method="post"><input type="hidden" name="action" value="yperf_migrate_w3tc">';
        wp_nonce_field('yperf_migrate_w3tc');
        echo '<button type="submit" id="yperf-guided-run-migration" class="button button-primary button-hero"' . ($ready || $awaitingUser ? '' : ' disabled') . '>' . esc_html($buttonLabel) . '</button></form>';
        echo '<div class="yperf-migration-live" id="yperf-guided-migration-live" data-yperf-migration-ready="' . esc_attr($ready ? '1' : '0') . '"><div class="yperf-progress-top"><strong>' . esc_html($activityLabel) . '</strong><span id="yperf-guided-migration-percent">' . esc_html((string) $percent) . '%</span></div><div class="yperf-progress is-animated" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr((string) $percent) . '" id="yperf-guided-migration-progress"><span id="yperf-guided-migration-bar" style="width:' . esc_attr((string) $percent) . '%"></span></div>';
        $stages = $cleanupRequired ? array(
            array(18, __('1. Snapshot legacy drop-in', 'yablonsky-performance')),
            array(46, __('2. Retire provider artifact', 'yablonsky-performance')),
            array(74, __('3. Runtime Acceptance', 'yablonsky-performance')),
            array(100, __('4. Finalize / rollback', 'yablonsky-performance')),
        ) : array(
            array(10, __('1. Preflight', 'yablonsky-performance')),
            array(30, __('2. User-confirmed handoff', 'yablonsky-performance')),
            array(55, __('3. YPerf ownership', 'yablonsky-performance')),
            array(80, __('4. Execution proof', 'yablonsky-performance')),
            array(100, __('5. Finalize / rollback', 'yablonsky-performance')),
        );
        echo '<div class="yperf-migration-stages" data-yperf-guided-migration-stages>';
        foreach ($stages as $stage) {
            $complete = $percent >= (int) $stage[0];
            echo '<div data-threshold="' . esc_attr((string) $stage[0]) . '" class="' . esc_attr($complete ? 'is-complete' : '') . '"><span>' . esc_html($complete ? '✓' : '○') . '</span><strong>' . esc_html((string) $stage[1]) . '</strong></div>';
        }
        $progressLabel = (!$progressCurrent || $cleanupRequired) ? ($cleanupRequired ? __('Ready to finish provider cleanup', 'yablonsky-performance') : __('Waiting for a safe start', 'yablonsky-performance')) : (string) ($progress['label'] ?? __('Waiting for a safe start', 'yablonsky-performance'));
        $progressDetail = (!$progressCurrent || $cleanupRequired) ? ($cleanupRequired ? __('Press Finish cleanup & prove to reconcile the remaining provider-owned drop-in.', 'yablonsky-performance') : __('No handover action is running.', 'yablonsky-performance')) : (string) ($progress['detail'] ?? __('No handover action is running.', 'yablonsky-performance'));
        echo '</div><div class="yperf-migration-status-line"><strong id="yperf-guided-migration-phase">' . esc_html($progressLabel) . '</strong><span id="yperf-guided-migration-detail">' . esc_html($progressDetail) . '</span></div><div class="yperf-migration-events" id="yperf-guided-migration-events" aria-live="polite">';
        foreach ((array) ($progress['events'] ?? array()) as $event) {
            if (!is_array($event)) {
                continue;
            }
            echo '<div><time>' . esc_html(wp_date('H:i:s', (int) ($event['time'] ?? time()))) . '</time><span>' . esc_html((string) ($event['message'] ?? '')) . '</span></div>';
        }
        echo '</div></div>';
        if (!$ready) {
            echo '<p class="yperf-inline-note">' . esc_html__('The handover button unlocks automatically after Step 4 proves that all active provider responsibilities have a safe path.', 'yablonsky-performance') . '</p>';
        }
    }

    /** @param array<string,mixed> $migration */
    private static function renderMigrationPlan(array $migration): void
    {
        if (empty($migration['available'])) {
            echo '<div class="yperf-empty"><strong>' . esc_html__('No supported migration source detected', 'yablonsky-performance') . '</strong><span>' . esc_html__('W3 Total Cache is the first exact adapter. Other provider adapters can be added without changing the migration workflow.', 'yablonsky-performance') . '</span></div>';
            return;
        }

        $gate = is_array($migration['exit_gate'] ?? null) ? $migration['exit_gate'] : array();
        $safe = !empty($gate['safe_to_remove']);
        $percent = max(0, min(100, (int) ($gate['progress_percent'] ?? 0)));
        $ready = (int) ($gate['ready_items'] ?? 0);
        $total = (int) ($gate['total_items'] ?? 0);
        $blockers = is_array($gate['blockers'] ?? null) ? $gate['blockers'] : array();
        $gateLabel = $safe ? __('Exit gate ready', 'yablonsky-performance') : __('Exit gate blocked', 'yablonsky-performance');
        $gateClass = $safe ? 'yperf-state-healthy' : 'yperf-state-attention';

        echo '<div class="yperf-migration-head"><span><strong>' . esc_html((string) ($migration['provider'] ?? '')) . '</strong> ' . esc_html((string) ($migration['provider_version'] ?? '')) . '</span><span class="yperf-state ' . esc_attr($gateClass) . '">' . esc_html($gateLabel) . '</span></div>';
        echo '<div class="yperf-exit-progress"><div class="yperf-progress-top"><strong>' . esc_html__('Manual provider removal readiness', 'yablonsky-performance') . '</strong><span>' . esc_html(sprintf(
            /* translators: 1: ready items, 2: total items, 3: percent */
            __('%1$d / %2$d · %3$d%%', 'yablonsky-performance'),
            $ready,
            $total,
            $percent
        )) . '</span></div><div class="yperf-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr((string) $percent) . '"><span style="width:' . esc_attr((string) $percent) . '%"></span></div></div>';

        if ($blockers !== array()) {
            echo '<div class="yperf-exit-blockers"><strong>' . esc_html__('Removal blockers', 'yablonsky-performance') . '</strong>';
            foreach ($blockers as $blocker) {
                if (!is_array($blocker)) {
                    continue;
                }
                echo '<div class="yperf-exit-blocker"><span class="yperf-badge yperf-badge-attention">' . esc_html__('BLOCK', 'yablonsky-performance') . '</span><div><strong>' . esc_html((string) ($blocker['title'] ?? '')) . '</strong><p>' . esc_html((string) ($blocker['reason'] ?? '')) . '</p><small>' . esc_html((string) ($blocker['next'] ?? '')) . '</small></div></div>';
            }
            echo '</div>';
        } else {
            echo '<div class="yperf-good-line"><span class="yperf-dot yperf-dot-pass"></span><strong>' . esc_html__('No provider-exit blockers are currently detected.', 'yablonsky-performance') . '</strong></div>';
        }

        $gateRows = is_array($gate['rows'] ?? null) ? $gate['rows'] : array();
        if ($gateRows !== array()) {
            echo '<div class="yperf-exit-matrix">';
            foreach ($gateRows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $state = sanitize_key((string) ($row['state'] ?? 'blocked'));
                $label = in_array($state, array('ready', 'retire_safe', 'replacement_proven', 'not_present'), true) ? __('READY', 'yablonsky-performance') : __('BLOCKED', 'yablonsky-performance');
                $badgeClass = $label === __('READY', 'yablonsky-performance') ? 'yperf-badge-pass' : 'yperf-badge-attention';
                echo '<div class="yperf-exit-row"><span class="yperf-badge ' . esc_attr($badgeClass) . '">' . esc_html($label) . '</span><div><strong>' . esc_html((string) ($row['name'] ?? '')) . '</strong><span>' . esc_html((string) ($row['source'] ?? '')) . '</span><p>' . esc_html((string) ($row['reason'] ?? '')) . '</p><small>' . esc_html((string) ($row['next'] ?? '')) . '</small></div></div>';
            }
            echo '</div>';
        }

        $handover = W3tcMigrationExecutor::assessment();
        $lastHandover = W3tcMigrationExecutor::last();
        echo '<div class="yperf-migration-operation">';
        if ((string) ($lastHandover['state'] ?? '') === 'active') {
            $cleanup = W3tcMigrationExecutor::postHandoverCleanupStatus();
            if (!empty($cleanup['required']) || !empty($cleanup['blocked'])) {
                echo '<div class="yperf-callout yperf-callout-attention"><strong>' . esc_html__('Provider handover complete · residual cleanup pending', 'yablonsky-performance') . '</strong><span>' . esc_html__('The W3 Total Cache plugin is inactive, but advanced-cache.php still requires the protected final cleanup shown in Step 5 above.', 'yablonsky-performance') . '</span></div>';
            } else {
                echo '<div class="yperf-good-line"><strong>' . esc_html__('W3TC → YPerf migration is active and proven.', 'yablonsky-performance') . '</strong></div>';
            }
            echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post"><input type="hidden" name="action" value="yperf_rollback_w3tc">';
            wp_nonce_field('yperf_rollback_w3tc');
            echo '<button type="submit" class="button">' . esc_html__('Roll back YPerf migration changes', 'yablonsky-performance') . '</button></form>';
        } elseif (!empty($handover['ready'])) {
            echo '<div class="yperf-good-line"><strong>' . esc_html__('Protected handoff is ready.', 'yablonsky-performance') . '</strong><span>' . esc_html((string) ($handover['summary'] ?? '')) . '</span></div>';
            echo '<p class="yperf-inline-note">' . esc_html__('A provider-owned advanced-cache.php can still block blind removal. YPerf prepares a reversible handoff, but you control W3 Total Cache activation on the normal Plugins screen. After your confirmation, YPerf reconciles ownership and proves the replacement.', 'yablonsky-performance') . '</p>';
            echo '<form id="yperf-migration-form" action="' . esc_url(admin_url('admin-post.php')) . '" method="post"><input type="hidden" name="action" value="yperf_migrate_w3tc">';
            wp_nonce_field('yperf_migrate_w3tc');
            echo '<button type="submit" id="yperf-run-migration" class="button button-primary">' . esc_html__('Prepare W3TC → YPerf handoff', 'yablonsky-performance') . '</button></form>';
        } else {
            echo '<div class="yperf-callout yperf-callout-attention"><strong>' . esc_html__('Protected handoff is blocked.', 'yablonsky-performance') . '</strong>';
            $handoverBlockers = is_array($handover['blockers'] ?? null) ? $handover['blockers'] : array();
            foreach ($handoverBlockers as $message) {
                echo '<span>' . esc_html((string) $message) . '</span>';
            }
            echo '</div>';
        }
        $migrationProgress = W3tcMigrationExecutor::progress();
        $migrationPercent = max(0, min(100, (int) ($migrationProgress['percent'] ?? ((string) ($lastHandover['state'] ?? '') === 'active' ? 100 : 0))));
        echo '<div class="yperf-migration-live" id="yperf-migration-live" data-yperf-migration-ready="' . esc_attr(!empty($handover['ready']) ? '1' : '0') . '"><div class="yperf-progress-top"><strong>' . esc_html__('Migration activity', 'yablonsky-performance') . '</strong><span id="yperf-migration-percent">' . esc_html((string) $migrationPercent) . '%</span></div><div class="yperf-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr((string) $migrationPercent) . '" id="yperf-migration-progress"><span id="yperf-migration-bar" style="width:' . esc_attr((string) $migrationPercent) . '%"></span></div>';
        $stages = array(
            array(10, __('1. Preflight', 'yablonsky-performance')),
            array(30, __('2. User-confirmed handoff', 'yablonsky-performance')),
            array(55, __('3. YPerf ownership', 'yablonsky-performance')),
            array(80, __('4. Execution proof', 'yablonsky-performance')),
            array(100, __('5. Finalize / rollback', 'yablonsky-performance')),
        );
        echo '<div class="yperf-migration-stages" data-yperf-migration-stages>';
        foreach ($stages as $stage) {
            $complete = $migrationPercent >= (int) $stage[0];
            echo '<div data-threshold="' . esc_attr((string) $stage[0]) . '" class="' . esc_attr($complete ? 'is-complete' : '') . '"><span>' . esc_html($complete ? '✓' : '○') . '</span><strong>' . esc_html((string) $stage[1]) . '</strong></div>';
        }
        echo '</div><div class="yperf-migration-status-line"><strong id="yperf-migration-phase">' . esc_html((string) ($migrationProgress['label'] ?? __('Waiting for a safe start', 'yablonsky-performance'))) . '</strong><span id="yperf-migration-detail">' . esc_html((string) ($migrationProgress['detail'] ?? __('No migration action is running.', 'yablonsky-performance'))) . '</span></div><div class="yperf-migration-events" id="yperf-migration-events" aria-live="polite">';
        foreach ((array) ($migrationProgress['events'] ?? array()) as $event) {
            if (!is_array($event)) { continue; }
            echo '<div><time>' . esc_html(wp_date('H:i:s', (int) ($event['time'] ?? time()))) . '</time><span>' . esc_html((string) ($event['message'] ?? '')) . '</span></div>';
        }
        echo '</div></div>';
        echo '</div>';
        echo '<p class="yperf-benchmark-safety"><strong>' . esc_html__('No blind removal.', 'yablonsky-performance') . '</strong> ' . esc_html((string) ($gate['summary'] ?? $migration['summary'] ?? '')) . '</p>';
    }

    /** @param array<string,mixed> $result */
    private static function renderPurgeControl(array $result): void
    {
        echo '<div class="yperf-card yperf-operation-card"><div class="yperf-card-head"><div><h2>' . esc_html__('Purge', 'yablonsky-performance') . '</h2><p>' . esc_html__('Clear exactly what you intend. Provider APIs are used when available.', 'yablonsky-performance') . '</p></div></div>';
        echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post"><input type="hidden" name="action" value="yperf_manual_purge">';
        wp_nonce_field('yperf_manual_purge');
        echo '<div class="yperf-field-row"><select name="yperf_purge_mode" aria-label="' . esc_attr__('Purge mode', 'yablonsky-performance') . '"><option value="smart">' . esc_html__('Smart / URL', 'yablonsky-performance') . '</option><option value="page">' . esc_html__('Page Cache', 'yablonsky-performance') . '</option><option value="object">' . esc_html__('Object Cache', 'yablonsky-performance') . '</option><option value="all">' . esc_html__('All active YPerf caches', 'yablonsky-performance') . '</option></select><input type="url" name="yperf_purge_url" value="' . esc_attr(home_url('/')) . '" aria-label="' . esc_attr__('URL to purge', 'yablonsky-performance') . '"><button type="submit" class="button button-primary">' . esc_html__('Purge', 'yablonsky-performance') . '</button></div></form>';
        if ($result !== array()) {
            $resultMeta = array();
            if (!empty($result['provider'])) {
                $resultMeta[] = (string) $result['provider'];
            }
            if (isset($result['duration_ms'])) {
                $resultMeta[] = max(0, (int) $result['duration_ms']) . ' ms';
            }
            $details = (string) ($result['details'] ?? '');
            if ($resultMeta !== array()) {
                $details .= ($details !== '' ? ' · ' : '') . implode(' · ', $resultMeta);
            }
            echo '<div class="yperf-operation-result"><strong>' . esc_html(strtoupper((string) ($result['status'] ?? ''))) . '</strong><span>' . esc_html($details) . '</span></div>';
        }
        echo '</div>';
    }

    /** @param array<string,mixed> $report */
    private static function renderPrewarmControl(array $report): void
    {
        $plan = Prewarm::plan();
        echo '<div class="yperf-card yperf-operation-card"><div class="yperf-card-head"><div><h2>' . esc_html__('Prewarm', 'yablonsky-performance') . '</h2><p>' . esc_html__('Bounded warm-up of high-value public URLs. Maximum 12 URLs per run.', 'yablonsky-performance') . '</p></div><span class="yperf-state yperf-state-neutral">' . esc_html(sprintf(
            /* translators: %d: number of planned URLs. */
            __('%d URLs', 'yablonsky-performance'),
            count($plan)
        )) . '</span></div>';
        echo '<form id="yperf-prewarm-form" action="#" method="post"><button type="submit" class="button button-primary" id="yperf-run-prewarm">' . esc_html__('Run Prewarm', 'yablonsky-performance') . '</button></form>';
        echo '<div id="yperf-prewarm-progress" class="yperf-progress-wrap yperf-progress-compact" hidden><div class="yperf-progress-top"><strong id="yperf-prewarm-label">' . esc_html__('Preparing URLs…', 'yablonsky-performance') . '</strong><span id="yperf-prewarm-percent">0%</span></div><div class="yperf-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span id="yperf-prewarm-bar"></span></div></div>';
        if ($report !== array()) {
            $summary = is_array($report['summary'] ?? null) ? $report['summary'] : array();
            echo '<div class="yperf-operation-result"><strong>' . esc_html((string) ((int) ($summary['pass'] ?? 0))) . ' ' . esc_html__('warmed', 'yablonsky-performance') . '</strong><span>' . esc_html(sprintf(
                /* translators: 1: failed count, 2: skipped count. */
                __('%1$d failed · %2$d skipped', 'yablonsky-performance'),
                (int) ($summary['fail'] ?? 0),
                (int) ($summary['skip'] ?? 0)
            )) . '</span>';
            if (isset($summary['verified_hit'])) {
                echo '<span><strong>' . esc_html__('Execution proof', 'yablonsky-performance') . ':</strong> ' . esc_html((string) ((int) $summary['verified_hit'])) . ' HIT · ' . esc_html((string) ((int) ($summary['avg_ms'] ?? 0))) . ' ms</span>';
            }
            echo '</div>';
        }
        echo '</div>';
    }

    /** @param array<string,mixed> $report */
    private static function renderCacheabilityInspector(array $report): void
    {
        $url = $report !== array() && !empty($report['url']) ? (string) $report['url'] : home_url('/');
        echo '<div class="yperf-card yperf-operation-card"><div class="yperf-card-head"><div><h2>' . esc_html__('Cacheability Inspector', 'yablonsky-performance') . '</h2><p>' . esc_html__('Explain whether a URL should be cached and inspect its live response signals.', 'yablonsky-performance') . '</p></div></div>';
        echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post"><input type="hidden" name="action" value="yperf_cacheability_inspect">';
        wp_nonce_field('yperf_cacheability_inspect');
        echo '<div class="yperf-field-row"><input type="url" name="yperf_inspect_url" value="' . esc_attr($url) . '" aria-label="' . esc_attr__('URL to inspect', 'yablonsky-performance') . '"><button type="submit" class="button button-primary">' . esc_html__('Inspect', 'yablonsky-performance') . '</button></div></form>';
        if ($report !== array()) {
            $decision = !empty($report['cacheable']) ? 'CACHE' : 'BYPASS';
            echo '<div class="yperf-operation-result"><strong>' . esc_html($decision . ' · ' . (string) ($report['reason'] ?? '')) . '</strong><span>' . esc_html(sprintf(
                /* translators: 1: HTTP status, 2: response time in milliseconds. */
                __('HTTP %1$d · %2$d ms', 'yablonsky-performance'),
                (int) ($report['http_code'] ?? 0),
                (int) ($report['ms'] ?? 0)
            )) . '</span></div>';
        }
        echo '</div>';
    }

    /** @param array<string,mixed> $cloudflare */
    private static function renderCloudflareEdge(array $cloudflare): void
    {
        $credentials = is_array($cloudflare['credentials'] ?? null) ? $cloudflare['credentials'] : array();
        $audit = is_array($cloudflare['audit'] ?? null) ? $cloudflare['audit'] : array();
        $adoption = is_array($cloudflare['adoption'] ?? null) ? $cloudflare['adoption'] : array();
        $operation = is_array($cloudflare['operation'] ?? null) ? $cloudflare['operation'] : array();
        $configured = !empty($credentials['configured']);
        $adopted = !empty($adoption['active']);
        $ready = !empty($audit['ready']);
        $drift = is_array($audit['drift'] ?? null) ? $audit['drift'] : array();
        $errors = is_array($audit['errors'] ?? null) ? $audit['errors'] : array();
        $aligned = $ready && $drift === array();
        $inspected = $audit !== array();
        $auditTime = (int) ($audit['generated_at'] ?? 0);

        $stateLabel = __('OPTIONAL', 'yablonsky-performance');
        $stateClass = 'yperf-state-neutral';
        if ($adopted && $aligned) {
            $stateLabel = __('ADOPTED · ALIGNED', 'yablonsky-performance');
            $stateClass = 'yperf-state-healthy';
        } elseif ($adopted) {
            $stateLabel = __('ADOPTED · REVIEW', 'yablonsky-performance');
            $stateClass = 'yperf-state-attention';
        } elseif ($configured) {
            $stateLabel = $aligned ? __('CONNECTED · ALIGNED', 'yablonsky-performance') : __('CONNECTED', 'yablonsky-performance');
            $stateClass = $aligned ? 'yperf-state-healthy' : 'yperf-state-attention';
        }

        echo '<div class="yperf-card yperf-cloudflare-card"><div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('EDGE DELIVERY', 'yablonsky-performance') . '</span><h2>' . esc_html__('Cloudflare Performance Adapter', 'yablonsky-performance') . '</h2><p>' . esc_html__('Connect Cloudflare as a bounded performance executor while YPerf remains the policy, proof and rollback authority.', 'yablonsky-performance') . '</p></div><span class="yperf-state ' . esc_attr($stateClass) . '">' . esc_html($stateLabel) . '</span></div>';

        echo '<div class="yperf-callout yperf-callout-neutral yperf-cloudflare-permission-note"><strong>' . esc_html__('Least-privilege token', 'yablonsky-performance') . '</strong><span>' . esc_html__('Use a Custom Token for this zone only: Zone Read, Zone Settings Edit and Cache Purge. Do not grant Bot Management, WAF, DNS or account-wide administrator permissions.', 'yablonsky-performance') . '</span></div>';

        echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="yperf-cloudflare-connection-form"><input type="hidden" name="action" value="yperf_save_cloudflare">';
        wp_nonce_field('yperf_save_cloudflare');
        echo '<div class="yperf-cloudflare-fields">';
        echo '<label class="yperf-cloudflare-field"><span>' . esc_html__('Cloudflare API Token', 'yablonsky-performance') . '</span><input type="password" name="yperf_cloudflare_api_token" autocomplete="new-password" placeholder="' . esc_attr($configured ? '•••••• · ' . (string) ($credentials['hint'] ?? '') : __('Paste least-privilege token', 'yablonsky-performance')) . '"></label>';
        echo '<label class="yperf-cloudflare-field"><span>' . esc_html__('Zone ID override (optional)', 'yablonsky-performance') . '</span><input type="text" name="yperf_cloudflare_zone_id" autocomplete="off" value="' . esc_attr((string) ($credentials['zone_override'] ?? '')) . '" placeholder="' . esc_attr__('Auto-discover from this hostname', 'yablonsky-performance') . '"></label>';
        echo '</div><div class="yperf-cloudflare-connection-actions"><button type="submit" class="button yperf-cloudflare-save-button">' . esc_html($configured ? __('Save / rotate connection', 'yablonsky-performance') : __('Save connection', 'yablonsky-performance')) . '</button>';
        if ($configured) {
            echo '<label class="yperf-cloudflare-disconnect"><input type="checkbox" name="yperf_cloudflare_clear" value="1"> <span>' . esc_html__('Disconnect on Save', 'yablonsky-performance') . '</span></label>';
        }
        echo '</div></form>';

        echo '<div class="yperf-cloudflare-action-progress" data-yperf-cloudflare-progress hidden aria-live="polite" aria-atomic="true"><span class="yperf-cloudflare-spinner" aria-hidden="true"></span><div><strong data-yperf-cloudflare-progress-title>' . esc_html__('Working with Cloudflare…', 'yablonsky-performance') . '</strong><span data-yperf-cloudflare-progress-detail>' . esc_html__('Please keep this page open while YPerf completes the request and verifies the result.', 'yablonsky-performance') . '</span></div></div>';

        if ($configured) {
            echo '<div class="yperf-cloudflare-actions">';
            echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" data-yperf-cloudflare-action="inspect" data-yperf-loading-title="' . esc_attr__('Inspecting Cloudflare…', 'yablonsky-performance') . '" data-yperf-loading-detail="' . esc_attr__('Validating the token, discovering the zone, reading performance settings and probing delivery.', 'yablonsky-performance') . '"><input type="hidden" name="action" value="yperf_inspect_cloudflare">';
            wp_nonce_field('yperf_inspect_cloudflare');
            echo '<button type="submit" class="button button-primary">' . esc_html__('Inspect Cloudflare', 'yablonsky-performance') . '</button></form>';
            if ($ready && !$adopted) {
                $applyLabel = $aligned ? __('Adopt aligned profile & enable purge sync', 'yablonsky-performance') : __('Adopt YPerf Cloudflare profile', 'yablonsky-performance');
                echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" data-yperf-cloudflare-action="apply" data-yperf-loading-title="' . esc_attr__('Adopting Cloudflare profile…', 'yablonsky-performance') . '" data-yperf-loading-detail="' . esc_attr__('YPerf is applying only allowlisted performance settings, then proving them by read-back.', 'yablonsky-performance') . '"><input type="hidden" name="action" value="yperf_apply_cloudflare">';
                wp_nonce_field('yperf_apply_cloudflare');
                echo '<button type="submit" class="button button-primary">' . esc_html($applyLabel) . '</button></form>';
            }
            if ($adopted) {
                echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" data-yperf-cloudflare-action="purge" data-yperf-loading-title="' . esc_attr__('Verifying URL purge synchronization…', 'yablonsky-performance') . '" data-yperf-loading-detail="' . esc_attr__('YPerf is purging the homepage URL through its cache authority and the adopted Cloudflare purge bridge.', 'yablonsky-performance') . '"><input type="hidden" name="action" value="yperf_verify_cloudflare_purge">';
                wp_nonce_field('yperf_verify_cloudflare_purge');
                echo '<button type="submit" class="button button-primary">' . esc_html__('Verify homepage URL purge', 'yablonsky-performance') . '</button></form>';
                echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" data-yperf-cloudflare-action="rollback" data-yperf-loading-title="' . esc_attr__('Rolling back YPerf Cloudflare changes…', 'yablonsky-performance') . '" data-yperf-loading-detail="' . esc_attr__('YPerf is restoring only settings it previously changed and will not overwrite unrelated external drift.', 'yablonsky-performance') . '"><input type="hidden" name="action" value="yperf_rollback_cloudflare">';
                wp_nonce_field('yperf_rollback_cloudflare');
                echo '<button type="submit" class="button">' . esc_html__('Rollback YPerf Cloudflare changes', 'yablonsky-performance') . '</button></form>';
            }
            echo '</div>';
        }

        if ($inspected) {
            if ($ready) {
                $alignmentClass = $aligned ? 'yperf-callout-success' : 'yperf-callout-attention';
                $alignmentTitle = $aligned ? __('Performance profile aligned', 'yablonsky-performance') : __('Performance profile needs alignment', 'yablonsky-performance');
                $alignmentBody = $aligned
                    ? ($adopted
                        ? __('Rocket Loader and HTTP/3 match the adopted YPerf profile. Cloudflare purge synchronization is active.', 'yablonsky-performance')
                        : __('Rocket Loader and HTTP/3 already match YPerf. Adoption will establish ownership and enable synchronized Cloudflare purge without unnecessary setting changes.', 'yablonsky-performance'))
                    : sprintf(
                        /* translators: %d: number of Cloudflare settings that differ from YPerf policy */
                        _n('%d setting differs from the YPerf performance policy.', '%d settings differ from the YPerf performance policy.', count($drift), 'yablonsky-performance'),
                        count($drift)
                    );
                echo '<div class="yperf-callout ' . esc_attr($alignmentClass) . ' yperf-cloudflare-alignment"><strong>' . esc_html($alignmentTitle) . '</strong><span>' . esc_html($alignmentBody) . '</span></div>';
            }
            if ($errors !== array()) {
                echo '<div class="yperf-callout yperf-callout-attention"><strong>' . esc_html__('Inspection completed with limited visibility', 'yablonsky-performance') . '</strong><span>' . esc_html(sprintf(
                    /* translators: %d: number of Cloudflare settings that could not be inspected */
                    _n('%d Cloudflare capability could not be read with the current token or plan.', '%d Cloudflare capabilities could not be read with the current token or plan.', count($errors), 'yablonsky-performance'),
                    count($errors)
                )) . '</span></div>';
            }

            $zone = is_array($audit['zone'] ?? null) ? $audit['zone'] : array();
            $settings = is_array($audit['settings'] ?? null) ? $audit['settings'] : array();
            $probe = is_array($audit['probe'] ?? null) ? $audit['probe'] : array();
            echo '<div class="yperf-provider-table yperf-external-capability-list yperf-cloudflare-capability-table"><div class="yperf-provider-head"><span>' . esc_html__('Capability', 'yablonsky-performance') . '</span><span>' . esc_html__('State', 'yablonsky-performance') . '</span><span>' . esc_html__('YPerf policy', 'yablonsky-performance') . '</span></div>';
            $rows = array(
                'rocket_loader' => array(__('Rocket Loader', 'yablonsky-performance'), __('OFF when YPerf owns JavaScript scheduling', 'yablonsky-performance')),
                'http3' => array(__('HTTP/3', 'yablonsky-performance'), __('ON', 'yablonsky-performance')),
                'early_hints' => array(__('Early Hints', 'yablonsky-performance'), __('Observe only until YPerf owns a proven Link-header contract', 'yablonsky-performance')),
                'speed_brain' => array(__('Speed Brain', 'yablonsky-performance'), __('Observe only; it targets subsequent navigation, not first-load PSI', 'yablonsky-performance')),
                'automatic_platform_optimization' => array(__('Cloudflare APO', 'yablonsky-performance'), __('Observe only; avoid hidden second page-cache authority', 'yablonsky-performance')),
            );
            foreach ($rows as $id => $meta) {
                $value = (string) ($settings[$id]['value'] ?? __('Unknown', 'yablonsky-performance'));
                $valueClass = $value === 'on' ? ' is-on' : ($value === 'off' ? ' is-off' : '');
                echo '<div class="yperf-provider-row"><strong>' . esc_html($meta[0]) . '</strong><span class="yperf-provider-state' . esc_attr($valueClass) . '">' . esc_html(strtoupper($value)) . '</span><span class="yperf-provider-policy">' . esc_html($meta[1]) . '</span></div>';
            }
            $encoding = (string) ($probe['content_encoding'] ?? '');
            echo '<div class="yperf-provider-row"><strong>' . esc_html__('Effective compression', 'yablonsky-performance') . '</strong><span class="yperf-provider-state">' . esc_html($encoding !== '' ? strtoupper($encoding) : __('Unknown', 'yablonsky-performance')) . '</span><span class="yperf-provider-policy">' . esc_html__('Provider-native execution; YPerf verifies the real response instead of compressing HTML in PHP.', 'yablonsky-performance') . '</span></div>';
            echo '</div>';
            if ($zone !== array() || $auditTime > 0) {
                echo '<div class="yperf-cloudflare-audit-meta">';
                if ($zone !== array()) {
                    echo '<span><strong>' . esc_html__('Zone', 'yablonsky-performance') . ':</strong> ' . esc_html((string) ($zone['name'] ?? '')) . '</span>';
                }
                if ($auditTime > 0) {
                    echo '<span><strong>' . esc_html__('Last inspected', 'yablonsky-performance') . ':</strong> ' . esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $auditTime)) . '</span>';
                }
                echo '</div>';
            }
        }

        if ($operation !== array() && !empty($operation['message'])) {
            $operationOk = !empty($operation['ok']);
            echo '<div class="yperf-callout ' . ($operationOk ? 'yperf-callout-success' : 'yperf-callout-attention') . '"><strong>' . esc_html__('Last Cloudflare operation', 'yablonsky-performance') . '</strong><span>' . esc_html((string) $operation['message']) . '</span></div>';
        }

        echo '<div class="yperf-callout yperf-callout-attention"><strong>' . esc_html__('Crawler policy is not a YPerf setting', 'yablonsky-performance') . '</strong><span>' . esc_html__('Cloudflare Search / Training / Agent controls, Bot Preference Sync, robots.txt and crawler permissions belong to Yablonsky SEO. YPerf deliberately does not request Bot Management permissions and never changes those settings.', 'yablonsky-performance') . '</span></div>';
        echo '<p class="yperf-benchmark-safety"><strong>' . esc_html__('Rollback contract.', 'yablonsky-performance') . '</strong> ' . esc_html__('Before YPerf changes an adopted Cloudflare performance setting, it records the previous value. Rollback restores only settings that still match the value YPerf applied; external drift is never overwritten blindly.', 'yablonsky-performance') . '</p>';
        echo '</div>';

        echo '<aside class="yperf-card yperf-cloudflare-guide" aria-label="' . esc_attr__('Cloudflare quick setup', 'yablonsky-performance') . '"><div class="yperf-card-head"><div><span class="yperf-eyebrow">' . esc_html__('QUICK SETUP', 'yablonsky-performance') . '</span><h2>' . esc_html__('Cloudflare in four steps', 'yablonsky-performance') . '</h2><p>' . esc_html__('A compact guide for the provider workflow. No Cloudflare expertise is required.', 'yablonsky-performance') . '</p></div></div>';
        echo '<ol class="yperf-cloudflare-steps">';
        self::renderCloudflareGuideStep(1, __('Connect token', 'yablonsky-performance'), __('Use a Custom Token limited to this zone.', 'yablonsky-performance'), $configured ? 'done' : 'current');
        self::renderCloudflareGuideStep(2, __('Inspect Cloudflare', 'yablonsky-performance'), __('YPerf reads the zone settings and effective delivery state.', 'yablonsky-performance'), $ready ? 'done' : ($configured ? 'current' : 'pending'));
        self::renderCloudflareGuideStep(3, __('Adopt performance profile', 'yablonsky-performance'), __('YPerf owns only Rocket Loader OFF, HTTP/3 ON and purge synchronization.', 'yablonsky-performance'), $adopted ? 'done' : ($ready ? 'current' : 'pending'));
        self::renderCloudflareGuideStep(4, __('Optimize, prewarm, test', 'yablonsky-performance'), __('Run Optimize Now, let YPerf prewarm the public cache, then measure the warm site.', 'yablonsky-performance'), $adopted ? 'current' : 'pending');
        echo '</ol>';

        echo '<div class="yperf-cloudflare-guide-section"><strong>' . esc_html__('Token permissions', 'yablonsky-performance') . '</strong><div class="yperf-cloudflare-chip-row"><span>' . esc_html__('Zone · Read', 'yablonsky-performance') . '</span><span>' . esc_html__('Zone Settings · Edit', 'yablonsky-performance') . '</span><span>' . esc_html__('Cache Purge · Purge', 'yablonsky-performance') . '</span></div><small>' . esc_html__('Resource scope: this zone only. No client IP filter is required for normal server-side YPerf use.', 'yablonsky-performance') . '</small></div>';
        echo '<div class="yperf-cloudflare-guide-section"><strong>' . esc_html__('YPerf may change', 'yablonsky-performance') . '</strong><p>' . esc_html__('Rocket Loader, HTTP/3 and synchronized cache purge — only after explicit adoption.', 'yablonsky-performance') . '</p></div>';
        echo '<div class="yperf-cloudflare-guide-section"><strong>' . esc_html__('YPerf never changes', 'yablonsky-performance') . '</strong><p>' . esc_html__('DNS, WAF, Bot Management, AI crawler policy, robots.txt or YSEO crawler signals.', 'yablonsky-performance') . '</p></div>';
        if ($aligned && !$adopted) {
            echo '<div class="yperf-callout yperf-callout-success"><strong>' . esc_html__('Already aligned', 'yablonsky-performance') . '</strong><span>' . esc_html__('Adoption can still be useful: it establishes YPerf ownership and enables synchronized Cloudflare purge without rewriting settings that already match.', 'yablonsky-performance') . '</span></div>';
        }
        echo '</aside>';
    }

    private static function renderCloudflareGuideStep(int $number, string $title, string $body, string $state): void
    {
        $safeState = in_array($state, array('done', 'current', 'pending'), true) ? $state : 'pending';
        $marker = $safeState === 'done' ? '✓' : (string) $number;
        echo '<li class="yperf-cloudflare-step is-' . esc_attr($safeState) . '"><span class="yperf-cloudflare-step-marker" aria-hidden="true">' . esc_html($marker) . '</span><div><strong>' . esc_html($title) . '</strong><span>' . esc_html($body) . '</span></div></li>';
    }

    /** @param array<string,mixed> $telemetry */
    private static function renderTelemetrySummary(array $telemetry): void
    {
        $summary = is_array($telemetry['summary'] ?? null) ? $telemetry['summary'] : array();
        echo '<div class="yperf-telemetry-strip"><div><span>' . esc_html__('Runtime runs · 24h', 'yablonsky-performance') . '</span><strong>' . esc_html((string) ($summary['runtime_runs_24h'] ?? 0)) . '</strong></div><div><span>' . esc_html__('Benchmarks · 24h', 'yablonsky-performance') . '</span><strong>' . esc_html((string) ($summary['benchmark_runs_24h'] ?? 0)) . '</strong></div><div><span>' . esc_html__('Plan applies · 24h', 'yablonsky-performance') . '</span><strong>' . esc_html((string) ($summary['performance_plan_applies_24h'] ?? 0)) . '</strong></div><div><span>' . esc_html__('Purges · 24h', 'yablonsky-performance') . '</span><strong>' . esc_html((string) ($summary['purges_24h'] ?? 0)) . '</strong></div><div><span>' . esc_html__('Prewarm', 'yablonsky-performance') . '</span><strong>' . esc_html((string) ($summary['prewarm_verified_hits_24h'] ?? 0)) . ' HIT</strong></div><div><span>' . esc_html__('Last avg response', 'yablonsky-performance') . '</span><strong>' . esc_html((string) ($summary['last_runtime_avg_ms'] ?? 0)) . ' ms</strong></div></div>';
    }

    /** @param array<string,mixed> $caps @param array<string,mixed> $environment */
    private static function renderBenchmarkPlan(array $caps, array $environment): void
    {
        $redis = self::environmentState($environment, 'redis');
        $memcached = self::environmentState($environment, 'memcached');
        $server = strtoupper((string) ($caps['web_server']['family'] ?? 'unknown'));
        echo '<div class="yperf-benchmark-flow"><span>' . esc_html__('Baseline', 'yablonsky-performance') . '</span><b>→</b><span>' . esc_html__('Candidate runs', 'yablonsky-performance') . '</span><b>→</b><span>' . esc_html__('Compare', 'yablonsky-performance') . '</span><b>→</b><span>' . esc_html__('Explicit Apply', 'yablonsky-performance') . '</span><b>→</b><span>' . esc_html__('Acceptance / rollback', 'yablonsky-performance') . '</span></div>';
        echo '<div class="yperf-benchmark-candidates">';
        self::renderBenchmarkCandidate(__('Native page cache', 'yablonsky-performance'), $server, $server === 'UNKNOWN' ? 'unknown' : 'available', __('Host helper required before a real candidate can be applied.', 'yablonsky-performance'));
        self::renderBenchmarkCandidate(__('Redis', 'yablonsky-performance'), __('Memory engine', 'yablonsky-performance'), $redis, __('Visible even when unavailable; YPerf will never hide the choice.', 'yablonsky-performance'));
        self::renderBenchmarkCandidate(__('Memcached', 'yablonsky-performance'), __('Memory engine', 'yablonsky-performance'), $memcached, __('User may choose it even when another engine benchmarks faster.', 'yablonsky-performance'));
        self::renderBenchmarkCandidate(__('Database Cache', 'yablonsky-performance'), __('Workload-dependent', 'yablonsky-performance'), 'attention', __('Benchmarked separately; never enabled just for feature parity.', 'yablonsky-performance'));
        echo '</div><p class="yperf-benchmark-safety"><strong>' . esc_html__('No “Next = Apply”.', 'yablonsky-performance') . '</strong> ' . esc_html__('Benchmarks and configuration changes remain separate actions. A candidate can be selected by the user even when YPerf recommends another one.', 'yablonsky-performance') . '</p>';
    }

    private static function renderBenchmarkCandidate(string $name, string $kind, string $state, string $note): void
    {
        $safeState = in_array($state, array('ready', 'available', 'attention', 'blocked', 'unknown'), true) ? $state : 'unknown';
        echo '<div class="yperf-benchmark-candidate"><span class="yperf-env-badge yperf-env-badge-' . esc_attr($safeState) . '">' . esc_html(strtoupper($safeState)) . '</span><div><strong>' . esc_html($name) . '</strong><span>' . esc_html($kind) . '</span><small>' . esc_html($note) . '</small></div></div>';
    }

    /** @param array<string,mixed> $environment */
    private static function environmentState(array $environment, string $id): string
    {
        foreach ((array) ($environment['checks'] ?? array()) as $check) {
            if (is_array($check) && (string) ($check['id'] ?? '') === $id) {
                return sanitize_key((string) ($check['state'] ?? 'unknown'));
            }
        }
        return 'unknown';
    }

    /** @param array<string,mixed> $caps */
    private static function pageCacheLabel(array $caps): string
    {
        $known = $caps['page_cache']['known_page_cache_plugins'] ?? array();
        if (is_array($known) && $known !== array()) {
            return implode(', ', $known);
        }
        return !empty($caps['page_cache']['advanced_cache_dropin']) ? __('Drop-in detected', 'yablonsky-performance') : __('Not detected', 'yablonsky-performance');
    }

    /** @param array<string,array<string,mixed>> $features */
    private static function renderFeatureCatalog(array $features): void
    {
        $groups = array(
            __('Cache', 'yablonsky-performance'),
            __('Delivery', 'yablonsky-performance'),
            __('Control', 'yablonsky-performance'),
            __('Protection', 'yablonsky-performance'),
            __('Observability', 'yablonsky-performance'),
        );
        echo '<div class="yperf-feature-filters" role="group" aria-label="' . esc_attr__('Feature groups', 'yablonsky-performance') . '"><button type="button" class="yperf-filter is-active" data-yperf-feature-filter="all">' . esc_html__('All', 'yablonsky-performance') . '</button>';
        foreach ($groups as $group) {
            echo '<button type="button" class="yperf-filter" data-yperf-feature-filter="' . esc_attr(sanitize_key($group)) . '">' . esc_html($group) . '</button>';
        }
        echo '</div>';

        echo '<div class="yperf-feature-section-head"><div><span class="yperf-eyebrow">' . esc_html__('YPERF-OWNED EXECUTION', 'yablonsky-performance') . '</span><h3>' . esc_html__('Controls that actually change YPerf runtime', 'yablonsky-performance') . '</h3></div><span class="yperf-state yperf-state-healthy">' . esc_html__('Apply · Prove · Rollback', 'yablonsky-performance') . '</span></div>';
        echo '<div class="yperf-feature-grid yperf-feature-grid-owned">';
        foreach ($features as $id => $feature) {
            if (!self::isOwnedControlFeature($id)) { continue; }
            self::renderFeatureTile($id, $feature, 'owned');
        }
        echo '</div>';

        echo '<details class="yperf-external-capabilities"><summary><strong>' . esc_html__('Detected / external capabilities', 'yablonsky-performance') . '</strong><span>' . esc_html__('Read-only evidence, migration and diagnostics — not fake YPerf toggles', 'yablonsky-performance') . '</span></summary>';
        echo '<div class="yperf-provider-table yperf-external-capability-list"><div class="yperf-provider-head"><span>' . esc_html__('Capability', 'yablonsky-performance') . '</span><span>' . esc_html__('State', 'yablonsky-performance') . '</span><span>' . esc_html__('Owner / provider', 'yablonsky-performance') . '</span></div>';
        foreach ($features as $id => $feature) {
            if (self::isOwnedControlFeature($id) || in_array((string) ($feature['state'] ?? ''), array('roadmap', 'planned'), true)) { continue; }
            echo '<div class="yperf-provider-row"><strong>' . esc_html((string) ($feature['name'] ?? $id)) . '</strong><span class="yperf-provider-state">' . esc_html((string) ($feature['status'] ?? '')) . '</span><code>' . esc_html((string) ($feature['provider'] ?? '')) . '</code></div>';
        }
        echo '</div><p class="yperf-inline-note">' . esc_html__('External capabilities are inspected in Setup / Diagnostics. They are not presented as YPerf switches until YPerf owns a real executor and rollback contract.', 'yablonsky-performance') . '</p></details>';

        echo '<details class="yperf-external-capabilities yperf-roadmap-capabilities"><summary><strong>' . esc_html__('Future roadmap', 'yablonsky-performance') . '</strong><span>' . esc_html__('Not part of the current YPerf market baseline', 'yablonsky-performance') . '</span></summary>';
        echo '<div class="yperf-provider-table yperf-external-capability-list"><div class="yperf-provider-head"><span>' . esc_html__('Capability', 'yablonsky-performance') . '</span><span>' . esc_html__('State', 'yablonsky-performance') . '</span><span>' . esc_html__('Boundary', 'yablonsky-performance') . '</span></div>';
        foreach ($features as $id => $feature) {
            if (!in_array((string) ($feature['state'] ?? ''), array('roadmap', 'planned'), true)) { continue; }
            echo '<div class="yperf-provider-row"><strong>' . esc_html((string) ($feature['name'] ?? $id)) . '</strong><span class="yperf-provider-state">' . esc_html((string) ($feature['status'] ?? '')) . '</span><code>' . esc_html__('No current-release write authority', 'yablonsky-performance') . '</code></div>';
        }
        echo '</div><p class="yperf-inline-note">' . esc_html__('Roadmap items are intentionally excluded from current release claims until YPerf owns a bounded executor, execution proof and rollback contract.', 'yablonsky-performance') . '</p></details>';
    }

    /** @param array<string,mixed> $feature */
    private static function renderFeatureTile(string $id, array $feature, string $ownership): void
    {
        $group = (string) ($feature['group'] ?? '');
        $state = (string) ($feature['state'] ?? 'planned');
        echo '<button type="button" class="yperf-feature-tile yperf-feature-' . esc_attr($ownership) . '" data-yperf-feature="' . esc_attr($id) . '" data-yperf-feature-group="' . esc_attr(sanitize_key($group)) . '">';
        echo '<span class="yperf-feature-top"><span class="yperf-feature-name">' . esc_html((string) ($feature['name'] ?? '')) . '</span><span class="yperf-feature-state yperf-feature-state-' . esc_attr($state) . '">' . esc_html((string) ($feature['status'] ?? '')) . '</span></span>';
        echo '<span class="yperf-feature-provider">' . esc_html((string) ($feature['provider'] ?? '')) . '</span>';
        echo '<span class="yperf-feature-owner">' . esc_html($ownership === 'owned' ? __('YPerf control', 'yablonsky-performance') : __('Read-only / external', 'yablonsky-performance')) . '</span>';
        echo '<span class="yperf-feature-arrow" aria-hidden="true">→</span></button>';
    }

    private static function isOwnedControlFeature(string $id): bool
    {
        return in_array($id, array('page-cache', 'object-cache', 'browser-cache', 'frontend-optimizer', 'first-hit-guard', 'selective-purge', 'prewarm'), true);
    }

    /** @param array<string,array<string,mixed>> $features */
    private static function renderFeatureTemplates(array $features): void
    {
        echo '<div class="yperf-feature-templates" hidden>';
        foreach ($features as $id => $feature) {
            $state = (string) ($feature['state'] ?? 'planned');
            echo '<template id="yperf-feature-template-' . esc_attr($id) . '"><div class="yperf-feature-detail">';
            echo '<span class="yperf-feature-state yperf-feature-state-' . esc_attr($state) . '">' . esc_html((string) ($feature['status'] ?? '')) . '</span>';
            echo '<h2 id="yperf-modal-title">' . esc_html((string) ($feature['name'] ?? '')) . '</h2>';
            echo '<p class="yperf-feature-detail-provider"><span>' . esc_html__('Current provider / execution', 'yablonsky-performance') . '</span><strong>' . esc_html((string) ($feature['provider'] ?? '')) . '</strong></p>';
            echo '<div class="yperf-feature-detail-block"><h3>' . esc_html__('What it does', 'yablonsky-performance') . '</h3><p>' . esc_html((string) ($feature['what'] ?? '')) . '</p></div>';
            echo '<div class="yperf-feature-detail-block"><h3>' . esc_html__('YPerf policy', 'yablonsky-performance') . '</h3><p>' . esc_html((string) ($feature['policy'] ?? '')) . '</p></div>';
            echo '<div class="yperf-feature-detail-block"><h3>' . esc_html__('Next safe step', 'yablonsky-performance') . '</h3><p>' . esc_html((string) ($feature['next'] ?? '')) . '</p></div>';
            self::renderFeatureActions($id);
            if (!empty($feature['local_free'])) {
                echo '<div class="yperf-free-note"><strong>' . esc_html__('Local / self-hosted functionality: free.', 'yablonsky-performance') . '</strong><span>' . esc_html__('External infrastructure or third-party services may still have their own costs.', 'yablonsky-performance') . '</span></div>';
            }
            echo '</div></template>';
        }
        echo '</div>';
    }

    private static function renderFeatureActions(string $id): void
    {
        $config = array();
        if ($id === 'page-cache') {
            $enabled = PageCacheExecutor::enabled();
            $config = array('enabled' => $enabled, 'label_on' => __('Disable Page Cache', 'yablonsky-performance'), 'label_off' => __('Enable & Prove Page Cache', 'yablonsky-performance'));
        } elseif ($id === 'object-cache') {
            $status = ObjectCacheExecutor::status();
            $objectState = (string) ($status['state'] ?? '');
            $ownedDropinNeedsRollback = !empty($status['dropin_owned']) && in_array($objectState, array('upgrade_reactivation_required', 'dropin_present_not_active'), true);
            if ($objectState === 'active' || $ownedDropinNeedsRollback) {
                $config = array('enabled' => true, 'operation' => 'disable', 'label_on' => __('Disable & Roll Back Object Cache', 'yablonsky-performance'), 'label_off' => '');
            } else {
                $host = RedisEvidence::resolve(HostSnapshot::last());
                $dropins = DropinOwnership::inspect();
                $runtime = RuntimeAcceptance::lastReport();
                $benchmark = Benchmark::lastReport();
                $adoption = ObjectCacheAdoption::analyze($host, $dropins, $runtime, $benchmark);
                $contract = ObjectCacheOperationContract::build($adoption, $host, $dropins, $runtime);
                $ready = (string) ($contract['state'] ?? '') === 'operation_contract_ready';
                $config = array('enabled' => false, 'operation' => $ready ? 'enable' : 'prepare', 'label_on' => '', 'label_off' => $ready ? __('Activate & Prove Object Cache', 'yablonsky-performance') : __('Prepare Redis Object Cache', 'yablonsky-performance'));
            }
        } elseif ($id === 'browser-cache') {
            $enabled = BrowserCacheController::enabled();
            $config = array('enabled' => $enabled, 'label_on' => __('Disable Browser Cache policy', 'yablonsky-performance'), 'label_off' => __('Enable & Prove Browser Cache policy', 'yablonsky-performance'));
        } elseif ($id === 'frontend-optimizer') {
            $enabled = FrontendOptimizer::deliveryEngineEnabled();
            $config = array('enabled' => $enabled, 'operation' => $enabled ? 'disable' : 'enable', 'label_on' => __('Disable Frontend Delivery Engine', 'yablonsky-performance'), 'label_off' => __('Enable & Prove Frontend Delivery Engine', 'yablonsky-performance'));
        } elseif ($id === 'first-hit-guard') {
            $enabled = get_option('yperf_deep_archive_guard', '0') === '1';
            $config = array('enabled' => $enabled, 'label_on' => __('Disable First-hit Query Guard', 'yablonsky-performance'), 'label_off' => __('Enable & Prove First-hit Query Guard', 'yablonsky-performance'));
        }
        if ($config === array()) {
            $tab = in_array($id, array('cache-statistics', 'purge-audit', 'pagespeed', 'query-cost-telemetry'), true) ? 'insights' : 'setup';
            if (in_array($id, array('selective-purge', 'prewarm'), true)) {
                $tab = 'control';
            }
            echo '<div class="yperf-feature-control-form yperf-feature-readonly-action"><button type="button" class="button" data-yperf-open-tab="' . esc_attr($tab) . '">' . esc_html(in_array($id, array('selective-purge', 'prewarm'), true) ? __('Open operation controls', 'yablonsky-performance') : __('Open evidence / migration', 'yablonsky-performance')) . '</button>';
            echo '<span>' . esc_html(self::isOwnedControlFeature($id) ? __('This YPerf capability is operated from its dedicated bounded workflow.', 'yablonsky-performance') : __('This tile is evidence only. YPerf does not claim ownership of an external runtime layer.', 'yablonsky-performance')) . '</span></div>';
            return;
        }
        $operation = isset($config['operation']) ? sanitize_key((string) $config['operation']) : (!empty($config['enabled']) ? 'disable' : 'enable');
        $label = !empty($config['enabled']) ? (string) $config['label_on'] : (string) $config['label_off'];
        echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post" class="yperf-feature-control-form">';
        wp_nonce_field('yperf_control_feature');
        echo '<input type="hidden" name="action" value="yperf_control_feature">';
        echo '<input type="hidden" name="yperf_feature" value="' . esc_attr($id) . '">';
        echo '<input type="hidden" name="yperf_operation" value="' . esc_attr($operation) . '">';
        echo '<button type="submit" class="button ' . (!empty($config['enabled']) ? '' : 'button-primary') . '">' . esc_html($label) . '</button>';
        echo '<span>' . esc_html__('Every change is followed by Runtime Acceptance. A functional failure rolls the change back automatically.', 'yablonsky-performance') . '</span>';
        echo '</form>';
    }

    /** @param array<string,mixed> $caps */
    private static function renderStackSummary(array $caps, bool $guardEnabled, int $attention, array $effectiveness): void
    {
        $integrations = array();
        foreach (array('woocommerce' => 'WooCommerce', 'ypm' => 'YMulti', 'yps' => 'YSEO', 'wpml' => 'WPML', 'polylang' => 'Polylang') as $key => $label) {
            if (!empty($caps['application'][$key])) {
                $integrations[] = $label;
            }
        }
        $layers = is_array($effectiveness['layers'] ?? null) ? $effectiveness['layers'] : array();
        echo '<div class="yperf-stack-lines">';
        self::renderStackLine(__('Web server', 'yablonsky-performance'), strtoupper((string) ($caps['web_server']['family'] ?? 'unknown')), 'neutral');
        foreach (array('page_cache' => __('Page cache', 'yablonsky-performance'), 'object_cache' => __('Object cache', 'yablonsky-performance'), 'opcache' => __('PHP OPcache', 'yablonsky-performance'), 'first_hit_guard' => __('First-hit guard', 'yablonsky-performance')) as $key => $label) {
            $layer = isset($layers[$key]) && is_array($layers[$key]) ? $layers[$key] : array();
            $state = sanitize_key((string) ($layer['state'] ?? ($key === 'first_hit_guard' && $guardEnabled ? 'armed' : 'not_proven')));
            $value = self::executionStateLabel($state);
            $textState = $state === 'active' ? 'healthy' : (in_array($state, array('armed', 'needs_review'), true) ? 'attention' : 'neutral');
            self::renderStackLine($label, $value, $textState);
        }
        echo '</div>';
        echo '<div class="yperf-stack-foot"><span>' . esc_html__('Integrations', 'yablonsky-performance') . '</span><div class="yperf-chip-row">';
        if ($integrations === array()) {
            echo '<span class="yperf-chip">' . esc_html__('None detected', 'yablonsky-performance') . '</span>';
        } else {
            foreach ($integrations as $integration) {
                echo '<span class="yperf-chip">' . esc_html($integration) . '</span>';
            }
        }
        echo '</div></div>';
        if ($attention > 0) {
            echo '<div class="yperf-callout yperf-callout-attention"><strong>' . esc_html__('Attention signal', 'yablonsky-performance') . '</strong><span>' . esc_html(sprintf(
                /* translators: %d: number of attention signals. */
                _n('%d response needs visibility before cache adoption.', '%d responses need visibility before cache adoption.', $attention, 'yablonsky-performance'),
                $attention
            )) . '</span></div>';
        }
    }

    private static function renderStackLine(string $label, string $value, string $state): void
    {
        echo '<div class="yperf-stack-line"><span>' . esc_html($label) . '</span><strong class="yperf-text-' . esc_attr($state) . '">' . esc_html($value) . '</strong></div>';
    }

    /** @param array<string,mixed> $report */
    private static function renderRuntimeSnapshot(array $report): void
    {
        if ($report === array()) {
            echo '<div class="yperf-empty"><strong>' . esc_html__('No runtime report yet', 'yablonsky-performance') . '</strong><span>' . esc_html__('Start the check above. Progress will be shown here step by step.', 'yablonsky-performance') . '</span></div>';
            return;
        }
        $summary = is_array($report['summary'] ?? null) ? $report['summary'] : array();
        $fail = (int) ($summary['fail'] ?? 0);
        $skip = (int) ($summary['skip'] ?? 0);
        $attention = (int) ($summary['attention'] ?? 0);
        echo '<div class="yperf-result-summary">';
        echo '<div><span>' . esc_html__('Passed', 'yablonsky-performance') . '</span><strong>' . esc_html((string) ($summary['pass'] ?? 0)) . '</strong></div>';
        echo '<div><span>' . esc_html__('Failed', 'yablonsky-performance') . '</span><strong>' . esc_html((string) $fail) . '</strong></div>';
        echo '<div><span>' . esc_html__('Skipped', 'yablonsky-performance') . '</span><strong>' . esc_html((string) $skip) . '</strong></div>';
        echo '<div><span>' . esc_html__('Attention', 'yablonsky-performance') . '</span><strong>' . esc_html((string) $attention) . '</strong></div>';
        echo '</div>';
        echo '<div class="yperf-compact-results">';
        $shown = 0;
        foreach ((array) ($report['tests'] ?? array()) as $test) {
            if (!is_array($test)) {
                continue;
            }
            $status = (string) ($test['status'] ?? 'fail');
            if ($status === 'pass' && empty($test['attention'])) {
                continue;
            }
            self::renderCompactResult($test);
            ++$shown;
            if ($shown >= 4) {
                break;
            }
        }
        if ($shown === 0) {
            echo '<div class="yperf-good-line"><span class="dashicons dashicons-yes-alt"></span><span>' . esc_html__('All critical checks passed. Open Diagnostics for the full report.', 'yablonsky-performance') . '</span></div>';
        }
        echo '</div>';
    }

    /** @param array<string,mixed> $test */
    private static function renderCompactResult(array $test): void
    {
        $status = (string) ($test['status'] ?? 'fail');
        $label = !empty($test['attention']) ? __('ATTENTION', 'yablonsky-performance') : strtoupper($status);
        $class = !empty($test['attention']) ? 'attention' : $status;
        echo '<div class="yperf-compact-row"><span class="yperf-badge yperf-badge-' . esc_attr($class) . '">' . esc_html($label) . '</span><div><strong>' . esc_html((string) ($test['name'] ?? '')) . '</strong><span>' . esc_html(!empty($test['attention']) ? (string) ($test['attention_note'] ?? '') : (string) ($test['details'] ?? '')) . '</span></div></div>';
    }

    /** @param array<string,mixed> $report */
    private static function renderRuntimeTable(array $report): void
    {
        if ($report === array()) {
            echo '<div class="yperf-empty"><strong>' . esc_html__('No report available', 'yablonsky-performance') . '</strong><span>' . esc_html__('Run Runtime Acceptance from Overview first.', 'yablonsky-performance') . '</span></div>';
            return;
        }
        echo '<div class="yperf-table-scroll"><table class="widefat striped yperf-table"><thead><tr><th>' . esc_html__('Status', 'yablonsky-performance') . '</th><th>' . esc_html__('Test', 'yablonsky-performance') . '</th><th>' . esc_html__('HTTP / time', 'yablonsky-performance') . '</th><th>' . esc_html__('Details', 'yablonsky-performance') . '</th></tr></thead><tbody>';
        foreach ((array) ($report['tests'] ?? array()) as $test) {
            if (!is_array($test)) {
                continue;
            }
            $status = (string) ($test['status'] ?? 'fail');
            echo '<tr><td><span class="yperf-badge yperf-badge-' . esc_attr($status) . '">' . esc_html(strtoupper($status)) . '</span>';
            if (!empty($test['attention'])) {
                echo '<span class="yperf-badge yperf-badge-attention">' . esc_html__('ATTN', 'yablonsky-performance') . '</span>';
            }
            echo '</td><td><strong>' . esc_html((string) ($test['name'] ?? '')) . '</strong>';
            if (!empty($test['url'])) {
                echo '<small class="yperf-url">' . esc_html((string) $test['url']) . '</small>';
            }
            echo '</td><td>';
            if (isset($test['http_code'])) {
                echo '<strong>' . esc_html((string) $test['http_code']) . '</strong>';
            } else {
                echo '—';
            }
            if (isset($test['ms'])) {
                echo '<small>' . esc_html((string) $test['ms']) . ' ms</small>';
            }
            echo '</td><td><span>' . esc_html((string) ($test['details'] ?? '')) . '</span>';
            if (!empty($test['attention_note'])) {
                echo '<small class="yperf-attention-note">' . esc_html((string) $test['attention_note']) . '</small>';
            }
            if (!empty($test['cache_signals']) && is_array($test['cache_signals'])) {
                echo '<details><summary>' . esc_html__('Cache signals', 'yablonsky-performance') . '</summary><code>' . esc_html((string) wp_json_encode($test['cache_signals'])) . '</code></details>';
            }
            if (!empty($test['origin_metrics']) && is_array($test['origin_metrics'])) {
                $origin = $test['origin_metrics'];
                if ((string) ($origin['state'] ?? '') === 'measured') {
                    echo '<details><summary>' . esc_html__('Origin cost', 'yablonsky-performance') . '</summary><code>' . esc_html(sprintf(
                        /* translators: 1: query count, 2: DB ms, 3: max query ms, 4: slow query count */
                        __('%1$d queries · %2$.1f ms DB · slowest %3$.1f ms · ≥100 ms: %4$d', 'yablonsky-performance'),
                        (int) ($origin['query_count'] ?? 0),
                        (float) ($origin['db_time_ms'] ?? 0),
                        (float) ($origin['max_query_ms'] ?? 0),
                        (int) ($origin['slow_queries_100ms'] ?? 0)
                    )) . '</code>';
                    $hotspots = QueryCostTelemetry::sanitizeHotspots($origin['hotspots'] ?? array());
                    if ($hotspots !== array()) {
                        echo '<div class="yperf-origin-hotspots"><strong>' . esc_html__('SQL Hotspots — slow query shapes', 'yablonsky-performance') . '</strong>';
                        echo '<p class="description">' . esc_html__('Signed probes only. Ranked by cumulative database time; no SQL text, arguments or caller paths are exported.', 'yablonsky-performance') . '</p>';
                        echo '<table class="widefat striped yperf-hotspots-table"><thead><tr>';
                        foreach (array(__('Fingerprint', 'yablonsky-performance'), __('Query family', 'yablonsky-performance'), __('Source category', 'yablonsky-performance'), __('Calls', 'yablonsky-performance'), __('Total ms', 'yablonsky-performance'), __('Peak ms', 'yablonsky-performance'), __('Query shape', 'yablonsky-performance')) as $heading) {
                            echo '<th scope="col">' . esc_html($heading) . '</th>';
                        }
                        echo '</tr></thead><tbody>';
                        foreach ($hotspots as $hotspot) {
                            $flags = array();
                            foreach (array('found_rows' => 'FOUND_ROWS', 'order_by' => 'ORDER BY', 'group_by' => 'GROUP BY', 'joins' => 'JOIN', 'has_limit' => 'LIMIT') as $key => $label) {
                                if (!empty($hotspot[$key])) {
                                    $flags[] = $label;
                                }
                            }
                            echo '<tr><td><code>' . esc_html(substr((string) $hotspot['signature_sha256'], 0, 12)) . '</code></td>';
                            echo '<td>' . esc_html((string) $hotspot['family']) . '</td>';
                            echo '<td>' . esc_html((string) $hotspot['caller_class']) . '</td>';
                            echo '<td>' . esc_html(number_format_i18n((int) $hotspot['count'])) . '</td>';
                            echo '<td>' . esc_html(number_format_i18n((float) $hotspot['total_ms'], 1)) . '</td>';
                            echo '<td>' . esc_html(number_format_i18n((float) $hotspot['max_ms'], 1)) . '</td>';
                            echo '<td>' . esc_html($flags !== array() ? implode(' · ', $flags) : '—') . '</td></tr>';
                        }
                        echo '</tbody></table></div>';
                    }
                    echo '</details>';
                } elseif ((string) ($origin['state'] ?? '') === 'not_executed_cache_hit') {
                    echo '<small class="yperf-runtime-origin">' . esc_html__('Origin cost not measured: this probe was served by a cache layer before WordPress executed.', 'yablonsky-performance') . '</small>';
                }
            }
            echo '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    /** Download the stored, bounded SQL evidence directly from WordPress. No AJAX/Blob dependencies. */
    public static function downloadSqlHotspots(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Access denied.', 'yablonsky-performance'), '', array('response' => 403));
        }
        check_admin_referer('yperf_download_sql_hotspots');
        $report = get_option('yperf_runtime_acceptance_v1', array());
        if (!is_array($report) || empty($report['tests']) || !is_array($report['tests'])) {
            wp_die(esc_html__('Saved report is unavailable.', 'yablonsky-performance'), '', array('response' => 404));
        }
        $routes = array();
        foreach ($report['tests'] as $test) {
            if (!is_array($test)) {
                continue;
            }
            $metrics = $test['origin_metrics'] ?? null;
            if (!is_array($metrics) || ($metrics['state'] ?? '') !== 'measured') {
                continue;
            }
            $hotspots = array();
            foreach ((array) ($metrics['hotspots'] ?? array()) as $h) {
                if (!is_array($h)) {
                    continue;
                }
                $hotspots[] = array(
                    'signature_sha256' => (string) ($h['signature_sha256'] ?? ''),
                    'family' => (string) ($h['family'] ?? ''),
                    'caller_class' => (string) ($h['caller_class'] ?? ''),
                    'count' => (int) ($h['count'] ?? 0),
                    'total_ms' => (float) ($h['total_ms'] ?? 0),
                    'max_ms' => (float) ($h['max_ms'] ?? 0),
                    'found_rows' => !empty($h['found_rows']),
                    'order_by' => !empty($h['order_by']),
                    'group_by' => !empty($h['group_by']),
                    'joins' => !empty($h['joins']),
                    'has_limit' => !empty($h['has_limit']),
                );
            }
            $routes[] = array(
                'route' => (string) ($test['name'] ?? ''),
                'query_count' => (int) ($metrics['query_count'] ?? 0),
                'db_time_ms' => (float) ($metrics['db_time_ms'] ?? 0),
                'max_query_ms' => (float) ($metrics['max_query_ms'] ?? 0),
                'php_elapsed_ms' => (float) ($metrics['php_elapsed_ms'] ?? 0),
                'slow_queries_100ms' => (int) ($metrics['slow_queries_100ms'] ?? 0),
                'sql_calc_found_rows_count' => (int) ($metrics['sql_calc_found_rows_count'] ?? 0),
                'hotspots' => $hotspots,
            );
        }
        $payload = array(
            'schema' => 1,
            'source' => 'Yablonsky Performance / saved Runtime Acceptance',
            'version' => (string) ($report['version'] ?? ''),
            'generated_at' => $report['generated_at'] ?? null,
            'origin_cost_summary' => is_array($report['origin_cost_summary'] ?? null) ? $report['origin_cost_summary'] : array(),
            'routes' => $routes,
            'raw_sql_exported' => false,
        );
        $json = wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            wp_die(esc_html__('SQL export is unavailable.', 'yablonsky-performance'), '', array('response' => 500));
        }
        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="yperf-sql-hotspots-' . gmdate('Ymd-His') . '.json"');
        header('X-Content-Type-Options: nosniff');
        echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON file response, not HTML.
        exit;
    }

    private static function renderRuntimeExportActions(): void
    {
        echo '<button type="button" class="button" data-yperf-action="copy-report">' . esc_html__('Copy as text', 'yablonsky-performance') . '</button>';
        echo '<button type="button" class="button button-primary" data-yperf-action="download-json">' . esc_html__('Download JSON', 'yablonsky-performance') . '</button>';
        echo '<button type="button" class="button" data-yperf-action="download-txt">' . esc_html__('Download TXT', 'yablonsky-performance') . '</button>';
    }

    /** @param array<int,mixed> $events */
    private static function renderInvalidationLog(array $events): void
    {
        if ($events === array()) {
            echo '<div class="yperf-empty"><strong>' . esc_html__('No purge events yet', 'yablonsky-performance') . '</strong><span>' . esc_html__('The audit is ready and will keep the 50 most recent semantic invalidation requests.', 'yablonsky-performance') . '</span></div>';
            return;
        }
        echo '<div class="yperf-table-scroll yperf-purge-scroll"><table class="widefat striped yperf-table"><thead><tr><th>' . esc_html__('Time', 'yablonsky-performance') . '</th><th>' . esc_html__('Type', 'yablonsky-performance') . '</th><th>' . esc_html__('Context / URLs', 'yablonsky-performance') . '</th></tr></thead><tbody>';
        foreach (array_slice($events, 0, 50) as $event) {
            if (!is_array($event)) {
                continue;
            }
            $payload = is_array($event['payload'] ?? null) ? $event['payload'] : array();
            $urls = is_array($payload['urls'] ?? null) ? $payload['urls'] : array();
            $context = (string) ($payload['context'] ?? '');
            $time = isset($event['time']) ? wp_date('Y-m-d H:i:s', (int) $event['time']) : '—';
            echo '<tr><td><code>' . esc_html($time) . '</code></td><td>' . esc_html((string) ($event['type'] ?? '')) . '</td><td>';
            if ($context !== '') {
                echo '<strong>' . esc_html($context) . '</strong>';
            }
            if ($urls !== array()) {
                echo '<small>' . esc_html(implode(' · ', array_map('strval', array_slice($urls, 0, 4)))) . (count($urls) > 4 ? ' …' : '') . '</small>';
            }
            echo '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    /** @param array<string,mixed> $caps */
    private static function renderCapabilityTable(array $caps): void
    {
        $rows = array(
            __('Web server', 'yablonsky-performance') => (string) ($caps['web_server']['family'] ?? 'unknown'),
            __('Server software', 'yablonsky-performance') => (string) ($caps['web_server']['software'] ?? 'unknown'),
            __('WP_CACHE', 'yablonsky-performance') => !empty($caps['page_cache']['wp_cache_constant']) ? 'yes' : 'no',
            __('advanced-cache.php', 'yablonsky-performance') => !empty($caps['page_cache']['advanced_cache_dropin']) ? 'present' : 'absent',
            __('Object cache drop-in', 'yablonsky-performance') => !empty($caps['object_cache']['dropin_present']) ? 'present' : 'absent',
            __('External object cache', 'yablonsky-performance') => !empty($caps['object_cache']['external_enabled']) ? 'enabled' : 'not confirmed',
            __('PHP SAPI', 'yablonsky-performance') => (string) ($caps['origin']['php_sapi'] ?? 'unknown'),
            __('PHP OPcache', 'yablonsky-performance') => !empty($caps['opcode_cache']['enabled']) ? 'enabled' : 'not confirmed',
            __('WooCommerce', 'yablonsky-performance') => !empty($caps['application']['woocommerce']) ? 'detected' : 'not detected',
            __('YMulti', 'yablonsky-performance') => !empty($caps['application']['ypm']) ? 'detected' : 'not detected',
            __('YSEO', 'yablonsky-performance') => !empty($caps['application']['yps']) ? 'detected' : 'not detected',
            __('WPML', 'yablonsky-performance') => !empty($caps['application']['wpml']) ? 'detected' : 'not detected',
            __('Polylang', 'yablonsky-performance') => !empty($caps['application']['polylang']) ? 'detected' : 'not detected',
        );
        echo '<div class="yperf-table-scroll yperf-cap-scroll"><table class="widefat striped yperf-table"><tbody>';
        foreach ($rows as $label => $value) {
            echo '<tr><th>' . esc_html($label) . '</th><td><code>' . esc_html($value) . '</code></td></tr>';
        }
        echo '</tbody></table></div>';
    }

    /** @param array<string,mixed> $history */
    private static function renderRuntimeHistorySummary(array $history): void
    {
        $baselines = is_array($history['route_baselines'] ?? null) ? $history['route_baselines'] : array();
        echo '<div class="yperf-history-block"><div class="yperf-history-head"><strong>' . esc_html__('Health History', 'yablonsky-performance') . '</strong><span>' . esc_html(sprintf(
            /* translators: 1: healthy run count, 2: recent run count */
            __('%1$d healthy / %2$d recent checks', 'yablonsky-performance'),
            (int) ($history['recent_healthy_runs'] ?? 0),
            (int) ($history['recent_runs'] ?? 0)
        )) . '</span></div>';
        if ($baselines === array()) {
            echo '<p class="yperf-inline-note">' . esc_html__('Health history builds automatically as YPerf checks and optimizes the site. A single sample is never treated as a permanent baseline.', 'yablonsky-performance') . '</p></div>';
            return;
        }
        echo '<div class="yperf-history-grid">';
        foreach (array_slice($baselines, 0, 6) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $stability = sanitize_key((string) ($row['stability'] ?? 'variable'));
            echo '<div class="yperf-history-route"><span>' . esc_html((string) ($row['name'] ?? '')) . '</span><strong>' . esc_html((string) ((int) ($row['median_ms'] ?? 0))) . ' ms</strong><small class="is-' . esc_attr($stability) . '">' . esc_html(strtoupper($stability)) . ' · ' . esc_html((string) ((int) ($row['samples'] ?? 0))) . ' ' . esc_html__('samples', 'yablonsky-performance') . '</small></div>';
        }
        echo '</div><p class="yperf-inline-note">' . esc_html__('History is bounded and local. It is used to distinguish persistent regressions from ordinary server/network noise.', 'yablonsky-performance') . '</p></div>';
    }

    /** @param array<string,mixed> $report */
    private static function renderIntelligenceSummary(array $report): void
    {
        $environment = is_array($report['environment'] ?? null) ? $report['environment'] : array();
        $caps = is_array($report['capabilities'] ?? null) ? $report['capabilities'] : array();
        $runtime = is_array($report['runtime_acceptance'] ?? null) ? $report['runtime_acceptance'] : array();
        $extensions = is_array($environment['extensions'] ?? null) ? $environment['extensions'] : array();
        $availableEngines = array();
        foreach (array('redis' => 'Redis', 'memcached' => 'Memcached', 'memcache' => 'Memcache', 'opcache' => 'OPcache', 'brotli' => 'Brotli') as $key => $label) {
            if (!empty($extensions[$key])) {
                $availableEngines[] = $label;
            }
        }
        $runtimeSummary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        echo '<div class="yperf-intelligence-summary">';
        echo '<div><span>' . esc_html__('Runtime', 'yablonsky-performance') . '</span><strong>' . esc_html((string) ($environment['php_sapi'] ?? 'unknown')) . '</strong><small>PHP ' . esc_html((string) ($environment['php_version'] ?? 'unknown')) . '</small></div>';
        echo '<div><span>' . esc_html__('Server', 'yablonsky-performance') . '</span><strong>' . esc_html(strtoupper((string) ($caps['web_server']['family'] ?? 'unknown'))) . '</strong><small>WordPress ' . esc_html((string) ($environment['wordpress_version'] ?? 'unknown')) . '</small></div>';
        echo '<div><span>' . esc_html__('Engines visible', 'yablonsky-performance') . '</span><strong>' . esc_html((string) count($availableEngines)) . '</strong><small>' . esc_html($availableEngines !== array() ? implode(' · ', $availableEngines) : __('No optional engines confirmed', 'yablonsky-performance')) . '</small></div>';
        echo '<div><span>' . esc_html__('Acceptance', 'yablonsky-performance') . '</span><strong>' . esc_html((string) ($runtimeSummary['pass'] ?? 0)) . ' / ' . esc_html((string) ($runtimeSummary['fail'] ?? 0)) . '</strong><small>' . esc_html__('pass / fail', 'yablonsky-performance') . '</small></div>';
        echo '</div>';
        echo '<div class="yperf-intelligence-note"><strong>' . esc_html__('Think of it as PageSpeed Insights with access under the hood.', 'yablonsky-performance') . '</strong><span>' . esc_html__('The report can correlate frontend timing with WordPress, cache ownership, PHP/runtime capabilities and application integrations instead of seeing only the public page.', 'yablonsky-performance') . '</span></div>';
    }

    /** @param array<int,mixed> $items */
    private static function renderRecommendations(array $items): void
    {
        $dismissed = get_option('yperf_dismissed_recommendations_v1', array());
        if (!is_array($dismissed)) {
            $dismissed = array();
        }
        $visible = array();
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = sanitize_key((string) ($item['id'] ?? ''));
            if ($id !== '' && isset($dismissed[$id])) {
                continue;
            }
            $visible[] = $item;
        }
        if ($visible === array()) {
            echo '<div class="yperf-empty"><strong>' . esc_html__('No immediate recommendations', 'yablonsky-performance') . '</strong><span>' . esc_html__('No concrete local or ecosystem opportunity needs your attention right now.', 'yablonsky-performance') . '</span></div>';
            return;
        }
        echo '<div class="yperf-recommendation-list">';
        foreach ($visible as $item) {
            $state = sanitize_html_class((string) ($item['state'] ?? 'optional'));
            $id = sanitize_key((string) ($item['id'] ?? ''));
            echo '<div class="yperf-recommendation yperf-recommendation-' . esc_attr($state) . '">';
            echo '<div><span class="yperf-recommendation-type">' . esc_html(ucfirst((string) ($item['type'] ?? 'insight'))) . '</span><strong>' . esc_html((string) ($item['title'] ?? '')) . '</strong><p>' . esc_html((string) ($item['reason'] ?? '')) . '</p></div>';
            echo '<div class="yperf-recommendation-side"><span class="yperf-recommendation-action">' . esc_html((string) ($item['action'] ?? '')) . '</span>';
            if ($id !== '') {
                echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post"><input type="hidden" name="action" value="yperf_dismiss_recommendation"><input type="hidden" name="yperf_recommendation_id" value="' . esc_attr($id) . '">';
                wp_nonce_field('yperf_dismiss_recommendation');
                echo '<button type="submit" class="button-link yperf-dismiss-link">' . esc_html__('Dismiss', 'yablonsky-performance') . '</button></form>';
            }
            echo '</div></div>';
        }
        echo '</div>';
    }

    public static function renderToolbarNotice(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $message = get_transient(self::noticeKey('toolbar_message'));
        if (!is_string($message) || $message === '') {
            return;
        }
        delete_transient(self::noticeKey('toolbar_message'));
        $level = get_transient(self::noticeKey('toolbar_message_level'));
        delete_transient(self::noticeKey('toolbar_message_level'));
        $level = in_array($level, array('success', 'warning', 'error'), true) ? $level : 'warning';
        echo '<div class="notice notice-' . esc_attr($level) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }

    private static function renderNotices(): void
    {
        foreach (array('adopted' => __('Read-only adoption snapshot captured.', 'yablonsky-performance'), 'runtime' => __('Runtime Acceptance completed.', 'yablonsky-performance'), 'environment' => __('Environment Check completed.', 'yablonsky-performance'), 'host_import' => __('Host Intelligence snapshot imported.', 'yablonsky-performance'), 'host_clear' => __('Host Intelligence snapshot cleared.', 'yablonsky-performance'), 'performance_plan' => __('Performance plan applied and Runtime Acceptance passed.', 'yablonsky-performance'), 'performance_plan_rollback' => __('Performance plan produced a Runtime Acceptance failure and was rolled back automatically.', 'yablonsky-performance'), 'performance_regression_rollback' => __('A material performance regression repeated on the confirmation pass. YPerf rolled the profile back automatically.', 'yablonsky-performance'), 'autopilot' => __('Performance Autopilot completed and the selected local profile was accepted.', 'yablonsky-performance'), 'autopilot_blocked' => __('Performance Autopilot is blocked until a clean current-version Runtime Acceptance exists.', 'yablonsky-performance'), 'ai_settings_saved' => __('Yablonsky AI connection settings saved.', 'yablonsky-performance'), 'ai_connection_verified' => __('Yablonsky AI connection verified. The current workspace is ready for explicit AI requests.', 'yablonsky-performance'), 'ai_advisor_ready' => __('AI Advisor created a recommendation. Review the validated plan before Apply.', 'yablonsky-performance'), 'ai_advisor_answered' => __('AI Advisor answered the performance question.', 'yablonsky-performance'), 'ai_advisor_applied' => __('AI-assisted performance plan was accepted after Runtime Acceptance.', 'yablonsky-performance'), 'inspect' => __('Cacheability inspection completed.', 'yablonsky-performance'), 'purge' => __('Purge operation completed.', 'yablonsky-performance'), 'native_page_cache_adopted' => __('Proven Nginx page cache adopted as the local authoritative executor. No host configuration was changed.', 'yablonsky-performance'), 'native_page_cache_released' => __('Native page cache adoption record released. Nginx configuration was not changed.', 'yablonsky-performance'), 'page_cache_artifact_refreshed' => __('Page Cache executor artifact refreshed to the current YPerf build. MISS → HIT proof and Runtime Acceptance passed.', 'yablonsky-performance'), 'object_cache_reserved' => __('Isolated Redis target reserved locally. Redis and object-cache.php were not changed.', 'yablonsky-performance'), 'object_cache_reservation_released' => __('Redis target reservation released. Redis remained untouched.', 'yablonsky-performance'), 'object_cache_activated' => __('YPerf persistent object cache is active. Redis round-trip and Runtime Acceptance passed.', 'yablonsky-performance'), 'object_cache_rolled_back' => __('YPerf object-cache drop-in was removed. Redis was not flushed or reconfigured.', 'yablonsky-performance'), 'origin_budget_adopted' => __('Existing PHP-FPM max_children adopted as the YPerf Origin Budget. No host file was changed.', 'yablonsky-performance'), 'origin_budget_released' => __('Origin Budget adoption released. PHP-FPM configuration remained unchanged.', 'yablonsky-performance'), 'feature_control' => __('YPerf control changed and Runtime Acceptance accepted the result.', 'yablonsky-performance'), 'video_lite_control' => __('Media optimization policy saved at WordPress origin. External page-cache freshness is not yet verified; review the canonical public URL before declaring activation complete.', 'yablonsky-performance'), 'w3tc_user_handoff' => __('Migration is prepared. Deactivate W3 Total Cache yourself in Plugins, then return to Migration and continue. YPerf never changes another plugin’s activation status.', 'yablonsky-performance'), 'w3tc_migration' => __('W3 Total Cache handoff was confirmed by the administrator and YPerf migration proof passed.', 'yablonsky-performance'), 'w3tc_rollback' => __('W3 Total Cache migration was rolled back to the previous provider state.', 'yablonsky-performance'), 'settings_import' => __('YPerf settings were imported and verified successfully.', 'yablonsky-performance'), 'cloudflare_saved' => __('Cloudflare connection settings saved.', 'yablonsky-performance'), 'cloudflare_inspected' => __('Cloudflare performance delivery inspected.', 'yablonsky-performance'), 'cloudflare_applied' => __('Cloudflare performance profile applied and proved.', 'yablonsky-performance'), 'cloudflare_rollback' => __('YPerf Cloudflare changes were rolled back safely.', 'yablonsky-performance'), 'cloudflare_purge_verified' => __('Homepage URL purge synchronization with Cloudflare was verified.', 'yablonsky-performance')) as $type => $message) {
            $key = self::noticeKey($type);
            if (get_transient($key) === '1') {
                delete_transient($key);
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
            }
        }
        foreach (array('purge_attention' => 'warning', 'purge_error' => 'error') as $noticeType => $noticeLevel) {
            $message = get_transient(self::noticeKey($noticeType));
            if (is_string($message) && $message !== '') {
                delete_transient(self::noticeKey($noticeType));
                echo '<div class="notice notice-' . esc_attr($noticeLevel) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
            }
        }
        $objectCacheAttention = get_transient(self::noticeKey('object_cache_attention'));
        if (is_string($objectCacheAttention) && $objectCacheAttention !== '') {
            delete_transient(self::noticeKey('object_cache_attention'));
            echo '<div class="notice notice-warning is-dismissible"><p><strong>' . esc_html__('Site optimization remains active.', 'yablonsky-performance') . '</strong> ' . esc_html($objectCacheAttention) . '</p></div>';
        }
        foreach (array('host_error', 'performance_plan_error', 'performance_plan_preflight_error', 'settings_import_error', 'native_page_cache_error', 'page_cache_artifact_error', 'object_cache_reservation_error', 'object_cache_activation_error', 'origin_budget_error', 'ai_advisor_error', 'feature_control_error', 'cloudflare_error') as $errorType) {
            $errorKey = self::noticeKey($errorType);
            $error = get_transient($errorKey);
            if (is_string($error) && $error !== '') {
                delete_transient($errorKey);
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
            }
        }
    }

    private static function requireCapability(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'yablonsky-performance'));
        }
    }

    private static function requireOperator(string $nonceAction): void
    {
        self::requireCapability();
        check_admin_referer($nonceAction);
    }

    private static function redirectDashboard(string $tab = '', string $section = ''): void
    {
        $url = admin_url('admin.php?page=yablonsky-performance');
        if ($tab !== '') {
            $url = add_query_arg('yperf_tab', sanitize_key($tab), $url);
        }
        if ($section !== '') {
            $url = add_query_arg('yperf_section', sanitize_text_field($section), $url);
        }
        wp_safe_redirect($url);
        exit;
    }

    private static function noticeKey(string $type): string
    {
        return 'yperf_notice_' . sanitize_key($type) . '_' . get_current_user_id();
    }
}
