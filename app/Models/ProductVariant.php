<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasTranslations;

    protected $guarded = [];

    /** @var array<int, string> */
    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'options' => 'array',
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Bos secenekleri at.
     *
     * Panelde secilmeyen eksen `null` olarak gelir; saklanirsa varyant
     * "iki secenekli" sayilir ve musterinin secimi hicbir zaman eslesmez.
     */
    protected function setOptionsAttribute(mixed $value): void
    {
        $options = is_array($value) ? array_filter($value, fn ($v) => $v !== null && $v !== '') : null;

        $this->attributes['options'] = $options ? json_encode($options, JSON_UNESCAPED_UNICODE) : null;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Secim bu varyanti tarif ediyor mu?
     *
     * Karsilastirma varyantin TUM secenekleri uzerinden yapilir; eksik secim
     * eslesmez, yoksa "Ceviz" secildiginde iki olcuden hangisi oldugu belirsiz
     * kalirdi.
     *
     * @param  array<string, string>  $selection
     */
    public function matches(array $selection): bool
    {
        $options = $this->options ?? [];

        if (count($selection) !== count($options)) {
            return false;
        }

        foreach ($options as $attributeSlug => $valueSlug) {
            if (($selection[$attributeSlug] ?? null) !== $valueSlug) {
                return false;
            }
        }

        return true;
    }

    /**
     * Gorunen ad: yonetici elle bir ad yazmadiysa seceneklerden uretilir
     * ("Ceviz / 3'lu"). Sepette ve siparis dokumunde bu ad gorunur.
     */
    public function label(?string $locale = null): string
    {
        $name = trim((string) $this->t('name', $locale));

        if ($name !== '') {
            return $name;
        }

        $options = $this->options ?? [];

        if ($options === []) {
            return '';
        }

        $dictionary = AttributeValue::dictionary();
        $parts = [];

        foreach ($options as $attributeSlug => $valueSlug) {
            $value = $dictionary[$attributeSlug][$valueSlug] ?? null;

            // Secenek sonradan silinmisse ham slug'i gostermek bos birakmaktan iyidir
            $parts[] = $value ? $value->t('name', $locale) : $valueSlug;
        }

        return implode(' / ', $parts);
    }

    /** Varyantin kendi fiyati yoksa urunun fiyati gecerlidir. */
    public function effectivePrice(): float
    {
        return (float) ($this->price ?? $this->product->price);
    }

    public function isInStock(): bool
    {
        return ! $this->product->manage_stock || $this->stock > 0;
    }
}
