<?php

namespace App\Services\Import;

/**
 * Urun adresinin yolundan kategori slug'i.
 *
 * Bazi tedarikcilerin urun adresleri kategoriyi zaten tasir:
 *   /urunler/arno-home/yatak-odalari/komodinler/hisar-komodin/
 * Boyle sitelerde kategori sayfasi gezmeye gerek yok. Ayni hesap hem ilk
 * aktarimda hem de sonradan "kategori:yeniden-esle" ile calistirildigi icin
 * tek yerde duruyor; ikisi ayri yerde dursa biri guncellenip digeri unutulur.
 */
class CategoryFromPath
{
    /** Yol tabanli esleme tanimli degilse null doner. */
    public function __invoke(string $url, array $site): ?string
    {
        if (! isset($site['yoldan_kategori'])) {
            return null;
        }

        $parts = array_values(array_filter(explode('/', parse_url($url, PHP_URL_PATH) ?? '')));

        // Adresin sonuna dogru olan parca daha ozel: "genc-komodinler"
        // varsa "arno-go" degil o kullanilir.
        foreach (array_reverse($parts) as $part) {
            if (isset($site['yoldan_kategori'][$part])) {
                return $site['yoldan_kategori'][$part];
            }
        }

        return $site['varsayilan'];
    }
}
