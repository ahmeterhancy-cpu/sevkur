<?php

namespace App\Filament\Resources\Coupons;

use App\Filament\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Resources\Coupons\Pages\EditCoupon;
use App\Filament\Resources\Coupons\Pages\ListCoupons;
use App\Models\Coupon;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static string|UnitEnum|null $navigationGroup = 'Satış';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Kupon';

    protected static ?string $pluralModelLabel = 'Kuponlar';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->label('Kupon kodu')
                ->required()->unique(ignoreRecord: true)
                ->maxLength(40)
                ->helperText('Müşteri sepette bu kodu yazar. Büyük/küçük harf ayrımı yoktur.'),
            Select::make('type')->label('İndirim türü')->options([
                'percent' => 'Yüzde (%)',
                'fixed' => 'Sabit tutar (₺)',
            ])->default('percent')->required()->live(),
            TextInput::make('value')->label('Değer')->numeric()->required()->minValue(0)
                ->suffix(fn ($get) => $get('type') === 'percent' ? '%' : '₺'),
            TextInput::make('min_total')->label('Alt limit (₺)')->numeric()->minValue(0)
                ->helperText('Bu tutarın altındaki sepetlerde geçmez.'),
            TextInput::make('max_uses')->label('Toplam kullanım hakkı')->numeric()->minValue(1),
            TextInput::make('max_uses_per_user')->label('Kişi başı hak')->numeric()->minValue(1),
            Toggle::make('free_shipping')->label('Kargo bedava olsun'),
            Toggle::make('is_active')->label('Aktif')->default(true),
            DateTimePicker::make('starts_at')->label('Başlangıç'),
            DateTimePicker::make('ends_at')->label('Bitiş'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')->label('Kod')->searchable()->copyable(),
                TextColumn::make('value')->label('İndirim')
                    ->formatStateUsing(fn ($state, Coupon $record) => $record->type === 'percent'
                        ? '%'.rtrim(rtrim(number_format((float) $state, 2, ',', '.'), '0'), ',')
                        : money_base($state)),
                TextColumn::make('min_total')->label('Alt limit')
                    ->formatStateUsing(fn ($state) => $state ? money_base($state) : '—'),
                TextColumn::make('used_count')->label('Kullanım')
                    ->formatStateUsing(fn ($state, Coupon $record) => $record->max_uses ? "$state / {$record->max_uses}" : $state),
                TextColumn::make('ends_at')->label('Bitiş')->dateTime('d.m.Y')->placeholder('Süresiz'),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoupons::route('/'),
            'create' => CreateCoupon::route('/create'),
            'edit' => EditCoupon::route('/{record}/edit'),
        ];
    }
}
