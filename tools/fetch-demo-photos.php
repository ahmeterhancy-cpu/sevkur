<?php

/*
 |  Demo urun fotograflarini Wikimedia Commons'tan indirir.
 |
 |  Kullanim:
 |      php tools/fetch-demo-photos.php            tum turler (eksikleri tamamlar)
 |      php tools/fetch-demo-photos.php sofa       tek tur
 |      php tools/fetch-demo-photos.php --zorla    dolu turleri de yeniler
 |
 |  ONEMLI: Bu fotograflar DEMO icindir. Magaza canliya cikarken kendi urun
 |  fotograflariniz ile degistirilmelidir — burada gorulen mobilyalar satilan
 |  urunler DEGILDIR.
 |
 |  Uc suzgecten geciyor:
 |    1. LISANS — kamu mali / CC0 / CC BY / CC BY-SA kabul; NC ve ND RED
 |                (magaza ticari, NC lisansli gorsel kullanilamaz).
 |    2. KONU   — once kuratorlu Commons kategorileri (ve alt kategorileri);
 |                metin aramasina dusuldugunde baslikta zorunlu/yasak kelime
 |                denetimi yapilir ("table" aramasi TUZLUK getirmisti).
 |    3. ZEMIN  — her aday kucuk kopya halinde indirilip KENAR SERIDI olculur;
 |                zemin acik ve tekduze degilse elenir. Commons'ta mobilya
 |                fotograflarinin cogu amator oda cekimi, vitrine uymuyor.
 */

require __DIR__.'/demo-photo-match.php';
require __DIR__.'/demo-photo-colour.php';

$SUBJECT_RULES = require __DIR__.'/demo-photo-rules.php';

const ENDPOINT = 'https://commons.wikimedia.org/w/api.php';
const USER_AGENT = 'SevkurMobilyaDemoSeeder/1.0 (https://sevkurmobilya.com; demo icerigi)';
const TARGET_DIR = __DIR__.'/../public/demo/urunler';

const PER_TYPE = 4;         // tur basina hedef gorsel
const CANDIDATES = 26;      // tur basina indirilip olculecek azami aday
const SCREEN_WIDTH = 420;   // eleme icin kucuk kopya
const THUMB_WIDTH = 1100;   // kabul edilenler icin

/**
 * Her tur icin once kategoriler (konu kuratorlu), yetmezse metin aramasi.
 * Kategorilerin dosyalari cogu zaman ALT kategorilerde durur; bir seviye
 * asagi da iniliyor.
 */
