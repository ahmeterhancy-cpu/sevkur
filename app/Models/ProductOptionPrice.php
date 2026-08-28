<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bir urunde tek bir secenegin fiyata etkisi ("Kose: +2000").
 *
 * Varyant basina fiyat girmenin yerine gecer: 3 renk x 3 olcu = 9 varyant
 * icin 9 fiyat degil, olcu basina 3 kural yeter.
 */
class ProductOptionPrice extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['price_delta' => 'decimal:2'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
