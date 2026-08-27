<?php

namespace Tests\Feature;

use App\Support\Locales;
use Tests\TestCase;

/**
 * Dil dosyalarinin butunlugu.
 *
 * Yinelenen anahtar PHP'de hata vermez, sessizce sonuncuyu kullanir. Boyle bir
 * hata yuzunden magaza adi yerine "Marka" yazan bir sayfa basligi yayina
 * gitmisti; bu testler o sinifi kapatiyor.
 */
class TranslationTest extends TestCase
{
    public function test_language_files_have_no_duplicate_keys(): void
    {
        foreach (Locales::all() as $locale) {
            $path = lang_path("$locale/shop.php");

            $this->assertFileExists($path);

            preg_match_all('/^\s*\'([a-z0-9_]+)\'\s*=>/m', file_get_contents($path), $matches);

            $duplicates = array_keys(array_filter(
                array_count_values($matches[1]),
                fn (int $count) => $count > 1
            ));

            $this->assertSame([], $duplicates, "$locale/shop.php içinde yinelenen anahtar: ".implode(', ', $duplicates));
        }
    }

    public function test_every_locale_defines_the_same_keys(): void
    {
        $reference = array_keys(require lang_path(Locales::default().'/shop.php'));
        sort($reference);

        foreach (Locales::all() as $locale) {
            if ($locale === Locales::default()) {
                continue;
            }

            $keys = array_keys(require lang_path("$locale/shop.php"));
            sort($keys);

            $missing = array_diff($reference, $keys);
            $extra = array_diff($keys, $reference);

            $this->assertSame([], array_values($missing), "$locale dilinde eksik anahtar: ".implode(', ', $missing));
            $this->assertSame([], array_values($extra), "$locale dilinde fazladan anahtar: ".implode(', ', $extra));
        }
    }

    public function test_placeholders_match_across_locales(): void
    {
        $reference = require lang_path(Locales::default().'/shop.php');

        foreach (Locales::all() as $locale) {
            if ($locale === Locales::default()) {
                continue;
            }

            $translations = require lang_path("$locale/shop.php");

            foreach ($reference as $key => $value) {
                if (! is_string($value) || ! isset($translations[$key])) {
                    continue;
                }

                preg_match_all('/:[a-z_]+/', $value, $expected);
                preg_match_all('/:[a-z_]+/', $translations[$key], $actual);

                sort($expected[0]);
                sort($actual[0]);

                $this->assertSame(
                    $expected[0],
                    $actual[0],
                    "$locale/$key: yer tutucular eslesmiyor (:count gibi degiskenler)"
                );
            }
        }
    }
}
