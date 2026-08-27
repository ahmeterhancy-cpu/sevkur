<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Support\DemoImages;
use App\Support\DemoPhotos;
use Illuminate\Database\Seeder;

class TaxonomySeeder extends Seeder
{
    /**
     * Kategori agaci: [slug, cizim, TR, EN, BG, EL, [alt kategoriler]]
     */
    public const TREE = [
        ['oturma-odasi', 'sofa', 'Oturma Odası', 'Living Room', 'Хол', 'Καθιστικό', [
            ['koltuk-takimlari', 'sofa', 'Koltuk Takımları', 'Sofas', 'Дивани', 'Καναπέδες'],
            ['berjer-ve-koltuk', 'armchair', 'Berjer ve Tekli Koltuk', 'Armchairs', 'Кресла', 'Πολυθρόνες'],
            ['sehpalar', 'coffee_table', 'Sehpalar', 'Coffee Tables', 'Холни маси', 'Τραπεζάκια'],
            ['tv-uniteleri', 'tv_unit', 'TV Üniteleri', 'TV Units', 'ТВ шкафове', 'Έπιπλα TV'],
        ]],
        ['yatak-odasi', 'bed', 'Yatak Odası', 'Bedroom', 'Спалня', 'Υπνοδωμάτιο', [
            ['karyolalar', 'bed', 'Karyolalar', 'Beds', 'Легла', 'Κρεβάτια'],
            ['gardiroplar', 'wardrobe', 'Gardıroplar', 'Wardrobes', 'Гардероби', 'Ντουλάπες'],
            ['komodinler', 'nightstand', 'Komodinler', 'Nightstands', 'Нощни шкафчета', 'Κομοδίνα'],
            ['sifonyerler', 'dresser', 'Şifonyerler', 'Dressers', 'Скринове', 'Συρταριέρες'],
        ]],
        ['yemek-odasi', 'dining_table', 'Yemek Odası', 'Dining Room', 'Трапезария', 'Τραπεζαρία', [
            ['yemek-masalari', 'dining_table', 'Yemek Masaları', 'Dining Tables', 'Трапезни маси', 'Τραπέζια'],
            ['sandalyeler', 'chair', 'Sandalyeler', 'Chairs', 'Столове', 'Καρέκλες'],
            ['konsollar', 'console', 'Konsollar', 'Consoles', 'Конзоли', 'Κονσόλες'],
            ['vitrinler', 'display_cabinet', 'Vitrinler', 'Display Cabinets', 'Витрини', 'Βιτρίνες'],
        ]],
        ['calisma-odasi', 'desk', 'Çalışma Odası', 'Home Office', 'Кабинет', 'Γραφείο', [
            ['calisma-masalari', 'desk', 'Çalışma Masaları', 'Desks', 'Бюра', 'Γραφεία'],
            ['ofis-koltuklari', 'office_chair', 'Ofis Koltukları', 'Office Chairs', 'Офис столове', 'Καρέκλες Γραφείου'],
            ['kitapliklar', 'bookcase', 'Kitaplıklar', 'Bookcases', 'Библиотеки', 'Βιβλιοθήκες'],
        ]],
        ['aydinlatma', 'lamp', 'Aydınlatma', 'Lighting', 'Осветление', 'Φωτισμός', [
            ['zemin-lambalari', 'lamp', 'Zemin Lambaları', 'Floor Lamps', 'Лампиони', 'Φωτιστικά Δαπέδου'],
            ['avizeler', 'chandelier', 'Avizeler', 'Chandeliers', 'Полилеи', 'Πολυέλαιοι'],
        ]],
        ['ev-tekstili', 'rug', 'Ev Tekstili', 'Home Textiles', 'Домашен текстил', 'Λευκά Είδη', [
            ['halilar', 'rug', 'Halılar', 'Rugs', 'Килими', 'Χαλιά'],
            ['kirlentler', 'cushion', 'Kırlentler', 'Cushions', 'Възглавници', 'Μαξιλάρια'],
            ['perdeler', 'curtain', 'Perdeler', 'Curtains', 'Завеси', 'Κουρτίνες'],
        ]],
        ['dekorasyon', 'mirror', 'Dekorasyon', 'Decor', 'Декорация', 'Διακόσμηση', [
            ['aynalar', 'mirror', 'Aynalar', 'Mirrors', 'Огледала', 'Καθρέφτες'],
            ['tablolar', 'artwork', 'Tablolar', 'Wall Art', 'Картини', 'Πίνακες'],
            ['raflar', 'shelf', 'Raflar', 'Shelves', 'Рафтове', 'Ράφια'],
        ]],
    ];

