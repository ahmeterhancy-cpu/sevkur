<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class SalesChartWidget extends ChartWidget
{
    protected ?string $heading = 'Son 30 günün cirosu (₺)';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $labels = [];
        $values = [];

        // Gunluk toplamlari tek sorguda al, eksik gunleri sifirla doldur.
        $totals = Order::where('payment_status', 'paid')
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', now()->subDays(29)->startOfDay())
            ->get(['paid_at', 'grand_total'])
            ->groupBy(fn (Order $order) => $order->paid_at->toDateString())
            ->map(fn ($group) => (float) $group->sum('grand_total'));

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d.m');
            $values[] = round($totals[$date->toDateString()] ?? 0, 2);
        }

        return [
            'datasets' => [[
                'label' => 'Ciro',
                'data' => $values,
                'borderColor' => '#eb7700',
                'backgroundColor' => 'rgba(235, 119, 0, 0.12)',
                'fill' => true,
                'tension' => 0.35,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
