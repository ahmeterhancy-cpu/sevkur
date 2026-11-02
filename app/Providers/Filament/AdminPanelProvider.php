<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\LatestOrdersWidget;
use App\Filament\Widgets\SalesChartWidget;
use App\Filament\Widgets\ShopStatsWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            // Canli sunucuda SSH yok; parola yalnizca panelden degistirilebilir
            // olmali, yoksa kurulum parolasi ("Sevkur2026!") yayinda kalir.
            ->profile(isSimple: false)
            ->brandName('Sevkur Mobilya')
            // Marka turuncusu dogrudan hex olarak verilir; Color::hex() paletin
            // tonlarini bozdugu icin kullanilmiyor.
            ->colors([
                'primary' => [
                    50 => '255, 243, 230',
                    100 => '254, 228, 199',
                    200 => '252, 200, 148',
                    300 => '250, 172, 97',
                    400 => '245, 145, 48',
                    500 => '235, 119, 0',
                    600 => '204, 103, 0',
                    700 => '163, 82, 0',
                    800 => '122, 62, 0',
                    900 => '82, 41, 0',
                    950 => '46, 23, 0',
                ],
            ])
            ->navigationGroups([
                NavigationGroup::make('Katalog'),
                NavigationGroup::make('Satış'),
                NavigationGroup::make('İçerik'),
                NavigationGroup::make('Ayarlar'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                ShopStatsWidget::class,
                SalesChartWidget::class,
                LatestOrdersWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
