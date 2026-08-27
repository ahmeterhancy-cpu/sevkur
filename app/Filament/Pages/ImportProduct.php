<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Brand;
use App\Models\Category;
use App\Services\Import\ProductImporter;
use App\Support\Locales;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use UnitEnum;

/**
 * Bir urun sayfasinin adresinden ad, aciklama, fiyat ve gorselleri cekip
 * taslak urun olusturur.
 *
 * Iki adim: once "Getir" ile sayfa okunur ve form doldurulur, yonetici
 * duzenler, sonra kaydeder. Urun PASIF olusur — fiyat ve ceviri kontrol
 * edilmeden yayina cikmasin.
 */
class ImportProduct extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-on-square';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'URL’den Ürün Çek';

    protected static ?string $navigationLabel = 'URL’den Ürün Çek';

    protected string $view = 'filament.pages.import-product';

    /** @var array<string, mixed> */
    public ?array $data = [];

    /** Sayfa okundu mu; okunmadan kaydet dugmesi cikmaz. */
    public bool $fetched = false;

    /** @var array<int, string> */
    public array $notes = [];

    public function mount(): void
    {
        $this->form->fill(['stock' => 0]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kaynak')
                    ->description('Ürün sayfasının adresini yapıştırın. Yalnızca kendi sitenizden ya da izniniz olan tedarikçi kataloglarından içe aktarın.')
                    ->schema([
                        TextInput::make('source_url')
                            ->label('Ürün sayfası adresi')
                            ->url()
                            ->required()
                            ->placeholder('https://tedarikci.example.com/urun/koltuk')
                            ->columnSpanFull(),
                    ]),

                Section::make('Ürün bilgileri')
                    ->visible(fn () => $this->fetched)
                    ->schema([
                        TextInput::make('name')
                            ->label('Ürün adı ('.strtoupper(Locales::default()).')')
                            ->required()
                            ->maxLength(200)
                            ->helperText('Diğer diller boş kalır; çeviri girilene kadar varsayılan dil gösterilir.')
                            ->columnSpanFull(),

                        TextInput::make('sku')->label('Ürün kodu')->maxLength(60),

                        Select::make('category_id')
                            ->label('Kategori')
                            ->options(fn () => Category::orderBy('position')->get()
                                ->mapWithKeys(fn (Category $c) => [$c->id => $c->t('name', Locales::default())])->all())
                            ->searchable()
                            ->required(),

                        Select::make('brand_id')
                            ->label('Marka')
                            ->options(fn () => Brand::orderBy('position')->get()
                                ->mapWithKeys(fn (Brand $b) => [$b->id => $b->t('name', Locales::default())])->all())
                            ->searchable(),

                        TextInput::make('price')
                            ->label('Satış fiyatı (₺)')
                            ->numeric()->required()->minValue(0)
                            ->helperText('Kaynak sayfa başka para biriminde olabilir — kontrol edin.'),

                        TextInput::make('compare_at_price')->label('Üstü çizili fiyat (₺)')->numeric()->minValue(0),

                        TextInput::make('stock')->label('Stok')->numeric()->default(0),

                        Textarea::make('short_description')->label('Kısa açıklama')->rows(3)->columnSpanFull(),
                        Textarea::make('description')->label('Açıklama')->rows(8)->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Görseller')
                    ->visible(fn () => $this->fetched)
                    ->description('İşaretlediğiniz görseller indirilip ürüne eklenir. İlki kapak, ikincisi üzerine gelince görünen kare olur.')
                    ->schema([
                        CheckboxList::make('selected_images')
                            ->label('İndirilecek görseller')
                            ->options(fn (): array => collect($this->data['images'] ?? [])
                                ->mapWithKeys(fn (string $url) => [$url => $url])
                                ->all())
                            ->columns(1)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('getir')
                ->label('Sayfayı getir')
                ->icon('heroicon-o-arrow-path')
                ->action('fetch'),
        ];
    }

    public function fetch(): void
    {
        $url = trim((string) ($this->data['source_url'] ?? ''));

        if ($url === '') {
            Notification::make()->title('Önce bir adres girin')->warning()->send();

            return;
        }

        $imported = app(ProductImporter::class)->preview($url);

        $this->notes = $imported->notes;

        if (! $imported->hasEnough()) {
            $this->fetched = false;

            Notification::make()
                ->title('Ürün okunamadı')
                ->body(implode(' ', $imported->notes) ?: 'Sayfada ürün bilgisi bulunamadı.')
                ->danger()
                ->send();

            return;
        }

        // Okunanlari forma yaz, yoneticinin girdiklerini ezme
        $state = $imported->toFormState();
        $state['stock'] = $this->data['stock'] ?? 0;
        $state['category_id'] = $this->data['category_id'] ?? null;
        $state['brand_id'] = $this->data['brand_id'] ?? null;

        $this->form->fill($state);
        $this->fetched = true;

        Notification::make()
            ->title('Sayfa okundu')
            ->body(count($imported->images).' görsel bulundu. Bilgileri kontrol edip kaydedin.')
            ->success()
            ->send();
    }

    public function save(): void
    {
        if (! $this->fetched) {
            Notification::make()->title('Önce sayfayı getirin')->warning()->send();

            return;
        }

        $data = $this->form->getState();

        $result = app(ProductImporter::class)->create($data);

        Notification::make()
            ->title('Ürün taslak olarak oluşturuldu')
            ->body($result['downloaded'].' görsel indirildi'
                .($result['failed'] > 0 ? ', '.$result['failed'].' tanesi alınamadı' : '')
                .'. Ürün pasif; kontrol edip yayına alın.')
            ->success()
            ->send();

        $this->redirect(ProductResource::getUrl('edit', ['record' => $result['product']]));
    }
}
