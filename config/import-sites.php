<?php

/**
 * Tedarikci sitelerinden urun aktarma ayarlari.
 *
 * Her site icin: urun adreslerinin KESIN kaynagi (site haritasi) ve kategori
 * eslesmesinin kaynagi (kategori sayfalari). Kategoriyi urun slug'indan
 * TAHMIN ETMIYORUZ — Oben katalogunda sira tahmini dort koleksiyonda yanlis
 * fotografa yol acmisti. Kategori sayfasi neyi listeliyorsa o dogrudur.
 *
 * `kategoriler`: tedarikci kategori adresi => Sevkur kategori slug'i
 * `varsayilan`:  kategori sayfalarinda gorunmeyen urun buraya duser
 * `ad_eki`:      baslikta tekrarlayan marka eki ("… - Sude Collection")
 */
return [

    'sude' => [
        'ad' => 'Sude Collection',
        'taban' => 'https://www.sudecollection.com',
        'site_haritasi' => 'https://www.sudecollection.com/product-sitemap.xml',
        'ad_eki' => ['- Sude Collection'],
        'kategoriler' => [
            '/koltuk-takimlari/' => 'koltuk-takimlari',
            '/kose-koltuklar/' => 'kose-takimlari',
            '/kanepeler/' => 'koltuk-takimlari',
            '/tv-koltuklari/' => 'berjer-ve-koltuk',
        ],
        'varsayilan' => 'koltuk-takimlari',
        // Urun olmayan sayfalar site haritasinda da duruyor.
        'atla' => ['/urunler/', '/hakkimizda/', '/iletisim/', '/fuarlar/'],
    ],

    'ravizza' => [
        'ad' => 'Ravizza Yatak',
        'taban' => 'https://www.ravizzayatak.com',
        // Urunler WooCommerce urunu degil duz WordPress YAZISI; urun site
        // haritasi yok, yazi haritasi blog yazilariyla karisik. Bu yuzden
        // urun kumesi kategori sayfalarindan cikariliyor.
        'kategoriden_kesfet' => true,
        'ad_eki' => ['- Ravizza Yatak', '- Ravizza'],
        'kategoriler' => [
            '/yataklar/' => 'yataklar',
            '/yatak-setleri/' => 'yatak-setleri',
            '/bazalar/' => 'bazalar',
            '/yatak-baza-basligi/' => 'yatak-basliklari',
        ],
        'varsayilan' => 'yataklar',
        // Qode temasinin galeri kabi. Kesilmezse sayfanin altindaki ilgili
        // urun karuselinden BASKA urunlerin fotograflari geliyor: Theraphy
        // sayfasindan Bambu ve Bohem cikmisti.
        'galeri_basi' => 'qode-advanced-image-gallery',
        'galeri_sonu' => 'qode-blog-carousel-titled',
        'ayni_aile' => true,
        // Kategori sayfalarinda gorunen ama urun olmayan adresler.
        'atla' => [
            '/', '/urunler/', '/hakkimizda/', '/iletisim/', '/blog/',
            '/kariyer/', '/kataloglar/', '/bayimiz-ol/', '/feed/',
            '/gizlilik-politikamiz/', '/bilgi-guvenligi-politikamiz/',
            '/kisisel-verilerin-korunmasi-kanunu/', '/wp-json/',
            '/yataklar/', '/yatak-setleri/', '/bazalar/', '/yatak-baza-basligi/',
        ],
    ],

    'arno' => [
        'ad' => 'Arno',
        'taban' => 'https://arno.com.tr',
        'site_haritasi' => 'https://arno.com.tr/wp-sitemap-posts-product-1.xml',
        'ad_eki' => ['- Arno', '| Arno'],
        // Arno urun adresleri kategoriyi ZATEN tasiyor:
        // /urunler/arno-home/yatak-odalari/komodinler/hisar-komodin/
        // Bu yuzden kategori sayfasi gezmeye gerek yok, yol parcasi okunur.
        // Eslesme adresin SONUNA dogru aranir, yani en ozel parca kazanir:
        // "genc-komodinler" varsa "arno-go" degil o kullanilir.
        //
        // arno-go hatti GENC ODASI mobilyasi ve kendi kategori agacina
        // dusuyor; yetiskin kategorileriyle karismasin diye ayri tutuldu.
        'yoldan_kategori' => [
            // arno-yatak hatti
            'yataklar' => 'yataklar',
            'yatak-setleri' => 'yatak-setleri',
            'bazalar' => 'bazalar',
            'basliklar' => 'yatak-basliklari',

            // arno-go (genc odasi) hatti — en ozel parcalar once
            'genc-dolaplar' => 'genc-gardiroplar',
            'genc-karyolalar' => 'genc-karyolalar',
            // Baslik ve karyola alti tek basina satilmiyor, karyolayla birlikte.
            'genc-karyola-basliklari' => 'genc-karyolalar',
            'genc-karyola-altlari' => 'genc-karyolalar',
            'genc-calisma-masalari' => 'genc-calisma-masalari',
            'genc-komodinler' => 'genc-komodinler',
            'genc-sifonyerler' => 'genc-sifonyerler',
            'genc-sifonyer-aynalari' => 'genc-sifonyerler',
            'genc-kitapliklar' => 'genc-kitapliklar',
            'genc-odalari' => 'genc-odasi-takimlari',
            'ranzalar' => 'ranzalar',

            // arno-home hatti
            'dolap-govdeleri-giyinme-odalari' => 'gardiroplar',
            'dolap-icleri-giyinme-odalari' => 'gardiroplar',
            'ada-tezgahlar-giyinme-odalari' => 'gardiroplar',
            'giyinme-odalari' => 'gardiroplar',
            'karyola-basliklari' => 'yatak-basliklari',
            'sifonyer-aynalari' => 'aynalar',
            'konsol-aynalari' => 'aynalar',
            'acilir-masalar' => 'yemek-masalari',
            'tv-sehpalari' => 'tv-uniteleri',
            'orta-sehpalar' => 'sehpalar',
            'zigon-sehpalar' => 'sehpalar',
            'puflar' => 'berjer-ve-koltuk',
            'karyolalar' => 'karyolalar',
            'komodinler' => 'komodinler',
            'sifonyerler' => 'sifonyerler',
            'dolaplar' => 'gardiroplar',
            'gardiroplar' => 'gardiroplar',
            'sandalyeler' => 'sandalyeler',
            'konsollar' => 'konsollar',
            'vitrinler' => 'vitrinler',
            'sehpalar' => 'sehpalar',
            'tv-uniteleri' => 'tv-uniteleri',
            'koltuk-takimlari' => 'koltuk-takimlari',
            'kose-takimlari' => 'kose-takimlari',
            'berjerler' => 'berjer-ve-koltuk',
            'kitapliklar' => 'kitapliklar',
            'calisma-masalari' => 'calisma-masalari',
            'aynalar' => 'aynalar',
            'raflar' => 'raflar',

            // Alt kategorisi olmayan urunler oda takimina duser
            'yatak-odalari' => 'yatak-odasi-takimlari',
            'yemek-odalari' => 'yemek-odasi-takimlari',
        ],
        'varsayilan' => 'yatak-odasi-takimlari',
        // Blocksy temasinin galeri kabi. Kesilmezse Hisar Komodin sayfasindan
        // LARINA ve KUMSAL fotograflari geliyor.
        'galeri_basi' => 'ct-product-gallery-container',
        'galeri_sonu' => 'woocommerce-tabs',
        'atla' => [],
    ],

];
