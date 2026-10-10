<?php

use App\Http\Middleware\CountVisit;
use App\Http\Middleware\MaintenanceMode;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // On every response, the panel's included.
        $middleware->append(SecurityHeaders::class);
        // The visitor's language first, so that the closed page is in it; that one runs after the session, so that it knows an administrator when it sees one.
        $middleware->web(append: [SetLocale::class, MaintenanceMode::class, CountVisit::class]);
        // The language cookie is read as it is; it only ever holds "th" or "en".
        $middleware->encryptCookies(except: [SetLocale::COOKIE]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
