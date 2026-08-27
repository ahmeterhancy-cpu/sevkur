<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->generateVariantsAction(),
            DeleteAction::make(),
        ];
    }

    /**
     * Secilen renk ve olculerin TUM kombinasyonlarini varyant olarak acar.
     *
     * 3 renk x 3 olcu = 9 satiri elle girmek hem yorucu hem de bir
     * kombinasyonu atlamaya acik. Var olan varyantlara dokunulmaz; yalnizca
     * eksik kombinasyonlar eklenir, boylece islem tekrar calistirilabilir.
     */
    private function generateVariantsAction(): Action
    {
        return Action::make('kombinasyon_uret')
            ->label('Kombinasyon üret')
            ->icon('heroicon-o-squares-plus')
            ->modalHeading('Seçeneklerden varyant üret')
            ->modalDescription('İşaretlediğiniz değerlerin bütün kombinasyonları varyant olarak eklenir. '
                .'Zaten var olan kombinasyonlar korunur.')
            ->modalSubmitActionLabel('Üret')
            ->schema(fn (): array => [
                ...self::axisFields(),

                TextInput::make('stock')
                    ->label('Her varyant için başlangıç stoğu')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
            ])
            ->action(function (array $data): void {
                $created = $this->generateVariants($data);

                Notification::make()
                    ->title($created === 0 ? 'Yeni kombinasyon yok' : $created.' varyant eklendi')
                    ->body($created === 0
                        ? 'Seçtiğiniz kombinasyonların hepsi zaten mevcut.'
                        : 'Fiyat ve stokları kontrol edip kaydedin.')
                    ->success()
                    ->send();

                $this->fillForm();
            });
    }

    /**
     * Varyant eksenlerinin coklu seciciye donusmus hali.
     *
     * @return array<int, Select>
     */
    private static function axisFields(): array
    {
        return Attribute::with('values')
            ->where('is_variant', true)
            ->orderBy('position')
            ->get()
            ->map(fn (Attribute $attribute) => Select::make('eksen.'.$attribute->slug)
                ->label($attribute->t('name'))
                ->multiple()
                ->options(
                    $attribute->values
                        ->mapWithKeys(fn (AttributeValue $value) => [$value->slug => $value->t('name')])
                        ->all()
                ))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return int Eklenen varyant sayisi
     */
    private function generateVariants(array $data): int
    {
        /** @var Product $product */
        $product = $this->getRecord();

        // Bos eksenler kombinasyona girmez: yalnizca renk secildiyse
        // varyantlar tek eksenli olur.
        $axes = array_filter($data['eksen'] ?? [], fn ($values) => is_array($values) && $values !== []);

        if ($axes === []) {
            return 0;
        }

        $existing = $product->variants->map(fn ($variant) => $variant->options ?? [])->all();
        $position = (int) $product->variants->max('position');
        $created = 0;

        foreach (self::combinations($axes) as $options) {
            foreach ($existing as $current) {
                if ($current == $options) {
                    continue 2;
                }
            }

            $product->variants()->create([
                'name' => [],
                'sku' => $product->sku ? $product->sku.'-'.strtoupper(substr(implode('', $options), 0, 6)) : null,
                'stock' => (int) ($data['stock'] ?? 0),
                'options' => $options,
                'position' => ++$position,
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * Kartezyen carpim: her eksenden birer deger alan tum diziler.
     *
     * @param  array<string, array<int, string>>  $axes
     * @return array<int, array<string, string>>
     */
    private static function combinations(array $axes): array
    {
        $result = [[]];

        foreach ($axes as $attributeSlug => $values) {
            $next = [];

            foreach ($result as $combination) {
                foreach ($values as $value) {
                    $next[] = $combination + [$attributeSlug => $value];
                }
            }

            $result = $next;
        }

        return $result;
    }
}
