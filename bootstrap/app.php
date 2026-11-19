<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(__DIR__.'/../routes/channels.php', ['middleware' => ['web', 'auth']])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\EnsureAccountAvailable::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\EnsureLegalAcceptance::class,
            \App\Http\Middleware\EnsureProfileComplete::class,
        ]);
        $middleware->api(append: [\App\Http\Middleware\EnsureAccountAvailable::class, \App\Http\Middleware\EnsureProfileComplete::class]);

        $middleware->alias([
            'verified.email' => \App\Http\Middleware\RequireVerifiedEmail::class,
            'page.available' => \App\Http\Middleware\EnsurePageAvailable::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $exception, Request $request) {
            $status = $response->getStatusCode();
            if ($request->is('api/*') || ($request->expectsJson() && !$request->header('X-Inertia'))
                || !in_array($status, [403, 404, 419, 429, 500, 503], true)
                || ($status === 500 && config('app.debug'))) {
                return $response;
            }

            $headers = $response->headers->allPreserveCaseWithoutCookies();
            unset($headers['Content-Type'], $headers['Content-Length']);
            return \App\Support\ErrorPage::response($request, $status, headers: $headers);
        });
    })->create();
