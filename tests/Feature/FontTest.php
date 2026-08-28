<?php

namespace Tests\Feature;

use Tests\ShopTestCase;

/**
 * Yazi tipleri derleme aninda indirilip projeye gomulur.
 *
 * Alt kume (subset) secimi kritiktir: sade "latin" alt kumesinde Turkce
 * s/g/I harfleri YOKTUR. Eksik olursa sayfa bozulmaz, yalnizca O HARFLER
 * sistem fontuna duser — "Yasam Alani Notlari" basligi karisik gorunur.
 * Goz denetimi bunu kacirabildigi icin test ediliyor.
 */
class FontTest extends ShopTestCase
{
    /** Derlenmis font stil dosyasi. */
    private function fontCss(): string
    {
        $files = glob(public_path('build/assets/fonts-*.css'));

        $this->assertNotEmpty($files, 'Font CSS derlenmemis; once "npm run build" calistirin.');

        return file_get_contents($files[0]);
    }

    public function test_turkish_letters_are_covered_by_the_downloaded_subsets(): void
    {
        $ranges = $this->unicodeRanges($this->fontCss());

        // s = U+015F, g = U+011F, I = U+0130 — hepsi latin-ext icinde
        foreach (['015F' => 'ş', '011F' => 'ğ', '0130' => 'İ', '0131' => 'ı'] as $code => $letter) {
            $this->assertTrue(
                $this->covered(hexdec($code), $ranges),
                "$letter (U+$code) hicbir font alt kumesinde yok; o harf sistem fontuna duser."
            );
        }
    }

    public function test_bulgarian_and_greek_letters_are_covered(): void
    {
        $ranges = $this->unicodeRanges($this->fontCss());

        // Kiril "Б" (U+0411) ve Yunan "Δ" (U+0394)
        foreach (['0411' => 'Б', '0394' => 'Δ'] as $code => $letter) {
            $this->assertTrue(
                $this->covered(hexdec($code), $ranges),
                "$letter (U+$code) hicbir font alt kumesinde yok."
            );
        }
    }

    public function test_the_layout_actually_links_the_fonts(): void
    {
        // Vite::fonts() unutulursa @font-face uretilir ama sayfaya hic
        // baglanmaz; metin sessizce sistem fontuna duser (bir kez yasandi).
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString('Vite::fonts()', $layout);
    }

    /**
     * CSS'teki butun unicode-range araliklarini toplar.
     *
     * @return array<int, array{0: int, 1: int}>
     */
    private function unicodeRanges(string $css): array
    {
        preg_match_all('/unicode-range:\s*([^;]+);/i', $css, $matches);

        $ranges = [];

        foreach ($matches[1] as $list) {
            foreach (explode(',', $list) as $part) {
                $part = trim($part);

                if (preg_match('/^U\+([0-9A-F]+)-([0-9A-F]+)$/i', $part, $m)) {
                    $ranges[] = [hexdec($m[1]), hexdec($m[2])];
                } elseif (preg_match('/^U\+([0-9A-F]+)$/i', $part, $m)) {
                    $ranges[] = [hexdec($m[1]), hexdec($m[1])];
                }
            }
        }

        $this->assertNotEmpty($ranges, 'Font CSS icinde unicode-range bulunamadi.');

        return $ranges;
    }

    /** @param array<int, array{0: int, 1: int}> $ranges */
    private function covered(int $codepoint, array $ranges): bool
    {
        foreach ($ranges as [$start, $end]) {
            if ($codepoint >= $start && $codepoint <= $end) {
                return true;
            }
        }

        return false;
    }
}
