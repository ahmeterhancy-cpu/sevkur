<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Rota grubundan gelen dili uygulamaya uygular.
     * Ornek: ->middleware('locale:bg')
     */
    public function handle(Request $request, Closure $next, ?string $locale = null): Response
    {
        $locale = Locales::isSupported($locale) ? $locale : Locales::default();

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);

        $config = config("shop.locales.$locale");

        if (! empty($config['iso'])) {
            setlocale(LC_TIME, $config['iso'].'.UTF-8', $config['iso']);
        }

        return $next($request);
    }
}
