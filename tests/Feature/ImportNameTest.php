<?php

namespace Tests\Feature;

use App\Services\Import\ProductName;
use Tests\ShopTestCase;

/**
 * Urun adi cikarma.
 *
 * h1'e guvenmek vespero.com'un uc sayfasinda yanlis ad getirdi: o sayfalarin
 * basligi kopyala-yapistir hatasiyla baska urunu gosteriyor, <title> ise
 * adresle uyusuyor. Adres sayfanin kimligi — onu yalanlayan baslik supheli.
 */
class ImportNameTest extends ShopTestCase
{
    private function resolver(): ProductName
    {
        return app(ProductName::class);
    }

    public function test_it_takes_the_heading_when_it_agrees_with_the_url(): void
    {
        [$name, $suspect] = $this->resolver()->resolve(
            '<h1>Oscar Koltuk Takımı</h1><title>Başka Bir Şey</title>',
            'https://example.com/oscar-koltuk-takimi/'
        );

        $this->assertSame('Oscar Koltuk Takımı', $name);
        $this->assertFalse($suspect);
    }

    public function test_a_heading_that_contradicts_the_url_loses_to_the_title(): void
    {
        // vespero.com/teos-kose-takimi/ gercek durumu
        [$name, $suspect] = $this->resolver()->resolve(
            '<h1>Armani Midi Köşe Takımı</h1><title>Teos Köşe Takımı &#8211; Vespero</title>',
            'https://www.vespero.com/teos-kose-takimi/'
        );

        $this->assertSame('Teos Köşe Takımı', $name);
        $this->assertFalse($suspect);
    }

    public function test_the_brand_suffix_is_stripped(): void
    {
        [$name] = $this->resolver()->resolve(
            '<title>Oscar Koltuk Takımı - Sude Collection</title>',
            'https://www.sudecollection.com/oscar-koltuk-takimi/',
            ['- Sude Collection']
        );

        $this->assertSame('Oscar Koltuk Takımı', $name);
    }

    public function test_a_dash_separated_brand_suffix_is_stripped_without_config(): void
    {
        [$name] = $this->resolver()->resolve(
            '<title>Theraphy Yatak – Ravizza Yatak</title>',
            'https://www.ravizzayatak.com/theraphy-yatak/'
        );

        $this->assertSame('Theraphy Yatak', $name);
    }

    public function test_nothing_matching_the_url_is_flagged_as_suspect(): void
    {
        [$name, $suspect] = $this->resolver()->resolve(
            '<h1>Bambaşka Ürün</h1>',
            'https://example.com/hic-alakasi-yok/'
        );

        $this->assertSame('Bambaşka Ürün', $name);
        $this->assertTrue($suspect, 'Adresle uyuşmayan ad şüpheli işaretlenmeli.');
    }

    public function test_a_page_without_any_name_returns_null(): void
    {
        [$name, $suspect] = $this->resolver()->resolve('<div>boş</div>', 'https://example.com/bos/');

        $this->assertNull($name);
        $this->assertFalse($suspect);
    }

    public function test_a_url_suffixed_copy_still_matches(): void
    {
        [$name, $suspect] = $this->resolver()->resolve(
            '<h1>Milano Koltuk Takımı</h1>',
            'https://www.sudecollection.com/milano-koltuk-takimi-2/'
        );

        $this->assertSame('Milano Koltuk Takımı', $name);
        $this->assertFalse($suspect);
    }
    public function test_a_dash_inside_the_real_name_is_kept(): void
    {
        // ravizzayatak.com/luna-yatak-pembe/ — tire marka eki DEGIL, adin parcasi.
        // Kirpmak "Luna Yatak" adli ikinci bir urun uretiyordu.
        [$name, $suspect] = $this->resolver()->resolve(
            '<title>Luna Yatak – Pembe – Ravizza Yatak</title>',
            'https://www.ravizzayatak.com/luna-yatak-pembe/',
            ['- Ravizza Yatak']
        );

        $this->assertSame('Luna Yatak – Pembe', $name);
        $this->assertFalse($suspect);
    }
}
