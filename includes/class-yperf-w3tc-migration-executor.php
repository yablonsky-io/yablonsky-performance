<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/** User-directed handoff from W3 Total Cache to proven YPerf-owned layers. */
final class W3tcMigrationExecutor
{
    private const OPTION = 'yperf_w3tc_migration_v1';
    private const PROGRESS_OPTION = 'yperf_w3tc_migration_progress_v1';
    private const BASENAME = 'w3-total-cache/w3-total-cache.php';
    private const ADVANCED_CACHE_BACKUP_OPTION = 'yperf_w3tc_advanced_cache_backup_v1';
    private const HANDOFF_OPTION = 'yperf_w3tc_user_handoff_v2';

    /** @return array<string,mixed> */
    public static function assessment(): array
    {
        $caps = CapabilityDetector::detect();
        $providers = is_array($caps['providers'] ?? null) ? $caps['providers'] : array();
        $w3tc = is_array($providers['w3-total-cache'] ?? null) ? $providers['w3-total-cache'] : array();
        if ($w3tc === array()) {
            return array('applicable' => false, 'ready' => false, 'blockers' => array(), 'summary' => __('W3 Total Cache is not active.', 'yablonsky-performance'));
        }
        $layers = is_array($w3tc['layers'] ?? null) ? $w3tc['layers'] : array();
        $blockers = array();
        if (empty($w3tc['config_readable'])) {
            $blockers[] = __('W3 Total Cache configuration is not readable in this request.', 'yablonsky-performance');
        }

        $runtime = RuntimeAcceptance::lastReport();
        $runtimeCurrent = $runtime !== array() && (string) ($runtime['version'] ?? '') === YPERF_VERSION;
        $runtimeClean = $runtimeCurrent && (int) (($runtime['summary']['fail'] ?? 0)) === 0;
        if (!$runtimeClean) {
            $blockers[] = __('Run a clean Runtime Acceptance on this YPerf version before migration.', 'yablonsky-performance');
        }

        // These active W3TC responsibilities cannot yet be retired automatically by YPerf.
        // Cloudflare is evaluated separately below because an installed W3TC
        // integration is not the same thing as active edge HTML execution.
        $unsupported = array(
            'database_cache' => __('Database Cache is active in W3 Total Cache.', 'yablonsky-performance'),
            'minify' => __('Minify / Assets is active in W3 Total Cache.', 'yablonsky-performance'),
            'fragment_cache' => __('Fragment Cache is active/configured in W3 Total Cache.', 'yablonsky-performance'),
            'cdn' => __('CDN delivery is active in W3 Total Cache.', 'yablonsky-performance'),
            'full_site_delivery' => __('Full-site delivery is active in W3 Total Cache.', 'yablonsky-performance'),
            'lazy_load' => __('Lazy Load is active in W3 Total Cache.', 'yablonsky-performance'),
        );
        foreach ($unsupported as $key => $message) {
            $layer = is_array($layers[$key] ?? null) ? $layers[$key] : array();
            $active = !empty($layer['enabled']);
            if ($key === 'fragment_cache') {
                $active = !empty($layer['enabled']) && !empty($layer['configured']);
            }
            if ($active) {
                $blockers[] = $message;
            }
        }

        $cloudflare = is_array($layers['cloudflare_extension'] ?? null) ? $layers['cloudflare_extension'] : array();
        if (!empty($cloudflare['enabled'])) {
            $cdn = is_array($layers['cdn'] ?? null) ? $layers['cdn'] : array();
            $fsd = is_array($layers['full_site_delivery'] ?? null) ? $layers['full_site_delivery'] : array();
            $cloudflareSafeToRetireWithProvider = empty($cdn['enabled'])
                && empty($fsd['enabled'])
                && self::runtimeProvesDynamicCloudflare($runtime);
            if (!$cloudflareSafeToRetireWithProvider) {
                $blockers[] = __('The W3 Total Cache Cloudflare extension is active.', 'yablonsky-performance');
            }
        }

        $w3Object = is_array($layers['object_cache'] ?? null) ? $layers['object_cache'] : array();
        if (!empty($w3Object['enabled'])) {
            $blockers[] = !empty(ObjectCacheExecutor::status()['active'])
                ? __('W3 Total Cache Object Cache is still enabled. YPerf Object Cache is active, but the old responsibility must be intentionally retired before plugin deactivation.', 'yablonsky-performance')
                : __('W3 Total Cache Object Cache is active and YPerf Object Cache is not yet proven active.', 'yablonsky-performance');
        }

        $w3Page = is_array($layers['page_cache'] ?? null) ? $layers['page_cache'] : array();
        $w3Browser = is_array($layers['browser_cache'] ?? null) ? $layers['browser_cache'] : array();
        return array(
            'schema' => 1,
            'applicable' => true,
            'ready' => empty($blockers),
            'blockers' => $blockers,
            'provider_version' => sanitize_text_field((string) ($w3tc['version'] ?? '')),
            'source' => array(
                'page_cache_enabled' => !empty($w3Page['enabled']),
                'browser_cache_enabled' => !empty($w3Browser['enabled']),
                'object_cache_enabled' => !empty($w3Object['enabled']),
            ),
            'runtime_current_clean' => $runtimeClean,
            'summary' => empty($blockers)
                ? __('W3 Total Cache responsibilities visible to this migration are replaceable by YPerf and the current runtime gate is clean.', 'yablonsky-performance')
                : __('Migration remains blocked until every active W3 Total Cache responsibility has a safe replacement or is intentionally retired.', 'yablonsky-performance'),
        );
    }

