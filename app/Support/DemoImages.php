<?php

namespace App\Support;

/**
 * Demo icerigi icin yer tutucu gorseller uretir.
 *
 * Fotograf yerine SVG teknik cizim kullanilir: dosyalar kucuk, deterministik
 * ve depoya girmeye uygun. Gercek fotograflar geldiginde yonetim panelinden
 * degistirilir, kod degismez.
 */
class DemoImages
{
    /** Sicak, mobilyaya yakisan zemin tonlari. */
    private const BACKGROUNDS = ['#f4efe9', '#eef0ec', '#f6f1ea', '#eceef1', '#f3eee7', '#efeae3'];

    private const INK = '#2b2b2b';

    public static function directory(): string
    {
        return public_path('demo');
    }

    public static function ensureDirectory(): void
    {
        if (! is_dir(self::directory())) {
            mkdir(self::directory(), 0755, true);
        }
    }

    /**
     * Urun gorseli yazar ve genel yolunu doner ("demo/sofa-1.svg").
     */
    public static function product(string $shape, string $name, int $variant = 0): string
    {
        $file = 'demo/'.self::slug($shape.'-'.$name.'-'.$variant).'.svg';
        self::write($file, self::productSvg($shape, $variant));

        return $file;
    }

    public static function category(string $shape, string $name): string
    {
        $file = 'demo/kategori-'.self::slug($name).'.svg';
        self::write($file, self::productSvg($shape, 3, 600, 600));

        return $file;
    }

    public static function banner(string $key, string $title, int $width = 1200, int $height = 900, string $tone = '#efe7dc'): string
    {
        $file = 'demo/afis-'.self::slug($key).'.svg';
        self::write($file, self::bannerSvg($title, $width, $height, $tone));

        return $file;
    }

    public static function post(string $title): string
    {
        $file = 'demo/yazi-'.self::slug($title).'.svg';
        self::write($file, self::bannerSvg('', 1000, 750, '#eceae5', true));

        return $file;
    }

    // ------------------------------------------------------------------ cizim

