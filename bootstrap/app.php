<?php

use App\Http\Middleware\MaintenanceMode;
use App\Http\Middleware\SecurityHeaders;
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
        // After the session, so that it knows an administrator when it sees one.
        $middleware->web(append: MaintenanceMode::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