    /** @param array<string,mixed> $runtime */
    private static function runtimeProvesDynamicCloudflare(array $runtime): bool
    {
        $tests = is_array($runtime['tests'] ?? null) ? $runtime['tests'] : array();
        foreach ($tests as $test) {
            if (!is_array($test) || (string) ($test['name'] ?? '') !== 'Homepage') {
                continue;
            }
            $signals = is_array($test['cache_signals'] ?? null) ? $test['cache_signals'] : array();
            return strtoupper((string) ($signals['cf-cache-status'] ?? '')) === 'DYNAMIC';
        }
        return false;
    }

    /** @return array<string,mixed> */
    public static function progress(): array
    {
        $value = get_option(self::PROGRESS_OPTION, array());
        return is_array($value) ? $value : array();
    }


    /**
     * Reports whether a completed W3TC handover still owns the WordPress
     * advanced-cache.php drop-in. This closes the gap where the provider is
     * already inactive, so normal active-plugin detection can no longer see
     * the stale artifact even though WordPress may still load it via WP_CACHE.
     *
     * @return array<string,mixed>
     */
    public static function postHandoverCleanupStatus(): array
    {
        $record = self::last();
        if ((string) ($record['state'] ?? '') !== 'active') {
            return array('applicable' => false, 'required' => false, 'blocked' => false, 'complete' => false, 'state' => 'not_applicable');
        }

        self::loadPluginApi();
        $pluginActive = function_exists('is_plugin_active') && is_plugin_active(self::BASENAME);
        if ($pluginActive) {
            return array('applicable' => false, 'required' => false, 'blocked' => false, 'complete' => false, 'state' => 'provider_active');
        }

        $dropins = DropinOwnership::inspect();
        $advanced = is_array($dropins['advanced_cache'] ?? null) ? $dropins['advanced_cache'] : array();
        $present = !empty($advanced['present']);
        $owner = sanitize_key((string) ($advanced['owner'] ?? 'none'));
        $confidence = sanitize_key((string) ($advanced['confidence'] ?? 'low'));

        if (!$present || $owner === 'yablonsky-performance') {
            return array(
                'applicable' => true,
                'required' => false,
                'blocked' => false,
                'complete' => true,
                'state' => 'complete',
                'owner' => $owner,
                'confidence' => $confidence,
            );
        }

        if ($owner === 'w3-total-cache' && $confidence === 'high') {
            return array(
                'applicable' => true,
                'required' => true,
                'blocked' => false,
                'complete' => false,
                'state' => 'cleanup_required',
                'owner' => $owner,
                'confidence' => $confidence,
                'sha256' => sanitize_text_field((string) ($advanced['sha256'] ?? '')),
                'size_bytes' => max(0, (int) ($advanced['size_bytes'] ?? 0)),
            );
        }

        return array(
            'applicable' => true,
            'required' => false,
            'blocked' => true,
            'complete' => false,
            'state' => 'ownership_review_required',
            'owner' => $owner,
            'confidence' => $confidence,
        );
    }

