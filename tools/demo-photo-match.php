<?php

/*
 |  Konu kelimesi eslestirme.
 |
 |  Duz str_contains kullanilamaz: "Corvara Sofa" icindeki "car", "bedrock"
 |  icindeki "bed" gibi yanlis eslesmeler oluyor. Kelime siniri araniyor.
 */

if (! function_exists('subjectMatches')) {
    function subjectMatches(string $haystack, string $needle): bool
    {
        $needle = trim(mb_strtolower($needle));

        if ($needle === '') {
            return false;
        }

        $pattern = '/(?<![\p{L}\p{N}])'.preg_quote($needle, '/').'(?![\p{L}\p{N}])/u';

        return (bool) preg_match($pattern, mb_strtolower($haystack));
    }
}

if (! function_exists('subjectAllowed')) {
    /**
     * @param  array{gerekli: array<int,string>, yasak: array<int,string>}  $rule
     * @return array{uygun: bool, sebep: string}
     */
    function subjectAllowed(string $haystack, array $rule): array
    {
        foreach ($rule['yasak'] as $word) {
            if (subjectMatches($haystack, $word)) {
                return ['uygun' => false, 'sebep' => "yasakli: $word"];
            }
        }

        if ($rule['gerekli'] === []) {
            return ['uygun' => true, 'sebep' => ''];
        }

        foreach ($rule['gerekli'] as $word) {
            if (subjectMatches($haystack, $word)) {
                return ['uygun' => true, 'sebep' => ''];
            }
        }

        return ['uygun' => false, 'sebep' => 'konu eslesmedi'];
    }
}
