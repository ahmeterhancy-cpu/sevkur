<?php

namespace App\Filament\Resources\Banners;

use App\Filament\Resources\Banners\Pages\CreateBanner;
use App\Filament\Resources\Banners\Pages\EditBanner;
use App\Filament\Resources\Banners\Pages\ListBanners;
use App\Filament\Support\Translatable;
use App\Models\Banner;
use App\Support\Locales;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static string|UnitEnum|null $navigationGroup = 'İçerik';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Tanıtım kutusu';

    protected static ?string $pluralModelLabel = 'Tanıtım kutuları';

    /** @return array<string, string> */
    public static function placements(): array
    {
        return [
            'home_mid' => 'Ana sayfa — orta üçlü',
            'home_wide' => 'Ana sayfa — geniş şerit',
            'home_grid' => 'Ana sayfa — ızgara',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('placement')->label('Konum')->options(self::placements())->required(),
            FileUpload::make('image')->label('Görsel')->image()->directory('afisler')->imageEditor()->required(),
            Translatable::text('title', 'Başlık', false),
            Translatable::text('subtitle', 'Alt başlık', false),
            Translatable::text('button_text', 'Buton yazısı', false),
            TextInput::make('link')->label('Bağlantı')->url()->maxLength(255),
            ColorPicker::make('text_color')->label('Yazı rengi')->default('#141414'),
            TextInput::make('position')->label('Sıra')->numeric()->default(0),
            Toggle::make('is_active')->label('Yayında')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                ImageColumn::make('image')->label('')->width(110)->height(60),
                TextColumn::make('title')->label('Başlık')
                    ->formatStateUsing(fn ($record) => $record->t('title', Locales::default()) ?: '—'),
                TextColumn::make('placement')->label('Konum')->badge()
                    ->formatStateUsing(fn (string $state) => self::placements()[$state] ?? $state),
                TextColumn::make('position')->label('Sıra')->sortable(),
                ToggleColumn::make('is_active')->label('Yayında'),
            ])
            ->filters([
                SelectFilter::make('placement')->label('Konum')->options(self::placements()),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBanners::route('/'),
            'create' => CreateBanner::route('/create'),
            'edit' => EditBanner::route('/{record}/edit'),
        ];
    }
}
