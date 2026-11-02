<?php

namespace App\Services\Import;

use Illuminate\Support\Str;

/**
 * Urun sayfasindan urun adini cikarir.
 *
 * h1'e guvenmek yanlis ad getirdi: vespero.com'un uc sayfasinda baslik
 * kopyala-yapistir hatasiyla baska urunu gosteriyor — /teos-kose-takimi/
 * sayfasinin h1'i "Armani Midi Kose Takimi" diyor. O sayfalarin <title>'i ise
 * adresle birebir uyusuyor.
 *
 * Bu yuzden aday adlar (h1, og:title, title) arasindan ADRESLE UYUSANI
 * seciyoruz: adres sayfanin kimligi, onu yalanlayan baslik supheli.
 */
class ProductName
{
    /**
     * @param  array<int, string>  $suffixes  baslikta tekrarlayan marka ekleri
     * @return array{0: ?string, 1: bool} [ad, adresle_uyusmadi]
     */
    public function resolve(string $html, string $url, array $suffixes = []): array
    {
        $candidates = $this->candidates($html, $suffixes);

        if ($candidates === []) {
            return [null, false];
        }

        $target = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
        $target = Str::afterLast($target, '/');

        // 1) Adresle birebir ayni
        foreach ($candidates as $name) {
            if (Str::slug($name) === $target) {
                return [$name, false];
            }
        }

        // 2) Adresin basiyla uyusan (adres sonuna "-2" gibi ek almis olabilir).
        //    Birden fazla aday uyuyorsa adrese UZUNLUKCA EN YAKINI dogrudur:
        //    "Luna Yatak – Pembe – Ravizza Yatak" da "Luna Yatak" da
        //    /luna-yatak-pembe/ ile onek iliskisi kurar, dogrusu ortadaki.
        $best = null;
        $bestDistance = PHP_INT_MAX;

        foreach ($candidates as $name) {
            $slug = Str::slug($name);

            if ($slug === '' || ! (str_starts_with($target, $slug) || str_starts_with($slug, $target))) {
                continue;
            }

            $distance = abs(strlen($slug) - strlen($target));

            if ($distance < $bestDistance) {
                $best = $name;
                $bestDistance = $distance;
            }
        }

        if ($best !== null) {
            return [$best, false];
        }

        // 3) Hicbiri uyusmuyor: ilk adayi al ama supheli isaretle.
        return [$candidates[0], true];
    }

    /**
     * Sayfadaki aday adlar, guvenilirlik sirasinda.
     *
     * @param  array<int, string>  $suffixes
     * @return array<int, string>
     */
    private function candidates(string $html, array $suffixes): array
    {
        $raw = [];

        if (preg_match('#<h1[^>]*>(.*?)</h1>#is', $html, $m)) {
            $raw[] = strip_tags($m[1]);
        }

        if (preg_match('#<meta property="og:title" content="([^"]*)"#i', $html, $m)) {
            $raw[] = $m[1];
        }

        if (preg_match('#<title>(.*?)</title>#is', $html, $m)) {
            $raw[] = $m[1];
        }

        $names = [];

        foreach ($raw as $value) {
            $name = trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            foreach ($suffixes as $suffix) {
                $name = preg_replace('/\s*'.preg_quote($suffix, '/').'\s*$/u', '', $name);
            }

            $name = trim(preg_replace('/\s+/u', ' ', $name));

            if ($name === '') {
                continue;
            }

            // Sondan birer parca kirpilmis haller de ADAY olur, karari adres
            // verir. Kirpmayi burada kesinlestirmek "Luna Yatak – Pembe"
            // adini "Luna Yatak"a dusurup ayni adli iki urun uretiyordu;
            // hic kirpmamak da "… – Ravizza Yatak" ekini ada katiyordu.
            // Ozgun yazim ilk aday: yeniden birlestirmek ayiraci degistiriyor.
            $names[$name] = true;

            $parts = preg_split('/\s+[–—|-]\s+/u', $name);

            for ($take = count($parts) - 1; $take >= 1; $take--) {
                $candidate = trim(implode(' – ', array_slice($parts, 0, $take)));

                if ($candidate !== '') {
                    $names[$candidate] = true;
                }
            }
        }

        return array_keys($names);
    }
}
