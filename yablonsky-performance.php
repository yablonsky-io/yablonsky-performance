<?php
/**
 * Plugin Name: Yablonsky Performance
 * Description: Fast WordPress, automatic by default. Manual control when you want it.
 * Version: 2.56.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Yablonsky.io
 * Author URI: https://yablonsky.io/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: yablonsky-performance
 * Domain Path: /languages
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/*
 * Canonical runtime rule (1.4.0+): this package never migrates, loads,
 * deactivates or deletes a historical copy at runtime. Technical-identity
 * migration is an operator/deployment concern and the canonical plugin knows
 * only its own immutable __DIR__ code path.
 */

if (!defined('YPERF_VERSION')) {
    define('YPERF_VERSION', '2.56.0');
}
if (!defined('YPERF_FILE')) {
    define('YPERF_FILE', __FILE__);
}
if (!defined('YPERF_DIR')) {
    define('YPERF_DIR', plugin_dir_path(__FILE__));
}
if (!defined('YPERF_BASENAME')) {
    define('YPERF_BASENAME', plugin_basename(__FILE__));
}

/*
 * Canonical code isolation rule:
 * Never resolve this package's own PHP files through YPERF_DIR or any other
 * compatibility constant. A historical package may have defined those
 * constants earlier in the same request. __DIR__ is immutable and therefore
 * the only authority for loading canonical code.
 */
