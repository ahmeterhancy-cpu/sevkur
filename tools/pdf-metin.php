<?php

/**
 * PDF metnini sayfa sayfa cikarir — CID fontlari ToUnicode ile cozerek.
 *
 * Katalogdaki yazilar gomulu alt kume fontlariyla yazilmis: akista gorulen
 * baytlar harf degil GLIF numarasi. Her fontun /ToUnicode akisi bu numarayi
 * gercek harfe ceviren tabloyu tasir; onu okumadan cikan metin anlamsiz olur.
 *
 * Kullanim: php pdf-metin2.php <pdf> [sayfa]
 */
$pdfPath = $argv[1] ?? null;
$onlyPage = isset($argv[2]) ? (int) $argv[2] : null;

if (! $pdfPath) {
    exit("kullanim: php pdf-metin2.php <pdf> [sayfa]\n");
}

$pdf = file_get_contents($pdfPath);

preg_match_all('/(\d+)\s+(\d+)\s+obj\b/', $pdf, $m, PREG_OFFSET_CAPTURE);
$objects = [];

foreach ($m[1] as $i => $num) {
    $objects[(int) $num[0]] = $m[0][$i][1];
}

function body(string $pdf, array $objects, int $num): ?string
{
    if (! isset($objects[$num])) {
        return null;
    }

    $start = $objects[$num];
    $end = strpos($pdf, 'endobj', $start);

    return $end === false ? null : substr($pdf, $start, $end - $start);
}

function streamData(string $obj): ?string
{
    $pos = strpos($obj, 'stream');

    if ($pos === false) {
        return null;
    }

    $start = $pos + 6;

    if (substr($obj, $start, 2) === "\r\n") {
        $start += 2;
    } elseif ($obj[$start] === "\n" || $obj[$start] === "\r") {
        $start += 1;
    }

    $end = strrpos($obj, 'endstream');
    $data = $end === false ? substr($obj, $start) : substr($obj, $start, $end - $start);

    if (str_contains(substr($obj, 0, $pos), 'FlateDecode')) {
        $out = @gzuncompress($data) ?: @gzinflate(substr($data, 2));

        return $out === false ? null : $out;
    }

    return $data;
}

/**
 * Bir fontun /ToUnicode tablosu: glif numarasi => harf.
 *
 * @return array<int, string>
 */
function toUnicode(string $pdf, array $objects, int $fontNum): array
{
    $font = body($pdf, $objects, $fontNum);

    if ($font === null || ! preg_match('/\/ToUnicode\s+(\d+)\s+\d+\s+R/', $font, $u)) {
        return [];
    }

    $cmap = streamData(body($pdf, $objects, (int) $u[1]) ?? '');

    if ($cmap === null) {
        return [];
    }

    $map = [];

    // bfchar: <src> <dst>
    if (preg_match_all('/beginbfchar(.*?)endbfchar/s', $cmap, $blocks)) {
        foreach ($blocks[1] as $block) {
            preg_match_all('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>/', $block, $pairs, PREG_SET_ORDER);

            foreach ($pairs as $p) {
                $map[hexdec($p[1])] = hexToUtf8($p[2]);
            }
        }
    }

    // bfrange: <lo> <hi> <dstBase>  ya da  <lo> <hi> [<d1> <d2> …]
    if (preg_match_all('/beginbfrange(.*?)endbfrange/s', $cmap, $blocks)) {
        foreach ($blocks[1] as $block) {
            preg_match_all('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>\s*(<([0-9A-Fa-f]+)>|\[(.*?)\])/s', $block, $ranges, PREG_SET_ORDER);

            foreach ($ranges as $r) {
                $lo = hexdec($r[1]);
                $hi = hexdec($r[2]);

                if (isset($r[4]) && $r[4] !== '') {
                    $base = hexdec($r[4]);

                    for ($c = $lo; $c <= $hi && $c - $lo < 65535; $c++) {
                        $map[$c] = mb_chr($base + ($c - $lo), 'UTF-8');
                    }
                } elseif (isset($r[5])) {
                    preg_match_all('/<([0-9A-Fa-f]+)>/', $r[5], $items);

                    foreach ($items[1] as $i => $hexValue) {
                        $map[$lo + $i] = hexToUtf8($hexValue);
                    }
                }
            }
        }
    }

    return $map;
}

function hexToUtf8(string $hex): string
{
    $out = '';

    for ($i = 0; $i + 4 <= strlen($hex); $i += 4) {
        $out .= mb_chr(hexdec(substr($hex, $i, 4)), 'UTF-8');
    }

    return $out;
}

