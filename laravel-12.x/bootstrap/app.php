<?php

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
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
         $middleware->appendToGroup('api', [
        \App\Http\Middleware\RequestIdMiddleware::class,
    ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
       
        $exceptions->render(function (Throwable $e, $request) {

            // Only format API routes
        if (! $request->is('api/*')) {
            return null;
        }

        $requestId = $request->attributes->get('request_id');

        // 422 - Validation Error
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

        // 401 - Authentication
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'error' => [
                    'code' => 401,
                    'message' => 'Unauthenticated',
                ],
                'request_id' => $requestId,
            ], 401);
        }

        // 403 - Authorization
        if ($e instanceof AuthorizationException) {
            return response()->json([
                'error' => [
                    'code' => 403,
                    'message' => 'Forbidden',
                ],
                'request_id' => $requestId,
            ], 403);
        }

        // 404 - Model Not Found
        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Resource not found',
                ],
                'request_id' => $requestId,
            ], 404);
        }

        // Other HTTP errors
        if ($e instanceof HttpExceptionInterface) {
            return response()->json([
                'error' => [
                    'code' => $e->getStatusCode(),
                    'message' => $e->getMessage() ?: 'HTTP Error',
                ],
                'request_id' => $requestId,
            ], $e->getStatusCode());
        }

        // 500 - Default fallback
        return response()->json([
            'error' => [
                'code' => 500,
                'message' => app()->environment('local')
                    ? $e->getMessage()
                    : 'Internal Server Error',
            ],
            'request_id' => $requestId,
        ], 500);
    });
    })->create();
