<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * YPerf-owned portable page-cache executor.
 *
 * The executor installs only a YPerf-owned advanced-cache.php drop-in, manages
 * the WP_CACHE bootstrap with a reversible marker, stores anonymous public HTML
 * under wp-content/cache/yablonsky-performance/page and proves MISS -> HIT before
 * the control is accepted as active.
 */
final class PageCacheExecutor
{
    private const OPTION = 'yperf_page_cache_execution_v1';
    private const CACHE_SUBDIR = 'cache/yablonsky-performance/page';
    private const CONFIG_BEGIN = '/* YPERF_PAGE_CACHE_BEGIN */';
    private const CONFIG_END = '/* YPERF_PAGE_CACHE_END */';
    private const TTL = 300;
    private const STALE_LOCK_SECONDS = 120;

    public static function register(): void
    {
        if (!self::enabled()) {
            return;
        }
        add_action('yablonsky_performance_adapter_purge_urls', array(self::class, 'purgeUrls'), 5, 2);
        add_action('yablonsky_performance_adapter_purge_all', array(self::class, 'purgeAll'), 5, 1);
    }

    public static function enabled(): bool
    {
        // Runtime truth wins over stale option metadata. A compatible YPerf-owned
        // advanced-cache.php executes before normal plugins bootstrap, so an old
        // or incomplete option record must never make a live YPerf cache look OFF.
        $record = self::last();
        if (in_array((string) ($record['state'] ?? ''), array('active', 'needs_review'), true)) {
            return true;
        }

        $ownership = DropinOwnership::inspect();
        $advanced = is_array($ownership['advanced_cache'] ?? null) ? $ownership['advanced_cache'] : array();
        $artifactVersion = sanitize_text_field((string) ($advanced['artifact_version'] ?? ''));
        return !empty($advanced['present'])
            && (string) ($advanced['owner'] ?? '') === 'yablonsky-performance'
            && $artifactVersion !== ''
            && self::isCompatibleDropinVersion($artifactVersion)
            && defined('WP_CACHE') && WP_CACHE;
    }

    /** @return array<string,mixed> */
    public static function last(): array
    {
        $value = get_option(self::OPTION, array());
        return is_array($value) ? $value : array();
    }

    /** @return array<string,mixed> */
    public static function status(): array
    {
        $ownership = DropinOwnership::inspect();
        $advanced = is_array($ownership['advanced_cache'] ?? null) ? $ownership['advanced_cache'] : array();
        $record = self::last();
        $owned = !empty($advanced['present']) && (string) ($advanced['owner'] ?? '') === 'yablonsky-performance';
        $hashMatches = $owned && !empty($record['dropin_sha256']) && !empty($advanced['sha256'])
            && hash_equals((string) $record['dropin_sha256'], (string) $advanced['sha256']);
        $artifactVersion = sanitize_text_field((string) ($advanced['artifact_version'] ?? ''));
        $artifactCompatible = $owned && $artifactVersion !== '' && self::isCompatibleDropinVersion($artifactVersion);
        $artifactRefreshable = $owned && $artifactVersion !== '' && self::isRefreshableDropinVersion($artifactVersion);
        $artifactCurrent = $owned && $artifactVersion !== '' && hash_equals(YPERF_VERSION, $artifactVersion);
        $runtimeInstalled = $owned && $artifactCompatible && defined('WP_CACHE') && WP_CACHE;
        $lastProof = is_array($record['last_proof'] ?? null) ? $record['last_proof'] : array();
        $proofCurrent = !empty($lastProof['ok']) && (string) ($lastProof['version'] ?? '') === YPERF_VERSION;
        $effectiveEnabled = self::enabled() || $runtimeInstalled;
        $state = $effectiveEnabled && $owned && $hashMatches && $artifactCompatible && $proofCurrent
            ? 'active'
            : ($effectiveEnabled ? 'needs_review' : 'off');
        return array(
            'enabled' => $effectiveEnabled,
            'runtime_installed' => $runtimeInstalled,
            'dropin_owned' => $owned,
            'dropin_hash_matches' => $hashMatches,
            'dropin_version' => $artifactVersion,
            'dropin_version_current' => $artifactCurrent,
            'dropin_version_compatible' => $artifactCompatible,
            'dropin_version_refreshable' => $artifactRefreshable,
            'dropin_artifact_state' => !$owned ? 'not_owned' : ($artifactCurrent ? 'current' : ($artifactCompatible ? 'compatible_previous' : ($artifactRefreshable ? 'refresh_required' : 'incompatible'))),
            'wp_cache' => defined('WP_CACHE') && WP_CACHE,
            'state' => $state,
            'record' => self::publicRecord($record),
        );
    }

    /** @return array<string,mixed> */
    public static function activate(bool $allowW3tcHandover = false): array
    {
        // The WordPress drop-in is the portable fallback for sites without a
        // proven native page-cache authority. Never silently add a second page
        // cache on a site that explicitly adopted Nginx as its cache executor.
        if (NativePageCacheAdoption::last() !== array()) {
            return self::error(__('A native page-cache adoption record exists. Release that cache authority explicitly before enabling portable YPerf Page Cache.', 'yablonsky-performance'));
        }
        $fs = self::filesystem();
        if ($fs === null) {
            return self::error(__('WordPress Filesystem could not obtain direct write access for the page-cache activation.', 'yablonsky-performance'));
        }

        $ownership = DropinOwnership::inspect();
        $advanced = is_array($ownership['advanced_cache'] ?? null) ? $ownership['advanced_cache'] : array();
        $owner = (string) ($advanced['owner'] ?? 'none');
        if (!empty($advanced['present']) && $owner !== 'yablonsky-performance') {
            if (!$allowW3tcHandover || $owner !== 'w3-total-cache') {
                return self::error(__('advanced-cache.php is owned by another provider. YPerf will not overwrite it outside an explicit migration handover.', 'yablonsky-performance'));
            }
        }

        $cacheDir = self::cacheDir();
        if (!self::prepareCacheTree($fs, $cacheDir)) {
            return self::error(__('YPerf could not prepare its bounded page-cache directory.', 'yablonsky-performance'));
        }

        $configMutation = self::ensureWpCacheTrue($fs);
        if (empty($configMutation['ok'])) {
            return self::error((string) ($configMutation['message'] ?? __('YPerf could not safely enable WP_CACHE.', 'yablonsky-performance')));
        }

        $templatePath = YPERF_DIR . 'dropins/advanced-cache.php.tpl';
        $template = $fs->get_contents($templatePath);
        if (!is_string($template) || $template === '') {
            self::restoreWpCacheMutation($fs, $configMutation);
            return self::error(__('The YPerf advanced-cache.php template is unavailable.', 'yablonsky-performance'));
        }
        $rendered = str_replace(
            array('__YPERF_VERSION__', '__YPERF_TTL__', '__YPERF_BROWSER_MAX_AGE__'),
            array(self::escapeSingleQuotedPhpString(YPERF_VERSION), (string) self::TTL, BrowserCacheController::enabled() ? '60' : '0'),
            $template
        );
        if (strpos($rendered, '__YPERF_') !== false) {
            self::restoreWpCacheMutation($fs, $configMutation);
            return self::error(__('The YPerf page-cache drop-in template could not be rendered safely.', 'yablonsky-performance'));
        }

        $target = self::dropinPath();
        $backup = array();
        if (!empty($advanced['present']) && $owner === 'w3-total-cache') {
            $backup = self::backupExistingDropin($fs, $target, (string) ($advanced['sha256'] ?? ''));
            if (empty($backup['ok'])) {
                self::restoreWpCacheMutation($fs, $configMutation);
                return self::error(__('The existing W3 Total Cache drop-in could not be backed up for rollback.', 'yablonsky-performance'));
            }
        }

        $write = self::atomicWrite($fs, $target, $rendered);
        if (empty($write['ok'])) {
            self::restoreWpCacheMutation($fs, $configMutation);
            if (!empty($backup['path'])) {
                self::restoreBackup($fs, (string) $backup['path'], $target, (string) ($backup['sha256'] ?? ''));
            }
            return self::error(__('YPerf could not atomically install advanced-cache.php.', 'yablonsky-performance'));
        }

        $record = array(
            'schema' => 1,
            'state' => 'active',
            'activated_at' => time(),
            'version' => YPERF_VERSION,
            'ttl' => self::TTL,
            'dropin_sha256' => hash('sha256', $rendered),
            'wp_cache_mutation' => $configMutation,
            'handover_backup' => $backup,
            'cache_dir_fingerprint' => hash('sha256', self::cacheDir()),
            'last_proof' => array(),
        );
        update_option(self::OPTION, $record, false);
        self::purgeAll('activation');
        return array('ok' => true, 'state' => 'active', 'record' => self::publicRecord($record));
    }

