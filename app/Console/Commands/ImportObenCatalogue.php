<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Oben 2026 katalogundaki urunleri magazaya aktarir.
 *
 * Katalog PDF'inden once gorseller SAYFA SAYFA cikarilir
 * (tools/pdf-sayfa-gorsel.php), sonra bu komut her urunu kendi sayfasindaki
 * fotograflarla eslestirip olusturur.
 *
 * Urunler PASIF olusur ve fiyatlari SIFIRDIR: katalogda fiyat yok. Yonetici
 * fiyatlari girip yayina alir — fiyatsiz urun yanlislikla satilamaz.
 *
 * Komut tekrar calistirilabilir: var olan urun slug'una gore guncellenir,
 * kopya olusmaz.
 */
class ImportObenCatalogue extends Command
{
    protected $signature = 'oben:aktar
        {--kaynak= : Sayfa gorsellerinin bulundugu dizin}
        {--kuru : Hicbir sey yazma, ne yapilacagini goster}';

    protected $description = 'Oben 2026 katalogundaki urunleri taslak olarak ekler';

    /**
     * Koleksiyonlar: her biri dort urun olarak satilir.
     *
     * Sayfa numaralari katalog PDF'inin SAYFALARIDIR (1 tabanli).
     * yatak/yemek/koltuk sayfalarinin her birinde iki fotograf var:
     * kucuk nesne numarasi SAG sayfanin, buyuk numara SOL sayfanin fotografi.
     *
     * @var array<int, array<string, mixed>>
     */
    private const COLLECTIONS = [
        [
            'ad' => 'Nova',
            // TV unitesi fotografi sayfadaki hangi fotograf (gorselle dogrulandi)
            'tv_gorsel' => 'ilk',
            'yatak' => 3, 'yemek' => 4, 'koltuk' => 5,
            'ozet' => 'Modern zarafetin ışıltılı hali.',
            'yatak_aciklama' => 'Nova Yatak Odası Takımı, zarif krem tonları ve yenilikçi tasarımıyla yaşam alanınıza huzur dolu bir şıklık katıyor. Karyola altındaki gizli LED aydınlatmaları ve yatak başlığına entegre şık aplikleriyle yatak odanıza sıcak, romantik bir atmosfer kazandırır. Kapitone detaylı, yumuşak dokulu geniş yatak başlığı kusursuz bir konfor sunarken, mobilyalardaki geometrik formlu ayak tasarımları mekana dinamik bir hava katıyor.',
            'yatak_olculer' => ['Karyola' => 'G 245 cm - Y 125 cm - D 215 cm (160x200 yatağa uygundur)', 'Şifonyer' => 'G 150 cm - Y 86 cm - D 44 cm', 'Şifonyer Aynası' => 'G 103 cm - Y 74 cm - D 3 cm', 'Komodin' => 'G 64 cm - Y 50 cm - D 44 cm', 'Puf' => 'G 35 cm - Y 44 cm - D 35 cm', 'Gardırop (6 Kapılı)' => 'G 251 cm - Y 218 cm - D 60 cm'],
            'yemek_olculer' => ['Masa' => 'G 170 cm - Y 79 cm - D 92 cm (açık hali 205 cm)', 'Konsol' => 'G 210 cm - Y 86 cm - D 50 cm', 'Konsol Aynası' => 'G 70 cm - Y 102 cm - D 3 cm'],
            'tv_olculer' => ['Tv Ünite Alt' => 'G 210 cm - Y 68 cm - D 48 cm', 'Tv Ünite' => 'G 277 cm - Y 170 cm - D 48 cm', 'Orta Sehpa' => 'G 86 cm - Y 43 cm - D 86 cm'],
            'koltuk_aciklama' => 'Nova koltuk takımı, yumuşak dokulu kumaşı ve altın renkli özgün alt baza tasarımıyla modern lüksü yaşam alanlarınıza taşıyor. Kavisli formlara sahip kiremit tonlarındaki iddialı berjeri ise tasarıma sıcak ve dinamik bir karakter kazandırıyor.',
            'koltuk_olculer' => ["3'lü Kanepe" => 'G 243 cm - Y 80 cm - D 98 cm', 'Berjer' => 'G 84 cm - Y 83 cm - D 88 cm', 'Özellik' => 'Yatak olabilme özelliği'],
        ],
        [
            'ad' => 'Grande',
            // TV unitesi fotografi sayfadaki hangi fotograf (gorselle dogrulandi)
            'tv_gorsel' => 'ilk',
            'yatak' => 7, 'yemek' => 8, 'koltuk' => 9,
            'ozet' => 'Doğal dokuların ve ahşabın sıcak buluşması.',
            'yatak_aciklama' => 'Grande Yatak Odası Takımı, yumuşak dokulu kumaşı ve zarif ahşap ayak detaylarıyla yaşam alanınıza doğal ve davetkar bir his katıyor. Yatak başlığındaki yenilikçi silindir form ve hemen altına entegre edilen sıcak LED aydınlatmalar, dinlenme alanınıza modern ve huzurlu bir atmosfer sağlıyor. Komodin ve şifonyer çekmecelerinde öne çıkan yatay çizgili kabartma formlar, takımın tasarım diline dinamik ve estetik bir ritim kazandırıyor.',
            'yatak_olculer' => ['Karyola' => 'G 240 cm - Y 118 cm - D 210 cm (160x200 yatağa uygundur)', 'Şifonyer' => 'G 140 cm - Y 83 cm - D 46 cm', 'Şifonyer Aynası' => 'G 80 cm - Y 80 cm - D 3 cm', 'Komodin' => 'G 60 cm - Y 47 cm - D 46 cm', 'Puf' => 'G 35 cm - Y 44 cm - D 35 cm', 'Gardırop (5 Kapılı)' => 'G 254 cm - Y 218 cm - D 61 cm'],
            'yemek_olculer' => ['Masa' => 'G 170 cm - Y 79 cm - D 92 cm (açık hali 205 cm)', 'Konsol' => 'G 210 cm - Y 83 cm - D 44 cm', 'Konsol Aynası' => 'G 109 cm - Y 83 cm - D 3 cm'],
            'tv_olculer' => ['Tv Ünite' => 'G 210 cm - Y 160 cm - D 44 cm', 'Tv Ünite Alt' => 'G 210 cm - Y 59 cm - D 44 cm', 'Orta Sehpa' => 'G 100 cm - Y 35 cm - D 61 cm'],
            'koltuk_aciklama' => 'Grande koltuk takımı, toprak ve krem tonlarının doğal uyumunu yumuşak dokulu kumaşıyla harmanlayarak yaşam alanlarına sıcak bir atmosfer katıyor. Kavisli modern hatları ve zarif ahşap ayak detaylarıyla estetiği ve üst düzey konforu kusursuzca bir araya getiriyor.',
            'koltuk_olculer' => ["3'lü Kanepe" => 'G 240 cm - Y 85 cm - D 95 cm', 'Berjer' => 'G 92 cm - Y 76 cm - D 95 cm', 'Özellik' => 'Yatak olabilme özelliği'],
        ],
        [
            'ad' => 'Napoli',
            // TV unitesi fotografi sayfadaki hangi fotograf (gorselle dogrulandi)
            'tv_gorsel' => 'ilk',
            'yatak' => 11, 'yemek' => 12, 'koltuk' => 13,
            'ozet' => 'Modern çizgilerin lüks dokunuşu.',
            'yatak_aciklama' => 'Napoli Yatak Odası Takımı, ferahlatıcı açık tonları, dikey dikiş detaylı geniş başlığı ve zarif metalik vurgularıyla yaşam alanınıza sofistike bir şıklık katıyor. Yatak başlığının iki yanına entegre edilen aydınlatmalı fitilli paneller, odanızda hem estetik bir derinlik hem de sıcak bir atmosfer yaratıyor. Komodin ve şifonyerde öne çıkan ikonik dairesel kulplar ile ince yapılı metal ayaklar, takımın modern tasarım dilini iddialı bir zarafetle tamamlıyor.',
            'yatak_olculer' => ['Karyola' => 'G 248 cm - Y 126 cm - D 210 cm (160x200 yatağa uygundur)', 'Şifonyer' => 'G 150 cm - Y 91 cm - D 43 cm', 'Şifonyer Aynası' => 'G 90 cm - Y 92 cm - D 3 cm', 'Komodin' => 'G 65 cm - Y 46 cm - D 43 cm', 'Puf' => 'G 35 cm - Y 44 cm - D 35 cm', 'Gardırop (2 Kapılı)' => 'G 100 cm - Y 218 cm - D 59 cm', 'Gardırop (1 Kapılı)' => 'G 52 cm - Y 218 cm - D 58 cm'],
            'yemek_olculer' => ['Masa' => 'G 170 cm - Y 75 cm - D 92 cm (açık hali 205 cm)', 'Konsol' => 'G 206 cm - Y 82 cm - D 43 cm', 'Konsol Aynası' => 'G 100 cm - Y 101 cm - D 3 cm'],
            'tv_olculer' => ['Tv Ünite' => 'G 206 cm - Y 165 cm - D 43 cm', 'Tv Ünite Alt' => 'G 206 cm - Y 53 cm - D 43 cm', 'Tv Ünite Üst' => 'G 49 cm - Y 90 cm - D 25 cm', 'Orta Sehpa' => 'G 170 cm - Y 35 cm - D 125 cm (puf dahil)'],
            'koltuk_aciklama' => 'Napoli koltuk takımı, ince işçiliği ve modern çizgileriyle oturma alanınıza sofistike bir duruş kazandırıyor.',
            'koltuk_olculer' => [],
        ],
        [
            'ad' => 'Viyana',
            // TV unitesi fotografi sayfadaki hangi fotograf (gorselle dogrulandi)
            'tv_gorsel' => 'son',
            'yatak' => 15, 'yemek' => 16, 'koltuk' => 17,
            'ozet' => 'Yumuşak hatların ve modern zarafetin uyumu.',
            'yatak_aciklama' => 'Viyana Yatak Odası Takımı, kavisli yumuşak hatları ve huzur veren krem tonlarıyla yatak odanıza davetkar bir şıklık getiriyor. Oval formlu, yumuşak dokulu yatak başlığı konfor hissini en üst seviyeye taşırken, tasarıma eşlik eden altın rengi metalik detaylar mekana lüks bir ışıltı katıyor. Komodindeki heykelsi ayak kurgusu ve minimalist kulplar, takımın estetik bütünlüğünü modern bir dille vurguluyor.',
            'yatak_olculer' => ['Karyola' => 'G 207 cm - Y 127 cm - D 210 cm (160x200 yatağa uygundur)', 'Şifonyer' => 'G 135 cm - Y 81 cm - D 43 cm', 'Şifonyer Aynası' => 'G 103 cm - Y 80 cm - D 3 cm', 'Komodin' => 'G 66 cm - Y 45 cm - D 43 cm', 'Puf' => 'G 35 cm - Y 44 cm - D 35 cm', 'Gardırop (5 Kapılı)' => 'G 251 cm - Y 222 cm - D 58 cm'],
            'yemek_olculer' => ['Masa' => 'G 170 cm - Y 75 cm - D 92 cm (açık hali 205 cm)', 'Konsol' => 'G 209 cm - Y 79 cm - D 44 cm', 'Konsol Aynası (Tek)' => 'G 35 cm - Y 98 cm - D 3 cm'],
            'tv_olculer' => ['Tv Ünite' => 'G 209 cm - Y 165 cm - D 43 cm', 'Tv Ünite Alt' => 'G 209 cm - Y 49 cm - D 43 cm', 'Tv Ünite Üst' => 'G 50 cm - Y 80 cm - D 27 cm', 'Tv Ünite Raf' => 'G 150 cm - Y 15 cm - D 19 cm', 'Orta Sehpa' => 'G 104 cm - Y 42 cm - D 65 cm'],
            'koltuk_aciklama' => 'Viyana koltuk takımı, yumuşak hatları ve dengeli oranlarıyla oturma alanınıza sakin bir zarafet taşıyor.',
            'koltuk_olculer' => [],
        ],
        [
            'ad' => 'Liva',
            // TV unitesi fotografi sayfadaki hangi fotograf (gorselle dogrulandi)
            'tv_gorsel' => 'son',
            'yatak' => 19, 'yemek' => 20, 'koltuk' => 21,
            'ozet' => 'Ahşap sıcaklığı ve modern geometrinin kusursuz uyumu.',
            'yatak_aciklama' => 'Liva Yatak Odası Takımı, ahşabın doğal sıcaklığını ve krem tonlarının ferahlığını bir araya getirerek yaşam alanınıza çağdaş bir şıklık katıyor. İki panelli, hafif eğimli ve yumuşak dokulu yatak başlığı, yatak odanızda kusursuz bir dinlenme konforu sunuyor. Komodin ve makyaj masasında öne çıkan ahşap ile krem renk kombinasyonu, geometrik formlarla birleşerek mekana modern bir dinamizm kazandırıyor.',
            'yatak_olculer' => ['Karyola' => 'G 206 cm - Y 125 cm - D 210 cm (160x200 yatağa uygundur)', 'Şifonyer' => 'G 142 cm - Y 82 cm - D 45 cm', 'Şifonyer Aynası' => 'G 80 cm - Y 80 cm - D 3 cm', 'Komodin' => 'G 64 cm - Y 45 cm - D 43 cm', 'Puf' => 'G 35 cm - Y 44 cm - D 35 cm', 'Gardırop (4 Kapılı)' => 'G 164 cm - Y 218 cm - D 58 cm', 'Gardırop (1 Kapılı)' => 'G 43 cm - Y 218 cm - D 60 cm'],
            'yemek_olculer' => ['Masa' => 'G 170 cm - Y 74 cm - D 92 cm (açık hali 205 cm)', 'Konsol' => 'G 209 cm - Y 82 cm - D 45 cm', 'Konsol Aynası' => 'G 94 cm - Y 100 cm - D 32 cm'],
            'tv_olculer' => ['Tv Ünite' => 'G 209 cm - Y 62 cm - D 45 cm', 'Tv Ünite Pano' => 'G 100 cm - Y 80 cm - D 27 cm', 'Orta Sehpa' => 'G 104 cm - Y 41 cm - D 60 cm'],
            'koltuk_aciklama' => 'Liva koltuk takımı, ahşap detayları ve yalın formlarıyla yaşam alanınıza dengeli bir modernlik getiriyor.',
            'koltuk_olculer' => [],
        ],
        [
            'ad' => 'Alaçatı',
            // TV unitesi fotografi sayfadaki hangi fotograf (gorselle dogrulandi)
            'tv_gorsel' => 'son',
            'yatak' => 23, 'yemek' => 24, 'koltuk' => 25,
            'ozet' => 'Modern çizgiler, ahşap detaylar ve traverten dizayn.',
            'yatak_aciklama' => 'Modern hatları ve ahşap detaylarıyla dikkat çeken Alaçatı koleksiyonu, gri ve bej rengi kombinasyonlarının travertenle tamamlanmasıyla estetik uyum sağlıyor. Alaçatı Yatak Odası gösterişten uzak yapısını gri tonlarıyla ve bej rengi yüzeylerle buluşturuyor. Oval tasarımı ve dokuma kumaş yapısıyla yatak başlığı modern bir duruş sergiliyor. 5 kapaklı dolap ve fren raylı çekmeceler işlevselliği ön planda tutuyor.',
            'yatak_olculer' => ['Karyola' => 'G 207 cm - Y 127 cm - D 210 cm (160x200 yatağa uygundur)', 'Şifonyer' => 'G 137 cm - Y 87 cm - D 45 cm', 'Şifonyer Aynası' => 'G 80 cm - Y 80 cm - D 3 cm', 'Komodin' => 'G 65 cm - Y 43 cm - D 45 cm', 'Puf' => 'G 35 cm - Y 44 cm - D 35 cm', 'Gardırop (5 Kapılı)' => 'G 252 cm - Y 219 cm - D 61 cm'],
            'yemek_olculer' => ['Masa' => 'G 171 cm - Y 76 cm - D 92 cm (açık hali 205 cm)', 'Konsol' => 'G 210 cm - Y 86 cm - D 46 cm', 'Konsol Aynası' => 'G 94 cm - Y 100 cm - D 3 cm'],
            'tv_olculer' => ['Tv Ünite Alt' => 'G 208 cm - Y 63 cm - D 45 cm', 'Tv Ünite Üst' => 'G 40 cm - Y 83 cm - D 27 cm', 'Tv Ünite Raf' => 'G 165 cm - Y 25 cm - D 19 cm', 'Orta Sehpa' => 'G 104 cm - Y 46 cm - D 66 cm', 'Zigon Sehpa' => 'G 37 cm - Y 51 cm - D 37 cm'],
            'koltuk_aciklama' => 'Alaçatı koltuk takımı, gri ve bej tonlarının dengeli uyumuyla sade ve çağdaş bir oturma alanı kuruyor.',
            'koltuk_olculer' => [],
        ],
        [
            'ad' => 'Bohem',
            // TV unitesi fotografi sayfadaki hangi fotograf (gorselle dogrulandi)
            'tv_gorsel' => 'son',
            'yatak' => 27, 'yemek' => 28, 'koltuk' => 29,
            'ozet' => 'Vintage esintiler ve çağdaş tasarımlar.',
            'yatak_aciklama' => 'Doğal malzemeleri ve özel detaylarıyla sıcaklık katan Bohem, rustik dokuları bohem estetiğiyle birleştirerek evlere hem şıklık hem de rahatlık getiriyor. Bohem Yatak Odası açık renkleriyle ve yuvarlak kulplarıyla dikkat çekiyor, yaşam alanlarına ferahlık ve aydınlık getiriyor. Yumuşak döşemeleriyle ekstra konfor sunuyor. Tek çekmeceli komodin, altı farklı dolap alternatifi sunan Bohem yatak odası takımı, şıklığı ve rahatlığı yatak odanıza taşıyor.',
            'yatak_olculer' => ['Karyola' => 'G 208 cm - Y 127 cm - D 202 cm (160x200 yatağa uygundur)', 'Şifonyer' => 'G 141 cm - Y 83 cm - D 45 cm', 'Şifonyer Aynası' => 'G 60 cm - Y 90 cm - D 3 cm', 'Komodin' => 'G 66 cm - Y 50 cm - D 45 cm', 'Puf' => 'G 68 cm - Y 33 cm - D 68 cm', 'Gardırop (4 Kapılı)' => 'G 167 cm - Y 218 cm - D 60 cm', 'Gardırop (2 Kapılı)' => 'G 84 cm - Y 218 cm - D 60 cm', 'Gardırop (1 Kapılı)' => 'G 42 cm - Y 218 cm - D 60 cm'],
            'yemek_olculer' => ['Masa' => 'G 170 cm - Y 77 cm - D 92 cm (açık hali 205 cm)', 'Konsol' => 'G 210 cm - Y 86 cm - D 46 cm', 'Konsol Aynası (Tek)' => 'G 90 cm - Y 45 cm - D 3 cm'],
            'tv_olculer' => ['Tv Ünite' => 'G 209 cm - Y 185 cm - D 45 cm', 'Tv Ünite Raf' => 'G 165 cm - Y 29 cm - D 35 cm', 'Tv Ünite Kutu' => 'G 40 cm - Y 30 cm - D 90 cm', 'Orta Sehpa' => 'G 110 cm - Y 45 cm - D 60 cm'],
            'koltuk_aciklama' => 'Bohem koltuk takımı, rustik dokuları ve yumuşak döşemeleriyle evinize rahat ve sıcak bir karakter katıyor.',
            'koltuk_olculer' => [],
        ],
    ];

