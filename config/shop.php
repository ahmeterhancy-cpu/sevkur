<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Diller
    |--------------------------------------------------------------------------
    | Varsayilan dil URL'de on ek almaz (sevkurmobilya.com/), digerleri
    | /en, /bg, /el on eki ile calisir.
    */

    'default_locale' => 'tr',

    'locales' => [
        'tr' => ['name' => 'Turkce', 'native' => 'Turkce', 'iso' => 'tr_TR', 'currency' => 'TRY', 'dir' => 'ltr'],
        'en' => ['name' => 'Ingilizce', 'native' => 'English', 'iso' => 'en_GB', 'currency' => 'EUR', 'dir' => 'ltr'],
        'bg' => ['name' => 'Bulgarca', 'native' => 'Български', 'iso' => 'bg_BG', 'currency' => 'EUR', 'dir' => 'ltr'],
        'el' => ['name' => 'Yunanca', 'native' => 'Ελληνικά', 'iso' => 'el_GR', 'currency' => 'EUR', 'dir' => 'ltr'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Yerellestirilmis URL segmentleri
    |--------------------------------------------------------------------------
    | Her rota adi icin dile gore yol parcasi. routes/web.php bunlari doner,
    | localized_route() yardimcisi gecerli dilin rotasini cozer.
    */

    'segments' => [
        'shop' => ['tr' => 'magaza', 'en' => 'shop', 'bg' => 'magazin', 'el' => 'katastima'],
        'category' => ['tr' => 'kategori', 'en' => 'category', 'bg' => 'kategoriya', 'el' => 'katigoria'],
        'product' => ['tr' => 'urun', 'en' => 'product', 'bg' => 'produkt', 'el' => 'proion'],
        'cart' => ['tr' => 'sepet', 'en' => 'cart', 'bg' => 'kolichka', 'el' => 'kalathi'],
        'checkout' => ['tr' => 'odeme', 'en' => 'checkout', 'bg' => 'plashtane', 'el' => 'pliromi'],
        'order' => ['tr' => 'siparis', 'en' => 'order', 'bg' => 'porachka', 'el' => 'paraggelia'],
        'wishlist' => ['tr' => 'favorilerim', 'en' => 'wishlist', 'bg' => 'lyubimi', 'el' => 'agapimena'],
        'compare' => ['tr' => 'karsilastir', 'en' => 'compare', 'bg' => 'sravni', 'el' => 'sygkrisi'],
        'blog' => ['tr' => 'blog', 'en' => 'blog', 'bg' => 'blog', 'el' => 'blog'],
        'contact' => ['tr' => 'iletisim', 'en' => 'contact', 'bg' => 'kontakti', 'el' => 'epikoinonia'],
        'about' => ['tr' => 'hakkimizda', 'en' => 'about', 'bg' => 'za-nas', 'el' => 'sxetika'],
        'search' => ['tr' => 'arama', 'en' => 'search', 'bg' => 'tarsene', 'el' => 'anazitisi'],
        'account' => ['tr' => 'hesabim', 'en' => 'account', 'bg' => 'profil', 'el' => 'logariasmos'],
        'login' => ['tr' => 'giris', 'en' => 'login', 'bg' => 'vhod', 'el' => 'syndesi'],
        'register' => ['tr' => 'kayit', 'en' => 'register', 'bg' => 'registraciya', 'el' => 'eggrafi'],
        'orders' => ['tr' => 'siparislerim', 'en' => 'orders', 'bg' => 'porachki', 'el' => 'paraggelies'],
        'addresses' => ['tr' => 'adreslerim', 'en' => 'addresses', 'bg' => 'adresi', 'el' => 'diefthynseis'],
        'profile' => ['tr' => 'profilim', 'en' => 'profile', 'bg' => 'nastroyki', 'el' => 'profil'],
        'page' => ['tr' => 'sayfa', 'en' => 'page', 'bg' => 'stranica', 'el' => 'selida'],
        'faq' => ['tr' => 'sikca-sorulanlar', 'en' => 'faq', 'bg' => 'vaprosi', 'el' => 'erotiseis'],
        'brands' => ['tr' => 'markalar', 'en' => 'brands', 'bg' => 'marki', 'el' => 'markes'],
        'track' => ['tr' => 'siparis-takip', 'en' => 'track-order', 'bg' => 'prosledi', 'el' => 'parakolouthisi'],
        'thanks' => ['tr' => 'tesekkurler', 'en' => 'thank-you', 'bg' => 'blagodarim', 'el' => 'efxaristoume'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Para birimi
    |--------------------------------------------------------------------------
    | Tahsilat her zaman TRY uzerinden yapilir (PayTR yalnizca TRY kabul eder).
    | Diger para birimleri vitrin/gosterim amaclidir.
    */

    'base_currency' => 'TRY',

    'rates' => [
        'source' => 'tcmb',
        'endpoint' => 'https://www.tcmb.gov.tr/kurlar/today.xml',
        'cache_minutes' => 180,
    ],

    /*
    |--------------------------------------------------------------------------
    | Siparis
    |--------------------------------------------------------------------------
    */

    'order' => [
        'number_prefix' => 'SVK',
        'statuses' => ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'],
        'payment_statuses' => ['pending', 'paid', 'failed', 'refunded'],
    ],

    'stock' => [
        'hold_minutes' => 30,
    ],
];
