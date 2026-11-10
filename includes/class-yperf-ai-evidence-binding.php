<?php

declare(strict_types=1);
namespace Yablonsky\Performance;

final class AIEvidenceBinding
{
    private const TTL = 1200;
    /** @param array<string,mixed> $evidence */
    public static function fingerprint(array $evidence): string
    {
        return hash('sha256', (string) wp_json_encode($evidence, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
    /** @param array<string,mixed> $recommendation */
    public static function fresh(array $recommendation): bool
    {
        return (string) ($recommendation['yperf_version'] ?? '') === YPERF_VERSION
            && (int) ($recommendation['generated_at'] ?? 0) >= time() - self::TTL
            && preg_match('/^[a-f0-9]{64}$/', (string) ($recommendation['evidence_fingerprint'] ?? '')) === 1;
    }
}