    /** @return array<string,mixed> */
    public static function finalizeResidualCleanup(): array
    {
        ignore_user_abort(true);
        $status = self::postHandoverCleanupStatus();
        if (!empty($status['complete'])) {
            self::setProgress('complete', 100, __('Migration cleanup is already complete.', 'yablonsky-performance'), __('No W3 Total Cache-owned advanced-cache.php remains.', 'yablonsky-performance'), true);
            return array('ok' => true, 'record' => self::last(), 'cleanup' => $status);
        }
        if (empty($status['required'])) {
            self::setProgress('blocked', 5, __('Post-handover cleanup requires review.', 'yablonsky-performance'), __('YPerf will not change an advanced-cache.php whose ownership is not proven.', 'yablonsky-performance'), true);
            return self::fail(__('YPerf cannot safely finalize the remaining advanced-cache.php because ownership is not proven.', 'yablonsky-performance'), $status);
        }

        self::setProgress('cleanup_snapshot', 18, __('Snapshotting the legacy drop-in.', 'yablonsky-performance'), __('The exact W3 Total Cache advanced-cache.php is captured locally for rollback before any file change.', 'yablonsky-performance'));
        $backup = self::backupW3tcAdvancedCache();
        if (empty($backup['ok'])) {
            self::setProgress('blocked', 18, __('Cleanup stopped before changing the filesystem.', 'yablonsky-performance'), __('The legacy drop-in could not be captured safely, so nothing was removed.', 'yablonsky-performance'), true);
            return self::fail((string) ($backup['message'] ?? __('Could not snapshot the W3 Total Cache advanced-cache.php.', 'yablonsky-performance')), $backup);
        }

        self::setProgress('cleanup_remove', 46, __('Retiring the legacy drop-in.', 'yablonsky-performance'), __('Only the high-confidence W3 Total Cache-owned advanced-cache.php is being removed; WP_CACHE and unrelated files remain untouched.', 'yablonsky-performance'));
        $removed = self::removeW3tcAdvancedCache();
        if (empty($removed['ok'])) {
            self::setProgress('blocked', 46, __('Cleanup stopped safely.', 'yablonsky-performance'), __('The provider-owned drop-in was not removed because read-back verification failed.', 'yablonsky-performance'), true);
            return self::fail((string) ($removed['message'] ?? __('Could not retire the W3 Total Cache advanced-cache.php.', 'yablonsky-performance')), $removed);
        }

        self::setProgress('cleanup_runtime', 74, __('Proving WordPress after cleanup.', 'yablonsky-performance'), __('Runtime Acceptance is running after the provider-owned drop-in was retired.', 'yablonsky-performance'));
        $runtime = self::runAndSaveRuntime();
        $runtimeClean = (int) (($runtime['summary']['fail'] ?? 0)) === 0;
        if (!$runtimeClean) {
            $restore = self::restoreAdvancedCacheBackup();
            if (empty($restore['ok'])) {
                self::setProgress('rollback_attention', 100, __('Cleanup proof failed — rollback requires review.', 'yablonsky-performance'), __('Runtime Acceptance failed after cleanup and the saved advanced-cache.php could not be restored automatically. YPerf stopped without claiming a successful rollback.', 'yablonsky-performance'), true);
                return self::fail(__('Post-handover cleanup failed and the exact drop-in rollback could not be completed automatically.', 'yablonsky-performance'), array('runtime' => $runtime, 'restore' => $restore));
            }
            $rollbackRuntime = self::runAndSaveRuntime();
            self::setProgress('rolled_back', 100, __('Cleanup proof failed — legacy drop-in restored.', 'yablonsky-performance'), __('Runtime Acceptance failed after cleanup, so YPerf restored the exact saved advanced-cache.php and rechecked the restored state.', 'yablonsky-performance'), true);
            return self::fail(__('Post-handover cleanup failed Runtime Acceptance and was rolled back.', 'yablonsky-performance'), array('runtime' => $runtime, 'restore' => $restore, 'rollback_runtime' => $rollbackRuntime));
        }

        $record = self::last();
        $record['cleanup'] = array(
            'state' => 'complete',
            'completed_at' => time(),
            'version' => YPERF_VERSION,
            'advanced_cache_removed' => !empty($removed['removed']),
            'advanced_cache_sha256' => sanitize_text_field((string) ($backup['sha256'] ?? '')),
            'runtime_summary' => $runtime['summary'] ?? array(),
        );
        update_option(self::OPTION, $record, false);
        self::setProgress('complete', 100, __('Migration complete and fully reconciled.', 'yablonsky-performance'), __('The legacy provider is inactive, its owned advanced-cache.php is retired, and post-cleanup Runtime Acceptance is clean.', 'yablonsky-performance'), true);
        return array('ok' => true, 'record' => $record, 'cleanup' => self::postHandoverCleanupStatus());
    }

