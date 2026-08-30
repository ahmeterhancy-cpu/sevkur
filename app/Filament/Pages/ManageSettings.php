<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Locales;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use UnitEnum;

/**
 * Magaza ayarlari — tek sayfada toplanan anahtar/deger kayitlari.
 */
class ManageSettings extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Ayarlar';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Mağaza Ayarları';

    protected static ?string $navigationLabel = 'Mağaza Ayarları';

    protected string $view = 'filament.pages.manage-settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    /** Dile gore ayri girilen SEO metinleri. */
    /**
     * Musteriye GORUNEN metinler dile gore ayri saklanir.
     *
     * Bunlar disarida kalirsa 4 dilli sitede ana sayfa basliklari ve alt
     * bilgi vaatleri her dilde Turkce gorunur.
     */
    private const LOCALISED_KEYS = [
        'site_title', 'site_description',
        'top_bar_text', 'working_hours',
        'promise_shipping', 'promise_return_title', 'promise_return',
        'promise_secure_title', 'promise_secure',
        'promise_support_title', 'promise_support',
        'home_categories_title', 'home_bestsellers_title', 'home_bestsellers_sub',
        'home_deals_title', 'home_new_title', 'home_blog_title', 'footer_about',
    ];

    /** Sayfada yonetilen ayar anahtarlari. */
    private const KEYS = [
        'site_name', 'site_title', 'site_description', 'logo', 'logo_light', 'favicon',
        'phone', 'whatsapp_number', 'email', 'email_support', 'address', 'working_hours', 'map_embed',
        'social_facebook', 'social_instagram', 'social_youtube', 'social_whatsapp',
        'free_shipping_over', 'top_bar_text',
        'promise_shipping', 'promise_return_title', 'promise_return',
        'promise_secure_title', 'promise_secure', 'promise_support_title', 'promise_support',
        'home_categories_title', 'home_bestsellers_title', 'home_bestsellers_sub',
        'home_deals_title', 'home_new_title', 'home_blog_title', 'footer_about',
    ];

    public function mount(): void
    {
        $values = [];

        foreach ($this->managedKeys() as $key) {
            $values[$key] = Setting::get($key);
        }

        $this->form->fill($values);
    }

    /**
     * Varsayilan dil "site_title" anahtarini kullanir, digerleri
     * "site_title_en" gibi son ekli anahtarlara yazilir.
     *
     * @return array<int, string>
     */
    private function managedKeys(): array
    {
        $keys = self::KEYS;

        foreach (self::LOCALISED_KEYS as $key) {
            foreach (Locales::all() as $locale) {
                if ($locale !== Locales::default()) {
                    $keys[] = $key.'_'.$locale;
                }
            }
        }

        return array_values(array_unique($keys));
    }

    /** Bir SEO metninin dile gore anahtar adi. */
    private static function localisedKey(string $key, string $locale): string
    {
        return $locale === Locales::default() ? $key : $key.'_'.$locale;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Ayarlar')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Kimlik')->schema([
                            TextInput::make('site_name')->label('Site adı')->required()
                                ->helperText('Her dilde aynı kalır (marka adı).'),

                            Tabs::make('SEO metinleri')
                                ->columnSpanFull()
                                ->tabs(array_map(
                                    fn (string $locale) => Tab::make(strtoupper($locale))->schema([
                                        TextInput::make(self::localisedKey('site_title', $locale))
                                            ->label('Tarayıcı başlığı')
                                            ->helperText('Ana sayfada ve sekmede görünür.'),
                                        Textarea::make(self::localisedKey('site_description', $locale))
                                            ->label('Site açıklaması (arama sonuçları)')
                                            ->rows(2),
                                    ]),
                                    Locales::all()
                                )),

                            FileUpload::make('logo')->label('Logo (açık zemin)')->image()->directory('marka'),
                            FileUpload::make('logo_light')->label('Logo (koyu zemin)')->image()->directory('marka'),
                            FileUpload::make('favicon')->label('Favicon')->directory('marka'),
                        ])->columns(2),

                        Tab::make('İletişim')->schema([
                            TextInput::make('phone')->label('Telefon'),
                            TextInput::make('whatsapp_number')
                                ->label('WhatsApp numarası')
                                ->helperText('Boş bırakırsanız WhatsApp bağlantısı gösterilmez.'),
                            TextInput::make('email')->label('E-posta')->email(),
                            TextInput::make('email_support')
                                ->label('Destek e-postası')
                                ->email()
                                ->helperText('İsteğe bağlı; iletişim sayfasında ikinci adres olarak görünür.'),
                            Textarea::make('address')->label('Adres')->rows(2),
                            Textarea::make('map_embed')->label('Harita gömme kodu')->rows(3)->columnSpanFull()
                                ->helperText('OpenStreetMap veya Google Haritalar iframe kodu.'),
                            TextInput::make('social_facebook')->label('Facebook')->url(),
                            TextInput::make('social_instagram')->label('Instagram')->url(),
                            TextInput::make('social_youtube')->label('YouTube')->url(),
                            TextInput::make('social_whatsapp')->label('WhatsApp bağlantısı')->url(),
                        ])->columns(2),

                        Tab::make('Mağaza')->schema([
                            TextInput::make('free_shipping_over')->label('Ücretsiz kargo alt sınırı (₺)')->numeric()
                                ->helperText('Üst şeritte ve alt bilgide gösterilir. Kargo yöntemlerindeki sınır ayrıca ayarlanır.'),
                            Section::make('Üst şerit ve güvence kutuları')
                                ->description('Her dil için ayrı girilir; boş bırakılan dil Türkçeye düşer.')
                                ->schema([
                                    Tabs::make('Güvence metinleri')
                                        ->columnSpanFull()
                                        ->tabs(array_map(
                                            fn (string $locale) => Tab::make(strtoupper($locale))->schema([
                                                TextInput::make(self::localisedKey('top_bar_text', $locale))
                                                    ->label('Üst şerit yazısı')->columnSpanFull(),
                                                TextInput::make(self::localisedKey('promise_shipping', $locale))
                                                    ->label('Kargo açıklaması'),
                                                TextInput::make(self::localisedKey('working_hours', $locale))
                                                    ->label('Çalışma saatleri'),
                                                TextInput::make(self::localisedKey('promise_return_title', $locale))
                                                    ->label('İade başlığı'),
                                                TextInput::make(self::localisedKey('promise_return', $locale))
                                                    ->label('İade açıklaması'),
                                                TextInput::make(self::localisedKey('promise_secure_title', $locale))
                                                    ->label('Ödeme başlığı'),
                                                TextInput::make(self::localisedKey('promise_secure', $locale))
                                                    ->label('Ödeme açıklaması'),
                                                TextInput::make(self::localisedKey('promise_support_title', $locale))
                                                    ->label('Destek başlığı'),
                                                TextInput::make(self::localisedKey('promise_support', $locale))
                                                    ->label('Destek açıklaması'),
                                            ])->columns(2),
                                            Locales::all()
                                        )),
                                ])->columnSpanFull(),
                        ])->columns(2),

                        Tab::make('Ana sayfa')->schema([
                            Tabs::make('Bölüm başlıkları')
                                ->columnSpanFull()
                                ->tabs(array_map(
                                    fn (string $locale) => Tab::make(strtoupper($locale))->schema([
                                        TextInput::make(self::localisedKey('home_categories_title', $locale))
                                            ->label('Kategoriler bölümü başlığı'),
                                        TextInput::make(self::localisedKey('home_bestsellers_title', $locale))
                                            ->label('Çok satanlar başlığı'),
                                        TextInput::make(self::localisedKey('home_bestsellers_sub', $locale))
                                            ->label('Çok satanlar alt başlığı'),
                                        TextInput::make(self::localisedKey('home_deals_title', $locale))
                                            ->label('Fırsatlar başlığı'),
                                        TextInput::make(self::localisedKey('home_new_title', $locale))
                                            ->label('Yeni gelenler başlığı'),
                                        TextInput::make(self::localisedKey('home_blog_title', $locale))
                                            ->label('Blog bölümü başlığı'),
                                        Textarea::make(self::localisedKey('footer_about', $locale))
                                            ->label('Alt bilgi tanıtım metni')->rows(3)->columnSpanFull(),
                                    ])->columns(2),
                                    Locales::all()
                                )),

                            Placeholder::make('bolum_notu')
                                ->label('Ana sayfada başka neler yönetilir?')
                                ->content('Slider görselleri → Slider, ara afişler → Afişler, öne çıkan '
                                    .'kategoriler → Kategoriler, fırsat ürünleri → ürünün üstü çizili fiyatı '
                                    .'ve indirim bitişi, blog → Yazılar. Bir bölümün verisi yoksa o bölüm '
                                    .'ana sayfada hiç görünmez.')
                                ->columnSpanFull(),
                        ])->columns(2),
                    ]),
            ])
            ->statePath('data');
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('kaydet')
                ->label('Kaydet')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::putMany($data);

        Notification::make()->title('Ayarlar kaydedildi')->success()->send();
    }
}
