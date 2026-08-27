<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Support\Locales;

class SitemapController extends Controller
{
    public function index()
    {
        $urls = [];

        foreach (Locales::all() as $locale) {
            $urls[] = ['loc' => localized_route('home', [], $locale), 'priority' => '1.0', 'freq' => 'daily'];
            $urls[] = ['loc' => localized_route('shop', [], $locale), 'priority' => '0.9', 'freq' => 'daily'];
            $urls[] = ['loc' => localized_route('blog', [], $locale), 'priority' => '0.6', 'freq' => 'weekly'];
            $urls[] = ['loc' => localized_route('contact', [], $locale), 'priority' => '0.4', 'freq' => 'monthly'];

            foreach (Category::active()->get() as $category) {
                $urls[] = [
                    'loc' => localized_route('category', ['category' => $category->slug], $locale),
                    'priority' => '0.8', 'freq' => 'weekly',
                    'lastmod' => $category->updated_at?->toAtomString(),
                ];
            }

            foreach (Product::active()->get(['slug', 'updated_at']) as $product) {
                $urls[] = [
                    'loc' => localized_route('product', ['product' => $product->slug], $locale),
                    'priority' => '0.7', 'freq' => 'weekly',
                    'lastmod' => $product->updated_at?->toAtomString(),
                ];
            }

            foreach (Post::published()->get(['slug', 'updated_at']) as $post) {
                $urls[] = [
                    'loc' => localized_route('blog.show', ['post' => $post->slug], $locale),
                    'priority' => '0.5', 'freq' => 'monthly',
                    'lastmod' => $post->updated_at?->toAtomString(),
                ];
            }

            foreach (Page::active()->get(['slug', 'updated_at']) as $page) {
                $urls[] = [
                    'loc' => localized_route('page', ['page' => $page->slug], $locale),
                    'priority' => '0.4', 'freq' => 'monthly',
                    'lastmod' => $page->updated_at?->toAtomString(),
                ];
            }
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
