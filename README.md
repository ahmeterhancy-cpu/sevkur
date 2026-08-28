# Sevkur Mobilya

Dört dilli (TR / EN / BG / EL) mobilya e-ticaret sitesi ve yönetim paneli.

- **Yığın:** Laravel 13, Filament 5, Tailwind 4, sıfır JS kütüphanesi
- **Ödeme:** PayTR iFrame API (3D Secure)
- **Para birimi:** TRY taban; EUR vitrinde gösterilir, kur TCMB'den
- **Hedef ortam:** Turhost / cPanel paylaşımlı hosting

Kurulum, yayına alma ve canlıya çıkış listesi için **[KURULUM.md](KURULUM.md)**.

---

## Klasör haritası

```
app/
  Concerns/HasTranslations.php   JSON tabanlı çeviri alanları
  Filament/                      Yönetim paneli (kaynaklar, ayarlar, widget'lar)
  Http/Controllers/              Vitrin denetleyicileri
  Models/                        Katalog, satış ve içerik modelleri
  Services/
    CartService.php              Sepet, kupon, stok kırpma
    OrderService.php             Sipariş oluşturma, stok düşümü/iadesi
    PayTrService.php             Token alma + bildirim imzası doğrulama
    CurrencyService.php          TCMB kuru, marj, biçimleme
    FavoritesService.php         Favoriler ve karşılaştırma
  Support/
    Locales.php                  Dil ve yerelleştirilmiş URL parçaları
    DemoImages.php               Demo SVG yer tutucu üretici
    helpers.php                  localized_route(), money(), setting()…
config/shop.php                  Diller, URL segmentleri, sipariş ayarları
config/paytr.php                 PayTR kimlikleri ve uç noktaları
resources/views/                 Vitrin şablonları (Furnob referanslı)
```

## Günlük işler

```bash
php artisan test              # tüm test paketi
php artisan kur:guncelle      # TCMB kurlarını elle tazele
npm run dev                   # geliştirirken (HMR)
npm run build                 # yayına hazırlık
```

## Önemli tasarım kararları

- **Çeviri:** metin alanları JSON sütunlarda tutulur (`{"tr": "…", "en": "…"}`),
  `$model->t('name')` ile okunur. Eksik dil varsayılana (TR) düşer.
- **URL:** her dilin kendi yol parçaları vardır (`/magaza`, `/en/shop`,
  `/bg/magazin`, `/el/katastima`). Varsayılan dil ön ek almaz.
- **Stok:** sipariş oluşturulurken koşullu `UPDATE … WHERE stock >= ?` ile
  düşülür; yetersizse işlem geri alınır. Ödeme başarısız olursa stok iade edilir.
- **Tahsilat:** her zaman TRY. EUR yalnızca vitrin gösterimidir ve müşteriye
  bu not açıkça yazılır. Bulgarca mağaza da euro gösterir — leva kaldırıldı.
- **İkonlar:** `<x-ui.icon>` bileşeni kullanılır — `<x-icon>` adı Filament'in
  getirdiği `blade-ui-kit` paketine aittir, çakışır.
- **Varyantlar seçenek kombinasyonudur.** Bir varyant serbest bir isim değil,
  `options` sütunundaki `{"renk": "ceviz", "olcu": "3lu"}` eşleşmesidir.
  Eksenler (Renk, Ölçü) `attributes.is_variant` ile işaretlenir; ürünün hangi
  eksenleri sunduğu varyantlarından türetilir, ayrı bir tabloda tutulmaz. Ad boş
  bırakılırsa `ProductVariant::label()` dört dilde üretir ("Ceviz / 3'lü").
  Müşterinin seçimi **sunucuda** varyanta çevrilir; tarayıcıdan gelen
  `variant_id` yalnızca ürüne aitse kabul edilir. Varyantı olan ürün, varyant
  seçilmeden sepete eklenemez — aksi halde stok yanlış havuzdan düşerdi.
  Seçiciler radyo düğmesidir, JS kapalıyken de çalışır.
- **Medya tek diskte.** `medya` diski `public/` kökünü kullanır; hem depoyla
  gelen demo görselleri hem panelden yüklenenler oradadır. Filament tek disk
  bildiği için ikiye bölünürse mevcut görseller panelde görünmez ve kaydetme
  onları siler. `storage:link` gerekmez.
- **Müşteriye görünen her ayar dile göre saklanır.** `setting_t('anahtar')`
  önce `anahtar_en` gibi son ekli kaydı arar, yoksa Türkçeye düşer. Panelde
  hangi alanların dil sekmesi göstereceğini `ManageSettings::LOCALISED_KEYS`
  belirler — yeni bir görünür metin eklerken o listeye de eklenmezse dört
  dilli sitede her dilde Türkçe görünür. Ham `setting()` yalnızca dilden
  bağımsız değerler içindir (logo, telefon, kargo sınırı).

## Ana sayfa nasıl yönetilir

Tamamı panelden; Blade'de sabit metin yok.

| Bölüm | Nereden |
|---|---|
| Üstteki slider | **Slider** kaynağı (görsel, başlık, buton, sıra) |
| Ara ve geniş afişler | **Afişler** — `home_mid`, `home_grid`, `home_wide` yerleşimi |
| Öne çıkan kategoriler | **Kategoriler** → "öne çıkar" + sıra |
| Çok satanlar | Otomatik: `sold_count` sıralaması |
| Haftanın fırsatları | Ürünün üstü çizili fiyatı + indirim bitiş tarihi |
| Yeni gelenler | Ürün → "yeni" bayrağı |
| Blog | **Yazılar** (son 3 yayınlanmış) |
| Markalar | **Markalar** (aktif, sıraya göre) |
| Bölüm başlıkları | **Ayarlar → Ana sayfa**, dört dilde ayrı |