const SUBJECTS = [
    'sofa' => [
        'kategoriler' => ['Category:Sofas', 'Category:Couches', 'Category:Settees'],
        'aramalar' => ['sofa white background', 'couch isolated white background'],
    ],
    'armchair' => [
        'kategoriler' => ['Category:Armchairs', 'Category:Lounge chairs'],
        'aramalar' => ['armchair white background', 'armchair isolated'],
    ],
    'chair' => [
        'kategoriler' => ['Category:Chairs by type', 'Category:Dining chairs', 'Category:Wooden chairs'],
        'aramalar' => ['chair white background', 'dining chair isolated'],
    ],
    'coffee_table' => [
        'kategoriler' => ['Category:Coffee tables', 'Category:Side tables'],
        'aramalar' => ['coffee table white background', 'side table white background'],
    ],
    'dining_table' => [
        'kategoriler' => ['Category:Dining tables', 'Category:Tables (furniture) by type'],
        'aramalar' => ['dining table white background', 'Tisch freigestellt'],
    ],
    'tv_unit' => [
        'kategoriler' => ['Category:Sideboards', 'Category:Credenzas', 'Category:Cabinets (furniture) by type'],
        'aramalar' => ['sideboard white background', 'credenza white background'],
    ],
    'bed' => [
        'kategoriler' => ['Category:Beds by type', 'Category:Bed frames', 'Category:Wooden beds'],
        'aramalar' => ['bed white background', 'bed frame isolated'],
    ],
    'wardrobe' => [
        'kategoriler' => ['Category:Wardrobes', 'Category:Armoires'],
        'aramalar' => ['wardrobe white background', 'Kleiderschrank freigestellt'],
    ],
    'nightstand' => [
        'kategoriler' => ['Category:Nightstands', 'Category:Bedside tables'],
        'aramalar' => ['nightstand white background', 'bedside table isolated'],
    ],
    'dresser' => [
        'kategoriler' => ['Category:Chests of drawers', 'Category:Commodes (furniture)', 'Category:Dressers (furniture)', 'Category:Chests of drawers by country'],
        'aramalar' => ['chest of drawers white background', 'commode furniture white background', 'Kommode freigestellt', 'dresser furniture isolated'],
    ],
    'console' => [
        'kategoriler' => ['Category:Console tables', 'Category:Hall tables'],
        'aramalar' => ['console table white background', 'hall table isolated'],
    ],
    'display_cabinet' => [
        'kategoriler' => ['Category:Display cases', 'Category:Vitrines', 'Category:China cabinets'],
        'aramalar' => ['display cabinet white background', 'vitrine isolated'],
    ],
    'desk' => [
        'kategoriler' => ['Category:Desks by type', 'Category:Writing desks', 'Category:Wooden desks'],
        'aramalar' => ['desk white background', 'writing desk isolated'],
    ],
    'office_chair' => [
        'kategoriler' => ['Category:Office chairs', 'Category:Swivel chairs'],
        'aramalar' => ['office chair white background', 'swivel chair isolated'],
    ],
    'bookcase' => [
        'kategoriler' => ['Category:Bookcases', 'Category:Shelving'],
        'aramalar' => ['bookcase white background', 'shelving unit white background'],
    ],
    'lamp' => [
        'kategoriler' => ['Category:Floor lamps', 'Category:Table lamps'],
        'aramalar' => ['floor lamp white background', 'table lamp isolated'],
    ],
    'chandelier' => [
        'kategoriler' => ['Category:Chandeliers', 'Category:Pendant lamps', 'Category:Ceiling lamps', 'Category:Chandeliers by country'],
        'aramalar' => ['chandelier white background', 'Kronleuchter freigestellt', 'ceiling lamp white background', 'pendant light isolated'],
    ],
    'rug' => [
        'kategoriler' => ['Category:Rugs', 'Category:Carpets by type'],
        'aramalar' => ['rug white background', 'carpet isolated'],
    ],
    'cushion' => [
        'kategoriler' => ['Category:Cushions', 'Category:Pillows'],
        'aramalar' => ['cushion white background', 'pillow isolated'],
    ],
    'curtain' => [
        'kategoriler' => ['Category:Curtains', 'Category:Draperies'],
        'aramalar' => ['curtain white background', 'drapery isolated'],
    ],
    'mirror' => [
        'kategoriler' => ['Category:Wall mirrors', 'Category:Mirrors by type'],
        'aramalar' => ['mirror white background', 'wall mirror isolated'],
    ],
    'artwork' => [
        'kategoriler' => ['Category:Picture frames', 'Category:Empty picture frames'],
        'aramalar' => ['picture frame white background', 'empty frame isolated'],
    ],
    'shelf' => [
        'kategoriler' => ['Category:Shelves', 'Category:Wall shelves'],
        'aramalar' => ['shelf white background', 'wall shelf isolated'],
    ],
];

// ------------------------------------------------------------------ yardimcilar

