<?php

/**
 * Omairat katalogunu SAYFA BAZLI urun listesine cevirir.
 *
 * Kod bazli eslestirme denendi: sayfa duzenleri degisken oldugu icin
 * fotograf-kod eslesmesi 238 kaydin ancak yarisinda guvenilirdi. Yanlis
 * fotografli iki yuz urun, dogru fotografli elli urunden kotudur — bu yuzden
 * her SAYFA bir urun olarak aliniyor, sayfadaki butun kodlar ve olculer
 * teknik ozelliklere yaziliyor.
 *
 * Cikti: urunler-sayfa.json
 */

$textFile = $argv[1] ?? 'omairat/metin.txt';
$imageDir = $argv[2] ?? 'omairat/sayfalar';

$text = file_get_contents($textFile);

// Katalogdaki bolumler (PDF sayfa araliklari, metin incelenerek bulundu)
$sections = [
    ['ilk' => 3, 'son' => 26, 'ad' => 'Yemek Odası Takımı', 'kategori' => 'yemek-odasi-takimlari', 'anaKod' => '/^2/'],
    ['ilk' => 27, 'son' => 33, 'ad' => 'Tv Sehpası ve Orta Sehpa', 'kategori' => 'tv-uniteleri', 'anaKod' => '/^3/'],
    ['ilk' => 34, 'son' => 36, 'ad' => 'Orta Sehpa', 'kategori' => 'sehpalar', 'anaKod' => '/^4/'],
    ['ilk' => 37, 'son' => 44, 'ad' => 'Zigon Sehpa', 'kategori' => 'sehpalar', 'anaKod' => '/^5/'],
    ['ilk' => 45, 'son' => 53, 'ad' => 'Mutfak Masası', 'kategori' => 'yemek-masalari', 'anaKod' => '/^7/'],
    ['ilk' => 54, 'son' => 54, 'ad' => 'Sandalye Koleksiyonu', 'kategori' => 'sandalyeler', 'anaKod' => '/^8/'],
];

function sectionFor(int $page, array $sections): ?array
{
    foreach ($sections as $s) {
        if ($page >= $s['ilk'] && $page <= $s['son']) {
            return $s;
        }
    }

    return null;
}

/** Sayfadaki en buyuk iki fotograf: mekan cekimleri. */
function pageImages(string $dir, int $page): array
{
    $path = sprintf('%s/sayfa-%02d', rtrim($dir, '/\\'), $page);

    if (! is_dir($path)) {
        return [];
    }

    $files = glob($path.'/*.jpg') ?: [];

    usort($files, function (string $a, string $b) {
        $sa = @getimagesize($a);
        $sb = @getimagesize($b);

        return (($sb[0] ?? 0) * ($sb[1] ?? 0)) <=> (($sa[0] ?? 0) * ($sa[1] ?? 0));
    });

    return array_slice($files, 0, 2);
}

$products = [];

preg_match_all('/=== sayfa (\d+) ===\n(.+?)(?=\n\n|\z)/s', $text, $blocks, PREG_SET_ORDER);

foreach ($blocks as $block) {
    $page = (int) $block[1];
    $body = $block[2];

    if ($page < 3 || $page > 54) {
        continue;
    }

    // "Kod: 2081Ağırlık: 70 Kg - M3:0,593Ölçü: G:200 - Y:78,5 - D:41,5"
    preg_match_all(
        '/Kod:\s*([0-9]{3,5})(\s*Ayna|\s*Sandalye|\s*\([^)]*\))?(?:Ağırlık:\s*([^Ö]+?))?(?:Ölçü:\s*([^K]+?))?(?=Kod:|\*\*|$)/u',
        $body,
        $items,
        PREG_SET_ORDER
    );

    if ($items === []) {
        continue;
    }

    $specs = [];
    $codes = [];

    foreach ($items as $item) {
        $code = trim($item[1].($item[2] ?? ''));
        $codes[] = $item[1];

        $detail = [];

        if (! empty($item[3])) {
            $detail[] = 'Ağırlık: '.trim(preg_replace('/\s+/', ' ', $item[3]));
        }

        if (! empty($item[4])) {
            $detail[] = 'Ölçü: '.trim(preg_replace('/\s+/u', ' ', rtrim($item[4], "0123456789 \t\n")));
        }

        $specs['Kod '.$code] = $detail === [] ? '—' : implode(' · ', $detail);
    }

    // Bolum SAYFA ARALIGINDAN cikarilamaz: katalogda yemek odasi
    // sayfalarinin arasina tv ve orta sehpa sayfalari serpistirilmis.
    // Siniflandirma sayfadaki KOD TIPLERINE gore yapilir:
    // 1/2 govde (vitrin, konsol), 3 tv sehpasi, 4 orta sehpa,
    // 5 zigon, 7 masa, 8 sandalye.
    $prefixes = array_unique(array_map(fn (string $c) => $c[0], $codes));
    $has = fn (string $p): bool => in_array($p, $prefixes, true);

    if ($has('7') && ($has('2') || $has('1'))) {
        $section = ['ad' => 'Yemek Odası Takımı', 'kategori' => 'yemek-odasi-takimlari', 'anaKod' => '/^2/'];
    } elseif ($has('7')) {
        $section = ['ad' => 'Mutfak Masası', 'kategori' => 'yemek-masalari', 'anaKod' => '/^7/'];
    } elseif ($has('3') || ($has('2') && $has('4'))) {
        $section = ['ad' => 'Tv Sehpası ve Orta Sehpa', 'kategori' => 'tv-uniteleri', 'anaKod' => '/^[23]/'];
    } elseif ($has('5')) {
        $section = ['ad' => 'Zigon Sehpa', 'kategori' => 'sehpalar', 'anaKod' => '/^5/'];
    } elseif ($has('4')) {
        $section = ['ad' => 'Orta Sehpa', 'kategori' => 'sehpalar', 'anaKod' => '/^4/'];
    } elseif ($has('8')) {
        $section = ['ad' => 'Sandalye Koleksiyonu', 'kategori' => 'sandalyeler', 'anaKod' => '/^8/'];
    } else {
        continue;
    }

    $main = null;

    foreach ($codes as $code) {
        if (preg_match($section['anaKod'], $code)) {
            $main = $code;
            break;
        }
    }

    $main ??= $codes[0];

    $images = pageImages($imageDir, $page);

    if ($images === []) {
        continue;
    }

    $products[] = [
        'sayfa' => $page,
        'ad' => $section['ad'].' '.$main,
        'kategori' => $section['kategori'],
        'kodlar' => array_values(array_unique($codes)),
        'specs' => $specs,
        'gorseller' => $images,
    ];
}

file_put_contents('omairat/urunler-sayfa.json', json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo 'urun: '.count($products)."\n";
echo 'gorselli: '.count(array_filter($products, fn ($p) => $p['gorseller'] !== []))."\n";

foreach (array_slice($products, 0, 4) as $p) {
    echo sprintf("  %-34s %-24s %d kod, %d gorsel\n", $p['ad'], $p['kategori'], count($p['kodlar']), count($p['gorseller']));
}