    /** @return array<string,mixed> */
    public static function migrate(): array
    {
        ignore_user_abort(true);
        $postHandover = self::postHandoverCleanupStatus();
        if (!empty($postHandover['required']) || !empty($postHandover['blocked'])) {
            return self::finalizeResidualCleanup();
        }

        self::loadPluginApi();
        $pluginActive = function_exists('is_plugin_active') && is_plugin_active(self::BASENAME);
        $pending = self::pendingHandoff();

        if ($pluginActive) {
            self::setProgress('preflight', 5, __('Preparing migration preflight.', 'yablonsky-performance'), __('Reading provider responsibilities and current Runtime Acceptance.', 'yablonsky-performance'));
            $assessment = self::assessment();
            if (empty($assessment['ready'])) {
                self::setProgress('blocked', 5, __('Migration preflight is blocked.', 'yablonsky-performance'), __('No ownership change was attempted.', 'yablonsky-performance'), true);
                return self::fail(__('W3 Total Cache migration preflight is not ready.', 'yablonsky-performance'), $assessment);
            }

            $before = array(
                'w3tc_active' => true,
                'browser_cache_yperf' => BrowserCacheController::enabled(),
                'page_cache_yperf' => PageCacheExecutor::enabled(),
                'object_cache_yperf' => !empty(ObjectCacheExecutor::status()['active']),
            );
            $pending = array(
                'schema' => 2,
                'state' => 'awaiting_user_deactivation',
                'version' => YPERF_VERSION,
                'prepared_at' => time(),
                'provider_version' => sanitize_text_field((string) ($assessment['provider_version'] ?? '')),
                'before' => $before,
                'source' => is_array($assessment['source'] ?? null) ? $assessment['source'] : array(),
            );
            update_option(self::HANDOFF_OPTION, $pending, false);
            self::setProgress(
                'awaiting_user_deactivation',
                25,
                __('Ready for your confirmation in Plugins.', 'yablonsky-performance'),
                __('WordPress.org requires plugin activation status to remain under user control. Deactivate W3 Total Cache yourself, then return here and continue migration.', 'yablonsky-performance'),
                true
            );
            return array(
                'ok' => true,
                'state' => 'user_action_required',
                'requires_user_action' => true,
                'action' => 'deactivate_w3tc',
                'message' => __('Preparation is complete. Deactivate W3 Total Cache on the WordPress Plugins screen, then return and continue migration. YPerf will not deactivate another plugin for you.', 'yablonsky-performance'),
                'pending' => $pending,
            );
        }

        if ($pending === array() || (string) ($pending['state'] ?? '') !== 'awaiting_user_deactivation') {
            self::setProgress('stopped', 10, __('Migration is waiting for an explicit user handoff.', 'yablonsky-performance'), __('Activate W3 Total Cache, prepare the migration, and then deactivate it yourself from Plugins before YPerf claims any replacement layer.', 'yablonsky-performance'), true);
            return self::fail(__('W3 Total Cache is inactive, but no prepared user-directed handoff exists. YPerf will not change another plugin’s activation status.', 'yablonsky-performance'));
        }

        $before = is_array($pending['before'] ?? null) ? $pending['before'] : array();
        $source = is_array($pending['source'] ?? null) ? $pending['source'] : array();
        $assessment = array(
            'ready' => true,
            'provider_version' => sanitize_text_field((string) ($pending['provider_version'] ?? '')),
            'source' => $source,
        );

        self::setProgress('user_handoff_confirmed', 35, __('User deactivation confirmed.', 'yablonsky-performance'), __('W3 Total Cache is inactive because the administrator changed its status in WordPress. YPerf can now reconcile only the prepared replacement layers.', 'yablonsky-performance'));

        $pageWasActivated = false;
        if (!empty($source['page_cache_enabled']) && !PageCacheExecutor::enabled()) {
            self::setProgress('yperf_page_cache', 48, __('Activating the YPerf Page Cache replacement.', 'yablonsky-performance'), __('Only the prepared page-cache responsibility is being claimed.', 'yablonsky-performance'));
            $activation = PageCacheExecutor::activate(true);
            if (empty($activation['ok'])) {
                self::setProgress('rolled_back', 48, __('Page Cache handover stopped safely.', 'yablonsky-performance'), __('YPerf made no plugin activation changes. Review the page-cache ownership state before retrying.', 'yablonsky-performance'), true);
                return self::fail((string) ($activation['message'] ?? __('YPerf Page Cache activation failed.', 'yablonsky-performance')), $assessment);
            }
            $pageWasActivated = true;
        }

        $browserChanged = false;
        if (!empty($source['browser_cache_enabled']) && !BrowserCacheController::enabled()) {
            self::setProgress('yperf_browser_cache', 58, __('Binding Browser Cache policy.', 'yablonsky-performance'), __('YPerf is preserving the prepared browser-cache responsibility.', 'yablonsky-performance'));
            if (!BrowserCacheController::setEnabled(true)) {
                if ($pageWasActivated) {
                    PageCacheExecutor::disable(true);
                }
                self::setProgress('rolled_back', 58, __('Browser Cache handover stopped safely.', 'yablonsky-performance'), __('YPerf restored its own partial changes. Plugin activation status remains under user control.', 'yablonsky-performance'), true);
                return self::fail(__('YPerf Browser Cache policy could not be bound safely.', 'yablonsky-performance'), $assessment);
            }
            $browserChanged = true;
        }

        self::setProgress('dropin_reconciliation', 66, __('Reconciling the prepared page-cache drop-in.', 'yablonsky-performance'), __('Only a high-confidence W3 Total Cache-owned advanced-cache.php may be backed up and retired.', 'yablonsky-performance'));
        $dropinCleanup = self::retireW3tcAdvancedCacheIfOwned();
        if (empty($dropinCleanup['ok'])) {
            if ($browserChanged) {
                BrowserCacheController::setEnabled(false);
            }
            if ($pageWasActivated) {
                PageCacheExecutor::disable(true);
            }
            self::restoreAdvancedCacheBackup();
            self::setProgress('rolled_back', 66, __('Drop-in reconciliation stopped safely.', 'yablonsky-performance'), __('YPerf restored its own changes and left plugin activation status untouched.', 'yablonsky-performance'), true);
            return self::fail((string) ($dropinCleanup['message'] ?? __('Provider-owned advanced-cache.php could not be reconciled safely.', 'yablonsky-performance')), $dropinCleanup);
        }

        self::setProgress('execution_proof', 76, __('Proving YPerf execution.', 'yablonsky-performance'), __('Prepared replacement responsibilities are being verified.', 'yablonsky-performance'));
        $pageProof = PageCacheExecutor::enabled() ? PageCacheExecutor::prove() : array('ok' => true, 'state' => 'preserved_off');
        $browserProof = BrowserCacheController::enabled() ? BrowserCacheController::prove() : array('ok' => true, 'state' => 'preserved_off');
        self::setProgress('runtime_acceptance', 88, __('Running post-handover Health Check.', 'yablonsky-performance'), __('The YPerf-owned replacement must pass before migration can finalize.', 'yablonsky-performance'));
        $runtime = self::runAndSaveRuntime();
        $runtimeClean = (int) (($runtime['summary']['fail'] ?? 0)) === 0;

        if (empty($pageProof['ok']) || empty($browserProof['ok']) || !$runtimeClean) {
            if ($browserChanged) {
                BrowserCacheController::setEnabled(false);
            }
            $pageRollback = array('ok' => true, 'state' => 'not_needed');
            if ($pageWasActivated) {
                $pageRollback = PageCacheExecutor::disable(true);
            }
            $dropinRestore = self::restoreAdvancedCacheBackup();
            $rollbackComplete = !empty($pageRollback['ok']) && !empty($dropinRestore['ok']);
            self::setProgress(
                $rollbackComplete ? 'rolled_back' : 'rollback_attention',
                100,
                $rollbackComplete ? __('Proof failed — YPerf rollback complete.', 'yablonsky-performance') : __('Proof failed — rollback requires review.', 'yablonsky-performance'),
                __('YPerf restored its own migration changes. W3 Total Cache remains inactive until the administrator chooses to reactivate it from Plugins.', 'yablonsky-performance'),
                true
            );
            return self::fail(
                $rollbackComplete
                    ? __('Migration proof failed. YPerf restored its own changes; reactivate W3 Total Cache manually if you want to return to the previous provider.', 'yablonsky-performance')
                    : __('Migration proof failed and the YPerf rollback could not be fully verified.', 'yablonsky-performance'),
                array('page_proof' => $pageProof, 'browser_proof' => $browserProof, 'runtime' => $runtime)
            );
        }

        $record = array(
            'schema' => 2,
            'state' => 'active',
            'version' => YPERF_VERSION,
            'migrated_at' => time(),
            'completed_at' => time(),
            'provider_version' => sanitize_text_field((string) ($pending['provider_version'] ?? '')),
            'before' => $before,
            'source' => $source,
            'user_deactivated_provider' => true,
            'activation_status_changed_by_yperf' => false,
            'cleanup' => array(
                'state' => 'complete',
                'completed_at' => time(),
                'version' => YPERF_VERSION,
                'advanced_cache_removed' => !empty($dropinCleanup['removed']),
                'advanced_cache_sha256' => sanitize_text_field((string) ($dropinCleanup['sha256'] ?? '')),
            ),
            'page_proof' => $pageProof,
            'browser_proof' => $browserProof,
            'runtime_summary' => $runtime['summary'] ?? array(),
        );
        update_option(self::OPTION, $record, false);
        delete_option(self::HANDOFF_OPTION);
        self::setProgress('complete', 100, __('Migration complete and proven.', 'yablonsky-performance'), __('YPerf owns the prepared replacement layers. Another plugin’s activation status was never changed by YPerf.', 'yablonsky-performance'), true);
        return array('ok' => true, 'state' => 'complete', 'record' => $record);
    }

