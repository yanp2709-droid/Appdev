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
use Throwable;


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

            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'validation_error',
                        'message' => 'Validation failed',
                        'details' => $e->errors(),
                    ],
                    'request_id' => $requestId,
                ], 422);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'unauthorized',
                        'message' => 'Unauthenticated',
                    ],
                    'request_id' => $requestId,
                ], 401);
            }

            if ($e instanceof AuthorizationException) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'forbidden',
                        'message' => 'Forbidden',
                    ],
                    'request_id' => $requestId,
                ], 403);
            }

            if ($e instanceof ModelNotFoundException) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'not_found',
                        'message' => 'Resource not found',
                    ],
                    'request_id' => $requestId,
                ], 404);
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => $status === 404 ? 'not_found' : 'http_error',
                        'message' => $e->getMessage() ?: 'HTTP Error',
                    ],
                    'request_id' => $requestId,
                ], $status);
            }

            // Fallback 500
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'server_error',
                    'message' => app()->environment('local') ? $e->getMessage() : 'Internal Server Error',
                ],
                'request_id' => $requestId,
            ], 500);
        });
    })
    ->create();
