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

if (! function_exists('whatsapp_url')) {
    /**
     * Ayarlardaki numaradan wa.me bagi uretir.
     *
     * Numara "0536 567 85 48" gibi yerel bicimde giriliyor; wa.me ulke kodlu
     * ve yalin rakam istiyor. Basindaki 0 atilip 90 ekleniyor.
     */
    function whatsapp_url(?string $message = null): ?string
    {
        $raw = (string) setting('whatsapp_number');
        $digits = preg_replace('/\D+/', '', $raw);

        if ($digits === '' || $digits === null) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '90'.substr($digits, 1);
        }

        $url = 'https://wa.me/'.$digits;

        // Katalogda fiyat yok; musteri "fiyat sorun" diyerek geliyor. Hangi
        // urun icin yazdigini elle yazmak zorunda kalmasin.
        if ($message !== null && trim($message) !== '') {
            $url .= '?text='.rawurlencode(trim($message));
        }

        return $url;
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

if (! function_exists('whatsapp_message')) {
    /**
     * WhatsApp mesaj sablonu.
     *
     * Once yonetimden girilen metin, yoksa dil dosyasindaki varsayilan.
     * Magaza sahibi ifadeyi kendi diliyle degistirebilsin diye ayardan
     * okunuyor; :product yer tutucusu her iki kaynakta da gecerli.
     *
     * @param  'product'|'general'  $type
     * @param  array<string, string>  $replace
     */
    function whatsapp_message(string $type, array $replace = []): string
    {
        $custom = setting_t('whatsapp_'.$type.'_message');

        if (is_string($custom) && trim($custom) !== '') {
            return strtr($custom, array_combine(
                array_map(fn (string $k) => ':'.$k, array_keys($replace)),
                array_values($replace)
            ));
        }

        return __('shop.whatsapp_'.$type, $replace);
    }
}

if (! function_exists('ask_price_url')) {
    /**
     * "Fiyat sorun" dugmesinin hedefi.
     *
     * Katalogda fiyat yok; bu dugme musterinin ana eylemi. WhatsApp numarasi
     * tanimliysa dogrudan sohbet acilir ve urun adi mesaja hazir yazilir —
     * musteri hangi urun icin sordugunu anlatmak zorunda kalmaz. Numara
     * girilmemisse dugme olu kalmasin diye iletisim sayfasina duser.
     */
    function ask_price_url(string $productName): string
    {
        if (! setting('ask_price_whatsapp', true)) {
            return localized_route('contact');
        }

        $url = whatsapp_url(whatsapp_message('product', ['product' => $productName]));

        return $url ?? localized_route('contact');
    }
}

if (! function_exists('ask_price_is_whatsapp')) {
    /** Dugme WhatsApp aciyor mu? Ikon ve target bu bilgiye gore degisir. */
    function ask_price_is_whatsapp(): bool
    {
        return setting('ask_price_whatsapp', true) && whatsapp_url() !== null;
    }
}

if (! function_exists('panel_ink')) {
    /**
     * Krem pano uzerinde okunacak yazi rengi.
     *
     * Slayt metni artik fotografin UZERINDE degil, yanindaki krem panoda
     * duruyor. Yoneticide acik renk (ornegin beyaz) secilmis bir slaytta
     * yazi panoda kaybolurdu; acik renkler murekkebe dusuruluyor, koyu
     * renkler oldugu gibi birakiliyor.
     */
    function panel_ink(?string $textColor): string
    {
        $hex = ltrim((string) $textColor, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6) {
            return 'var(--color-ink)';
        }

        [$r, $g, $b] = array_map('hexdec', str_split($hex, 2));
        $luminance = ($r * 0.299 + $g * 0.587 + $b * 0.114) / 255;

        return $luminance > 0.55 ? 'var(--color-ink)' : '#'.$hex;
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

        // Tum medya 'medya' diskinde, yani public/ altinda durur.
        // Eski kayitlarda kalmis 'storage/...' onekli yollar da calissin.
        return asset(ltrim($path, '/'));
    }
}