    /** @return array<string,mixed> */
    public static function rollback(): array
    {
        $record = self::last();
        if ((string) ($record['state'] ?? '') !== 'active') {
            return self::fail(__('No active W3 Total Cache migration record is available to roll back.', 'yablonsky-performance'));
        }
        $before = is_array($record['before'] ?? null) ? $record['before'] : array();
        if (empty($before['browser_cache_yperf'])) {
            BrowserCacheController::setEnabled(false);
        }
        if (empty($before['page_cache_yperf'])) {
            $pageRollback = PageCacheExecutor::disable(true);
            if (empty($pageRollback['ok'])) {
                return self::fail(__('YPerf could not restore the previous page-cache drop-in safely.', 'yablonsky-performance'), $pageRollback);
            }
        }
        $dropinRestore = self::restoreAdvancedCacheBackup();
        if (empty($dropinRestore['ok'])) {
            return self::fail(__('YPerf could not restore the saved W3 Total Cache advanced-cache.php safely.', 'yablonsky-performance'), $dropinRestore);
        }

        $record['state'] = 'rolled_back_waiting_user_reactivation';
        $record['rolled_back_at'] = time();
        $record['activation_status_changed_by_yperf'] = false;
        update_option(self::OPTION, $record, false);
        self::setProgress('rolled_back', 100, __('YPerf rollback complete.', 'yablonsky-performance'), __('YPerf restored its own migration changes. If you want W3 Total Cache active again, reactivate it yourself from the WordPress Plugins screen.', 'yablonsky-performance'), true);
        return array(
            'ok' => true,
            'state' => 'rolled_back_waiting_user_reactivation',
            'requires_user_action' => true,
            'message' => __('YPerf rollback is complete. W3 Total Cache was not reactivated automatically; WordPress.org requires that activation decision to remain with you.', 'yablonsky-performance'),
            'record' => $record,
        );
    }

