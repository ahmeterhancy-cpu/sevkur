<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AttributeValue extends Model
{
    use HasTranslations;

    protected $guarded = [];

    /** @var array<int, string> */
    public array $translatable = ['name'];

    protected function casts(): array
    {
        return ['name' => 'array'];
    }

    /**
     * Istek boyunca gecerli "nitelik slug -> deger slug -> deger" sozlugu.
     *
     * Varyant etiketleri sepette her satir icin cozuluyor; sozluk olmadan her
     * satir ayri sorgu acardi. Seceneklerin sayisi az oldugu icin tek seferde
     * yuklemek en ucuzu.
     *
     * @return array<string, array<string, AttributeValue>>
     */
    public static function dictionary(): array
    {
        if (self::$dictionary !== null) {
            return self::$dictionary;
        }

        $dictionary = [];

        foreach (self::with('attribute')->get() as $value) {
            $attributeSlug = $value->attribute?->slug;

            if ($attributeSlug !== null) {
                $dictionary[$attributeSlug][$value->slug] = $value;
            }
        }

        return self::$dictionary = $dictionary;
    }

    /** Testlerde ve seeder sonrasinda sozlugu tazelemek icin. */
    public static function forgetDictionary(): void
    {
        self::$dictionary = null;
    }

    /** @var array<string, array<string, AttributeValue>>|null */
    private static ?array $dictionary = null;

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
