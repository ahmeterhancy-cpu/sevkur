<?php

namespace App\Services\Import;

/**
 * Disaridan alinan urun fotograflarini magaza medyasina yazar.
 *
 * Dort aktarici (Oben, Omairat, Vespero, site kazima) ayni isi yapiyordu:
 * indir, JPEG'e cevir, buyukse kucult, public/urunler altina yaz. Tedarikci
 * siteleri WebP sunuyor, vitrin JPEG kullaniyor.
 *
 * Inmis dosya yeniden indirilmez: komut yarida kalirsa bastan calistirmak
 * ucuz olsun (Vespero'da yuz urunun ortasinda bellek tukenmisti).
 */
class ImageStore
{
    /** Vitrinde bu genislikten buyuk fotografa gerek yok. */
    private const MAX_WIDTH = 1400;

    /** Bundan kucugu onizleme sayilir, vitrine konmaz. */
    private const MIN_WIDTH = 500;

    public function __construct(private readonly RemoteFetcher $fetcher) {}

    /**
     * Fotografi indirip kaydeder, magaza koku icindeki yolunu dondurur.
     *
     * @param  string  $folder  public/urunler altindaki klasor adi
     */
    public function store(string $url, string $folder, string $slug, int $index): ?string
    {
        $relative = sprintf('urunler/%s/%s-%d.jpg', $folder, $slug, $index);
        $target = public_path($relative);

        if (is_file($target)) {
            return $relative;
        }

        $response = $this->fetcher->image($url);

        if (! $response['ok']) {
            return null;
        }

        $image = @imagecreatefromstring($response['body']);

        if ($image === false) {
            return null;
        }

        if (imagesx($image) < self::MIN_WIDTH) {
            imagedestroy($image);

            return null;
        }

        if (imagesx($image) > self::MAX_WIDTH) {
            $scaled = imagescale($image, self::MAX_WIDTH);

            if ($scaled !== false) {
                imagedestroy($image);
                $image = $scaled;
            }
        }

        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }

        $ok = imagejpeg($image, $target, 86);
        imagedestroy($image);

        // Paletli PNG'de imagewebp/imagejpeg true donup 0 baytlik dosya
        // birakabiliyor; bayt sayisi dogrulanmadan basarili sayilmaz.
        if (! $ok || ! is_file($target) || filesize($target) < 1024) {
            @unlink($target);

            return null;
        }

        return $relative;
    }
}
