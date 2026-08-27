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
