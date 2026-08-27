<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShopStatsWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Bugün';

    protected function getStats(): array
    {
        $todayOrders = Order::whereDate('created_at', today())->count();
        $todayRevenue = (float) Order::whereDate('paid_at', today())->where('payment_status', 'paid')->sum('grand_total');
        $monthRevenue = (float) Order::whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->where('payment_status', 'paid')
            ->sum('grand_total');

        $awaiting = Order::whereIn('status', ['pending', 'processing'])->count();
        $lowStock = Product::where('manage_stock', true)
            ->whereColumn('stock', '<=', 'stock_alert')
            ->where('is_active', true)
            ->count();
        $pendingReviews = Review::where('is_approved', false)->count();

        return [
            Stat::make('Bugünkü sipariş', $todayOrders)
                ->description($awaiting.' sipariş işlem bekliyor')
                ->color($awaiting > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-shopping-bag'),

            Stat::make('Bugünkü ciro', money_base($todayRevenue))
                ->description('Bu ay: '.money_base($monthRevenue))
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Kritik stok', $lowStock)
                ->description($lowStock > 0 ? 'Stok girmeniz gereken ürün var' : 'Stoklar yeterli')
                ->color($lowStock > 0 ? 'danger' : 'gray')
                ->icon('heroicon-o-exclamation-triangle'),

            Stat::make('Onay bekleyen yorum', $pendingReviews)
                ->description(User::where('role', User::ROLE_CUSTOMER)->count().' kayıtlı müşteri')
                ->color($pendingReviews > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-star'),
        ];
    }
}