    public function run(): void
    {
        DemoImages::ensureDirectory();

        $position = 0;

        foreach (self::TREE as [$slug, $shape, $tr, $en, $bg, $el, $children]) {
            $root = Category::updateOrCreate(['slug' => $slug], [
                'name' => ['tr' => $tr, 'en' => $en, 'bg' => $bg, 'el' => $el],
                'description' => [
                    'tr' => "$tr için sade çizgili, dayanıklı ve zamansız parçalar.",
                    'en' => "Simple, durable and timeless pieces for your $en.",
                    'bg' => 'Прости, здрави и вечни мебели за вашия дом.',
                    'el' => 'Απλά, ανθεκτικά και διαχρονικά κομμάτια για τον χώρο σας.',
                ],
                'image' => DemoPhotos::one($shape) ?? DemoImages::category($shape, $slug),
                'is_active' => true,
                'is_featured' => true,
                'show_in_menu' => true,
                'position' => $position++,
            ]);

            $childPosition = 0;

            foreach ($children as [$childSlug, $childShape, $ctr, $cen, $cbg, $cel]) {
                Category::updateOrCreate(['slug' => $childSlug], [
                    'parent_id' => $root->id,
                    'name' => ['tr' => $ctr, 'en' => $cen, 'bg' => $cbg, 'el' => $cel],
                    'image' => DemoPhotos::one($childShape, 1) ?? DemoImages::category($childShape, $childSlug),
                    'is_active' => true,
                    'show_in_menu' => true,
                    'position' => $childPosition++,
                ]);
            }
        }

        $this->brands();
        $this->attributes();
    }

    private function brands(): void
    {
        $brands = [
            ['sevkur-atolye', 'Sevkur Atölye', 'Sevkur Atelier', 'Севкур Ателие', 'Sevkur Atelier'],
            ['meric-design', 'Meriç Design', 'Meriç Design', 'Мерич Дизайн', 'Meriç Design'],
            ['trakya-ahsap', 'Trakya Ahşap', 'Trakya Wood', 'Тракия Дърво', 'Trakya Wood'],
            ['kaleici-koleksiyon', 'Kaleiçi Koleksiyon', 'Kaleiçi Collection', 'Колекция Калеичи', 'Συλλογή Kaleiçi'],
            ['selimiye-home', 'Selimiye Home', 'Selimiye Home', 'Селимие Хоум', 'Selimiye Home'],
            ['enez-living', 'Enez Living', 'Enez Living', 'Енез Ливинг', 'Enez Living'],
        ];

        foreach ($brands as $index => [$slug, $tr, $en, $bg, $el]) {
            Brand::updateOrCreate(['slug' => $slug], [
                'name' => ['tr' => $tr, 'en' => $en, 'bg' => $bg, 'el' => $el],
                'is_active' => true,
                'position' => $index,
            ]);
        }
    }

