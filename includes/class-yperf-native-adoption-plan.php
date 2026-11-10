<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

final class NativeAdoptionPlan
{
    /** @param array<string,mixed> $caps @param array<string,mixed> $host @param array<string,mixed> $runtimeProof @return array<string,mixed> */
    public static function build(array $caps, array $host, array $runtimeProof = array(), array $pageCacheAdoption = array()): array
    {
        $nginx = is_array($host['nginx'] ?? null) ? $host['nginx'] : array();
        $fpm = is_array($host['php_fpm'] ?? null) ? $host['php_fpm'] : array();
        $redisProof = is_array($runtimeProof['object_cache']['redis'] ?? null) ? $runtimeProof['object_cache']['redis'] : array();
        $pageProof = is_array($runtimeProof['page_cache'] ?? null) ? $runtimeProof['page_cache'] : array();
        $originProof = is_array($runtimeProof['origin'] ?? null) ? $runtimeProof['origin'] : array();

        $page = array(
            'state' => 'host_snapshot_required',
            'strategy' => 'Inspect the host before proposing native page-cache ownership.',
            'risk' => 'unknown',
        );
        if ($pageProof !== array()) {
            $proofState = (string) ($pageProof['state'] ?? '');
            if (in_array($proofState, array('miss_then_hit_confirmed', 'hit_confirmed'), true)) {
                $page = array(
                    'state' => 'native_execution_proven',
                    'strategy' => 'Site-specific Nginx cache execution is proven by bounded local origin probes. Preserve the existing execution layer and move next to ownership, purge and rollback contracts.',
                    'risk' => 'review',
                    'evidence' => $pageProof,
                );
            } elseif ($proofState === 'bypass_confirmed') {
                $page = array(
                    'state' => 'native_bypass_proven',
                    'strategy' => 'The site-specific origin probe proves BYPASS. Review cacheability and Nginx bypass conditions before claiming page-cache execution.',
                    'risk' => 'attention',
                    'evidence' => $pageProof,
                );
            } elseif (strpos($proofState, 'native_configured') === 0 || $proofState === 'global_native_config_visible_site_unproven') {
                $page = array(
                    'state' => 'native_configured_execution_unproven',
                    'strategy' => 'Nginx FastCGI cache is configured, but site-specific HIT/MISS/BYPASS execution is not yet proven. Do not replace or re-enable anything; expose proof first.',
                    'risk' => 'review',
                    'evidence' => $pageProof,
                );
            }
        } elseif ($host !== array()) {
            $cachePaths = (int) ($nginx['fastcgi_cache_paths'] ?? 0);
            $cacheUses = (int) ($nginx['fastcgi_cache_uses'] ?? 0);
            if (!empty($nginx['installed']) && $cachePaths > 0) {
                $page = array(
                    'state' => 'adopt_existing_native',
                    'strategy' => 'Existing Nginx FastCGI cache directives are visible. Capture exact site ownership and build a no-change adoption diff first.',
                    'risk' => $cacheUses > 0 ? 'review' : 'attention',
                    'evidence' => array('fastcgi_cache_paths' => $cachePaths, 'fastcgi_cache_uses' => $cacheUses),
                );
            }
        }

        $object = array(
            'state' => 'benchmark_first',
            'strategy' => 'Benchmark Redis/Valkey/Memcached and preserve the current object-cache state until explicit approval.',
            'risk' => 'review',
        );
        if ($redisProof !== array()) {
            $state = (string) ($redisProof['state'] ?? '');
            if ($state === 'high_memory_pressure' || $state === 'active_eviction_pressure') {
                $object = array(
                    'state' => 'redis_pressure_review',
                    'strategy' => 'Redis is fast and bounded, but current memory/eviction evidence shows pressure. Do not add a new WordPress object-cache consumer until DB/prefix ownership and isolation are verified.',
                    'risk' => 'attention',
                    'evidence' => $redisProof,
                );
            } elseif ($state === 'healthy_candidate') {
                $object = array(
                    'state' => 'redis_ready_for_review',
                    'strategy' => 'Redis is reachable and bounded without high pressure in the imported evidence. Verify current consumers and drop-in ownership before explicit object-cache adoption.',
                    'risk' => 'review',
                    'evidence' => $redisProof,
                );
            } elseif ($state === 'unbounded_attention') {
                $object = array(
                    'state' => 'redis_unbounded_attention',
                    'strategy' => 'Redis is reachable but not memory-bounded. Do not adopt it for WordPress object caching until maxmemory and eviction policy are reviewed.',
                    'risk' => 'attention',
                    'evidence' => $redisProof,
                );
            }
        }

        $origin = array(
            'state' => 'host_snapshot_required',
            'strategy' => 'Origin Budget needs host-level PHP-FPM pool evidence before any recommendation.',
            'risk' => 'unknown',
        );
        if ($originProof !== array()) {
            if (($originProof['state'] ?? '') === 'site_pool_mapped') {
                $origin = array(
                    'state' => 'site_fpm_pool_mapped',
                    'strategy' => 'The site is mapped to a concrete PHP-FPM pool. Use queue, RSS and request-cost evidence before changing max_children or max_requests.',
                    'risk' => 'review',
                    'evidence' => $originProof,
                );
            } elseif (!empty($fpm['installed'])) {
                $origin = array(
                    'state' => 'fpm_visible_review',
                    'strategy' => 'PHP-FPM global pool limits are visible, but the site-specific pool is not mapped yet. Do not apply an Origin Budget change from aggregate limits.',
                    'risk' => 'review',
                    'evidence' => $originProof,
                );
            }
        }

        if (!empty($pageCacheAdoption['adopted'])) {
            $page['state'] = 'native_adopted_read_only';
            $page['strategy'] = 'Existing Nginx FastCGI cache is recognized as the authoritative page-cache executor. Prewarm can use normal GET traffic; native purge/config writes remain separate reviewed host capabilities.';
            $page['risk'] = 'review';
            $page['adoption'] = $pageCacheAdoption;
        }

        return array(
            'schema' => 4,
            'generated_at' => time(),
            'mode' => 'read_only_plan',
            'apply_available' => false,
            'layers' => array(
                'page_cache' => $page,
                'object_cache' => $object,
                'origin_budget' => $origin,
            ),
            'safety' => array(
                'server_changes' => false,
                'service_restart' => false,
                'credentials_required' => false,
                'next_write_step_requires_explicit_review' => true,
            ),
        );
    }
}
