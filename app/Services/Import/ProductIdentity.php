<?php

namespace App\Services\Import;

use App\Models\Product;
use Illuminate\Support\Str;

/**
 * Aktarilan urunun veritabanindaki kimligini belirler.
 *
 * Urunu SLUG ile eslestirmek veri kaybina yol acti: Sude aktariminda
 * "Armani Koltuk Takimi" slug'i Vespero'nun ayni adli urunune denk geldi ve
 * updateOrCreate o kaydi sessizce Sude'ye gecirdi — Vespero bir urun kaybetti.
 * Tedarikci siteleri ayni model adlarini kullaniyor (Milano, Roma, Torino,
 * Armani…), bu yuzden carpisma istisna degil kural.
 *
 * Kimlik artik KAYNAK ADRESI: her tedarikci sayfasi bir urun. Slug yalnizca
 * adres bilinmiyorsa uretilir ve baska markada doluysa marka eki alir.
 */
class ProductIdentity
{
    /** Ayni marka icinde bile ad tekrarlarsa kac deneme yapilir. */
    private const MAX_ATTEMPTS = 20;

    /**
     * Kaynak adresine bagli mevcut urun (varsa).
     */
    public function existing(string $sourceUrl): ?Product
    {
        return Product::where('meta->kaynak_adres', $sourceUrl)->first();
    }

    /**
     * Bu marka icin serbest bir slug.
     *
     * @param  string  $brandKey  marka slug'i — carpisma durumunda eke girer
     * @param  int|null  $ignoreId  guncellenen urunun kendi id'si
     */
    public function slug(string $name, string $brandKey, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = $brandKey.'-urun';
        }

        $candidates = [$base, $base.'-'.$brandKey];

        for ($i = 2; $i <= self::MAX_ATTEMPTS; $i++) {
            $candidates[] = $base.'-'.$brandKey.'-'.$i;
        }

        foreach ($candidates as $slug) {
            if (! $this->taken($slug, $ignoreId)) {
                return $slug;
            }
        }

        // Buraya dusmek icin ayni adin yirmi kez tekrarlamasi gerekir;
        // yine de kaybetmemek icin benzersiz bir ek konur.
        return $base.'-'.$brandKey.'-'.Str::random(6);
    }

    private function taken(string $slug, ?int $ignoreId): bool
    {
        return Product::where('slug', $slug)
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }
}
