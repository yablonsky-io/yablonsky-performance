<?php

declare(strict_types=1);
namespace Yablonsky\Performance;

final class AIPrivacyAudit
{
    private const MAX_BYTES = 65536;
    /** @param array<string,mixed> $evidence @return array<string,mixed> */
    public static function inspect(array $evidence): array
    {
        $json = (string) wp_json_encode($evidence, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $forbidden = array('password','api_key','token','nonce','cookie','raw_sql','filesystem_path','hostname','ip_address','site_url');
        $hits = array();
        $walk = function ($value, string $path='') use (&$walk,&$hits,$forbidden): void {
            if (!is_array($value)) { return; }
            foreach ($value as $k=>$v) {
                $key = strtolower((string) $k); $next = $path === '' ? $key : $path.'.'.$key;
                foreach ($forbidden as $term) { if (strpos($key,$term)!==false) { $hits[]=$next; break; } }
                if (is_array($v)) { $walk($v,$next); }
            }
        };
        $walk($evidence);
        $hits=array_values(array_unique($hits));
        return array('schema'=>1,'pass'=>strlen($json)<=self::MAX_BYTES && $hits===array(),'bytes'=>strlen($json),'limit_bytes'=>self::MAX_BYTES,'forbidden_key_paths'=>$hits);
    }
}
