<?php

namespace App\Filament\Resources\Reviews;

use App\Filament\Resources\Reviews\Pages\EditReview;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Models\Review;
use App\Support\Locales;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Değerlendirme';

    protected static ?string $pluralModelLabel = 'Değerlendirmeler';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_id')
                ->label('Ürün')
                ->relationship('product', 'slug')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->t('name', Locales::default()))
                ->searchable()->preload()->required(),
            TextInput::make('name')->label('Ad')->required(),
            TextInput::make('email')->label('E-posta')->email(),
            Select::make('rating')->label('Puan')->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])->required(),
            TextInput::make('title')->label('Başlık'),
            Toggle::make('is_approved')->label('Yayında'),
            Textarea::make('body')->label('Yorum')->rows(5)->required()->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('product.slug')
                    ->label('Ürün')
                    ->formatStateUsing(fn ($state, Review $record) => $record->product?->t('name', Locales::default()) ?? '—')
                    ->wrap(),
                TextColumn::make('name')->label('Ad')->searchable(),
                TextColumn::make('rating')->label('Puan')->badge()
                    ->color(fn (int $state) => $state >= 4 ? 'success' : ($state >= 3 ? 'warning' : 'danger')),
                TextColumn::make('body')->label('Yorum')->limit(60)->wrap(),
                TextColumn::make('created_at')->label('Tarih')->date('d.m.Y')->sortable(),
                ToggleColumn::make('is_approved')->label('Yayında'),
            ])
            ->filters([
                TernaryFilter::make('is_approved')->label('Onay durumu'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('onayla')
                        ->label('Seçilenleri onayla')
                        ->icon('heroicon-o-check')
                        ->action(fn (Collection $records) => $records->each->update(['is_approved' => true]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
            'edit' => EditReview::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('is_approved', false)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
