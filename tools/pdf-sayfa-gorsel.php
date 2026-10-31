<?php

/**
 * PDF gorsellerini SAYFA SAYFA cikarir.
 *
 * Onceki betik tum dosyayi sirayla tariyordu; hangi fotografin hangi urune
 * ait oldugunu bilmek icin sayfa baglantisi sart. Burada her sayfa nesnesinin
 * /Resources /XObject sozlugu okunup, referans verdigi JPEG akislari o
 * sayfanin klasorune yaziliyor.
 *
 * Kullanim: php pdf-sayfa-gorsel.php <pdf> <hedef-dizin>
 */

$pdfPath = $argv[1] ?? null;
$outDir = $argv[2] ?? null;

if (! $pdfPath || ! $outDir) {
    exit("kullanim: php pdf-sayfa-gorsel.php <pdf> <hedef-dizin>\n");
}

$pdf = file_get_contents($pdfPath);

// --- 1) Butun nesnelerin dosyadaki yerini cikar: "12 0 obj" ---
preg_match_all('/(\d+)\s+(\d+)\s+obj\b/', $pdf, $objMatches, PREG_OFFSET_CAPTURE);

$objects = [];

foreach ($objMatches[1] as $i => $m) {
    $objects[(int) $m[0]] = $objMatches[0][$i][1];
}

echo 'nesne: '.count($objects)."\n";

/** Bir nesnenin ham govdesini doner. */
function objectBody(string $pdf, array $objects, int $num): ?string
{
    if (! isset($objects[$num])) {
        return null;
    }

    $start = $objects[$num];
    $end = strpos($pdf, 'endobj', $start);

    return $end === false ? null : substr($pdf, $start, $end - $start);
}

// --- 2) Sayfa nesnelerini dosya sirasina gore topla ---
$pages = [];

foreach ($objects as $num => $pos) {
    $body = objectBody($pdf, $objects, $num);

    if ($body === null || ! preg_match('/\/Type\s*\/Page\b(?!s)/', $body)) {
        continue;
    }

    $pages[$pos] = ['num' => $num, 'body' => $body];
}

ksort($pages);
$pages = array_values($pages);

echo 'sayfa: '.count($pages)."\n";

// --- 3) Her sayfanin XObject referanslarini cikar ve JPEG'leri yaz ---
$total = 0;

foreach ($pages as $index => $page) {
    $pageNo = $index + 1;
    $body = $page['body'];

    // /Resources dogrudan sayfada ya da ayri bir nesnede olabilir
    if (preg_match('/\/Resources\s+(\d+)\s+\d+\s+R/', $body, $m)) {
        $body .= "\n".(objectBody($pdf, $objects, (int) $m[1]) ?? '');
    }

    if (! preg_match_all('/\/(?:Im|Xi|Fm|X)\w*\s+(\d+)\s+\d+\s+R/', $body, $refs)) {
        continue;
    }

    $pageDir = sprintf('%s/sayfa-%02d', $outDir, $pageNo);
    $written = 0;

    foreach (array_unique($refs[1]) as $refNum) {
        $obj = objectBody($pdf, $objects, (int) $refNum);

        if ($obj === null || ! str_contains($obj, 'DCTDecode')) {
            continue;
        }

        $sPos = strpos($obj, 'stream');

        if ($sPos === false) {
            continue;
        }

        $dataStart = $sPos + 6;

        if (substr($obj, $dataStart, 2) === "\r\n") {
            $dataStart += 2;
        } elseif ($obj[$dataStart] === "\n" || $obj[$dataStart] === "\r") {
            $dataStart += 1;
        }

        $data = substr($obj, $dataStart);

        // endstream'e kadar
        $eEnd = strrpos($data, 'endstream');

        if ($eEnd !== false) {
            $data = substr($data, 0, $eEnd);
        }

        if (substr($data, 0, 2) !== "\xFF\xD8") {
            continue;
        }

        if (! is_dir($pageDir)) {
            mkdir($pageDir, 0755, true);
        }

        $file = sprintf('%s/%s.jpg', $pageDir, $refNum);
        file_put_contents($file, $data);

        $size = @getimagesize($file);

        // Dekoratif kucuk parcalari ele
        if (! $size || $size[0] < 600 || $size[1] < 450) {
            unlink($file);

            continue;
        }

        $written++;
        $total++;
    }

    if ($written > 0) {
        echo sprintf("sayfa %02d: %d gorsel\n", $pageNo, $written);
    }
}

echo "toplam: $total\n";
