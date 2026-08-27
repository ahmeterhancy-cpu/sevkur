<?php

namespace App\Console\Commands;

use App\Services\CurrencyService;
use Illuminate\Console\Command;

class RefreshExchangeRates extends Command
{
    protected $signature = 'kur:guncelle';

    protected $description = 'TCMB gunluk kurlarindan EUR/BGN degerlerini gunceller';

    public function handle(CurrencyService $currencies): int
    {
        $this->info('TCMB kurlari cekiliyor…');

        $result = $currencies->refreshRates();

        if ($result['error'] !== null) {
            $this->error('Kur guncellenemedi: '.$result['error']);

            return self::FAILURE;
        }

        $this->info($result['updated'].' para birimi guncellendi, '.$result['skipped'].' atlandi.');

        return self::SUCCESS;
    }
}