Bir bölümün verisi yoksa o bölüm sayfada hiç görünmez. Bölümlerin **sırası**
Blade'de sabittir — panelden değiştirilemez.

### Menü

Başlık menüsü **Menü** ekranından yönetilir: sıra sürükle-bırak, etiket dört
dilde, öğeler yayından kaldırılabilir. Kategori seçtiğinizde etiket kendiliğinden
dolar. Bir öğe kategoriye
bağlandığında (`type: category`) alt menüsü o kategorinin alt kategorilerinden
**canlı** okunur — yeni bir alt kategori açtığınızda menüyü ayrıca düzenlemek
gerekmez. Aynı liste hem masaüstü şeridini hem mobil paneli besler.

Masaüstü menü şeridi **yalnızca kategorilerden** oluşur. Ana Sayfa (logo),
Mağaza, Blog, İletişim, Markalar ve Hakkımızda oradan çıkarıldı — hepsi alt
bilgide, Ana Sayfa da logoda. İstenirse Menü Öğeleri ekranından geri
eklenebilir.

Mobil panelde tam liste durur (Ana Sayfa, Mağaza, kategoriler, Blog,
İletişim, sipariş takibi, hesap): dikey listede yer sorunu yok ve mobilde
başka gezinme yolu bulunmuyor.

## Bu projede tökezlenen tuzaklar

Hepsi bir testle kapatıldı; yine de bilerek dolaşmakta fayda var.

| Tuzak | Belirti | Koruma |
| --- | --- | --- |
| Blade yorumu içine `@php`/`@endphp` yazmak | Yorumlar ham PHP blokları çözüldükten **sonra** silinir; aradaki HTML PHP'nin içine yutulur, sayfa 500 verir | `BladeCompilationTest` her şablonu derleyip söz dizimini denetler |
| Aynı dil dosyasında yinelenen anahtar | PHP hata vermez, sessizce sonuncuyu kullanır (site başlığı "Marka" oldu) | `TranslationTest` |
| Kalıcı önbelleğe Eloquent nesnesi yazmak | `CACHE_STORE=database` iken geri okununca `__PHP_Incomplete_Class`, ikinci istekten sonra her sayfa 500 | `CurrencyTest::test_pages_still_render_with_a_persistent_cache_store` |
| Markdown mail bileşenli şablonu `view:` ile vermek | "No hint path defined for [mail]" — e-posta hiç gitmez, hata `try/catch` içinde kaybolur | `OrderMailTest` + gönderim sonucu sipariş geçmişine yazılır |
| Filament'te JSON sütunu `formatStateUsing` ile basmak | Değer her dil için bir kez tekrarlar ("Ergene Raf, Ergene Raf, …") | `state()` kullanılır |
| `Vite::fonts()` çağrısını unutmak | `@font-face` üretilir ama sayfaya bağlanmaz, metin sistem fontuna düşer | Düzende çağrılıyor |
| `.reveal { opacity: 0 }` | JS çalışmazsa sayfanın büyük bölümü görünmez | Gizleme `.js` sınıfına bağlandı |

## Demo görselleri

Ürün ve kategori görselleri `public/demo/urunler` altındaki fotoğraflardan
gelir. Bunlar **Wikimedia Commons**'tan indirilir:

```bash
php tools/fetch-demo-photos.php          # tüm türler
php tools/fetch-demo-photos.php sofa     # tek tür
```

Betik yalnızca ticari kullanıma açık lisansları (kamu malı, CC0, CC BY,
CC BY-SA) kabul eder; NC/ND lisanslıları eler. Commons'taki mobilya
fotoğraflarının çoğu amatör oda çekimi olduğundan her aday indirilip **kenar
şeridi** ölçülür — zemin açık ve tekdüze değilse eleniyor, böylece yalnızca
stüdyo/kesim çekimleri kalıyor.

Kaynak ve lisans bilgileri `KAYNAKLAR.json` içinde tutulur ve alt bilgideki
**Görsel Kaynakları** sayfasında yayımlanır (CC BY'ın kaynak gösterme şartı).

Klasör boşsa katalog otomatik olarak `DemoImages` SVG çizimlerine döner —
uygulama görsel olmadan da çalışır.

### Hero, tanıtım şeritleri ve blog kapakları

```bash
php tools/build-demo-scenes.php
```

Bu alanlar hazır sahne fotoğrafı **indirmez**; indirmeyi denedim ve Commons'ın
"oturma odası" kategorileri amatör, kalabalık, markanın sade diline zıt kareler
verdi — hero sitenin en marka-tanımlayıcı alanı olduğu için bu, mevcut soyut
panellerden kötüydü.

Onun yerine betik, **zaten seçilmiş ürün fotoğraflarından** bölünmüş kompozisyon
üretiyor: solda markanın krem zemini (yazı buraya oturur), sağda tam boy
fotoğraf, ikisi arasında yumuşak geçiş. Yazının okunması için Blade tarafında
`contrast_scrim()` ile ayrıca bir perde konuyor.

> Bu fotoğraflar **tanıtım içindir**; gösterilen mobilyalar satılan ürünler
> değildir. Canlıya çıkmadan önce gerçek ürün fotoğraflarıyla değiştirin.

## Doğrulama

```bash
php artisan test          # birim + özellik testleri
php tools/smoke.php       # gerçek veritabanıyla 69 adres, 4 dil
```

`tools/smoke.php` yalnızca durum koduna bakmaz; yanıt gövdesinde PHP uyarısı,
yığın izi ve çevrilmemiş dil anahtarı arar.
