<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Support\Translatable;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Support\Locales;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 0;

    protected static ?string $modelLabel = 'Ürün';

    protected static ?string $pluralModelLabel = 'Ürünler';

    protected static ?string $recordTitleAttribute = 'slug';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Ürün')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Genel')->schema([
                        Translatable::text('name', 'Ürün adı'),
                        TextInput::make('slug')
                            ->label('URL adı')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(200)
                            ->helperText('Boş bırakırsanız ürün adından üretilir.')
                            ->dehydrateStateUsing(fn (?string $state, $get) => $state ?: Str::slug($get('name.'.Locales::default()) ?? '')),
                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'slug')
                            ->getOptionLabelFromRecordUsing(fn (Category $record) => trim(($record->parent?->t('name', Locales::default()).' › ' ?? '').$record->t('name', Locales::default()), ' ›'))
                            ->searchable()->preload()->required(),
                        Select::make('brand_id')
                            ->label('Marka')
                            ->relationship('brand', 'slug')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->t('name', Locales::default()))
                            ->searchable()->preload(),
                        TextInput::make('sku')->label('Ürün kodu')->unique(ignoreRecord: true)->maxLength(60),
                        Translatable::textarea('short_description', 'Kısa açıklama', 3),
                        Translatable::body('description', 'Açıklama'),
                    ])->columns(2),

                    Tab::make('Fiyat ve stok')->schema([
                        TextInput::make('price')
                            ->label('Satış fiyatı (₺)')
                            ->numeric()->required()->minValue(0)->step('0.01')
                            ->helperText('Tüm fiyatlar TRY girilir; vitrindeki EUR/BGN karşılığı kurdan hesaplanır.'),
                        TextInput::make('compare_at_price')
                            ->label('Üstü çizili fiyat (₺)')
                            ->numeric()->minValue(0)->step('0.01')
                            ->helperText('Satış fiyatından yüksekse indirim rozeti görünür.'),
                        TextInput::make('cost_price')->label('Maliyet (₺)')->numeric()->minValue(0)->step('0.01'),
                        Toggle::make('manage_stock')->label('Stok takibi yapılsın')->default(true)->live(),
                        TextInput::make('stock')->label('Stok adedi')->numeric()->default(0)
                            ->visible(fn ($get) => (bool) $get('manage_stock')),
                        TextInput::make('stock_alert')->label('Kritik stok')->numeric()->default(3)
                            ->visible(fn ($get) => (bool) $get('manage_stock')),
                        TextInput::make('sold_count')->label('Satılan adet')->numeric()->default(0)
                            ->helperText('Vitrindeki "satılan" göstergesi için.'),
                        TextInput::make('sale_target')->label('Kampanya hedefi')->numeric()
                            ->helperText('Doluluk çubuğu için hedef adet. Boşsa çubuk gösterilmez.'),
                        DateTimePicker::make('sale_ends_at')->label('Kampanya bitişi')
                            ->helperText('Ürün sayfasında geri sayım gösterilir.'),
                        TextInput::make('delivery_days')->label('Teslim süresi (gün)')->numeric(),
                    ])->columns(2),

                    Tab::make('Görseller')->schema([
                        FileUpload::make('cover')->label('Kapak görseli')->image()->directory('urunler')->imageEditor(),
                        FileUpload::make('hover_image')->label('Üzerine gelince')->image()->directory('urunler'),
                        Repeater::make('images')
                            ->label('Galeri')
                            ->relationship()
                            ->schema([
                                FileUpload::make('path')->label('Görsel')->image()->directory('urunler')->required(),
                                TextInput::make('alt')->label('Alternatif metin'),
                                TextInput::make('position')->label('Sıra')->numeric()->default(0),
                            ])
                            ->orderColumn('position')
                            ->columns(3)
                            ->columnSpanFull()
                            ->defaultItems(0),
                    ])->columns(2),

                    Tab::make('Seçenekler')->schema([
                        Select::make('attributeValues')
                            ->label('Özellikler')
                            ->relationship('attributeValues', 'slug')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->attribute?->t('name', Locales::default()).': '.$record->t('name', Locales::default()))
                            ->multiple()->searchable()->preload()
                            ->helperText('Mağaza filtrelerinde kullanılır.')
                            ->columnSpanFull(),

                        Repeater::make('variants')
                            ->label('Varyantlar')
                            ->relationship()
                            ->schema([
                                // Her varyant secenek KOMBINASYONUDUR: Renk + Ölçü gibi.
                                // Ad bos birakilirsa seceneklerden dört dilde üretilir.
                                ...self::variantOptionFields(),

                                TextInput::make('sku')->label('Kod'),
                                TextInput::make('price')->label('Fiyat (₺)')->numeric()
                                    ->helperText('Boşsa ürün fiyatı geçerlidir.'),
                                TextInput::make('stock')->label('Stok')->numeric()->default(0),
                                FileUpload::make('image')->label('Görsel')->image()->directory('urunler'),
                                Toggle::make('is_active')->label('Aktif')->default(true),

                                // Ad zorunlu DEGIL: bos birakilirsa ProductVariant::label()
                                // adi seceneklerden dort dilde uretir.
                                Translatable::text('name', 'Varyant adı (isteğe bağlı)', requiredDefault: false),
                            ])
                            ->itemLabel(fn (array $state): ?string => self::variantItemLabel($state))
                            ->orderColumn('position')
                            ->columns(3)
                            ->columnSpanFull()
                            ->defaultItems(0)
                            ->helperText('Seçenekleri boş bırakırsanız varyant tek başına satılır. '
                                .'Renk ve Ölçü seçerseniz müşteri ürün sayfasında bunları seçebilir.'),

                        Select::make('related')
                            ->label('Benzer ürünler')
                            ->relationship('related', 'slug')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->t('name', Locales::default()))
                            ->multiple()->searchable()->preload()
                            ->columnSpanFull(),
                    ]),

                    Tab::make('Yayın')->schema([
                        Toggle::make('is_active')->label('Yayında')->default(true),
                        Toggle::make('is_featured')->label('Vitrinde göster'),
                        Toggle::make('is_new')->label('Yeni rozeti'),
                        Toggle::make('is_trending')->label('Trend rozeti'),
                        DateTimePicker::make('published_at')->label('Yayın tarihi'),
                        TextInput::make('position')->label('Sıra')->numeric()->default(0),
                        TextInput::make('dimensions')->label('Ölçüler')->maxLength(120),
                        TextInput::make('weight')->label('Ağırlık (kg)')->numeric()->step('0.01'),
                    ])->columns(2),
                ]),
        ]);
    }

    /**
     * Varyant satirindaki secenek seciciler (Renk, Ölçü…).
     *
     * Alanlar `is_variant` isaretli niteliklerden uretilir; yeni bir eksen
     * eklemek icin Nitelikler ekranindan isaretlemek yeterlidir, burasi
     * degismez.
     *
     * @return array<int, Select>
     */
    protected static function variantOptionFields(): array
    {
        return Attribute::with('values')
            ->where('is_variant', true)
            ->orderBy('position')
            ->get()
            ->map(fn (Attribute $attribute) => Select::make('options.'.$attribute->slug)
                ->label($attribute->t('name', Locales::default()))
                ->options(
                    $attribute->values
                        ->mapWithKeys(fn (AttributeValue $value) => [
                            $value->slug => $value->t('name', Locales::default()),
                        ])
                        ->all()
                )
                ->searchable()
                ->placeholder('—'))
            ->all();
    }

    /** Katlanmis varyant satirinda gorunen baslik: "Ceviz / 3’lü". */
    protected static function variantItemLabel(array $state): ?string
    {
        $name = trim((string) ($state['name'][Locales::default()] ?? ''));

        if ($name !== '') {
            return $name;
        }

        $dictionary = AttributeValue::dictionary();
        $parts = [];

        foreach (array_filter($state['options'] ?? []) as $attributeSlug => $valueSlug) {
            $value = $dictionary[$attributeSlug][$valueSlug] ?? null;
            $parts[] = $value ? $value->t('name', Locales::default()) : $valueSlug;
        }

        return $parts === [] ? null : implode(' / ', $parts);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('cover')->label('')->square(),
                TextColumn::make('name')
                    ->label('Ürün')
                    ->state(fn ($record) => $record->t('name', Locales::default()))
                    ->description(fn (Product $record) => $record->sku)
                    ->searchable(query: fn ($query, string $search) => $query
                        ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%'])
                        ->orWhereRaw('LOWER(sku) LIKE ?', ['%'.mb_strtolower($search).'%'])),
                TextColumn::make('category.slug')
                    ->label('Kategori')
                    ->formatStateUsing(fn ($state, Product $record) => $record->category?->t('name', Locales::default()) ?? '—')
                    ->toggleable(),
                TextColumn::make('price')->label('Fiyat')->formatStateUsing(fn ($state) => money_base($state))->sortable(),
                TextColumn::make('stock')->label('Stok')->sortable()
                    ->badge()
                    ->color(fn (Product $record) => match (true) {
                        ! $record->manage_stock => 'gray',
                        $record->stock <= 0 => 'danger',
                        $record->isLowStock() => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn ($state, Product $record) => $record->manage_stock ? $state : 'Sınırsız'),
                TextColumn::make('sold_count')->label('Satılan')->sortable()->toggleable(),
                TextColumn::make('rating_avg')->label('Puan')->sortable()->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_featured')->label('Vitrin'),
                ToggleColumn::make('is_active')->label('Yayında'),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->options(fn () => Category::orderBy('position')->get()
                        ->mapWithKeys(fn (Category $c) => [$c->id => $c->t('name', Locales::default())])->all())
                    ->searchable(),
                TernaryFilter::make('is_active')->label('Yayın durumu'),
                TernaryFilter::make('is_featured')->label('Vitrin'),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()->label('Kopyala')
                    ->beforeReplicaSaved(function (Product $replica): void {
                        $replica->slug = $replica->slug.'-kopya-'.Str::lower(Str::random(4));
                        $replica->sku = $replica->sku ? $replica->sku.'-K' : null;
                        $replica->is_active = false;
                    }),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    /** Baslikta slug yerine urunun adi gorunsun. */
    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return $record?->t('name', Locales::default());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('is_active', true)->count();
    }
}
