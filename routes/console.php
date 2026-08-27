<?php

use Illuminate\Support\Facades\Schedule;

/*
| TCMB gunluk kurlarini her sabah 09:15'te tazele (TCMB kurlari 15:30 civari
| yenilenir; sabah cekmek onceki is gununun kapanis kurunu verir, bu yeterli).
| Turhost'ta cron: php /home/KULLANICI/public_html/artisan schedule:run
*/
Schedule::command('kur:guncelle')
    ->dailyAt('09:15')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

// Odemesi 24 saattir alinmayan siparisleri iptal edip stogu geri yukle.
Schedule::call(function () {
    $orders = App\Models\Order::where('payment_status', 'pending')
        ->where('status', 'pending')
        ->where('created_at', '<', now()->subDay())
        ->get();

    foreach ($orders as $order) {
        app(App\Services\OrderService::class)->cancel($order, 'Odeme alinmadi (24 saat)');
    }
})->hourly()->name('bekleyen-siparisleri-temizle')->withoutOverlapping();
