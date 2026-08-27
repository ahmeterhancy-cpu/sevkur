<?php

namespace App\Filament\Resources\Attributes;

use App\Filament\Resources\Attributes\Pages\CreateAttribute;
use App\Filament\Resources\Attributes\Pages\EditAttribute;
use App\Filament\Resources\Attributes\Pages\ListAttributes;
use App\Filament\Support\Translatable;
use App\Models\Attribute;
use App\Support\Locales;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class AttributeResource extends Resource
{
    protected static ?string $model = Attribute::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Özellik';

    protected static ?string $pluralModelLabel = 'Özellikler';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Translatable::text('name', 'Özellik adı'),
            TextInput::make('slug')->label('URL adı')->required()->unique(ignoreRecord: true),
            Select::make('type')->label('Tür')->options([
                'select' => 'Liste',
                'color' => 'Renk',
            ])->default('select')->required(),
            TextInput::make('position')->label('Sıra')->numeric()->default(0),
            Toggle::make('is_filterable')->label('Mağaza filtresinde göster')->default(true),
            Toggle::make('is_variant')->label('Varyant üretiminde kullanılır'),

            Repeater::make('values')
                ->label('Seçenekler')
                ->relationship()
                ->schema([
                    Translatable::text('name', 'Seçenek'),
                    TextInput::make('slug')->label('URL adı')->required(),
                    ColorPicker::make('color_hex')->label('Renk kodu'),
                    TextInput::make('position')->label('Sıra')->numeric()->default(0),
                ])
                ->orderColumn('position')
                ->columns(3)
                ->columnSpanFull()
                ->defaultItems(1),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                TextColumn::make('name')->label('Özellik')
                    ->state(fn ($record) => $record->t('name', Locales::default())),
                TextColumn::make('slug')->label('URL'),
                TextColumn::make('type')->label('Tür')->badge(),
                TextColumn::make('values_count')->label('Seçenek')->counts('values'),
                ToggleColumn::make('is_filterable')->label('Filtrede'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttributes::route('/'),
            'create' => CreateAttribute::route('/create'),
            'edit' => EditAttribute::route('/{record}/edit'),
        ];
    }
}
