# Sevkur Mobilya — Kurulum ve Yayına Alma

Laravel 13 + Filament 5 üzerine kurulu, dört dilli (TR / EN / BG / EL) mobilya
e-ticaret sitesi. Ödeme PayTR, vitrin para birimleri TRY / EUR.

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

Veritabanı MySQL'dir (`DB_CONNECTION=mysql`). Hızlı bir kurulum için SQLite
de yeterlidir (`DB_CONNECTION=sqlite`, diğer `DB_` satırları gereksiz) ama
canlı ortam MySQL olduğu için geliştirmeyi de MySQL ile yapmak, yalnızca
MySQL'de çıkan hataları (sıralama, FULLTEXT, kesin tarih karşılaştırmaları)
erken yakalar.

Elinizde SQLite ile dolmuş bir katalog varsa veriyi taşıyın:

```bash
php artisan migrate --force
php artisan veri:tasi --kaynak=sqlite --hedef=mysql --kuru   # önce sayıları görün
php artisan veri:tasi --kaynak=sqlite --hedef=mysql
```

Komut şemayı değil **satırları** kopyalar; hedefte aynı tablolar önce
boşaltılır, yani iki kez çalıştırılırsa satırlar ikilenmez. Oturum, önbellek
ve kuyruk tabloları taşınmaz.

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
5. **Ürün fotoğraflarını ayrıca yükleyin.** `public/urunler` depoya dahil
   değildir (panelden yüklenen medya sunucuda kalsın diye). Yerelde
   **1715 dosya / 272 MB** var, markalara göre ayrılmış:

   | Klasör | Dosya | Boyut | Kaynak |
   | --- | --- | --- | --- |
   | `urunler/oben` | 68 | 15 MB | müşterinin kendi kataloğu |
   | `urunler/omairat` | 103 | 9,6 MB | müşterinin kendi kataloğu |
   | `urunler/arno` | 754 | 116 MB | markanın kendi sitesi — telif |
   | `urunler/vespero` | 382 | 77 MB | markanın kendi sitesi — telif |
   | `urunler/sude` | 180 | 27 MB | markanın kendi sitesi — telif |
   | `urunler/ravizza` | 162 | 18 MB | markanın kendi sitesi — telif |
   | `urunler/asse` | 59 | 12 MB | markanın kendi sitesi — telif |

   Telifli sütunundaki beş marka için 4. bölümdeki karar verilmeden
   fotoğraflarını sunucuya koymayın.

### 2.2.1 Depo gizli — cPanel Git deploy

Depo (`ahmeterhancy-cpu/sevkur`) **gizlidir**. cPanel'in "Git Version
Control" özelliği gizli depoyu parolasız çekemez. İki yol var:

- Depoyu genele alın (GitHub → Settings → Danger Zone → Change visibility).
  Diğer Turhost projelerinde (zeys, necipoglu, ayparcasi) bu yapıldı.
- Ya da cPanel'de SSH anahtarı üretip (SSH Access → Manage SSH Keys) genel
  anahtarı GitHub'da depo ayarlarına **Deploy key** olarak ekleyin, klonu
  `git@github.com:ahmeterhancy-cpu/sevkur.git` adresinden yapın.