    /** @return array<string,mixed> */
    public static function pendingHandoff(): array
    {
        $value = get_option(self::HANDOFF_OPTION, array());
        return is_array($value) ? $value : array();
    }

    /** @return array<string,mixed> */
    public static function last(): array
    {
        $record = get_option(self::OPTION, array());
        return is_array($record) ? $record : array();
    }


    /** @return array<string,mixed> */
    private static function retireW3tcAdvancedCacheIfOwned(): array
    {
        $dropins = DropinOwnership::inspect();
        $advanced = is_array($dropins['advanced_cache'] ?? null) ? $dropins['advanced_cache'] : array();
        if (empty($advanced['present'])) {
            return array('ok' => true, 'removed' => false, 'state' => 'absent');
        }
        $owner = sanitize_key((string) ($advanced['owner'] ?? 'unknown'));
        if ($owner === 'yablonsky-performance') {
            return array('ok' => true, 'removed' => false, 'state' => 'yperf_owned');
        }
        if ($owner !== 'w3-total-cache' || sanitize_key((string) ($advanced['confidence'] ?? '')) !== 'high') {
            return self::fail(__('advanced-cache.php is present, but W3 Total Cache ownership is not proven. YPerf will not delete it.', 'yablonsky-performance'), $advanced);
        }

        $backup = self::backupW3tcAdvancedCache();
        if (empty($backup['ok'])) {
            return $backup;
        }
        $removed = self::removeW3tcAdvancedCache();
        if (empty($removed['ok'])) {
            return $removed;
        }
        $removed['sha256'] = sanitize_text_field((string) ($backup['sha256'] ?? ''));
        return $removed;
    }