    /** @return array<string,mixed> */
    public static function disable(bool $restoreHandover = false): array
    {
        $fs = self::filesystem();
        if ($fs === null) {
            return self::error(__('WordPress Filesystem could not obtain direct write access for page-cache rollback.', 'yablonsky-performance'));
        }
        $record = self::last();
        $ownership = DropinOwnership::inspect();
        $advanced = is_array($ownership['advanced_cache'] ?? null) ? $ownership['advanced_cache'] : array();
        if (!empty($advanced['present']) && (string) ($advanced['owner'] ?? '') === 'yablonsky-performance') {
            $expected = (string) ($record['dropin_sha256'] ?? '');
            $actual = (string) ($advanced['sha256'] ?? '');
            if ($expected !== '' && $actual !== '' && !hash_equals($expected, $actual)) {
                return self::error(__('The YPerf advanced-cache.php hash changed after activation. Automatic removal is blocked for review.', 'yablonsky-performance'));
            }
            if (!$fs->delete(self::dropinPath(), false, 'f')) {
                return self::error(__('YPerf could not remove its advanced-cache.php drop-in.', 'yablonsky-performance'));
            }
        }

        self::purgeAll('disable');
        if (is_array($record['wp_cache_mutation'] ?? null)) {
            self::restoreWpCacheMutation($fs, (array) $record['wp_cache_mutation']);
        }
        if ($restoreHandover && is_array($record['handover_backup'] ?? null)) {
            $backup = (array) $record['handover_backup'];
            if (!empty($backup['path'])) {
                self::restoreBackup($fs, (string) $backup['path'], self::dropinPath(), (string) ($backup['sha256'] ?? ''));
            }
        }
        update_option(self::OPTION, array(
            'schema' => 1,
            'state' => 'off',
            'disabled_at' => time(),
            'version' => YPERF_VERSION,
        ), false);
        return array('ok' => true, 'state' => 'off');
    }

    /**
     * Refresh a compatible previous YPerf Page Cache artifact to the current
     * build under an explicit write + proof + rollback boundary.
     *
     * @return array<string,mixed>
     */
    public static function refreshOwnedArtifact(): array
    {
        $status = self::status();
        if (empty($status['enabled'])) {
            return self::error(__('YPerf Page Cache must be enabled before its executor artifact can be refreshed.', 'yablonsky-performance'));
        }
        if (empty($status['dropin_owned']) || empty($status['dropin_hash_matches'])) {
            return self::error(__('The current advanced-cache.php ownership or hash is ambiguous. Artifact refresh is blocked for review.', 'yablonsky-performance'));
        }
        if (!empty($status['dropin_version_current'])) {
            return array('ok' => true, 'state' => 'already_current', 'version' => YPERF_VERSION);
        }
        if (empty($status['dropin_version_compatible']) && empty($status['dropin_version_refreshable'])) {
            return self::error(__('The existing YPerf Page Cache artifact is not in the reviewed refresh chain. Refresh is blocked.', 'yablonsky-performance'));
        }

        $fs = self::filesystem();
        if ($fs === null) {
            return self::error(__('WordPress Filesystem could not obtain direct write access for the Page Cache artifact refresh.', 'yablonsky-performance'));
        }
        $target = self::dropinPath();
        if (is_link($target)) {
            return self::error(__('The Page Cache drop-in path is a symlink. Artifact refresh is blocked for review.', 'yablonsky-performance'));
        }
        $before = $fs->get_contents($target);
        if (!is_string($before) || $before === '') {
            return self::error(__('The existing YPerf Page Cache artifact could not be read for rollback.', 'yablonsky-performance'));
        }
        $beforeSha = hash('sha256', $before);
        $recordBefore = self::last();
        $expectedSha = (string) ($recordBefore['dropin_sha256'] ?? '');
        if ($expectedSha === '' || !hash_equals($expectedSha, $beforeSha)) {
            return self::error(__('The stored Page Cache artifact hash does not match the live file. Refresh is blocked.', 'yablonsky-performance'));
        }

        $template = $fs->get_contents(YPERF_DIR . 'dropins/advanced-cache.php.tpl');
        if (!is_string($template) || $template === '') {
            return self::error(__('The YPerf advanced-cache.php template is unavailable.', 'yablonsky-performance'));
        }
        $rendered = str_replace(
            array('__YPERF_VERSION__', '__YPERF_TTL__', '__YPERF_BROWSER_MAX_AGE__'),
            array(self::escapeSingleQuotedPhpString(YPERF_VERSION), (string) self::TTL, BrowserCacheController::enabled() ? '60' : '0'),
            $template
        );
        if (strpos($rendered, '__YPERF_') !== false) {
            return self::error(__('The YPerf page-cache drop-in template could not be rendered safely.', 'yablonsky-performance'));
        }

        $write = self::atomicWrite($fs, $target, $rendered);
        if (empty($write['ok'])) {
            return self::error(__('YPerf could not atomically refresh advanced-cache.php.', 'yablonsky-performance'));
        }
        $after = $fs->get_contents($target);
        $afterSha = is_string($after) ? hash('sha256', $after) : '';
        if ($afterSha === '' || !hash_equals(hash('sha256', $rendered), $afterSha)) {
            self::restoreArtifactRefresh($fs, $target, $before, $beforeSha, $recordBefore);
            return self::error(__('The refreshed Page Cache artifact failed its read-back hash check and was rolled back.', 'yablonsky-performance'));
        }

        $record = $recordBefore;
        $record['version'] = YPERF_VERSION;
        $record['dropin_sha256'] = $afterSha;
        $record['artifact_refreshed_at'] = time();
        $record['artifact_refreshed_from_version'] = sanitize_text_field((string) ($status['dropin_version'] ?? ''));
        update_option(self::OPTION, $record, false);
        self::purgeAll('artifact_refresh');

        $proof = self::prove();
        if (empty($proof['ok'])) {
            $rollback = self::restoreArtifactRefresh($fs, $target, $before, $beforeSha, $recordBefore);
            return array(
                'ok' => false,
                'state' => !empty($rollback['ok']) ? 'proof_failed_rolled_back' : 'proof_failed_rollback_failed',
                'message' => !empty($rollback['ok'])
                    ? __('The refreshed Page Cache artifact failed MISS → HIT proof and the previous artifact was restored.', 'yablonsky-performance')
                    : __('The refreshed Page Cache artifact failed proof and automatic rollback could not be confirmed. Stop and review before continuing.', 'yablonsky-performance'),
            );
        }

        $runtime = RuntimeAcceptance::run();
        update_option('yperf_runtime_acceptance_v1', $runtime, false);
        Telemetry::recordRuntime($runtime);
        RuntimeHistory::record($runtime, 'page_cache_artifact_refresh');
        $summary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        $artifactGateFailures = self::artifactRefreshGateFailures($runtime);
        if ($artifactGateFailures !== array()) {
            $rollback = self::restoreArtifactRefresh($fs, $target, $before, $beforeSha, $recordBefore);
            return array(
                'ok' => false,
                'state' => !empty($rollback['ok']) ? 'runtime_failed_rolled_back' : 'runtime_failed_rollback_failed',
                'message' => !empty($rollback['ok'])
                    ? __('Runtime Acceptance failed after Page Cache artifact refresh, so the previous artifact was restored.', 'yablonsky-performance')
                    : __('Runtime Acceptance failed after artifact refresh and automatic rollback could not be confirmed. Stop and review before continuing.', 'yablonsky-performance'),
                'artifact_gate_failures' => $artifactGateFailures,
            );
        }

        return array(
            'ok' => true,
            'state' => (int) ($summary['fail'] ?? 0) > 0 ? 'artifact_refreshed_with_unrelated_runtime_failures' : 'artifact_refreshed',
            'version' => YPERF_VERSION,
            'previous_version' => sanitize_text_field((string) ($status['dropin_version'] ?? '')),
            'runtime_pass' => (int) ($summary['pass'] ?? 0),
            'runtime_fail' => (int) ($summary['fail'] ?? 0),
            'artifact_gate_failures' => array(),
        );
    }

