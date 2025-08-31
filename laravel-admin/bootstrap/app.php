<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'custom.auth' => \App\Http\Middleware\CustomAuthMiddleware::class,
        ]);
        
        // Exclude SOAP endpoints from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'soap/pck',
            'soap/pck/*',
            'wsdl/*',
            'pck/health',
            'pck/tenant/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
