<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/** Version- and site-bound human acceptance checklist for field rollout. */
final class FieldAcceptance
{
    private const OPTION = 'yperf_field_acceptance_v1';

    public static function register(): void
    {
        add_action('admin_post_yperf_save_field_acceptance', array(self::class, 'handleSave'));
    }

    /** @return array<string,string> */
    public static function definitions(): array
    {
        return array(
            'plugin_check' => __('Official Plugin Check reviewed', 'yablonsky-performance'),
            'desktop_ui' => __('Desktop dashboard acceptance', 'yablonsky-performance'),
            'narrow_ui' => __('Narrow viewport acceptance', 'yablonsky-performance'),
            'locale_smoke' => __('Locale switch smoke test', 'yablonsky-performance'),
            'critical_routes' => __('Critical storefront/admin routes accepted', 'yablonsky-performance'),
            'migration_review' => __('Legacy provider and MU-plugin migration reviewed', 'yablonsky-performance'),
            'lifecycle_smoke' => __('Deactivate / reactivate lifecycle accepted', 'yablonsky-performance'),
        );
    }

    /** @param array<string,mixed> $releaseReadiness @param array<string,mixed> $muInventory @return array<string,mixed> */
    public static function report(array $releaseReadiness, array $muInventory): array
    {
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) { $saved = array(); }
        $bound = (string) ($saved['version'] ?? '') === YPERF_VERSION && hash_equals((string) ($saved['site_fingerprint'] ?? ''), self::siteFingerprint());
        $manual = $bound && is_array($saved['checks'] ?? null) ? $saved['checks'] : array();
        $definitions = self::definitions();
        $manualPassed = 0;
        foreach ($definitions as $id => $_label) { if (!empty($manual[$id])) { ++$manualPassed; } }
        $core = is_array($releaseReadiness['core_gates'] ?? null) ? $releaseReadiness['core_gates'] : array();
        $automatic = array(
            'runtime_current_clean' => !empty($core['runtime_current_clean']),
            'control_plane_not_blocked' => !empty($core['control_plane_not_blocked']),
            'origin_cost_evidence' => !empty($core['origin_cost_evidence']),
            'mu_inventory_captured' => !empty($muInventory['available']),
        );
        $automaticPassed = count(array_filter($automatic));
        $automaticTotal = count($automatic);
        $manualTotal = count($definitions);
        $passed = $automaticPassed + $manualPassed;
        $total = $automaticTotal + $manualTotal;
        return array(
            'schema' => 1,
            'version' => YPERF_VERSION,
            'automatic' => $automatic,
            'manual' => $manual,
            'definitions' => $definitions,
            'automatic_passed' => $automaticPassed,
            'automatic_total' => $automaticTotal,
            'manual_passed' => $manualPassed,
            'manual_total' => $manualTotal,
            'passed' => $passed,
            'total' => $total,
            'progress_percent' => $total > 0 ? (int) round(($passed / $total) * 100) : 0,
            'complete' => $passed === $total,
            'saved_at' => $bound ? (int) ($saved['saved_at'] ?? 0) : 0,
        );
    }

    public static function handleSave(): void
    {
        if (!current_user_can('manage_options')) { wp_die(esc_html__('You are not allowed to perform this action.', 'yablonsky-performance')); }
        check_admin_referer('yperf_save_field_acceptance');
        $checks = array();
        $raw_checks = filter_input(INPUT_POST, 'yperf_field_check', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        if (!is_array($raw_checks)) {
            $raw_checks = array();
        }
        foreach (array_keys(self::definitions()) as $id) {
            $raw_value = isset($raw_checks[$id]) && is_scalar($raw_checks[$id]) ? (string) $raw_checks[$id] : '';
            $value = sanitize_text_field($raw_value);
            $checks[$id] = '1' === $value;
        }
        update_option(self::OPTION, array(
            'version' => YPERF_VERSION,
            'site_fingerprint' => self::siteFingerprint(),
            'checks' => $checks,
            'saved_at' => time(),
        ), false);
        wp_safe_redirect(add_query_arg(array('page' => 'yablonsky-performance', 'yperf_tab' => 'insights', 'yperf_section' => 'insights:release', 'yperf_notice' => 'field_acceptance_saved'), admin_url('admin.php')));
        exit;
    }

    private static function siteFingerprint(): string
    {
        return hash('sha256', (string) home_url('/') . '|' . (string) get_current_blog_id());
    }
}
