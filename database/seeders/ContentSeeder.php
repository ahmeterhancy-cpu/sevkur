<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Banner;
use App\Models\Faq;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Slider;
use App\Support\DemoImages;
use App\Support\DemoPhotos;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->sliders();
        $this->banners();
        $this->announcements();
        $this->menu();
        $this->blog();
        $this->pages();
        $this->photoCredits();
        $this->faqs();
    }

    private function sliders(): void
    {
        $rows = [
            [
                'key' => 'slider-1',
                'badge' => ['tr' => 'Yeni Sezon', 'en' => 'New Season', 'bg' => 'Нов сезон', 'el' => 'Νέα Σεζόν'],
                'title' => [
                    'tr' => 'Evinize karakter katan sade parçalar',
                    'en' => 'Simple pieces that give your home character',
                    'bg' => 'Прости мебели, които дават характер на дома ви',
                    'el' => 'Απλά κομμάτια που δίνουν χαρακτήρα στο σπίτι σας',
                ],
                'subtitle' => [
                    'tr' => 'Masif ahşap gövde, doğal kumaş, kendi atölyemizde üretim.',
                    'en' => 'Solid wood frames, natural fabrics, made in our own workshop.',
                    'bg' => 'Масивна дървесина, естествени тъкани, собствено производство.',
                    'el' => 'Μασίφ σκελετός, φυσικά υφάσματα, δική μας παραγωγή.',
                ],
                'button' => ['tr' => 'Koleksiyonu keşfet', 'en' => 'Shop the collection', 'bg' => 'Разгледай колекцията', 'el' => 'Δείτε τη συλλογή'],
                'tone' => '#efe7dc',
            ],
            [
                'key' => 'slider-2',
                'badge' => ['tr' => '%20’ye varan indirim', 'en' => 'Up to 20% off', 'bg' => 'До 20% отстъпка', 'el' => 'Έως 20% έκπτωση'],
                'title' => [
                    'tr' => 'Oturma odası koleksiyonunda sonbahar fırsatı',
                    'en' => 'Autumn deal on the living room collection',
                    'bg' => 'Есенна оферта за колекцията за хол',
                    'el' => 'Φθινοπωρινή προσφορά στη συλλογή καθιστικού',
                ],
                'subtitle' => [
                    'tr' => 'Seçili koltuk takımları ve berjerlerde geçerlidir.',
                    'en' => 'Valid on selected sofas and armchairs.',
                    'bg' => 'Важи за избрани дивани и кресла.',
                    'el' => 'Ισχύει σε επιλεγμένους καναπέδες και πολυθρόνες.',
                ],
                'button' => ['tr' => 'Fırsatları gör', 'en' => 'See the deals', 'bg' => 'Виж офертите', 'el' => 'Δείτε τις προσφορές'],
                'tone' => '#e9ece8',
            ],
            [
                'key' => 'slider-3',
                'badge' => ['tr' => 'Atölye', 'en' => 'Workshop', 'bg' => 'Работилница', 'el' => 'Εργαστήριο'],
                'title' => [
                    'tr' => 'Ölçüye özel üretim, tek tek kontrol',
                    'en' => 'Made to measure, checked one by one',
                    'bg' => 'Изработка по мярка, проверена една по една',
                    'el' => 'Κατασκευή στα μέτρα σας, ελεγμένη ένα προς ένα',
                ],
                'subtitle' => [
                    'tr' => 'Ölçülerinizi paylaşın, atölyemiz size özel üretsin.',
                    'en' => 'Share your dimensions and our workshop will build it for you.',
                    'bg' => 'Споделете размерите си и ще изработим мебелта за вас.',
                    'el' => 'Στείλτε μας τις διαστάσεις σας και το φτιάχνουμε για εσάς.',
                ],
                'button' => ['tr' => 'Bize ulaşın', 'en' => 'Contact us', 'bg' => 'Свържете се с нас', 'el' => 'Επικοινωνήστε'],
                'tone' => '#f1ece5',
            ],
        ];

        foreach ($rows as $index => $row) {
            // Kompozisyon varsa (tools/build-demo-scenes.php) onu kullan,
            // yoksa soyut SVG panele don.
            $image = DemoPhotos::scene($row['key'])
                ?? DemoImages::banner($row['key'], '', 1600, 760, $row['tone']);

            Slider::updateOrCreate(
                ['image' => $image],
                [
                    'badge' => $row['badge'],
                    'title' => $row['title'],
                    'subtitle' => $row['subtitle'],
                    'button_text' => $row['button'],
                    'link' => $index === 2 ? localized_route('contact', [], 'tr', false) : localized_route('shop', [], 'tr', false),
                    'text_align' => 'left',
                    'text_color' => '#141414',
                    'position' => $index,
                    'is_active' => true,
                ]
            );
        }
    }

    private function banners(): void
    {
        $rows = [
            ['home_mid', 'banner-koltuk', ['tr' => 'Köşe Koltuklar', 'en' => 'Sectional Sofas', 'bg' => 'Ъглови дивани', 'el' => 'Γωνιακοί Καναπέδες'], ['tr' => '%25’e varan indirim', 'en' => 'Up to 25% off', 'bg' => 'До 25% отстъпка', 'el' => 'Έως 25% έκπτωση'], 'koltuk-takimlari', '#e8e4dc'],
            ['home_mid', 'banner-masa', ['tr' => 'Yemek Masaları', 'en' => 'Dining Tables', 'bg' => 'Трапезни маси', 'el' => 'Τραπέζια'], ['tr' => 'Yeni ölçüler', 'en' => 'New sizes', 'bg' => 'Нови размери', 'el' => 'Νέα μεγέθη'], 'yemek-masalari', '#e4e8e6'],
            ['home_mid', 'banner-aydinlatma', ['tr' => 'Aydınlatma', 'en' => 'Lighting', 'bg' => 'Осветление', 'el' => 'Φωτισμός'], ['tr' => 'Sıcak ışık serisi', 'en' => 'Warm light series', 'bg' => 'Серия топла светлина', 'el' => 'Σειρά θερμού φωτός'], 'aydinlatma', '#efe9df'],
            ['home_wide', 'banner-genis', ['tr' => 'Daha fazlasına yer açın', 'en' => 'Make room for more', 'bg' => 'Направете място за повече', 'el' => 'Κάντε χώρο για περισσότερα'], ['tr' => 'Oturma Odası', 'en' => 'Living Room', 'bg' => 'Хол', 'el' => 'Καθιστικό'], 'oturma-odasi', '#e6e2da'],
        ];

        foreach ($rows as $index => [$placement, $key, $title, $subtitle, $categorySlug, $tone]) {
            $isWide = $placement === 'home_wide';

            $image = DemoPhotos::scene($key)
                ?? DemoImages::banner($key, '', $isWide ? 1600 : 900, $isWide ? 620 : 680, $tone);

            Banner::updateOrCreate(
                ['image' => $image],
                [
                    'placement' => $placement,
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'button_text' => ['tr' => 'İncele', 'en' => 'Shop now', 'bg' => 'Разгледай', 'el' => 'Δείτε'],
                    'link' => localized_route('category', ['category' => $categorySlug], 'tr', false),
                    'text_color' => '#141414',
                    'position' => $index,
                    'is_active' => true,
                ]
            );
        }
    }

    private function announcements(): void
    {
        $rows = [
            ['tr' => '15.000 ₺ ve üzeri siparişlerde kargo bedava', 'en' => 'Free shipping over 15,000 ₺', 'bg' => 'Безплатна доставка над 15 000 ₺', 'el' => 'Δωρεάν αποστολή άνω των 15.000 ₺'],
            ['tr' => 'Tüm ürünlerde 2 yıl üretim garantisi', 'en' => '2-year manufacturing warranty on everything', 'bg' => '2 години гаранция за всички продукти', 'el' => '2 χρόνια εγγύηση σε όλα τα προϊόντα'],
            ['tr' => '14 gün içinde koşulsuz iade', 'en' => 'No-questions returns within 14 days', 'bg' => 'Връщане до 14 дни без въпроси', 'el' => 'Επιστροφές εντός 14 ημερών'],
            ['tr' => 'Ölçüye özel üretim için bize yazın', 'en' => 'Write to us for made-to-measure pieces', 'bg' => 'Пишете ни за изработка по мярка', 'el' => 'Γράψτε μας για κατασκευή στα μέτρα σας'],
        ];

        Announcement::query()->delete();

        foreach ($rows as $index => $text) {
            Announcement::create([
                'text' => $text,
                'position' => $index,
                'is_active' => true,
            ]);
        }
    }

    private function menu(): void
    {
        MenuItem::query()->delete();

        MenuItem::create([
            'location' => 'header',
            'label' => ['tr' => 'Hakkımızda', 'en' => 'About us', 'bg' => 'За нас', 'el' => 'Σχετικά'],
            'type' => 'page',
            'target_slug' => 'hakkimizda',
            'position' => 0,
            'is_active' => true,
        ]);

        MenuItem::create([
            'location' => 'header',
            'label' => ['tr' => 'Markalar', 'en' => 'Brands', 'bg' => 'Марки', 'el' => 'Μάρκες'],
            'type' => 'custom',
            'url' => localized_route('brands', [], 'tr', false),
            'position' => 1,
            'is_active' => true,
        ]);
    }

    private function blog(): void
    {
        $categories = [
            ['dekorasyon', 'Dekorasyon', 'Decoration', 'Декорация', 'Διακόσμηση'],
            ['ic-mimari', 'İç Mimari', 'Interior Design', 'Интериорен дизайн', 'Εσωτερική Διακόσμηση'],
            ['bakim', 'Bakım', 'Care', 'Поддръжка', 'Φροντίδα'],
        ];

        foreach ($categories as $index => [$slug, $tr, $en, $bg, $el]) {
            PostCategory::updateOrCreate(['slug' => $slug], [
                'name' => ['tr' => $tr, 'en' => $en, 'bg' => $bg, 'el' => $el],
                'position' => $index,
                'is_active' => true,
            ]);
        }

        $posts = [
            [
                'slug' => 'kucuk-oturma-odasini-buyuk-gosteren-7-detay',
                'category' => 'ic-mimari',
                'title' => [
                    'tr' => 'Küçük oturma odasını büyük gösteren 7 detay',
                    'en' => 'Seven details that make a small living room feel bigger',
                    'bg' => 'Седем детайла, които уголемяват малкия хол',
                    'el' => 'Επτά λεπτομέρειες που μεγαλώνουν ένα μικρό σαλόνι',
                ],
                'excerpt' => [
                    'tr' => 'Metrekare eklemeden ferahlık kazanmanın yolu, mobilyanın yerleşiminden geçiyor.',
                    'en' => 'You can gain a sense of space without adding square metres — it starts with layout.',
                    'bg' => 'Може да спечелите усещане за простор без повече квадратни метри.',
                    'el' => 'Μπορείτε να κερδίσετε αίσθηση χώρου χωρίς επιπλέον τετραγωνικά.',
                ],
            ],
            [
                'slug' => 'masif-ahsap-mobilya-bakimi',
                'category' => 'bakim',
                'title' => [
                    'tr' => 'Masif ahşap mobilya nasıl bakılır?',
                    'en' => 'How to care for solid wood furniture',
                    'bg' => 'Как да се грижим за масивна дървесина',
                    'el' => 'Πώς να φροντίζετε έπιπλα από μασίφ ξύλο',
                ],
                'excerpt' => [
                    'tr' => 'Doğru nem, doğru bez ve doğru ürün: ahşabı yıllarca ilk günkü gibi tutmanın üç kuralı.',
                    'en' => 'The right humidity, the right cloth, the right product — three rules for lasting wood.',
                    'bg' => 'Правилната влажност, кърпа и препарат — три правила за дълготрайно дърво.',
                    'el' => 'Σωστή υγρασία, σωστό πανί, σωστό προϊόν — τρεις κανόνες.',
                ],
            ],
            [
                'slug' => 'notr-tonlarla-sicak-bir-ev',
                'category' => 'dekorasyon',
                'title' => [
                    'tr' => 'Nötr tonlarla sıcak bir ev kurmak',
                    'en' => 'Building a warm home with neutral tones',
                    'bg' => 'Топъл дом с неутрални тонове',
                    'el' => 'Ένα ζεστό σπίτι με ουδέτερους τόνους',
                ],
                'excerpt' => [
                    'tr' => 'Bej ve gri sıkıcı değildir — doku, kontrast ve ışıkla nasıl canlandırılır?',
                    'en' => 'Beige and grey are not boring — how texture, contrast and light bring them alive.',
                    'bg' => 'Бежово и сиво не са скучни — текстура, контраст и светлина.',
                    'el' => 'Το μπεζ και το γκρι δεν είναι βαρετά — υφή, αντίθεση και φως.',
                ],
            ],
            [
                'slug' => 'yemek-masasi-secerken',
                'category' => 'ic-mimari',
                'title' => [
                    'tr' => 'Yemek masası seçerken bakılacak 5 ölçü',
                    'en' => 'Five measurements to check when choosing a dining table',
                    'bg' => 'Пет размера при избор на трапезна маса',
                    'el' => 'Πέντε μετρήσεις για την επιλογή τραπεζιού',
                ],
                'excerpt' => [
                    'tr' => 'Kaç kişi oturacak, sandalye ne kadar geri çekilecek, geçiş için kaç santim gerekir?',
                    'en' => 'How many seats, how far chairs pull back, and how much walkway you need.',
                    'bg' => 'Колко места, колко се издърпват столовете и колко проход е нужен.',
                    'el' => 'Πόσες θέσεις, πόσο τραβιούνται οι καρέκλες και πόσος διάδρομος χρειάζεται.',
                ],
            ],
            [
                'slug' => 'kadife-mi-keten-mi',
                'category' => 'dekorasyon',
                'title' => [
                    'tr' => 'Kadife mi, keten mi? Kumaş seçim rehberi',
                    'en' => 'Velvet or linen? A fabric guide',
                    'bg' => 'Кадифе или лен? Ръководство за тъкани',
                    'el' => 'Βελούδο ή λινό; Οδηγός υφασμάτων',
                ],
                'excerpt' => [
                    'tr' => 'Evde çocuk mu var, evcil hayvan mı? Kumaş kararını günlük hayat belirlemeli.',
                    'en' => 'Kids at home? Pets? Everyday life should drive the fabric decision.',
                    'bg' => 'Деца? Домашни любимци? Ежедневието определя избора на плат.',
                    'el' => 'Παιδιά; Κατοικίδια; Η καθημερινότητα καθορίζει το ύφασμα.',
                ],
            ],
            [
                'slug' => 'yatak-odasinda-isik-plani',
                'category' => 'ic-mimari',
                'title' => [
                    'tr' => 'Yatak odasında üç katmanlı ışık planı',
                    'en' => 'A three-layer lighting plan for the bedroom',
                    'bg' => 'Трислоен план за осветление в спалнята',
                    'el' => 'Σχέδιο φωτισμού τριών επιπέδων στο υπνοδωμάτιο',
                ],
                'excerpt' => [
                    'tr' => 'Genel, görev ve vurgu aydınlatmasını aynı odada nasıl dengelersiniz?',
                    'en' => 'How to balance ambient, task and accent lighting in one room.',
                    'bg' => 'Как да балансирате общо, работно и акцентно осветление.',
                    'el' => 'Πώς να ισορροπήσετε γενικό, λειτουργικό και διακοσμητικό φωτισμό.',
                ],
            ],
        ];

        foreach ($posts as $index => $post) {
            $category = PostCategory::where('slug', $post['category'])->first();

            Post::updateOrCreate(['slug' => $post['slug']], [
                'post_category_id' => $category?->id,
                'title' => $post['title'],
                'excerpt' => $post['excerpt'],
                'body' => $this->postBody($post['title'], $post['excerpt']),
                'cover' => DemoPhotos::scene('blog-'.$post['slug']) ?? DemoImages::post($post['slug']),
                'is_active' => true,
                'is_featured' => $index < 2,
                'published_at' => now()->subDays(($index + 1) * 9),
            ]);
        }
    }

    /** @return array<string, string> */
    private function postBody(array $title, array $excerpt): array
    {
        $body = [];

        $paragraphs = [
            'tr' => [
                'Evdeki her parça bir karar. Doğru seçim, çoğu zaman daha pahalı olan değil, günlük hayata en az direnen olandır.',
                'Ölçü almadan alışverişe başlamayın. Odanın en dar geçişini, kapı genişliğini ve asansör ölçüsünü not edin; bu üç sayı çoğu iade sebebini ortadan kaldırır.',
                'Malzemeyi kullanım sıklığına göre seçin. Günde sekiz saat oturulan bir koltukta kumaşın sürtünme dayanımı, rengin modasından daha önemlidir.',
                'Son olarak ışığı hesaba katın: aynı ceviz ton, kuzeye bakan bir odada soğuk, güneye bakan bir odada sıcak görünür. Kararınızı odanın kendi ışığında verin.',
            ],
            'en' => [
                'Every piece at home is a decision. The right choice is usually not the most expensive one, but the one that resists daily life the least.',
                'Do not start shopping before measuring. Note the narrowest passage, the door width and the lift dimensions; those three numbers remove most reasons for a return.',
                'Choose the material according to how often it will be used. On a sofa that is sat on eight hours a day, abrasion resistance matters more than a fashionable colour.',
                'Finally, account for light: the same walnut tone looks cold in a north-facing room and warm in a south-facing one. Decide in the room’s own light.',
            ],
            'bg' => [
                'Всяка мебел у дома е решение. Правилният избор обикновено не е най-скъпият, а този, който най-малко пречи на ежедневието.',
                'Не започвайте да пазарувате, преди да измерите. Запишете най-тесния проход, ширината на вратата и размера на асансьора.',
                'Изберете материала според честотата на употреба. При диван, на който се седи по осем часа дневно, устойчивостта на плата е по-важна от модния цвят.',
                'Накрая имайте предвид светлината: същият орехов тон изглежда студен в стая на север и топъл в стая на юг.',
            ],
            'el' => [
                'Κάθε κομμάτι στο σπίτι είναι μια απόφαση. Η σωστή επιλογή συνήθως δεν είναι η ακριβότερη, αλλά αυτή που αντιστέκεται λιγότερο στην καθημερινότητα.',
                'Μην ξεκινήσετε τις αγορές πριν μετρήσετε. Σημειώστε το στενότερο πέρασμα, το πλάτος της πόρτας και τις διαστάσεις του ασανσέρ.',
                'Επιλέξτε το υλικό ανάλογα με τη συχνότητα χρήσης. Σε έναν καναπέ που χρησιμοποιείται οκτώ ώρες την ημέρα, η αντοχή του υφάσματος μετράει περισσότερο από το χρώμα της μόδας.',
                'Τέλος, λάβετε υπόψη το φως: ο ίδιος τόνος καρυδιάς φαίνεται ψυχρός σε βόρειο δωμάτιο και ζεστός σε νότιο.',
            ],
        ];

        foreach ($paragraphs as $locale => $lines) {
            $body[$locale] = ($excerpt[$locale] ?? '')."\n\n".implode("\n\n", $lines);
        }

        return $body;
    }

    private function pages(): void
    {
        $pages = [
            [
                'slug' => 'hakkimizda',
                'footer' => false,
                'title' => ['tr' => 'Hakkımızda', 'en' => 'About us', 'bg' => 'За нас', 'el' => 'Σχετικά με εμάς'],
                'body' => [
                    'tr' => "Sevkur Mobilya, Edirne’deki kendi atölyesinde masif ahşap ve doğal kumaşlarla mobilya üretir.\n\nHer parça tek tek ölçülür, zımparalanır ve montaj öncesi kontrol edilir. Amacımız moda olan değil, yıllarca kullanılan mobilya yapmak.\n\nTürkiye geneline kurumsal kargo ile gönderim yapıyor, Edirne ve çevresinde kendi ekibimizle montaj hizmeti veriyoruz.",
                    'en' => "Sevkur Furniture builds furniture from solid wood and natural fabrics in its own workshop in Edirne, Türkiye.\n\nEvery piece is measured, sanded and inspected before assembly. Our goal is not fashionable furniture but furniture that lasts.\n\nWe ship across Türkiye with corporate couriers and offer in-house assembly around Edirne.",
                    'bg' => "Севкур изработва мебели от масивно дърво и естествени тъкани в собствена работилница в Одрин.\n\nВсяко изделие се измерва, шлайфа и проверява преди сглобяване. Целта ни са мебели, които издържат години.\n\nИзпращаме до цяла Турция и предлагаме монтаж в района на Одрин.",
                    'el' => "Η Sevkur κατασκευάζει έπιπλα από μασίφ ξύλο και φυσικά υφάσματα στο δικό της εργαστήριο στην Αδριανούπολη.\n\nΚάθε κομμάτι μετριέται, τρίβεται και ελέγχεται πριν τη συναρμολόγηση.\n\nΑποστέλλουμε σε όλη την Τουρκία και προσφέρουμε συναρμολόγηση στην περιοχή.",
                ],
            ],
            [
                'slug' => 'teslimat-ve-kargo',
                'footer' => true,
                'title' => ['tr' => 'Teslimat ve Kargo', 'en' => 'Delivery & Shipping', 'bg' => 'Доставка', 'el' => 'Παράδοση & Αποστολή'],
                'body' => [
                    'tr' => "Siparişleriniz ödeme onayından sonra ürün sayfasında belirtilen sürede hazırlanır.\n\n15.000 ₺ ve üzeri siparişlerde kargo ücretsizdir. Diğer siparişlerde kargo ücreti ödeme adımında gösterilir.\n\nBüyük hacimli ürünler (koltuk, gardırop, karyola) kurumsal nakliye ile gönderilir ve kapıya teslim edilir. Edirne merkez ve çevre ilçelerde montaj ekibimiz kurulum yapar.",
                    'en' => "Orders are prepared within the lead time shown on each product page after payment is confirmed.\n\nShipping is free for orders over 15,000 ₺. Otherwise the shipping fee is shown at checkout.\n\nBulky items (sofas, wardrobes, beds) travel with a freight carrier and are delivered to your door. Around Edirne our own team handles assembly.",
                    'bg' => "Поръчките се подготвят в срока, посочен на страницата на продукта, след потвърдено плащане.\n\nДоставката е безплатна над 15 000 ₺.\n\nОбемните продукти се изпращат с товарен превозвач и се доставят до вратата.",
                    'el' => "Οι παραγγελίες ετοιμάζονται εντός του χρόνου που αναγράφεται στη σελίδα του προϊόντος μετά την επιβεβαίωση πληρωμής.\n\nΗ αποστολή είναι δωρεάν για παραγγελίες άνω των 15.000 ₺.\n\nΤα ογκώδη αντικείμενα παραδίδονται με μεταφορική στην πόρτα σας.",
                ],
            ],
            [
                'slug' => 'iade-ve-degisim',
                'footer' => true,
                'title' => ['tr' => 'İade ve Değişim', 'en' => 'Returns & Exchanges', 'bg' => 'Връщане и замяна', 'el' => 'Επιστροφές & Αλλαγές'],
                'body' => [
                    'tr' => "Teslim tarihinden itibaren 14 gün içinde, kullanılmamış ve orijinal ambalajındaki ürünleri iade edebilirsiniz.\n\nÖlçüye özel üretilen ürünler bu kapsamın dışındadır.\n\nİade talebiniz onaylandıktan sonra ödeme, kullandığınız yöntemle 10 iş günü içinde iade edilir.",
                    'en' => "You may return unused items in their original packaging within 14 days of delivery.\n\nMade-to-measure items are excluded.\n\nOnce your return is approved, the refund is issued to your original payment method within 10 business days.",
                    'bg' => "Можете да върнете неизползвани продукти в оригинална опаковка до 14 дни след доставката.\n\nИзделията по поръчка са изключени.\n\nСумата се възстановява до 10 работни дни.",
                    'el' => "Μπορείτε να επιστρέψετε αχρησιμοποίητα προϊόντα στην αρχική συσκευασία εντός 14 ημερών.\n\nΤα κατά παραγγελία προϊόντα εξαιρούνται.\n\nΗ επιστροφή χρημάτων γίνεται εντός 10 εργάσιμων ημερών.",
                ],
            ],
            [
                'slug' => 'gizlilik-politikasi',
                'footer' => true,
                'title' => ['tr' => 'Gizlilik Politikası', 'en' => 'Privacy Policy', 'bg' => 'Политика за поверителност', 'el' => 'Πολιτική Απορρήτου'],
                'body' => [
                    'tr' => "Bu metin örnek içeriktir; yayına almadan önce hukuk danışmanınızla birlikte güncelleyin.\n\nSitemizde yalnızca siparişin tamamlanması ve müşteri desteği için gereken kişisel verileri işleriz. Ödeme bilgileri sitemizde saklanmaz; ödeme PayTR altyapısı üzerinden alınır.\n\nKişisel verilerinizin silinmesini veya düzeltilmesini talep etmek için bizimle iletişime geçebilirsiniz.",
                    'en' => "This is placeholder content; review it with your legal adviser before going live.\n\nWe process only the personal data required to complete your order and provide support. Payment details are never stored on our servers — payments run through PayTR.\n\nContact us to request deletion or correction of your data.",
                    'bg' => "Това е примерно съдържание; прегледайте го с юрист преди публикуване.\n\nОбработваме само данните, необходими за изпълнение на поръчката. Данните за плащане не се съхраняват при нас.",
                    'el' => "Αυτό είναι ενδεικτικό περιεχόμενο· ελέγξτε το με νομικό σύμβουλο πριν τη δημοσίευση.\n\nΕπεξεργαζόμαστε μόνο τα δεδομένα που απαιτούνται για την παραγγελία σας. Τα στοιχεία πληρωμής δεν αποθηκεύονται.",
                ],
            ],
            [
                'slug' => 'mesafeli-satis-sozlesmesi',
                'footer' => true,
                'title' => ['tr' => 'Mesafeli Satış Sözleşmesi', 'en' => 'Distance Sales Agreement', 'bg' => 'Договор за продажба от разстояние', 'el' => 'Σύμβαση Πώλησης εξ Αποστάσεως'],
                'body' => [
                    'tr' => "Bu metin örnek içeriktir; yayına almadan önce hukuk danışmanınızla birlikte güncelleyin.\n\nSözleşme, alıcı ile satıcı arasında elektronik ortamda kurulan satışın koşullarını düzenler. Sipariş onayı ile birlikte alıcı, bu koşulları kabul etmiş sayılır.",
                    'en' => "This is placeholder content; review it with your legal adviser before going live.\n\nThe agreement sets out the conditions of the sale concluded electronically between buyer and seller.",
                    'bg' => 'Това е примерно съдържание; прегледайте го с юрист преди публикуване.',
                    'el' => 'Αυτό είναι ενδεικτικό περιεχόμενο· ελέγξτε το με νομικό σύμβουλο πριν τη δημοσίευση.',
                ],
            ],
        ];

        foreach ($pages as $index => $page) {
            Page::updateOrCreate(['slug' => $page['slug']], [
                'title' => $page['title'],
                'body' => $page['body'],
                'is_active' => true,
                'show_in_footer' => $page['footer'],
                'position' => $index,
            ]);
        }
    }

    /**
     * Demo fotograflarinin kaynak/lisans sayfasi.
     *
     * Gorseller Wikimedia Commons'tan geliyor; CC BY ve CC BY-SA lisanslari
     * kullanildiklari yerde kaynak gosterilmesini SART kosuyor. Bu sayfa alt
     * bilgiden erisilebilir. Gercek urun fotograflari yuklendiginde sayfa
     * yonetim panelinden silinebilir.
     */
    private function photoCredits(): void
    {
        $credits = DemoPhotos::credits();

        if ($credits === []) {
            Page::where('slug', 'gorsel-kaynaklari')->delete();

            return;
        }

        $lines = [];

        foreach ($credits as $file => $info) {
            $lines[] = sprintf(
                '• %s — %s (%s) — %s',
                $info['baslik'] ?: basename($file),
                $info['yazar'] ?: 'Bilinmiyor',
                $info['lisans'] ?: '—',
                $info['kaynak'] ?: ''
            );
        }

        $list = implode("\n", $lines);

        $intro = [
            'tr' => "Bu sitedeki demo ürün fotoğrafları Wikimedia Commons'tan alınmıştır ve yalnızca tanıtım amaçlıdır — gösterilen mobilyalar satılan ürünler değildir.\n\nAşağıda her görselin yaratıcısı, lisansı ve kaynağı listelenmiştir.",
            'en' => "The demo product photographs on this site come from Wikimedia Commons and are for illustration only — the furniture shown is not the product being sold.\n\nEach image's creator, licence and source is listed below.",
            'bg' => "Демонстрационните снимки на продукти идват от Wikimedia Commons и са само с илюстративна цел.\n\nПо-долу са авторът, лицензът и източникът на всяко изображение.",
            'el' => "Οι φωτογραφίες προϊόντων προέρχονται από το Wikimedia Commons και είναι μόνο ενδεικτικές.\n\nΠαρακάτω αναφέρονται ο δημιουργός, η άδεια και η πηγή κάθε εικόνας.",
        ];

        $body = [];
        foreach ($intro as $locale => $text) {
            $body[$locale] = $text."\n\n".$list;
        }

        Page::updateOrCreate(['slug' => 'gorsel-kaynaklari'], [
            'title' => [
                'tr' => 'Görsel Kaynakları',
                'en' => 'Image Credits',
                'bg' => 'Източници на изображенията',
                'el' => 'Πηγές Εικόνων',
            ],
            'body' => $body,
            'is_active' => true,
            'show_in_footer' => true,
            'position' => 90,
        ]);
    }

    private function faqs(): void
    {
        $rows = [
            [
                ['tr' => 'Siparişim ne kadar sürede elime ulaşır?', 'en' => 'How long does delivery take?', 'bg' => 'За колко време пристига поръчката?', 'el' => 'Πόσο διαρκεί η παράδοση;'],
                ['tr' => 'Her ürünün hazırlanma süresi ürün sayfasında yazar; genellikle 2–7 iş günüdür. Kargo süresi buna 1–3 gün ekler.', 'en' => 'The lead time is shown on each product page, usually 2–7 business days, plus 1–3 days in transit.', 'bg' => 'Срокът е посочен на страницата на продукта, обикновено 2–7 работни дни.', 'el' => 'Ο χρόνος αναγράφεται στη σελίδα του προϊόντος, συνήθως 2–7 εργάσιμες ημέρες.'],
            ],
            [
                ['tr' => 'Montaj hizmeti veriyor musunuz?', 'en' => 'Do you offer assembly?', 'bg' => 'Предлагате ли монтаж?', 'el' => 'Προσφέρετε συναρμολόγηση;'],
                ['tr' => 'Edirne merkez ve çevre ilçelerde kendi ekibimiz kurulum yapar. Diğer illerde ürünler montajlı ya da montaj talimatıyla gönderilir.', 'en' => 'Our own team assembles around Edirne. Elsewhere items arrive assembled or with instructions.', 'bg' => 'В района на Одрин монтажът се извършва от наш екип.', 'el' => 'Στην περιοχή της Αδριανούπολης η συναρμολόγηση γίνεται από την ομάδα μας.'],
            ],
            [
                ['tr' => 'Ödeme hangi para biriminden alınır?', 'en' => 'Which currency am I charged in?', 'bg' => 'В каква валута се таксувам?', 'el' => 'Σε ποιο νόμισμα χρεώνομαι;'],
                ['tr' => 'Tahsilat her zaman Türk Lirası (₺) üzerinden yapılır. Euro ve Leva fiyatları TCMB kuruyla hesaplanan bilgi amaçlı gösterimlerdir.', 'en' => 'You are always charged in Turkish Lira (₺). Euro and Lev prices are indicative, converted at the Turkish Central Bank rate.', 'bg' => 'Таксуването е винаги в турски лири (₺). Цените в евро и лева са ориентировъчни.', 'el' => 'Η χρέωση γίνεται πάντα σε τουρκική λίρα (₺). Οι τιμές σε ευρώ και λεβ είναι ενδεικτικές.'],
            ],
            [
                ['tr' => 'Ölçüye özel üretim yapıyor musunuz?', 'en' => 'Do you make custom sizes?', 'bg' => 'Изработвате ли по поръчка?', 'el' => 'Κατασκευάζετε σε ειδικές διαστάσεις;'],
                ['tr' => 'Evet. İletişim formundan ölçülerinizi ve kullanmak istediğiniz malzemeyi paylaşın, iki iş günü içinde fiyat verelim.', 'en' => 'Yes. Send your dimensions and preferred material through the contact form and we will quote within two business days.', 'bg' => 'Да. Изпратете размерите си чрез формата за контакт.', 'el' => 'Ναι. Στείλτε τις διαστάσεις σας μέσω της φόρμας επικοινωνίας.'],
            ],
            [
                ['tr' => 'İade koşulları neler?', 'en' => 'What are the return conditions?', 'bg' => 'Какви са условията за връщане?', 'el' => 'Ποιοι είναι οι όροι επιστροφής;'],
                ['tr' => 'Kullanılmamış ve orijinal ambalajındaki ürünler 14 gün içinde iade edilebilir. Ölçüye özel üretimler kapsam dışıdır.', 'en' => 'Unused items in original packaging can be returned within 14 days. Made-to-measure pieces are excluded.', 'bg' => 'Неизползвани продукти в оригинална опаковка — до 14 дни.', 'el' => 'Αχρησιμοποίητα προϊόντα στην αρχική συσκευασία εντός 14 ημερών.'],
            ],
        ];

        Faq::query()->delete();

        foreach ($rows as $index => [$question, $answer]) {
            Faq::create([
                'question' => $question,
                'answer' => $answer,
                'group' => 'general',
                'position' => $index,
                'is_active' => true,
            ]);
        }
    }
}
