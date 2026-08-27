<?php

namespace App\Filament\Resources\ContactMessages;

use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|UnitEnum|null $navigationGroup = 'Satış';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Mesaj';

    protected static ?string $pluralModelLabel = 'İletişim mesajları';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Ad')->disabled(),
            TextInput::make('email')->label('E-posta')->disabled(),
            TextInput::make('phone')->label('Telefon')->disabled(),
            TextInput::make('subject')->label('Konu')->disabled(),
            Textarea::make('message')->label('Mesaj')->rows(8)->disabled()->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('is_read')->label('Okundu')->boolean(),
                TextColumn::make('name')->label('Ad')->searchable(),
                TextColumn::make('email')->label('E-posta')->searchable()->copyable(),
                TextColumn::make('subject')->label('Konu')->placeholder('—'),
                TextColumn::make('message')->label('Mesaj')->limit(60)->wrap(),
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_read')->label('Okunma durumu'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Oku')
                    ->after(fn (ContactMessage $record) => $record->update(['is_read' => true])),
                Action::make('yanitla')
                    ->label('E-posta ile yanıtla')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (ContactMessage $record) => 'mailto:'.$record->email.'?subject='.rawurlencode('Re: '.($record->subject ?: 'Mesajınız')))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactMessages::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('is_read', false)->count();

        return $count > 0 ? (string) $count : null;
    }
}