    /** @param array<string,mixed> $runtime @return array<int,string> */
    private static function artifactRefreshGateFailures(array $runtime): array
    {
        $failures = array();
        foreach ((array) ($runtime['tests'] ?? array()) as $test) {
            if (!is_array($test) || (string) ($test['status'] ?? '') !== 'fail') {
                continue;
            }
            $name = sanitize_text_field((string) ($test['name'] ?? ''));
            if (in_array($name, array('YPerf Page Cache', 'YPerf sitemap isolation'), true)) {
                $failures[] = $name;
            }
        }
        return array_values(array_unique($failures));
    }

    /**
     * Repair/reconcile the YPerf-owned page-cache runtime under a bounded
     * write + proof + rollback boundary. This is the market-facing repair path
     * used by Optimize Now and the direct Page Cache control.
     *
     * @return array<string,mixed>
     */
    public static function repair(): array
    {
        $status = self::status();

        // Recover a live, compatible YPerf-owned executor whose option record was
        // lost or marked OFF by an older upgrade. Ownership is proved from the
        // drop-in marker itself; a mismatched stored hash still fails closed.
        if (!empty($status['runtime_installed']) && !empty($status['dropin_owned'])
            && !empty($status['dropin_version_compatible']) && empty($status['dropin_hash_matches'])) {
            $record = self::last();
            $storedSha = sanitize_text_field((string) ($record['dropin_sha256'] ?? ''));
            $ownership = DropinOwnership::inspect();
            $advanced = is_array($ownership['advanced_cache'] ?? null) ? $ownership['advanced_cache'] : array();
            $liveSha = sanitize_text_field((string) ($advanced['sha256'] ?? ''));
            if ($storedSha !== '' && $liveSha !== '' && !hash_equals($storedSha, $liveSha)) {
                return self::error(__('The live YPerf Page Cache hash differs from the stored authority record. Repair is blocked for review.', 'yablonsky-performance'));
            }
            if ($liveSha !== '') {
                $record = array_merge(array(
                    'schema' => 1,
                    'state' => 'needs_review',
                    'activated_at' => time(),
                    'version' => sanitize_text_field((string) ($status['dropin_version'] ?? YPERF_VERSION)),
                    'ttl' => self::TTL,
                    'wp_cache_mutation' => array(),
                    'handover_backup' => array(),
                    'cache_dir_fingerprint' => hash('sha256', self::cacheDir()),
                    'last_proof' => array(),
                ), is_array($record) ? $record : array());
                $record['state'] = 'needs_review';
                $record['dropin_sha256'] = $liveSha;
                $record['reconciled_at'] = time();
                update_option(self::OPTION, $record, false);
                $status = self::status();
            }
        }

        if (empty($status['enabled'])) {
            // If YPerf is genuinely off, repair means a normal safe activation.
            $activation = self::activate(false);
            if (empty($activation['ok'])) {
                return $activation;
            }
            $proof = self::prove();
            if (empty($proof['ok'])) {
                self::disable(false);
                return self::error(__('Page Cache activation could not prove MISS → HIT and was rolled back.', 'yablonsky-performance'));
            }
            return array('ok' => true, 'state' => 'activated_and_proven', 'proof' => $proof);
        }

        if (empty($status['dropin_owned']) || empty($status['dropin_hash_matches'])) {
            return self::error(__('Page Cache ownership is ambiguous. YPerf will not overwrite or remove an unproven drop-in.', 'yablonsky-performance'));
        }

        if (empty($status['dropin_version_current']) && !empty($status['dropin_version_compatible'])) {
            return self::refreshOwnedArtifact();
        }

        if (empty($status['dropin_version_compatible']) && !empty($status['dropin_version_refreshable'])) {
            return self::refreshOwnedArtifact();
        }

        if (empty($status['dropin_version_compatible'])) {
            return self::error(__('The installed YPerf Page Cache artifact is not in the reviewed compatibility chain.', 'yablonsky-performance'));
        }

        $proof = self::prove();
        if (empty($proof['ok'])) {
            return self::error(__('Page Cache is installed but could not prove a clean MISS → HIT cycle. No external cache was changed.', 'yablonsky-performance'));
        }
        return array('ok' => true, 'state' => 'reproved', 'proof' => $proof);
    }

