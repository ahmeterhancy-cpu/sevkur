<?php

return [
    'merchant_id' => env('PAYTR_MERCHANT_ID', ''),
    'merchant_key' => env('PAYTR_MERCHANT_KEY', ''),
    'merchant_salt' => env('PAYTR_MERCHANT_SALT', ''),

    // 1 iken PayTR test ortamina gider, gercek para cekilmez.
    'test_mode' => (int) env('PAYTR_TEST_MODE', 1),

    'token_endpoint' => 'https://www.paytr.com/odeme/api/get-token',
    'iframe_url' => 'https://www.paytr.com/odeme/guvenli/',

    'timeout_limit' => 30,
    'no_installment' => 0,
    'max_installment' => 0,
    'currency' => 'TL',

    // Kimlik bilgileri girilmediginde yerel gelistirme icin sahte odeme
    // ekrani gosterilir. Canlida .env doldurulunca kendiliginden kapanir.
    'allow_simulation' => (bool) env('PAYTR_ALLOW_SIMULATION', true),
];
