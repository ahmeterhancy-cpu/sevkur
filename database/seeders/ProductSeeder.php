<?php

namespace Database\Seeders;

use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\DemoImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo urun katalogu.
 *
 * Tum degerler deterministiktir (rastgelelik yok) — boylece tekrar
 * calistirildiginda ayni katalog olusur ve testler kirilmaz.
 */
class ProductSeeder extends Seeder
{
    /** Model adlari Edirne cografyasindan; her dilde ayni yazilir. */
    private const MODELS = [
        'Meriç', 'Selimiye', 'Karaağaç', 'Sarayiçi', 'Kaleiçi', 'Havsa',
        'Enez', 'Keşan', 'İpsala', 'Uzunköprü', 'Lalapaşa', 'Tunca',
        'Saros', 'Ergene', 'Süloğlu', 'Trakya',
    ];

    /**
     * [kategori slug, cizim, tur adlari, taban fiyat, teslim gunu, olcu]
     */
    private const BLUEPRINT = [
        ['koltuk-takimlari', 'sofa', ['Koltuk Takımı', 'Sofa Set', 'Диван', 'Καναπές'], 38900, 5, '240 × 96 × 82 cm'],
        ['berjer-ve-koltuk', 'armchair', ['Berjer', 'Armchair', 'Кресло', 'Πολυθρόνα'], 12400, 4, '78 × 82 × 96 cm'],
        ['sehpalar', 'coffee_table', ['Orta Sehpa', 'Coffee Table', 'Холна маса', 'Τραπεζάκι Σαλονιού'], 6450, 3, '110 × 60 × 42 cm'],
        ['tv-uniteleri', 'tv_unit', ['TV Ünitesi', 'TV Unit', 'ТВ шкаф', 'Έπιπλο TV'], 14900, 5, '180 × 40 × 48 cm'],
        ['karyolalar', 'bed', ['Karyola', 'Bed', 'Легло', 'Κρεβάτι'], 27500, 6, '160 × 200 cm'],
        ['gardiroplar', 'wardrobe', ['Gardırop', 'Wardrobe', 'Гардероб', 'Ντουλάπα'], 31900, 7, '200 × 60 × 220 cm'],
        ['komodinler', 'nightstand', ['Komodin', 'Nightstand', 'Нощно шкафче', 'Κομοδίνο'], 4250, 3, '48 × 40 × 55 cm'],
        ['sifonyerler', 'dresser', ['Şifonyer', 'Dresser', 'Скрин', 'Συρταριέρα'], 11800, 5, '100 × 45 × 95 cm'],
        ['yemek-masalari', 'dining_table', ['Yemek Masası', 'Dining Table', 'Трапезна маса', 'Τραπέζι Τραπεζαρίας'], 18700, 5, '180 × 90 × 76 cm'],
        ['sandalyeler', 'chair', ['Sandalye', 'Chair', 'Стол', 'Καρέκλα'], 2950, 3, '46 × 52 × 88 cm'],
        ['konsollar', 'console', ['Konsol', 'Console', 'Конзола', 'Κονσόλα'], 8900, 4, '120 × 38 × 80 cm'],
        ['vitrinler', 'display_cabinet', ['Vitrin', 'Display Cabinet', 'Витрина', 'Βιτρίνα'], 16400, 6, '100 × 42 × 190 cm'],
        ['calisma-masalari', 'desk', ['Çalışma Masası', 'Desk', 'Бюро', 'Γραφείο'], 9600, 4, '140 × 65 × 75 cm'],
        ['ofis-koltuklari', 'office_chair', ['Ofis Koltuğu', 'Office Chair', 'Офис стол', 'Καρέκλα Γραφείου'], 7250, 3, '62 × 62 × 110 cm'],
        ['kitapliklar', 'bookcase', ['Kitaplık', 'Bookcase', 'Библиотека', 'Βιβλιοθήκη'], 8400, 5, '90 × 32 × 190 cm'],
        ['zemin-lambalari', 'lamp', ['Zemin Lambası', 'Floor Lamp', 'Лампион', 'Φωτιστικό Δαπέδου'], 3450, 2, '40 × 40 × 155 cm'],
        ['avizeler', 'chandelier', ['Avize', 'Chandelier', 'Полилей', 'Πολυέλαιος'], 5900, 3, '60 × 60 cm'],
        ['halilar', 'rug', ['Halı', 'Rug', 'Килим', 'Χαλί'], 6800, 3, '200 × 290 cm'],
        ['kirlentler', 'cushion', ['Kırlent', 'Cushion', 'Възглавница', 'Μαξιλάρι'], 690, 2, '45 × 45 cm'],
        ['perdeler', 'curtain', ['Perde', 'Curtain', 'Завеса', 'Κουρτίνα'], 2400, 3, '140 × 260 cm'],
        ['aynalar', 'mirror', ['Ayna', 'Mirror', 'Огледало', 'Καθρέφτης'], 4100, 3, '70 × 110 cm'],
        ['tablolar', 'artwork', ['Tablo', 'Wall Art', 'Картина', 'Πίνακας'], 1750, 2, '60 × 80 cm'],
        ['raflar', 'shelf', ['Raf', 'Shelf', 'Рафт', 'Ράφι'], 2150, 2, '80 × 22 cm'],
    ];

