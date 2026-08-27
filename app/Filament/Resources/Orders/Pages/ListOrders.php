<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'hepsi' => Tab::make('Hepsi'),
            'bekleyen' => Tab::make('Onay bekleyen')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),
            'hazirlanan' => Tab::make('Hazırlanıyor')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'processing')),
            'kargoda' => Tab::make('Kargoda')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'shipped')),
            'odenmeyen' => Tab::make('Ödemesi alınmayan')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_status', 'pending')),
        ];
    }
}
