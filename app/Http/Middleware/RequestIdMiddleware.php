<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;

class RequestIdMiddleware
{
    public function handle($request, Closure $next)
    {
        $requestId = Str::uuid()->toString();

        // Store in request for later use
        $request->attributes->set('request_id', $requestId);

        $response = $next($request);

        // Attach to response header
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}