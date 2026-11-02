<?php

/**
 * Asse 2026 katalogunu MODEL bazli urun listesine cevirir.
 *
 * Katalog 45 PDF sayfasi, her sayfada iki basili sayfa var. Ikinci PDF
 * sayfasi DIZIN: model adlari ve basili sayfa araliklari orada.
 *
 *   indexOdessaLuganoTetra…Beyza  02-05 06-09 10-15 … 84-85
 *
 * Basili sayfa p, PDF sayfasi floor(p/2)+2 icinde. Aritmetik TEK BASINA
 * yeterli sayilmiyor: her model, kendi sayfalarinin metninde adi gecerek
 * dogrulaniyor. Oben katalogunda sira tahminine guvenmek dort koleksiyonda
 * yanlis fotografa yol acmisti.
 *
 * Olcu, kumas kodu ve duzen (3+3+1+1) bilgisi sayfa metninden okunup teknik
 * ozelliklere yaziliyor.
 *
 * Kullanim: php asse-veri.php <metin.txt> <sayfalar-dizini> <cikti.json>
 */
$textFile = $argv[1] ?? null;
$imageDir = $argv[2] ?? null;
$outFile = $argv[3] ?? null;

if (! $textFile || ! $imageDir || ! $outFile) {
    exit("kullanim: php asse-veri.php <metin.txt> <sayfalar-dizini> <cikti.json>\n");
}

$text = file_get_contents($textFile);

/** Sayfa numarasi => sayfa metni */
$pages = [];
preg_match_all('/=== sayfa (\d+) ===\n(.*?)(?=\n\n=== sayfa |\z)/s', $text, $blocks, PREG_SET_ORDER);

foreach ($blocks as $b) {
    $pages[(int) $b[1]] = trim($b[2]);
}

if (! isset($pages[2])) {
    exit("dizin sayfasi (2) bulunamadi\n");
}

// --- Dizin: model adlari ve basili sayfa araliklari ---
$index = $pages[2];

// Adlar "index" ile ilk aralik arasinda, bitisik yazilmis.
if (! preg_match('/index(.*?)(\d{2}-\d{2})/u', $index, $m)) {
    exit("dizin cozumlenemedi\n");
}

$namesBlob = $m[1];

// Her model buyuk harfle basliyor: "OdessaLuganoTetra" -> ayri ayri.
preg_match_all('/\p{Lu}[\p{Ll}\p{M}]+/u', $namesBlob, $nm);
$models = $nm[0];

preg_match_all('/(\d{2})-(\d{2})/', $index, $rm, PREG_SET_ORDER);
$ranges = $rm;

if (count($models) !== count($ranges)) {
    fwrite(STDERR, sprintf(
        "UYARI: %d model adina karsi %d sayfa araligi — eslestirme guvenilmez\n",
        count($models),
        count($ranges)
    ));
}

/** Basili sayfa -> PDF sayfasi */
$pdfPage = fn (int $printed): int => (int) floor($printed / 2) + 2;

/**
 * Karsilastirma icin harf katlama.
 *
 * mb_strtolower Turkce noktali I'yi cozmuyor: "MİNA" -> "mi̇na" (i + birlesen
 * nokta), model adi "Mina" -> "mina". Uc urun bu yuzden dogrulamayi
 * gecemedi. Turkce harfler ASCII karsiliklarina indirilip karsilastiriliyor.
 */
function fold(string $value): string
{
    $map = [
        'İ' => 'i', 'I' => 'i', 'ı' => 'i', 'i' => 'i',
        'Ş' => 's', 'ş' => 's', 'Ğ' => 'g', 'ğ' => 'g',
        'Ü' => 'u', 'ü' => 'u', 'Ö' => 'o', 'ö' => 'o',
        'Ç' => 'c', 'ç' => 'c',
    ];

    return mb_strtolower(strtr($value, $map), 'UTF-8');
}

