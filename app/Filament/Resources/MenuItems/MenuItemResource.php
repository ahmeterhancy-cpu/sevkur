<?php

namespace App\Filament\Resources\MenuItems;

use App\Filament\Resources\MenuItems\Pages\CreateMenuItem;
use App\Filament\Resources\MenuItems\Pages\EditMenuItem;
use App\Filament\Resources\MenuItems\Pages\ListMenuItems;
use App\Filament\Support\Translatable;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Page;
use App\Support\Locales;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bars-3';

    protected static string|UnitEnum|null $navigationGroup = 'İçerik';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'Menü öğesi';

    protected static ?string $pluralModelLabel = 'Menü';

    /** @var array<string, string> */
    private const TYPES = [
        'custom' => 'Serbest adres',
        'category' => 'Kategori',
        'page' => 'Sayfa',
        'shop' => 'Mağaza',
        'blog' => 'Blog',
        'contact' => 'İletişim',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Select::make('type')->label('Bağlantı türü')->options(self::TYPES)
                ->default('custom')->required()->live(),

            Select::make('target_slug')
                ->label('Hedef')
                ->options(fn ($get) => match ($get('type')) {
                    'category' => Category::orderBy('position')->get()
                        ->mapWithKeys(fn (Category $c) => [$c->slug => $c->t('name', Locales::default())])->all(),
                    'page' => Page::orderBy('position')->get()
                        ->mapWithKeys(fn (Page $p) => [$p->slug => $p->t('title', Locales::default())])->all(),
                    default => [],
                })
                ->searchable()
                ->required(fn ($get) => in_array($get('type'), ['category', 'page'], true))
                ->visible(fn ($get) => in_array($get('type'), ['category', 'page'], true))
                ->live()
                // Kategori secilince etiket dort dilde kendiliginden dolsun;
                // yonetici yine de uzerine yazabilir.
                ->afterStateUpdated(function ($state, $get, $set) {
                    if ($get('type') !== 'category' || ! $state) {
                        return;
                    }

                    $category = Category::where('slug', $state)->first();

                    if (! $category) {
                        return;
                    }

                    foreach (Locales::all() as $locale) {
                        if (blank($get('label.'.$locale))) {
                            $set('label.'.$locale, $category->t('name', $locale));
                        }
                    }
                })
                ->helperText(fn ($get) => $get('type') === 'category'
                    ? 'Kategorinin alt kategorileri açılır menüde kendiliğinden görünür.'
                    : null),

            TextInput::make('url')->label('Adres')->maxLength(255)
                ->required(fn ($get) => $get('type') === 'custom')
                ->visible(fn ($get) => $get('type') === 'custom'),

            Select::make('parent_id')
                ->label('Üst menü')
                ->relationship('parent', 'id')
                ->getOptionLabelFromRecordUsing(fn (MenuItem $record) => $record->t('label', Locales::default()))
                ->searchable()->preload()->placeholder('Üst seviye')
                ->helperText('Boş bırakırsanız menü şeridinde görünür.'),

            Toggle::make('is_active')->label('Yayında')->default(true),

            Translatable::text('label', 'Etiket')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            // Menu sirasi surukle birak ile degisir; sayi girmek zorunda degil
            ->reorderable('position')
            ->columns([
                TextColumn::make('label')->label('Etiket')
                    ->state(fn ($record) => $record->t('label', Locales::default()))
                    ->description(fn (MenuItem $record) => $record->parent
                        ? '↳ '.$record->parent->t('label', Locales::default())
                        : null),
                TextColumn::make('type')->label('Tür')->badge()
                    ->formatStateUsing(fn (string $state) => self::TYPES[$state] ?? $state),
                TextColumn::make('target_slug')->label('Hedef')->placeholder('—'),
                TextColumn::make('submenu')->label('Alt menü')
                    ->state(fn (MenuItem $record) => count($record->submenu()) ?: '—')
                    ->tooltip(fn (MenuItem $record) => implode(', ', array_column($record->submenu(), 'label')) ?: null),
                ToggleColumn::make('is_active')->label('Yayında'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenuItems::route('/'),
            'create' => CreateMenuItem::route('/create'),
            'edit' => EditMenuItem::route('/{record}/edit'),
        ];
    }
}
