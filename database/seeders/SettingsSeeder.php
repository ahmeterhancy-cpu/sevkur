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
            'working_hours_en' => 'Monday – Saturday, 09:00 – 19:00',
            'working_hours_bg' => 'Понеделник – събота, 09:00 – 19:00',
            'working_hours_el' => 'Δευτέρα – Σάββατο, 09:00 – 19:00',
            'map_embed' => null,

            'social_facebook' => 'https://facebook.com/',
            'social_instagram' => 'https://instagram.com/',
            'social_youtube' => null,
            'social_whatsapp' => null,

            'free_shipping_over' => 15000,
            'top_bar_text' => 'El işçiliğiyle üretilen mobilyalar — Edirne’den tüm Türkiye’ye',
            'top_bar_text_en' => 'Handcrafted furniture — from Edirne to your home',
            'top_bar_text_bg' => 'Ръчно изработени мебели — от Одрин до вашия дом',
            'top_bar_text_el' => 'Χειροποίητα έπιπλα — από την Αδριανούπολη στο σπίτι σας',

            'promise_shipping' => 'Türkiye geneli kurumsal kargo ve montaj',
            'promise_shipping_en' => 'Nationwide delivery and assembly',
            'promise_shipping_bg' => 'Доставка и монтаж в цялата страна',
            'promise_shipping_el' => 'Πανελλαδική αποστολή και συναρμολόγηση',

            'promise_return_title' => '14 gün içinde iade',
            'promise_return_title_en' => 'Returns within 14 days',
            'promise_return_title_bg' => 'Връщане до 14 дни',
            'promise_return_title_el' => 'Επιστροφές εντός 14 ημερών',

            'promise_return' => 'Koşulsuz değişim ve iade hakkı',
            'promise_return_en' => 'No-questions exchange and refund',
            'promise_return_bg' => 'Безусловна замяна и възстановяване',
            'promise_return_el' => 'Ανταλλαγή και επιστροφή χρημάτων χωρίς όρους',

            'promise_secure_title' => 'Güvenli ödeme',
            'promise_secure_title_en' => 'Secure payment',
            'promise_secure_title_bg' => 'Сигурно плащане',
            'promise_secure_title_el' => 'Ασφαλής πληρωμή',

            'promise_secure' => 'PayTR 3D Secure altyapısı',
            'promise_secure_en' => 'Protected by PayTR 3D Secure',
            'promise_secure_bg' => 'Защитено с PayTR 3D Secure',
            'promise_secure_el' => 'Προστασία με PayTR 3D Secure',

            'promise_support_title' => 'Uzman desteği',
            'promise_support_title_en' => 'Expert support',
            'promise_support_title_bg' => 'Експертна поддръжка',
            'promise_support_title_el' => 'Υποστήριξη από ειδικούς',

            'promise_support' => 'Hafta içi 09:00 – 18:00 arası',
            'promise_support_en' => 'Weekdays 09:00 – 18:00',
            'promise_support_bg' => 'В делнични дни 09:00 – 18:00',
            'promise_support_el' => 'Καθημερινές 09:00 – 18:00',

            // Ana sayfa bolum basliklari her dilde ayri; eksik dil TR'ye duser.
            'home_categories_title' => 'Odalarına göre keşfet',
            'home_categories_title_en' => 'Explore by room',
            'home_categories_title_bg' => 'Разгледайте по стая',
            'home_categories_title_el' => 'Εξερευνήστε ανά δωμάτιο',

            'home_bestsellers_title' => 'Çok Satanlar',
            'home_bestsellers_title_en' => 'Best Sellers',
            'home_bestsellers_title_bg' => 'Най-продавани',
            'home_bestsellers_title_el' => 'Δημοφιλέστερα',

            'home_bestsellers_sub' => 'Müşterilerimizin en çok tercih ettiği parçalar.',
            'home_bestsellers_sub_en' => 'The pieces our customers choose most.',
            'home_bestsellers_sub_bg' => 'Изделията, които клиентите ни избират най-често.',
            'home_bestsellers_sub_el' => 'Τα κομμάτια που προτιμούν περισσότερο οι πελάτες μας.',

            'home_deals_title' => 'Haftanın Fırsatları',
            'home_deals_title_en' => 'Deals of the Week',
            'home_deals_title_bg' => 'Оферти на седмицата',
            'home_deals_title_el' => 'Προσφορές της εβδομάδας',

            'home_new_title' => 'Yeni Gelenler',
            'home_new_title_en' => 'New Arrivals',
            'home_new_title_bg' => 'Нови продукти',
            'home_new_title_el' => 'Νέες αφίξεις',

            'home_blog_title' => 'Yaşam Alanı Notları',
            'home_blog_title_en' => 'Living Space Notes',
            'home_blog_title_bg' => 'Бележки за дома',
            'home_blog_title_el' => 'Σημειώσεις για τον χώρο',

            'footer_about' => 'Sevkur Mobilya; sade, dayanıklı ve zamansız mobilyalarla evinize karakter katar. Masif ahşap ve doğal kumaşlarla, kendi atölyemizde üretiyoruz.',
            'footer_about_en' => 'Sevkur Mobilya brings character to your home with simple, durable and timeless furniture, made in our own workshop from solid wood and natural fabrics.',
            'footer_about_bg' => 'Sevkur Mobilya придава характер на дома ви с простички, издръжливи и вечни мебели, изработени в собствената ни работилница от масивно дърво и естествени тъкани.',
            'footer_about_el' => 'Η Sevkur Mobilya δίνει χαρακτήρα στο σπίτι σας με απλά, ανθεκτικά και διαχρονικά έπιπλα, φτιαγμένα στο δικό μας εργαστήριο από μασίφ ξύλο και φυσικά υφάσματα.',
        ]);
    }
}