function yablonsky_performance_require_canonical_files(): void
{
    $canonical_dir = __DIR__ . '/';
    require_once $canonical_dir . 'includes/class-yperf-language-registry.php';
    require_once $canonical_dir . 'includes/class-yperf-state-migration.php';
    require_once $canonical_dir . 'includes/class-yperf-request-policy.php';
    require_once $canonical_dir . 'includes/class-yperf-provider-inspector.php';
    require_once $canonical_dir . 'includes/class-yperf-capability-detector.php';
    require_once $canonical_dir . 'includes/class-yperf-environment-check.php';
    require_once $canonical_dir . 'includes/class-yperf-host-snapshot.php';
    require_once $canonical_dir . 'includes/class-yperf-dropin-ownership.php';
    require_once $canonical_dir . 'includes/class-yperf-native-runtime-proof.php';
    require_once $canonical_dir . 'includes/class-yperf-native-page-cache-adoption.php';
    require_once $canonical_dir . 'includes/class-yperf-native-page-cache-operations.php';
    require_once $canonical_dir . 'includes/class-yperf-page-cache-executor.php';
    require_once $canonical_dir . 'includes/class-yperf-page-cache-campaign.php';
    require_once $canonical_dir . 'includes/class-yperf-browser-cache-controller.php';
    require_once $canonical_dir . 'includes/class-yperf-redis-evidence.php';
    require_once $canonical_dir . 'includes/class-yperf-object-cache-adoption.php';
    require_once $canonical_dir . 'includes/class-yperf-origin-budget-readiness.php';
    require_once $canonical_dir . 'includes/class-yperf-control-plane-readiness.php';
    require_once $canonical_dir . 'includes/class-yperf-object-cache-operation-contract.php';
    require_once $canonical_dir . 'includes/class-yperf-object-cache-executor.php';
    require_once $canonical_dir . 'includes/class-yperf-origin-budget-contract.php';
    require_once $canonical_dir . 'includes/class-yperf-origin-budget-adoption.php';
    require_once $canonical_dir . 'includes/class-yperf-release-readiness.php';
    require_once $canonical_dir . 'includes/class-yperf-product-progress.php';
    require_once $canonical_dir . 'includes/class-yperf-evidence-reconciler.php';
    require_once $canonical_dir . 'includes/class-yperf-native-adoption-plan.php';
    require_once $canonical_dir . 'includes/class-yperf-frontend-optimizer.php';
    require_once $canonical_dir . 'includes/class-yperf-performance-plan.php';
    require_once $canonical_dir . 'includes/class-yperf-settings-transfer.php';
    require_once $canonical_dir . 'includes/class-yperf-runtime-history.php';
    require_once $canonical_dir . 'includes/class-yperf-regression-guard.php';
    require_once $canonical_dir . 'includes/class-yperf-autopilot.php';
    require_once $canonical_dir . 'includes/class-yperf-yablonsky-ai-credentials.php';
    require_once $canonical_dir . 'includes/class-yperf-yablonsky-ai-client.php';
    require_once $canonical_dir . 'includes/class-yperf-yablonsky-ai-account.php';
    require_once $canonical_dir . 'includes/class-yperf-ai-plan-policy.php';
    require_once $canonical_dir . 'includes/class-yperf-ai-evidence-binding.php';
    require_once $canonical_dir . 'includes/class-yperf-ai-advice-history.php';
    require_once $canonical_dir . 'includes/class-yperf-idle-budget.php';
    require_once $canonical_dir . 'includes/class-yperf-ai-recommendation-diff.php';
    require_once $canonical_dir . 'includes/class-yperf-external-service-readiness.php';
    require_once $canonical_dir . 'includes/class-yperf-ai-privacy-audit.php';
    require_once $canonical_dir . 'includes/class-yperf-release-train-gate.php';
    require_once $canonical_dir . 'includes/class-yperf-ai-advisor.php';
    require_once $canonical_dir . 'includes/class-yperf-optimization-campaign.php';
    require_once $canonical_dir . 'includes/class-yperf-effective-performance.php';
    require_once $canonical_dir . 'includes/class-yperf-optimization-outcome.php';
    require_once $canonical_dir . 'includes/class-yperf-telemetry.php';
    require_once $canonical_dir . 'includes/class-yperf-benchmark.php';
    require_once $canonical_dir . 'includes/class-yperf-cacheability-inspector.php';
    require_once $canonical_dir . 'includes/class-yperf-cloudflare-credentials.php';
    require_once $canonical_dir . 'includes/class-yperf-cloudflare-client.php';
    require_once $canonical_dir . 'includes/class-yperf-cloudflare-edge.php';
    require_once $canonical_dir . 'includes/class-yperf-purge-controller.php';
    require_once $canonical_dir . 'includes/class-yperf-prewarm.php';
    require_once $canonical_dir . 'includes/class-yperf-migration.php';
    require_once $canonical_dir . 'includes/class-yperf-migration-journey.php';
    require_once $canonical_dir . 'includes/class-yperf-migration-activity.php';
    require_once $canonical_dir . 'includes/class-yperf-w3tc-responsibility-resolver.php';
    require_once $canonical_dir . 'includes/class-yperf-w3tc-migration-executor.php';
    require_once $canonical_dir . 'includes/class-yperf-mu-plugin-inventory.php';
    require_once $canonical_dir . 'includes/class-yperf-field-acceptance.php';
    require_once $canonical_dir . 'includes/class-yperf-provider-exit-gate.php';
    require_once $canonical_dir . 'includes/class-yperf-invalidation-bus.php';
    require_once $canonical_dir . 'includes/class-yperf-ymulti-invalidation-bridge.php';
    require_once $canonical_dir . 'includes/class-yperf-adoption.php';
    require_once $canonical_dir . 'includes/class-yperf-query-cost-telemetry.php';
    require_once $canonical_dir . 'includes/class-yperf-upgrade-health.php';
    require_once $canonical_dir . 'includes/class-yperf-lifecycle-policy.php';
    require_once $canonical_dir . 'includes/class-yperf-managed-runtime.php';
    require_once $canonical_dir . 'includes/class-yperf-query-cost-guard.php';
    require_once $canonical_dir . 'includes/class-yperf-runtime-acceptance.php';
    require_once $canonical_dir . 'includes/class-yperf-feature-catalog.php';
    require_once $canonical_dir . 'includes/class-yperf-recommendations.php';
    require_once $canonical_dir . 'includes/class-yperf-intelligence-report.php';
    require_once $canonical_dir . 'includes/class-yperf-admin.php';
    require_once $canonical_dir . 'includes/class-yperf-plugin.php';
}

yablonsky_performance_require_canonical_files();

register_activation_hook(__FILE__, array(Yablonsky\Performance\LifecyclePolicy::class, 'activate'));
register_deactivation_hook(__FILE__, array(Yablonsky\Performance\LifecyclePolicy::class, 'deactivate'));

Yablonsky\Performance\StateMigration::migrate();
Yablonsky\Performance\Plugin::boot();
