# Sevkur Mobilya

Dört dilli (TR / EN / BG / EL) mobilya e-ticaret sitesi ve yönetim paneli.

- **Yığın:** Laravel 13, Filament 5, Tailwind 4, sıfır JS kütüphanesi
- **Ödeme:** PayTR iFrame API (3D Secure)
- **Para birimi:** TRY taban; EUR ve BGN vitrinde gösterilir, kurlar TCMB'den
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
- **Tahsilat:** her zaman TRY. EUR/BGN yalnızca vitrin gösterimidir ve müşteriye
  bu not açıkça yazılır.
- **İkonlar:** `<x-ui.icon>` bileşeni kullanılır — `<x-icon>` adı Filament'in
  getirdiği `blade-ui-kit` paketine aittir, çakışır.