    private const MATERIAL_BY_SHAPE = [
        'sofa' => ['keten', 'kadife'],
        'armchair' => ['kadife', 'deri'],
        'chair' => ['masif-ahsap', 'keten'],
        'office_chair' => ['deri', 'metal'],
        'coffee_table' => ['ceviz-kaplama', 'masif-ahsap'],
        'dining_table' => ['mese', 'masif-ahsap'],
        'tv_unit' => ['ceviz-kaplama', 'metal'],
        'bed' => ['masif-ahsap', 'keten'],
        'wardrobe' => ['ceviz-kaplama', 'mese'],
        'nightstand' => ['masif-ahsap', 'ceviz-kaplama'],
        'dresser' => ['mese', 'masif-ahsap'],
        'console' => ['ceviz-kaplama', 'metal'],
        'display_cabinet' => ['mese', 'metal'],
        'desk' => ['mese', 'metal'],
        'bookcase' => ['masif-ahsap', 'metal'],
        'lamp' => ['metal', 'rattan'],
        'chandelier' => ['metal', 'rattan'],
        'rug' => ['keten', 'rattan'],
        'cushion' => ['kadife', 'keten'],
        'curtain' => ['keten', 'kadife'],
        'mirror' => ['masif-ahsap', 'metal'],
        'artwork' => ['masif-ahsap', 'metal'],
        'shelf' => ['masif-ahsap', 'metal'],
    ];

