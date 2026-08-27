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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
