<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_total' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'free_shipping' => 'boolean',
        ];
    }

    /**
     * Kuponun su an ve verilen sepet tutari icin gecerli olup olmadigi.
     * Gecersizse sebebi anahtar olarak doner, gecerliyse null.
     */
    public function invalidReason(float $subtotal, ?int $userId = null): ?string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'not_started';
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'expired';
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return 'used_up';
        }

        if ($this->min_total !== null && $subtotal < (float) $this->min_total) {
            return 'min_total';
        }

        if ($userId && $this->max_uses_per_user !== null) {
            $used = Order::where('user_id', $userId)
                ->where('coupon_code', $this->code)
                ->whereIn('payment_status', ['paid', 'pending'])
                ->count();

            if ($used >= $this->max_uses_per_user) {
                return 'used_up';
            }
        }

        return null;
    }

    public function discountFor(float $subtotal): float
    {
        $discount = $this->type === 'percent'
            ? $subtotal * ((float) $this->value / 100)
            : (float) $this->value;

        return round(min($discount, $subtotal), 2);
    }
}
