<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\Review;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        $this->users();
        $this->shipping();
        $this->coupons();
        $this->reviews();
    }

    private function users(): void
    {
        User::updateOrCreate(['email' => 'admin@sevkurmobilya.com'], [
            'name' => 'Sevkur Yönetici',
            'password' => 'Sevkur2026!',
            'role' => User::ROLE_ADMIN,
            'locale' => 'tr',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        User::updateOrCreate(['email' => 'musteri@example.com'], [
            'name' => 'Deniz Yılmaz',
            'password' => 'password',
            'role' => User::ROLE_CUSTOMER,
            'phone' => '+90 555 000 00 00',
            'locale' => 'tr',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function shipping(): void
    {
        $methods = [
            [
                'code' => 'standart',
                'name' => ['tr' => 'Standart Kargo', 'en' => 'Standard Shipping', 'bg' => 'Стандартна доставка', 'el' => 'Κανονική Αποστολή'],
                'description' => [
                    'tr' => '2–4 iş günü içinde kapınızda, 15.000 ₺ üzeri ücretsiz.',
                    'en' => 'At your door in 2–4 business days, free over 15,000 ₺.',
                    'bg' => 'До 2–4 работни дни, безплатно над 15 000 ₺.',
                    'el' => 'Σε 2–4 εργάσιμες ημέρες, δωρεάν άνω των 15.000 ₺.',
                ],
                'price' => 499,
                'free_over' => 15000,
                'eta_days' => 4,
                'position' => 0,
            ],
            [
                'code' => 'ekspres',
                'name' => ['tr' => 'Ekspres Kargo', 'en' => 'Express Shipping', 'bg' => 'Експресна доставка', 'el' => 'Ταχεία Αποστολή'],
                'description' => [
                    'tr' => 'Stoktaki ürünlerde 1–2 iş günü.',
                    'en' => '1–2 business days for in-stock items.',
                    'bg' => '1–2 работни дни за налични продукти.',
                    'el' => '1–2 εργάσιμες ημέρες για διαθέσιμα προϊόντα.',
                ],
                'price' => 999,
                'free_over' => null,
                'eta_days' => 2,
                'position' => 1,
            ],
            [
                'code' => 'magazadan-teslim',
                'name' => ['tr' => 'Mağazadan Teslim', 'en' => 'Store Pickup', 'bg' => 'Вземане от магазина', 'el' => 'Παραλαβή από το Κατάστημα'],
                'description' => [
                    'tr' => 'Edirne mağazamızdan ücretsiz teslim alın.',
                    'en' => 'Collect free of charge from our Edirne store.',
                    'bg' => 'Безплатно вземане от магазина ни в Одрин.',
                    'el' => 'Δωρεάν παραλαβή από το κατάστημά μας.',
                ],
                'price' => 0,
                'free_over' => null,
                'eta_days' => 1,
                'position' => 2,
            ],
        ];

        foreach ($methods as $method) {
            ShippingMethod::updateOrCreate(['code' => $method['code']], $method + ['is_active' => true]);
        }
    }

    private function coupons(): void
    {
        Coupon::updateOrCreate(['code' => 'HOSGELDIN10'], [
            'type' => 'percent',
            'value' => 10,
            'min_total' => 3000,
            'max_uses' => 500,
            'max_uses_per_user' => 1,
            'is_active' => true,
            'ends_at' => now()->addMonths(6),
        ]);

        Coupon::updateOrCreate(['code' => 'KARGOBEDAVA'], [
            'type' => 'fixed',
            'value' => 0,
            'min_total' => 2000,
            'free_shipping' => true,
            'is_active' => true,
            'ends_at' => now()->addMonths(3),
        ]);

        Coupon::updateOrCreate(['code' => 'SEVKUR500'], [
            'type' => 'fixed',
            'value' => 500,
            'min_total' => 6000,
            'max_uses' => 200,
            'is_active' => true,
            'ends_at' => now()->addMonths(2),
        ]);
    }

    /**
     * Gercekci ama uydurma olmayan bir denge: yorumlar demo verisidir ve
     * yonetim panelinden silinebilir. Metinler urun turunden bagimsiz,
     * genel ifadelerdir.
     */
    private function reviews(): void
    {
        $samples = [
            ['Ayşe K.', 5, 'Beklediğimden iyi', 'Kutudan çıktığı gibi kullanıma hazırdı, işçiliği temiz. Renk sitedeki görselle uyumlu.'],
            ['Mert D.', 4, 'Sağlam ve şık', 'Montaj kolaydı, malzeme kalitesi fiyatına göre iyi. Teslimat bir gün gecikti.'],
            ['Elif T.', 5, 'Tavsiye ederim', 'Salonumuza çok yakıştı. Müşteri hizmetleri sorularıma hızlı döndü.'],
            ['Burak S.', 4, 'Memnunum', 'Ölçüler sitede yazdığı gibi. Ambalaj gayet özenliydi.'],
            ['Selin A.', 5, 'Kaliteli işçilik', 'Ahşap dokusu çok güzel, detaylara özen gösterilmiş.'],
        ];

        $products = Product::orderBy('id')->get();

        foreach ($products as $index => $product) {
            // Her ürüne 0-3 arası yorum, deterministik dagilim
            $count = $index % 4;

            for ($i = 0; $i < $count; $i++) {
                [$name, $rating, $title, $body] = $samples[($index + $i) % count($samples)];

                Review::updateOrCreate(
                    ['product_id' => $product->id, 'name' => $name],
                    [
                        'email' => null,
                        'rating' => $rating,
                        'title' => $title,
                        'body' => $body,
                        'is_approved' => true,
                        'created_at' => now()->subDays(($index * 3 + $i * 7) % 90),
                    ]
                );
            }

            $product->refreshRating();
        }
    }
}