    public function run(): void
    {
        DemoImages::ensureDirectory();

        $brands = Brand::orderBy('position')->get();
        $colorValues = AttributeValue::whereHas('attribute', fn ($q) => $q->where('slug', 'renk'))->get()->keyBy('slug');
        $materialValues = AttributeValue::whereHas('attribute', fn ($q) => $q->where('slug', 'malzeme'))->get()->keyBy('slug');
        $styleValues = AttributeValue::whereHas('attribute', fn ($q) => $q->where('slug', 'stil'))->get()->values();

        $index = 0;

        foreach (self::BLUEPRINT as $blueIndex => [$categorySlug, $shape, $typeNames, $basePrice, $deliveryDays, $dimensions]) {
            $category = Category::where('slug', $categorySlug)->first();

            if (! $category) {
                continue;
            }

            // Her kategoride 2 urun (toplam 46)
            for ($n = 0; $n < 2; $n++) {
                $model = self::MODELS[($blueIndex * 2 + $n) % count(self::MODELS)];
                $slug = Str::slug($model.'-'.$typeNames[1].'-'.($blueIndex * 2 + $n + 1));

                $priceStep = 1 + (($index % 5) * 0.06);
                $price = round($basePrice * $priceStep / 10) * 10;

                // Her 3. urun indirimli
                $onSale = $index % 3 === 0;
                $compareAt = $onSale ? round($price * 1.18 / 10) * 10 : null;

                $materialSlug = self::MATERIAL_BY_SHAPE[$shape][$n % 2] ?? 'masif-ahsap';
                $colorSlugs = ['ceviz', 'dogal-mese', 'antrasit', 'krem', 'petrol-yesili', 'tarcin', 'gri', 'siyah'];
                $colorSlug = $colorSlugs[$index % count($colorSlugs)];

                $names = [
                    'tr' => $model.' '.$typeNames[0],
                    'en' => $model.' '.$typeNames[1],
                    'bg' => $model.' '.$typeNames[2],
                    'el' => $model.' '.$typeNames[3],
                ];

                $product = Product::updateOrCreate(['slug' => $slug], [
                    'category_id' => $category->id,
                    'brand_id' => $brands[$index % max(1, $brands->count())]->id ?? null,
                    'name' => $names,
                    'sku' => 'SVK-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'short_description' => $this->shortDescription($typeNames, $materialValues[$materialSlug] ?? null),
                    'description' => $this->description($typeNames, $materialValues[$materialSlug] ?? null, $dimensions),
                    'specs' => $this->specs($dimensions, $materialValues[$materialSlug] ?? null, $deliveryDays),
                    'price' => $price,
                    'compare_at_price' => $compareAt,
                    'cost_price' => round($price * 0.62, 2),
                    'manage_stock' => true,
                    'stock' => 6 + (($index * 3) % 22),
                    'stock_alert' => 3,
                    'sold_count' => 8 + (($index * 7) % 60),
                    'sale_target' => $onSale ? 80 + (($index * 5) % 60) : null,
                    'delivery_days' => $deliveryDays,
                    'dimensions' => $dimensions,
                    'cover' => DemoImages::product($shape, $slug, 0),
                    'hover_image' => DemoImages::product($shape, $slug, 1),
                    'is_active' => true,
                    'is_featured' => $index % 4 === 0,
                    'is_new' => $index % 5 === 0,
                    'is_trending' => $index % 7 === 0,
                    'sale_ends_at' => $onSale ? now()->addDays(3 + ($index % 9))->setTime(23, 59) : null,
                    'rating_avg' => 0,
                    'rating_count' => 0,
                    'position' => $index,
                    'published_at' => now()->subDays(120 - $index),
                ]);

                // Ek galeri gorselleri
                $product->images()->delete();
                foreach ([2, 3] as $variantIndex) {
                    $product->images()->create([
                        'path' => DemoImages::product($shape, $slug, $variantIndex),
                        'position' => $variantIndex,
                    ]);
                }

                // Ozellikler
                $ids = array_filter([
                    $materialValues[$materialSlug]->id ?? null,
                    $colorValues[$colorSlug]->id ?? null,
                    $styleValues[$index % max(1, $styleValues->count())]->id ?? null,
                ]);
                $product->attributeValues()->sync($ids);

                // Renk varyantlari (koltuk, berjer, sandalye, kirlent)
                if (in_array($shape, ['sofa', 'armchair', 'chair', 'cushion'], true)) {
                    $product->variants()->delete();

                    foreach ([0, 1, 2] as $variantPosition) {
                        $variantColor = $colorSlugs[($index + $variantPosition) % count($colorSlugs)];
                        $value = $colorValues[$variantColor] ?? null;

                        if (! $value) {
                            continue;
                        }

                        $product->variants()->create([
                            'name' => $value->translations('name'),
                            'sku' => $product->sku.'-'.strtoupper(substr($variantColor, 0, 3)),
                            'price' => $variantPosition === 0 ? null : $price + ($variantPosition * 350),
                            'stock' => 3 + (($index + $variantPosition * 4) % 12),
                            'options' => ['renk' => $variantColor],
                            'position' => $variantPosition,
                        ]);
                    }
                }

                $index++;
            }
        }

        $this->linkRelated();
    }

    /** Ayni kategorideki urunleri birbirine bagla. */
    private function linkRelated(): void
    {
        foreach (Category::whereNotNull('parent_id')->with('products')->get() as $category) {
            $products = $category->products;

            foreach ($products as $product) {
                $related = Product::where('category_id', $category->parent_id)
                    ->orWhereIn('category_id', Category::where('parent_id', $category->parent_id)->pluck('id'))
                    ->whereKeyNot($product->id)
                    ->orderBy('id')
                    ->take(6)
                    ->pluck('id');

                $product->related()->sync($related);
            }
        }
    }

    /** @return array<string, string> */
    private function shortDescription(array $typeNames, ?AttributeValue $material): array
    {
        $m = fn (string $locale) => $material ? mb_strtolower($material->t('name', $locale)) : '';

        return [
            'tr' => $typeNames[0].', '.$m('tr').' dokusu ve sade çizgisiyle her yaşam alanına uyum sağlar.',
            'en' => 'A '.mb_strtolower($typeNames[1]).' with a '.$m('en').' finish and clean lines that suits any living space.',
            'bg' => 'Мебел с '.$m('bg').' и изчистени линии, която пасва на всяко пространство.',
            'el' => 'Κομμάτι με '.$m('el').' και καθαρές γραμμές που ταιριάζει σε κάθε χώρο.',
        ];
    }

