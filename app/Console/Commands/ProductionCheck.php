<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Canliya cikmadan once bakilacaklar.
 *
 * Bunlarin cogu tek satirlik ayar ama unutuldugunda pahaliya mal oluyor:
 * APP_DEBUG acik kalirsa hata ekrani musteriye .env degerlerini gosterir,
 * MAIL_MAILER=log kalirsa siparis onay postasi hic gonderilmez, PayTR test
 * kipinde kalirsa tahsilat yapilmaz. Liste tek yerde dursun ki deploy
 * oncesi bir komutla gecilebilsin.
 *
 *   php artisan yayin:kontrol
 */
class ProductionCheck extends Command
{
    protected $signature = 'yayin:kontrol {--tam : Uyarıları da hata say}';

    protected $description = 'Canlıya çıkmadan önce ayarları ve içeriği denetler';

    /** @var array<int, array{0: string, 1: string}> */
    private array $hatalar = [];

    /** @var array<int, array{0: string, 1: string}> */
    private array $uyarilar = [];

    public function handle(): int
    {
        $this->ortam();
        $this->veritabani();
        $this->eposta();
        $this->odeme();
        $this->yasal();
        $this->icerik();
        $this->guvenlik();

        $this->newLine();

        if ($this->hatalar !== []) {
            $this->error('ENGEL — bunlar düzeltilmeden canlıya çıkılmamalı:');
            $this->table(['Konu', 'Durum'], $this->hatalar);
        }

        if ($this->uyarilar !== []) {
            $this->warn('Uyarı — canlıya çıkar ama eksik görünür:');
            $this->table(['Konu', 'Durum'], $this->uyarilar);
        }

        if ($this->hatalar === [] && $this->uyarilar === []) {
            $this->info('Her şey yerinde görünüyor.');

            return self::SUCCESS;
        }

        $this->line(count($this->hatalar).' engel, '.count($this->uyarilar).' uyarı.');

        return $this->hatalar !== [] || ($this->option('tam') && $this->uyarilar !== [])
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function hata(string $konu, string $durum): void
    {
        $this->hatalar[] = [$konu, $durum];
    }

    private function uyari(string $konu, string $durum): void
    {
        $this->uyarilar[] = [$konu, $durum];
    }

    private function ortam(): void
    {
        if (config('app.debug')) {
            $this->hata('APP_DEBUG', 'Açık — hata ekranı müşteriye .env değerlerini gösterir');
        }

        if (config('app.env') !== 'production') {
            $this->hata('APP_ENV', config('app.env').' — "production" olmalı');
        }

        if (blank(config('app.key'))) {
            $this->hata('APP_KEY', 'Boş — php artisan key:generate');
        }

        $url = (string) config('app.url');

        if (! str_starts_with($url, 'https://')) {
            $this->hata('APP_URL', "$url — https olmalı, yoksa bağlantılar ve çerezler kırılır");
        }

        if (str_contains($url, 'localhost') || str_contains($url, '127.0.0.1')) {
            $this->hata('APP_URL', "$url — gerçek alan adı girilmemiş");
        }
    }

    private function veritabani(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->hata('Veritabanı', 'SQLite — paylaşımlı barındırmada eşzamanlı yazmada kilitlenir, MySQL kullanın');
        }
    }

    private function eposta(): void
    {
        $mailer = config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->hata('MAIL_MAILER', "$mailer — sipariş onay e-postası müşteriye GÖNDERİLMEZ");

            return;
        }

        if ($mailer === 'smtp' && blank(config('mail.mailers.smtp.host'))) {
            $this->hata('MAIL_HOST', 'Boş — smtp seçili ama sunucu girilmemiş');
        }
    }

    private function odeme(): void
    {
        foreach (['merchant_id' => 'PAYTR_MERCHANT_ID', 'merchant_key' => 'PAYTR_MERCHANT_KEY', 'merchant_salt' => 'PAYTR_MERCHANT_SALT'] as $key => $isim) {
            if (blank(config("paytr.$key"))) {
                $this->hata($isim, 'Boş — ödeme alınamaz');
            }
        }

        if (config('paytr.test_mode')) {
            $this->hata('PAYTR_TEST_MODE', '1 — gerçek tahsilat yapılmaz');
        }
    }

    private function yasal(): void
    {
        $zorunlu = [
            'legal_company_name' => 'Ticaret unvanı',
            'legal_mersis' => 'MERSİS numarası',
            'legal_tax' => 'Vergi dairesi ve numarası',
        ];

        foreach ($zorunlu as $key => $isim) {
            if (blank(setting($key))) {
                $this->hata($isim, 'Girilmemiş — sözleşmede "[… girilmedi]" olarak görünür');
            }
        }

        $sayfalar = ['mesafeli-satis-sozlesmesi', 'kvkk-aydinlatma-metni', 'cerez-politikasi', 'gizlilik-politikasi'];

        foreach ($sayfalar as $slug) {
            if (! Page::where('slug', $slug)->where('is_active', true)->exists()) {
                $this->hata('Yasal sayfa', "$slug yayında değil");
            }
        }
    }

    private function icerik(): void
    {
        if (blank(setting('logo'))) {
            $this->uyari('Logo', 'Yüklenmemiş — başlıkta site adı yazıyor');
        }

        if (blank(setting('favicon'))) {
            $this->uyari('Favicon', 'Yüklenmemiş');
        }

        $fiyatsiz = Product::where('is_active', true)->where('price', 0)->count();

        if ($fiyatsiz > 0) {
            $this->uyari('Fiyat', "$fiyatsiz yayındaki üründe fiyat yok — sepete eklenemezler");
        }

        $demo = Product::where('cover', 'like', 'demo/%')->count();

        if ($demo > 0) {
            $this->uyari('Demo ürün', "$demo ürün kurulum görselleriyle duruyor — php artisan demo:temizle");
        }

        $bos = Category::where('is_active', true)->where('show_in_menu', true)->get()
            ->filter(fn (Category $c) => Product::where('is_active', true)
                ->whereIn('category_id', $c->descendantIds())->doesntExist())
            ->count();

        if ($bos > 0) {
            $this->uyari('Boş kategori', "$bos kategori menüde ama ürünü yok — tıklayan müşteri boş sayfaya düşer");
        }
    }

    private function guvenlik(): void
    {
        // Kurulum parolasi KURULUM.md'de yazili; degismeden canliya cikarsa
        // panel herkese aciktir.
        $admin = User::where('email', 'admin@sevkurmobilya.com')->first();

        if ($admin && Hash::check('Sevkur2026!', $admin->password)) {
            $this->hata('Yönetici parolası', 'Kurulum parolası değişmemiş — KURULUM.md\'de yazılı');
        }
    }
}
