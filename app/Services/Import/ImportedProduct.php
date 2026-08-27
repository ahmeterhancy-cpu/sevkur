<?php

namespace App\Services\Import;

/**
 * Uzak bir sayfadan okunan urun bilgileri.
 *
 * Henuz veritabanina yazilmamis, yalnizca ayristirilmis veridir; yonetici
 * panelde duzenledikten sonra kaydeder.
 */
class ImportedProduct
{
    /**
     * @param  array<int, string>  $images  Mutlak gorsel adresleri
     * @param  array<int, string>  $notes  Yoneticiye gosterilecek uyarilar
     */
    public function __construct(
        public string $sourceUrl,
        public ?string $name = null,
        public ?string $shortDescription = null,
        public ?string $description = null,
        public ?string $sku = null,
        public ?float $price = null,
        public ?float $compareAtPrice = null,
        public ?string $currency = null,
        public ?string $brand = null,
        public array $images = [],
        public array $notes = [],
    ) {}

    public function hasEnough(): bool
    {
        return $this->name !== null && trim($this->name) !== '';
    }

    /** @return array<string, mixed> Filament formunu doldurmak icin. */
    public function toFormState(): array
    {
        return [
            'source_url' => $this->sourceUrl,
            'name' => $this->name,
            'short_description' => $this->shortDescription,
            'description' => $this->description,
            'sku' => $this->sku,
            'price' => $this->price,
            'compare_at_price' => $this->compareAtPrice,
            'currency' => $this->currency,
            'brand' => $this->brand,
            'images' => $this->images,
            'selected_images' => array_slice($this->images, 0, 5),
        ];
    }
}
