<?php

use App\Http\Controllers\InstallController;
use App\Http\Middleware\CountVisit;
use App\Http\Middleware\MaintenanceMode;
use App\Http\Middleware\RunDailyWorkOnVisits;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Setting the site up from a browser (App\Http\Controllers\InstallController): outside the "web" middleware,
        // because before the tables exist there is no session to start.
        then: function () {
            Route::get('/install', [InstallController::class, 'show']);
            Route::post('/install', [InstallController::class, 'run']);
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // On every response, the panel's included.
        $middleware->append(SecurityHeaders::class);
        // Where the server has no scheduler: the day's work after a page has been sent (SCHEDULE_ON_VISITS; App\Support\WebCron).
        $middleware->append(RunDailyWorkOnVisits::class);
        // Someone not signed in, at an address only for staff on the site itself (the bell's): to the panel's sign-in.
        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));
        // The visitor's language first, so that the closed page is in it; that one runs after the session, so that it knows an administrator when it sees one.
        $middleware->web(append: [SetLocale::class, MaintenanceMode::class, CountVisit::class]);
        // The language cookie is read as it is; it only ever holds "th" or "en".
        $middleware->encryptCookies(except: [SetLocale::COOKIE]);
        // Behind a proxy that says the visitor came by https (a Cloudflare tunnel, a load balancer): TRUSTED_PROXIES="*".
        if ($proxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : explode(',', $proxies));
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