    /** Refresh only the YPerf-owned drop-in after a local delivery-policy change. */
    public static function refreshDropinPolicy(): bool
    {
        if (!self::enabled()) {
            return true;
        }
        $fs = self::filesystem();
        if ($fs === null) {
            return false;
        }
        $status = self::status();
        if (empty($status['dropin_owned']) || empty($status['dropin_hash_matches'])) {
            return false;
        }
        $template = $fs->get_contents(YPERF_DIR . 'dropins/advanced-cache.php.tpl');
        if (!is_string($template) || $template === '') {
            return false;
        }
        $rendered = str_replace(
            array('__YPERF_VERSION__', '__YPERF_TTL__', '__YPERF_BROWSER_MAX_AGE__'),
            array(self::escapeSingleQuotedPhpString(YPERF_VERSION), (string) self::TTL, BrowserCacheController::enabled() ? '60' : '0'),
            $template
        );
        if (strpos($rendered, '__YPERF_') !== false) {
            return false;
        }
        $write = self::atomicWrite($fs, self::dropinPath(), $rendered);
        if (empty($write['ok'])) {
            return false;
        }
        $record = self::last();
        $record['dropin_sha256'] = hash('sha256', $rendered);
        $record['policy_refreshed_at'] = time();
        update_option(self::OPTION, $record, false);
        return true;
    }

    /** @return array<string,mixed> */
    public static function prove(): array
    {
        if (!self::enabled()) {
            return self::error(__('Enable the YPerf Page Cache before requesting execution proof.', 'yablonsky-performance'));
        }
        $url = home_url('/');
        // Purge the entire bounded YPerf-owned cache tree before proof. Older
        // compatible executor versions can use the same runtime while their
        // per-URL path semantics differ from the current controller. A bounded
        // own-cache purge guarantees the first proof request is genuinely cold
        // without touching another provider or server cache.
        self::purgeAll('proof');
        $first = self::probeRequest($url);
        $second = self::probeRequest($url);
        $ok = (string) ($first['cache'] ?? '') === 'MISS'
            && (string) ($second['cache'] ?? '') === 'HIT'
            && self::isCompatibleDropinVersion((string) ($first['version'] ?? ''))
            && self::isCompatibleDropinVersion((string) ($second['version'] ?? ''))
            && (int) ($first['http_code'] ?? 0) >= 200 && (int) ($first['http_code'] ?? 0) < 400
            && (int) ($second['http_code'] ?? 0) >= 200 && (int) ($second['http_code'] ?? 0) < 400;
        $proof = array(
            'ok' => $ok,
            'state' => $ok ? 'miss_then_hit_confirmed' : 'not_proven',
            'url' => $url,
            'first' => $first,
            'second' => $second,
            'proved_at' => time(),
            'version' => YPERF_VERSION,
        );
        $record = self::last();
        $record['last_proof'] = $proof;
        $record['state'] = $ok ? 'active' : 'needs_review';
        update_option(self::OPTION, $record, false);
        return $proof;
    }

    /** @return array<string,mixed> */
    public static function sampleHit(): array
    {
        if (!self::enabled()) {
            return self::error(__('Enable the YPerf Page Cache before measuring a cached HIT.', 'yablonsky-performance'));
        }
        $sample = self::probeRequest(home_url('/'));
        $cacheState = strtoupper((string) ($sample['cache'] ?? ''));
        $ok = in_array($cacheState, array('HIT', 'HIT-WAIT'), true)
            && self::isCompatibleDropinVersion((string) ($sample['version'] ?? ''))
            && (int) ($sample['http_code'] ?? 0) >= 200
            && (int) ($sample['http_code'] ?? 0) < 400;
        return array(
            'ok' => $ok,
            'state' => $ok ? 'hit_confirmed' : 'not_proven',
            'sample' => $sample,
            'measured_at' => time(),
            'version' => YPERF_VERSION,
        );
    }

    /** @param array<int,string> $urls @return array<string,int> */
    public static function purgeUrls(array $urls, string $context = 'semantic'): array
    {
        unset($context);
        $result = array(
            'requested_urls' => count($urls),
            'matched_urls' => 0,
            'deleted_files' => 0,
            'delete_failures' => 0,
        );
        $fs = self::filesystem();
        if ($fs === null) {
            $result['delete_failures'] = count($urls);
            return $result;
        }
        foreach ($urls as $url) {
            $paths = self::cachePathsForUrl((string) $url);
            if ($paths === array()) {
                continue;
            }
            ++$result['matched_urls'];
            foreach ($paths as $path) {
                if (!$fs->exists($path)) {
                    continue;
                }
                if ($fs->delete($path, false, 'f')) {
                    ++$result['deleted_files'];
                } else {
                    ++$result['delete_failures'];
                }
            }
        }
        return $result;
    }

    /** @param mixed $context @return array<string,int> */
    public static function purgeAll($context = 'semantic'): array
    {
        unset($context);
        $result = array(
            'deleted_files' => 0,
            'delete_failures' => 0,
            'scanned_shards' => 0,
        );
        $fs = self::filesystem();
        if ($fs === null) {
            $result['delete_failures'] = 1;
            return $result;
        }
        $root = self::cacheDir();
        if (!$fs->exists($root)) {
            return $result;
        }
        foreach (self::shards() as $shard) {
            $dir = trailingslashit($root) . $shard;
            if (!$fs->exists($dir)) {
                continue;
            }
            ++$result['scanned_shards'];
            $items = $fs->dirlist($dir, false, false);
            if (!is_array($items)) {
                continue;
            }
            foreach ($items as $name => $meta) {
                $name = (string) $name;
                $path = trailingslashit($dir) . $name;
                if (preg_match('/^[a-f0-9]{64}\.(?:html|json)$/', $name) === 1) {
                    if ($fs->delete($path, false, 'f')) {
                        ++$result['deleted_files'];
                    } else {
                        ++$result['delete_failures'];
                    }
                    continue;
                }

                // Corrective stale-lock hygiene. A killed PHP worker can leave a
                // per-key .lock behind indefinitely; the old proof then returns
                // BYPASS-LOCK forever even though no writer exists. Never touch a
                // fresh lock: only reap an orphan older than two minutes.
                if (preg_match('/^[a-f0-9]{64}\.lock$/', $name) !== 1) {
                    continue;
                }
                $mtime = (int) $fs->mtime($path);
                if ($mtime <= 0 || (time() - $mtime) < self::STALE_LOCK_SECONDS) {
                    continue;
                }
                if ($fs->delete($path, false, 'f')) {
                    ++$result['deleted_files'];
                } else {
                    ++$result['delete_failures'];
                }
            }
        }
        return $result;
    }

