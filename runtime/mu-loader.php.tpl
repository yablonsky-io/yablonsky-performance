<?php
/**
 * Plugin Name: Yablonsky Performance Runtime
 * Description: Managed early runtime bootstrap for Yablonsky Performance.
 * Version: __YPERF_VERSION__
 * Author: Yablonsky.io
 * License: GPL-2.0-or-later
 * Text Domain: yablonsky-performance
 */

// YPerf Managed MU Runtime

if (!defined('ABSPATH') || !defined('WP_PLUGIN_DIR')) {
    return;
}

$yperf_loader_version = '__YPERF_VERSION__';
$yperf_basename = 'yablonsky-performance/yablonsky-performance.php';
$yperf_active = false;

if (function_exists('get_option')) {
    $yperf_plugins = get_option('active_plugins', array());
    if (is_array($yperf_plugins) && in_array($yperf_basename, $yperf_plugins, true)) {
        $yperf_active = true;
    }
}

if (!$yperf_active && function_exists('is_multisite') && is_multisite() && function_exists('get_site_option')) {
    $yperf_network = get_site_option('active_sitewide_plugins', array());
    if (is_array($yperf_network) && isset($yperf_network[$yperf_basename])) {
        $yperf_active = true;
    }
}

if (!$yperf_active) {
    return;
}

$yperf_runtime = rtrim((string) WP_PLUGIN_DIR, '/\\') . '/yablonsky-performance/runtime/early-runtime.php';
if (!is_readable($yperf_runtime)) {
    return;
}

require_once $yperf_runtime;

if (!defined('YPERF_EARLY_RUNTIME_SOURCE_VERSION')
    || !hash_equals($yperf_loader_version, (string) YPERF_EARLY_RUNTIME_SOURCE_VERSION)
    || !class_exists('Yablonsky\\Performance\\EarlyRuntime', false)
) {
    return;
}

// Register YPerf on an explicit early-runtime phase rather than relying on
// MU filename ordering. Security runtimes can register an earlier priority
// (the YSec coordination contract reserves -1000) while YPerf owns the
// performance/capacity phase at -900. Both remain before normal plugins.
if (!defined('YPERF_EARLY_RUNTIME_PHASE_HOOK')) {
    define('YPERF_EARLY_RUNTIME_PHASE_HOOK', 'muplugins_loaded');
}
if (!defined('YPERF_EARLY_RUNTIME_PHASE_PRIORITY')) {
    define('YPERF_EARLY_RUNTIME_PHASE_PRIORITY', -900);
}

if (function_exists('add_action')) {
    add_action(
        YPERF_EARLY_RUNTIME_PHASE_HOOK,
        array('Yablonsky\\Performance\\EarlyRuntime', 'boot'),
        YPERF_EARLY_RUNTIME_PHASE_PRIORITY
    );
} else {
    \Yablonsky\Performance\EarlyRuntime::boot();
}
