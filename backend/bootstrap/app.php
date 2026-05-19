<?php

declare(strict_types=1);

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Pure Bearer-token API — no SPA cookie auth, so statefulApi() is
        // intentionally omitted. The Flutter client passes a Sanctum PAT.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
            if ($e instanceof ValidationException) {
                $status = 422;
            } elseif ($e instanceof AuthenticationException) {
                $status = 401;
            } elseif ($e instanceof NotFoundHttpException) {
                $status = 404;
            }

            return response()->json([
                'type' => 'about:blank',
                'title' => class_basename($e),
                'status' => $status,
                'detail' => $e->getMessage(),
                'trace_id' => $request->headers->get('X-Request-Id'),
            ], $status);
        });
    })->create();
