<?php

/*
 |  Renk doygunlugu olcumu.
 |
 |  Commons'ta mobilya kategorilerinde bol miktarda SIYAH-BEYAZ arsiv fotografi
 |  ve gravur var. Basliklarindan anlasilmiyorlar ("043b Theresianism Style -
 |  Interiors"), ama vitrinde apacik yanlis duruyorlar. Bu yuzden gorselin
 |  kendisine bakip renksiz olanlari eliyoruz.
 */

if (! function_exists('imageSaturation')) {
    /** 0 (gri) — 1 (canli). */
    function imageSaturation(GdImage $image): float
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $step = (int) max(2, min($w, $h) / 60);

        $total = 0.0;
        $count = 0;

        for ($y = 0; $y < $h; $y += $step) {
            for ($x = 0; $x < $w; $x += $step) {
                $rgb = imagecolorat($image, $x, $y);
                $r = $rgb >> 16 & 255;
                $g = $rgb >> 8 & 255;
                $b = $rgb & 255;

                $max = max($r, $g, $b);
                $min = min($r, $g, $b);

                // Cok koyu/cok acik pikseller doygunlugu yaniltir, atla
                if ($max < 24 || $max > 246) {
                    continue;
                }

                $total += ($max - $min) / $max;
                $count++;
            }
        }

        return $count === 0 ? 0.0 : $total / $count;
    }
}

if (! function_exists('imageHueSpread')) {
    /**
     * Renk tonlarinin dagilimi (derece). Sepya/duoton arsiv fotograflarinda
     * tum pikseller ayni ton bandindadir; gercek bir urun fotografinda ahsap,
     * kumas ve zemin farkli tonlara duser.
     */
    function imageHueSpread(GdImage $image): float
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $step = (int) max(2, min($w, $h) / 60);

        $sin = 0.0;
        $cos = 0.0;
        $count = 0;

        for ($y = 0; $y < $h; $y += $step) {
            for ($x = 0; $x < $w; $x += $step) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16 & 255) / 255;
                $g = ($rgb >> 8 & 255) / 255;
                $b = ($rgb & 255) / 255;

                $max = max($r, $g, $b);
                $min = min($r, $g, $b);
                $delta = $max - $min;

                if ($delta < 0.04 || $max < 0.1) {
                    continue;
                }

                $hue = match (true) {
                    $max === $r => 60 * fmod(($g - $b) / $delta, 6),
                    $max === $g => 60 * (($b - $r) / $delta + 2),
                    default => 60 * (($r - $g) / $delta + 4),
                };

                $rad = deg2rad($hue);
                $sin += sin($rad);
                $cos += cos($rad);
                $count++;
            }
        }

        if ($count === 0) {
            return 0.0;
        }

        // Dairesel dagilim: 1'e yakin R = tek ton, 0'a yakin = genis dagilim
        $R = sqrt(($sin / $count) ** 2 + ($cos / $count) ** 2);

        return rad2deg(sqrt(max(0, -2 * log(max(1e-6, $R)))));
    }
}

if (! function_exists('isColourPhoto')) {
    /**
     * Renkli bir urun fotografi mi?
     *
     * Yalnizca TAM GRI kareler elenir (gravur, siyah-beyaz arsiv fotografi).
     * Ton dagilimina bakmayi da denedim ama ahsap mobilya dogasi geregi tek
     * ton bandinda; gecerli fotograflarin ucte biri yanlislikla eleniyordu.
     */
    function isColourPhoto(GdImage $image, float $threshold = 0.03): bool
    {
        return imageSaturation($image) >= $threshold;
    }
}
