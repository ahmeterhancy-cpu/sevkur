<?php

/*
 |  Hero slider ve tanitim seritleri icin kompozisyon uretir.
 |
 |  Kullanim:  php tools/build-demo-scenes.php
 |
 |  NEDEN BOYLE: Once Commons'in "oturma odasi" kategorilerinden hazir sahne
 |  fotografi cekmeyi denedim; gelenler amator, kalabalik ve markanin sade
 |  diline zit kareler oldu (turuncu-mavi desenli veranda cekimleri…). Hero
 |  sitenin en marka-tanimlayici alani; oraya boyle bir kare koymak mevcut
 |  soyut panellerden daha kotu duruyordu.
 |
 |  Bunun yerine ZATEN OZENLE SECILMIS urun fotograflari (public/demo/urunler)
 |  markanin krem zeminine yerlestiriliyor: sol taraf yaziya birakiliyor, urun
 |  saga oturtuluyor. Boylece hero'da gercek mobilya goruluyor, palet birebir
 |  tutuyor ve kalite tamamen bizim kontrolumuzde.
 |
 |  Gercek marka gorselleri geldiginde bu dosyalar yonetim panelinden
 |  degistirilir; betige gerek kalmaz.
 */

const SOURCE_DIR = __DIR__.'/../public/demo/urunler';
const TARGET_DIR = __DIR__.'/../public/demo/sahneler';

/**
 * Slot tanimlari:
 *   urun  → public/demo/urunler icindeki tur anahtari
 *   en/boy→ hedef olcu
 *   zemin → marka paletinden arka plan
 *   bolme → fotografin basladigi yatay oran (solu yaziya kalir)
 */
const SCENES = [
    'slider-1' => ['urun' => 'sofa', 'kare' => 1, 'en' => 1600, 'boy' => 760, 'zemin' => 'efe7dc', 'bolme' => 0.52],
    'slider-2' => ['urun' => 'armchair', 'kare' => 0, 'en' => 1600, 'boy' => 760, 'zemin' => 'e9ece8', 'bolme' => 0.52],
    'slider-3' => ['urun' => 'dining_table', 'kare' => 0, 'en' => 1600, 'boy' => 760, 'zemin' => 'f1ece5', 'bolme' => 0.52],

    'banner-koltuk' => ['urun' => 'sofa', 'kare' => 2, 'en' => 900, 'boy' => 680, 'zemin' => 'e8e4dc', 'bolme' => 0.42],
    'banner-masa' => ['urun' => 'dining_table', 'kare' => 1, 'en' => 900, 'boy' => 680, 'zemin' => 'e4e8e6', 'bolme' => 0.42],
    'banner-aydinlatma' => ['urun' => 'lamp', 'kare' => 0, 'en' => 900, 'boy' => 680, 'zemin' => 'efe9df', 'bolme' => 0.42],

    'banner-genis' => ['urun' => 'bed', 'kare' => 0, 'en' => 1600, 'boy' => 620, 'zemin' => 'e6e2da', 'bolme' => 0.50],
    // Blog kapaklari (4:3) — yazilarin konusuna yakin urunler
    'blog-kucuk-oturma-odasini-buyuk-gosteren-7-detay' => ['urun' => 'sofa', 'kare' => 3, 'en' => 1000, 'boy' => 750, 'zemin' => 'ece9e3', 'bolme' => 0.30],
    'blog-masif-ahsap-mobilya-bakimi' => ['urun' => 'dining_table', 'kare' => 1, 'en' => 1000, 'boy' => 750, 'zemin' => 'ede7de', 'bolme' => 0.30],
    'blog-notr-tonlarla-sicak-bir-ev' => ['urun' => 'cushion', 'kare' => 0, 'en' => 1000, 'boy' => 750, 'zemin' => 'eeeae4', 'bolme' => 0.30],
    'blog-yemek-masasi-secerken' => ['urun' => 'chair', 'kare' => 1, 'en' => 1000, 'boy' => 750, 'zemin' => 'e9ece8', 'bolme' => 0.30],
    'blog-kadife-mi-keten-mi' => ['urun' => 'armchair', 'kare' => 2, 'en' => 1000, 'boy' => 750, 'zemin' => 'efe7dc', 'bolme' => 0.30],
    'blog-yatak-odasinda-isik-plani' => ['urun' => 'lamp', 'kare' => 1, 'en' => 1000, 'boy' => 750, 'zemin' => 'efe9df', 'bolme' => 0.30],
];

/** @return array{int,int,int} */
function hexToRgb(string $hex): array
{
    return [
        (int) hexdec(substr($hex, 0, 2)),
        (int) hexdec(substr($hex, 2, 2)),
        (int) hexdec(substr($hex, 4, 2)),
    ];
}

