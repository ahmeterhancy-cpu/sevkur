<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Satış';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Kullanıcı';

    protected static ?string $pluralModelLabel = 'Müşteriler';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kullanıcı')
                ->schema([
                    TextInput::make('name')->label('Ad soyad')->required()->maxLength(100),
                    TextInput::make('email')->label('E-posta')->email()->required()->unique(ignoreRecord: true),
                    TextInput::make('phone')->label('Telefon')->maxLength(30),
                    Select::make('role')->label('Rol')->options([
                        User::ROLE_CUSTOMER => 'Müşteri',
                        User::ROLE_STAFF => 'Personel',
                        User::ROLE_ADMIN => 'Yönetici',
                    ])->default(User::ROLE_CUSTOMER)->required()
                        ->helperText('Personel ve yönetici bu panele girebilir.'),
                    Select::make('locale')->label('Dil')->options([
                        'tr' => 'Türkçe', 'en' => 'English', 'bg' => 'Български', 'el' => 'Ελληνικά',
                    ])->default('tr'),
                    TextInput::make('password')
                        ->label('Şifre')
                        ->password()
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->required(fn (string $operation) => $operation === 'create')
                        ->helperText('Boş bırakırsanız mevcut şifre korunur.'),
                    Toggle::make('is_active')->label('Hesap aktif')->default(true),
                    Toggle::make('accepts_marketing')->label('E-bültene kayıtlı'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Ad soyad')->searchable()->sortable(),
                TextColumn::make('email')->label('E-posta')->searchable()->copyable(),
                TextColumn::make('phone')->label('Telefon')->toggleable(),
                TextColumn::make('role')->label('Rol')->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        User::ROLE_ADMIN => 'Yönetici',
                        User::ROLE_STAFF => 'Personel',
                        default => 'Müşteri',
                    })
                    ->color(fn (string $state) => $state === User::ROLE_CUSTOMER ? 'gray' : 'success'),
                TextColumn::make('orders_count')->label('Sipariş')->counts('orders')->sortable(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('created_at')->label('Kayıt')->date('d.m.Y')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('role')->label('Rol')->options([
                    User::ROLE_CUSTOMER => 'Müşteri',
                    User::ROLE_STAFF => 'Personel',
                    User::ROLE_ADMIN => 'Yönetici',
                ]),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
