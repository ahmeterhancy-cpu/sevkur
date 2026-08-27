<?php

/*
 |  Duman testi — gercek (seed edilmis) veritabaniyla vitrini gezer.
 |
 |  Kullanim:  php tools/smoke.php
 |
 |  Yalnizca durum koduna bakmak yetmez: PHP uyarilari govdeye basilir ve
 |  sayfa yine 200 doner. Bu yuzden yanit govdesinde de uyari / yigin izi /
 |  sunucu yolu / cevrilmemis anahtar araniyor.
 */

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Support\Locales;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Http\Request;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

/** @var Kernel $kernel */
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// config()/route() kullanabilmek icin cerceveyi once ayaga kaldir.
$kernel->bootstrap();

$base = rtrim($_SERVER['argv'][1] ?? config('app.url', 'http://localhost:8137'), '/');

// route() yardimcisinin calisabilmesi icin kapsayicida bir istek bulunmali.
$app->instance('request', Request::create($base.'/'));

/** Gezilecek adresleri topla. */
$urls = [];

foreach (Locales::all() as $locale) {
    foreach (['home', 'shop', 'blog', 'contact', 'faq', 'brands', 'track', 'cart', 'wishlist', 'compare', 'login', 'register'] as $key) {
        $urls[] = localized_route($key, [], $locale);
    }
}

// Ornek kategori / urun / yazi / sayfa — her dilde
$category = Category::query()->whereNotNull('parent_id')->first();
$product = Product::query()->where('is_active', true)->first();
$post = Post::query()->first();
$page = Page::query()->first();

foreach (Locales::all() as $locale) {
    if ($category) {
        $urls[] = localized_route('category', ['category' => $category->slug], $locale);
    }
    if ($product) {
        $urls[] = localized_route('product', ['product' => $product->slug], $locale);
    }
    if ($post) {
        $urls[] = localized_route('blog.show', ['post' => $post->slug], $locale);
    }
    if ($page) {
        $urls[] = localized_route('page', ['page' => $page->slug], $locale);
    }
}

// Suzgecli mağaza adresleri (yalnizca varsayilan dilde)
$urls[] = localized_route('shop').'?sirala=fiyat-artan';
$urls[] = localized_route('shop').'?indirimli=1&stokta=1';
$urls[] = localized_route('shop').'?ozellik[renk][]=ceviz';
$urls[] = localized_route('search').'?q=koltuk';
$urls[] = $base.'/sitemap.xml';

// robots.txt gibi statik dosyalar web sunucusundan servis edilir, HTTP
// cekirdeginden gecmez; bu yuzden buraya alinmiyor.

$urls = array_values(array_unique($urls));

$needles = ['Warning:', 'Notice:', 'Deprecated:', 'Fatal error', 'Stack trace', 'Whoops', 'vendor\\laravel', 'shop.'];

$fail = 0;
$checked = 0;

printf('%d adres geziliyor…%s%s', count($urls), PHP_EOL, PHP_EOL);

foreach ($urls as $url) {
    $started = microtime(true);

    $response = $kernel->handle(Request::create($url, 'GET'));
    $body = $response->getContent();
    $status = $response->getStatusCode();
    $ms = (int) round((microtime(true) - $started) * 1000);
    $kb = round(strlen($body) / 1024, 1);

    $problems = [];

    if ($status !== 200) {
        $problems[] = "durum $status";
    }

    foreach ($needles as $needle) {
        // "shop." yalnizca cevrilmemis dil anahtarini yakalamak icin;
        // gecerli baglantilarda gecmemeli.
        if ($needle === 'shop.' && ! preg_match('/\bshop\.[a-z_]+/', $body)) {
            continue;
        }

        if (str_contains($body, $needle)) {
            $problems[] = "'$needle' bulundu";
        }
    }

    $checked++;
    $mark = $problems === [] ? 'OK  ' : 'HATA';

    if ($problems !== []) {
        $fail++;
    }

    printf('%s  %3d ms  %6s KB  %s%s', $mark, $ms, $kb, str_replace($base, '', $url), PHP_EOL);

    foreach ($problems as $problem) {
        printf('      → %s%s', $problem, PHP_EOL);
    }
}

printf('%s%d adres, %d sorunlu.%s', PHP_EOL, $checked, $fail, PHP_EOL);

exit($fail > 0 ? 1 : 0);
