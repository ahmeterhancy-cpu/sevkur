<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Support\Translatable;
use App\Models\Post;
use App\Support\Locales;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';

    protected static string|UnitEnum|null $navigationGroup = 'İçerik';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Blog yazısı';

    protected static ?string $pluralModelLabel = 'Blog yazıları';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Translatable::text('title', 'Başlık'),
            TextInput::make('slug')->label('URL adı')->required()->unique(ignoreRecord: true)->maxLength(200),
            Select::make('post_category_id')
                ->label('Kategori')
                ->relationship('category', 'slug')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->t('name', Locales::default()))
                ->searchable()->preload(),
            FileUpload::make('cover')->label('Kapak görseli')->image()->directory('blog')->imageEditor(),
            DateTimePicker::make('published_at')->label('Yayın tarihi')->default(now()),
            Toggle::make('is_active')->label('Yayında')->default(true),
            Toggle::make('is_featured')->label('Öne çıkar'),
            Translatable::textarea('excerpt', 'Özet', 3),
            Translatable::body('body', 'İçerik'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                ImageColumn::make('cover')->label('')->square(),
                TextColumn::make('title')->label('Başlık')
                    ->state(fn ($record) => $record->t('title', Locales::default()))
                    ->wrap()
                    ->searchable(query: fn ($query, string $search) => $query->whereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($search).'%'])),
                TextColumn::make('category.slug')->label('Kategori')
                    ->formatStateUsing(fn ($state, Post $record) => $record->category?->t('name', Locales::default()) ?? '—'),
                TextColumn::make('published_at')->label('Yayın')->date('d.m.Y')->sortable(),
                TextColumn::make('views')->label('Görüntülenme')->toggleable(),
                ToggleColumn::make('is_active')->label('Yayında'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
