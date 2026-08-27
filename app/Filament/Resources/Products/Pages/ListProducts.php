<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Pages\ImportProduct;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ice_aktar')
                ->label('URL’den çek')
                ->icon('heroicon-o-arrow-down-on-square')
                ->color('gray')
                ->url(fn () => ImportProduct::getUrl()),

            CreateAction::make(),
        ];
    }
}
