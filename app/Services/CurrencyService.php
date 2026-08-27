<?php

namespace App\Services;

use App\Models\Currency;
use App\Support\Locales;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Vitrin para birimi.
 *
 * Butun fiyatlar veritabaninda TRY tutulur. Diger para birimleri TCMB'nin
 * gunluk kur XML'inden turetilir ve gosterim amaclidir; tahsilat (PayTR)
 * her zaman TRY uzerinden yapilir.
 */
class CurrencyService
{
    public const SESSION_KEY = 'display_currency';

    /** @var array<string, Currency>|null */
    private ?array $currencies = null;

    /**
     * @return array<string, Currency>
     *
     * Yalnizca istek suresince bellekte tutulur. Kalici onbellege (database
     * store) ALINMAZ: Eloquent nesnelerini serilestirip geri okumak
     * __PHP_Incomplete_Class uretiyor ve tablo zaten uc satir.
     */
    public function all(): array
    {
        if ($this->currencies !== null) {
            return $this->currencies;
        }

        return $this->currencies = Currency::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get()
            ->keyBy('code')
            ->all();
    }

    public function base(): Currency
    {
        foreach ($this->all() as $currency) {
            if ($currency->is_base) {
                return $currency;
            }
        }

        return $this->fallback();
    }

    /** Veritabani hazir degilse (kurulum aninda) calisan yedek. */
    private function fallback(): Currency
    {
        return new Currency([
            'code' => 'TRY',
            'symbol' => '₺',
            'name' => ['tr' => 'Turk Lirasi'],
            'rate' => 1,
            'is_base' => true,
            'is_active' => true,
            'decimals' => 2,
            'position_symbol' => 'after',
        ]);
    }

    /** Ziyaretcinin secili para birimi (oturum > dilin varsayilani > taban). */
    public function current(): Currency
    {
        $code = session(self::SESSION_KEY);

        if (! $code || ! isset($this->all()[$code])) {
            $code = Locales::currencyFor(app()->getLocale());
        }

        return $this->all()[$code] ?? $this->base();
    }

    public function setCurrent(string $code): bool
    {
        if (! isset($this->all()[$code])) {
            return false;
        }

        session([self::SESSION_KEY => $code]);

        return true;
    }

    public function format(float $amountInTry, ?string $code = null): string
    {
        $currency = $code ? ($this->all()[$code] ?? $this->current()) : $this->current();

        return $currency->format($amountInTry);
    }

    public function convert(float $amountInTry, ?string $code = null): float
    {
        $currency = $code ? ($this->all()[$code] ?? $this->current()) : $this->current();

        return $currency->convertFromBase($amountInTry);
    }

    /**
     * TCMB gunluk kurlarini ceker ve auto_update acik para birimlerini gunceller.
     *
     * TCMB, 1 birim yabanci para = kac TRY seklinde yayinlar (ForexSelling).
     * Bizim sakladigimiz oran ise tersi: 1 TRY = kac birim yabanci para.
     *
     * @return array{updated: int, skipped: int, error: string|null}
     */
    public function refreshRates(): array
    {
        $endpoint = config('shop.rates.endpoint');
        $updated = 0;
        $skipped = 0;

        try {
            $response = Http::timeout(15)->withoutVerifying()->get($endpoint);

            if (! $response->successful()) {
                return ['updated' => 0, 'skipped' => 0, 'error' => 'HTTP '.$response->status()];
            }

            $xml = @simplexml_load_string($response->body());

            if ($xml === false) {
                return ['updated' => 0, 'skipped' => 0, 'error' => 'XML cozumlenemedi'];
            }

            $tryPerUnit = [];

            foreach ($xml->Currency as $node) {
                $code = (string) $node['CurrencyCode'];
                $unit = (float) $node->Unit ?: 1;
                $selling = (float) str_replace(',', '.', (string) $node->ForexSelling);

                if ($selling <= 0) {
                    $selling = (float) str_replace(',', '.', (string) $node->BanknoteSelling);
                }

                if ($code !== '' && $selling > 0) {
                    $tryPerUnit[$code] = $selling / $unit;
                }
            }

            foreach (Currency::all() as $currency) {
                if ($currency->is_base) {
                    $currency->forceFill(['rate' => 1, 'rate_updated_at' => now()])->save();

                    continue;
                }

                if (! $currency->auto_update || ! isset($tryPerUnit[$currency->code])) {
                    $skipped++;

                    continue;
                }

                $currency->forceFill([
                    'rate' => round(1 / $tryPerUnit[$currency->code], 6),
                    'rate_updated_at' => now(),
                ])->save();

                $updated++;
            }

            $this->currencies = null;

            return ['updated' => $updated, 'skipped' => $skipped, 'error' => null];
        } catch (\Throwable $e) {
            Log::warning('Kur guncellenemedi: '.$e->getMessage());

            return ['updated' => 0, 'skipped' => 0, 'error' => $e->getMessage()];
        }
    }

    public function forgetCache(): void
    {
        $this->currencies = null;
    }
}
