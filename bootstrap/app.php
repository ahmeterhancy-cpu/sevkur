<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'locale' => SetLocale::class,
        ]);

        // PayTR bildirim ucu disaridan POST edilir, CSRF disi tutulur.
        $middleware->validateCsrfTokens(except: [
            'paytr/callback',
        ]);

        // Giris sayfasi dile gore degistigi icin adres calisma aninda cozulur.
        $middleware->redirectGuestsTo(fn () => localized_route('login'));
        $middleware->redirectUsersTo(fn () => localized_route('account.index'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