    /**
     * Koleksiyon disindaki tekil urunler.
     *
     * @var array<int, array<string, mixed>>
     */
    private const SINGLES = [
        [
            'ad' => 'Efes Köşe Dolabı', 'sayfa' => 31, 'kategori' => 'gardiroplar',
            'aciklama' => 'Efes Köşe Dolap, modüler kurgusuyla yatak odanızı giyinme odasına dönüştürüyor. Akordiyon kapaklar, köşe modülleri, camlı raflı ve çekmeceli üniteler ile ihtiyacınıza göre kurgulanabiliyor.',
            'olculer' => ['4 Kapılı Akordiyon' => 'G 168 cm - Y 218 cm - D 60 cm', '2 Kapılı Ahşap' => 'G 84 cm - Y 218 cm - D 60 cm', '4 Kapılı Akordiyon Köşe' => 'G 144 cm - Y 218 cm - D 144 cm', 'Köşe Modül' => 'G 89x89 cm - Y 218 cm - D 100 cm', '2 Kapılı Camlı Raflı' => 'G 84 cm - Y 218 cm - D 60 cm', '2 Kapılı Camlı Çekmeceli' => 'G 84 cm - Y 218 cm - D 60 cm', '1 Kapılı' => 'G 42 cm - Y 218 cm - D 60 cm', 'Ada' => 'G 100 cm - Y 95 cm - D 80 cm'],
        ],
        [
            'ad' => 'Flora Koltuk Takımı', 'sayfa' => 33, 'kategori' => 'koltuk-takimlari',
            'aciklama' => 'Flora koltuk takımı, modern hatları ve dokulu buklet kumaşıyla yaşam alanınıza hem konforlu hem de şık bir hava katıyor. Doğal ahşap ayakları ve kolçak detayları, takımın yalın tasarımını sıcak ve estetik bir şekilde tamamlıyor.',
            'olculer' => ["3'lü Kanepe" => 'G 238 cm - Y 82 cm - D 96 cm', 'Berjer' => 'G 75 cm - Y 88 cm - D 95 cm', 'Özellik' => 'Yatak olabilme özelliği'],
        ],
        [
            'ad' => 'Merry Koltuk Takımı', 'sayfa' => 34, 'kategori' => 'koltuk-takimlari',
            'aciklama' => 'Sade görünümünü işlevsellikle bir araya getiren Merry koltuk takımı, S-yay sistemi ile tasarlanan geniş ve yumuşak oturumu sayesinde yatak özellikli koltuk olarak öne çıkıyor. Kuş tüyü sünger yapısı ve hareketli sırt dinlenme bölgesiyle ekstra rahatlık sağlıyor.',
            'olculer' => ["4'lü Kanepe" => 'G 260 cm - Y 80 cm - D 100 cm', "3'lü Kanepe" => 'G 235 cm - Y 80 cm - D 100 cm', 'Berjer' => 'G 71 cm - Y 97 cm - D 93 cm'],
        ],
        [
            'ad' => 'Alonzo Koltuk Takımı', 'sayfa' => 35, 'kategori' => 'koltuk-takimlari',
            'aciklama' => 'Dayanıklı ve ihtişamlı görünümü yüksek konforla bir araya getiren Alonzo koltuk takımı, mekanizmalarıyla yüksek fonksiyonel özellikler sunuyor. S-yay sistemi ile tasarlanan geniş oturumu ve firm sünger yapısı sayesinde yüksek özellikli bir TV koltuğu havası veriyor. Silikonize ve hareketli sırt dinlenme bölgesiyle mükemmel ötesi konfor sağlıyor.',
            'olculer' => ["3'lü Kanepe" => 'G 227 cm - Y 88 cm - D 106 cm', 'Berjer' => 'G 70 cm - Y 97 cm - D 101 cm', 'Özellik' => 'Yatak olabilme özelliği'],
        ],
        [
            'ad' => 'Aspendos Country Koltuk Takımı', 'sayfa' => 36, 'kategori' => 'koltuk-takimlari',
            'aciklama' => 'Evlerinize modern Aspendos rüzgarı getiren bu takım soft yapısı, dokuma kumaş özelliği ve özel dikiş ve çektirme detaylarıyla ön plana çıkıyor. Şık ve asil duruşu berrak renk kombinasyonu ve özel ahşap ayak tasarımı evlerinizde ferahlık yaratıyor.',
            'olculer' => ["3'lü Kanepe" => 'G 220 cm - Y 86 cm - D 91 cm', 'Berjer' => 'G 75 cm - Y 96 cm - D 88 cm', 'Özellik' => 'Yatak olabilme özelliği'],
        ],
        [
            'ad' => 'Nero Koltuk Takımı', 'sayfa' => 37, 'kategori' => 'koltuk-takimlari',
            'aciklama' => 'Gri tonlar, siyah ahşap ayaklar ve çektirme detayların basit ve pratik sırt takla fonksiyonuyla buluşması.',
            'olculer' => ["3'lü Kanepe" => 'G 231 cm - Y 82 cm - D 96 cm', 'Berjer' => 'G 75 cm - Y 90 cm - D 99 cm', 'Özellik' => 'Yatak olabilme özelliği'],
        ],
        [
            'ad' => 'April Koltuk Takımı', 'sayfa' => 38, 'kategori' => 'koltuk-takimlari',
            'aciklama' => 'Ahşap detayların ceviz rengiyle uyumu. Keskin çizgileri, dayanıklı yapısı, kol çıta detayları ve sırt takla özelliğiyle yaşam yerlerine büyük güven veriyor.',
            'olculer' => ["3'lü Kanepe" => 'G 234 cm - Y 83 cm - D 95 cm', 'Berjer' => 'G 78 cm - Y 89 cm - D 93 cm', 'Özellik' => 'Yatak olabilme özelliği'],
        ],
        [
            'ad' => 'Astana Koltuk Takımı', 'sayfa' => 39, 'kategori' => 'koltuk-takimlari',
            'aciklama' => 'Sade ve doğallığın buluşması. Sırt takla fonksiyonu ve çektirme düğme detayları yaşam alanınıza ferahlık getiriyor.',
            'olculer' => ["3'lü Kanepe" => 'G 221 cm - Y 80 cm - D 93 cm', 'Berjer' => 'G 77 cm - Y 93 cm - D 89 cm', 'Özellik' => 'Yatak olabilme özelliği'],
        ],
        [
            'ad' => 'Kapadokya Koltuk Takımı', 'sayfa' => 40, 'kategori' => 'koltuk-takimlari',
            'aciklama' => 'İskandinav esintisi berjer ve fonksiyonel koltuğun buluşması. Doğal ahşap rengi tasarıma sıcak bir hava katarken yüksek ayak yapısı ve pati dostu kumaşı ise kolay temizlik imkanı sağlıyor. S-yay ile güçlendirilmiş geniş oturumu, dreamsoft süngerleri, kol ve sırt mekanizmalarıyla birlikte yaşam alanlarınıza konfor getiriyor.',
            'olculer' => ["3'lü Kanepe" => 'G 228 cm - Y 80 cm - D 95 cm', 'Berjer' => 'G 78 cm - Y 76 cm - D 76 cm', 'Özellik' => 'Yatak olabilme özelliği'],
        ],
        [
            'ad' => 'Pera Maxi Takım', 'sayfa' => 41, 'kategori' => 'koltuk-takimlari',
            'aciklama' => 'Pera koltuk takımı, zarif hatları ve ahşap ayak detaylarıyla yaşam alanınıza modern ve ferah bir dokunuş katıyor. Silindir formlu kırlentleri ve yumuşak dokulu yapısı, estetik görünümü üst düzey konforla kusursuz bir şekilde buluşturuyor.',
            'olculer' => ["3'lü Kanepe" => 'G 213 cm - Y 82 cm - D 84 cm', 'Berjer' => 'G 72 cm - Y 94 cm - D 90 cm', 'Yatak Ölçüsü' => 'G 185 cm x D 105 cm'],
        ],
        [
            'ad' => 'Merry Köşe Takımı', 'sayfa' => 42, 'kategori' => 'kose-takimlari',
            'aciklama' => 'Sadelik, yatak özelliği ve alternatif ölçü seçenekleri evinize renk katıyor. Desenli kırlentler dekoratif olarak takımda yer almaktadır.',
            'olculer' => ['Large Köşe' => 'G 260 cm - Y 80 cm - D 300 cm', 'Köşe' => 'G 265 cm - Y 80 cm - D 180 cm', 'Puf' => 'G 90 cm - Y 43 cm - D 90 cm'],
        ],
        [
            'ad' => 'Alonzo Köşe Takımı', 'sayfa' => 43, 'kategori' => 'kose-takimlari',
            'aciklama' => 'Alonzo köşe takımı, modüler kurgusu ve geniş oturumuyla kalabalık yaşam alanlarına konfor getiriyor. Desenli kırlentler dekoratif olarak takımda yer almaktadır.',
            'olculer' => ['Köşe' => 'G 300 cm - Y 95 cm - D 300 cm'],
        ],
        [
            'ad' => 'Lexus Köşe Takımı', 'sayfa' => 44, 'kategori' => 'kose-takimlari',
            'aciklama' => 'Lexus köşe takımı, yuvarlak hatlı modülleri ve ahşap küre ayaklarıyla yaşam alanınıza yumuşak ve çağdaş bir karakter katıyor. Modüler yapısı sayesinde mekâna göre kurgulanabiliyor.',
            'olculer' => ['Modül' => 'G 65 / 85 / 105 / 120 cm - Y 85 cm - D 98 cm', 'Puf' => 'G 75 cm - Y 40 cm - D 75 cm'],
        ],
    ];