/** Sayfadaki en buyuk fotograflar. */
function pageImages(string $dir, int $page, int $limit): array
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

    return array_slice($files, 0, $limit);
}

$products = [];
$warnings = [];

foreach ($models as $i => $model) {
    if (! isset($ranges[$i])) {
        continue;
    }

    $from = (int) $ranges[$i][1];
    $to = (int) $ranges[$i][2];

    $pdfFrom = $pdfPage($from);
    $pdfTo = $pdfPage($to);

    // Modelin kendi sayfalarinda adi geciyor mu — aritmetigin dogrulamasi.
    $own = [];
    $blob = '';

    for ($p = $pdfFrom; $p <= $pdfTo; $p++) {
        if (! isset($pages[$p])) {
            continue;
        }

        $own[] = $p;
        $blob .= ' '.$pages[$p];
    }

    $needle = fold($model);

    if (! str_contains(fold($blob), $needle)) {
        $warnings[] = sprintf('%s: %d-%d sayfalarinda adi gecmiyor', $model, $pdfFrom, $pdfTo);

        continue;
    }

    // Tur: sayfa metni "Köşe" diyorsa kose takimi.
    $corner = (bool) preg_match('/kö[sş]e/iu', $blob);
    $type = $corner ? 'Köşe Takımı' : 'Koltuk Takımı';

    // Olculer
    preg_match_all('/(\d{2,3})\s*cm/u', $blob, $dm);
    $sizes = array_values(array_unique($dm[1]));

    // Kumas kartelalari: "BELLA 01", "OSCAR 200", "İNFİNİTİ 796-01"
    preg_match_all('/\b([A-ZÇĞİÖŞÜ]{3,}(?:\s+[A-ZÇĞİÖŞÜ]{2,})?)\s+(\d{2,4}(?:-\d{2})?)\b/u', $blob, $fm, PREG_SET_ORDER);
    $fabrics = [];

    foreach ($fm as $f) {
        $label = trim($f[1]).' '.$f[2];

        // Model adinin kendisi kumas kodu degil.
        if (fold(trim($f[1])) === $needle) {
            continue;
        }

        $fabrics[$label] = true;
    }

    // Duzen: 3+3+1+1 / 3+1+1 / 3'lu
    preg_match_all("/\d(?:\+\d)+|\d’l[uü]|\d'l[uü]/u", $blob, $cm);
    $layouts = array_values(array_unique($cm[0]));

    $images = [];

    foreach ($own as $p) {
        foreach (pageImages($imageDir, $p, 2) as $file) {
            $images[] = $file;
        }
    }

    if ($images === []) {
        $warnings[] = $model.': fotograf bulunamadi';

        continue;
    }

    $specs = [];

    if ($layouts !== []) {
        $specs['Düzen'] = implode(', ', $layouts);
    }

    if ($sizes !== []) {
        $specs['Ölçüler'] = implode(' cm · ', array_slice($sizes, 0, 8)).' cm';
    }

    if ($fabrics !== []) {
        $specs['Kumaş seçenekleri'] = implode(', ', array_slice(array_keys($fabrics), 0, 8));
    }

    $products[] = [
        'ad' => mb_convert_case($model, MB_CASE_TITLE, 'UTF-8').' '.$type,
        'kategori' => $corner ? 'kose-takimlari' : 'koltuk-takimlari',
        'basili_sayfa' => $from.'-'.$to,
        'pdf_sayfa' => $own,
        'specs' => $specs,
        'gorseller' => array_slice($images, 0, 4),
    ];
}

file_put_contents($outFile, json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo 'model: '.count($models).'  urun: '.count($products)."\n";

foreach ($products as $p) {
    echo sprintf(
        "  %-26s %-16s s.%-7s %d gorsel  %s\n",
        $p['ad'],
        $p['kategori'],
        $p['basili_sayfa'],
        count($p['gorseller']),
        $p['specs']['Düzen'] ?? ''
    );
}

foreach ($warnings as $w) {
    fwrite(STDERR, 'UYARI '.$w."\n");
}
