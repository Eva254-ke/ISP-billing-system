<?php

use App\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withSingletons([
        \Illuminate\Contracts\Console\Kernel::class => ConsoleKernel::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
        ]);

        // Captive-portal browsers can lose cookies between the package page
        // and anonymous payment POSTs, so keep these public WiFi POSTs stateless.
        $middleware->validateCsrfTokens(except: [
            'wifi/pay',
            'wifi/reconnect',
            'wifi/extend',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
