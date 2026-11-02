<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bir veritabanindaki satirlari digerine kopyalar.
 *
 * Gelistirme SQLite ile yurudu, Turhost'ta MySQL var. Aktarilan 601 urunu ve
 * fotograf yollarini elle girmek mumkun degil; bu komut semayi degil VERIYI
 * tasir, yani hedefte once "php artisan migrate" calismis olmali.
 *
 *   php artisan migrate --force
 *   php artisan veri:tasi --kaynak=sqlite --hedef=mysql --kuru
 *   php artisan veri:tasi --kaynak=sqlite --hedef=mysql
 *
 * Hedefteki ayni tablolar ONCE BOSALTILIR: komut iki kez calistirilirsa
 * satirlar ikilenmez.
 */
class CopyDatabase extends Command
{
    protected $signature = 'veri:tasi
                            {--kaynak=sqlite : Okunacak bağlantı}
                            {--hedef=mysql : Yazılacak bağlantı}
                            {--dosya= : Kaynak SQLite dosyası (varsayılan database/database.sqlite)}
                            {--kuru : Hiçbir şey yazma, sadece sayıları göster}';

    protected $description = 'Satırları bir veritabanından diğerine kopyalar (şemayı değil, veriyi)';

    /**
     * Kopyalanmayan tablolar.
     *
     * migrations hedefte zaten dolu; oturum, onbellek ve kuyruk ise gecici
     * veri — tasinmasi yanlis olur, eski oturumlar hedefte canlanir.
     */
    private const SKIP = [
        'migrations', 'sessions', 'cache', 'cache_locks',
        'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens',
    ];

    /** Tek seferde kac satir yazilir; buyuk paketler MySQL paket sinirini asar. */
    private const CHUNK = 250;

    public function handle(): int
    {
        $kaynak = (string) $this->option('kaynak');
        $hedef = (string) $this->option('hedef');
        $kuru = (bool) $this->option('kuru');

        if ($kaynak === $hedef) {
            $this->error('Kaynak ve hedef aynı bağlantı.');

            return self::FAILURE;
        }

        // .env hedefe cevrildiginde DB_DATABASE de degisir ve "sqlite"
        // baglantisi var olmayan bir dosyayi acmaya calisir; yolu acikca veriyoruz.
        if (config("database.connections.$kaynak.driver") === 'sqlite') {
            $dosya = $this->option('dosya') ?: database_path('database.sqlite');

            if (! is_file($dosya)) {
                $this->error("SQLite dosyası bulunamadı: $dosya");

                return self::FAILURE;
            }

            config(["database.connections.$kaynak.database" => $dosya]);
            DB::purge($kaynak);
        }

        $from = DB::connection($kaynak);
        $to = DB::connection($hedef);

        // Tablo listesi KAYNAKTAN okunur: MySQL'de getTableListing() root
        // kullanicisina sunucudaki tum veritabanlarini "sema.tablo" olarak
        // dondurur, yani iki listeyi kesistirmek bos sonuc verir. Hedefte
        // var mi sorusunu hasTable() dogru veritabaninda yanitlar.
        $hedefSema = Schema::connection($hedef);

        $tablolar = array_values(array_filter(
            Schema::connection($kaynak)->getTableListing(schemaQualified: false),
            fn (string $tablo) => ! in_array($tablo, self::SKIP, true) && $hedefSema->hasTable($tablo)
        ));

        sort($tablolar);

        if ($tablolar === []) {
            $this->error('Ortak tablo bulunamadı. Hedefte "php artisan migrate" çalıştı mı?');

            return self::FAILURE;
        }

        $satirlar = [];
        $toplam = 0;

        // Yabanci anahtarlar tablolari dogru sirada yazmayi zorunlu kilar;
        // sirayi cozmek yerine denetimi kopyalama boyunca kapatiyoruz.
        $this->withoutForeignKeys($to, $kuru, function () use ($from, $to, $tablolar, $kuru, &$satirlar, &$toplam) {
            foreach ($tablolar as $tablo) {
                $adet = $from->table($tablo)->count();
                $satirlar[] = [$tablo, $adet, $kuru ? '—' : $to->table($tablo)->count()];
                $toplam += $adet;

                if ($kuru || $adet === 0) {
                    continue;
                }

                $to->table($tablo)->delete();

                $from->table($tablo)->orderBy(
                    Schema::connection($from->getName())->hasColumn($tablo, 'id') ? 'id' : $this->firstColumn($from, $tablo)
                )->chunk(self::CHUNK, function ($rows) use ($to, $tablo) {
                    $to->table($tablo)->insert(array_map(
                        fn ($row) => (array) $row,
                        $rows->all()
                    ));
                });
            }
        });

        $this->table(['Tablo', 'Kaynak', 'Hedef (önce)'], $satirlar);

        $this->info(($kuru ? 'KURU ÇALIŞMA — ' : '')
            .number_format($toplam).' satır, '.count($tablolar).' tablo'
            .($kuru ? ' kopyalanacak.' : ' kopyalandı.'));

        if (! $kuru) {
            $this->warn('Bağlantıyı .env içinde değiştirmeyi ve "php artisan optimize:clear" çalıştırmayı unutmayın.');
        }

        return self::SUCCESS;
    }

    /** Tablonun ilk sutunu — id'si olmayan ara tablolari siralamak icin. */
    private function firstColumn($connection, string $table): string
    {
        return Schema::connection($connection->getName())->getColumnListing($table)[0];
    }

    private function withoutForeignKeys($connection, bool $kuru, callable $callback): void
    {
        if ($kuru) {
            $callback();

            return;
        }

        $schema = Schema::connection($connection->getName());
        $schema->disableForeignKeyConstraints();

        try {
            $callback();
        } finally {
            $schema->enableForeignKeyConstraints();
        }
    }
}
