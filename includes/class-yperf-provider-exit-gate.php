<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Evidence-aware provider exit gate.
 *
 * The gate never removes or disables a provider. It only answers whether the
 * currently inspected provider can be removed without silently dropping an
 * active capability, integration or provider-owned drop-in.
 */
final class ProviderExitGate
{
    /**
     * @param array<string,mixed> $caps
     * @param array<string,mixed> $runtime
     * @param array<string,mixed> $effectiveness
     * @param array<string,mixed> $dropins
     * @return array<string,mixed>
     */
    public static function evaluate(array $caps, array $runtime, array $effectiveness, array $dropins): array
    {
        $providers = is_array($caps['providers'] ?? null) ? $caps['providers'] : array();
        $w3tc = is_array($providers['w3-total-cache'] ?? null) ? $providers['w3-total-cache'] : array();
        if ($w3tc === array()) {
            $cleanup = W3tcMigrationExecutor::postHandoverCleanupStatus();
            if (!empty($cleanup['applicable']) && (empty($cleanup['complete']) || !empty($cleanup['required']) || !empty($cleanup['blocked']))) {
                $owner = sanitize_key((string) ($cleanup['owner'] ?? 'unknown'));
                $reason = !empty($cleanup['required'])
                    ? 'The W3 Total Cache plugin is inactive, but its high-confidence advanced-cache.php artifact still remains in wp-content.'
                    : 'The W3 Total Cache handover is recorded, but the remaining advanced-cache.php ownership is not safe to reconcile automatically.';
                $next = !empty($cleanup['required'])
                    ? 'Finish the protected cleanup step. YPerf will snapshot the exact file, retire it, run Runtime Acceptance, and restore it automatically if proof fails.'
                    : 'Review advanced-cache.php ownership before any cleanup. YPerf will not overwrite or delete an unknown provider artifact.';
                $row = self::row('advanced-cache-dropin', 'advanced-cache.php', 'blocked', 'present:' . $owner, $reason, $next);
                return array(
                    'schema' => 1,
                    'generated_at' => time(),
                    'provider' => 'W3 Total Cache',
                    'provider_version' => sanitize_text_field((string) (W3tcMigrationExecutor::last()['provider_version'] ?? '')),
                    'applicable' => true,
                    'status' => !empty($cleanup['required']) ? 'cleanup_required' : 'blocked',
                    'safe_to_remove' => false,
                    'progress_percent' => 93,
                    'ready_items' => 13,
                    'total_items' => 14,
                    'guided_progress_percent' => 93,
                    'guided_ready_items' => 13,
                    'guided_total_items' => 14,
                    'blockers' => array(self::blocker('advanced-cache-dropin', 'advanced-cache.php', $reason, $next)),
                    'rows' => array($row),
                    'summary' => 'The provider handover succeeded, but one provider-owned WordPress drop-in still needs protected cleanup and proof.',
                    'safety' => array(
                        'provider_removed' => false,
                        'provider_settings_changed' => false,
                        'dropins_deleted' => false,
                        'automatic_deactivation' => false,
                    ),
                );
            }
            return array(
                'schema' => 1,
                'generated_at' => time(),
                'provider' => '',
                'applicable' => false,
                'status' => 'not_applicable',
                'safe_to_remove' => false,
                'progress_percent' => 0,
                'ready_items' => 0,
                'total_items' => 0,
                'blockers' => array(),
                'rows' => array(),
                'summary' => 'No W3 Total Cache provider was detected.',
            );
        }

        $layers = is_array($w3tc['layers'] ?? null) ? $w3tc['layers'] : array();
        $effectiveLayers = is_array($effectiveness['layers'] ?? null) ? $effectiveness['layers'] : array();
        $rows = array();
        $blockers = array();

        if (empty($w3tc['config_readable'])) {
            $blockers[] = self::blocker(
                'provider-config-unreadable',
                'Provider configuration is not readable',
                'YPerf cannot prove which W3 Total Cache layers are enabled in this request.',
                'Re-run Environment Check / provider inspection before any removal decision.'
            );
        }

        $rows[] = self::pageCacheRow(self::layer($layers, 'page_cache'), $effectiveLayers);
        $rows[] = self::objectCacheRow(self::layer($layers, 'object_cache'), $effectiveLayers);
        $rows[] = self::simpleLayerRow('database-cache', 'Database Cache', self::layer($layers, 'database_cache'));
        $rows[] = self::simpleLayerRow('minify-assets', 'Minify & Assets', self::layer($layers, 'minify'));
        $rows[] = self::simpleLayerRow('browser-cache', 'Browser Cache', self::layer($layers, 'browser_cache'));
        $rows[] = self::fragmentRow(self::layer($layers, 'fragment_cache'));
        $rows[] = self::simpleLayerRow('cdn-edge', 'CDN / Edge', self::layer($layers, 'cdn'));
        $rows[] = self::simpleLayerRow('statistics', 'Statistics', self::layer($layers, 'statistics'));
        $rows[] = self::simpleLayerRow('lazy-load', 'Lazy Load', self::layer($layers, 'lazy_load'));
        $rows[] = self::fullSiteDeliveryRow(self::layer($layers, 'full_site_delivery'));
        $rows[] = self::cloudflareExtensionRow(
            self::layer($layers, 'cloudflare_extension'),
            $effectiveLayers,
            self::layer($layers, 'cdn'),
            self::layer($layers, 'full_site_delivery')
        );

        $advanced = is_array($dropins['advanced_cache'] ?? null) ? $dropins['advanced_cache'] : array();
        $object = is_array($dropins['object_cache'] ?? null) ? $dropins['object_cache'] : array();
        $rows[] = self::dropinRow('advanced-cache-dropin', 'advanced-cache.php', $advanced, 'w3-total-cache');
        $rows[] = self::dropinRow('object-cache-dropin', 'object-cache.php', $object, 'w3-total-cache');

        foreach ($rows as $row) {
            if (!is_array($row) || (string) ($row['state'] ?? '') !== 'blocked') {
                continue;
            }
            $blockers[] = self::blocker(
                (string) ($row['id'] ?? 'provider-exit-blocker'),
                (string) ($row['name'] ?? 'Provider exit blocker'),
                (string) ($row['reason'] ?? ''),
                (string) ($row['next'] ?? '')
            );
        }

        $runtimeSummary = is_array($runtime['summary'] ?? null) ? $runtime['summary'] : array();
        $runtimeCurrent = $runtime !== array() && (string) ($runtime['version'] ?? '') === YPERF_VERSION;
        $runtimeClean = $runtimeCurrent && (int) ($runtimeSummary['fail'] ?? 0) === 0;
        if (!$runtimeClean) {
            $blockers[] = self::blocker(
                'current-runtime-gate',
                'Current Runtime Acceptance is required',
                $runtime === array()
                    ? 'No Runtime Acceptance report is available for the provider-exit decision.'
                    : ($runtimeCurrent ? 'The current Runtime Acceptance contains a functional failure.' : 'The latest Runtime Acceptance belongs to an earlier YPerf version.'),
                'Run a clean Runtime Acceptance on the current YPerf version before removing the provider.'
            );
        }

        $total = count($rows) + 1; // + current runtime gate.
        $ready = 0;
        $guidedReady = 0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rowState = (string) ($row['state'] ?? '');
            $rowReady = in_array($rowState, array('ready', 'retire_safe', 'replacement_proven', 'not_present'), true);
            if ($rowReady) {
                ++$ready;
                ++$guidedReady;
                continue;
            }

            // The guided protected handoff intentionally reconciles W3TC's
            // advanced-cache.php cleanup. It remains a blocker for blind/manual
            // provider removal, but it must not keep the guided migration meter
            // below 100% after every human responsibility has been resolved.
            $rowId = sanitize_key((string) ($row['id'] ?? ''));
            $rowSource = sanitize_text_field((string) ($row['source'] ?? ''));
            if ($rowId === 'advanced-cache-dropin' && $rowState === 'blocked' && $rowSource === 'present:w3-total-cache') {
                ++$guidedReady;
            }
        }
        if ($runtimeClean) {
            ++$ready;
            ++$guidedReady;
        }
        $progress = $total > 0 ? (int) round(($ready / $total) * 100) : 0;
        $guidedProgress = $total > 0 ? (int) round(($guidedReady / $total) * 100) : 0;
        $safe = empty($blockers) && !empty($w3tc['config_readable']) && $runtimeClean;

