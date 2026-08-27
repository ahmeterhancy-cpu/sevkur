<?php

namespace App\Concerns;

use App\Support\Locales;

/**
 * Cok dilli alanlar icin hafif cozum.
 *
 * Ceviri alanlari veritabaninda JSON tutulur: {"tr":"Koltuk","en":"Sofa"}.
 * Model uzerinde alan HAM dizi olarak kalir (Filament formlari name.tr /
 * name.en gibi nokta gosterimiyle dogrudan duzenleyebilsin diye), okumak
 * icin $model->t('name') kullanilir.
 */
trait HasTranslations
{
    /**
     * Istenen dildeki degeri dondurur; yoksa varsayilan dile, o da yoksa
     * dolu olan ilk dile duser.
     */
    public function t(string $key, ?string $locale = null): string
    {
        $values = $this->translations($key);

        if ($values === []) {
            return '';
        }

        $chain = array_unique(array_filter([
            $locale ?: app()->getLocale(),
            Locales::default(),
            config('app.fallback_locale'),
        ]));

        foreach ($chain as $candidate) {
            $value = $values[$candidate] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * Alanin tum dillerdeki degerleri.
     *
     * @return array<string, string>
     */
    public function translations(string $key): array
    {
        $raw = $this->getAttribute($key);

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [Locales::default() => $raw];
        }

        return is_array($raw) ? $raw : [];
    }

    public function setTranslation(string $key, string $locale, ?string $value): static
    {
        $values = $this->translations($key);
        $values[$locale] = $value;
        $this->setAttribute($key, $values);

        return $this;
    }

    /**
     * Tum diller icin ayni degeri yazmadan once eksik dilleri varsayilanla doldurur.
     *
     * @param  array<string, string>  $values
     */
    public static function trans(array $values): array
    {
        return $values;
    }

    /**
     * @return array<int, string>
     */
    public function translatableAttributes(): array
    {
        return $this->translatable ?? [];
    }
}
