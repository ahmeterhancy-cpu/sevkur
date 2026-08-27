# Sevkur Mobilya — Kurulum ve Yayına Alma

Laravel 13 + Filament 5 üzerine kurulu, dört dilli (TR / EN / BG / EL) mobilya
e-ticaret sitesi. Ödeme PayTR, vitrin para birimleri TRY / EUR / BGN.

---

## 1. Yerelde çalıştırma

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve --port=8137
```

- Vitrin: <http://localhost:8137>
- Yönetim paneli: <http://localhost:8137/admin>
  - `admin@sevkurmobilya.com` / `Sevkur2026!` — **canlıya çıkmadan önce değiştirin.**
- Demo müşteri: `musteri@example.com` / `password`

Yerelde veritabanı SQLite'tır (`database/database.sqlite`), ayrıca kurulum
gerektirmez.

---

## 2. Turhost / cPanel'e yükleme

### 2.1 Gereksinimler

| Şey | Değer |
| --- | --- |
| PHP | 8.2 veya üzeri (8.3 önerilir) |
| Eklentiler | `pdo_mysql`, `mbstring`, `openssl`, `curl`, `gd`, `zip`, `intl`, `fileinfo` |
| Veritabanı | MySQL 8 / MariaDB 10.4+ |

### 2.2 Dosyaları yükleme

Proje **tek klasör** kurulumuna göre hazırlandı: deponun kendisi `public_html`
olur. Kök dizindeki `.htaccess` kaynak dosyaları kapatır ve istekleri
`public/` altına yönlendirir.

1. Yerelde `npm run build` çalıştırın — `public/build` klasörü oluşur ve
   **depoya dahildir**, sunucuda Node.js gerekmez.
2. `composer install --no-dev --optimize-autoloader` çalıştırıp `vendor/`
   klasörünü de yükleyin (Turhost'ta composer çalıştırmak zorunda kalmayın).
3. Tüm dosyaları `public_html` içine açın.
4. Klasör izinleri: `storage/` ve `bootstrap/cache/` **755** (gerekirse 775),
   içindekiler yazılabilir olmalı.

### 2.3 .env

```dotenv
APP_NAME="Sevkur Mobilya"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sevkurmobilya.com
APP_KEY=            # php artisan key:generate ile üretin

APP_LOCALE=tr
APP_TIMEZONE=Europe/Istanbul

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=kullanici_sevkur
DB_USERNAME=kullanici_sevkur
DB_PASSWORD=********

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=mail.sevkurmobilya.com
MAIL_PORT=587
MAIL_USERNAME=info@sevkurmobilya.com
MAIL_PASSWORD=********
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@sevkurmobilya.com

PAYTR_MERCHANT_ID=
PAYTR_MERCHANT_KEY=
PAYTR_MERCHANT_SALT=
PAYTR_TEST_MODE=1
PAYTR_ALLOW_SIMULATION=false
```

> `PAYTR_ALLOW_SIMULATION=false` canlıda **şart**: kimlik bilgileri eksik
> kalırsa test ödeme ekranı yerine açık bir hata gösterilir, yanlışlıkla
> "ödendi" işaretlenen sipariş oluşmaz.

### 2.4 İlk kurulum komutları

SSH varsa:

```bash
php artisan migrate --force
php artisan db:seed --force        # yalnızca ilk kurulumda, demo içerik ister
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

SSH yoksa cPanel → **Terminal** ya da **Setup PHP App**'in komut alanını
kullanın.

`storage:link` sembolik bağ oluşturamıyorsa (bazı paylaşımlı sunucularda
kapalıdır) `public/storage` klasörünü elle oluşturup `storage/app/public`
içeriğini oraya kopyalayın; yüklenen görseller oradan sunulur.

### 2.5 Zamanlanmış görevler (cron)

cPanel → **Cron Jobs**, dakikada bir:

```
* * * * * /usr/local/bin/php /home/KULLANICI/public_html/artisan schedule:run >/dev/null 2>&1
```

Bu iki işi yürütür:

- **09:15** — TCMB kurlarından EUR/BGN güncellemesi (`kur:guncelle`)
- **Saat başı** — 24 saattir ödemesi alınmayan siparişleri iptal edip stoğu
  geri yükleme

### 2.6 PayTR bildirim adresi

PayTR mağaza panelinde **Bildirim URL** alanına şunu girin:

```
https://sevkurmobilya.com/paytr/callback
```

Bu uç CSRF dışında tutulmuştur ve imza (hash) doğrulaması yapar; imzası
tutmayan istekler `400` ile reddedilir.

---

## 3. Para birimi mantığı

- Bütün fiyatlar veritabanında **TRY** tutulur.
- Vitrinde EUR ve BGN gösterilir; kurlar TCMB'nin günlük XML'inden alınır.
- **TCMB Bulgar Levası yayınlamaz.** Leva 1999'dan beri para kurulu rejimiyle
  euroya sabit kurla bağlıdır (1 EUR = 1,95583 BGN); BGN bu sabit üzerinden
  euro kurundan türetilir.
- Yönetim panelinden her para birimi için **manuel kur** ve **kur marjı (%)**
  girilebilir. Manuel kur otomatik kuru ezer.
- **Tahsilat her zaman TRY üzerinden yapılır** (PayTR başka para birimi kabul
  etmez). Diğer kurlar bilgi amaçlıdır ve müşteriye bu not gösterilir.

---

## 4. Canlıya çıkmadan önce yapılacaklar listesi

- [ ] Yönetici şifresini değiştirin, demo müşteri hesabını silin
- [ ] Mağaza Ayarları → adres, telefon, e-posta, çalışma saatleri, sosyal medya
- [ ] Logo ve favicon yükleyin
- [ ] Demo ürünleri gerçek ürünlerle değiştirin, demo görselleri kaldırın
- [ ] **Mesafeli satış sözleşmesi ve gizlilik politikası metinleri yer
      tutucudur** — hukuk danışmanınızla güncelleyin
- [ ] Kargo yöntemlerinin ücretlerini ve ücretsiz kargo sınırını ayarlayın
- [ ] PayTR kimlik bilgilerini girin, `PAYTR_TEST_MODE=1` ile bir test
      siparişi geçin, sonra `0` yapın
- [ ] `APP_DEBUG=false` olduğunu doğrulayın
- [ ] Google Search Console'a `https://sevkurmobilya.com/sitemap.xml` ekleyin

---

## 5. Bilinerek yapılmayanlar

- Kapıda ödeme / havale seçeneği yok (yalnızca PayTR istendi)
- Çoklu depo, kargo firması API entegrasyonu, fatura (e-arşiv) entegrasyonu yok
- Ürün soru-cevap, stok bildirimi ("gelince haber ver") yok
- Blog yorumları yok