    public function handle(): int
    {
        $source = $this->option('kaynak')
            ?: base_path('../../Users/ahmet/AppData/Local/Temp/claude');

        if (! is_dir($source)) {
            $this->error("Kaynak dizin bulunamadı: $source");
            $this->line('--kaynak ile sayfa görsellerinin dizinini verin.');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('kuru');

        $brand = $this->brand($dry);
        $this->ensureCategories($dry);

        $created = 0;
        $updated = 0;
        $missing = [];

        foreach (self::COLLECTIONS as $collection) {
            foreach ($this->collectionProducts($collection) as $item) {
                [$state, $note] = $this->importOne($item, $source, $brand, $dry);
                $state === 'yeni' ? $created++ : ($state === 'guncel' ? $updated++ : $missing[] = $note);
            }
        }

        foreach (self::SINGLES as $single) {
            $item = [
                'ad' => $single['ad'],
                'kategori' => $single['kategori'],
                'aciklama' => $single['aciklama'],
                'olculer' => $single['olculer'],
                'sayfa' => $single['sayfa'],
                'gorsel' => 'ikisi',
            ];

            [$state, $note] = $this->importOne($item, $source, $brand, $dry);
            $state === 'yeni' ? $created++ : ($state === 'guncel' ? $updated++ : $missing[] = $note);
        }

        $this->newLine();
        $this->info(($dry ? '[KURU] ' : '').$created.' ürün eklendi, '.$updated.' ürün güncellendi.');

        if ($missing !== []) {
            $this->warn('Görseli bulunamayanlar: '.implode(', ', $missing));
        }

        if (! $dry) {
            $this->line('Ürünler PASİF ve fiyatları 0 — panelden fiyat girip yayına alın.');
        }

        return self::SUCCESS;
    }

    /**
     * Bir koleksiyonun dort urunu.
     *
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    private function collectionProducts(array $c): array
    {
        return [
            [
                'ad' => $c['ad'].' Yatak Odası Takımı',
                'kategori' => 'yatak-odasi-takimlari',
                'aciklama' => $c['yatak_aciklama'],
                'olculer' => $c['yatak_olculer'],
                'sayfa' => $c['yatak'],
                'gorsel' => 'ikisi',
                'ozet' => $c['ozet'],
            ],
            [
                'ad' => $c['ad'].' Yemek Odası Takımı',
                'kategori' => 'yemek-odasi-takimlari',
                'aciklama' => $c['ad'].' Yemek Odası Takımı, masa ve konsoluyla yemek alanınıza koleksiyonun tasarım dilini taşıyor. '.$c['ozet'],
                'olculer' => $c['yemek_olculer'],
                'sayfa' => $c['yemek'],
                'gorsel' => $c['tv_gorsel'] === 'ilk' ? 'son' : 'ilk',
                'ozet' => $c['ozet'],
            ],
            [
                'ad' => $c['ad'].' Tv Ünitesi',
                'kategori' => 'tv-uniteleri',
                'aciklama' => $c['ad'].' Tv Ünitesi, koleksiyonun çizgisini oturma alanınıza taşıyan modüler kurgusuyla televizyon duvarınızı tamamlıyor. '.$c['ozet'],
                'olculer' => $c['tv_olculer'],
                'sayfa' => $c['yemek'],
                'gorsel' => $c['tv_gorsel'],
                'ozet' => $c['ozet'],
            ],
            [
                'ad' => $c['ad'].' Koltuk Takımı',
                'kategori' => 'koltuk-takimlari',
                'aciklama' => $c['koltuk_aciklama'],
                'olculer' => $c['koltuk_olculer'],
                'sayfa' => $c['koltuk'],
                'gorsel' => 'ikisi',
                'ozet' => $c['ozet'],
            ],
        ];
    }

    /**
     * Tek bir urunu olusturur/gunceller.
     *
     * @param  array<string, mixed>  $item
     * @return array{0: string, 1: string}
     */
    private function importOne(array $item, string $source, ?Brand $brand, bool $dry): array
    {
        $images = $this->pageImages($source, (int) $item['sayfa']);

        if ($images === []) {
            return ['yok', $item['ad']];
        }

        // Nesne numarasina gore ilk ve son fotograf. Hangisinin sol/sag sayfa
        // oldugu PDF'ten PDF'e degistigi icin karar cagiran tarafta verilir.
        $first = $images[0];
        $last = $images[count($images) - 1];

        $picked = match ($item['gorsel']) {
            'ilk' => [$first],
            'son' => [$last],
            default => array_values(array_unique([$last, $first])),
        };

        $slug = Str::slug($item['ad']);
        $existing = Product::where('slug', $slug)->first();

        if ($dry) {
            $this->line(sprintf(
                '  %-38s %-26s %d görsel',
                $item['ad'],
                $item['kategori'],
                count($picked)
            ));

            return [$existing ? 'guncel' : 'yeni', ''];
        }

        $category = Category::where('slug', $item['kategori'])->first();

        $stored = [];

        foreach ($picked as $index => $path) {
            $target = $this->storeImage($path, $slug, $index);

            if ($target !== null) {
                $stored[] = $target;
            }
        }

        if ($stored === []) {
            return ['yok', $item['ad']];
        }

        $product = Product::updateOrCreate(['slug' => $slug], [
            'category_id' => $category?->id,
            'brand_id' => $brand?->id,
            'name' => ['tr' => $item['ad']],
            'short_description' => ['tr' => $item['ozet'] ?? Str::limit(strip_tags($item['aciklama']), 140)],
            'description' => ['tr' => $item['aciklama']],
            'specs' => $item['olculer'] ?: null,
            'price' => 0,
            'manage_stock' => false,
            'stock' => 0,
            'cover' => $stored[0],
            'hover_image' => $stored[1] ?? null,
            'is_active' => false,
            'published_at' => null,
            'meta' => ['kaynak' => 'Oben 2026 Kataloğu', 'katalog_sayfa' => $item['sayfa']],
        ]);

        return [$product->wasRecentlyCreated ? 'yeni' : 'guncel', ''];
    }

    /**
     * Bir katalog sayfasindaki fotograflar, nesne numarasina gore sirali.
     *
     * @return array<int, string>
     */
    private function pageImages(string $source, int $page): array
    {
        $dir = sprintf('%s/sayfa-%02d', rtrim($source, '/\\'), $page);

        if (! is_dir($dir)) {
            return [];
        }

        $files = glob($dir.'/*.jpg') ?: [];

        usort($files, fn ($a, $b) => (int) basename($a, '.jpg') <=> (int) basename($b, '.jpg'));

        return $files;
    }

    /** Fotografi magaza medyasina kopyalar, goreli yolu doner. */
    private function storeImage(string $path, string $slug, int $index): ?string
    {
        $relative = sprintf('urunler/oben/%s-%d.jpg', $slug, $index);
        $target = public_path($relative);

        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }

        // Katalog fotograflari 1700px genislikte; vitrin icin 1400px yeter
        $image = @imagecreatefromjpeg($path);

        if ($image === false) {
            return null;
        }

        $width = imagesx($image);

        if ($width > 1400) {
            $scaled = imagescale($image, 1400);

            if ($scaled !== false) {
                imagedestroy($image);
                $image = $scaled;
            }
        }

        $ok = imagejpeg($image, $target, 86);
        imagedestroy($image);

        return $ok ? $relative : null;
    }

