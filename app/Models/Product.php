<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $guarded = [];

    /** @var array<int, string> */
    public array $translatable = ['name', 'short_description', 'description'];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'short_description' => 'array',
            'description' => 'array',
            'specs' => 'array',
            'meta' => 'array',
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'manage_stock' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'is_trending' => 'boolean',
            'sale_ends_at' => 'datetime',
            'published_at' => 'datetime',
            'rating_avg' => 'float',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ---------------------------------------------------------------- ilişkiler

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function variants(): HasMany
    {
        // chaperone(): her varyanta kendi urununu geri baglar. Olmazsa
        // varyant fiyati hesaplanirken $variant->product her seferinde
        // yeniden sorgulanir (N+1).
        return $this->hasMany(ProductVariant::class)->orderBy('position')->chaperone();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true)->latest();
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class);
    }

    public function related(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'product_related', 'product_id', 'related_id');
    }

    public function optionPrices(): HasMany
    {
        return $this->hasMany(ProductOptionPrice::class);
    }

    // ----------------------------------------------------------------- varyant

    /**
     * Secenek fiyat farklari: ["olcu" => ["kose" => 2000.0], ...]
     *
     * @return array<string, array<string, float>>
     */
    public function optionDeltas(): array
    {
        $map = [];

        foreach ($this->optionPrices as $rule) {
            $map[$rule->attribute_slug][$rule->value_slug] = (float) $rule->price_delta;
        }

        return $map;
    }

    /**
     * Varyantlarin dayandigi secenek eksenleri: Renk, Olcu gibi.
     *
     * Eksenler varyantlarin kendi `options` alanindan turetilir; boylece bir
     * urune hangi seceneklerin sunuldugu ayri bir tabloda tutulmak zorunda
     * kalmaz. Donen sira yoneticinin belirledigi sirayla ayni olur.
     *
     * @return array<int, array{attribute: Attribute, values: array<int, AttributeValue>}>
     */
    public function variantAxes(): array
    {
        $used = [];

        foreach ($this->variants as $variant) {
            foreach ($variant->options ?? [] as $attributeSlug => $valueSlug) {
                $used[$attributeSlug][$valueSlug] = true;
            }
        }

        if ($used === []) {
            return [];
        }

        $attributes = Attribute::with('values')
            ->whereIn('slug', array_keys($used))
            ->orderBy('position')
            ->get();

        $axes = [];

        foreach ($attributes as $attribute) {
            $values = $attribute->values
                ->filter(fn (AttributeValue $value) => isset($used[$attribute->slug][$value->slug]))
                ->values()
                ->all();

            if ($values !== []) {
                $axes[] = ['attribute' => $attribute, 'values' => $values];
            }
        }

        return $axes;
    }

    /** Secilen seceneklere karsilik gelen varyant; yoksa null. */
    public function findVariantByOptions(array $selection): ?ProductVariant
    {
        $selection = array_filter($selection, fn ($value) => $value !== null && $value !== '');

        if ($selection === []) {
            return null;
        }

        return $this->variants->first(fn (ProductVariant $variant) => $variant->matches($selection));
    }

    /**
     * Tarayicinin fiyat/stok guncellemesi icin varyant tablosu.
     *
     * @return array<int, array<string, mixed>>
     */
    public function variantMap(): array
    {
        return $this->variants->map(fn (ProductVariant $variant) => [
            'id' => $variant->id,
            'options' => $variant->options ?? [],
            'price' => $variant->effectivePrice(),
            'price_label' => money($variant->effectivePrice()),
            'in_stock' => $variant->isInStock(),
            'image' => $variant->image ? media_url($variant->image) : null,
        ])->all();
    }
    // ------------------------------------------------------------------ kapsam

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('manage_stock', false)->orWhere('stock', '>', 0);
        });
    }

    // --------------------------------------------------------------- yardımcılar

    /** Vitrinde gosterilen guncel fiyat (TRY). */
    public function getFinalPriceAttribute(): float
    {
        return (float) $this->price;
    }

    public function isOnSale(): bool
    {
        if ($this->compare_at_price === null) {
            return false;
        }

        if ($this->sale_ends_at !== null && $this->sale_ends_at->isPast()) {
            return false;
        }

        return (float) $this->compare_at_price > (float) $this->price;
    }

    public function getDiscountPercentAttribute(): int
    {
        if (! $this->isOnSale()) {
            return 0;
        }

        $old = (float) $this->compare_at_price;

        return (int) round((($old - (float) $this->price) / $old) * 100);
    }

    public function isInStock(): bool
    {
        return ! $this->manage_stock || $this->stock > 0;
    }

    public function isLowStock(): bool
    {
        return $this->manage_stock && $this->stock > 0 && $this->stock <= $this->stock_alert;
    }

    /** Sayac gosterilecek mi (Furnob tarzi "kalan sure" bloklari). */
    public function hasCountdown(): bool
    {
        return $this->isOnSale() && $this->sale_ends_at !== null && $this->sale_ends_at->isFuture();
    }

    /** @return array<int, string> Galeri: kapak + ek gorseller. */
    public function gallery(): array
    {
        $paths = [];

        if ($this->cover) {
            $paths[] = $this->cover;
        }

        foreach ($this->images as $image) {
            $paths[] = $image->path;
        }

        return array_values(array_unique(array_filter($paths)));
    }

    public function refreshRating(): void
    {
        $stats = $this->reviews()->where('is_approved', true)
            ->selectRaw('COUNT(*) as c, COALESCE(AVG(rating), 0) as a')
            ->first();

        $this->forceFill([
            'rating_count' => (int) ($stats->c ?? 0),
            'rating_avg' => round((float) ($stats->a ?? 0), 2),
        ])->saveQuietly();
    }
}
