<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class Migration
{
    /** @param array<string,mixed> $caps @return array<string,mixed> */
    public static function plan(array $caps, array $exitGate = array()): array
    {
        $providers = is_array($caps['providers'] ?? null) ? $caps['providers'] : array();
        $w3tc = is_array($providers['w3-total-cache'] ?? null) ? $providers['w3-total-cache'] : array();
        if ($w3tc === array()) {
            $pending = W3tcMigrationExecutor::pendingHandoff();
            if ($pending !== array() && (string) ($pending['state'] ?? '') === 'awaiting_user_deactivation') {
                return array(
                    'schema' => 3,
                    'generated_at' => time(),
                    'provider' => 'W3 Total Cache',
                    'provider_version' => sanitize_text_field((string) ($pending['provider_version'] ?? '')),
                    'available' => true,
                    'inspection_state' => 'awaiting_user_handoff',
                    'rows' => array(),
                    'safe_to_remove' => false,
                    'exit_gate' => $exitGate,
                    'summary' => 'W3 Total Cache was prepared for migration and is now inactive. Continue the user-directed handoff; YPerf did not change the provider activation status.',
                    'privacy' => array('secrets_included' => false, 'credentials_imported' => false),
                );
            }
            $cleanup = W3tcMigrationExecutor::postHandoverCleanupStatus();
            if (!empty($cleanup['applicable']) && (empty($cleanup['complete']) || !empty($cleanup['required']) || !empty($cleanup['blocked']))) {
                $last = W3tcMigrationExecutor::last();
                return array(
                    'schema' => 2,
                    'generated_at' => time(),
                    'provider' => 'W3 Total Cache',
                    'provider_version' => sanitize_text_field((string) ($last['provider_version'] ?? '')),
                    'available' => true,
                    'inspection_state' => 'post_handover_cleanup',
                    'rows' => array(),
                    'safe_to_remove' => false,
                    'exit_gate' => $exitGate,
                    'summary' => 'The provider handover is complete. Finish the protected residual drop-in cleanup before the migration is considered fully reconciled.',
                    'privacy' => array('secrets_included' => false, 'credentials_imported' => false),
                );
            }
            return array(
                'schema' => 2,
                'provider' => '',
                'available' => false,
                'rows' => array(),
                'safe_to_remove' => false,
                'summary' => 'No W3 Total Cache provider was detected.',
            );
        }

        $layers = is_array($w3tc['layers'] ?? null) ? $w3tc['layers'] : array();
        $rows = array();
        $rows[] = self::row('Page Cache', self::layerState($layers, 'page_cache'), 'Benchmark current behavior, then adopt a native or Yablonsky-controlled execution layer.', 'review');
        $rows[] = self::row('Object Cache', self::layerState($layers, 'object_cache'), 'Benchmark Redis/Memcached and only enable persistent object cache after explicit approval.', 'review');
        $rows[] = self::row('Database Cache', self::layerState($layers, 'database_cache'), 'Do not copy blindly. Keep off unless a workload benchmark proves a benefit.', 'ready');
        $rows[] = self::row('Minify & Assets', self::layerState($layers, 'minify'), 'Preserve current OFF/ON intent until Yablonsky asset optimization passes frontend acceptance.', 'review');
        $rows[] = self::row('Browser Cache', self::layerState($layers, 'browser_cache'), 'Inspect live headers and translate intent to native/server header policy.', 'review');
        $rows[] = self::row('Fragment Cache', self::layerState($layers, 'fragment_cache'), 'Migrate only configured fragment boundaries. An active extension with no engine is not an active cache.', 'ready');
        $rows[] = self::row('CDN / Edge', self::layerState($layers, 'cdn'), 'Keep external edge delivery independent. Credentials are never imported into Yablonsky reports.', 'ready');
        $rows[] = self::row('Statistics', self::layerState($layers, 'statistics'), 'Yablonsky local telemetry replaces plugin-specific statistics without a Pro gate.', 'ready');
        $rows[] = self::row('Lazy Load', self::layerState($layers, 'lazy_load'), 'Preserve current intent and avoid duplicate lazy-load ownership.', 'ready');

        return array(
            'schema'          => 2,
            'generated_at'    => time(),
            'provider'        => 'W3 Total Cache',
            'provider_version'=> sanitize_text_field((string) ($w3tc['version'] ?? '')),
            'available'       => true,
            'inspection_state'=> sanitize_key((string) ($w3tc['inspection_state'] ?? 'unknown')),
            'rows'            => $rows,
            'safe_to_remove'  => !empty($exitGate['safe_to_remove']),
            'exit_gate'       => $exitGate,
            'summary'         => 'Install Yablonsky Performance first. Remove the legacy provider only after every active layer has an accepted replacement or is intentionally retired.',
            'privacy'         => array(
                'secrets_included' => false,
                'credentials_imported' => false,
            ),
        );
    }

    /** @param array<string,mixed> $layers @return array<string,mixed> */
    private static function layerState(array $layers, string $key): array
    {
        $layer = is_array($layers[$key] ?? null) ? $layers[$key] : array();
        return array(
            'enabled'    => !empty($layer['enabled']),
            'configured' => !empty($layer['configured']),
            'engine'     => sanitize_text_field((string) ($layer['engine'] ?? 'None')),
        );
    }

    /** @param array<string,mixed> $source @return array<string,mixed> */
    private static function row(string $name, array $source, string $strategy, string $readiness): array
    {
        $state = !empty($source['enabled']) ? 'enabled' : (!empty($source['configured']) ? 'configured_off' : 'not_configured');
        return array(
            'name'      => $name,
            'source'    => $state,
            'engine'    => sanitize_text_field((string) ($source['engine'] ?? 'None')),
            'strategy'  => sanitize_text_field($strategy),
            'readiness' => sanitize_key($readiness),
        );
    }
}
