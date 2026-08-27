<?php

namespace App\Support;

/**
 * public/demo/urunler altindaki demo urun fotograflarina erisim.
 *
 * Fotograflar Wikimedia Commons'tan indirilir (tools/fetch-demo-photos.php),
 * ticari kullanima acik lisanslarla sinirlidir ve kaynaklari KAYNAKLAR.json
 * icinde tutulur. Klasor bossa cagiran taraf SVG cizimlere doner.
 */
class DemoPhotos
{
    /** @var array<string, array<int, string>>|null */
    private static ?array $index = null;

    /** @return array<string, array<int, string>> tur => genel yollar */
    public static function all(): array
    {
        if (self::$index !== null) {
            return self::$index;
        }

        $index = [];
        $directory = public_path('demo/urunler');

        foreach (glob($directory.'/*.jpg') ?: [] as $path) {
            $file = basename($path);

            if (! preg_match('/^(.+)-(\d+)\.jpg$/', $file, $matches)) {
                continue;
            }

            $index[$matches[1]][(int) $matches[2]] = 'demo/urunler/'.$file;
        }

        foreach ($index as &$group) {
            ksort($group);
            $group = array_values($group);
        }

        return self::$index = $index;
    }

    /** @return array<int, string> */
    public static function forShape(string $shape): array
    {
        return self::all()[$shape] ?? [];
    }

    /**
     * Bir urune ayrilan fotograf dilimi. Ayni turdeki iki urun farkli
     * fotograflari kullansin diye kaydirmali dagitilir; fotograf sayisi
     * yetmezse basa sarilir.
     *
     * @return array<int, string>
     */
    public static function slice(string $shape, int $productIndex, int $count = 3): array
    {
        $photos = self::forShape($shape);

        if ($photos === []) {
            return [];
        }

        // Kaydirma, eldeki fotograf sayisina gore: iki urun ayni turden de
        // olsa farkli kareyle acilsin. $productIndex * $count kullanilirsa
        // 2 fotografli turlerde ikisi de ayni kareye dusuyordu.
        $slice = [];
        $offset = $productIndex * max(1, intdiv(count($photos), 2));

        for ($i = 0; $i < $count; $i++) {
            $slice[] = $photos[($offset + $i) % count($photos)];
        }

        return array_values(array_unique($slice));
    }

    /** Kategori kartlari icin tek gorsel. */
    public static function one(string $shape, int $position = 0): ?string
    {
        $photos = self::forShape($shape);

        return $photos === [] ? null : $photos[$position % count($photos)];
    }

    public static function available(): bool
    {
        return self::all() !== [];
    }

    /** @return array<string, array<string, string>> dosya => kaynak bilgisi */
    public static function credits(): array
    {
        $file = public_path('demo/urunler/KAYNAKLAR.json');

        if (! is_file($file)) {
            return [];
        }

        return json_decode(file_get_contents($file), true) ?: [];
    }

    public static function forget(): void
    {
        self::$index = null;
    }
}
