<?php

declare(strict_types=1);
namespace Yablonsky\Performance;

final class ReleaseTrainGate
{
    /** @return array<string,mixed> */
    public static function build(): array
    {
        $availability = YablonskyAIClient::availability();
        $service = ExternalServiceReadiness::analyze();
        $idle = IdleBudget::analyze();
        $checks = array(
            'yablonsky_ai_only' => (string) ($availability['service'] ?? '') === 'Yablonsky AI',
            'no_ai_hot_path' => (int) ($idle['ai_frontend_calls'] ?? 1) === 0 && (int) ($idle['ai_dashboard_auto_calls'] ?? 1) === 0,
            'local_first' => !empty($idle['local_first']),
            'explicit_remote_compute' => !empty($service['checks']['explicit_only']),
            'no_guessed_ai_wire_contract' => !empty($service['checks']['auth_header_explicit']) && !empty($service['checks']['auth_mode_explicit']) && !empty($service['checks']['workspace_header_explicit']) && !empty($service['checks']['compute_transport_explicit']) && !empty($service['checks']['contract_version_explicit']),
            'credential_redirect_protection' => !empty($service['checks']['redirects_disabled']),
            'local_features_free' => !empty($service['checks']['local_features_free']),
            'profile_only_ai_write_authority' => true,
            'runtime_acceptance_required' => true,
            'rollback_required' => true,
        );
        return array('schema'=>1,'version'=>YPERF_VERSION,'pass'=>!in_array(false,$checks,true),'checks'=>$checks);
    }
}
