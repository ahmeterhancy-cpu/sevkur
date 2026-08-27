<?php

use App\Models\Setting;
use App\Services\CurrencyService;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

if (! function_exists('current_locale')) {
    function current_locale(): string
    {
        return app()->getLocale();
    }
}

if (! function_exists('localized_route')) {
    /**
     * Gecerli (ya da verilen) dilin rotasini cozer.
     * Rotalar "tr.shop", "en.shop" gibi adlarla kayitli.
     */
    function localized_route(string $key, array $parameters = [], ?string $locale = null, bool $absolute = true): string
    {
        $locale = $locale && Locales::isSupported($locale) ? $locale : current_locale();
        $name = $locale.'.'.$key;

        if (! Route::has($name)) {
            $name = Locales::default().'.'.$key;
        }

        return route($name, $parameters, $absolute);
    }
}

if (! function_exists('locale_switch_url')) {
    /**
     * Ayni sayfanin baska dildeki adresi. Rota adi cozulemezse
     * o dilin ana sayfasina duser.
     */
    function locale_switch_url(string $locale): string
    {
        $route = Route::current();

        if (! $route || ! $route->getName()) {
            return localized_route('home', [], $locale);
        }

        $key = preg_replace('/^[a-z]{2}\./', '', $route->getName());
        $name = $locale.'.'.$key;

        if (! Route::has($name)) {
            return localized_route('home', [], $locale);
        }

        $parameters = $route->parameters();

        // Model baglamalarini yeniden rota anahtarina cevir.
        foreach ($parameters as $paramKey => $value) {
            if ($value instanceof Model) {
                $parameters[$paramKey] = $value->getRouteKey();
            }
        }

        try {
            return route($name, $parameters + request()->query());
        } catch (Throwable) {
            return localized_route('home', [], $locale);
        }
    }
}

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('contrast_scrim')) {
    /**
     * Slider/afis gorseli uzerine binen yazinin okunabilir kalmasi icin
     * yumusak bir perde. Yazi koyuysa beyaz, aciksa siyah perde uretilir;
     * yon, yazinin hizasina gore secilir.
     *
     * Fotograf yerine duz renk paneller kullanildiginda da zarar vermez.
     */
    function contrast_scrim(?string $textColor = '#111111', string $align = 'left'): string
    {
        $hex = ltrim((string) $textColor, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        $rgb = strlen($hex) === 6 ? array_map('hexdec', str_split($hex, 2)) : [17, 17, 17];
        $luminance = ($rgb[0] * 0.299 + $rgb[1] * 0.587 + $rgb[2] * 0.114) / 255;

        // Acik yazi -> koyu perde, koyu yazi -> acik perde
        $shades = $luminance > 0.6
            ? ['from-black/70', 'via-black/40']
            : ['from-white/85', 'via-white/55'];

        $direction = match ($align) {
            'right' => 'bg-gradient-to-l',
            'center' => 'bg-gradient-to-t',
            default => 'bg-gradient-to-r',
        };

        return $direction.' '.$shades[0].' '.$shades[1].' to-transparent';
    }
}

if (! function_exists('setting_t')) {
    /**
     * Dile gore ayar okur: once "anahtar_bg", yoksa "anahtar", o da yoksa
     * verilen varsayilan. Site basligi/aciklamasi gibi SEO metinleri her dilde
     * ayri girilebilsin diye.
     */
    function setting_t(string $key, mixed $default = null): mixed
    {
        $locale = current_locale();

        $localised = Setting::get($key.'_'.$locale);

        if (is_string($localised) && trim($localised) !== '') {
            return $localised;
        }

        return Setting::get($key, $default);
    }
}

if (! function_exists('money')) {
    /**
     * TRY cinsinden tutari, ziyaretcinin secili para biriminde bicimler.
     */
    function money(float|int|string|null $amountInTry, ?string $currencyCode = null): string
    {
        return app(CurrencyService::class)->format((float) $amountInTry, $currencyCode);
    }
}

if (! function_exists('money_base')) {
    /** Tutari her zaman TRY olarak bicimler (yonetim / fatura ekranlari). */
    function money_base(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 2, ',', '.').' ₺';
    }
}

if (! function_exists('media_url')) {
    /**
     * Yuklenen dosyanin genel adresi. Dis baglantilar oldugu gibi doner,
     * dosya yoksa yer tutucu gorsel kullanilir.
     */
    function media_url(?string $path, string $fallback = 'images/placeholder.svg'): string
    {
        if (! $path) {
            return asset($fallback);
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'images/') || str_starts_with($path, 'demo/')) {
            return asset($path);
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}