    /** @return array<string,mixed> */
    private static function backupW3tcAdvancedCache(): array
    {
        if (!self::loadFilesystem()) {
            return self::fail(__('WordPress filesystem access is unavailable; no legacy drop-in was changed.', 'yablonsky-performance'));
        }
        global $wp_filesystem;
        $path = defined('WP_CONTENT_DIR') ? (string) WP_CONTENT_DIR . '/advanced-cache.php' : '';
        if ($path === '' || !is_object($wp_filesystem) || !method_exists($wp_filesystem, 'exists') || !$wp_filesystem->exists($path)) {
            return array('ok' => true, 'present' => false, 'sha256' => '');
        }
        $dropins = DropinOwnership::inspect();
        $advanced = is_array($dropins['advanced_cache'] ?? null) ? $dropins['advanced_cache'] : array();
        if ((string) ($advanced['owner'] ?? '') !== 'w3-total-cache' || (string) ($advanced['confidence'] ?? '') !== 'high') {
            return self::fail(__('The legacy advanced-cache.php no longer has high-confidence W3 Total Cache ownership.', 'yablonsky-performance'), $advanced);
        }
        $contents = method_exists($wp_filesystem, 'get_contents') ? $wp_filesystem->get_contents($path) : false;
        if (!is_string($contents) || $contents === '') {
            return self::fail(__('The W3 Total Cache advanced-cache.php could not be read for rollback capture.', 'yablonsky-performance'));
        }
        if (strlen($contents) > 524288) {
            return self::fail(__('The legacy advanced-cache.php is unexpectedly large, so YPerf refused automatic cleanup.', 'yablonsky-performance'));
        }
        $sha = hash('sha256', $contents);
        update_option(self::ADVANCED_CACHE_BACKUP_OPTION, array(
            'schema' => 1,
            'captured_at' => time(),
            'version' => YPERF_VERSION,
            'sha256' => $sha,
            'size_bytes' => strlen($contents),
            'contents_b64' => base64_encode($contents),
        ), false);
        return array('ok' => true, 'present' => true, 'sha256' => $sha, 'size_bytes' => strlen($contents));
    }

    /** @return array<string,mixed> */
    private static function removeW3tcAdvancedCache(): array
    {
        if (!self::loadFilesystem()) {
            return self::fail(__('WordPress filesystem access is unavailable; no legacy drop-in was changed.', 'yablonsky-performance'));
        }
        global $wp_filesystem;
        $path = defined('WP_CONTENT_DIR') ? (string) WP_CONTENT_DIR . '/advanced-cache.php' : '';
        if ($path === '' || !is_object($wp_filesystem) || !method_exists($wp_filesystem, 'exists')) {
            return self::fail(__('The WordPress content directory is unavailable for verified cleanup.', 'yablonsky-performance'));
        }
        if (!$wp_filesystem->exists($path)) {
            return array('ok' => true, 'removed' => false, 'state' => 'already_absent');
        }
        $dropins = DropinOwnership::inspect();
        $advanced = is_array($dropins['advanced_cache'] ?? null) ? $dropins['advanced_cache'] : array();
        if ((string) ($advanced['owner'] ?? '') !== 'w3-total-cache' || (string) ($advanced['confidence'] ?? '') !== 'high') {
            return self::fail(__('YPerf refused to remove advanced-cache.php because its W3 Total Cache ownership changed before write time.', 'yablonsky-performance'), $advanced);
        }
        $deleted = method_exists($wp_filesystem, 'delete') && $wp_filesystem->delete($path, false, 'f');
        if (!$deleted || $wp_filesystem->exists($path)) {
            return self::fail(__('The W3 Total Cache advanced-cache.php could not be removed with verified read-back.', 'yablonsky-performance'));
        }
        return array('ok' => true, 'removed' => true, 'state' => 'removed');
    }