    /** @return array<string,mixed> */
    private static function probeRequest(string $url): array
    {
        $start = microtime(true);
        $response = wp_safe_remote_get($url, array(
            'timeout' => 12,
            'redirection' => 2,
            'sslverify' => true,
            'limit_response_size' => 65536,
            'user-agent' => 'Yablonsky-Performance/' . YPERF_VERSION . ' PageCacheProof',
            'headers' => array(
                'Accept' => 'text/html,application/xhtml+xml',
                'X-YPerf-Probe' => 'page-cache',
            ),
        ));
        $ms = (int) round((microtime(true) - $start) * 1000);
        if (is_wp_error($response)) {
            return array('http_code' => 0, 'cache' => '', 'ms' => $ms, 'error' => sanitize_text_field($response->get_error_message()));
        }
        return array(
            'http_code' => (int) wp_remote_retrieve_response_code($response),
            'cache' => strtoupper(sanitize_key((string) wp_remote_retrieve_header($response, 'x-yperf-cache'))),
            'version' => sanitize_text_field((string) wp_remote_retrieve_header($response, 'x-yperf-cache-version')),
            'frontend' => sanitize_key((string) wp_remote_retrieve_header($response, 'x-yperf-frontend')),
            'browser_cache' => sanitize_key((string) wp_remote_retrieve_header($response, 'x-yperf-browser-cache')),
            'age' => max(0, (int) wp_remote_retrieve_header($response, 'age')),
            'ms' => $ms,
            'error' => '',
        );
    }