// --- sayfalar ---
$pages = [];

foreach ($objects as $num => $pos) {
    $b = body($pdf, $objects, $num);

    if ($b !== null && preg_match('/\/Type\s*\/Page\b(?!s)/', $b)) {
        $pages[$pos] = ['num' => $num, 'body' => $b];
    }
}

ksort($pages);
$pages = array_values($pages);

foreach ($pages as $index => $page) {
    $pageNo = $index + 1;

    if ($onlyPage !== null && $pageNo !== $onlyPage) {
        continue;
    }

    $b = $page['body'];

    if (preg_match('/\/Resources\s+(\d+)\s+\d+\s+R/', $b, $r)) {
        $b .= "\n".(body($pdf, $objects, (int) $r[1]) ?? '');
    }

    // Sayfadaki font adlarini nesne numarasiyla esle: /F1 12 0 R
    $fonts = [];

    $fontDict = null;

    if (preg_match('/\/Font\s*<<(.*?)>>/s', $b, $fb)) {
        $fontDict = $fb[1];
    } elseif (preg_match('/\/Font\s+(\d+)\s+\d+\s+R/', $b, $fr)) {
        // Kozan katalogunda font sozlugu DOLAYLI basvuru (/Font 26 0 R);
        // satir ici sozluk bekleyen arama bos donup metni glif numarasina
        // cevirmisti.
        $fontDict = body($pdf, $objects, (int) $fr[1]) ?? '';
    }

    if ($fontDict !== null) {
        preg_match_all('/\/(\w+)\s+(\d+)\s+\d+\s+R/', $fontDict, $fm, PREG_SET_ORDER);

        foreach ($fm as $f) {
            $fonts[$f[1]] = toUnicode($pdf, $objects, (int) $f[2]);
        }
    }

    $content = '';

    if (preg_match('/\/Contents\s+(\d+)\s+\d+\s+R/', $page['body'], $c)) {
        $content = streamData(body($pdf, $objects, (int) $c[1]) ?? '') ?? '';
    } elseif (preg_match('/\/Contents\s*\[(.*?)\]/s', $page['body'], $c)) {
        preg_match_all('/(\d+)\s+\d+\s+R/', $c[1], $refs);

        foreach ($refs[1] as $ref) {
            $content .= (streamData(body($pdf, $objects, (int) $ref) ?? '') ?? '')."\n";
        }
    }

    if ($content === '') {
        continue;
    }

    // Akisi sirayla gez: /Fx Tf font degistirir, <..>/(..) metin basar
    $out = '';
    $current = [];

    preg_match_all('#/(\w+)\s+[\d.]+\s+Tf|<([0-9A-Fa-f\s]+)>|\(((?:\\\\.|[^\\\\()])*)\)#s', $content, $tokens, PREG_SET_ORDER);

    foreach ($tokens as $t) {
        if (isset($t[1]) && $t[1] !== '') {
            $current = $fonts[$t[1]] ?? [];

            continue;
        }

        if (isset($t[2]) && $t[2] !== '') {
            $hex = preg_replace('/\s+/', '', $t[2]);
            $step = (strlen($hex) % 4 === 0) ? 4 : 2;

            for ($i = 0; $i + $step <= strlen($hex); $i += $step) {
                $code = hexdec(substr($hex, $i, $step));
                $out .= $current[$code] ?? '';
            }

            continue;
        }

        if (isset($t[3])) {
            $raw = stripcslashes($t[3]);

            // Type0 fontlarda kod IKI BAYT. Tek bayt okumak Kozan
            // katalogunda metni glif numarasina ceviriyordu ("/ 3 ( =").
            // Genis kod kullanan fontu cmap anahtarlarindan taniyoruz.
            $wide = $current !== [] && max(array_keys($current)) > 255;

            if ($wide) {
                for ($i = 0; $i + 2 <= strlen($raw); $i += 2) {
                    $code = (ord($raw[$i]) << 8) | ord($raw[$i + 1]);
                    $out .= $current[$code] ?? '';
                }

                continue;
            }

            foreach (str_split($raw) as $ch) {
                $out .= $current[ord($ch)] ?? $ch;
            }
        }
    }

    $out = preg_replace('/\s+/u', ' ', trim($out));

    if ($out !== '') {
        echo "=== sayfa $pageNo ===\n$out\n\n";
    }
}
