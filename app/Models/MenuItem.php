<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasTranslations;

    protected $guarded = [];

    /** @var array<int, string> */
    public array $translatable = ['label'];

    protected function casts(): array
    {
        return [
            'label' => 'array',
            'is_mega' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->where('is_active', true)->orderBy('position');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('position');
    }

    /**
     * Acilir menude gosterilecek alt baglantilar.
     *
     * Oge bir KATEGORIYE baglandiysa alt menu, kategorinin kendi alt
     * kategorilerinden gelir; yonetici yeni bir alt kategori actiginda menuyu
     * ayrica duzenlemek zorunda kalmaz. Diger turlerde menunun kendi alt
     * ogeleri kullanilir.
     *
     * @return array<int, array{label: string, href: string}>
     */
    public function submenu(): array
    {
        if ($this->type === 'category') {
            $category = self::categoryIndex()[$this->target_slug] ?? null;

            if (! $category) {
                return [];
            }

            return $category->activeChildren
                ->map(fn (Category $child) => [
                    'label' => $child->t('name'),
                    'href' => localized_route('category', ['category' => $child->slug]),
                ])
                ->all();
        }

        return $this->children
            ->map(fn (self $child) => [
                'label' => $child->t('label'),
                'href' => $child->href(),
            ])
            ->all();
    }

    /**
     * Menude kullanilan kategoriler, slug'a gore.
     *
     * Her menu ogesi icin ayri sorgu acmak yerine tek seferde yuklenir;
     * baslik her sayfada cizildigi icin bu fark her istekte hissedilir.
     *
     * @return array<string, Category>
     */
    private static function categoryIndex(): array
    {
        if (self::$categoryIndex !== null) {
            return self::$categoryIndex;
        }

        return self::$categoryIndex = Category::query()
            ->with('activeChildren')
            ->get()
            ->keyBy('slug')
            ->all();
    }

    /** Testlerde ve kategori degisikliklerinden sonra tazelemek icin. */
    public static function forgetCategoryIndex(): void
    {
        self::$categoryIndex = null;
    }

    /** @var array<string, Category>|null */
    private static ?array $categoryIndex = null;

    /** Menu ogesinin hedef adresi. */
    public function href(): string
    {
        return match ($this->type) {
            'category' => localized_route('category', ['category' => $this->target_slug]),
            'page' => localized_route('page', ['page' => $this->target_slug]),
            'shop' => localized_route('shop'),
            'blog' => localized_route('blog'),
            'contact' => localized_route('contact'),
            default => $this->url ?: '#',
        };
    }
}
