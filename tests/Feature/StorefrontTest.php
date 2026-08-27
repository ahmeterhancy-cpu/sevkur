<?php

namespace Tests\Feature;

use App\Support\Locales;
use Tests\ShopTestCase;

class StorefrontTest extends ShopTestCase
{
    public function test_home_page_renders_in_every_locale(): void
    {
        foreach (Locales::all() as $locale) {
            $response = $this->get(localized_route('home', [], $locale));

            $response->assertOk();
            $this->assertNoPhpNoise($response->getContent(), "home/$locale");
        }
    }

    public function test_shop_listing_and_filters(): void
    {
        $this->get(localized_route('shop'))
            ->assertOk()
            ->assertSee('Test Koltuk 1');

        // Fiyat suzgeci
        $this->get(localized_route('shop').'?min=2500')
            ->assertOk()
            ->assertDontSee('Test Koltuk 1')
            ->assertSee('Test Koltuk 5');

        // Siralama
        $this->get(localized_route('shop').'?sirala=fiyat-azalan')->assertOk();

        // Indirimli suzgeci
        $this->get(localized_route('shop').'?indirimli=1')
            ->assertOk()
            ->assertSee('Test Koltuk 1');
    }

    public function test_category_page_includes_child_category_products(): void
    {
        $this->get(localized_route('category', ['category' => 'oturma-odasi']))
            ->assertOk()
            ->assertSee('Test Koltuk 1');
    }

    public function test_product_page_renders_with_localised_name(): void
    {
        $this->get(localized_route('product', ['product' => 'test-koltuk-2']))
            ->assertOk()
            ->assertSee('Test Koltuk 2');

        $this->get(localized_route('product', ['product' => 'test-koltuk-2'], 'en'))
            ->assertOk()
            ->assertSee('Test Sofa 2');
    }

    public function test_inactive_product_returns_404(): void
    {
        $product = $this->product(1);
        $product->update(['is_active' => false]);

        $this->get(localized_route('product', ['product' => $product->slug]))->assertNotFound();
    }

    public function test_search_finds_products(): void
    {
        $this->get(localized_route('search').'?q=koltuk')
            ->assertOk()
            ->assertSee('Test Koltuk 1');

        $this->get(localized_route('search').'?q=zzzzzz')
            ->assertOk()
            ->assertSee(__('shop.no_products'));
    }

    public function test_static_pages_render(): void
    {
        $this->get(localized_route('contact'))->assertOk();
        $this->get(localized_route('faq'))->assertOk();
        $this->get(localized_route('brands'))->assertOk();
        $this->get(localized_route('blog'))->assertOk();
        $this->get(localized_route('track'))->assertOk();
        $this->get(localized_route('cart'))->assertOk();
        $this->get(localized_route('wishlist'))->assertOk();
        $this->get(localized_route('compare'))->assertOk();
        $this->get(localized_route('page', ['page' => 'hakkimizda']))->assertOk();
    }

    public function test_sitemap_is_valid_xml(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');

        $this->assertNotFalse(simplexml_load_string($response->getContent()));
    }

    public function test_language_switcher_keeps_the_same_product(): void
    {
        $response = $this->get(localized_route('product', ['product' => 'test-koltuk-3']));

        $response->assertOk();
        $response->assertSee(localized_route('product', ['product' => 'test-koltuk-3'], 'bg'), false);
    }

    public function test_review_submission_is_held_for_approval(): void
    {
        $product = $this->product(1);

        $this->post(localized_route('product.review', ['product' => $product->slug]), [
            'name' => 'Test Kullanıcı',
            'email' => 'test@example.com',
            'rating' => 5,
            'body' => 'Bu ürün gerçekten çok iyi, tavsiye ederim.',
        ])->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'product_id' => $product->id,
            'is_approved' => false,
        ]);
    }

    public function test_newsletter_subscription(): void
    {
        $this->post(localized_route('newsletter'), ['email' => 'Abone@Example.com'])
            ->assertRedirect();

        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'abone@example.com']);
    }

    /** Yanit govdesinde PHP uyarisi / yigin izi / sunucu yolu olmamali. */
    private function assertNoPhpNoise(string $body, string $context): void
    {
        foreach (['Warning:', 'Notice:', 'Deprecated:', 'Fatal error', 'Stack trace', 'vendor\\laravel'] as $needle) {
            $this->assertStringNotContainsString($needle, $body, "$context sayfasinda '$needle' bulundu");
        }
    }
}