Git deploy kullanmayacaksanız depoyu zip olarak indirip File Manager ile
açmak da yeterlidir; `vendor/` zaten ayrı yükleniyor.

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
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan yayin:kontrol          # eksik ayar kaldı mı
```

`yayin:kontrol` aşağıdaki 4. bölümün makine tarafından denetlenebilen
kısmıdır: APP_DEBUG, APP_ENV, APP_URL, veritabanı sürücüsü, e-posta
sürücüsü, PayTR bilgileri, yasal şirket bilgileri, yönetici parolası, logo,
fiyatsız ürün ve boş kategori. "ENGEL" başlığı altındakiler düzeltilmeden
canlıya çıkılmamalı; komut bu durumda sıfırdan farklı kodla biter, yani
dağıtım betiğine de konabilir.

SSH yoksa cPanel → **Terminal** ya da **Setup PHP App**'in komut alanını
kullanın.

**`storage:link` gerekmez.** Tüm medya (demo görselleri ve panelden
yüklenenler) `public/` altında, `medya` diskinde durur — paylaşımlı
sunucularda sembolik bağ çoğu zaman kapalı olduğu için bu yol seçildi.
Panelden yükleme yapılabilmesi için şu klasörlerin yazılabilir (755) olması
yeterlidir: `public/urunler`, `public/afisler`, `public/blog`, `public/marka`,
`public/markalar`, `public/slider`, `public/kategoriler`. Yoksa panel ilk
yüklemede kendisi oluşturur.

Bu klasörler depoya dahil DEĞİLDİR; sunucudaki yüklemeler bir sonraki
kurulumda silinmez. `public/demo` ise depoyla birlikte gelir.

### 2.5 Zamanlanmış görevler (cron)

cPanel → **Cron Jobs**, dakikada bir:

```
* * * * * /usr/local/bin/php /home/KULLANICI/public_html/artisan schedule:run >/dev/null 2>&1
```

Bu iki işi yürütür:

- **09:15** — TCMB kurundan EUR güncellemesi (`kur:guncelle`)
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
- Vitrinde EUR gösterilir; kur TCMB'nin günlük XML'inden alınır.
- **Bulgar Levası (BGN) kaldırıldı.** Bulgarca mağaza da euro gösterir.
- Yönetim panelinden her para birimi için **manuel kur** ve **kur marjı (%)**
  girilebilir. Manuel kur otomatik kuru ezer.
- **Tahsilat her zaman TRY üzerinden yapılır** (PayTR başka para birimi kabul
  etmez). Diğer kurlar bilgi amaçlıdır ve müşteriye bu not gösterilir.

---

## 4. Canlıya çıkmadan önce yapılacaklar listesi

- [ ] **601 ürünün hiçbirinde fiyat yok.** Katalog aktarımlarında fiyat
      gelmedi; `price` alanı hepsinde 0. Fiyatsız ürün sepete eklenemez,
      yani site bu haliyle **hiçbir şey satamaz**. Fiyatlar panelden tek tek
      ya da Ürünler listesinde toplu düzenleme ile girilebilir.

- [ ] Yönetici şifresini değiştirin (**panelde sağ üst → Profil**, yani
      `/admin/profile`; SSH gerekmez), demo müşteri hesabını silin
- [x] ~~Mağaza Ayarları → adres, telefon, e-posta, çalışma saatleri~~ (girildi)
- [ ] Sosyal medya bağlantılarını gerçek hesaplarla değiştirin (şu an örnek)
- [ ] Logo ve favicon yükleyin
- [x] ~~Demo ürünleri kaldırın~~ (2026-11-01: `php artisan demo:temizle`
      ile 47 demo ürün ve 6 uydurma marka silindi; katalogda yalnızca Oben,
      Omairat ve Vespero ürünleri var). Fotoğraf dosyaları
      `public/demo/urunler` altında **bilerek bırakıldı**: `ProductSeeder` ve
      panel disk testleri onları düzenek olarak kullanıyor. Canlıya
      yüklemeden önce `php artisan demo:temizle --gorseller` ile dosyaları da
      atın — Wikimedia Commons fotoğrafları sunucuda durmasın.
- [ ] **Katalogdan aktarılan ürünlerin fotoğraf hakkını netleştirin.**
      Oben ve Omairat ürünleri sizin verdiğiniz katalog PDF'lerinden geldi.
      Vespero, Sude Collection, Ravizza Yatak, Asse Koltuk ve Arno ürünleri
      ise kendi web sitelerinden/kataloglarından alındı — o fotoğraflar ilgili
      markaların telifli ürün çekimleridir ve yazılı izin olmadan kendi
      mağazanızda yayınlamak risklidir. Bayilik/izin olmayan markaları
      `php artisan marka:pasif <slug>` ile yayından alın (birden fazla slug
      verilebilir, `--geri` ile iade edilir). Slug'lar: `vespero`, `sude`,
      `ravizza`, `asse`, `arno`.

- [ ] **Kozan Grup ürünleri EKLENMEDİ.** kozangrup.com.tr sitesinde ürün
      sayfası yok; ürünler yalnızca e-katalog PDF'lerinde duruyor. Beş
      katalogun üçü (2026, 2025, Valeron-Lizbon) **metin katmanı olmayan düz
      görsel** — ürün adı fotoğrafın içine gömülü, OCR olmadan çıkmıyor.
      Metni olan ikisinde (Bohem, Tekiller) ad ve ölçüler harf harf aralıklı
      yazıldığı için ayrıştırma kısmi ve hatalı kaldı (adlar bitişiyor,
      ölçüler kayıyor). Yanlış veriyle katalog doldurmak yerine bekletildi.
      Çözüm: Kozan'dan ürün listesi (Excel/CSV) isteyin — katalog sayfa
      fotoğrafları çıkarılmış durumda, liste gelince eşleştirilebilir.

- [ ] **Yasal metinler TASLAKTIR — hukuk danışmanınıza okutun.** 6502 sayılı
      Tüketicinin Korunması Hakkında Kanun, Mesafeli Sözleşmeler Yönetmeliği
      ve KVKK esas alınarak dört dilde yazıldı: Mesafeli Satış Sözleşmesi,
      KVKK Aydınlatma Metni, Çerez Politikası, Gizlilik Politikası, İade ve
      Değişim. Kaynakları `database/data/legal/` altındadır; panelden
      Sayfalar bölümünden de düzenlenebilirler.
- [ ] **Yasal şirket bilgilerini girin** — Mağaza Ayarları → İletişim →
      Yasal bilgiler: ticaret unvanı, MERSİS, vergi dairesi/numarası, site
      adresi. Bu alanlar sözleşmelerin içine kendiliğinden yerleşir;
      girilmezse metinde `[MERSİS numarası girilmedi]` gibi görünürler.
- [ ] **Çerez Politikası yalnızca zorunlu çerezleri anlatıyor** (oturum ve
      CSRF). Google Analytics, Meta Pixel ya da benzeri bir ölçüm kodu
      eklerseniz metin güncellenmeli ve ziyaretçiden ayrıca onay alınmalı.
- [ ] Kargo yöntemlerinin ücretlerini ve ücretsiz kargo sınırını ayarlayın
- [ ] PayTR kimlik bilgilerini girin, `PAYTR_TEST_MODE=1` ile bir test
      siparişi geçin, sonra `0` yapın
- [ ] `APP_DEBUG=false` ve `MAIL_MAILER=smtp` olduğunu doğrulayın
      (`php artisan yayin:kontrol` ikisini de denetler; `log` sürücüsünde
      sipariş onay e-postası müşteriye **gönderilmez**)
- [ ] Google Search Console'a `https://sevkurmobilya.com/sitemap.xml` ekleyin

---

## 5. Bilinerek yapılmayanlar

- Kapıda ödeme / havale seçeneği yok (yalnızca PayTR istendi)
- Çoklu depo, kargo firması API entegrasyonu, fatura (e-arşiv) entegrasyonu yok
- Ürün soru-cevap, stok bildirimi ("gelince haber ver") yok
- Blog yorumları yok