    /** @return array<string, string> */
    private function description(array $typeNames, ?AttributeValue $material, string $dimensions): array
    {
        $m = fn (string $locale) => $material ? mb_strtolower($material->t('name', $locale)) : '';

        return [
            'tr' => "Atölyemizde tek tek üretilen bu {$typeNames[0]}, ".$m('tr').' kullanılarak hazırlandı. '
                ."Gövde bağlantıları zıvanalı geçmeyle güçlendirildi; yüzey su bazlı, kokusuz vernikle korundu.\n\n"
                ."Ölçüler: {$dimensions}. Ürün montajlı teslim edilir; nakliye sırasında köşe koruma köpüğü ve çift katlı koli kullanılır.\n\n"
                .'İki yıl üretim hatalarına karşı garantilidir. Nemli bezle silinerek temizlenmesi yeterlidir; çözücü içeren temizleyicilerden kaçının.',
            'en' => "Each {$typeNames[1]} is built one at a time in our own workshop using ".$m('en').'. '
                ."Frame joints are reinforced with mortise-and-tenon work and the surface is protected with a water-based, odourless lacquer.\n\n"
                ."Dimensions: {$dimensions}. Delivered assembled, packed with corner protection foam and double-wall cartons.\n\n"
                .'Covered by a two-year warranty against manufacturing defects. Wipe with a damp cloth; avoid solvent-based cleaners.',
            'bg' => 'Всеки екземпляр се изработва поединично в собствената ни работилница от '.$m('bg').'. '
                ."Сглобките на рамката са подсилени, а повърхността е защитена с лак на водна основа без мирис.\n\n"
                ."Размери: {$dimensions}. Доставя се сглобен, с ъглова защита и двуслоен кашон.\n\n"
                .'Две години гаранция срещу производствени дефекти. Почиства се с влажна кърпа; избягвайте препарати с разтворители.',
            'el' => 'Κάθε κομμάτι κατασκευάζεται ξεχωριστά στο εργαστήριό μας από '.$m('el').'. '
                ."Οι ενώσεις του σκελετού είναι ενισχυμένες και η επιφάνεια προστατεύεται με άοσμο βερνίκι νερού.\n\n"
                ."Διαστάσεις: {$dimensions}. Παραδίδεται συναρμολογημένο, με προστασία γωνιών και διπλό χαρτοκιβώτιο.\n\n"
                .'Δύο χρόνια εγγύηση για κατασκευαστικά ελαττώματα. Καθαρίζεται με υγρό πανί· αποφύγετε διαλύτες.',
        ];
    }

    /** @return array<int, array{label: array<string,string>, value: array<string,string>}> */
    private function specs(string $dimensions, ?AttributeValue $material, int $deliveryDays): array
    {
        return [
            [
                'label' => ['tr' => 'Ölçüler', 'en' => 'Dimensions', 'bg' => 'Размери', 'el' => 'Διαστάσεις'],
                'value' => ['tr' => $dimensions, 'en' => $dimensions, 'bg' => $dimensions, 'el' => $dimensions],
            ],
            [
                'label' => ['tr' => 'Malzeme', 'en' => 'Material', 'bg' => 'Материал', 'el' => 'Υλικό'],
                'value' => [
                    'tr' => $material?->t('name', 'tr') ?? '-',
                    'en' => $material?->t('name', 'en') ?? '-',
                    'bg' => $material?->t('name', 'bg') ?? '-',
                    'el' => $material?->t('name', 'el') ?? '-',
                ],
            ],
            [
                'label' => ['tr' => 'Üretim', 'en' => 'Production', 'bg' => 'Производство', 'el' => 'Παραγωγή'],
                'value' => ['tr' => 'Türkiye — Edirne atölyesi', 'en' => 'Türkiye — Edirne workshop', 'bg' => 'Турция — работилница в Одрин', 'el' => 'Τουρκία — εργαστήριο Αδριανούπολης'],
            ],
            [
                'label' => ['tr' => 'Garanti', 'en' => 'Warranty', 'bg' => 'Гаранция', 'el' => 'Εγγύηση'],
                'value' => ['tr' => '2 yıl', 'en' => '2 years', 'bg' => '2 години', 'el' => '2 χρόνια'],
            ],
            [
                'label' => ['tr' => 'Teslim süresi', 'en' => 'Lead time', 'bg' => 'Срок за доставка', 'el' => 'Χρόνος παράδοσης'],
                'value' => [
                    'tr' => $deliveryDays.' iş günü',
                    'en' => $deliveryDays.' business days',
                    'bg' => $deliveryDays.' работни дни',
                    'el' => $deliveryDays.' εργάσιμες ημέρες',
                ],
            ],
        ];
    }
}
