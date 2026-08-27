<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'payment_payload' => 'array',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'display_rate' => 'decimal:6',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->latest();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function customerName(): string
    {
        return trim($this->customer_first_name.' '.$this->customer_last_name);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'processing'], true);
    }

    public function log(string $type, ?string $message = null, array $payload = []): void
    {
        $this->events()->create([
            'type' => $type,
            'message' => $message,
            'payload' => $payload ?: null,
        ]);
    }

    public static function generateNumber(): string
    {
        $prefix = config('shop.order.number_prefix', 'SVK');

        do {
            $number = $prefix.'-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
        } while (static::where('number', $number)->exists());

        return $number;
    }
}
