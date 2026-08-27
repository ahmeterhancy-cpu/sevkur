<?php

namespace App\Filament\Resources\Currencies;

use App\Filament\Resources\Currencies\Pages\ListCurrencies;
use App\Filament\Support\Translatable;
use App\Models\Currency;
use App\Services\CurrencyService;
use App\Support\Locales;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Ayarlar';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Para birimi';

    protected static ?string $pluralModelLabel = 'Para birimleri';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label('Kod')->required()->maxLength(3)
                ->helperText('ISO kodu: TRY, EUR…'),
            TextInput::make('symbol')->label('Simge')->required()->maxLength(8),
            Translatable::text('name', 'Ad'),
            TextInput::make('rate')->label('Otomatik kur (1 ₺ = ?)')->numeric()->step('0.000001')
                ->disabled()
                ->helperText('TCMB kurundan otomatik hesaplanır.'),
            TextInput::make('manual_rate')->label('Manuel kur')->numeric()->step('0.000001')
                ->helperText('Doldurursanız otomatik kuru ezer. Boş bırakın ki TCMB kuru kullanılsın.'),
            TextInput::make('margin_percent')->label('Kur marjı (%)')->numeric()->default(0)
                ->helperText('Kur riskine karşı ek yüzde. Örn. 2 girerseniz fiyatlar %2 yukarı yuvarlanır.'),
            Select::make('position_symbol')->label('Simge konumu')->options([
                'after' => 'Sonda (100,00 ₺)',
                'before' => 'Başta (₺100,00)',
            ])->default('after'),
            TextInput::make('decimals')->label('Ondalık basamak')->numeric()->default(2),
            TextInput::make('position')->label('Sıra')->numeric()->default(0),
            Toggle::make('auto_update')->label('TCMB kurundan güncelle')->default(true),
            Toggle::make('is_active')->label('Vitrinde göster')->default(true),
            Toggle::make('is_base')->label('Taban para birimi')
                ->helperText('Tahsilat bu para biriminden yapılır; yalnızca bir tanesi işaretli olmalıdır.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                TextColumn::make('code')->label('Kod')->badge(),
                TextColumn::make('name')->label('Ad')
                    ->state(fn ($record) => $record->t('name', Locales::default())),
                TextColumn::make('rate')->label('Otomatik kur')
                    ->formatStateUsing(fn ($state) => rtrim(rtrim(number_format((float) $state, 6, ',', '.'), '0'), ',')),
                TextColumn::make('manual_rate')->label('Manuel kur')->placeholder('—'),
                TextColumn::make('margin_percent')->label('Marj')->suffix('%'),
                TextColumn::make('rate_updated_at')->label('Son güncelleme')->dateTime('d.m.Y H:i')->placeholder('—'),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->headerActions([
                Action::make('kurlari_guncelle')
                    ->label('TCMB kurlarını güncelle')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (): void {
                        $result = app(CurrencyService::class)->refreshRates();

                        if ($result['error']) {
                            Notification::make()
                                ->title('Kur güncellenemedi')
                                ->body($result['error'])
                                ->danger()->send();

                            return;
                        }

                        Notification::make()
                            ->title($result['updated'].' para birimi güncellendi')
                            ->success()->send();
                    }),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCurrencies::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
