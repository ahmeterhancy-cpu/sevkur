<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Support\Translatable;
use App\Models\Page;
use App\Support\Locales;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'İçerik';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Sayfa';

    protected static ?string $pluralModelLabel = 'Sayfalar';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Translatable::text('title', 'Başlık'),
            TextInput::make('slug')->label('URL adı')->required()->unique(ignoreRecord: true)->maxLength(160),
            TextInput::make('position')->label('Sıra')->numeric()->default(0),
            Toggle::make('is_active')->label('Yayında')->default(true),
            Toggle::make('show_in_footer')->label('Alt menüde göster'),
            Translatable::body('body', 'İçerik'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                TextColumn::make('title')->label('Başlık')
                    ->formatStateUsing(fn ($record) => $record->t('title', Locales::default()))
                    ->searchable(query: fn ($query, string $search) => $query->whereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($search).'%'])),
                TextColumn::make('slug')->label('URL'),
                ToggleColumn::make('show_in_footer')->label('Alt menü'),
                ToggleColumn::make('is_active')->label('Yayında'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
