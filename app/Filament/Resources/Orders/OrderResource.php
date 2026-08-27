<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Services\OrderService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|UnitEnum|null $navigationGroup = 'Satış';

    protected static ?int $navigationSort = 0;

    protected static ?string $modelLabel = 'Sipariş';

    protected static ?string $pluralModelLabel = 'Siparişler';

    protected static ?string $recordTitleAttribute = 'number';

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return [
            'pending' => 'Onay bekliyor',
            'processing' => 'Hazırlanıyor',
            'shipped' => 'Kargoya verildi',
            'delivered' => 'Teslim edildi',
            'cancelled' => 'İptal edildi',
            'refunded' => 'İade edildi',
        ];
    }

    /** @return array<string, string> */
    public static function paymentOptions(): array
    {
        return [
            'pending' => 'Ödeme bekleniyor',
            'paid' => 'Ödendi',
            'failed' => 'Başarısız',
            'refunded' => 'İade edildi',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Durum')
                ->schema([
                    TextInput::make('number')->label('Sipariş no')->disabled(),
                    Select::make('status')->label('Sipariş durumu')->options(self::statusOptions())->required(),
                    Select::make('payment_status')->label('Ödeme durumu')->options(self::paymentOptions())->required()
                        ->helperText('Elle "Ödendi" yapmak stoğu değiştirmez; yalnızca kaydı günceller.'),
                    TextInput::make('tracking_number')->label('Kargo takip no'),
                    DateTimePicker::make('shipped_at')->label('Kargoya veriliş'),
                    DateTimePicker::make('delivered_at')->label('Teslim tarihi'),
                    Textarea::make('admin_note')->label('İç not')->rows(2)->columnSpanFull(),
                ])
                ->columns(3),

            Section::make('Müşteri')
                ->schema([
                    TextInput::make('customer_first_name')->label('Ad'),
                    TextInput::make('customer_last_name')->label('Soyad'),
                    TextInput::make('customer_email')->label('E-posta')->email(),
                    TextInput::make('customer_phone')->label('Telefon'),
                    Textarea::make('note')->label('Müşteri notu')->rows(2)->disabled()->columnSpanFull(),
                ])
                ->columns(4),

            Section::make('Teslimat adresi')
                ->schema([
                    TextInput::make('shipping_address.city')->label('İl'),
                    TextInput::make('shipping_address.district')->label('İlçe'),
                    TextInput::make('shipping_address.postcode')->label('Posta kodu'),
                    Textarea::make('shipping_address.address')->label('Adres')->rows(2)->columnSpanFull(),
                ])
                ->columns(3),

            Section::make('Ürünler')
                ->schema([
                    Repeater::make('items')
                        ->label('')
                        ->relationship()
                        ->schema([
                            TextInput::make('name')->label('Ürün')->disabled(),
                            TextInput::make('sku')->label('Kod')->disabled(),
                            TextInput::make('quantity')->label('Adet')->disabled(),
                            TextInput::make('unit_price')->label('Birim')->prefix('₺')->disabled(),
                            TextInput::make('line_total')->label('Toplam')->prefix('₺')->disabled(),
                        ])
                        ->columns(5)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->columnSpanFull(),
                ]),

            Section::make('Tutarlar (₺)')
                ->schema([
                    TextInput::make('subtotal')->label('Ara toplam')->disabled(),
                    TextInput::make('discount_total')->label('İndirim')->disabled(),
                    TextInput::make('shipping_total')->label('Kargo')->disabled(),
                    TextInput::make('grand_total')->label('Genel toplam')->disabled(),
                    TextInput::make('coupon_code')->label('Kupon')->disabled(),
                    TextInput::make('display_currency')->label('Görüntülenen para birimi')->disabled(),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')->label('Sipariş no')->searchable()->copyable(),
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('customer_first_name')
                    ->label('Müşteri')
                    ->formatStateUsing(fn ($state, Order $record) => $record->customerName())
                    ->description(fn (Order $record) => $record->customer_email)
                    ->searchable(['customer_first_name', 'customer_last_name', 'customer_email']),
                TextColumn::make('grand_total')->label('Tutar')->money('TRY')->sortable(),
                TextColumn::make('payment_status')
                    ->label('Ödeme')->badge()
                    ->formatStateUsing(fn (string $state) => self::paymentOptions()[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'paid' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Durum')->badge()
                    ->formatStateUsing(fn (string $state) => self::statusOptions()[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'delivered' => 'success',
                        'shipped' => 'info',
                        'cancelled', 'refunded' => 'danger',
                        'processing' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('items_count')->label('Kalem')->counts('items')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Durum')->options(self::statusOptions()),
                SelectFilter::make('payment_status')->label('Ödeme')->options(self::paymentOptions()),
                Filter::make('bugun')
                    ->label('Bugünkü siparişler')
                    ->query(fn (Builder $query) => $query->whereDate('created_at', today())),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('kargola')
                    ->label('Kargoya ver')
                    ->icon('heroicon-o-truck')
                    ->visible(fn (Order $record) => in_array($record->status, ['pending', 'processing'], true))
                    ->schema([
                        TextInput::make('tracking_number')->label('Takip numarası'),
                    ])
                    ->action(function (Order $record, array $data): void {
                        $record->forceFill([
                            'status' => 'shipped',
                            'shipped_at' => now(),
                            'tracking_number' => $data['tracking_number'] ?? null,
                        ])->save();

                        $record->log('shipped', 'Kargoya verildi');

                        Notification::make()->title('Sipariş kargoya verildi')->success()->send();
                    }),
                Action::make('teslim')
                    ->label('Teslim edildi')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Order $record) => $record->status === 'shipped')
                    ->requiresConfirmation()
                    ->action(function (Order $record): void {
                        $record->forceFill(['status' => 'delivered', 'delivered_at' => now()])->save();
                        $record->log('delivered', 'Teslim edildi');
                        Notification::make()->title('Sipariş teslim edildi olarak işaretlendi')->success()->send();
                    }),
                Action::make('iptal')
                    ->label('İptal et')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Order $record) => $record->status !== 'cancelled')
                    ->requiresConfirmation()
                    ->modalDescription('Stok geri yüklenecek. Ödemesi alınmış siparişlerde iade işlemini ayrıca yapmanız gerekir.')
                    ->action(function (Order $record): void {
                        app(OrderService::class)->cancel($record, 'Yönetici iptali');
                        Notification::make()->title('Sipariş iptal edildi, stok geri yüklendi')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereIn('status', ['pending', 'processing'])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
