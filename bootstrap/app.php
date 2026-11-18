<?php

use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\NormalizeLocationContext;
use App\Http\Middleware\PreventAuthenticatedPageCaching;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => EnsureUserHasPermission::class,
            'private.nocache' => PreventAuthenticatedPageCaching::class,
            'location.context' => NormalizeLocationContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if ($request->isMethod('post') && $request->routeIs('login.store')) {
                return redirect()->route('login')->with('status', 'Sesi login telah kedaluwarsa. Silakan masuk kembali.');
            }
        });
    })->create();
