<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PayTrWebhookController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WishlistController;
use App\Support\Locales;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Yerellestirilmis magaza rotalari
|--------------------------------------------------------------------------
| Her dil icin ayni rota kumesi, o dile ait yol parcalari ve "<dil>.<anahtar>"
| adlariyla kaydedilir. Varsayilan dil (tr) on ek almaz.
*/

foreach (Locales::all() as $locale) {
    $prefix = Locales::prefix($locale);
    $seg = fn (string $key) => Locales::segment($key, $locale);

    Route::middleware(['web', 'locale:'.$locale])
        ->prefix($prefix)
        ->name($locale.'.')
        ->group(function () use ($seg) {
            Route::get('/', [HomeController::class, 'index'])->name('home');

            // ---------------------------------------------------------- magaza
            Route::get($seg('shop'), [ShopController::class, 'index'])->name('shop');
            Route::get($seg('search'), [ShopController::class, 'search'])->name('search');
            Route::get($seg('brands'), [ShopController::class, 'brands'])->name('brands');
            Route::get($seg('category').'/{category:slug}', [ShopController::class, 'category'])->name('category');
            Route::get($seg('product').'/{product:slug}', [ShopController::class, 'product'])->name('product');
            Route::post($seg('product').'/{product:slug}/yorum', [ShopController::class, 'storeReview'])->name('product.review');
            Route::get('quick-view/{product:slug}', [ShopController::class, 'quickView'])->name('product.quick');

            // ----------------------------------------------------------- sepet
            Route::get($seg('cart'), [CartController::class, 'index'])->name('cart');
            Route::post($seg('cart').'/ekle', [CartController::class, 'add'])->name('cart.add');
            Route::post($seg('cart').'/guncelle', [CartController::class, 'update'])->name('cart.update');
            Route::post($seg('cart').'/sil', [CartController::class, 'remove'])->name('cart.remove');
            Route::post($seg('cart').'/kupon', [CartController::class, 'applyCoupon'])->name('cart.coupon');
            Route::post($seg('cart').'/kupon-sil', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');
            Route::get($seg('cart').'/ozet', [CartController::class, 'summary'])->name('cart.summary');

            // ----------------------------------------------------------- odeme
            Route::get($seg('checkout'), [CheckoutController::class, 'index'])->name('checkout');
            Route::post($seg('checkout'), [CheckoutController::class, 'store'])->name('checkout.store');
            Route::get($seg('checkout').'/{order:number}/pay', [CheckoutController::class, 'pay'])->name('checkout.pay');
            Route::post($seg('checkout').'/{order:number}/simule', [CheckoutController::class, 'simulate'])->name('checkout.simulate');
            Route::get($seg('checkout').'/{order:number}/'.$seg('thanks'), [CheckoutController::class, 'thanks'])->name('checkout.thanks');
            Route::get($seg('checkout').'/{order:number}/hata', [CheckoutController::class, 'failed'])->name('checkout.failed');
            Route::match(['get', 'post'], $seg('track'), [CheckoutController::class, 'track'])->name('track');

            // ------------------------------------------------ favori / karsilastir
            Route::get($seg('wishlist'), [WishlistController::class, 'index'])->name('wishlist');
            Route::post($seg('wishlist').'/degistir', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
            Route::get($seg('compare'), [CompareController::class, 'index'])->name('compare');
            Route::post($seg('compare').'/degistir', [CompareController::class, 'toggle'])->name('compare.toggle');

            // ------------------------------------------------------------ icerik
            Route::get($seg('blog'), [BlogController::class, 'index'])->name('blog');
            Route::get($seg('blog').'/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
            Route::get($seg('contact'), [PageController::class, 'contact'])->name('contact');
            Route::post($seg('contact'), [PageController::class, 'sendContact'])->name('contact.send');
            Route::get($seg('faq'), [PageController::class, 'faq'])->name('faq');
            Route::get($seg('page').'/{page:slug}', [PageController::class, 'show'])->name('page');
            Route::post('newsletter', [NewsletterController::class, 'store'])->name('newsletter');
            Route::post('currency', [CurrencyController::class, 'switch'])->name('currency.switch');

            // -------------------------------------------------------------- uye
            Route::middleware('guest')->group(function () use ($seg) {
                Route::get($seg('login'), [AuthController::class, 'showLogin'])->name('login');
                Route::post($seg('login'), [AuthController::class, 'login'])->name('login.post');
                Route::get($seg('register'), [AuthController::class, 'showRegister'])->name('register');
                Route::post($seg('register'), [AuthController::class, 'register'])->name('register.post');
                Route::get($seg('account').'/sifremi-unuttum', [AuthController::class, 'showForgot'])->name('password.request');
                Route::post($seg('account').'/sifremi-unuttum', [AuthController::class, 'sendReset'])->name('password.email');
                Route::get($seg('account').'/sifre-sifirla/{token}', [AuthController::class, 'showReset'])->name('password.reset');
                Route::post($seg('account').'/sifre-sifirla', [AuthController::class, 'resetPassword'])->name('password.update');
            });

            Route::post('cikis', [AuthController::class, 'logout'])->name('logout');

            Route::middleware('auth')->prefix($seg('account'))->name('account.')->group(function () use ($seg) {
                Route::get('/', [AccountController::class, 'dashboard'])->name('index');
                Route::get($seg('orders'), [AccountController::class, 'orders'])->name('orders');
                Route::get($seg('orders').'/{order:number}', [AccountController::class, 'order'])->name('order');
                Route::post($seg('orders').'/{order:number}/iptal', [AccountController::class, 'cancelOrder'])->name('order.cancel');
                Route::get($seg('addresses'), [AccountController::class, 'addresses'])->name('addresses');
                Route::post($seg('addresses'), [AccountController::class, 'storeAddress'])->name('addresses.store');
                Route::put($seg('addresses').'/{address}', [AccountController::class, 'updateAddress'])->name('addresses.update');
                Route::delete($seg('addresses').'/{address}', [AccountController::class, 'destroyAddress'])->name('addresses.destroy');
                Route::get($seg('profile'), [AccountController::class, 'profile'])->name('profile');
                Route::put($seg('profile'), [AccountController::class, 'updateProfile'])->name('profile.update');
            });
        });
}

/*
|--------------------------------------------------------------------------
| Dil disi rotalar
|--------------------------------------------------------------------------
*/

Route::middleware('web')->group(function () {
    Route::post('paytr/callback', [PayTrWebhookController::class, 'handle'])->name('paytr.callback');
    Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
});
