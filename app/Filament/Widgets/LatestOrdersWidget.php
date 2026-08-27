<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrdersWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Son siparişler')
            ->query(Order::query()->latest()->limit(10))
            ->paginated(false)
            ->columns([
                TextColumn::make('number')->label('Sipariş no'),
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i'),
                TextColumn::make('customer_first_name')
                    ->label('Müşteri')
                    ->formatStateUsing(fn ($state, Order $record) => $record->customerName()),
                TextColumn::make('grand_total')->label('Tutar')->formatStateUsing(fn ($state) => money_base($state)),
                TextColumn::make('status')
                    ->label('Durum')->badge()
                    ->formatStateUsing(fn (string $state) => OrderResource::statusOptions()[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'delivered' => 'success',
                        'shipped' => 'info',
                        'cancelled', 'refunded' => 'danger',
                        'processing' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                Action::make('ac')
                    ->label('Aç')
                    ->url(fn (Order $record) => OrderResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
