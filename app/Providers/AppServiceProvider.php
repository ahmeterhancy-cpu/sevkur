<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Page;
use App\Services\CartService;
use App\Services\CurrencyService;
use App\Services\FavoritesService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CurrencyService::class);
        $this->app->singleton(CartService::class);
        $this->app->singleton(FavoritesService::class);
    }

    public function boot(): void
    {
        $this->shareLayoutData();
    }

    /**
     * Basliktaki menu, duyuru seridi, para birimleri ve alt bilgi sayfalari her
     * sayfada gerekiyor; tek yerde paylasilir.
     */
    private function shareLayoutData(): void
    {
        View::composer('layouts.*', function ($view) {
            // Kurulum/gecis anlarinda tablolar hazir olmayabilir.
            if (! Schema::hasTable('categories')) {
                $view->with([
                    'layoutMenu' => collect(),
                    'layoutCategories' => collect(),
                    'layoutAnnouncements' => collect(),
                    'layoutFooterPages' => collect(),
                    'layoutCurrencies' => [],
                ]);

                return;
            }

            $view->with([
                'layoutMenu' => MenuItem::active()->where('location', 'header')->whereNull('parent_id')->with('children.children')->get(),
                'layoutCategories' => Category::active()->roots()->where('show_in_menu', true)->with('activeChildren')->get(),
                'layoutAnnouncements' => Announcement::active()->get(),
                'layoutFooterPages' => Page::active()->where('show_in_footer', true)->orderBy('position')->get(),
                'layoutCurrencies' => app(CurrencyService::class)->all(),
            ]);
        });
    }
}
