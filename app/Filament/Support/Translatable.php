<?php

namespace App\Filament\Support;

use App\Support\Locales;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * Cok dilli alanlar icin sekmeli form parcalari.
 *
 * Alanlar veritabaninda JSON tutuldugundan nokta gosterimiyle
 * ("name.tr") dogrudan duzenlenebilir.
 */
class Translatable
{
    /** Tek satirlik metin (baslik, etiket…). */
    public static function text(string $field, string $label, bool $requiredDefault = true, int $maxLength = 255): Tabs
    {
        return self::tabs($field, $label, function (string $locale) use ($field, $label, $requiredDefault, $maxLength) {
            return TextInput::make("$field.$locale")
                ->label($label)
                ->maxLength($maxLength)
                ->required($requiredDefault && $locale === Locales::default());
        });
    }

    /** Cok satirli metin. */
    public static function textarea(string $field, string $label, int $rows = 3, bool $requiredDefault = false): Tabs
    {
        return self::tabs($field, $label, function (string $locale) use ($field, $label, $rows, $requiredDefault) {
            return Textarea::make("$field.$locale")
                ->label($label)
                ->rows($rows)
                ->required($requiredDefault && $locale === Locales::default());
        });
    }

    /** Uzun icerik (aciklama, sayfa govdesi). */
    public static function body(string $field, string $label, int $rows = 12): Tabs
    {
        return self::textarea($field, $label, $rows);
    }

    private static function tabs(string $field, string $label, callable $builder): Tabs
    {
        $tabs = [];

        foreach (Locales::all() as $locale) {
            $tabs[] = Tab::make(strtoupper($locale))
                ->schema([$builder($locale)]);
        }

        return Tabs::make($label)
            ->tabs($tabs)
            ->columnSpanFull();
    }
}
