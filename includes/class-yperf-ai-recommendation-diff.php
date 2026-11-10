<?php

declare(strict_types=1);
namespace Yablonsky\Performance;

final class AIRecommendationDiff
{
    /** @param array<string,mixed> $recommendation @return array<string,mixed> */
    public static function build(array $recommendation): array
    {
        $target = sanitize_key((string) ($recommendation['target_profile'] ?? 'observe'));
        $definition = PerformancePlan::profileDefinition($target);
        return array(
            'schema' => 1,
            'target_profile' => $target,
            'changes' => array_values(array_map('strval', (array) ($definition['changes'] ?? array()))),
            'scope' => 'yperf_owned_wordpress_controls',
            'host_writes' => false,
            'external_provider_writes' => false,
            'rollback_required' => true,
        );
    }
}