    /** @return array<string,mixed> */
    private static function restoreAdvancedCacheBackup(): array
    {
        $backup = get_option(self::ADVANCED_CACHE_BACKUP_OPTION, array());
        if (!is_array($backup) || (string) ($backup['contents_b64'] ?? '') === '') {
            return array('ok' => true, 'restored' => false, 'state' => 'no_backup');
        }
        if (!self::loadFilesystem()) {
            return self::fail(__('WordPress filesystem access is unavailable for legacy drop-in rollback.', 'yablonsky-performance'));
        }
        global $wp_filesystem;
        $path = defined('WP_CONTENT_DIR') ? (string) WP_CONTENT_DIR . '/advanced-cache.php' : '';
        if ($path === '' || !is_object($wp_filesystem)) {
            return self::fail(__('The WordPress content directory is unavailable for legacy drop-in rollback.', 'yablonsky-performance'));
        }
        if (method_exists($wp_filesystem, 'exists') && $wp_filesystem->exists($path)) {
            $dropins = DropinOwnership::inspect();
            $advanced = is_array($dropins['advanced_cache'] ?? null) ? $dropins['advanced_cache'] : array();
            $currentSha = sanitize_text_field((string) ($advanced['sha256'] ?? ''));
            if ($currentSha !== '' && hash_equals((string) ($backup['sha256'] ?? ''), $currentSha)) {
                return array('ok' => true, 'restored' => false, 'state' => 'already_restored');
            }
            return self::fail(__('Rollback stopped because another advanced-cache.php now exists. YPerf will not overwrite it.', 'yablonsky-performance'), $advanced);
        }
        $decoded = base64_decode((string) ($backup['contents_b64'] ?? ''), true);
        if (!is_string($decoded) || $decoded === '' || !hash_equals((string) ($backup['sha256'] ?? ''), hash('sha256', $decoded))) {
            return self::fail(__('The saved legacy drop-in backup failed integrity verification.', 'yablonsky-performance'));
        }
        $mode = defined('FS_CHMOD_FILE') ? (int) FS_CHMOD_FILE : 0644;
        $written = method_exists($wp_filesystem, 'put_contents') && $wp_filesystem->put_contents($path, $decoded, $mode);
        if (!$written) {
            return self::fail(__('The saved W3 Total Cache advanced-cache.php could not be restored.', 'yablonsky-performance'));
        }
        $verify = DropinOwnership::inspect();
        $advanced = is_array($verify['advanced_cache'] ?? null) ? $verify['advanced_cache'] : array();
        if ((string) ($advanced['sha256'] ?? '') !== (string) ($backup['sha256'] ?? '')) {
            return self::fail(__('The restored advanced-cache.php did not match the saved rollback fingerprint.', 'yablonsky-performance'), $advanced);
        }
        return array('ok' => true, 'restored' => true, 'state' => 'restored');
    }

    private static function loadFilesystem(): bool
    {
        if (!function_exists('WP_Filesystem') && defined('ABSPATH')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if (!function_exists('WP_Filesystem')) {
            return false;
        }
        WP_Filesystem();
        global $wp_filesystem;
        return is_object($wp_filesystem);
    }


    /** @return array<string,mixed> */
    private static function runAndSaveRuntime(): array
    {
        $runtime = RuntimeAcceptance::run();
        update_option('yperf_runtime_acceptance_v1', $runtime, false);
        Telemetry::recordRuntime($runtime);
        RuntimeHistory::record($runtime, 'migration');
        return $runtime;
    }

    private static function loadPluginApi(): void
    {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
    }

    private static function setProgress(string $phase, int $percent, string $label, string $detail, bool $terminal = false): void
    {
        $previous = self::progress();
        $events = is_array($previous['events'] ?? null) ? $previous['events'] : array();
        $events[] = array('time' => time(), 'phase' => sanitize_key($phase), 'message' => sanitize_text_field($label));
        $events = array_slice($events, -12);
        update_option(self::PROGRESS_OPTION, array(
            'schema' => 1,
            'version' => YPERF_VERSION,
            'updated_at' => time(),
            'phase' => sanitize_key($phase),
            'percent' => max(0, min(100, $percent)),
            'label' => sanitize_text_field($label),
            'detail' => sanitize_text_field($detail),
            'terminal' => $terminal,
            'events' => $events,
        ), false);
    }

    /** @param mixed $context @return array<string,mixed> */
    private static function fail(string $message, $context = array()): array
    {
        return array('ok' => false, 'message' => sanitize_text_field($message), 'context' => is_array($context) ? $context : array());
    }
}
