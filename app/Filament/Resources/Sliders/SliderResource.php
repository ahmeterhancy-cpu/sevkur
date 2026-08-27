<?php

namespace App\Filament\Resources\Sliders;

use App\Filament\Resources\Sliders\Pages\CreateSlider;
use App\Filament\Resources\Sliders\Pages\EditSlider;
use App\Filament\Resources\Sliders\Pages\ListSliders;
use App\Filament\Support\Translatable;
use App\Models\Slider;
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
use Filament\Tables\Table;
use UnitEnum;

class SliderResource extends Resource
{
    protected static ?string $model = Slider::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|UnitEnum|null $navigationGroup = 'İçerik';

    protected static ?int $navigationSort = 0;

    protected static ?string $modelLabel = 'Ana sayfa afişi';

    protected static ?string $pluralModelLabel = 'Ana sayfa afişleri';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('image')->label('Görsel (1600×760 önerilir)')->image()->directory('slider')->imageEditor()->required(),
            FileUpload::make('mobile_image')->label('Mobil görsel')->image()->directory('slider'),
            Translatable::text('badge', 'Üst etiket', false),
            Translatable::text('title', 'Başlık', false),
            Translatable::textarea('subtitle', 'Alt başlık', 2),
            Translatable::text('button_text', 'Buton yazısı', false),
            TextInput::make('link')->label('Bağlantı')->url()->maxLength(255),
            Select::make('text_align')->label('Yazı hizası')->options([
                'left' => 'Sol', 'center' => 'Orta', 'right' => 'Sağ',
            ])->default('left'),
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
                ImageColumn::make('image')->label('')->width(120)->height(60),
                TextColumn::make('title')->label('Başlık')
                    ->state(fn ($record) => $record->t('title', Locales::default()) ?: '—')->wrap(),
                TextColumn::make('position')->label('Sıra')->sortable(),
                ToggleColumn::make('is_active')->label('Yayında'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSliders::route('/'),
            'create' => CreateSlider::route('/create'),
            'edit' => EditSlider::route('/{record}/edit'),
        ];
    }
}
