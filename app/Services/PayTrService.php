<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PayTR iFrame API entegrasyonu.
 *
 * PayTR yalnizca TRY tahsil eder; siparisin grand_total alani zaten TRY'dir,
 * vitrindeki EUR gosterimi tahsilati etkilemez.
 */
class PayTrService
{
    public function isConfigured(): bool
    {
        return config('paytr.merchant_id') !== ''
            && config('paytr.merchant_key') !== ''
            && config('paytr.merchant_salt') !== '';
    }

    public function simulationAllowed(): bool
    {
        return ! $this->isConfigured() && config('paytr.allow_simulation');
    }

    /**
     * Odeme icin token alir.
     *
     * @return array{ok: bool, token: string|null, error: string|null}
     */
    public function createToken(Order $order, string $okUrl, string $failUrl): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'token' => null, 'error' => 'PayTR kimlik bilgileri tanimli degil.'];
        }

        $merchantId = config('paytr.merchant_id');
        $merchantKey = config('paytr.merchant_key');
        $merchantSalt = config('paytr.merchant_salt');

        $merchantOid = $this->merchantOid($order);
        $amount = (int) round((float) $order->grand_total * 100); // kurus
        $userIp = $this->clientIp();
        $basket = $this->basket($order);
        $noInstallment = (int) config('paytr.no_installment');
        $maxInstallment = (int) config('paytr.max_installment');
        $currency = config('paytr.currency');
        $testMode = (int) config('paytr.test_mode');

        $hashStr = $merchantId.$userIp.$merchantOid.$order->customer_email.$amount
            .$basket.$noInstallment.$maxInstallment.$currency.$testMode;

        $token = base64_encode(hash_hmac('sha256', $hashStr.$merchantSalt, $merchantKey, true));

        $payload = [
            'merchant_id' => $merchantId,
            'user_ip' => $userIp,
            'merchant_oid' => $merchantOid,
            'email' => $order->customer_email,
            'payment_amount' => $amount,
            'paytr_token' => $token,
            'user_basket' => $basket,
            'debug_on' => app()->isLocal() ? 1 : 0,
            'no_installment' => $noInstallment,
            'max_installment' => $maxInstallment,
            'user_name' => $order->customerName(),
            'user_address' => $this->addressLine($order),
            'user_phone' => $order->customer_phone,
            'merchant_ok_url' => $okUrl,
            'merchant_fail_url' => $failUrl,
            'timeout_limit' => (int) config('paytr.timeout_limit'),
            'currency' => $currency,
            'test_mode' => $testMode,
            'lang' => in_array($order->locale, ['tr', 'en'], true) ? $order->locale : 'en',
        ];

        try {
            $response = Http::asForm()->timeout(25)->withoutVerifying()
                ->post(config('paytr.token_endpoint'), $payload);

            $body = $response->json();

            if (($body['status'] ?? '') === 'success') {
                $order->forceFill([
                    'merchant_oid' => $merchantOid,
                    'payment_payload' => array_merge($order->payment_payload ?? [], [
                        'token_requested_at' => now()->toIso8601String(),
                    ]),
                ])->save();

                return ['ok' => true, 'token' => $body['token'], 'error' => null];
            }

            $reason = $body['reason'] ?? $response->body();
            Log::warning('PayTR token alinamadi', ['order' => $order->number, 'reason' => $reason]);
            $order->log('paytr_token_failed', is_string($reason) ? $reason : null);

            return ['ok' => false, 'token' => null, 'error' => is_string($reason) ? $reason : 'Bilinmeyen hata'];
        } catch (\Throwable $e) {
            Log::error('PayTR istegi basarisiz: '.$e->getMessage());

            return ['ok' => false, 'token' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Bildirim (callback) imzasini dogrular.
     *
     * @param  array<string, mixed>  $request
     */
    public function verifyCallback(array $request): bool
    {
        $merchantKey = config('paytr.merchant_key');
        $merchantSalt = config('paytr.merchant_salt');

        if ($merchantKey === '' || $merchantSalt === '') {
            return false;
        }

        $expected = base64_encode(hash_hmac(
            'sha256',
            ($request['merchant_oid'] ?? '').$merchantSalt.($request['status'] ?? '').($request['total_amount'] ?? ''),
            $merchantKey,
            true
        ));

        return hash_equals($expected, (string) ($request['hash'] ?? ''));
    }

    public function iframeUrl(string $token): string
    {
        return rtrim(config('paytr.iframe_url'), '/').'/'.$token;
    }

    /** PayTR yalnizca harf/rakam kabul eder. */
    private function merchantOid(Order $order): string
    {
        return preg_replace('/[^A-Za-z0-9]/', '', $order->number).'X'.$order->id;
    }

    private function clientIp(): string
    {
        $ip = request()->ip();

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
    }

    /** Sepet listesi: [[ad, birim fiyat, adet], ...] base64 JSON. */
    private function basket(Order $order): string
    {
        $items = $order->items->map(fn ($item) => [
            mb_substr($item->name, 0, 80),
            number_format((float) $item->unit_price, 2, '.', ''),
            (int) $item->quantity,
        ])->values()->all();

        if ((float) $order->shipping_total > 0) {
            $items[] = ['Kargo', number_format((float) $order->shipping_total, 2, '.', ''), 1];
        }

        if ((float) $order->discount_total > 0) {
            $items[] = ['Indirim', '-'.number_format((float) $order->discount_total, 2, '.', ''), 1];
        }

        return base64_encode(json_encode($items, JSON_UNESCAPED_UNICODE));
    }

    private function addressLine(Order $order): string
    {
        $a = $order->shipping_address ?? [];

        return trim(implode(' ', array_filter([
            $a['address'] ?? null,
            $a['district'] ?? null,
            $a['city'] ?? null,
        ]))) ?: '-';
    }

    /** Callback'te gelen merchant_oid'den siparisi bulur. */
    public function resolveOrder(string $merchantOid): ?Order
    {
        $order = Order::where('merchant_oid', $merchantOid)->first();

        if ($order) {
            return $order;
        }

        // Yedek: sondaki X<id> parcasindan coz.
        if (preg_match('/X(\d+)$/', $merchantOid, $m)) {
            return Order::find((int) $m[1]);
        }

        return null;
    }
}
