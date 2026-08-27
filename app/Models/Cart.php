<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $guarded = [];

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): ?Coupon
    {
        if (! $this->coupon_code) {
            return null;
        }

        return Coupon::where('code', $this->coupon_code)->first();
    }

    public function itemCount(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function subtotal(): float
    {
        return (float) $this->items->sum(fn (CartItem $item) => $item->lineTotal());
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }
}
