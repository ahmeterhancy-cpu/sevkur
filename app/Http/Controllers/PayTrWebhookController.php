<?php

namespace App\Http\Controllers;

use App\Mail\OrderPlaced;
use App\Services\OrderService;
use App\Services\PayTrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * PayTR bildirim ucu.
 *
 * PayTR, "OK" disinda bir yanit alirsa bildirimi tekrar tekrar gonderir;
 * bu yuzden dogrulama sonrasi her durumda "OK" donulur.
 */
class PayTrWebhookController extends Controller
{
    public function handle(Request $request, PayTrService $paytr, OrderService $orders)
    {
        $payload = $request->all();

        if (! $paytr->verifyCallback($payload)) {
            Log::warning('PayTR bildirimi dogrulanamadi', ['oid' => $payload['merchant_oid'] ?? null]);

            return response('PAYTR notification failed: bad hash', 400);
        }

        $order = $paytr->resolveOrder((string) ($payload['merchant_oid'] ?? ''));

        if (! $order) {
            Log::warning('PayTR bildirimi: siparis bulunamadi', ['oid' => $payload['merchant_oid'] ?? null]);

            return response('OK');
        }

        // Ayni bildirim birden fazla gelebilir.
        if ($order->isPaid()) {
            return response('OK');
        }

        if (($payload['status'] ?? '') === 'success') {
            $expected = (int) round((float) $order->grand_total * 100);
            $received = (int) ($payload['total_amount'] ?? 0);

            if ($received !== $expected) {
                Log::error('PayTR tutar uyusmazligi', [
                    'order' => $order->number, 'expected' => $expected, 'received' => $received,
                ]);
                $order->log('amount_mismatch', "Beklenen $expected, gelen $received");
            }

            $orders->markPaid($order, [
                'payment_type' => $payload['payment_type'] ?? null,
                'total_amount' => $received,
                'installment_count' => $payload['installment_count'] ?? null,
            ]);

            try {
                Mail::to($order->customer_email)->send(new OrderPlaced($order));
            } catch (\Throwable $e) {
                report($e);
            }
        } else {
            $orders->markFailed($order, (string) ($payload['failed_reason_msg'] ?? ''));
        }

        return response('OK');
    }
}