/** Urun karesinin cevresindeki bos zemini kirpar. */
function trimBackground(GdImage $image, int $tolerance = 16): GdImage
{
    $w = imagesx($image);
    $h = imagesy($image);

    // Kose pikselini zemin kabul et
    $corner = imagecolorat($image, 1, 1);
    $cr = $corner >> 16 & 255;
    $cg = $corner >> 8 & 255;
    $cb = $corner & 255;

    $isBackground = function (int $x, int $y) use ($image, $cr, $cg, $cb, $tolerance): bool {
        $rgb = imagecolorat($image, $x, $y);

        return abs(($rgb >> 16 & 255) - $cr) <= $tolerance
            && abs(($rgb >> 8 & 255) - $cg) <= $tolerance
            && abs(($rgb & 255) - $cb) <= $tolerance;
    };

    $top = 0;
    $bottom = $h - 1;
    $left = 0;
    $right = $w - 1;
    $step = 3;

    while ($top < $bottom) {
        for ($x = 0; $x < $w; $x += $step) {
            if (! $isBackground($x, $top)) {
                break 2;
            }
        }
        $top += $step;
    }

    while ($bottom > $top) {
        for ($x = 0; $x < $w; $x += $step) {
            if (! $isBackground($x, $bottom)) {
                break 2;
            }
        }
        $bottom -= $step;
    }

    while ($left < $right) {
        for ($y = $top; $y < $bottom; $y += $step) {
            if (! $isBackground($left, $y)) {
                break 2;
            }
        }
        $left += $step;
    }

    while ($right > $left) {
        for ($y = $top; $y < $bottom; $y += $step) {
            if (! $isBackground($right, $y)) {
                break 2;
            }
        }
        $right -= $step;
    }

    $cropW = max(40, $right - $left);
    $cropH = max(40, $bottom - $top);

    // Cok agresif kirpim olduysa orijinali koru
    if ($cropW < $w * 0.25 || $cropH < $h * 0.25) {
        return $image;
    }

    $out = imagecreatetruecolor($cropW, $cropH);
    imagecopy($out, $image, 0, 0, $left, $top, $cropW, $cropH);

    return $out;
}

if (! is_dir(TARGET_DIR)) {
    mkdir(TARGET_DIR, 0755, true);
}

$built = 0;

foreach (SCENES as $slot => $spec) {
    $source = sprintf('%s/%s-%d.jpg', SOURCE_DIR, $spec['urun'], $spec['kare']);

    if (! is_file($source)) {
        // Ilk kare her zaman vardir; istenen kare yoksa ona dus
        $source = sprintf('%s/%s-0.jpg', SOURCE_DIR, $spec['urun']);
    }

    if (! is_file($source)) {
        printf("%-20s kaynak yok (%s)\n", $slot, $spec['urun']);

        continue;
    }

    $product = imagecreatefromjpeg($source);

    [$r, $g, $b] = hexToRgb($spec['zemin']);

    $canvas = imagecreatetruecolor($spec['en'], $spec['boy']);
    imagefill($canvas, 0, 0, imagecolorallocate($canvas, $r, $g, $b));

    // Bolunmus kompozisyon: sol taraf yaziya ayrilmis krem zemin, sag taraf
    // tam boy fotograf. Fotografi tuvalin ustune "kutu" gibi oturtmak, kendi
    // zemini krem ile tutmadigi icin kotu duruyordu; bu yuzden sag serit
    // fotografla TAMAMEN dolduruluyor ve iki taraf yumusak gecisle birlesiyor.
    $panelX = (int) ($spec['en'] * $spec['bolme']);
    $panelW = $spec['en'] - $panelX;

    // Fotografi serit oranina gore kirp (kapla)
    $srcW = imagesx($product);
    $srcH = imagesy($product);
    $panelRatio = $panelW / $spec['boy'];

    if ($srcW / $srcH > $panelRatio) {
        $cropH = $srcH;
        $cropW = (int) round($srcH * $panelRatio);
    } else {
        $cropW = $srcW;
        $cropH = (int) round($srcW / $panelRatio);
    }

    imagecopyresampled(
        $canvas, $product,
        $panelX, 0,
        (int) (($srcW - $cropW) / 2),
        (int) (($srcH - $cropH) / 2),
        $panelW, $spec['boy'],
        $cropW, $cropH
    );

    // Gecis seridi: fotografin sol kenari kreme dogru erisin
    $fade = (int) ($spec['en'] * 0.10);

    for ($i = 0; $i < $fade; $i++) {
        $alpha = (int) round(127 * ($i / max(1, $fade)));
        $shade = imagecolorallocatealpha($canvas, $r, $g, $b, $alpha);
        imageline($canvas, $panelX + $i, 0, $panelX + $i, $spec['boy'], $shade);
    }

    imagejpeg($canvas, TARGET_DIR."/$slot.jpg", 88);

    printf("%-20s %s (%dx%d)\n", $slot, basename($source), $spec['en'], $spec['boy']);
    $built++;
}

printf("\n%d sahne uretildi: %s\n", $built, TARGET_DIR);
