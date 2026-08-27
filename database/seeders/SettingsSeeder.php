<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        // ADRES / TELEFON / E-POSTA yer tutucudur; canliya cikmadan once
        // yonetim panelinden gercek bilgilerle degistirilmelidir.
        Setting::putMany([
            'site_name' => 'Sevkur Mobilya',
            // Baslik ve aciklama her dilde ayri girilebilir ("_en", "_bg", "_el").
            'site_title' => 'Sevkur Mobilya — Sade, dayanıklı, zamansız',
            'site_title_en' => 'Sevkur Furniture — Simple, durable, timeless',
            'site_title_bg' => 'Севкур Мебели — Прости, здрави, вечни',
            'site_title_el' => 'Sevkur Έπιπλα — Απλά, ανθεκτικά, διαχρονικά',

            'site_description' => 'Oturma odasından yatak odasına, el işçiliğiyle üretilen modern mobilyalar. Türkiye geneli kurumsal kargo, güvenli ödeme.',
            'site_description_en' => 'Handcrafted modern furniture for every room, from living room to bedroom. Nationwide delivery and secure payment.',
            'site_description_bg' => 'Ръчно изработени модерни мебели за всяка стая — от хола до спалнята. Доставка и сигурно плащане.',
            'site_description_el' => 'Χειροποίητα μοντέρνα έπιπλα για κάθε δωμάτιο, από το σαλόνι ως το υπνοδωμάτιο. Αποστολή και ασφαλής πληρωμή.',
            'logo' => null,
            'logo_light' => null,
            'favicon' => null,

            'phone' => '+90 284 000 00 00',
            'email' => 'info@sevkurmobilya.com',
            'address' => 'Sanayi Mah. Mobilyacılar Sitesi No: 00, Edirne',
            'working_hours' => 'Pazartesi – Cumartesi, 09:00 – 19:00',
            'map_embed' => null,

            'social_facebook' => 'https://facebook.com/',
            'social_instagram' => 'https://instagram.com/',
            'social_youtube' => null,
            'social_whatsapp' => null,

            'free_shipping_over' => 15000,
            'top_bar_text' => 'El işçiliğiyle üretilen mobilyalar — Edirne’den tüm Türkiye’ye',

            'promise_shipping' => 'Türkiye geneli kurumsal kargo ve montaj',
            'promise_return_title' => '14 gün içinde iade',
            'promise_return' => 'Koşulsuz değişim ve iade hakkı',
            'promise_secure_title' => 'Güvenli ödeme',
            'promise_secure' => 'PayTR 3D Secure altyapısı',
            'promise_support_title' => 'Uzman desteği',
            'promise_support' => 'Hafta içi 09:00 – 18:00 arası',

            'home_categories_title' => 'Odalarına göre keşfet',
            'home_bestsellers_title' => 'Çok Satanlar',
            'home_bestsellers_sub' => 'Müşterilerimizin en çok tercih ettiği parçalar.',
            'home_deals_title' => 'Haftanın Fırsatları',
            'home_new_title' => 'Yeni Gelenler',
            'home_blog_title' => 'Yaşam Alanı Notları',

            'footer_about' => 'Sevkur Mobilya; sade, dayanıklı ve zamansız mobilyalarla evinize karakter katar. Masif ahşap ve doğal kumaşlarla, kendi atölyemizde üretiyoruz.',
        ]);
    }
}