    private function brand(bool $dry): ?Brand
    {
        if ($dry) {
            return null;
        }

        return Brand::updateOrCreate(['slug' => 'oben'], [
            'name' => ['tr' => 'Oben', 'en' => 'Oben', 'bg' => 'Oben', 'el' => 'Oben'],
            'is_active' => true,
            'position' => 10,
        ]);
    }

    /** Katalogdaki takimlar icin eksik alt kategorileri acar. */
    private function ensureCategories(bool $dry): void
    {
        $needed = [
            'yatak-odasi-takimlari' => ['yatak-odasi', ['tr' => 'Yatak Odası Takımları', 'en' => 'Bedroom Sets', 'bg' => 'Спални комплекти', 'el' => 'Σετ Υπνοδωματίου']],
            'yemek-odasi-takimlari' => ['yemek-odasi', ['tr' => 'Yemek Odası Takımları', 'en' => 'Dining Sets', 'bg' => 'Трапезни комплекти', 'el' => 'Σετ Τραπεζαρίας']],
            'kose-takimlari' => ['oturma-odasi', ['tr' => 'Köşe Takımları', 'en' => 'Corner Sofas', 'bg' => 'Ъглови дивани', 'el' => 'Γωνιακοί Καναπέδες']],
        ];

        foreach ($needed as $slug => [$parentSlug, $name]) {
            if ($dry) {
                continue;
            }

            $parent = Category::where('slug', $parentSlug)->first();

            if (! $parent) {
                continue;
            }

            Category::updateOrCreate(['slug' => $slug], [
                'parent_id' => $parent->id,
                'name' => $name,
                'is_active' => true,
                // Urunler fiyat girilip yayina alinana kadar kategori de menude
                // gorunmesin: bos bir kategori sayfasi kotu bir ilk izlenim.
                'show_in_menu' => false,
                'position' => 90,
            ]);
        }
    }
}