    private function attributes(): void
    {
        $definitions = [
            [
                'slug' => 'malzeme', 'type' => 'select', 'is_variant' => false,
                'name' => ['tr' => 'Malzeme', 'en' => 'Material', 'bg' => 'Материал', 'el' => 'Υλικό'],
                'values' => [
                    ['masif-ahsap', 'Masif Ahşap', 'Solid Wood', 'Масивно дърво', 'Μασίφ Ξύλο', null],
                    ['ceviz-kaplama', 'Ceviz Kaplama', 'Walnut Veneer', 'Орехов фурнир', 'Καπλαμάς Καρυδιάς', null],
                    ['mese', 'Meşe', 'Oak', 'Дъб', 'Δρυς', null],
                    ['metal', 'Metal', 'Metal', 'Метал', 'Μέταλλο', null],
                    ['kadife', 'Kadife', 'Velvet', 'Кадифе', 'Βελούδο', null],
                    ['keten', 'Keten', 'Linen', 'Лен', 'Λινό', null],
                    ['deri', 'Hakiki Deri', 'Genuine Leather', 'Естествена кожа', 'Δέρμα', null],
                    ['rattan', 'Rattan', 'Rattan', 'Ратан', 'Ρατάν', null],
                ],
            ],
            [
                'slug' => 'renk', 'type' => 'color', 'is_variant' => true,
                'name' => ['tr' => 'Renk', 'en' => 'Colour', 'bg' => 'Цвят', 'el' => 'Χρώμα'],
                'values' => [
                    ['ceviz', 'Ceviz', 'Walnut', 'Орех', 'Καρυδί', '#6b4a2f'],
                    ['dogal-mese', 'Doğal Meşe', 'Natural Oak', 'Натурален дъб', 'Φυσική Δρυς', '#c9a37a'],
                    ['antrasit', 'Antrasit', 'Anthracite', 'Антрацит', 'Ανθρακί', '#3a3d42'],
                    ['krem', 'Krem', 'Cream', 'Кремав', 'Κρεμ', '#e8ded0'],
                    ['petrol-yesili', 'Petrol Yeşili', 'Teal', 'Петролено зелено', 'Πετρόλ', '#26635c'],
                    ['tarcin', 'Tarçın', 'Cinnamon', 'Канела', 'Κανέλα', '#a8623a'],
                    ['gri', 'Gri', 'Grey', 'Сив', 'Γκρι', '#9aa0a6'],
                    ['siyah', 'Siyah', 'Black', 'Черен', 'Μαύρο', '#1d1d1d'],
                ],
            ],
            [
                // Olculer urun tipine gore degisir (koltuk 3'lu, hali 160x230…);
                // hepsi tek nitelik altinda toplanir, her urun kendi alt kumesini
                // kullanir. Magaza filtresinde gosterilmez: farkli urun tiplerinin
                // olculeri yan yana anlamsiz olurdu.
                'slug' => 'olcu', 'type' => 'select', 'is_variant' => true, 'is_filterable' => false,
                'name' => ['tr' => 'Ölçü', 'en' => 'Size', 'bg' => 'Размер', 'el' => 'Μέγεθος'],
                'values' => [
                    ['2li', '2’li', '2-Seater', '2-местен', '2 θέσεων', null],
                    ['3lu', '3’lü', '3-Seater', '3-местен', '3 θέσεων', null],
                    ['kose', 'Köşe', 'Corner', 'Ъглов', 'Γωνιακό', null],
                    ['140x190', '140×190 cm', '140×190 cm', '140×190 см', '140×190 εκ.', null],
                    ['160x200', '160×200 cm', '160×200 cm', '160×200 см', '160×200 εκ.', null],
                    ['180x200', '180×200 cm', '180×200 cm', '180×200 см', '180×200 εκ.', null],
                    ['120x180', '120×180 cm', '120×180 cm', '120×180 см', '120×180 εκ.', null],
                    ['160x230', '160×230 cm', '160×230 cm', '160×230 см', '160×230 εκ.', null],
                    ['200x290', '200×290 cm', '200×290 cm', '200×290 см', '200×290 εκ.', null],
                    ['140x260', '140×260 cm', '140×260 cm', '140×260 см', '140×260 εκ.', null],
                    ['200x260', '200×260 cm', '200×260 cm', '200×260 см', '200×260 εκ.', null],
                    ['40x40', '40×40 cm', '40×40 cm', '40×40 см', '40×40 εκ.', null],
                    ['45x45', '45×45 cm', '45×45 cm', '45×45 см', '45×45 εκ.', null],
                    ['50x50', '50×50 cm', '50×50 cm', '50×50 см', '50×50 εκ.', null],
                    ['120cm', '120 cm', '120 cm', '120 см', '120 εκ.', null],
                    ['140cm', '140 cm', '140 cm', '140 см', '140 εκ.', null],
                    ['160cm', '160 cm', '160 cm', '160 см', '160 εκ.', null],
                ],
            ],
            [
                'slug' => 'stil', 'type' => 'select', 'is_variant' => false,
                'name' => ['tr' => 'Stil', 'en' => 'Style', 'bg' => 'Стил', 'el' => 'Στυλ'],
                'values' => [
                    ['modern', 'Modern', 'Modern', 'Модерен', 'Μοντέρνο', null],
                    ['iskandinav', 'İskandinav', 'Scandinavian', 'Скандинавски', 'Σκανδιναβικό', null],
                    ['rustik', 'Rustik', 'Rustic', 'Рустик', 'Ρουστίκ', null],
                    ['klasik', 'Klasik', 'Classic', 'Класически', 'Κλασικό', null],
                    ['minimal', 'Minimal', 'Minimal', 'Минимален', 'Μινιμαλιστικό', null],
                ],
            ],
        ];

        foreach ($definitions as $index => $definition) {
            $attribute = Attribute::updateOrCreate(['slug' => $definition['slug']], [
                'name' => $definition['name'],
                'type' => $definition['type'],
                'is_filterable' => $definition['is_filterable'] ?? true,
                'is_variant' => $definition['is_variant'],
                'position' => $index,
            ]);

            foreach ($definition['values'] as $valueIndex => [$slug, $tr, $en, $bg, $el, $hex]) {
                AttributeValue::updateOrCreate(
                    ['attribute_id' => $attribute->id, 'slug' => $slug],
                    [
                        'name' => ['tr' => $tr, 'en' => $en, 'bg' => $bg, 'el' => $el],
                        'color_hex' => $hex,
                        'position' => $valueIndex,
                    ]
                );
            }
        }
    }
}
