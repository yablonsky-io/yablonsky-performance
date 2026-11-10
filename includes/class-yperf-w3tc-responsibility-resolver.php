<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Explicit, reversible retirement of legacy W3 Total Cache responsibilities.
 *
 * This resolver changes only the exact W3TC controls the administrator chose
 * inside Guided Migration. It never removes W3TC, deletes provider settings,
 * touches Cloudflare DNS/proxy configuration, or deletes provider-owned drop-ins.
 */
final class W3tcResponsibilityResolver
{
    private const OPTION = 'yperf_w3tc_responsibility_resolution_v1';
    private const BASENAME = 'w3-total-cache/w3-total-cache.php';

    /** @return array<string,mixed> */
    public static function retire(string $responsibility): array
    {
        $responsibility = sanitize_key($responsibility);
        if (!in_array($responsibility, array('full-site-delivery', 'cloudflare-extension'), true)) {
            return self::fail(__('This provider responsibility is not supported by the guided resolver.', 'yablonsky-performance'));
        }

        self::loadPluginApi();
        if (function_exists('is_plugin_active') && !is_plugin_active(self::BASENAME)) {
            return self::fail(__('W3 Total Cache is no longer active, so its responsibility cannot be changed here.', 'yablonsky-performance'));
        }
        if (!class_exists('\\W3TC\\Config')) {
            return self::fail(__('W3 Total Cache configuration is not available in this request. No provider setting was changed.', 'yablonsky-performance'));
        }

        $before = array();
        try {
            $config = new \W3TC\Config();
            $before = self::snapshot($config);

            if ($responsibility === 'cloudflare-extension') {
                $fsdEnabled = !empty($before['cdnfsd_enabled']);
                $fsdEngine = sanitize_key((string) ($before['cdnfsd_engine'] ?? ''));
                if ($fsdEnabled && $fsdEngine === 'cloudflare') {
                    return self::fail(__('Retire W3TC Full-site Delivery first. Its current execution engine depends on the Cloudflare extension.', 'yablonsky-performance'));
                }
            }

            self::rememberBefore($responsibility, $before);
            self::applyRetirement($config, $responsibility);
            $config->save();

            $verifyConfig = new \W3TC\Config();
            $after = self::snapshot($verifyConfig);
            if (!self::retirementVerified($responsibility, $after)) {
                self::restoreSnapshot($before);
                return self::fail(__('W3 Total Cache did not confirm the requested retirement. The previous provider configuration was restored.', 'yablonsky-performance'));
            }

            self::rememberAfter($responsibility, $after);
            return array(
                'ok' => true,
                'responsibility' => $responsibility,
                'message' => $responsibility === 'full-site-delivery'
                    ? __('W3TC Full-site Delivery is retired. Its engine/settings remain stored for rollback.', 'yablonsky-performance')
                    : __('The W3TC Cloudflare extension is retired. Cloudflare DNS/proxy and saved Cloudflare settings were not changed.', 'yablonsky-performance'),
                'before' => self::publicState($before),
                'after' => self::publicState($after),
            );
        } catch (\Throwable $throwable) {
            unset($throwable);
            if ($before !== array()) {
                try {
                    self::restoreSnapshot($before);
                } catch (\Throwable $restoreThrowable) {
                    unset($restoreThrowable);
                }
            }
            return self::fail(__('The provider responsibility could not be retired safely. No further migration action was attempted.', 'yablonsky-performance'));
        }
    }

    /** @return array<string,mixed> */
    public static function restoreAll(): array
    {
        $state = get_option(self::OPTION, array());
        if (!is_array($state)) {
            return array('ok' => true, 'restored' => false);
        }
        $original = is_array($state['original'] ?? null) ? $state['original'] : array();
        if ($original === array()) {
            return array('ok' => true, 'restored' => false);
        }
        if (!class_exists('\\W3TC\\Config')) {
            return self::fail(__('W3 Total Cache configuration is unavailable, so the pre-migration provider settings could not be restored.', 'yablonsky-performance'));
        }

        try {
            self::restoreSnapshot($original);
            $state['restored_at'] = time();
            $state['state'] = 'restored';
            update_option(self::OPTION, $state, false);
            return array('ok' => true, 'restored' => true);
        } catch (\Throwable $throwable) {
            unset($throwable);
            return self::fail(__('The pre-migration W3 Total Cache responsibility settings could not be restored automatically.', 'yablonsky-performance'));
        }
    }

