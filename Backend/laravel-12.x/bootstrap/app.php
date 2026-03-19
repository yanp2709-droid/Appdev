<?php

use App\Http\Middleware\RequestIdMiddleware;
use App\Http\Middleware\RequireRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('api', [
            RequestIdMiddleware::class,
        ]);

        $middleware->alias([
            'role' => RequireRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, $request) {

            if (! $request->is('api/*')) {
                return null;
            }

            $requestId = $request->attributes->get('request_id');

            if (file_exists(base_path('routes/api.php'))) {
                Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'error' => [
                        'code' => 422,
                        'message' => 'Validation failed',
                        'details' => $e->errors(),
                    ],
                    'request_id' => $requestId,
                ], 422);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'error' => [
                        'code' => 401,
                        'message' => 'Unauthenticated',
                    ],
                    'request_id' => $requestId,
                ], 401);
            }

            if ($e instanceof AuthorizationException) {
                return response()->json([
                    'error' => [
                        'code' => 403,
                        'message' => 'Forbidden',
                    ],
                    'request_id' => $requestId,
                ], 403);
            }

            if ($e instanceof ModelNotFoundException) {
                return response()->json([
                    'error' => [
                        'code' => 404,
                        'message' => 'Resource not found',
                    ],
                    'request_id' => $requestId,
                ], 404);
            }

            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'error' => [
                        'code' => $e->getStatusCode(),
                        'message' => $e->getMessage() ?: 'HTTP Error',
                    ],
                    'request_id' => $requestId,
                ], $e->getStatusCode());
            }

            // Fallback 500
            return response()->json([
                'error' => [
                    'code' => 500,
                    'message' => app()->environment('local') ? $e->getMessage() : 'Internal Server Error',
                ],
                'request_id' => $requestId,
            ], 500);
        });
    })
    ->create();