    private static function productSvg(string $shape, int $variant, int $w = 800, int $h = 800): string
    {
        $bg = self::BACKGROUNDS[$variant % count(self::BACKGROUNDS)];
        $ink = self::INK;
        $body = self::shape($shape);

        // 200x140'lik cizim kutusu tuvalin ortasina olceklenir.
        $scale = round(min($w / 200, $h / 140) * 0.72, 3);
        $cx = $w / 2;
        $cy = $h / 2;

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$w} {$h}" width="{$w}" height="{$h}" role="img">
  <rect width="{$w}" height="{$h}" fill="{$bg}"/>
  <circle cx="{$cx}" cy="{$cy}" r="{$cy}" fill="#ffffff" opacity="0.35"/>
  <g transform="translate({$cx} {$cy}) scale({$scale}) translate(-100 -72)"
     fill="none" stroke="{$ink}" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" opacity="0.85">
    {$body}
  </g>
</svg>
SVG;
    }

    /** Basit, taninabilir mobilya siluetleri (100x140 kutu icinde). */
    private static function shape(string $shape): string
    {
        return match ($shape) {
            'sofa' => '<path d="M14 78h172v34H14z"/><path d="M14 78V52a10 10 0 0 1 10-10h152a10 10 0 0 1 10 10v26"/><path d="M46 78V50h108v28"/><path d="M28 112v14M172 112v14"/>',
            'armchair' => '<path d="M52 76h96v34H52z"/><path d="M52 76V46a10 10 0 0 1 10-10h76a10 10 0 0 1 10 10v30"/><path d="M52 60H40a8 8 0 0 0-8 8v34M148 60h12a8 8 0 0 1 8 8v34"/><path d="M62 110v16M138 110v16"/>',
            'chair' => '<path d="M66 84h68v10H66z"/><path d="M70 30h60v54H70z"/><path d="M70 94v34M130 94v34M70 30v-4M130 30v-4"/>',
            'coffee_table' => '<path d="M28 70h144v10H28z"/><path d="M44 80v40M156 80v40M44 106h112"/>',
            'dining_table' => '<path d="M18 66h164v10H18z"/><path d="M36 76v44M164 76v44M36 100h128"/>',
            'bed' => '<path d="M18 84h164v30H18z"/><path d="M18 84V40h30v44M182 84V56h-24v28"/><path d="M56 84V66h84v18"/><path d="M26 114v12M174 114v12"/>',
            'wardrobe' => '<rect x="46" y="20" width="108" height="104"/><path d="M100 20v104M88 68h8M104 68h8"/><path d="M52 124v8M148 124v8"/>',
            'bookcase' => '<rect x="48" y="18" width="104" height="110"/><path d="M48 46h104M48 74h104M48 102h104"/>',
            'tv_unit' => '<rect x="24" y="66" width="152" height="46"/><path d="M100 66v46M24 89h152"/><path d="M36 112v12M164 112v12"/>',
            'lamp' => '<path d="M70 20h60l10 34H60z"/><path d="M100 54v62"/><path d="M72 124h56l-8-8H80z"/>',
            'rug' => '<rect x="24" y="52" width="152" height="76" rx="6"/><rect x="42" y="68" width="116" height="44" rx="4"/><path d="M24 66h152M24 114h152"/>',
            'nightstand' => '<rect x="62" y="56" width="76" height="60"/><path d="M62 82h76M92 68h16M92 98h16"/><path d="M68 116v10M132 116v10"/>',
            'sideboard' => '<rect x="24" y="52" width="152" height="60"/><path d="M75 52v60M125 52v60M56 82h14M91 82h18M141 82h14"/><path d="M36 112v12M164 112v12"/>',
            'stool' => '<ellipse cx="100" cy="66" rx="42" ry="12"/><path d="M62 70l10 54M138 70l-10 54M74 100h52"/>',
            'mirror' => '<rect x="62" y="16" width="76" height="112" rx="38"/><path d="M78 44a30 30 0 0 1 18-14"/>',
            'desk' => '<path d="M20 62h160v10H20z"/><rect x="112" y="72" width="56" height="34"/><path d="M32 72v52M168 106v18M112 89h56"/>',
            'shelf' => '<path d="M30 34h140M30 74h140M30 114h140"/><path d="M46 30v88M154 30v88"/>',
            'console' => '<path d="M22 60h156v9H22z"/><path d="M40 69v52M160 69v52M40 92h120"/><rect x="70" y="70" width="60" height="20"/>',
            'chandelier' => '<path d="M100 12v22"/><path d="M56 34h88"/><path d="M56 34l-12 26h24zM100 34l-12 26h24zM144 34l-12 26h24z"/>',
            'cushion' => '<rect x="54" y="46" width="92" height="82" rx="10"/><path d="M70 62c20 14 40 14 60 0M70 112c20-14 40-14 60 0"/>',
            'curtain' => '<path d="M34 18h132"/><path d="M50 18c8 34-8 70 0 106M78 18c8 34-8 70 0 106M122 18c-8 34 8 70 0 106M150 18c-8 34 8 70 0 106"/>',
            'artwork' => '<rect x="46" y="26" width="108" height="92"/><path d="M58 100l26-34 20 24 14-16 24 26z"/><circle cx="124" cy="52" r="8"/>',
            'display_cabinet' => '<rect x="52" y="16" width="96" height="108"/><path d="M100 16v108M52 52h96M52 88h96"/>',
            'office_chair' => '<path d="M68 24h60v44H68z"/><path d="M62 80h76l-4 12H66z"/><path d="M100 92v18M74 124l26-14 26 14"/><path d="M62 68h6M132 68h6"/>',
            'dresser' => '<rect x="46" y="40" width="108" height="84"/><path d="M46 68h108M46 96h108M88 54h24M88 82h24M88 110h24"/>',
            default => '<rect x="40" y="40" width="120" height="80"/><path d="M40 80h120M100 40v80"/>',
        };
    }

    private static function bannerSvg(string $title, int $w, int $h, string $tone, bool $soft = false): string
    {
        $ink = self::INK;
        $accent = '#eb7700';
        $label = $title !== ''
            ? '<text x="'.($w / 2).'" y="'.($h / 2).'" text-anchor="middle" font-family="Jost, sans-serif" font-size="'.max(22, (int) ($w / 26)).'" fill="'.$ink.'" opacity="0.18">'.htmlspecialchars($title, ENT_QUOTES).'</text>'
            : '';

        $decor = $soft
            ? '<circle cx="'.($w * 0.72).'" cy="'.($h * 0.3).'" r="'.($w * 0.18).'" fill="#ffffff" opacity="0.55"/>'
            : '<rect x="'.($w * 0.55).'" y="'.($h * 0.18).'" width="'.($w * 0.34).'" height="'.($h * 0.64).'" fill="#ffffff" opacity="0.4"/>';

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$w} {$h}" width="{$w}" height="{$h}" role="img">
  <rect width="{$w}" height="{$h}" fill="{$tone}"/>
  {$decor}
  <circle cx="{$w}" cy="0" r="{$h}" fill="{$accent}" opacity="0.06"/>
  {$label}
</svg>
SVG;
    }

    private static function write(string $relative, string $contents): void
    {
        self::ensureDirectory();
        $path = public_path($relative);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $contents);
    }

    private static function slug(string $value): string
    {
        $value = str_replace(
            ['ı', 'İ', 'ğ', 'Ğ', 'ü', 'Ü', 'ş', 'Ş', 'ö', 'Ö', 'ç', 'Ç'],
            ['i', 'i', 'g', 'g', 'u', 'u', 's', 's', 'o', 'o', 'c', 'c'],
            $value
        );

        return trim(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($value)), '-');
    }
}