    /** @param \W3TC\Config $config @return array<string,mixed> */
    private static function snapshot($config): array
    {
        $active = $config->get_array('extensions.active');
        $activeFrontend = $config->get_array('extensions.active_frontend');
        return array(
            'cdnfsd_enabled' => $config->get_boolean('cdnfsd.enabled'),
            'cdnfsd_engine' => sanitize_text_field($config->get_string('cdnfsd.engine')),
            'extensions_active' => is_array($active) ? $active : array(),
            'extensions_active_frontend' => is_array($activeFrontend) ? $activeFrontend : array(),
        );
    }

    /** @param \W3TC\Config $config */
    private static function applyRetirement($config, string $responsibility): void
    {
        if ($responsibility === 'full-site-delivery') {
            // Preserve engine/provider settings. Only the explicit execution switch is retired.
            $config->set('cdnfsd.enabled', false);
            return;
        }

        $active = $config->get_array('extensions.active');
        if (!is_array($active)) {
            $active = array();
        }
        unset($active['cloudflare']);
        $config->set('extensions.active', $active);

        $activeFrontend = $config->get_array('extensions.active_frontend');
        if (!is_array($activeFrontend)) {
            $activeFrontend = array();
        }
        unset($activeFrontend['cloudflare']);
        $config->set('extensions.active_frontend', $activeFrontend);
    }

    /** @param array<string,mixed> $state */
    private static function retirementVerified(string $responsibility, array $state): bool
    {
        if ($responsibility === 'full-site-delivery') {
            return empty($state['cdnfsd_enabled']);
        }
        $active = is_array($state['extensions_active'] ?? null) ? $state['extensions_active'] : array();
        $activeFrontend = is_array($state['extensions_active_frontend'] ?? null) ? $state['extensions_active_frontend'] : array();
        return !array_key_exists('cloudflare', $active) && !array_key_exists('cloudflare', $activeFrontend);
    }

    /** @param array<string,mixed> $snapshot */
    private static function restoreSnapshot(array $snapshot): void
    {
        if (!class_exists('\\W3TC\\Config')) {
            throw new \RuntimeException('W3TC Config unavailable.');
        }
        $config = new \W3TC\Config();
        $config->set('cdnfsd.enabled', !empty($snapshot['cdnfsd_enabled']));
        $config->set('cdnfsd.engine', sanitize_text_field((string) ($snapshot['cdnfsd_engine'] ?? '')));
        $config->set('extensions.active', is_array($snapshot['extensions_active'] ?? null) ? $snapshot['extensions_active'] : array());
        $config->set('extensions.active_frontend', is_array($snapshot['extensions_active_frontend'] ?? null) ? $snapshot['extensions_active_frontend'] : array());
        $config->save();
    }

    /** @param array<string,mixed> $before */
    private static function rememberBefore(string $responsibility, array $before): void
    {
        $state = get_option(self::OPTION, array());
        if (!is_array($state)) {
            $state = array();
        }
        if (!is_array($state['original'] ?? null) || (string) ($state['state'] ?? '') === 'restored') {
            $state['original'] = $before;
            $state['started_at'] = time();
        }
        $state['state'] = 'retiring';
        $state['version'] = YPERF_VERSION;
        $state['last_responsibility'] = $responsibility;
        update_option(self::OPTION, $state, false);
    }

    /** @param array<string,mixed> $after */
    private static function rememberAfter(string $responsibility, array $after): void
    {
        $state = get_option(self::OPTION, array());
        if (!is_array($state)) {
            $state = array();
        }
        $retired = is_array($state['retired'] ?? null) ? $state['retired'] : array();
        $retired[$responsibility] = time();
        $state['retired'] = $retired;
        $state['last_after'] = $after;
        $state['state'] = 'retired';
        $state['updated_at'] = time();
        update_option(self::OPTION, $state, false);
    }

    /** @param array<string,mixed> $state @return array<string,mixed> */
    private static function publicState(array $state): array
    {
        $active = is_array($state['extensions_active'] ?? null) ? $state['extensions_active'] : array();
        return array(
            'full_site_delivery_enabled' => !empty($state['cdnfsd_enabled']),
            'full_site_delivery_engine' => sanitize_text_field((string) ($state['cdnfsd_engine'] ?? '')),
            'cloudflare_extension_active' => array_key_exists('cloudflare', $active),
        );
    }

    private static function loadPluginApi(): void
    {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
    }

    /** @return array<string,mixed> */
    private static function fail(string $message): array
    {
        return array('ok' => false, 'message' => sanitize_text_field($message));
    }
}
