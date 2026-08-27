<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Support\Translatable;
use App\Models\Category;
use App\Support\Locales;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Kategori';

    protected static ?string $pluralModelLabel = 'Kategoriler';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kategori')
                ->schema([
                    Translatable::text('name', 'Ad'),
                    TextInput::make('slug')
                        ->label('URL adı')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('Adreste görünen kısım. Değiştirirseniz eski bağlantılar kırılır.')
                        ->maxLength(160),
                    Select::make('parent_id')
                        ->label('Üst kategori')
                        ->relationship('parent', 'slug')
                        ->getOptionLabelFromRecordUsing(fn (Category $record) => $record->t('name', Locales::default()))
                        ->searchable()
                        ->preload()
                        ->placeholder('Ana kategori'),
                    Translatable::textarea('description', 'Açıklama'),
                ])
                ->columns(2),

            Section::make('Görseller ve görünürlük')
                ->schema([
                    FileUpload::make('image')
                        ->label('Kategori görseli')
                        ->image()
                        ->directory('kategoriler')
                        ->imageEditor(),
                    FileUpload::make('banner')
                        ->label('Üst afiş')
                        ->image()
                        ->directory('kategoriler'),
                    TextInput::make('position')->label('Sıra')->numeric()->default(0),
                    Toggle::make('is_active')->label('Yayında')->default(true),
                    Toggle::make('is_featured')->label('Ana sayfada göster')->default(false),
                    Toggle::make('show_in_menu')->label('Menüde göster')->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                ImageColumn::make('image')->label('')->square(),
                TextColumn::make('name')
                    ->label('Ad')
                    ->state(fn ($record) => $record->t('name', Locales::default()))
                    ->description(fn (Category $record) => $record->parent?->t('name', Locales::default()))
                    ->searchable(query: fn ($query, string $search) => $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%'])),
                TextColumn::make('slug')->label('URL')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('products_count')->label('Ürün')->counts('products')->sortable(),
                TextColumn::make('position')->label('Sıra')->sortable(),
                ToggleColumn::make('is_featured')->label('Vitrin'),
                ToggleColumn::make('is_active')->label('Yayında'),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Yayın durumu'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