function httpGet(string $url): ?string
{
    $context = stream_context_create([
        'http' => [
            'header' => 'User-Agent: '.USER_AGENT."\r\nAccept: */*\r\n",
            'timeout' => 45,
            'follow_location' => 1,
        ],
        // Yerel PHP'de CA paketi yok; Commons genel erisime acik.
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);

    $body = @file_get_contents($url, false, $context);

    return $body === false ? null : $body;
}

/** @return array<int, array<string, mixed>> */
function apiQuery(array $params): array
{
    $raw = httpGet(ENDPOINT.'?'.http_build_query($params + [
        'action' => 'query',
        'format' => 'json',
        'prop' => 'imageinfo',
        'iiprop' => 'url|size|mime|extmetadata',
        'iiurlwidth' => SCREEN_WIDTH,
    ]));

    if ($raw === null) {
        return [];
    }

    return array_values(json_decode($raw, true)['query']['pages'] ?? []);
}

/** Kategorinin dogrudan dosyalari. */
function categoryFiles(string $category, int $limit = 60): array
{
    return apiQuery([
        'generator' => 'categorymembers',
        'gcmtitle' => $category,
        'gcmtype' => 'file',
        'gcmlimit' => $limit,
    ]);
}

/** @return array<int, string> Kategorinin alt kategorileri. */
function subCategories(string $category, int $limit = 12): array
{
    $raw = httpGet(ENDPOINT.'?'.http_build_query([
        'action' => 'query',
        'format' => 'json',
        'list' => 'categorymembers',
        'cmtitle' => $category,
        'cmtype' => 'subcat',
        'cmlimit' => $limit,
    ]));

    if ($raw === null) {
        return [];
    }

    return array_column(json_decode($raw, true)['query']['categorymembers'] ?? [], 'title');
}

/** Metin aramasi (son care). */
function searchFiles(string $term, int $limit = 30): array
{
    return apiQuery([
        'generator' => 'search',
        'gsrsearch' => $term.' filetype:bitmap',
        'gsrnamespace' => 6,
        'gsrlimit' => $limit,
    ]);
}

function meta(array $page, string $key): string
{
    return trim(strip_tags($page['imageinfo'][0]['extmetadata'][$key]['value'] ?? ''));
}

/** Ticari kullanima kapali lisanslar elenir. */
function licenceAllowed(string $short, string $name): bool
{
    $haystack = mb_strtolower($short.' '.$name);

    foreach (['non-commercial', 'noncommercial', '-nc', 'nc-', 'no derivative', 'noderiv', '-nd', 'fair use', 'non-free'] as $blocked) {
        if (str_contains($haystack, $blocked)) {
            return false;
        }
    }

    foreach (['cc0', 'public domain', 'cc by', 'cc-by', 'attribution'] as $ok) {
        if (str_contains($haystack, $ok)) {
            return true;
        }
    }

    return false;
}

/**
 * Kenar seridini olcup zeminin ne kadar sade oldugunu puanlar.
 *
 * @return array{parlaklik: float, sapma: float, uygun: bool}
 */
function backgroundScore(GdImage $image): array
{
    $w = imagesx($image);
    $h = imagesy($image);
    $band = (int) max(4, min($w, $h) * 0.07);
    $step = (int) max(2, min($w, $h) / 90);

    $values = [];

    $sample = function (int $x, int $y) use ($image, $w, $h, &$values): void {
        $rgb = imagecolorat($image, max(0, min($w - 1, $x)), max(0, min($h - 1, $y)));
        $values[] = (($rgb >> 16 & 255) * 0.299) + (($rgb >> 8 & 255) * 0.587) + (($rgb & 255) * 0.114);
    };

    for ($x = 0; $x < $w; $x += $step) {
        for ($d = 0; $d < $band; $d += $step) {
            $sample($x, $d);
            $sample($x, $h - 1 - $d);
        }
    }

    for ($y = 0; $y < $h; $y += $step) {
        for ($d = 0; $d < $band; $d += $step) {
            $sample($d, $y);
            $sample($w - 1 - $d, $y);
        }
    }

    $count = count($values) ?: 1;
    $mean = array_sum($values) / $count;

    $variance = 0.0;
    foreach ($values as $value) {
        $variance += ($value - $mean) ** 2;
    }

    $deviation = sqrt($variance / $count);

    return [
        'parlaklik' => $mean,
        'sapma' => $deviation,
        'uygun' => $mean >= 195 && $deviation <= 26,
    ];
}

/** Kare kirpar, 900x900 JPEG yazar. */
function writeSquare(GdImage $image, string $path): void
{
    $side = min(imagesx($image), imagesy($image));
    $canvas = imagecreatetruecolor(900, 900);

    imagecopyresampled(
        $canvas, $image,
        0, 0,
        (int) ((imagesx($image) - $side) / 2),
        (int) ((imagesy($image) - $side) / 2),
        900, 900, $side, $side
    );

    imagejpeg($canvas, $path, 82);
}

// ------------------------------------------------------------------------ ana

if (! is_dir(TARGET_DIR)) {
    mkdir(TARGET_DIR, 0755, true);
}

$args = array_slice($argv, 1);
$force = in_array('--zorla', $args, true);
$only = null;

foreach ($args as $arg) {
    if (! str_starts_with($arg, '--')) {
        $only = $arg;
    }
}

$creditsFile = TARGET_DIR.'/KAYNAKLAR.json';
$credits = is_file($creditsFile) ? (json_decode(file_get_contents($creditsFile), true) ?: []) : [];

foreach (SUBJECTS as $key => $source) {
    if ($only !== null && $only !== $key) {
        continue;
    }

    $existing = count(glob(TARGET_DIR."/$key-*.jpg"));

    if (! $force && $existing >= PER_TYPE) {
        printf("%-16s atlandi (%d mevcut)\n", $key, $existing);

        continue;
    }

    printf('%-16s ', $key);

    // --- 1. Adaylari topla: kategori > alt kategori > arama ------------------
    $pages = [];

    foreach ($source['kategoriler'] as $category) {
        foreach (categoryFiles($category) as $page) {
            $pages[$page['title'] ?? uniqid()] = ['page' => $page, 'kuratorlu' => true];
        }
    }

    if (count($pages) < CANDIDATES) {
        foreach ($source['kategoriler'] as $category) {
            foreach (subCategories($category) as $sub) {
                foreach (categoryFiles($sub, 20) as $page) {
                    $pages[$page['title'] ?? uniqid()] = ['page' => $page, 'kuratorlu' => true];
                }

                if (count($pages) >= CANDIDATES * 2) {
                    break 2;
                }
            }
        }
    }

    if (count($pages) < CANDIDATES) {
        foreach ($source['aramalar'] as $term) {
            foreach (searchFiles($term) as $page) {
                $pages[$page['title'] ?? uniqid()] = ['page' => $page, 'kuratorlu' => false];
            }

            if (count($pages) >= CANDIDATES * 2) {
                break;
            }
        }
    }

    // --- 2. Ele ve puanla ---------------------------------------------------
    $rule = $SUBJECT_RULES[$key] ?? ['gerekli' => [], 'yasak' => []];
    $scored = [];
    $inspected = 0;

    foreach ($pages as $candidate) {
        if ($inspected >= CANDIDATES) {
            break;
        }

        $page = $candidate['page'];
        $info = $page['imageinfo'][0] ?? null;

        if (! $info || ! str_starts_with($info['mime'] ?? '', 'image/')) {
            continue;
        }

        $w = (int) ($info['width'] ?? 0);
        $h = (int) ($info['height'] ?? 0);

        if ($w < 700 || $h < 500 || $w / max(1, $h) > 2.4 || $w / max(1, $h) < 0.6) {
            continue;
        }

        if (! licenceAllowed(meta($page, 'LicenseShortName'), meta($page, 'UsageTerms'))) {
            continue;
        }

        // Kategoriden geldiyse konu zaten dogru; yalniz yanlis pozitifleri ele.
        // Aramadan geldiyse zorunlu kelime de aranir.
        $subject = ($page['title'] ?? '').' '.meta($page, 'ObjectName');
        // Kural HER adaya uygulanir. Kategori uyeligi konuyu garantilemedi:
        // "Category:Side tables" alt kategorilerinden bir KEDI fotografi,
        // "Picture frames" altindan dugun fotograflari gelmisti.
        $applied = $rule;

        if (! subjectAllowed($subject, $applied)['uygun']) {
            continue;
        }

        $bytes = isset($info['thumburl']) ? httpGet($info['thumburl']) : null;

        if ($bytes === null || strlen($bytes) < 12000) {
            continue;
        }

        $image = @imagecreatefromstring($bytes);

        if (! $image) {
            continue;
        }

        // Siyah-beyaz arsiv fotograflari ve gravurler vitrine uymuyor
        if (! isColourPhoto($image)) {
            continue;
        }

        $inspected++;

        $scored[] = [
            'page' => $page,
            'info' => $info,
            'score' => backgroundScore($image),
            'author' => mb_strtolower(mb_substr(meta($page, 'Artist'), 0, 40)),
        ];
    }

    // En temiz zeminden en kirliye
    usort($scored, fn (array $a, array $b) => ($a['score']['sapma'] <=> $b['score']['sapma'])
        ?: ($b['score']['parlaklik'] <=> $a['score']['parlaklik']));

    // Iki gecis: once siki esik, yetmezse kabul edilebilir olanlar
    $accepted = [];
    $seenAuthors = [];
    $strict = 0;

    // Uc kademe: siki (studyo), kabul edilebilir, ve son care "karanlik
    // dagink degil". Commons'ta hali/perde/ayna gibi turlerde temiz zeminli
    // cekim neredeyse yok; son kademe olmadan bu turler bos kaliyor.
    foreach (['siki', 'orta', 'son'] as $tier) {
        foreach ($scored as $item) {
            if (count($accepted) >= PER_TYPE) {
                break 2;
            }

            if (in_array($item, $accepted, true)) {
                continue;
            }

            $passes = match ($tier) {
                'siki' => $item['score']['uygun'],
                'orta' => $item['score']['parlaklik'] >= 168 && $item['score']['sapma'] <= 46,
                default => $item['score']['parlaklik'] >= 118 && $item['score']['sapma'] <= 72,
            };

            if (! $passes) {
                continue;
            }

            // Ayni fotografcinin ard arda cekimlerinden en fazla ikisi
            $seenAuthors[$item['author']] = ($seenAuthors[$item['author']] ?? 0) + 1;

            if ($seenAuthors[$item['author']] > 2) {
                continue;
            }

            $accepted[] = $item;
            $strict += $tier === 'siki' ? 1 : 0;
        }
    }

    // Son guvence: hicbir kademe tutmadiysa en temiz iki adayi yine de al.
    // Avize gibi turlerde fotograflar tavana bakiyor, zemin hicbir zaman acik
    // olmuyor; tur bos kalmasin diye eldeki en iyisi kullanilir.
    if ($accepted === [] && $scored !== []) {
        $accepted = array_slice($scored, 0, 2);
    }

    // --- 3. Yaz -------------------------------------------------------------
    // Yeni gorsel yoksa eldekine DOKUNMA: onceki surumde once silip sonra
    // ariyorduk, bos donen turlerde iyi gorseller kayboluyordu.
    if ($accepted === []) {
        printf("0/%d — eldeki %d gorsel korundu (incelenen %d)\n", PER_TYPE, $existing, $inspected);

        continue;
    }

    foreach (glob(TARGET_DIR."/$key-*.jpg") as $old) {
        unset($credits['demo/urunler/'.basename($old)]);
        unlink($old);
    }

    foreach ($accepted as $index => $item) {
        $file = sprintf('%s-%d.jpg', $key, $index);

        // Eleme kucuk kopyayla yapildi; kazanani tam boyutta indir.
        $big = str_replace('/'.SCREEN_WIDTH.'px-', '/'.THUMB_WIDTH.'px-', (string) ($item['info']['thumburl'] ?? ''));
        $bytes = $big !== '' ? httpGet($big) : null;
        $image = $bytes ? @imagecreatefromstring($bytes) : null;

        if (! $image) {
            $bytes = httpGet((string) ($item['info']['thumburl'] ?? ''));
            $image = $bytes ? @imagecreatefromstring($bytes) : null;
        }

        if (! $image) {
            continue;
        }

        writeSquare($image, TARGET_DIR.'/'.$file);

        $credits['demo/urunler/'.$file] = [
            'baslik' => meta($item['page'], 'ObjectName') ?: ($item['page']['title'] ?? ''),
            'yazar' => meta($item['page'], 'Artist') ?: 'Bilinmiyor',
            'lisans' => meta($item['page'], 'LicenseShortName'),
            'kaynak' => $item['info']['descriptionurl'] ?? '',
        ];
    }

    // Tek fotografla kalan turlerde ayni turun iki urunu birebir ayni
    // goruntuyu kullaniyordu; aynalanmis kopya bunu kiriyor.
    if (count(glob(TARGET_DIR."/$key-*.jpg")) === 1 && is_file(TARGET_DIR."/$key-0.jpg")) {
        $image = imagecreatefromjpeg(TARGET_DIR."/$key-0.jpg");
        imageflip($image, IMG_FLIP_HORIZONTAL);
        imagejpeg($image, TARGET_DIR."/$key-1.jpg", 82);

        $credits["demo/urunler/$key-1.jpg"] = ($credits["demo/urunler/$key-0.jpg"] ?? [])
            + ['not' => 'Aynalanmış kopya'];
    }

    printf("%d/%d gorsel (%d siki, incelenen %d)\n", count($accepted), PER_TYPE, $strict, $inspected);
}

ksort($credits);
file_put_contents($creditsFile, json_encode($credits, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

printf("\nToplam %d gorsel. Kaynaklar: %s\n", count(glob(TARGET_DIR.'/*.jpg')), $creditsFile);