    /**
     * 1.93.3 changed only wp-admin interaction timing, 1.94.0 changed proof
     * interpretation / stability measurement, 1.95.0 changes only the
     * Object Cache evidence path and 1.96.0 closes Object Cache/Origin Budget
     * security boundaries. The portable Page Cache runtime remains
     * unchanged, so an already-owned drop-in may be re-proven without a hidden
     * rewrite until an intentional Page Cache runtime boundary.
     */
    public static function isCompatibleDropinVersion(string $version): bool
    {
        if (YPERF_VERSION === '2.30.0') {
            return $version === '2.30.0';
        }
        if (YPERF_VERSION === '2.31.0') {
            return in_array($version, array('2.30.0', '2.31.0'), true);
        }
        if (YPERF_VERSION === '2.32.0') {
            return in_array($version, array('2.30.0', '2.31.0', '2.32.0'), true);
        }
        if (YPERF_VERSION === '2.33.0') {
            return in_array($version, array('2.30.0', '2.31.0', '2.32.0', '2.33.0'), true);
        }
        if (YPERF_VERSION === '2.34.0') {
            return in_array($version, array('2.30.0', '2.31.0', '2.32.0', '2.33.0', '2.34.0'), true);
        }
        if (YPERF_VERSION === '2.34.1') {
            return in_array($version, array('2.30.0', '2.31.0', '2.32.0', '2.33.0', '2.34.0', '2.34.1'), true);
        }
        if (in_array(YPERF_VERSION, array('2.38.0', '2.38.1', '2.39.0', '2.40.0', '2.41.0', '2.42.0', '2.43.0', '2.44.0', '2.45.0', '2.45.1', '2.45.2', '2.45.3', '2.45.4', '2.45.5', '2.46.2', '2.47.0', '2.47.1', '2.48.0', '2.48.1', '2.48.2', '2.49.0', '2.50.0', '2.50.1', '2.50.2', '2.51.0', '2.52.0', '2.52.1', '2.53.0', '2.54.0', '2.54.1', '2.55.0', '2.56.0'), true)) {
            return in_array($version, array('2.30.0', '2.31.0', '2.32.0', '2.33.0', '2.34.0', '2.34.1', '2.34.2', '2.34.3', '2.34.4', '2.34.5', '2.34.6', '2.34.7', '2.35.0', '2.35.1', '2.36.0', '2.37.0', '2.37.1', '2.37.2', '2.38.0', '2.38.1', '2.39.0', '2.40.0', '2.41.0', '2.42.0', '2.43.0', '2.44.0', '2.45.0', '2.45.1', '2.45.2', '2.45.3', '2.45.4', '2.45.5', '2.46.2', '2.47.0', '2.47.1', '2.48.0', '2.48.1', '2.48.2', '2.49.0', '2.50.0', '2.50.1', '2.50.2', '2.51.0', '2.52.0', '2.52.1', '2.53.0', '2.54.0', '2.54.1', '2.55.0', '2.56.0'), true);
        }
        if (YPERF_VERSION === '2.37.2') {
            return in_array($version, array('2.30.0', '2.31.0', '2.32.0', '2.33.0', '2.34.0', '2.34.1', '2.34.2', '2.34.3', '2.34.4', '2.34.5', '2.34.6', '2.34.7', '2.35.0', '2.35.1', '2.36.0', '2.37.0', '2.37.1', '2.37.2'), true);
        }
        if (YPERF_VERSION === '2.36.0') {
            return in_array($version, array('2.30.0', '2.31.0', '2.32.0', '2.33.0', '2.34.0', '2.34.1', '2.34.2', '2.34.3', '2.34.4', '2.34.5', '2.34.6', '2.34.7', '2.35.0', '2.35.1', '2.36.0'), true);
        }
        if (YPERF_VERSION === '2.35.1') {
            return in_array($version, array('2.30.0', '2.31.0', '2.32.0', '2.33.0', '2.34.0', '2.34.1', '2.34.2', '2.34.3', '2.34.4', '2.34.5', '2.34.6', '2.34.7', '2.35.0', '2.35.1'), true);
        }
        if (YPERF_VERSION === '2.35.0') {
            return in_array($version, array('2.30.0', '2.31.0', '2.32.0', '2.33.0', '2.34.0', '2.34.1', '2.34.2', '2.34.3', '2.34.4', '2.34.5', '2.34.6', '2.34.7', '2.35.0'), true);
        }
        if (YPERF_VERSION === '2.34.7') {
            return in_array($version, array('2.30.0', '2.31.0', '2.32.0', '2.33.0', '2.34.0', '2.34.1', '2.34.2', '2.34.3', '2.34.4', '2.34.5', '2.34.6', '2.34.7'), true);
        }
        if (YPERF_VERSION === '2.34.6') {
            return in_array($version, array('2.30.0', '2.31.0', '2.32.0', '2.33.0', '2.34.0', '2.34.1', '2.34.2', '2.34.3', '2.34.4', '2.34.5', '2.34.6'), true);
        }
        if (YPERF_VERSION === '2.34.5') {
            return in_array($version, array('2.30.0', '2.31.0', '2.32.0', '2.33.0', '2.34.0', '2.34.1', '2.34.2', '2.34.3', '2.34.4', '2.34.5'), true);
        }
        if ($version === YPERF_VERSION) {
            return true;
        }
        $compatibility = array(
            '1.93.3' => array('1.93.2'),
            '1.94.0' => array('1.93.2', '1.93.3'),
            '1.95.0' => array('1.93.2', '1.93.3', '1.94.0'),
            '1.96.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0'),
            // 1.97.0 changes evidence truth, lifecycle and admin accessibility.
            // The portable advanced-cache.php execution boundary is unchanged.
            '1.97.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0'),
            // 1.99.0 is a market-RC/admin/package closeout; portable runtime semantics are unchanged.
            '1.99.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0'),
            // 2.0.0 changes Host Intelligence/lifecycle/runtime truth aggregation, not the portable cache hot path.
            '2.0.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0'),
            // 2.1.0 changes the market UI, not the portable cache hot path.
            '2.1.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0'),
            // 2.2.0 reconciles cache state/proof semantics and market controls.
            // The drop-in wire/runtime format remains compatible.
            '2.2.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0'),
            // 2.3.0 changes product control, migration compliance and runtime truth reconciliation; the portable cache hot path stays compatible.
            '2.3.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0'),
            // 2.3.1 is a UI/compliance patch; the portable cache hot path is unchanged.
            '2.3.1' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0'),
            // 2.3.2 is layout-only; portable cache runtime remains unchanged.
            '2.3.2' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1'),
            // 2.4.0-2.10.0 build frontend/admin behavior outside the pre-bootstrap Page Cache hot path.
            // The advanced-cache.php wire/runtime format is unchanged, so already-owned historical executors remain repairable.
            '2.4.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2'),
            '2.5.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0'),
            '2.6.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0'),
            '2.7.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0'),
            '2.8.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0'),
            '2.8.1' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0'),
            // 2.9.0 changes first-paint/frontend scheduling and admin progress only; the pre-bootstrap Page Cache format stays compatible.
            '2.9.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1'),
            // 2.10.0 tightens paint stability only; Page Cache pre-bootstrap format remains compatible.
            '2.10.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0'),
            // 2.11.0 changes cached-HTML frontend delivery only; the pre-bootstrap Page Cache format remains compatible.
            '2.11.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0'),
            // 2.12.0 changes only post-bootstrap frontend delivery and locale scope; Page Cache format stays compatible.
            '2.12.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0', '2.11.0'),
            // 2.13.0 is a post-bootstrap paint-stability/UI corrective release; Page Cache format stays compatible.
            '2.13.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0', '2.11.0', '2.12.0'),
            // 2.14.0 changes only post-bootstrap frontend scheduling/style ownership evidence; Page Cache format stays compatible.
            '2.14.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0', '2.11.0', '2.12.0', '2.13.0'),
            // 2.18.0 changes only post-bootstrap CSS ownership capture; Page Cache wire/runtime format remains compatible through 2.17.0.
            '2.18.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0', '2.11.0', '2.12.0', '2.13.0', '2.14.0', '2.15.0', '2.16.0', '2.17.0'),
            // 2.19.0 changes only pre-rewrite CSS capture and Cloudflare scheduler isolation; Page Cache wire/runtime format remains compatible through 2.18.0.
            '2.19.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0', '2.11.0', '2.12.0', '2.13.0', '2.14.0', '2.15.0', '2.16.0', '2.17.0', '2.18.0'),
            // 2.20.0 adds post-acceptance bounded prewarm orchestration and edge-delivery evidence only; Page Cache wire/runtime format is unchanged.
            '2.20.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0', '2.11.0', '2.12.0', '2.13.0', '2.14.0', '2.15.0', '2.16.0', '2.17.0', '2.18.0', '2.19.0'),
            // 2.21.0 adds an explicit Cloudflare provider adapter and purge sync only; Page Cache wire/runtime format is unchanged.
            '2.21.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0', '2.11.0', '2.12.0', '2.13.0', '2.14.0', '2.15.0', '2.16.0', '2.17.0', '2.18.0', '2.19.0', '2.20.0'),
            // 2.22.0 hardens only the Cloudflare admin workspace UX; Page Cache wire/runtime format is unchanged.
            '2.22.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0', '2.11.0', '2.12.0', '2.13.0', '2.14.0', '2.15.0', '2.16.0', '2.17.0', '2.18.0', '2.19.0', '2.20.0', '2.21.0'),
            // 2.23.0 changes cache-control/admin UX only; Page Cache wire/runtime format is unchanged.
            '2.23.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0', '2.11.0', '2.12.0', '2.13.0', '2.14.0', '2.15.0', '2.16.0', '2.17.0', '2.18.0', '2.19.0', '2.20.0', '2.21.0', '2.22.0'),
            // 2.29.0 changes only post-bootstrap CSS ownership/proof behavior; Page Cache wire/runtime format is unchanged.
            '2.29.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0', '2.11.0', '2.12.0', '2.13.0', '2.14.0', '2.15.0', '2.16.0', '2.17.0', '2.18.0', '2.19.0', '2.20.0', '2.21.0', '2.22.0', '2.23.0', '2.24.0', '2.24.1', '2.24.2', '2.24.3', '2.25.0', '2.26.0', '2.27.0', '2.28.0'),
            // 2.29.1 changes only signed Runtime Acceptance proof routing; Page Cache wire/runtime format is unchanged.
            '2.29.1' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0', '2.11.0', '2.12.0', '2.13.0', '2.14.0', '2.15.0', '2.16.0', '2.17.0', '2.18.0', '2.19.0', '2.20.0', '2.21.0', '2.22.0', '2.23.0', '2.24.0', '2.24.1', '2.24.2', '2.24.3', '2.25.0', '2.26.0', '2.27.0', '2.28.0', '2.29.0'),
            // 2.29.2 changes only Runtime Acceptance diagnostics/export evidence; Page Cache wire/runtime format is unchanged.
            '2.29.2' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0', '2.11.0', '2.12.0', '2.13.0', '2.14.0', '2.15.0', '2.16.0', '2.17.0', '2.18.0', '2.19.0', '2.20.0', '2.21.0', '2.22.0', '2.23.0', '2.24.0', '2.24.1', '2.24.2', '2.24.3', '2.25.0', '2.26.0', '2.27.0', '2.28.0', '2.29.0', '2.29.1'),

            // 2.28.0 changes only post-bootstrap CSS ownership/proof behavior; Page Cache wire/runtime format is unchanged.
            '2.28.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0', '2.11.0', '2.12.0', '2.13.0', '2.14.0', '2.15.0', '2.16.0', '2.17.0', '2.18.0', '2.19.0', '2.20.0', '2.21.0', '2.22.0', '2.23.0', '2.24.0', '2.24.1', '2.24.2', '2.24.3', '2.25.0', '2.26.0', '2.27.0'),

            // 2.27.0 changes only post-bootstrap CSS ownership/proof behavior; Page Cache wire/runtime format is unchanged.
            '2.27.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0', '2.11.0', '2.12.0', '2.13.0', '2.14.0', '2.15.0', '2.16.0', '2.17.0', '2.18.0', '2.19.0', '2.20.0', '2.21.0', '2.22.0', '2.23.0', '2.24.0', '2.24.1', '2.24.2', '2.24.3', '2.25.0', '2.26.0'),

            // 2.26.0 changes only post-bootstrap CSS ownership/proof behavior; Page Cache wire/runtime format is unchanged.
            '2.26.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0', '2.11.0', '2.12.0', '2.13.0', '2.14.0', '2.15.0', '2.16.0', '2.17.0', '2.18.0', '2.19.0', '2.20.0', '2.21.0', '2.22.0', '2.23.0', '2.24.0', '2.24.1', '2.24.2', '2.24.3', '2.25.0'),

            // 2.25.0 changes only post-bootstrap CSS ownership/proof behavior; Page Cache wire/runtime format is unchanged.
            '2.25.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0', '2.11.0', '2.12.0', '2.13.0', '2.14.0', '2.15.0', '2.16.0', '2.17.0', '2.18.0', '2.19.0', '2.20.0', '2.21.0', '2.22.0', '2.23.0', '2.24.0', '2.24.1', '2.24.2', '2.24.3'),

            // 2.24.3 is a readme/Plugin Check corrective patch only; Page Cache wire/runtime format is unchanged.
            '2.24.3' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0', '2.11.0', '2.12.0', '2.13.0', '2.14.0', '2.15.0', '2.16.0', '2.17.0', '2.18.0', '2.19.0', '2.20.0', '2.21.0', '2.22.0', '2.23.0', '2.24.0', '2.24.1', '2.24.2'),
            // 2.24.2 is a readme/Plugin Check corrective patch only; Page Cache wire/runtime format is unchanged.
            '2.24.2' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0', '2.11.0', '2.12.0', '2.13.0', '2.14.0', '2.15.0', '2.16.0', '2.17.0', '2.18.0', '2.19.0', '2.20.0', '2.21.0', '2.22.0', '2.23.0', '2.24.0', '2.24.1'),
            // 2.24.1 is a Plugin Check corrective patch only; Page Cache wire/runtime format is unchanged.
            '2.24.1' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0', '2.11.0', '2.12.0', '2.13.0', '2.14.0', '2.15.0', '2.16.0', '2.17.0', '2.18.0', '2.19.0', '2.20.0', '2.21.0', '2.22.0', '2.23.0', '2.24.0'),
            // 2.24.0 changes frontend scheduling/toolbar/prewarm orchestration only; Page Cache wire/runtime format is unchanged.
            '2.24.0' => array('1.93.2', '1.93.3', '1.94.0', '1.95.0', '1.96.0', '1.97.0', '1.99.0', '2.0.0', '2.1.0', '2.2.0', '2.3.0', '2.3.1', '2.3.2', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0', '2.8.1', '2.9.0', '2.10.0', '2.11.0', '2.12.0', '2.13.0', '2.14.0', '2.15.0', '2.16.0', '2.17.0', '2.18.0', '2.19.0', '2.20.0', '2.21.0', '2.22.0', '2.23.0'),
        );
        return in_array($version, $compatibility[YPERF_VERSION] ?? array(), true);
    }

    /**
     * Older exact YPerf artifacts remain eligible only as guarded refresh
     * sources after the 2.30 pre-bootstrap XML/service-route boundary change.
     */
    private static function isRefreshableDropinVersion(string $version): bool
    {
        if ($version === '' || $version === YPERF_VERSION) {
            return false;
        }
        if (preg_match('/^\d+\.\d+\.\d+$/', $version) !== 1) {
            return false;
        }
        return version_compare($version, '1.93.2', '>=') && version_compare($version, '2.29.2', '<=');
    }

    /** @return array<int,string> */
    private static function cachePathsForUrl(string $url): array
    {
        $parts = wp_parse_url(esc_url_raw($url));
        if (!is_array($parts) || empty($parts['host'])) {
            return array();
        }
        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        $host = strtolower((string) $parts['host']);
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $path = (string) ($parts['path'] ?? '/');
        if ($path === '') {
            $path = '/';
        }
        $key = hash('sha256', $scheme . '://' . $host . $port . $path);
        $dir = trailingslashit(self::cacheDir()) . substr($key, 0, 2) . '/';
        return array($dir . $key . '.html', $dir . $key . '.json');
    }

    /** @param object $fs */
    private static function prepareCacheTree($fs, string $root): bool
    {
        $contentRoot = untrailingslashit((string) WP_CONTENT_DIR);
        $root = untrailingslashit($root);
        if ($contentRoot === '' || $root === '' || strpos($root, $contentRoot . DIRECTORY_SEPARATOR) !== 0) {
            return false;
        }

        // WP_Filesystem::mkdir() is not recursive. Build each bounded level
        // explicitly so a clean post-migration site does not fail merely
        // because wp-content/cache/yablonsky-performance does not exist yet.
        $relative = trim(substr($root, strlen($contentRoot)), '/\\');
        $parts = array_values(array_filter(preg_split('~[\\/]+~', $relative) ?: array()));
        $current = $contentRoot;
        $chmod = defined('FS_CHMOD_DIR') ? FS_CHMOD_DIR : 0755;
        foreach ($parts as $part) {
            if (!preg_match('/^[A-Za-z0-9._-]+$/', (string) $part)) {
                return false;
            }
            $current = trailingslashit($current) . $part;
            if (!$fs->exists($current) && !$fs->mkdir($current, $chmod)) {
                return false;
            }
            if (method_exists($fs, 'is_dir') && !$fs->is_dir($current)) {
                return false;
            }
        }

        foreach (self::shards() as $shard) {
            $dir = trailingslashit($root) . $shard;
            if (!$fs->exists($dir) && !$fs->mkdir($dir, $chmod)) {
                return false;
            }
        }
        return true;
    }

    /** @return array<int,string> */
    private static function shards(): array
    {
        $out = array();
        foreach (str_split('0123456789abcdef') as $a) {
            foreach (str_split('0123456789abcdef') as $b) {
                $out[] = $a . $b;
            }
        }
        return $out;
    }

    /** @param object $fs @return array<string,mixed> */
    private static function ensureWpCacheTrue($fs): array
    {
        $path = self::wpConfigPath($fs);
        if ($path === '') {
            return array('ok' => false, 'message' => __('wp-config.php could not be located safely.', 'yablonsky-performance'));
        }
        $contents = $fs->get_contents($path);
        if (!is_string($contents) || $contents === '') {
            return array('ok' => false, 'message' => __('wp-config.php could not be read safely.', 'yablonsky-performance'));
        }
        if (strpos($contents, self::CONFIG_BEGIN) !== false) {
            return array('ok' => true, 'mode' => 'yperf_existing', 'path_fingerprint' => hash('sha256', $path));
        }
        $pattern = '/define\s*\(\s*([\'\"])WP_CACHE\1\s*,\s*(true|false)\s*\)\s*;/i';
        if (preg_match($pattern, $contents, $match) === 1) {
            if (strtolower((string) $match[2]) === 'true') {
                return array('ok' => true, 'mode' => 'external_true', 'path_fingerprint' => hash('sha256', $path));
            }
            $original = (string) $match[0];
            $block = self::CONFIG_BEGIN . "\ndefine( 'WP_CACHE', true );\n" . self::CONFIG_END;
            $updated = preg_replace($pattern, $block, $contents, 1);
            if (!is_string($updated) || $updated === $contents || !self::atomicWrite($fs, $path, $updated)['ok']) {
                return array('ok' => false, 'message' => __('The existing WP_CACHE=false declaration could not be changed safely.', 'yablonsky-performance'));
            }
            return array('ok' => true, 'mode' => 'replaced_false', 'original' => $original, 'path_fingerprint' => hash('sha256', $path));
        }
        $block = self::CONFIG_BEGIN . "\ndefine( 'WP_CACHE', true );\n" . self::CONFIG_END . "\n";
        $marker = "/* That's all, stop editing!";
        $pos = strpos($contents, $marker);
        $updated = $pos !== false ? substr($contents, 0, $pos) . $block . substr($contents, $pos) : $contents . "\n" . $block;
        if (!self::atomicWrite($fs, $path, $updated)['ok']) {
            return array('ok' => false, 'message' => __('WP_CACHE could not be added to wp-config.php safely.', 'yablonsky-performance'));
        }
        return array('ok' => true, 'mode' => 'inserted', 'path_fingerprint' => hash('sha256', $path));
    }

    /** @param object $fs @param array<string,mixed> $mutation */
    private static function restoreWpCacheMutation($fs, array $mutation): void
    {
        $mode = (string) ($mutation['mode'] ?? '');
        if (!in_array($mode, array('inserted', 'replaced_false', 'yperf_existing'), true)) {
            return;
        }
        $path = self::wpConfigPath($fs);
        if ($path === '') {
            return;
        }
        $contents = $fs->get_contents($path);
        if (!is_string($contents) || strpos($contents, self::CONFIG_BEGIN) === false) {
            return;
        }
        $pattern = '/' . preg_quote(self::CONFIG_BEGIN, '/') . '\s*define\s*\(\s*[\'\"]WP_CACHE[\'\"]\s*,\s*true\s*\)\s*;\s*' . preg_quote(self::CONFIG_END, '/') . '\s*/i';
        if ($mode === 'replaced_false' && !empty($mutation['original'])) {
            $updated = preg_replace($pattern, (string) $mutation['original'] . "\n", $contents, 1);
        } else {
            $updated = preg_replace($pattern, '', $contents, 1);
        }
        if (is_string($updated) && $updated !== $contents) {
            self::atomicWrite($fs, $path, $updated);
        }
    }

    /** @param object $fs @return array<string,mixed> */
    private static function backupExistingDropin($fs, string $target, string $expectedSha): array
    {
        $contents = $fs->get_contents($target);
        if (!is_string($contents) || $contents === '') {
            return array('ok' => false);
        }
        $sha = hash('sha256', $contents);
        if ($expectedSha !== '' && !hash_equals($expectedSha, $sha)) {
            return array('ok' => false);
        }
        $dir = trailingslashit(dirname(self::cacheDir())) . 'migration-backups';
        if (!$fs->exists($dir) && !$fs->mkdir($dir, defined('FS_CHMOD_DIR') ? FS_CHMOD_DIR : 0755, true)) {
            return array('ok' => false);
        }
        $path = trailingslashit($dir) . 'advanced-cache-' . substr($sha, 0, 16) . '.php';
        $write = self::atomicWrite($fs, $path, $contents);
        return !empty($write['ok']) ? array('ok' => true, 'path' => $path, 'sha256' => $sha) : array('ok' => false);
    }

    /** @param object $fs */
    private static function restoreBackup($fs, string $backup, string $target, string $expectedSha): bool
    {
        if (!$fs->exists($backup)) {
            return false;
        }
        $contents = $fs->get_contents($backup);
        if (!is_string($contents) || $contents === '') {
            return false;
        }
        $sha = hash('sha256', $contents);
        if ($expectedSha !== '' && !hash_equals($expectedSha, $sha)) {
            return false;
        }
        return !empty(self::atomicWrite($fs, $target, $contents)['ok']);
    }

    /** @param object $fs @return array<string,mixed> */
    private static function restoreArtifactRefresh($fs, string $target, string $before, string $beforeSha, array $recordBefore): array
    {
        if (is_link($target)) {
            return array('ok' => false, 'state' => 'symlink_review_required');
        }
        $restore = self::atomicWrite($fs, $target, $before);
        if (empty($restore['ok'])) {
            return array('ok' => false, 'state' => 'write_failed');
        }
        $readBack = $fs->get_contents($target);
        if (!is_string($readBack) || !hash_equals($beforeSha, hash('sha256', $readBack))) {
            return array('ok' => false, 'state' => 'readback_failed');
        }
        update_option(self::OPTION, $recordBefore, false);
        self::purgeAll('artifact_refresh_rollback');
        return array('ok' => true, 'state' => 'restored');
    }

    /** @param object $fs @return array<string,mixed> */
    private static function atomicWrite($fs, string $target, string $contents): array
    {
        $temporary = $target . '.yperf-tmp';
        if ($fs->exists($temporary)) {
            $fs->delete($temporary, false, 'f');
        }
        $chmod = defined('FS_CHMOD_FILE') ? FS_CHMOD_FILE : 0644;
        if (!$fs->put_contents($temporary, $contents, $chmod)) {
            return array('ok' => false);
        }
        $readback = $fs->get_contents($temporary);
        if (!is_string($readback) || !hash_equals(hash('sha256', $contents), hash('sha256', $readback))) {
            $fs->delete($temporary, false, 'f');
            return array('ok' => false);
        }
        if (!$fs->move($temporary, $target, true)) {
            $fs->delete($temporary, false, 'f');
            return array('ok' => false);
        }
        $final = $fs->get_contents($target);
        return is_string($final) && hash_equals(hash('sha256', $contents), hash('sha256', $final))
            ? array('ok' => true, 'sha256' => hash('sha256', $final))
            : array('ok' => false);
    }

    /** @return object|null */
    private static function filesystem()
    {
        if (!function_exists('WP_Filesystem') && defined('ABSPATH')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if (!function_exists('WP_Filesystem') || !WP_Filesystem()) {
            return null;
        }
        global $wp_filesystem;
        return is_object($wp_filesystem) ? $wp_filesystem : null;
    }

    /** @param object $fs */
    private static function wpConfigPath($fs): string
    {
        $candidates = array(
            trailingslashit(ABSPATH) . 'wp-config.php',
            trailingslashit(dirname(rtrim(ABSPATH, '/\\'))) . 'wp-config.php',
        );
        foreach ($candidates as $path) {
            if ($fs->exists($path)) {
                return $path;
            }
        }
        return '';
    }

    private static function cacheDir(): string
    {
        return trailingslashit((string) WP_CONTENT_DIR) . self::CACHE_SUBDIR;
    }

    private static function dropinPath(): string
    {
        return trailingslashit((string) WP_CONTENT_DIR) . 'advanced-cache.php';
    }

    private static function escapeSingleQuotedPhpString(string $value): string
    {
        return str_replace(array('\\', "'"), array('\\\\', "\\'"), $value);
    }

    /** @param array<string,mixed> $record @return array<string,mixed> */
    private static function publicRecord(array $record): array
    {
        return array(
            'state' => sanitize_key((string) ($record['state'] ?? 'off')),
            'activated_at' => max(0, (int) ($record['activated_at'] ?? 0)),
            'version' => sanitize_text_field((string) ($record['version'] ?? '')),
            'ttl' => max(0, (int) ($record['ttl'] ?? 0)),
            'last_proof' => is_array($record['last_proof'] ?? null) ? $record['last_proof'] : array(),
        );
    }

    /** @return array<string,mixed> */
    private static function error(string $message): array
    {
        return array('ok' => false, 'state' => 'error', 'message' => sanitize_text_field($message));
    }
}
