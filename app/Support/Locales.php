<?php

namespace App\Support;

class Locales
{
    /** @return array<int, string> */
    public static function all(): array
    {
        return array_keys(config('shop.locales'));
    }

    public static function default(): string
    {
        return config('shop.default_locale', 'tr');
    }

    public static function isSupported(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, config('shop.locales'));
    }

    /** @return array<string, string> */
    public static function nativeNames(): array
    {
        return array_map(fn (array $l) => $l['native'], config('shop.locales'));
    }

    public static function native(string $locale): string
    {
        return config("shop.locales.$locale.native", strtoupper($locale));
    }

    public static function currencyFor(string $locale): string
    {
        return config("shop.locales.$locale.currency", config('shop.base_currency'));
    }

    /**
     * Verilen rota anahtarinin dile gore yol parcasi.
     */
    public static function segment(string $key, string $locale): string
    {
        return config("shop.segments.$key.$locale")
            ?? config('shop.segments.'.$key.'.'.self::default())
            ?? $key;
    }

    /**
     * Varsayilan dil on ek almaz.
     */
    public static function prefix(string $locale): string
    {
        return $locale === self::default() ? '' : $locale;
    }
}
