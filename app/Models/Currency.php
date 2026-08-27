<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasTranslations;

    protected $guarded = [];

    /** @var array<int, string> */
    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'rate' => 'decimal:6',
            'manual_rate' => 'decimal:6',
            'margin_percent' => 'decimal:2',
            'auto_update' => 'boolean',
            'is_base' => 'boolean',
            'is_active' => 'boolean',
            'rate_updated_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('position');
    }

    /**
     * 1 TRY karsiligi bu para biriminden kac birim eder.
     * Manuel kur girildiyse otomatik kuru ezer; marj yuzdesi her ikisine de uygulanir.
     */
    public function effectiveRate(): float
    {
        if ($this->is_base) {
            return 1.0;
        }

        $rate = (float) ($this->manual_rate ?? $this->rate);
        $margin = (float) $this->margin_percent;

        if ($margin !== 0.0) {
            $rate = $rate * (1 + $margin / 100);
        }

        return $rate > 0 ? $rate : 1.0;
    }

    public function convertFromBase(float $amountInTry): float
    {
        return round($amountInTry * $this->effectiveRate(), $this->decimals);
    }

    public function format(float $amountInTry): string
    {
        $value = $this->convertFromBase($amountInTry);

        $formatted = number_format(
            $value,
            $this->decimals,
            $this->code === 'TRY' ? ',' : ',',
            $this->code === 'TRY' ? '.' : '.'
        );

        return $this->position_symbol === 'before'
            ? $this->symbol.$formatted
            : $formatted.' '.$this->symbol;
    }
}
