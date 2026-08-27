<?php

namespace App\Filament\Resources\ShippingMethods;

use App\Filament\Resources\ShippingMethods\Pages\CreateShippingMethod;
use App\Filament\Resources\ShippingMethods\Pages\EditShippingMethod;
use App\Filament\Resources\ShippingMethods\Pages\ListShippingMethods;
use App\Filament\Support\Translatable;
use App\Models\ShippingMethod;
use App\Support\Locales;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class ShippingMethodResource extends Resource
{
    protected static ?string $model = ShippingMethod::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|UnitEnum|null $navigationGroup = 'Satış';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Kargo yöntemi';

    protected static ?string $pluralModelLabel = 'Kargo yöntemleri';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Translatable::text('name', 'Yöntem adı'),
            TextInput::make('code')->label('Kod')->required()->unique(ignoreRecord: true)->maxLength(40),
            TextInput::make('price')->label('Ücret (₺)')->numeric()->required()->default(0)->minValue(0),
            TextInput::make('free_over')->label('Bu tutarın üzerinde ücretsiz (₺)')->numeric()->minValue(0)
                ->helperText('Boş bırakırsanız her zaman ücretlidir.'),
            TextInput::make('eta_days')->label('Tahmini gün')->numeric(),
            TextInput::make('position')->label('Sıra')->numeric()->default(0),
            Toggle::make('is_active')->label('Aktif')->default(true),
            Translatable::textarea('description', 'Açıklama', 2),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                TextColumn::make('name')->label('Yöntem')
                    ->formatStateUsing(fn ($record) => $record->t('name', Locales::default())),
                TextColumn::make('price')->label('Ücret')->money('TRY'),
                TextColumn::make('free_over')->label('Ücretsiz sınırı')
                    ->formatStateUsing(fn ($state) => $state ? money_base($state) : '—'),
                TextColumn::make('eta_days')->label('Gün'),
                TextColumn::make('position')->label('Sıra')->sortable(),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShippingMethods::route('/'),
            'create' => CreateShippingMethod::route('/create'),
            'edit' => EditShippingMethod::route('/{record}/edit'),
        ];
    }
}