        return array(
            'schema' => 1,
            'generated_at' => time(),
            'provider' => 'W3 Total Cache',
            'provider_version' => sanitize_text_field((string) ($w3tc['version'] ?? '')),
            'applicable' => true,
            'status' => $safe ? 'ready' : 'blocked',
            'safe_to_remove' => $safe,
            'progress_percent' => $progress,
            'ready_items' => $ready,
            'total_items' => $total,
            'guided_progress_percent' => $guidedProgress,
            'guided_ready_items' => $guidedReady,
            'guided_total_items' => $total,
            'blockers' => $blockers,
            'rows' => $rows,
            'runtime_gate' => array(
                'current_version' => $runtimeCurrent,
                'functional_failures' => (int) ($runtimeSummary['fail'] ?? 0),
                'state' => $runtimeClean ? 'ready' : 'blocked',
            ),
            'summary' => $safe
                ? 'All inspected W3 Total Cache responsibilities are retired or have proven independent replacements. Provider removal can be reviewed as a separate explicit operator action.'
                : 'Provider removal remains blocked until every active responsibility, integration and provider-owned drop-in has an explicit retirement or proven replacement path.',
            'safety' => array(
                'provider_removed' => false,
                'provider_settings_changed' => false,
                'dropins_deleted' => false,
                'automatic_deactivation' => false,
            ),
        );
    }

    /** @param array<string,mixed> $layers @return array<string,mixed> */
    private static function layer(array $layers, string $key): array
    {
        return is_array($layers[$key] ?? null) ? $layers[$key] : array();
    }

    /** @param array<string,mixed> $source @param array<string,mixed> $effectiveLayers @return array<string,mixed> */
    private static function pageCacheRow(array $source, array $effectiveLayers): array
    {
        if (empty($source['enabled'])) {
            return self::row('page-cache', 'Page Cache', 'ready', self::sourceState($source), 'W3 Total Cache page caching is not enabled.', 'Preserve the OFF intent unless a separately proven executor is adopted.');
        }
        $effective = is_array($effectiveLayers['page_cache'] ?? null) ? $effectiveLayers['page_cache'] : array();
        $state = sanitize_key((string) ($effective['state'] ?? 'not_proven'));
        $provider = strtolower((string) ($effective['provider'] ?? ''));
        $independent = $state === 'active' && strpos($provider, 'w3 total cache') === false && strpos($provider, 'w3tc') === false;
        return $independent
            ? self::row('page-cache', 'Page Cache', 'replacement_proven', self::sourceState($source), 'A page-cache executor is proven active independently of W3 Total Cache.', 'Keep the replacement proof and validate provider removal separately.')
            : self::row('page-cache', 'Page Cache', 'blocked', self::sourceState($source), 'W3 Total Cache page caching is enabled and no independent proven replacement is available.', 'Prove a replacement HIT/MISS/BYPASS path before provider removal.');
    }

    /** @param array<string,mixed> $source @param array<string,mixed> $effectiveLayers @return array<string,mixed> */
    private static function objectCacheRow(array $source, array $effectiveLayers): array
    {
        if (empty($source['enabled'])) {
            return self::row('object-cache', 'Object Cache', 'ready', self::sourceState($source), 'W3 Total Cache object caching is not enabled.', 'Preserve the OFF intent unless a separately reviewed persistent object cache is adopted.');
        }
        $effective = is_array($effectiveLayers['object_cache'] ?? null) ? $effectiveLayers['object_cache'] : array();
        $state = sanitize_key((string) ($effective['state'] ?? 'off'));
        $provider = strtolower((string) ($effective['provider'] ?? ''));
        $independent = $state === 'active' && strpos($provider, 'w3 total cache') === false && strpos($provider, 'w3tc') === false;
        return $independent
            ? self::row('object-cache', 'Object Cache', 'replacement_proven', self::sourceState($source), 'A persistent object cache is proven active independently of W3 Total Cache.', 'Validate drop-in ownership before provider removal.')
            : self::row('object-cache', 'Object Cache', 'blocked', self::sourceState($source), 'W3 Total Cache object caching is enabled and no independent executor is proven.', 'Adopt and prove a replacement or intentionally retire the layer before provider removal.');
    }

    /** @param array<string,mixed> $source @return array<string,mixed> */
    private static function simpleLayerRow(string $id, string $name, array $source): array
    {
        if (!empty($source['enabled'])) {
            return self::row($id, $name, 'blocked', self::sourceState($source), $name . ' is enabled in W3 Total Cache.', 'Disable/retire the layer intentionally or prove an independent replacement before provider removal.');
        }
        return self::row($id, $name, 'ready', self::sourceState($source), $name . ' is not enabled in W3 Total Cache.', 'No active execution responsibility needs to be preserved from this layer.');
    }

    /** @param array<string,mixed> $source @return array<string,mixed> */
    private static function fragmentRow(array $source): array
    {
        if (!empty($source['enabled']) && !empty($source['configured'])) {
            return self::row('fragment-cache', 'Fragment Cache', 'blocked', self::sourceState($source), 'The W3 Total Cache Fragment Cache extension is enabled with a configured engine.', 'Map the configured fragment boundaries and replacement semantics before provider removal.');
        }
        if (!empty($source['enabled'])) {
            return self::row('fragment-cache', 'Fragment Cache', 'retire_safe', self::sourceState($source), 'The extension is active but has no configured engine.', 'Treat it as an unconfigured extension and verify no custom fragment groups depend on it.');
        }
        return self::row('fragment-cache', 'Fragment Cache', 'ready', self::sourceState($source), 'Fragment Cache is not active.', 'No active fragment-cache responsibility needs migration.');
    }

    /** @param array<string,mixed> $source @return array<string,mixed> */
    private static function fullSiteDeliveryRow(array $source): array
    {
        if (!empty($source['enabled']) && !empty($source['configured'])) {
            return self::row('full-site-delivery', 'Full-site Delivery', 'blocked', self::sourceState($source), 'W3 Total Cache full-site delivery is enabled with an execution engine.', 'Move or intentionally retire full-site delivery before provider removal.');
        }
        if (!empty($source['enabled'])) {
            return self::row('full-site-delivery', 'Full-site Delivery', 'retire_safe', self::sourceState($source), 'Full-site delivery is enabled but no engine is configured.', 'Verify that no hidden provider configuration is relied on before removal.');
        }
        return self::row('full-site-delivery', 'Full-site Delivery', 'ready', self::sourceState($source), 'Full-site delivery is not enabled.', 'No active execution responsibility needs migration.');
    }

    /**
     * Treat the W3TC Cloudflare extension as a blocking responsibility only
     * when current evidence shows that W3TC still owns an executing delivery
     * path or when edge execution cannot be ruled out. If W3TC CDN/FSD are off
     * and Runtime Acceptance proves Cloudflare HTML is dynamic, the extension
     * is safe to retire together with W3TC: Cloudflare itself remains external.
     *
     * @param array<string,mixed> $source
     * @param array<string,mixed> $effectiveLayers
     * @param array<string,mixed> $cdn
     * @param array<string,mixed> $fullSiteDelivery
     * @return array<string,mixed>
     */
    private static function cloudflareExtensionRow(array $source, array $effectiveLayers, array $cdn, array $fullSiteDelivery): array
    {
        if (empty($source['enabled'])) {
            return self::row('cloudflare-extension', 'Cloudflare Extension', 'ready', self::sourceState($source), 'The W3 Total Cache Cloudflare extension is not active.', 'No W3TC-owned Cloudflare integration needs migration.');
        }

        $edge = is_array($effectiveLayers['edge_html'] ?? null) ? $effectiveLayers['edge_html'] : array();
        $edgeState = sanitize_key((string) ($edge['state'] ?? 'not_proven'));
        $w3tcDeliveryActive = !empty($cdn['enabled']) || !empty($fullSiteDelivery['enabled']);

        if (!$w3tcDeliveryActive && $edgeState === 'off') {
            return self::row(
                'cloudflare-extension',
                'Cloudflare Extension',
                'retire_safe',
                self::sourceState($source),
                'The W3 Total Cache Cloudflare extension is active, but current Runtime Acceptance proves Cloudflare HTML delivery is dynamic and W3TC CDN / Full-site Delivery are off.',
                'Cloudflare itself stays active. The W3TC integration may retire automatically with the provider, or you can disconnect it explicitly before handover.'
            );
        }

        return self::row('cloudflare-extension', 'Cloudflare Extension', 'blocked', self::sourceState($source), 'The W3 Total Cache Cloudflare extension is active and YPerf cannot yet prove that retiring the W3TC integration is non-disruptive.', 'Keep it as a blocker until W3TC CDN / Full-site Delivery are off and current Runtime Acceptance proves Cloudflare HTML is dynamic, or retire the W3TC extension explicitly.');
    }

    /** @param array<string,mixed> $dropin @return array<string,mixed> */
    private static function dropinRow(string $id, string $name, array $dropin, string $provider): array
    {
        if (empty($dropin['present'])) {
            return self::row($id, $name, 'not_present', 'absent', $name . ' is absent.', 'No provider-owned drop-in cleanup is required.');
        }
        $owner = sanitize_key((string) ($dropin['owner'] ?? 'unknown'));
        if ($owner === $provider) {
            return self::row($id, $name, 'blocked', 'present:' . $owner, $name . ' is present and attributed to W3 Total Cache.', 'Do not remove it blindly. Let the provider clean it up or use a reviewed cleanup operation with read-back verification.');
        }
        if ($owner === 'unknown' || strpos($owner, 'unknown') === 0) {
            return self::row($id, $name, 'blocked', 'present:' . $owner, $name . ' is present but ownership is not certain.', 'Resolve drop-in ownership before provider removal.');
        }
        return self::row($id, $name, 'ready', 'present:' . $owner, $name . ' belongs to another identified provider.', 'Preserve the independent drop-in and verify it remains healthy after provider removal.');
    }

    /** @param array<string,mixed> $source */
    private static function sourceState(array $source): string
    {
        if (!empty($source['enabled'])) {
            return !empty($source['configured']) ? 'enabled_configured' : 'enabled_unconfigured';
        }
        return !empty($source['configured']) ? 'configured_off' : 'off';
    }

    /** @return array<string,mixed> */
    private static function row(string $id, string $name, string $state, string $source, string $reason, string $next): array
    {
        return array(
            'id' => sanitize_key($id),
            'name' => sanitize_text_field($name),
            'state' => sanitize_key($state),
            'source' => sanitize_text_field($source),
            'reason' => sanitize_text_field($reason),
            'next' => sanitize_text_field($next),
        );
    }

    /** @return array<string,string> */
    private static function blocker(string $id, string $title, string $reason, string $next): array
    {
        return array(
            'id' => sanitize_key($id),
            'title' => sanitize_text_field($title),
            'reason' => sanitize_text_field($reason),
            'next' => sanitize_text_field($next),
        );
    }
}
